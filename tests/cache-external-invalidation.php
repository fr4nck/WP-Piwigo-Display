<?php
/**
 * Regression: explicit cache purge must invalidate persistent-object-cache transients.
 *
 * WordPress stores transients outside wp_options when an external object cache is active.
 */
declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );

final class WPD_Settings {
    public static function get_piwigo_url(): string { return 'https://photos.example.test'; }
    public static function get_cache_duration(): int { return 60; }
}
final class WPD_Service_Account {
    public static function is_configured(): bool { return true; }
    public static function get_context_hash(): string { return 'same-service-account'; }
}
final class WP_Error {}
class WPD_Api {
    public function __construct(string $url) {}
    public function get_images_from_album(int $album_id, int $max): array { return array(); }
}
final class WPD_Service_Api {
    public static int $images_id = 101;
    public static int $calls = 0;
    public function __construct(string $url) {}
    public function get_images_from_album(int $album_id, int $max): array {
        ++self::$calls;
        return array(array('id' => self::$images_id));
    }
}
final class WPD_Api_Metrics {
    public static function cache_hit(string $source): void {}
    public static function cache_miss(string $source): void {}
}
final class WPD_Empty_Transient_DB {
    public string $options = 'wp_options';
    public function esc_like(string $value): string { return $value; }
    public function prepare(string $sql, ...$args): string { return $sql; }
    public function get_col(string $query): array { return array(); }
}
function __($text, $domain = '') { return $text; }
function untrailingslashit(string $value): string { return rtrim($value, '/'); }
function absint($value): int { return abs((int) $value); }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function get_transient(string $key) { return $GLOBALS['external_cache'][$key] ?? false; }
function set_transient(string $key, $value, int $ttl): bool {
    $GLOBALS['external_cache'][$key] = $value;
    return true;
}
function get_option(string $key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option(string $key, $value, $autoload = null): bool {
    $GLOBALS['options'][$key] = $value;
    return true;
}
function wp_generate_uuid4(): string { return 'test-generation-' . ++$GLOBALS['generation_count']; }
function wp_using_ext_object_cache(): bool { return true; }
function wp_cache_add(...$args): bool { return true; }
function wp_cache_delete(...$args): bool { return true; }

$GLOBALS['wpdb'] = new WPD_Empty_Transient_DB();
$GLOBALS['external_cache'] = array();
$GLOBALS['options'] = array();
$GLOBALS['generation_count'] = 0;
require dirname(__DIR__) . '/includes/class-wpd-cache.php';

function assert_external_cache(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
function begin_new_request(): void {
    foreach (array('request_cache', 'api_clients') as $name) {
        (new ReflectionProperty(WPD_Cache::class, $name))->setValue(null, array());
    }
}
$original = WPD_Cache::get_album_images(7);
assert_external_cache(101 === $original[0]['id'] && 1 === WPD_Service_Api::$calls, 'Initial private album was not fetched.');

WPD_Service_Api::$images_id = 202;
begin_new_request();
$stale = WPD_Cache::get_album_images(7);
assert_external_cache(101 === $stale[0]['id'] && 1 === WPD_Service_Api::$calls, 'Banc does not simulate a persistent transient.');

$deleted_from_database = WPD_Cache::clear_all();
assert_external_cache(0 === $deleted_from_database, 'External-cache simulation unexpectedly had database transients.');
begin_new_request();
$fresh = WPD_Cache::get_album_images(7);
assert_external_cache(202 === $fresh[0]['id'] && 2 === WPD_Service_Api::$calls, 'Purge reused old private image data from external object cache.');
assert_external_cache(1 === $GLOBALS['generation_count'], 'Durable generation was not rotated.');
assert_external_cache(count($GLOBALS['external_cache']) >= 2, 'Test should retain old physical transient to prove logical invalidation.');

echo "Persistent-object-cache purge invalidates private album transients: PASS\n";
