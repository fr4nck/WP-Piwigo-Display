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
docker cp tests/e2e/activate-osm.php wpd-310-piwigo:/var/www/html/piwigo/ci-activate-osm.php
docker exec -w /var/www/html/piwigo wpd-310-piwigo php84 ci-activate-osm.php
docker exec wpd-310-piwigo rm -f /var/www/html/piwigo/ci-activate-osm.php
# Give a test photo GPS coordinates, so OSM can exercise its actual category
# and picture hooks instead of running against an album without geodata.
docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot piwigo -e \
  'UPDATE piwigo_images SET latitude=48.107, longitude=-1.679 WHERE id=(SELECT image_id FROM piwigo_image_category LIMIT 1);'
[[ "$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot piwigo -N -e \
  "SELECT state FROM piwigo_plugins WHERE id='piwigo-openstreetmap';")" == active ]]
curl --fail --silent --show-error \
  'http://127.0.0.1:8090/ws.php?format=json&method=pwg.categories.getList&recursive=true' |
  jq -e '.stat == "ok"' > /dev/null
curl --fail --silent --show-error \
  "http://127.0.0.1:8090/ws.php?format=json&method=pwg.categories.getImages&cat_id=$WPD_ALBUM" |
  jq -e '.stat == "ok" and (.result.images | length >= 6)' >/dev/null
# A real Piwigo HTML page must remain accessible with OSM loaded.
curl --fail --silent --show-error \
  "http://127.0.0.1:8090/index.php?/category/$WPD_ALBUM" \
  -o "$WPD_LAB/piwigo-with-osm.html"
grep -Eiq '<html|<!doctype' "$WPD_LAB/piwigo-with-osm.html"
echo "OSM_LIVE_API=PASS"
echo "OSM_LIVE_HTML=PASS"
