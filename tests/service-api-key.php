<?php

define('ABSPATH', __DIR__ . '/../');
define('WPD_VERSION', 'test');

final class WP_Error
{
    public function __construct(private string $code, private string $message = '') {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}

final class WPD_Service_Account
{
    public static bool $api_key = true;

    public static function is_configured(): bool { return true; }
    public static function uses_api_key(): bool { return self::$api_key; }
    public static function get_api_key(): string { return 'secret-api-key'; }
    public static function get_username(): string { return 'legacy-user'; }
    public static function get_password(): string { return 'legacy-password'; }
}

final class WP_Http_Cookie
{
    public string $name = '';
}

$GLOBALS['wpd_http_calls'] = [];
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
function wp_remote_retrieve_cookies(array $response): array { return $response['cookies'] ?? []; }
function wp_safe_remote_post(string $url, array $args = []) {
    $GLOBALS['wpd_http_calls'][] = [$url, $args];
    return $GLOBALS['wpd_http_response'];
}

require_once __DIR__ . '/../includes/class-wpd-service-api.php';

function wpd_key_assert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function wpd_key_response(array $result): void
{
    $GLOBALS['wpd_http_calls'] = [];
    $GLOBALS['wpd_http_response'] = [
        'response' => ['code' => 200],
        'body' => json_encode(['stat' => 'ok', 'result' => $result]),
        'cookies' => [],
    ];
}

wpd_key_response(['username' => 'service-user']);
$api = new WPD_Service_Api('https://gallery.example.test');
$result = $api->test_connection();
wpd_key_assert(!is_wp_error($result), 'La clé API doit permettre le test de connexion.');
wpd_key_assert(count($GLOBALS['wpd_http_calls']) === 1, 'La clé API ne doit pas déclencher pwg.session.login.');
$args = $GLOBALS['wpd_http_calls'][0][1] ?? [];
wpd_key_assert(($args['headers']['X-PIWIGO-API'] ?? '') === 'secret-api-key', 'Le header X-PIWIGO-API doit contenir la clé.');
wpd_key_assert(!array_key_exists('cookies', $args), 'Le mode clé API ne doit pas envoyer de cookie de session.');
wpd_key_assert(($args['body']['method'] ?? '') === 'pwg.session.getStatus', 'Le test doit interroger directement le statut avec la clé API.');

wpd_key_response(['images' => [['id' => 9, 'latitude' => '48.1', 'longitude' => '-1.6']]]);
$images = $api->get_images_from_album(2, 1);
wpd_key_assert(!is_wp_error($images), 'La récupération d’images avec clé API doit réussir.');
wpd_key_assert(($images[0]['id'] ?? 0) === 9, 'La réponse Piwigo authentifiée doit rester intacte.');
$args = $GLOBALS['wpd_http_calls'][0][1] ?? [];
wpd_key_assert(($args['headers']['X-PIWIGO-API'] ?? '') === 'secret-api-key', 'Chaque appel authentifié doit porter X-PIWIGO-API.');
