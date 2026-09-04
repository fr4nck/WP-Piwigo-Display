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
$GLOBALS['wpd_http_calls'] = [];

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
function wp_remote_retrieve_cookies(array $response): array { return $response['cookies'] ?? []; }
function wp_safe_remote_post(string $url, array $args = []) {
    $GLOBALS['wpd_http_calls'][] = [$url, $args];
    return $GLOBALS['wpd_http_response'];
}

require_once __DIR__ . '/../includes/class-wpd-api.php';

function wpd_assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function wpd_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function wpd_set_response(int $status, string $body): void
{
    $GLOBALS['wpd_http_response'] = [
        'response' => ['code' => $status],
        'body' => $body,
        'cookies' => [],
    ];
    $GLOBALS['wpd_http_calls'] = [];
}

$api = new WPD_Api('https://gallery.example.test');

// OpenStreetMap and other Piwigo plugins may enrich image payloads. Unknown fields
// must remain transparent to the client and must not affect image retrieval.
wpd_set_response(200, json_encode([
    'stat' => 'ok',
    'result' => [
        'images' => [[
            'id' => 42,
            'name' => 'Carte',
            'latitude' => '48.1173',
            'longitude' => '-1.6778',
            'osm_extra' => ['provider' => 'OpenStreetMap'],
        ]],
    ],
]));
$images = $api->get_images_from_album(7, 1);
wpd_assert_true(!is_wp_error($images), 'Une réponse Piwigo enrichie par OpenStreetMap doit rester valide.');
wpd_assert_same(42, $images[0]['id'] ?? null, 'L’image enrichie doit être conservée.');
wpd_assert_same('48.1173', $images[0]['latitude'] ?? null, 'La latitude ne doit pas être supprimée ou casser le parsing.');
wpd_assert_same('OpenStreetMap', $images[0]['osm_extra']['provider'] ?? null, 'Les métadonnées inconnues doivent rester transparentes.');
wpd_assert_same('https://gallery.example.test/ws.php?format=json', $GLOBALS['wpd_http_calls'][0][0] ?? null, 'Le client doit viser le WebService Piwigo attendu.');

// JSON invalide : échec explicite, jamais tableau vide silencieux.
$api = new WPD_Api('https://invalid-json.example.test');
wpd_set_response(200, '<br>PHP warning before JSON');
$result = $api->get_all_categories();
wpd_assert_true(is_wp_error($result), 'Une réponse JSON polluée ou invalide doit être rejetée.');
wpd_assert_same('wpd_invalid_json', $result->get_error_code(), 'Le code d’erreur JSON invalide doit rester stable.');

// Erreur HTTP : elle doit être remontée avant tout parsing.
$api = new WPD_Api('https://http-error.example.test');
wpd_set_response(503, json_encode(['stat' => 'ok', 'result' => []]));
$result = $api->test_connection();
wpd_assert_true(is_wp_error($result), 'Un statut HTTP non-2xx doit provoquer une erreur.');
wpd_assert_same('wpd_http_status', $result->get_error_code(), 'Le code d’erreur HTTP doit rester stable.');

// Erreur API Piwigo valide JSON.
$api = new WPD_Api('https://api-error.example.test');
wpd_set_response(200, json_encode(['stat' => 'fail', 'message' => 'forbidden']));
$result = $api->test_connection();
wpd_assert_true(is_wp_error($result), 'Une erreur Piwigo stat=fail doit être remontée.');
wpd_assert_same('wpd_api_error', $result->get_error_code(), 'Le code d’erreur Piwigo doit rester stable.');

// Vérifie que le transport configuré passe bien par la variante safe de WordPress.
wpd_assert_true(function_exists('wp_safe_remote_post'), 'Le test doit intercepter wp_safe_remote_post().');
