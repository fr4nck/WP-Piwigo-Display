<?php
/**
 * Regression checks for slider transitions and direction.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/../' );

function esc_attr( $value ): string {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}

require_once __DIR__ . '/../includes/class-wpd-slider-transitions.php';

$assert_same = static function ( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$defaults = WPD_Slider_Transitions::add_defaults( array() );
$assert_same( 'slide', $defaults['transition'] ?? null, 'Le glissement doit rester la transition par défaut.' );
$assert_same( 'ltr', $defaults['direction'] ?? null, 'La direction ltr doit rester la valeur par défaut.' );

$transition_method = new ReflectionMethod( WPD_Slider_Transitions::class, 'sanitize_transition' );
$transition_method->setAccessible( true );
$direction_method = new ReflectionMethod( WPD_Slider_Transitions::class, 'sanitize_direction' );
$direction_method->setAccessible( true );

$assert_same( 'fade', $transition_method->invoke( null, 'fade' ), 'Le fondu doit être accepté.' );
$assert_same( 'none', $transition_method->invoke( null, 'none' ), 'Le mode sans animation doit être accepté.' );
$assert_same( 'slide', $transition_method->invoke( null, 'zoom' ), 'Une transition inconnue doit revenir au glissement.' );
$assert_same( 'rtl', $direction_method->invoke( null, 'rtl' ), 'La direction rtl doit être acceptée.' );
$assert_same( 'ltr', $direction_method->invoke( null, 'vertical' ), 'Une direction inconnue doit revenir à ltr.' );

$output = '<div class="wp-piwigo-display wp-piwigo-display-slider"></div>';
$injected = WPD_Slider_Transitions::inject_slider_attributes(
	$output,
	'piwigo',
	array(
		'type'       => 'slider',
		'transition' => 'fade',
		'direction'  => 'rtl',
	),
	array()
);
$assert_same( true, false !== strpos( $injected, 'data-transition="fade"' ), 'Le rendu final doit transmettre la transition au script public.' );
$assert_same( true, false !== strpos( $injected, 'data-direction="rtl"' ), 'Le rendu final doit transmettre la direction au script public.' );
$assert_same( $output, WPD_Slider_Transitions::inject_slider_attributes( $output, 'piwigo', array( 'type' => 'gallery' ), array() ), 'Une galerie ne doit pas recevoir les attributs du slider.' );

$slider = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-slider.js' );
$assert_same( true, false !== strpos( (string) $slider, "transition === 'none' ? 0" ), 'Le mode sans animation doit forcer une vitesse nulle.' );
$assert_same( true, false !== strpos( (string) $slider, "type: isFade ? 'fade' : 'loop'" ), 'Le fondu doit utiliser le type fade de Splide.' );
$assert_same( true, false !== strpos( (string) $slider, 'direction: direction' ), 'La direction doit être fournie à Splide.' );
$assert_same( true, false !== strpos( (string) $slider, 'prefers-reduced-motion: reduce' ), 'Le slider doit respecter prefers-reduced-motion.' );
$assert_same( true, false !== strpos( (string) $slider, "if (!slides.length)" ), 'Le fallback ne doit dépendre que de la présence de slides, pas de miniatures.' );
$assert_same( true, false !== strpos( (string) $slider, 'wpd-native-slider-controls' ), 'Le fallback doit créer des commandes précédent/suivant même sans miniatures.' );
$assert_same( true, false !== strpos( (string) $slider, "navigation === 'dots'" ), 'Le fallback doit recréer une pagination lorsque la navigation par points est demandée.' );
$assert_same( true, false !== strpos( (string) $slider, 'removeNativeFallbackControls(slider)' ), 'Les commandes de secours doivent être retirées quand Splide devient disponible.' );

$block = file_get_contents( __DIR__ . '/../blocks/piwigo/block.json' );
$assert_same( true, false !== strpos( (string) $block, '"transition"' ), 'Gutenberg doit exposer le réglage de transition.' );
$assert_same( true, false !== strpos( (string) $block, '"direction"' ), 'Gutenberg doit exposer le réglage de direction.' );

$classic = file_get_contents( __DIR__ . '/../assets/js/wp-piwigo-display-classic-editor.js' );
$assert_same( true, false !== strpos( (string) $classic, "'transition', 'direction'" ), 'Le composeur classique doit enregistrer transition et direction.' );

$modal = file_get_contents( __DIR__ . '/../includes/class-wpd-classic-editor.php' );
$assert_same( true, false !== strpos( (string) $modal, 'data-wpd="transition"' ), 'Le composeur classique doit afficher le champ transition.' );
$assert_same( true, false !== strpos( (string) $modal, 'data-wpd="direction"' ), 'Le composeur classique doit afficher le champ direction.' );

echo "Slider transition checks passed.\n";
