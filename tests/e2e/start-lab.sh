#!/usr/bin/env bash
# Disposable WordPress/Piwigo integration lab. Never connects to production.
set -Eeuo pipefail
trap 'printf "LAB_FAILURE_LINE=%s STATUS=%s\n" "$LINENO" "$?" >&2' ERR
umask 077
: "$RUNNER_TEMP" "$GITHUB_WORKSPACE" "$GITHUB_ENV"

LAB="$RUNNER_TEMP/piwigo-310-e2e"
mkdir -p "$LAB/reports" "$LAB/wordpress" "$LAB/stage/wp-piwigo-display"
echo "LAB_WRITE_ROOT=$LAB"
echo 'PIWIGO=16.4.0; WP=6.9; CI_DATA=DISPOSABLE; PRODUCTION=NONE'

# Published ports are loopback-only. Piwigo and MariaDB communicate on a
# dedicated Docker bridge; no production network or credentials are used.
docker network create wpd-310-e2e >/dev/null
docker run -d --name wpd-310-db --network wpd-310-e2e --network-alias db \
  -p 127.0.0.1:3307:3306 \
  -e MARIADB_ROOT_PASSWORD=ci-only-root \
  -e MARIADB_DATABASE=piwigo \
  -e MARIADB_USER=ci \
  -e MARIADB_PASSWORD=ci-only-db \
  mariadb:11.4 >/dev/null
DB_READY=no
for i in $(seq 1 80); do
  # mysqladmin ping also succeeds while the bootstrap server is shutting down.
  # Use an actual TCP SQL query to wait for the final MariaDB server.
  if docker exec -e MYSQL_PWD=ci-only-root wpd-310-db \
      mariadb -h 127.0.0.1 -uroot -N -e 'SELECT 1' >/dev/null 2>&1; then
    DB_READY=yes
    break
  fi
  sleep 2
done
if [[ "$DB_READY" != yes ]]; then
  echo 'DISPOSABLE_MARIADB_NOT_READY' >&2
  docker logs --tail 40 wpd-310-db >&2
  exit 3
fi
docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot -e \
  "CREATE DATABASE wordpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON wordpress.* TO 'ci'@'%';"
docker run -d --name wpd-310-piwigo --network wpd-310-e2e \
  -p 127.0.0.1:8090:80 -e TZ=UTC -e PIWIGO_UID=1000 -e PIWIGO_GID=1000 \
  piwigo/piwigo:16.4.0 >/dev/null
for i in $(seq 1 120); do
  if curl --fail --silent http://127.0.0.1:8090/install.php -o /dev/null; then break; fi
  sleep 2
done
curl --fail --silent --show-error \
  --output "$LAB/install-result.html" \
  --data-urlencode 'install=Install Piwigo' \
  --data-urlencode 'prefix=piwigo_' \
  --data-urlencode 'dbhost=db' \
  --data-urlencode 'dbuser=ci' \
  --data-urlencode 'dbpasswd=ci-only-db' \
  --data-urlencode 'dbname=piwigo' \
  --data-urlencode 'admin_name=ci_admin' \
  --data-urlencode 'admin_pass1=ci-only-piwigo' \
  --data-urlencode 'admin_pass2=ci-only-piwigo' \
  --data-urlencode 'admin_mail=ci@example.invalid' \
  http://127.0.0.1:8090/install.php?language=en_UK
docker exec wpd-310-piwigo test -f /var/www/html/piwigo/local/config/database.inc.php
curl --fail --silent --show-error \
  'http://127.0.0.1:8090/ws.php?format=json&method=pwg.getVersion' |
  jq -e '.stat == "ok"' >/dev/null
echo "PIWIGO_INSTALL=PASS"

# Piwigo 16.4.0 defaults to lounge_activate_threshold=1: all uploaded
# pictures would land in the pending-review lounge and never be attached to
# the requested album. The disposable fixture has no moderation workflow:
# raise this threshold BEFORE uploading its six synthetic public photos.
cat > "$LAB/piwigo-ci-config.php" <<'PHP'
<?php
$conf['lounge_activate_threshold'] = 100000;
PHP
docker cp "$LAB/piwigo-ci-config.php" \
  wpd-310-piwigo:/var/www/html/piwigo/local/config/config.inc.php
[[ "$(docker exec -w /var/www/html/piwigo wpd-310-piwigo php84 -r \
  'define("PHPWG_ROOT_PATH","./"); include "include/config_default.inc.php"; include "local/config/config.inc.php"; echo $conf["lounge_activate_threshold"];')" == 100000 ]] ||
  { echo 'CI lounge override was not applied' >&2; exit 5; }
