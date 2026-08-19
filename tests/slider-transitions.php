<?php

define('ABSPATH', __DIR__ . '/../');

function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function absint($value): int { return abs((int) $value); }
function esc_url_raw($value): string { return filter_var((string) $value, FILTER_SANITIZE_URL); }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function untrailingslashit($value): string { return rtrim((string) $value, '/\\'); }

require_once __DIR__ . '/../includes/class-wpd-shortcode.php';

function wpd_transition_assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$method = new ReflectionMethod(WPD_Shortcode::class, 'sanitize_atts');
$method->setAccessible(true);
$sanitize = static function (array $attributes) use ($method): array {
    return $method->invoke(null, $attributes);
};

wpd_transition_assert_same('slide', $sanitize([])['transition'], 'Le glissement doit rester la transition par défaut.');
wpd_transition_assert_same('fade', $sanitize(['transition' => 'fade'])['transition'], 'Le fondu doit être accepté.');
wpd_transition_assert_same('none', $sanitize(['transition' => 'none'])['transition'], 'Le mode sans animation doit être accepté.');
wpd_transition_assert_same('slide', $sanitize(['transition' => 'zoom'])['transition'], 'Une transition inconnue doit revenir au glissement.');
wpd_transition_assert_same('ltr', $sanitize([])['direction'], 'La direction ltr doit rester la valeur par défaut.');
wpd_transition_assert_same('rtl', $sanitize(['direction' => 'rtl'])['direction'], 'La direction rtl doit être acceptée.');
wpd_transition_assert_same('ltr', $sanitize(['direction' => 'vertical'])['direction'], 'Une direction inconnue doit revenir à ltr.');
wpd_transition_assert_same('420', $sanitize(['speed' => '420'])['speed'], 'La vitesse de transition doit rester indépendante.');
wpd_transition_assert_same('7200', $sanitize(['interval' => '7200'])['interval'], 'La durée d’affichage doit rester indépendante.');

$renderer = file_get_contents(__DIR__ . '/../includes/class-wpd-renderer.php');
wpd_transition_assert_same(true, strpos($renderer, 'data-transition') !== false, 'Le rendu doit transmettre la transition au script public.');
wpd_transition_assert_same(true, strpos($renderer, 'data-direction') !== false, 'Le rendu doit transmettre la direction au script public.');

$slider = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-slider.js');
wpd_transition_assert_same(true, strpos($slider, "transition === 'none' ? 0") !== false, 'Le mode sans animation doit forcer une vitesse nulle.');
wpd_transition_assert_same(true, strpos($slider, "type: isFade ? 'fade' : 'loop'") !== false, 'Le fondu doit utiliser le type fade de Splide.');
wpd_transition_assert_same(true, strpos($slider, 'direction: direction') !== false, 'La direction doit être fournie à Splide.');

$block = file_get_contents(__DIR__ . '/../blocks/piwigo/index.js');
wpd_transition_assert_same(true, strpos($block, "'transition'") !== false, 'Gutenberg doit exposer le réglage de transition.');
wpd_transition_assert_same(true, strpos($block, "'direction'") !== false, 'Gutenberg doit exposer le réglage de direction.');

$classic = file_get_contents(__DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js');
wpd_transition_assert_same(true, strpos($classic, "'transition', 'direction'") !== false, 'Le composeur classique doit enregistrer transition et direction.');

$modal = file_get_contents(__DIR__ . '/../includes/class-wpd-classic-editor.php');
wpd_transition_assert_same(true, strpos($modal, 'data-wpd="transition"') !== false, 'Le composeur classique doit afficher le champ transition.');
wpd_transition_assert_same(true, strpos($modal, 'data-wpd="direction"') !== false, 'Le composeur classique doit afficher le champ direction.');
