<?php
/**
 * Slider transition support.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds transition and direction attributes to slider output.
 */
final class WPD_Slider_Transitions {
	/**
	 * Registers slider transition hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'do_shortcode_tag', array( self::class, 'inject_slider_attributes' ), 10, 4 );
	}

	/**
	 * Adds slider transition defaults.
	 *
	 * @param array<string, mixed> $defaults Shortcode defaults.
	 * @return array<string, mixed>
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['transition'] = 'slide';
		$defaults['direction']  = 'ltr';

		return $defaults;
	}

	/**
	 * Injects slider transition attributes into rendered shortcode output.
	 *
	 * @param string               $output          Rendered shortcode output.
	 * @param string               $tag             Shortcode tag.
	 * @param array<string, mixed> $attr            Shortcode attributes.
	 * @param array<int, mixed>    $shortcode_match Shortcode parser match data.
	 * @return string
	 */
	public static function inject_slider_attributes( string $output, string $tag, array $attr, array $shortcode_match ): string {
		unset( $shortcode_match );

		if ( 'piwigo' !== $tag || 'slider' !== ( $attr['type'] ?? 'gallery' ) ) {
			return $output;
		}

		$transition = self::sanitize_transition( (string) ( $attr['transition'] ?? 'slide' ) );
		$direction  = self::sanitize_direction( (string) ( $attr['direction'] ?? 'ltr' ) );
		$attributes = sprintf(
			' data-transition="%s" data-direction="%s"',
			esc_attr( $transition ),
			esc_attr( $direction )
		);

		return (string) preg_replace(
			'/(<div\b[^>]*class="[^"]*\bwp-piwigo-display-slider\b[^"]*"[^>]*)(>)/',
			'$1' . $attributes . '$2',
			$output,
			1
		);
	}

	/**
	 * Sanitizes a slider transition value.
	 *
	 * @param string $transition Requested transition.
	 * @return string
	 */
	private static function sanitize_transition( string $transition ): string {
		return in_array( $transition, array( 'slide', 'fade', 'none' ), true ) ? $transition : 'slide';
	}

	/**
	 * Sanitizes a slider direction value.
	 *
	 * @param string $direction Requested direction.
	 * @return string
	 */
	private static function sanitize_direction( string $direction ): string {
		return in_array( $direction, array( 'ltr', 'rtl' ), true ) ? $direction : 'ltr';
	}
}
