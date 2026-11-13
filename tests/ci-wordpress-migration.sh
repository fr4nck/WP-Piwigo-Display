#!/usr/bin/env bash
# Real WordPress/WP-CLI migration check, on GitHub's disposable runner only.
set -Eeuo pipefail
umask 077
: "${RUNNER_TEMP:?GitHub runner required}"
: "${WP_VERSION:?WP_VERSION required}"
: "${WP_CLI_PHAR:?WP-CLI required}"
: "${WPQA_PKG_DIR:?Packages required}"
: "${WPQA_VERSION:?Candidate version required}"
[[ "$WPQA_VERSION" == "3.1.0" ]] || exit 2
[[ -f "$WP_CLI_PHAR" && -d "$WPQA_PKG_DIR" ]] || exit 2

GH_ZIP="$WPQA_PKG_DIR/piwigo-display-${WPQA_VERSION}.zip"
ORG_ZIP="$WPQA_PKG_DIR/display4piwigo-wporg-${WPQA_VERSION}-candidate.zip"
for f in "$GH_ZIP" "$ORG_ZIP" "$WPQA_PKG_DIR/SHA256SUMS"; do test -f "$f"; done
(cd "$WPQA_PKG_DIR" && sha256sum --check SHA256SUMS)

WORK="$(mktemp -d "${RUNNER_TEMP%/}/piwigo-wordpress-qa.XXXXXXXX")"
WP_ROOT="$WORK/wordpress"
mkdir -p "$WP_ROOT"
OLD="$WORK/piwigo-display-3.0.0.zip"
BASE301="$WORK/piwigo-display-3.0.1.zip"
echo "WRITE_ROOT=$WORK"
echo "WP_VERSION=$WP_VERSION; production data=NONE"
curl --fail --location --silent --show-error \
  https://github.com/fr4nck/WP-Piwigo-Display/releases/download/v3.0.0/piwigo-display-3.0.0.zip \
  -o "$OLD"
echo "50681266437068e425d6504b8f7c7e4d2517403003673f49c8a30b4df28d08c5  $OLD" | sha256sum --check
curl --fail --location --silent --show-error https://github.com/fr4nck/WP-Piwigo-Display/releases/download/v3.0.1/piwigo-display-3.0.1.zip -o "$BASE301"
echo "20faab8e598299a7db5d3ace1622f3b8f7c464c27b0ce16778ab93e7c1e59ed4  $BASE301" | sha256sum --check

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
# Use the actual published 3.0.1 ZIP and verify all historical identities.
wpqa plugin install "$BASE301" --force
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "3.0.1" ]] || fail "Published 3.0.1 baseline version"
wpqa plugin is-active wp-piwigo-display || fail "3.0.1 baseline disabled"
assert_options "published GitHub 3.0.1"
assert_content "published GitHub 3.0.1"
assert_identity "published GitHub 3.0.1"
echo "WP_MIGRATION_STAGE=published_3_0_1_pass"

# Upgrade in place to the new 3.1.0 candidate.
wpqa plugin install "$GH_ZIP" --force
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "$WPQA_VERSION" ]] || fail "Candidate upgrade version"
wpqa plugin is-active wp-piwigo-display || fail "Candidate upgrade disabled plugin"
assert_options "candidate 3.1.0"
assert_content "candidate 3.1.0"
assert_identity "candidate 3.1.0"
echo "WP_MIGRATION_STAGE=candidate_3_1_0_pass"

# Exercise a real rollback to the published stable 3.0.1 ZIP.
wpqa plugin install "$BASE301" --force
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "3.0.1" ]] || fail "Rollback version"
wpqa plugin is-active wp-piwigo-display || fail "Rollback deactivated plugin"
assert_options "rollback to published 3.0.1"
assert_content "rollback to published 3.0.1"
assert_identity "rollback to published 3.0.1"
echo "WP_MIGRATION_STAGE=rollback_to_published_3_0_1_pass"

