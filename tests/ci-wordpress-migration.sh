#!/usr/bin/env bash
# Real WordPress/WP-CLI migration check, on GitHub's disposable runner only.
set -Eeuo pipefail
umask 077
: "${RUNNER_TEMP:?GitHub runner required}"
: "${WP_VERSION:?WP_VERSION required}"
: "${WP_CLI_PHAR:?WP-CLI required}"
: "${WPQA_PKG_DIR:?Packages required}"
[[ -f "$WP_CLI_PHAR" && -d "$WPQA_PKG_DIR" ]] || exit 2

GH_ZIP="$WPQA_PKG_DIR/piwigo-display-3.0.1.zip"
ORG_ZIP="$WPQA_PKG_DIR/display4piwigo-wporg-3.0.1-candidate.zip"
for f in "$GH_ZIP" "$ORG_ZIP" "$WPQA_PKG_DIR/SHA256SUMS"; do test -f "$f"; done
(cd "$WPQA_PKG_DIR" && sha256sum --check SHA256SUMS)

WORK="$(mktemp -d "${RUNNER_TEMP%/}/piwigo-wordpress-qa.XXXXXXXX")"
WP_ROOT="$WORK/wordpress"
mkdir -p "$WP_ROOT"
OLD="$WORK/piwigo-display-3.0.0.zip"
echo "WRITE_ROOT=$WORK"
echo "WP_VERSION=$WP_VERSION; production data=NONE"
curl --fail --location --silent --show-error \
  https://github.com/fr4nck/WP-Piwigo-Display/releases/download/v3.0.0/piwigo-display-3.0.0.zip \
  -o "$OLD"
echo "50681266437068e425d6504b8f7c7e4d2517403003673f49c8a30b4df28d08c5  $OLD" | sha256sum --check

wpqa() { php "$WP_CLI_PHAR" --path="$WP_ROOT" --allow-root "$@"; }
fail() { echo "WP_MIGRATION_FAIL: $*" >&2; exit 1; }
snapshot() {
  wpqa eval 'echo hash("sha256",serialize(array(get_option("wp_piwigo_display_options"),get_option("wp_piwigo_display_service_account"))));'
}
assert_options() { [[ "$(snapshot)" == "$BASE_HASH" ]] || fail "Stored settings changed: $1"; }
assert_content() {
  [[ "$(wpqa post get "$SHORTCODE_POST" --field=post_content)" == "$SHORTCODE_CONTENT" ]] || fail "Shortcode content changed: $1"
  [[ "$(wpqa post get "$BLOCK_POST" --field=post_content)" == "$BLOCK_CONTENT" ]] || fail "Gutenberg content changed: $1"
}
assert_identity() {
  [[ "$(wpqa eval 'echo (shortcode_exists("piwigo") && WP_Block_Type_Registry::get_instance()->is_registered("wp-piwigo-display/gallery")) ? "PASS" : "FAIL";')" == PASS ]] || fail "Registered content identity changed: $1"
}

wpqa core download --version="$WP_VERSION" --locale=en_US
wpqa config create --dbname=wordpress --dbuser=wpqa --dbpass=wpqa --dbhost=127.0.0.1:3306
wpqa core install --url=http://piwigo-qa.local --title="Piwigo migration QA" \
  --admin_user=ci_admin --admin_password=ci_fake_password \
  --admin_email=ci@example.invalid --skip-email

wpqa plugin install "$OLD" --activate
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "3.0.0" ]] || fail "Legacy ZIP version"
wpqa eval 'update_option("wp_piwigo_display_options",array("piwigo_url"=>"https://ci.example.invalid/gallery","cache_duration"=>3600));update_option("wp_piwigo_display_service_account",array("enabled"=>false,"username"=>"ci","password"=>"fake-only"));'
BASE_HASH="$(snapshot)"
SHORTCODE_CONTENT='[piwigo album="5" type="slider" autoplay="false"]'
BLOCK_CONTENT='<!-- wp:wp-piwigo-display/gallery {"albumId":"5","displayType":"slider"} /-->'
SHORTCODE_POST="$(wpqa post create --post_type=page --post_status=publish --post_title="Shortcode QA" --post_content="$SHORTCODE_CONTENT" --porcelain)"
BLOCK_POST="$(wpqa post create --post_type=page --post_status=publish --post_title="Block QA" --post_content="$BLOCK_CONTENT" --porcelain)"
assert_content "old installation"
assert_identity "old installation"

# Mandatory upgrade IN PLACE: never uninstall the unprotected v3.0.0.
wpqa plugin install "$GH_ZIP" --force
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "3.0.1" ]] || fail "In-place upgrade version"
wpqa plugin is-active wp-piwigo-display || fail "In-place upgrade disabled legacy plugin"
assert_options "GitHub in-place 3.0.1"
assert_content "GitHub in-place 3.0.1"
assert_identity "GitHub in-place 3.0.1"
echo "WP_MIGRATION_STAGE=github_in_place_pass"

wpqa plugin install "$ORG_ZIP"
if wpqa plugin activate display4piwigo; then fail "Dual activation allowed"; fi
if wpqa plugin is-active display4piwigo; then fail "Rejected plugin was activated"; fi
wpqa plugin is-active wp-piwigo-display || fail "Existing plugin was deactivated"
assert_options "rejected dual activation"
echo "WP_MIGRATION_STAGE=dual_activation_guard_pass"

wpqa plugin deactivate wp-piwigo-display
wpqa plugin activate display4piwigo
assert_options "switch to separate directory"
assert_content "switch to separate directory"
assert_identity "new candidate active"
wpqa plugin uninstall wp-piwigo-display
assert_options "removing protected old folder"
assert_content "removing protected old folder"
assert_identity "old folder removed"
echo "WP_MIGRATION_STAGE=old_folder_removed_without_loss"

wpqa plugin uninstall display4piwigo --deactivate
[[ "$(wpqa eval 'echo (get_option("wp_piwigo_display_options",null)===null && get_option("wp_piwigo_display_service_account",null)===null) ? "PASS":"FAIL";')" == PASS ]] || fail "Final uninstall did not remove credentials"
assert_content "last uninstall"
echo "WP_MIGRATION_STAGE=final_uninstall_scrubs_secrets"
echo "WP_MIGRATION_PASS WP=$WP_VERSION"
