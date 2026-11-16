#!/usr/bin/env bash
# Multisite regression: an ordinary subscriber cannot deactivate a network plugin.
# All installations and database changes stay on an isolated GitHub runner.
set -Eeuo pipefail
umask 077
: "${RUNNER_TEMP:?}"
: "${WP_VERSION:?}"
: "${WP_CLI_PHAR:?}"
: "${WPQA_PKG_DIR:?}"
: "${WPQA_VERSION:?Candidate version required}"
[[ "$WPQA_VERSION" == "3.1.0-rc.2" ]] || exit 2
GH_ZIP="$WPQA_PKG_DIR/piwigo-display-${WPQA_VERSION}.zip"
ORG_ZIP="$WPQA_PKG_DIR/display4piwigo-wporg-${WPQA_VERSION}-candidate.zip"
test -f "$GH_ZIP"; test -f "$ORG_ZIP"
(cd "$WPQA_PKG_DIR" && sha256sum --check SHA256SUMS)

WORK="$(mktemp -d "${RUNNER_TEMP%/}/piwigo-multisite.XXXXXXXX")"
WP_ROOT="$WORK/wordpress"
mkdir -p "$WP_ROOT"
echo "WRITE_ROOT=$WORK"
echo "WORDPRESS_MULTISITE=$WP_VERSION"
wpqa() { php "$WP_CLI_PHAR" --path="$WP_ROOT" --allow-root "$@"; }
fail() { echo "WP_MULTISITE_FAIL: $*" >&2; exit 1; }

wpqa core download --version="$WP_VERSION" --locale=en_US
wpqa config create --dbname=wordpress --dbuser=wpqa --dbpass=wpqa --dbhost=127.0.0.1:3306
wpqa core install --url=http://piwigo-qa.local --title="Network QA" \
  --admin_user=ci_admin --admin_password=ci_fake_password --admin_email=ci@example.invalid --skip-email
wpqa core multisite-convert --title="Piwigo QA network"

B2='http://piwigo-qa.local/b2/'
B3='http://piwigo-qa.local/b3/'
wpqa site create --slug=b2 --title="Site b2" --email=ci@example.invalid
wpqa site create --slug=b3 --title="Site b3" --email=ci@example.invalid
wpqa plugin install "$GH_ZIP"
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "$WPQA_VERSION" ]] || fail "Candidate version mismatch"
wpqa plugin install "$ORG_ZIP"
wpqa --url="$B2" plugin activate wp-piwigo-display
wpqa --url="$B2" eval 'update_option("wpd_custom_svg_masks",array("a1b2c3d4e5f6"=>array("name"=>"SITE B2 ONLY","svg"=>"<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 10 10\"/>")));'
[[ "$(wpqa --url="$B3" eval 'echo get_option("wpd_custom_svg_masks",null)===null ? "PASS" : "FAIL";')" == PASS ]] || fail "A custom mask leaked across multisite sites"
if wpqa plugin activate display4piwigo --network; then
  fail "Network activation was accepted despite b2 already running GitHub Piwigo Display"
fi
wpqa --url="$B2" plugin is-active wp-piwigo-display || fail "Conflicting network activation disturbed b2"
echo "WP_MULTISITE_STAGE=network_activation_refused"

wpqa --url="$B2" plugin deactivate wp-piwigo-display
wpqa plugin activate display4piwigo --network
network_digest() {
  wpqa eval 'echo hash("sha256", serialize(get_site_option("active_sitewide_plugins",array())));'
}
ACTIVE_HASH="$(network_digest)"
[[ "$(wpqa eval 'echo isset(get_site_option("active_sitewide_plugins")["display4piwigo/wp-piwigo-display.php"]) ? "YES" : "NO";')" == YES ]] || fail "WordPress.org candidate not network-active"

# Simulate the incoherent state from CA-301-002. The legacy code and the
# protected network distribution now coexist on site b2.
wpqa --url="$B2" option update active_plugins '["wp-piwigo-display/wp-piwigo-display.php"]' --format=json
SUBSCRIBER_ID="$(wpqa --url="$B2" user create cisubscriber ci-subscriber@example.invalid --role=subscriber --porcelain)"
wpqa --url="$B2" eval "wp_set_current_user($SUBSCRIBER_ID); do_action('admin_init');"
[[ "$(network_digest)" == "$ACTIVE_HASH" ]] || fail "Subscriber admin_init deactivated Piwigo Display network-wide"
[[ "$(wpqa --url="$B3" eval 'echo shortcode_exists("piwigo") ? "YES" : "NO";')" == YES ]] || fail "Site b3 lost shortcode after subscriber request"
[[ "$(wpqa --url="$B3" eval 'echo WP_Block_Type_Registry::get_instance()->is_registered("wp-piwigo-display/gallery") ? "YES" : "NO";')" == YES ]] || fail "Site b3 lost Gutenberg gallery after subscriber request"
echo "WP_MULTISITE_STAGE=subscriber_cannot_deactivate_network"

# The network admin is equally unable to cause a silent repair.
wpqa --url="$B2" eval 'wp_set_current_user(1); do_action("admin_init");'
[[ "$(network_digest)" == "$ACTIVE_HASH" ]] || fail "Administrator admin_init deactivated Piwigo Display network-wide"
echo "WP_MULTISITE_STAGE=administrator_cannot_silently_deactivate_network"
[[ "$(wpqa --url="$B2" eval 'echo isset(get_option("wpd_custom_svg_masks",array())["a1b2c3d4e5f6"]) ? "PASS" : "FAIL";')" == PASS ]] || fail "Site B2 lost its private mask library during network activation"
[[ "$(wpqa --url="$B3" eval 'echo get_option("wpd_custom_svg_masks",null)===null ? "PASS" : "FAIL";')" == PASS ]] || fail "Site B3 inherited site B2 masks"
echo "WP_MULTISITE_MASKS=PASS site-local masks preserved and isolated"
echo "WP_MULTISITE_PASS WP=$WP_VERSION"
