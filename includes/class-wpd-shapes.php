<?php
/**
 * Shape support for Piwigo Display renderings.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and applies supported display shapes.
 */
final class WPD_Shapes {

	/**
	 * Supported shape identifiers.
	 *
	 * @var string[]
	 */
	private const SHAPES = array( 'rectangle', 'rounded', 'circle', 'oval', 'pill', 'star', 'hexagon', 'diamond' );

	/**
	 * Registers hooks for shape support.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'do_shortcode_tag', array( self::class, 'apply_shape' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_style' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_assets' ) );
	}

	/**
	 * Adds shape attributes to the shortcode defaults.
	 *
	 * @param array<string, mixed> $defaults Existing shortcode defaults.
	 * @return array<string, mixed>
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['shape']  = $defaults['shape'] ?? 'rectangle';
		$defaults['radius'] = $defaults['radius'] ?? '0';

		return $defaults;
	}

	/**
	 * Registers the public shape stylesheet.
	 *
	 * @return void
	 */
	public static function register_style(): void {
		wp_register_style(
			'wpd-shapes',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-shapes.css',
			array( 'wp-piwigo-display' ),
			WPD_VERSION
		);
	}

	/**
	 * Enqueues shape controls and preview styles in the block editor.
	 *
	 * Public styles are registered on `wp_enqueue_scripts`, which does not run on
	 * the editor screen. The editor therefore gets the same shape CSS through a
	 * dedicated handle so the visual preview matches the public rendering.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets(): void {
		wp_enqueue_style(
			'wpd-shapes-editor',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-shapes.css',
			array(),
			WPD_VERSION
		);

		wp_enqueue_script(
			'wpd-shapes-editor',
			WPD_PLUGIN_URL . 'blocks/piwigo/shapes.js',
			array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
			WPD_VERSION,
			true
		);
	}

	/**
	 * Applies shape classes and radius variables to Piwigo shortcode output.
	 *
	 * @param string              $output          Shortcode output.
	 * @param string              $tag             Shortcode tag.
	 * @param array<string,mixed> $attr            Shortcode attributes.
	 * @param array<int,mixed>    $shortcode_match Shortcode match data.
	 * @return string
	 */
	public static function apply_shape( string $output, string $tag, array $attr, array $shortcode_match ): string {
		unset( $shortcode_match );

		if ( 'piwigo' !== $tag || '' === $output ) {
			return $output;
		}

		$shape  = self::sanitize_shape( (string) ( $attr['shape'] ?? '' ) );
		$radius = self::sanitize_radius( $attr['radius'] ?? 0 );

		if ( 'rectangle' === $shape && filter_var( $attr['rounded'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
			$shape = 'rounded';

			if ( 0 === $radius ) {
				$radius = 8;
			}
		}

		if ( 'rectangle' === $shape && 0 === $radius ) {
			return $output;
		}

		wp_enqueue_style( 'wpd-shapes' );

		return (string) preg_replace_callback(
			'/<div\b([^>]*\bclass="[^"]*\bwp-piwigo-display\b[^"]*"[^>]*)>/',
			static function ( array $matches ) use ( $shape, $radius ): string {
				$attributes = $matches[1];
				$class      = 'wpd-shape-' . $shape;

				$attributes = preg_replace(
					'/\bclass="([^"]*)"/',
					'class="$1 ' . esc_attr( $class ) . '"',
					$attributes,
					1
				);

				if ( preg_match( '/\bstyle="([^"]*)"/', $attributes ) ) {
					$attributes = preg_replace(
						'/\bstyle="([^"]*)"/',
						'style="$1 --wpd-shape-radius:' . esc_attr( (string) $radius ) . '%;"',
						$attributes,
						1
					);
				} else {
					$attributes .= ' style="--wpd-shape-radius:' . esc_attr( (string) $radius ) . '%;"';
				}

				return '<div' . $attributes . '>';
			},
			$output,
			1
		);
	}

	/**
	 * Normalizes a requested shape identifier.
	 *
	 * @param string $shape Requested shape.
	 * @return string
	 */
	private static function sanitize_shape( string $shape ): string {
		$shape   = sanitize_key( $shape );
		$aliases = array(
			''         => 'rectangle',
			'none'     => 'rectangle',
			'arrondi'  => 'rounded',
			'rond'     => 'circle',
			'cercle'   => 'circle',
			'ovale'    => 'oval',
			'etoile'   => 'star',
			'étoile'   => 'star',
			'hexagone' => 'hexagon',
			'losange'  => 'diamond',
		);
		$shape   = $aliases[ $shape ] ?? $shape;

		return in_array( $shape, self::SHAPES, true ) ? $shape : 'rectangle';
	}

	/**
	 * Constrains the shape radius to the supported range.
	 *
	 * @param mixed $radius Requested radius.
	 * @return int
	 */
	private static function sanitize_radius( $radius ): int {
		return min( 50, max( 0, absint( $radius ) ) );
	}
}
