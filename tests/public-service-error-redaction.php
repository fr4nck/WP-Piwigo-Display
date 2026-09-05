<?php

define('ABSPATH', __DIR__ . '/../');

final class WP_Error
{
    public function __construct(private string $code, private string $message = '') {}
    public function get_error_message(): string { return $this->message; }
}

final class WPD_Settings
{
    public static function get_shortcode_defaults(): array { return []; }
    public static function get_piwigo_url(): string { return 'https://gallery.example.test'; }
    public static function get_debug_mode(): bool { return false; }
}

final class WPD_Api
{
    public function __construct(string $url) {}
    public function resolve_album_id(string $album) { return 7; }
}

final class WPD_Cache
{
    public static function get_album_images(int $album_id, int $max, string $url, bool $recursive, int $depth)
    {
        return new WP_Error('wpd_http_error', 'SECRET-MARKER http://10.0.0.7/private');
    }
}

final class WPD_Renderer {}

function __(string $text, string $domain = ''): string { return $text; }
function add_shortcode(string $tag, $callback): void {}
function apply_filters(string $hook, $value) { return $value; }
function shortcode_atts(array $defaults, array $atts, string $shortcode = ''): array { return array_merge($defaults, $atts); }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function wp_strip_all_tags($value): string { return strip_tags((string) $value); }
function esc_url_raw(string $url): string { return filter_var($url, FILTER_VALIDATE_URL) ? $url : ''; }
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function untrailingslashit(string $value): string { return rtrim($value, '/\\'); }
function absint($value): int { return abs((int) $value); }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function esc_html($value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr($value): string { return esc_html($value); }
function current_user_can(string $capability): bool { return false; }

require_once __DIR__ . '/../includes/class-wpd-shortcode.php';

$html = WPD_Shortcode::render(['album' => '7']);

foreach (['SECRET-MARKER', '10.0.0.7', '/private'] as $forbidden) {
    if (strpos($html, $forbidden) !== false) {
        fwrite(STDERR, "Authenticated public error disclosure: {$forbidden}\n");
        exit(1);
    }
}

if (strpos($html, 'Impossible de charger les données Piwigo.') === false) {
    fwrite(STDERR, "Missing generic authenticated public error\n");
    exit(1);
}

echo "Authenticated public error redaction OK\n";
