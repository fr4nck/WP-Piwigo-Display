#!/usr/bin/env bash
# Disposable WordPress/Piwigo integration lab. Never connects to production.
set -Eeuo pipefail
trap 'printf "LAB_FAILURE_LINE=%s STATUS=%s\n" "$LINENO" "$?" >&2' ERR
umask 077
: "$RUNNER_TEMP" "$GITHUB_WORKSPACE" "$GITHUB_ENV"

LAB="$RUNNER_TEMP/piwigo-editor-security"
export WPD_LAB="$LAB" WPD_EDITOR_SECURITY_LAB=1
mkdir -p "$LAB/reports" "$LAB/wordpress" "$LAB/stage/wp-piwigo-display"
echo "LAB_WRITE_ROOT=$LAB"
PIWIGO_TAG="${WPD_PIWIGO_TAG:-16.4.0}"
MARIADB_TAG="${WPD_MARIADB_TAG:-11.4}"
DARKROOM=false
case "$PIWIGO_TAG:$MARIADB_TAG:$DARKROOM" in
  '16.4.0:11.4:false'|'16.2.0:10.6:false') ;;
  *) echo 'Unsupported disposable lab combination' >&2; exit 2 ;;
esac
echo "PIWIGO=$PIWIGO_TAG; MARIADB=$MARIADB_TAG; WP=6.9; BOOTSTRAP_DARKROOM=$DARKROOM; CI_DATA=DISPOSABLE; PRODUCTION=NONE"

