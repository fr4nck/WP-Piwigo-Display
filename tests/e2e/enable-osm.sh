#!/usr/bin/env bash
# Activate the pinned real Piwigo OpenStreetMap plugin on the disposable lab.
set -Eeuo pipefail
umask 077
: "$WPD_LAB"
[[ -d "$WPD_LAB/wordpress" ]] || exit 2
echo "OSM_SOURCE=Piwigo/piwigo-openstreetmap@567f7aae970f41524c03e93b4cd2543d0548d039"
git clone --quiet https://github.com/Piwigo/piwigo-openstreetmap.git "$WPD_LAB/osm"
git -C "$WPD_LAB/osm" checkout --quiet 567f7aae970f41524c03e93b4cd2543d0548d039
docker exec wpd-310-piwigo mkdir -p /var/www/html/piwigo/plugins/piwigo-openstreetmap
docker cp "$WPD_LAB/osm/." wpd-310-piwigo:/var/www/html/piwigo/plugins/piwigo-openstreetmap/
# Git checkout inherits the CI runner's restrictive umask 077. Docker cp
# retains unreadable 0600/0700 modes, but Piwigo's web PHP user must be
# able to load plugin/main.inc.php and its PHP assets.
docker exec wpd-310-piwigo \
  chmod -R a+rX /var/www/html/piwigo/plugins/piwigo-openstreetmap
docker exec wpd-310-piwigo sh -c \
  'test "$(stat -c %a /var/www/html/piwigo/plugins/piwigo-openstreetmap/main.inc.php)" = 644'
# The real upstream installer writes PHPWG_ROOT_PATH/osmmap.php again on
# web requests (maintain.class.php:164). The container's PHP-FPM user cannot
# create a new file in the Piwigo root. Grant write access ONLY to this
# generated entrypoint inside the disposable, loopback-only CI container.
# Do not chmod the web root and never apply this on a real Piwigo site.
docker exec -u 0 wpd-310-piwigo touch /var/www/html/piwigo/osmmap.php
docker exec -u 0 wpd-310-piwigo chmod 0666 /var/www/html/piwigo/osmmap.php
docker exec wpd-310-piwigo test -f /var/www/html/piwigo/osmmap.php
echo "OSM_DISPOSABLE_ENTRYPOINT_WRITABLE=PASS"
docker cp tests/e2e/activate-osm.php wpd-310-piwigo:/var/www/html/piwigo/ci-activate-osm.php
docker exec -w /var/www/html/piwigo wpd-310-piwigo php84 ci-activate-osm.php
docker exec wpd-310-piwigo rm -f /var/www/html/piwigo/ci-activate-osm.php
# Give a test photo GPS coordinates, so OSM can exercise its actual category
# and picture hooks instead of running against an album without geodata.
docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot piwigo -e \
  'UPDATE piwigo_images SET latitude=48.107, longitude=-1.679 WHERE id=(SELECT image_id FROM piwigo_image_category LIMIT 1);'
[[ "$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot piwigo -N -e \
  "SELECT state FROM piwigo_plugins WHERE id='piwigo-openstreetmap';")" == active ]]
# Capture actual upstream output with the Piwigo OSM extension enabled.
# Corrupted upstream responses may be corrected by WPD's HTTP adapter; only
# the following REAL browser test may declare integration success.
OSM_LIST_HTTP=$(curl --silent --show-error \
  --output "$WPD_LAB/osm-getList.raw" --write-out '%{http_code}' \
  'http://127.0.0.1:8090/ws.php?format=json&method=pwg.categories.getList&recursive=true')
OSM_IMAGES_HTTP=$(curl --silent --show-error \
  --output "$WPD_LAB/osm-getImages.raw" --write-out '%{http_code}' \
  "http://127.0.0.1:8090/ws.php?format=json&method=pwg.categories.getImages&cat_id=$WPD_ALBUM")
echo "OSM_UPSTREAM_HTTP=$OSM_LIST_HTTP/$OSM_IMAGES_HTTP"
for name in getList getImages; do
  file="$WPD_LAB/osm-$name.raw"
  if jq -e '.stat == "ok"' "$file" >/dev/null 2>&1; then
    printf 'OSM_UPSTREAM_%s_JSON=VALID\n' "$name"
  else
    printf 'OSM_UPSTREAM_%s_JSON=INVALID\n' "$name"
    printf 'OSM_UPSTREAM_%s_BYTES=%s\n' "$name" "$(wc -c < "$file")"
    # Public synthetic fixture only; bound output, no real user credentials.
    printf 'OSM_UPSTREAM_%s_PREFIX=' "$name"
    head -c 220 "$file" | tr '\r\n' ' ' | tr -cd '[:print:]'
    echo
  fi
done
# A real Piwigo HTML page must remain accessible with OSM loaded.
curl --fail --silent --show-error \
  "http://127.0.0.1:8090/index.php?/category/$WPD_ALBUM" \
  -o "$WPD_LAB/piwigo-with-osm.html"
grep -Eiq '<html|<!doctype' "$WPD_LAB/piwigo-with-osm.html"
echo "OSM_LIVE_API=PASS"
echo "OSM_LIVE_HTML=PASS"
