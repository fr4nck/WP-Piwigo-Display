<?php
/**
 * Server-side integration for the Piwigo Gutenberg block.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the block and routes rendering through the shortcode pipeline.
 */
final class WPD_Block {
	/**
	 * Registers the block type and its editor assets.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_block_type(
			WPD_PLUGIN_DIR . 'blocks/piwigo',
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);

		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( self::class, 'enqueue_preview_styles' ) );
	}

	/**
	 * Loads the public styles in the editor canvas so previews match the front end.
	 *
	 * @return void
	 */
	public static function enqueue_preview_styles(): void {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style( 'wpd-splide', WPD_PLUGIN_URL . 'assets/vendor/splide/splide.min.css', array(), '4.1.4' );
		wp_enqueue_style( 'wp-piwigo-display', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display.css', array( 'wpd-splide' ), WPD_VERSION );
	}

	/**
	 * Enqueues the Gutenberg editor controls used by the block.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets(): void {
		wp_enqueue_script(
			'wpd-block-masonry-controls',
			WPD_PLUGIN_URL . 'blocks/piwigo/masonry-controls.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-i18n' ),
			WPD_VERSION,
			true
		);
	}

	/**
	 * Converts block attributes to the format accepted by the Piwigo shortcode.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return array<string, string> Shortcode attributes.
	 */
	public static function attributes_to_shortcode( array $attributes ): array {
		$map  = array_merge(
			array(
				'albumId'        => 'album',
				'displayType'    => 'type',
				'preset'         => 'preset',
				'piwigoUrl'      => 'url',
				'recursive'      => 'recursive',
				'depth'          => 'depth',
				'limit'          => 'limit',
				'max'            => 'max',
				'latest'         => 'latest',
				'random'         => 'random',
				'sort'           => 'sort',
				'order'          => 'order',
				'orientations'   => 'orientation',
				'caption'        => 'caption',
				'lightbox'       => 'lightbox',
				'clickAction'    => 'click_action',
				'rounded'        => 'rounded',
				'style'          => 'style',
				'shape'          => 'shape',
				'radius'         => 'radius',
				'autoplay'       => 'autoplay',
				'interval'       => 'interval',
				'speed'          => 'speed',
				'transition'     => 'transition',
				'direction'      => 'direction',
				'ratio'          => 'ratio',
				'width'          => 'width',
				'height'         => 'height',
				'align'          => 'align',
				'fit'            => 'fit',
				'navigation'     => 'navigation',
				'tag'            => 'tag',
				'tags'           => 'tags',
				'tagMode'        => 'tag_mode',
				'masonryColumns' => 'masonry_columns',
				'masonryGap'     => 'masonry_gap',
			),
			array(
				'transparentBackground' => 'transparent_background',
			),
			array(
				'justifiedRowHeight' => 'justified_row_height',
				'justifiedGap'       => 'justified_gap',
			),
			array(
				'collageSeed'      => 'collage_seed',
				'collageRotation'  => 'collage_rotation',
				'collageSpread'    => 'collage_spread',
				'collageOverlap'   => 'collage_overlap',
				'collageSize'      => 'collage_size',
				'collageVariation' => 'collage_variation',
			),
			array(
				'photoText'              => 'photo_text',
				'photoTextSeed'          => 'photo_text_seed',
				'photoTextFont'          => 'photo_text_font',
				'photoTextWeight'        => 'photo_text_weight',
				'photoTextSize'          => 'photo_text_size',
				'photoTextLetterSpacing' => 'photo_text_letter_spacing',
				'photoTextLineHeight'    => 'photo_text_line_height',
				'photoTextMaxWidth'      => 'photo_text_max_width',
				'photoTextAlign'         => 'photo_text_align',
				'photoTextFillMode'      => 'photo_text_fill_mode',
				'photoTextDensity'       => 'photo_text_density',
				'photoTextRotation'      => 'photo_text_rotation',
				'photoTextSpread'        => 'photo_text_spread',
				'photoTextMaxImages'     => 'photo_text_max_images',
				'photoTextOutline'       => 'photo_text_outline',
				'photoTextOutlineWidth'  => 'photo_text_outline_width',
				'photoTextOutlineColor'  => 'photo_text_outline_color',
				'photoTextBackground'    => 'photo_text_background',
			)
		);
		$atts = array();

		foreach ( $map as $block_key => $shortcode_key ) {
			if ( ! array_key_exists( $block_key, $attributes ) ) {
				continue;
			}

			$value = $attributes[ $block_key ];
			if ( is_array( $value ) ) {
				$value = implode( ',', array_map( 'sanitize_text_field', $value ) );
			} elseif ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			} else {
				$value = (string) $value;
			}

			$atts[ $shortcode_key ] = $value;
		}

		return $atts;
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress requires this callback signature.
	/**
	 * Renders the block through the existing shortcode renderer.
	 *
	 * Direct block rendering does not run WordPress's `do_shortcode_tag` filter,
	 * so post-shortcode decorators that depend on that filter must be applied here
	 * explicitly to preserve parity with `[piwigo]`.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Saved block content, unused.
	 * @param WP_Block|null        $block      Parsed block instance, unused.
	 * @return string Rendered block markup.
	 */
	public static function render( array $attributes = array(), string $content = '', ?WP_Block $block = null ): string {
		$atts   = self::attributes_to_shortcode( $attributes );
		$output = WPD_Shortcode::render( $atts );
		$output = WPD_Shapes::apply_shape( $output, 'piwigo', $atts, array() );
		$output = WPD_Custom_SVG_Masks::apply_mask( $output, 'piwigo', $atts, array() );

		return WPD_Slider_Transitions::inject_slider_attributes( $output, 'piwigo', $atts, array() );
	}
	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
}
