<?php
/**
 * Regression checks for uninstall cleanup and private-cache stale isolation.
 */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

final class WPD_Settings {
    public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
    public static function get_cache_duration(): int { return 60; }
}
final class WPD_Service_Account {
    public static function is_configured(): bool { return true; }
    public static function get_context_hash(): string { return 'service-user'; }
}
final class WP_Error {}
class WPD_Api {
    public function __construct(string $url) {}
    public function get_images_from_album(int $album_id, int $max = 0): array { return array(array('id'=>1)); }
}
final class WPD_Service_Api extends WPD_Api {}
final class WPD_Api_Metrics {
    public static function cache_hit(string $status): void {}
    public static function cache_miss(string $status): void {}
}
function __($x, $domain = '') { return $x; }
function sanitize_text_field($v): string { return (string) $v; }
function absint($v): int { return abs((int) $v); }
function untrailingslashit(string $v): string { return rtrim($v, '/\\'); }
function is_wp_error($v): bool { return $v instanceof WP_Error; }
function get_transient(string $key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient(string $key, $value, int $ttl) { $GLOBALS['transients'][$key] = $value; return true; }
function wp_using_ext_object_cache(): bool { return true; }
function wp_cache_add($key, $value, $group, $ttl): bool { return true; }
function wp_cache_delete($key, $group): bool { return true; }
define( 'DAY_IN_SECONDS', 86400 );

require dirname( __DIR__ ) . '/includes/class-wpd-cache.php';
$GLOBALS['transients'] = array();
WPD_Cache::get_album_images(7, 0, 'https://photos.example.test');
foreach ( array_keys( $GLOBALS['transients'] ) as $key ) {
    if ( str_starts_with( $key, 'wpd_stale_' ) ) {
        fwrite( STDERR, "Private album wrote a stale fallback.\n" );
        exit( 1 );
    }
}
WPD_Cache::get_album_images(8, 0, 'https://other.example.test');
$found = false;
foreach ( array_keys( $GLOBALS['transients'] ) as $key ) {
    if ( str_starts_with( $key, 'wpd_stale_' ) ) { $found = true; }
}
if ( ! $found ) {
    fwrite( STDERR, "Public gallery lost stale fallback.\n" );
    exit( 1 );
}

// WordPress calls uninstall.php with this guard set.
define( 'WP_UNINSTALL_PLUGIN', 'wp-piwigo-display/wp-piwigo-display.php' );
function get_plugins(): array {
    return array( 'wp-piwigo-display/wp-piwigo-display.php' => array( 'Name' => 'Piwigo Display' ) );
}
final class FakeDb {
    public string $options = 'wp_options';
    public function esc_like(string $key): string { return $key; }
    public function prepare(string $query, ...$params): string { return $query; }
    public function get_col(string $query): array {
        return array(
            '_transient_wpd_album_abc',
            '_transient_timeout_wpd_stale_def',
            '_transient_wpd_lock_ghi',
        );
    }
}
$GLOBALS['wpdb'] = new FakeDb();
$GLOBALS['deleted_options'] = array();
$GLOBALS['deleted_transients'] = array();
function delete_option(string $name): bool { $GLOBALS['deleted_options'][] = $name; return true; }
function delete_transient(string $name): bool { $GLOBALS['deleted_transients'][] = $name; return true; }
function is_multisite(): bool { return false; }
require dirname( __DIR__ ) . '/uninstall.php';

foreach ( array('wp_piwigo_display_options','wp_piwigo_display_service_account') as $key ) {
    if ( ! in_array( $key, $GLOBALS['deleted_options'], true ) ) { fwrite(STDERR, "Option not deleted: $key\n"); exit(1); }
}
foreach ( array('wpd_api_health_metrics','wpd_album_abc','wpd_stale_def','wpd_lock_ghi') as $key ) {
    if ( ! in_array( $key, $GLOBALS['deleted_transients'], true ) ) { fwrite(STDERR, "Transient not deleted: $key\n"); exit(1); }
}
echo "Uninstall cleanup and private-cache isolation: PASS\n";