# Reinstall the candidate before testing coexistence and final uninstall.
wpqa plugin install "$GH_ZIP" --force
[[ "$(wpqa plugin get wp-piwigo-display --field=version)" == "$WPQA_VERSION" ]] || fail "Candidate reupgrade"
assert_options "candidate resumed"
assert_content "candidate resumed"
assert_identity "candidate resumed"
echo "WP_MIGRATION_STAGE=candidate_resumed_pass"

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

# A real rollback must work before deleting the former GitHub directory.
# No options, saved blocks or shortcode content may be modified.
wpqa plugin deactivate display4piwigo
wpqa plugin activate wp-piwigo-display
assert_options "rollback from WordPress.org candidate to GitHub 3.0.1"
assert_content "rollback to GitHub 3.0.1"
assert_identity "rollback to GitHub 3.0.1"
echo "WP_MIGRATION_STAGE=rollback_to_github_pass"

wpqa plugin deactivate wp-piwigo-display
wpqa plugin activate display4piwigo
assert_options "resuming WordPress.org candidate after rollback"
assert_content "resuming WordPress.org candidate"
assert_identity "resuming WordPress.org candidate"
echo "WP_MIGRATION_STAGE=resume_wporg_pass"

wpqa plugin uninstall wp-piwigo-display
assert_options "removing protected old folder"
assert_content "removing protected old folder"
assert_identity "old folder removed"
echo "WP_MIGRATION_STAGE=old_folder_removed_without_loss"

wpqa plugin uninstall display4piwigo --deactivate
[[ "$(wpqa eval 'echo (get_option("wp_piwigo_display_options",null)===null && get_option("wp_piwigo_display_service_account",null)===null) ? "PASS":"FAIL";')" == PASS ]] || fail "Final uninstall did not remove credentials"
assert_content "last uninstall"
echo "WP_MIGRATION_STAGE=final_uninstall_scrubs_secrets"

# Independently exercise a true batched core delete_plugins() call.
# The two protected copies are installed but inactive; only the last
# uninstaller is allowed to remove the shared configuration and credentials.
wpqa plugin install "$GH_ZIP"
wpqa plugin install "$ORG_ZIP"
wpqa eval 'update_option("wp_piwigo_display_options",array("piwigo_url"=>"https://ci.example.invalid/gallery"));update_option("wp_piwigo_display_service_account",array("enabled"=>false,"username"=>"ci","password"=>"fake-only"));'
wpqa eval '
  require_once ABSPATH . "wp-admin/includes/file.php";
  require_once ABSPATH . "wp-admin/includes/plugin.php";
  if (!defined("FS_METHOD")) { define("FS_METHOD", "direct"); }
  if (!WP_Filesystem()) { fwrite(STDERR, "Filesystem unavailable\n"); exit(1); }
  $result = delete_plugins(array(
    "wp-piwigo-display/wp-piwigo-display.php",
    "display4piwigo/wp-piwigo-display.php"
  ));
  if (is_wp_error($result) || true !== $result) {
    fwrite(STDERR, "Batched delete_plugins failed\n");
    exit(1);
  }
  echo "WP_GROUP_DELETE_PASS";
' | grep -Fxq 'WP_GROUP_DELETE_PASS' || fail "Batched WordPress uninstall failed"
if wpqa plugin is-installed wp-piwigo-display; then fail "GitHub plugin survives group deletion"; fi
if wpqa plugin is-installed display4piwigo; then fail "WordPress.org candidate survives group deletion"; fi
[[ "$(wpqa eval 'echo (get_option("wp_piwigo_display_options",null)===null && get_option("wp_piwigo_display_service_account",null)===null) ? "PASS":"FAIL";')" == PASS ]] || fail "Batched final uninstall retained credentials"
assert_content "batched last uninstall"
echo "WP_MIGRATION_STAGE=group_uninstall_pass"
echo "WP_MIGRATION_PASS WP=$WP_VERSION"
