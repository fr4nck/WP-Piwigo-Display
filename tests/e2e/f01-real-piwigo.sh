#!/usr/bin/env bash
# Uniquement les services Docker jetables créés par start-lab.sh.
set -Eeuo pipefail
: "$WPD_LAB" "$GITHUB_WORKSPACE"
LAB="$WPD_LAB"
ws() {
  curl --fail --silent --show-error -b "$LAB/piwigo-cookies" -c "$LAB/piwigo-cookies" \
    --data-urlencode "method=$1" "${@:2}" 'http://127.0.0.1:8090/ws.php?format=json'
}
large=$(ws pwg.categories.add --data-urlencode 'name=F01 large' --data-urlencode 'status=public' | jq -er '.result.id')
shared=$(ws pwg.categories.add --data-urlencode 'name=F01 shared' --data-urlencode 'status=public' | jq -er '.result.id')
child=$(ws pwg.categories.add --data-urlencode 'name=F01 child' --data-urlencode "parent=$shared" --data-urlencode 'status=public' | jq -er '.result.id')
[[ "$large:$shared:$child" =~ ^[0-9]+:[0-9]+:[0-9]+$ ]]

# Les fichiers restent synthétiques : 10001 lignes distinctes référencent une
# photo du laboratoire. Aucune image utilisateur, ni écriture de 10001 JPEG.
docker exec -i -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot piwigo <<SQL
INSERT INTO piwigo_images (file,date_available,name,path,width,height,level)
SELECT 'f01-ci.jpg',NOW(),CONCAT('F01 ',s.seq),p.path,p.width,p.height,0
FROM seq_1_to_10001 s CROSS JOIN (SELECT path,width,height FROM piwigo_images ORDER BY id LIMIT 1) p;
INSERT INTO piwigo_image_category (image_id,category_id,rank)
SELECT id,$large,id FROM piwigo_images WHERE file='f01-ci.jpg';
INSERT INTO piwigo_image_category (image_id,category_id,rank)
SELECT id,$shared,id FROM piwigo_images WHERE file='f01-ci.jpg' ORDER BY id LIMIT 2;
INSERT INTO piwigo_image_category (image_id,category_id,rank)
SELECT id,$child,id FROM piwigo_images WHERE file='f01-ci.jpg' ORDER BY id LIMIT 3;
SQL
count=$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot -N piwigo -e "SELECT COUNT(*) FROM piwigo_image_category WHERE category_id=$large")
test "$count" = 10001
ids=$(docker exec -e MYSQL_PWD=ci-only-root wpd-310-db mariadb -uroot -N piwigo -e "SELECT id FROM piwigo_images WHERE file='f01-ci.jpg' ORDER BY id LIMIT 3" | paste -sd,)

# Terminaison TLS locale avec certificat propre au laboratoire, CA explicitement
# transmise à WordPress par la fixture ; aucune désactivation de validation TLS.
openssl req -x509 -newkey rsa:2048 -nodes -days 1 -subj '/CN=127.0.0.1' \
  -addext 'subjectAltName=IP:127.0.0.1' -keyout "$LAB/f01-key.pem" -out "$LAB/f01-ca.pem" 2>/dev/null
cat > "$LAB/f01-proxy.cjs" <<'JS'
const fs = require('node:fs'), https = require('node:https'), http = require('node:http');
https.createServer({key:fs.readFileSync(process.env.WPD_LAB+'/f01-key.pem'),cert:fs.readFileSync(process.env.WPD_LAB+'/f01-ca.pem')},(req,res)=>{
  const up=http.request({hostname:'127.0.0.1',port:8090,path:req.url,method:req.method,headers:{...req.headers,host:'127.0.0.1:8090'}},r=>{res.writeHead(r.statusCode,r.headers);r.pipe(res);});
  up.on('error',()=>{res.writeHead(502);res.end();});req.pipe(up);
}).listen(8091,'127.0.0.1');
JS
node "$LAB/f01-proxy.cjs" > "$LAB/f01-proxy.log" 2>&1 &
echo "$!" > "$LAB/f01-proxy.pid"
for i in $(seq 1 20); do
  if curl --fail --silent --cacert "$LAB/f01-ca.pem" 'https://127.0.0.1:8091/ws.php?format=json&method=pwg.getVersion' >/dev/null; then break; fi
  sleep 1
done
export WPD_F01_LARGE="$large" WPD_F01_SHARED="$shared" WPD_F01_IDS="$ids"
printf 'WPD_F01_LARGE=%s\nWPD_F01_SHARED=%s\nWPD_F01_IDS=%s\n' "$large" "$shared" "$ids" >> "$GITHUB_ENV"
BASE=ee79a7c5a67f69dd0724a592c4b86be1247775f4
git fetch --no-tags --depth=1 origin "$BASE"
test "$(git rev-parse FETCH_HEAD)" = "$BASE"
mkdir -p "$LAB/f01-baseline"
git archive "$BASE" includes | tar -x -C "$LAB/f01-baseline"
for mode in public service; do
  set +e
  WPD_F01_SOURCE="$LAB/f01-baseline" WPD_F01_MODE="$mode" WPD_F01_CASE=large \
    php "$LAB/wp-cli.phar" --path="$LAB/wordpress" --allow-root --skip-plugins \
    eval-file tests/e2e/f01-real-piwigo.php > "$LAB/reports/f01-red-$mode.log" 2>&1
  rc=$?
  set -e
  cat "$LAB/reports/f01-red-$mode.log"
  test "$rc" = 1
  grep -q 'WP_F01_REAL=FAIL' "$LAB/reports/f01-red-$mode.log"
  for case_id in large shared zero; do
    WPD_F01_SOURCE="$GITHUB_WORKSPACE" WPD_F01_MODE="$mode" WPD_F01_CASE="$case_id" \
      php "$LAB/wp-cli.phar" --path="$LAB/wordpress" --allow-root --skip-plugins \
      eval-file tests/e2e/f01-real-piwigo.php | tee "$LAB/reports/f01-green-$mode-$case_id.log"
  done
done
