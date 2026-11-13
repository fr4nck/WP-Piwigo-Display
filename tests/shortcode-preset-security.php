<?php
/** Runtime regressions for WordPress shortcode callbacks, presets and safe public errors. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

final class WP_Error {
    public function __construct(private string $message) {}
    public function get_error_message(): string { return $this->message; }
}
final class WPD_Settings {
    public static function get_shortcode_defaults(): array { return array( 'type' => 'gallery' ); }
    public static function get_piwigo_url(): string { return 'https://gallery.example.test'; }
    public static function get_debug_mode(): bool { return false; }
}
final class WPD_Api {
    public function __construct(string $url) {}
    public function resolve_album_id(string $value) {
        return 'secret-error' === $value ? new WP_Error('private-token=leak') : (int) $value;
    }
}
final class WPD_Cache {
    public static function resolve_album_id(string $value, string $url) {
        return 'secret-error' === $value ? new WP_Error('private-token=leak') : (int) $value;
    }
    public static function get_album_images(int $id, int $max, string $url, bool $recursive, int $depth) {
        return 999 === $id ? new WP_Error('secret.password=private-data') : array( array( 'id' => 1 ) );
    }
}
final class WPD_Renderer {
    public static function render(array $images, array $atts): string {
        return 'type=' . $atts['type'] . ';limit=' . $atts['limit'] . ';fit=' . $atts['fit'];
    }
}
function __(string $str, string $domain = ''): string { return $str; }
function apply_filters(string $hook, $value) { return $value; }
function shortcode_atts(array $defaults, array $input, string $hook = ''): array {
    return array_merge($defaults, array_intersect_key($input, $defaults));
}
function sanitize_text_field($text): string { return trim(strip_tags((string) $text)); }
function wp_strip_all_tags($text): string { return strip_tags((string) $text); }
function sanitize_key($text): string { return strtolower(preg_replace('/[^a-z0-9_\\-]/', '', (string) $text)); }
function remove_accents($text): string { return strtr((string) $text, array('é' => 'e', 'è' => 'e', 'ê' => 'e', 'œ' => 'oe')); }
function absint($value): int { return abs((int) $value); }
function esc_attr($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function is_wp_error($value): bool { return $value instanceof WP_Error; }

require dirname( __DIR__ ) . '/includes/class-wpd-shapes.php';
require dirname( __DIR__ ) . '/includes/class-wpd-slider-transitions.php';
require dirname( __DIR__ ) . '/includes/class-wpd-shortcode.php';

$check = static function (bool $ok, string $description): void {
    if (!$ok) { fwrite(STDERR, $description . PHP_EOL); exit(1); }
};

$check('ok' === WPD_Shapes::apply_shape('ok', 'gallery', '', array()), 'Forme : shortcode WordPress sans attribut.');
$check('ok' === WPD_Shapes::apply_shape('ok', 'piwigo', '', array()), 'Forme : Piwigo sans attribut.');
$check('ok' === WPD_Slider_Transitions::inject_slider_attributes('ok', 'gallery', '', array()), 'Transition : shortcode WordPress sans attribut.');
$check('ok' === WPD_Slider_Transitions::inject_slider_attributes('ok', 'piwigo', '', array()), 'Transition : Piwigo sans attribut.');
$check(str_contains(WPD_Shortcode::render(''), 'Aucun album'), 'Le shortcode [piwigo] sans attribut doit produire un message.');
$check(str_contains(WPD_Shortcode::render(array('album'=>'154','preset'=>'slider')), 'type=slider'), 'Preset slider : type incorrect.');
$check(str_contains(WPD_Shortcode::render(array('album'=>'154','preset'=>'actualites')), 'type=slider;limit=12'), 'Preset actualites : mauvais réglages.');
$check(str_contains(WPD_Shortcode::render(array('album'=>'154','preset'=>'slider','type'=>'gallery')), 'type=gallery'), 'Un type explicite doit primer.');
$check(str_contains(WPD_Shortcode::render(array('album'=>'154','preset'=>'actualites','limit'=>'5')), 'limit=5'), 'Une limite explicite doit primer.');
$check(!str_contains(WPD_Shortcode::render(array('album'=>'secret-error')), 'private-token='), 'Le détail de résolution API fuit au public.');
$check(!str_contains(WPD_Shortcode::render(array('album'=>'999')), 'secret.password='), 'Le détail API fuite au public.');

echo "Shortcodes, presets, erreurs publiques : 11 PASS.\n";