echo "PIWIGO_TEST_LOUNGE_DISABLED=PASS"

COOK="$LAB/piwigo-cookies"
ws() {
  local method="$1"
  shift
  curl --fail --silent --show-error -b "$COOK" -c "$COOK" \
    --data-urlencode "method=$method" "$@" \
    'http://127.0.0.1:8090/ws.php?format=json'
}
ws pwg.session.login \
  --data-urlencode 'username=ci_admin' \
  --data-urlencode 'password=ci-only-piwigo' |
  jq -e '.stat == "ok"' >/dev/null
echo "PIWIGO_ADMIN_API=PASS"
ALBUM=$(ws pwg.categories.add \
  --data-urlencode 'name=CI Public' --data-urlencode 'status=public' |
  jq -er 'select(.stat=="ok") | .result.id')
ALIAS=$(ws pwg.categories.add \
  --data-urlencode 'name=#2024' --data-urlencode 'status=public' |
  jq -er 'select(.stat=="ok") | .result.id')
PRIVATE=$(ws pwg.categories.add \
  --data-urlencode 'name=CI Hidden' --data-urlencode 'status=private' |
  jq -er 'select(.stat=="ok") | .result.id')
echo "PIWIGO_CATEGORIES=PASS"

# Six synthetic pictures with real EXIF-independent pixel dimensions: two
# landscapes, portrait, square, panorama, tall portrait. Visual guides make
# cropping/stretching obvious in the Playwright screenshots.
# Never read photos from an actual Piwigo, WordPress or PMSL installation.
for i in 1 2 3 4 5 6; do
  export CI_FIXTURE_PATH="$LAB/photo-$i.jpg"
  CI_FIXTURE_INDEX="$i" php -r '
    $n = (int) getenv("CI_FIXTURE_INDEX");
    $sizes = [[900,600], [840,560], [540,810], [720,720], [1200,480], [480,960]];
    [$w, $h] = $sizes[$n - 1];
    $im = imagecreatetruecolor($w, $h);
    $background = imagecolorallocate($im, ($n * 27) % 205 + 40, ($n * 39) % 185 + 30, ($n * 47) % 185 + 30);
    $light = imagecolorallocate($im, 252, 249, 236);
    $dark = imagecolorallocate($im, 30, 30, 37);
    imagefilledrectangle($im, 0, 0, $w, $h, $background);
    imagerectangle($im, 12, 12, $w - 13, $h - 13, $light);
    imageline($im, 0, 0, $w - 1, $h - 1, $light);
    imageline($im, $w - 1, 0, 0, $h - 1, $light);
    imagefilledellipse($im, (int) ($w / 2), (int) ($h / 2), max(50, (int) ($w / 5)), max(50, (int) ($h / 5)), $dark);
    imagestring($im, 5, 24, 25, "CI PHOTO $n - ".$w."x".$h, $light);
    imagejpeg($im, getenv("CI_FIXTURE_PATH"), 85);
    imagedestroy($im);
  '
  read -r ACTUAL_W ACTUAL_H < <(php -r '
    [$w, $h] = getimagesize(getenv("CI_FIXTURE_PATH"));
    echo "$w $h";
  ')
  case "$i" in
    1) [[ "$ACTUAL_W:$ACTUAL_H" == "900:600" ]] ;;
    2) [[ "$ACTUAL_W:$ACTUAL_H" == "840:560" ]] ;;
    3) [[ "$ACTUAL_W:$ACTUAL_H" == "540:810" ]] ;;
    4) [[ "$ACTUAL_W:$ACTUAL_H" == "720:720" ]] ;;
    5) [[ "$ACTUAL_W:$ACTUAL_H" == "1200:480" ]] ;;
    6) [[ "$ACTUAL_W:$ACTUAL_H" == "480:960" ]] ;;
  esac || { echo "PIWIGO_FIXTURE_DIMENSIONS_INCORRECT=$i" >&2; exit 5; }
  printf '%s\t%s\t%s\n' "$i" "$ACTUAL_W" "$ACTUAL_H" >> "$LAB/reports/fixture-dimensions.tsv"
  HTTP_STATUS=$(curl --silent --show-error -b "$COOK" -c "$COOK" \
    --output "$LAB/upload-response.json" --write-out '%{http_code}' \
    --form 'method=pwg.images.addSimple' \
    --form "image=@$CI_FIXTURE_PATH;type=image/jpeg" \
    --form "category=$ALBUM" \
    --form 'level=0' \
    --form "name=CI photo $i" \
    'http://127.0.0.1:8090/ws.php?format=json')
  if [[ "$HTTP_STATUS" != 200 ]] ||
    ! jq -e '.stat == "ok"' "$LAB/upload-response.json" >/dev/null 2>&1; then
    echo "PIWIGO_IMAGE_UPLOAD_REJECTED_INDEX=$i HTTP=$HTTP_STATUS" >&2
    if jq -e 'type == "object"' "$LAB/upload-response.json" >/dev/null 2>&1; then
      jq -r '[.stat // "unknown", (.err // "none" | tostring), .message // "none"] | join(" / ")' "$LAB/upload-response.json" >&2
    else
      echo "Non-JSON response; first 350 bytes from disposable fixture:" >&2
      head -c 350 "$LAB/upload-response.json" | tr '\n' ' ' >&2
      echo >&2
    fi
    exit 5
  fi
  # Use Piwigo's actual public admin API to publish and associate the image.
  # addSimple alone may leave new uploads in the pending lounge even though
  # the file itself has been imported successfully.
  PHOTO_ID=$(jq -er '.result.image_id' "$LAB/upload-response.json")
  PUBLISH_RESULT=$(ws pwg.images.setInfo \
    --data-urlencode "image_id=$PHOTO_ID" \
    --data-urlencode "categories=$ALBUM" \
    --data-urlencode 'multiple_value_mode=append')
  if ! jq -e '.stat == "ok"' <<< "$PUBLISH_RESULT" >/dev/null 2>&1; then
    echo "PIWIGO_PUBLISH_PHOTO_FAILED_INDEX=$i" >&2
    jq -r '[.stat // "unknown", (.err // "none" | tostring), .message // "none"] | join(" / ")' <<< "$PUBLISH_RESULT" >&2
    exit 5
  fi