# Published ports are loopback-only. Piwigo and MariaDB communicate on a
# dedicated Docker bridge; no production network or credentials are used.
docker network create wpd-310-e2e >/dev/null
docker run -d --name wpd-310-db --network wpd-310-e2e --network-alias db \
  -p 127.0.0.1:3307:3306 \
  -e MARIADB_ROOT_PASSWORD=ci-only-root \
  -e MARIADB_DATABASE=piwigo \
  -e MARIADB_USER=ci \
  -e MARIADB_PASSWORD=ci-only-db \
  "mariadb:$MARIADB_TAG" >/dev/null
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
  "piwigo/piwigo:$PIWIGO_TAG" >/dev/null
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
PIWIGO_PHP=$(docker exec wpd-310-piwigo sh -c 'for cmd in php84 php83 php82 php; do if command -v "$cmd" >/dev/null 2>&1; then echo "$cmd"; exit 0; fi; done; exit 2' | tr -d '\r')
[[ -n "$PIWIGO_PHP" ]] || { echo 'PIWIGO_PHP_NOT_FOUND' >&2; exit 5; }
printf 'WPD_PIWIGO_PHP=%s\n' "$PIWIGO_PHP" >> "$GITHUB_ENV"
[[ "$(docker exec -w /var/www/html/piwigo wpd-310-piwigo "$PIWIGO_PHP" -r \
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
ws pwg.session.login --data-urlencode 'username=ci_admin' \
  --data-urlencode 'password=ci-only-piwigo' | jq -e '.stat == "ok"' >/dev/null
TOKEN=$(ws pwg.session.getStatus | jq -er '.result.pwg_token')
parent=$(ws pwg.categories.add --data-urlencode 'name=CI Private Parent' \
  --data-urlencode 'status=private' | jq -er 'select(.stat=="ok") | .result.id')
create_public() {
  ws pwg.categories.add --data-urlencode 'name=CI Homonyme' \
    --data-urlencode 'status=public' | jq -er 'select(.stat=="ok") | .result.id'
}
create_private() {
  ws pwg.categories.add --data-urlencode 'name=CI Homonyme' --data-urlencode "parent=$parent" \
    --data-urlencode 'status=private' | jq -er 'select(.stat=="ok") | .result.id'
}
# Les deux matrices créent aussi les homonymes dans des ordres inverses.
if [[ "$PIWIGO_TAG" == 16.2.0 ]]; then
  private=$(create_private); public=$(create_public)
else
  public=$(create_public); private=$(create_private)
fi
empty=$(ws pwg.categories.add --data-urlencode 'name=CI Empty Private' --data-urlencode "parent=$parent" \
  --data-urlencode 'status=private' | jq -er 'select(.stat=="ok") | .result.id')
reader=$(ws pwg.users.add --data-urlencode 'username=ci_reader' \
  --data-urlencode 'password=ci-only-reader' --data-urlencode 'password_confirm=ci-only-reader' \
  --data-urlencode 'email=ci-reader@example.invalid' --data-urlencode "pwg_token=$TOKEN" |
  jq -er 'select(.stat=="ok") | .result.users[0].id')
ws pwg.permissions.add --data-urlencode "cat_id=$parent" --data-urlencode "user_id=$reader" \
  --data-urlencode 'recursive=true' --data-urlencode "pwg_token=$TOKEN" |
  jq -e '.stat == "ok"' >/dev/null

# Deux images générées différentes, chacune liée à un seul album.
for kind in public private; do
  export WPD_CI_PICTURE="$LAB/$kind.jpg" WPD_CI_KIND="$kind"
  php -r '
    $im = imagecreatetruecolor(480,320);
    $bg = imagecolorallocate($im, getenv("WPD_CI_KIND") === "public" ? 30 : 180, 70, 100);
    imagefill($im,0,0,$bg);
    imagestring($im,5,20,30,"CI ".getenv("WPD_CI_KIND"),imagecolorallocate($im,255,255,255));
    imagejpeg($im,getenv("WPD_CI_PICTURE"),85);
    imagedestroy($im);
  '
  album="$public"
  [[ "$kind" != private ]] || album="$private"
  uploaded=$(curl --fail --silent --show-error -b "$COOK" -c "$COOK" \
    --form 'method=pwg.images.addSimple' --form "image=@$WPD_CI_PICTURE;type=image/jpeg" \
    --form "category=$album" --form 'level=0' --form "name=CI_"$kind"_SENTINEL" \
    'http://127.0.0.1:8090/ws.php?format=json' |
    jq -er 'select(.stat=="ok") | .result.image_id')
  ws pwg.images.setInfo --data-urlencode "image_id=$uploaded" --data-urlencode "categories=$album" \
    --data-urlencode 'multiple_value_mode=replace' | jq -e '.stat == "ok"' >/dev/null
  printf -v "$kind"_image '%s' "$uploaded"
done
jq -n --argjson public "$public" --argjson private "$private" --argjson parent "$parent" \
  --argjson empty "$empty" --argjson reader "$reader" \
  --argjson publicImage "$public_image" --argjson privateImage "$private_image" \
  '{public:$public,private:$private,parent:$parent,empty:$empty,reader:$reader,publicImage:$publicImage,privateImage:$privateImage}' \
  > "$LAB/fixture.json"
echo "REAL_HOMONYM_FIXTURE=PASS version=$PIWIGO_TAG public=$public private=$private reader=$reader"

# TLS vérifié par WordPress avec une CA propre au laboratoire.
openssl req -x509 -newkey rsa:2048 -nodes -days 1 -subj '/CN=127.0.0.1' \
  -addext 'subjectAltName=IP:127.0.0.1' -keyout "$LAB/key.pem" -out "$LAB/ca.pem" 2>/dev/null
cat > "$LAB/proxy.cjs" <<'JS'
const fs = require('node:fs'), https = require('node:https'), http = require('node:http');
https.createServer({
  key:fs.readFileSync(process.env.WPD_LAB+'/key.pem'),
  cert:fs.readFileSync(process.env.WPD_LAB+'/ca.pem')
},(req,res)=>{
  const upstream=http.request({
    hostname:'127.0.0.1',port:8090,path:req.url,method:req.method,
    headers:{...req.headers,host:'127.0.0.1:8090'}
  },response=>{res.writeHead(response.statusCode,response.headers);response.pipe(res);});
  upstream.on('error',()=>{res.writeHead(502);res.end();});
  req.pipe(upstream);
}).listen(8091,'127.0.0.1');
JS
node "$LAB/proxy.cjs" > "$LAB/proxy.log" 2>&1 &
echo "$!" > "$LAB/proxy.pid"
for i in $(seq 1 30); do
  if curl --fail --silent --cacert "$LAB/ca.pem" \
    'https://127.0.0.1:8091/ws.php?format=json&method=pwg.getVersion' >/dev/null; then break; fi
  sleep 1
done
WP="$LAB/wordpress"
curl --fail --location --silent --show-error \
  https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar \
  -o "$LAB/wp-cli.phar"
printf '%s  %s\n' 'ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c' "$LAB/wp-cli.phar" | sha256sum --check
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

rsync -a "$GITHUB_WORKSPACE/" "$WP/wp-content/plugins/wp-piwigo-display/" \
  --exclude '/.git/' --exclude '/.github/' --exclude '/tests/' --exclude '/docs/' \
  --exclude '/node_modules/' --exclude '/vendor/' --exclude '/build/' --exclude '*.zip'
mkdir -p "$WP/wp-content/mu-plugins"
cp tests/e2e/editor-security-fixture.php "$WP/wp-content/mu-plugins/editor-security-fixture.php"
wpqa plugin activate wp-piwigo-display --quiet
wpqa eval '
  $options = WPD_Settings::default_options();
  $options["piwigo_url"] = "https://127.0.0.1:8091";
  $options["cache_duration"] = 3600;
  update_option("wp_piwigo_display_options", $options);
  update_option(WPD_Service_Account::OPTION_NAME, [
    "enabled"=>"true","username"=>"ci_reader","password"=>"ci-only-reader"
  ], false);
'
wpqa user create ci_contributor ci-contributor@example.invalid \
  --role=contributor --user_pass=ci-only-contributor --quiet
php -S 127.0.0.1:8088 -t "$WP" > "$LAB/wp-server.log" 2>&1 &
echo "$!" > "$LAB/wp-server.pid"
for i in $(seq 1 60); do
  if curl --fail --silent 'http://127.0.0.1:8088/' -o /dev/null; then break; fi
  sleep 1
done
printf 'WPD_LAB=%s\nWPD_EDITOR_SECURITY_LAB=1\n' "$LAB" >> "$GITHUB_ENV"
echo 'EDITOR_SECURITY_LAB=READY'
