#!/usr/bin/env bash
# Disposable WordPress/Piwigo integration lab. Never connects to production.
set -Eeuo pipefail
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
for i in $(seq 1 80); do
  if docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb-admin -uroot ping --silent >/dev/null 2>&1; then break; fi
  sleep 2
done
docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot -e \
  'CREATE DATABASE wordpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
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

for i in 1 2 3 4 5 6; do
  export CI_FIXTURE_PATH="$LAB/photo-$i.jpg"
  CI_FIXTURE_INDEX="$i" php -r '
    $im = imagecreatetruecolor(720, 480);
    $n = (int) getenv("CI_FIXTURE_INDEX");
    $color = imagecolorallocate($im, ($n*27)%240, ($n*39)%220, ($n*47)%230);
    imagefilledrectangle($im, 0, 0, 720, 480, $color);
    imagejpeg($im, getenv("CI_FIXTURE_PATH"), 84);
    imagedestroy($im);
  '
  curl --fail --silent --show-error -b "$COOK" -c "$COOK" \
    --form 'method=pwg.images.addSimple' \
    --form "image=@$CI_FIXTURE_PATH;type=image/jpeg" \
    --form "category=$ALBUM" \
    --form "name=CI photo $i" \
    'http://127.0.0.1:8090/ws.php?format=json' |
    jq -e '.stat == "ok"' >/dev/null
done
ws pwg.categories.getImages --data-urlencode "cat_id=$ALBUM" |
  jq -e '.stat == "ok" and (.result.images | length >= 6)' >/dev/null
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

rsync -a "$GITHUB_WORKSPACE/" "$LAB/stage/wp-piwigo-display/" \
  --exclude .git --exclude .github --exclude tests --exclude vendor \
  --exclude node_modules --exclude build --exclude '*.zip'
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