done
echo "PIWIGO_DB_IMAGE_TOTAL=$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -h 127.0.0.1 -uroot -N piwigo -e 'SELECT COUNT(*) FROM piwigo_images')"
echo "PIWIGO_DB_IMAGE_LINKS=$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -h 127.0.0.1 -uroot -N piwigo -e "SELECT COUNT(*) FROM piwigo_image_category WHERE category_id=$ALBUM")"
echo "PIWIGO_DB_ALBUM_STATUS=$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -h 127.0.0.1 -uroot -N piwigo -e "SELECT status FROM piwigo_categories WHERE id=$ALBUM")"
echo "PIWIGO_DB_IMAGE_LEVEL=$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -h 127.0.0.1 -uroot -N piwigo -e "SELECT MIN(level), MAX(level) FROM piwigo_images")"
ws pwg.categories.getImages --data-urlencode "cat_id=$ALBUM" > "$LAB/image-list.json"
echo "PIWIGO_IMAGE_LIST_STATUS=$(jq -r '.stat // "missing"' "$LAB/image-list.json")"
echo "PIWIGO_IMAGE_LIST_TYPE=$(jq -r '.result.images | type' "$LAB/image-list.json")"
echo "PIWIGO_IMAGE_LIST_KEYS=$(jq -r '(.result.images // {}) | if type=="object" then keys | join(",") else "array" end' "$LAB/image-list.json")"
echo "PIWIGO_IMAGE_LIST_COUNT=$(jq -r '(.result.images // []) | if type=="array" then length elif type=="object" then (.image // [] | length) else 0 end' "$LAB/image-list.json")"
jq -e '.stat == "ok" and (
 (.result.images | type) == "array" and (.result.images | length >= 6)
 or (.result.images | type) == "object" and (.result.images.image | length >= 6)
)' "$LAB/image-list.json" >/dev/null
# Public alias and private category must both exist, but the guest must not
# be granted access to the private one by the integration.
echo "PIWIGO_REAL_IMAGES=PASS"

WP="$LAB/wordpress"
curl --fail --location --silent --show-error \
  https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
  -o "$LAB/wp-cli.phar"
wpqa() { php "$LAB/wp-cli.phar" --path="$WP" --allow-root "$@"; }
wpqa core download --version=6.9 --locale=en_US --quiet
wpqa config create --dbname=wordpress --dbuser=ci --dbpass=ci-only-db \
  --dbhost=127.0.0.1:3307 --quiet
wpqa core install --url=http://127.0.0.1:8088 \
  --title='Piwigo 3.1 disposable E2E' \
  --admin_user=ci_admin --admin_password=ci-only-wordpress \
  --admin_email=ci@example.invalid --skip-email --quiet
# WP-CLI may infer the containing directory name (/wordpress) for siteurl
# even when core install receives the desired loopback URL. The PHP server
# exposes this directory as its document root, so both URLs must be root.
wpqa option update siteurl 'http://127.0.0.1:8088' --quiet
wpqa option update home 'http://127.0.0.1:8088' --quiet
[[ "$(wpqa option get siteurl)" == 'http://127.0.0.1:8088' ]] ||
  { echo 'CI WordPress siteurl mismatch' >&2; exit 7; }

