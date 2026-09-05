<?php

define('ABSPATH', __DIR__ . '/../');
define('WPD_VERSION', 'test');

final class WP_Error
{
    public function __construct(private string $code, private string $message = '') {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}

$GLOBALS['wpd_http_response'] = [];

function __(string $text, string $domain = ''): string { return $text; }
function absint($value): int { return abs((int) $value); }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function wp_json_encode($value): string { return json_encode($value); }
function esc_url_raw(string $url): string { return filter_var($url, FILTER_VALIDATE_URL) ? $url : ''; }
function wp_parse_url(string $url, int $component = -1) { return parse_url($url, $component); }
function untrailingslashit(string $value): string { return rtrim($value, '/\\'); }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function wp_remote_retrieve_response_code(array $response): int { return (int) ($response['response']['code'] ?? 0); }
function wp_remote_retrieve_body(array $response): string { return (string) ($response['body'] ?? ''); }
function wp_safe_remote_post(string $url, array $args = []) { return $GLOBALS['wpd_http_response']; }

require_once __DIR__ . '/../includes/class-wpd-api.php';

function wpd_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$api = new WPD_Api('https://gallery.example.test');

$secret = 'internal-host=10.0.0.12 token=should-never-leak';
$GLOBALS['wpd_http_response'] = new WP_Error('http_request_failed', $secret);
$result = $api->test_connection();
wpd_assert(is_wp_error($result), 'Une erreur transport doit rester une WP_Error.');
wpd_assert($result->get_error_code() === 'wpd_http_error', 'Le code transport public doit rester stable.');
wpd_assert(strpos($result->get_error_message(), $secret) === false, 'Le détail transport brut ne doit jamais être exposé.');
wpd_assert(strpos($result->get_error_message(), '10.0.0.12') === false, 'Une adresse interne reflétée ne doit jamais être exposée.');

$api = new WPD_Api('https://status.example.test');
$GLOBALS['wpd_http_response'] = [
    'response' => ['code' => 503],
    'body' => 'upstream internal failure',
];
$result = $api->test_connection();
wpd_assert(is_wp_error($result), 'Un statut HTTP non-2xx doit rester une WP_Error.');
wpd_assert($result->get_error_code() === 'wpd_http_status', 'Le code HTTP public doit rester stable.');
wpd_assert(strpos($result->get_error_message(), '503') === false, 'Le statut HTTP brut ne doit pas être exposé au visiteur.');

$api = new WPD_Api('https://api-error.example.test');
$reflected = 'forbidden for user admin from 192.168.1.20';
$GLOBALS['wpd_http_response'] = [
    'response' => ['code' => 200],
    'body' => json_encode(['stat' => 'fail', 'message' => $reflected]),
];
$result = $api->test_connection();
wpd_assert(is_wp_error($result), 'Un refus Piwigo doit rester une WP_Error.');
wpd_assert($result->get_error_code() === 'wpd_api_error', 'Le code Piwigo public doit rester stable.');
wpd_assert(strpos($result->get_error_message(), $reflected) === false, 'Le message serveur Piwigo ne doit jamais être relayé au visiteur.');
wpd_assert(strpos($result->get_error_message(), '192.168.1.20') === false, 'Une adresse interne reflétée par Piwigo ne doit jamais être exposée.');

$api = new WPD_Api('https://invalid-json.example.test');
$GLOBALS['wpd_http_response'] = [
    'response' => ['code' => 200],
    'body' => '<br>Warning: database password=secret',
];
$result = $api->test_connection();
wpd_assert(is_wp_error($result), 'Un JSON contaminé doit rester une WP_Error.');
wpd_assert($result->get_error_code() === 'wpd_invalid_json', 'Le code JSON public doit rester stable.');
wpd_assert(strpos($result->get_error_message(), 'password=secret') === false, 'Le corps contaminé ne doit jamais être relayé au visiteur.');