rsync -a "$GITHUB_WORKSPACE/" "$LAB/stage/wp-piwigo-display/" \
  --exclude '/.git/' --exclude '/.github/' --exclude '/tests/' \
  --exclude '/vendor/' --exclude '/node_modules/' --exclude '/build/' \
  --exclude '*.zip'
# The upstream Splide dependency is bundled under assets/vendor/ and MUST NOT
# be mistaken for a project-root Composer vendor directory.
test -f "$LAB/stage/wp-piwigo-display/assets/vendor/splide/splide.min.js"
test -f "$LAB/stage/wp-piwigo-display/assets/vendor/splide/splide.min.css"
(cd "$LAB/stage" && zip -qr "$LAB/candidate-3.1.0.zip" wp-piwigo-display)
# WordPress protects public sites against SSRF/loopback URLs. This filter is
# only installed inside the CI disposable WordPress, NEVER packaged in Piwigo
# Display. It allows exactly the local Piwigo fixture port and nothing else.
mkdir -p "$WP/wp-content/mu-plugins"
cat > "$WP/wp-content/mu-plugins/ci-piwigo-loopback-only.php" <<'PHP'
<?php
add_filter('http_request_host_is_external', static function ($allowed, $host, $url) {
  return ($host === '127.0.0.1' && parse_url($url, PHP_URL_PORT) === 8090) ? true : $allowed;
}, 10, 3);
add_filter('http_allowed_safe_ports', static function ($ports, $host) {
  if ($host === '127.0.0.1') { $ports[] = 8090; }
  return $ports;
}, 10, 2);
PHP
wpqa plugin install "$LAB/candidate-3.1.0.zip" --activate --quiet
wpqa eval '
  $opt = get_option("wp_piwigo_display_options", []);
  if (!is_array($opt)) $opt = [];
  $opt["piwigo_url"] = "http://127.0.0.1:8090/";
  $opt["cache_duration"] = 0;
  update_option("wp_piwigo_display_options", $opt);
  if (!shortcode_exists("piwigo")) { exit(2); }
  if (!WP_Block_Type_Registry::get_instance()->is_registered("wp-piwigo-display/gallery")) { exit(3); }
'
make_page() {
  local name="$1"
  local shortcode="$2"
  local id
  id=$(wpqa post create --post_type=page --post_status=publish \
    --post_name="$name" --post_title="$name" \
    --post_content="$shortcode" --porcelain)
  printf '%s=%s\n' "$name" "$id" >> "$LAB/pages.txt"
}
make_page gallery "[piwigo album=\"$ALBUM\" limit=\"6\"]"
make_page slider "[piwigo album=\"$ALBUM\" type=\"slider\" autoplay=\"false\" limit=\"6\"]"
make_page justified "[piwigo album=\"$ALBUM\" type=\"justified\" limit=\"6\"]"
make_page collage "[piwigo album=\"$ALBUM\" type=\"collage\" limit=\"6\"]"
make_page phototext "[piwigo album=\"$ALBUM\" type=\"photo-text\" photo_text=\"CI TEST\" limit=\"6\"]"
make_page albumlink "[piwigo album=\"$ALBUM\" click_action=\"piwigo_album\" limit=\"6\"]"
make_page photolink "[piwigo album=\"$ALBUM\" click_action=\"piwigo_photo\" limit=\"6\"]"
make_page block "<!-- wp:wp-piwigo-display/gallery {\"albumId\":\"$ALBUM\",\"displayType\":\"gallery\"} /-->"
make_page alias "[piwigo album=\"#2024\" limit=\"6\"]"
php -S 127.0.0.1:8088 -t "$WP" > "$LAB/wp-server.log" 2>&1 &
echo "$!" > "$LAB/wp-server.pid"
for i in $(seq 1 60); do
  if curl --fail --silent 'http://127.0.0.1:8088/' -o /dev/null; then break; fi
  sleep 1
done
curl --fail --silent --show-error "http://127.0.0.1:8088/?page_id=$(grep '^gallery=' "$LAB/pages.txt" | cut -d= -f2)" \
  > "$LAB/gallery-before.html"
grep -Fq 'wp-piwigo-display' "$LAB/gallery-before.html"
printf 'WPD_LAB=%s\nWPD_ALBUM=%s\nWPD_ALIAS=%s\nWPD_PRIVATE=%s\n' \
  "$LAB" "$ALBUM" "$ALIAS" "$PRIVATE" >> "$GITHUB_ENV"
echo "WP_REAL_PIWIGO_SHORTCODE=PASS"
