<?php
/**
 * Masonry layout integration for Piwigo galleries.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Masonry gallery layout.
 */
final class WPD_Masonry {
	/**
	 * Registers Masonry defaults, normalization and rendering hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'shortcode_atts_piwigo', array( self::class, 'normalize_shortcode' ), 10, 4 );
		add_filter( 'wp_piwigo_display_render', array( self::class, 'render' ), 10, 4 );
	}

	/**
	 * Adds Masonry-specific shortcode defaults.
	 *
	 * @param array<string, mixed> $defaults Existing shortcode defaults.
	 * @return array<string, mixed> Updated shortcode defaults.
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['layout']          = $defaults['layout'] ?? '';
		$defaults['masonry_columns'] = $defaults['masonry_columns'] ?? '4';
		$defaults['masonry_gap']     = $defaults['masonry_gap'] ?? '16';

		return $defaults;
	}

	/**
	 * Normalizes Masonry aliases into the canonical shortcode attributes.
	 *
	 * @param array<string, mixed> $out       Normalized shortcode attributes.
	 * @param array<string, mixed> $pairs     Registered shortcode defaults, unused.
	 * @param array<string, mixed> $atts      Raw shortcode attributes.
	 * @param string               $shortcode Shortcode name, unused.
	 * @return array<string, mixed> Normalized shortcode attributes.
	 */
	public static function normalize_shortcode( array $out, array $pairs, array $atts, string $shortcode ): array {
		unset( $pairs, $shortcode );

		if ( ( $atts['type'] ?? '' ) === 'masonry' ) {
			$out['type']   = 'gallery';
			$out['layout'] = 'masonry';
		}

		if ( ( $atts['layout'] ?? '' ) === 'masonry' ) {
			$out['layout'] = 'masonry';
		}

		if ( isset( $atts['columns'] ) && ! isset( $atts['masonry_columns'] ) ) {
			$out['masonry_columns'] = $atts['columns'];
		}

		if ( isset( $atts['gap'] ) && ! isset( $atts['masonry_gap'] ) ) {
			$out['masonry_gap'] = $atts['gap'];
		}

		return $out;
	}

	/**
	 * Renders a gallery using the Masonry layout when requested.
	 *
	 * @param string              $html   Existing renderer output.
	 * @param array<int, mixed>   $images Piwigo image records.
	 * @param array<string,mixed> $atts   Normalized shortcode attributes.
	 * @param string              $type   Requested display type, unused.
	 * @return string Rendered Masonry markup or the original output.
	 */
	public static function render( string $html, array $images, array $atts, string $type ): string {
		unset( $type );

		if ( ( $atts['layout'] ?? '' ) !== 'masonry' ) {
			return $html;
		}

		wp_enqueue_style(
			'wp-piwigo-display-masonry',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-masonry.css',
			array( 'wp-piwigo-display' ),
			WPD_VERSION
		);

		$lightbox = filter_var( $atts['lightbox'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
		if ( $lightbox ) {
			wp_enqueue_script( 'wp-piwigo-display' );
		}

		$columns      = min( 6, max( 2, absint( $atts['masonry_columns'] ?? 4 ) ) );
		$gap          = min( 64, max( 0, absint( $atts['masonry_gap'] ?? 16 ) ) );
		$caption_mode = self::caption_mode( (string) ( $atts['caption'] ?? 'default' ) );
		$rounded      = filter_var( $atts['rounded'] ?? 'false', FILTER_VALIDATE_BOOLEAN );
		$style        = self::style( (string) ( $atts['style'] ?? 'default' ) );
		$classes      = 'wp-piwigo-display wp-piwigo-display-masonry wp-piwigo-display-style-' . $style;

		if ( $rounded ) {
			$classes .= ' wp-piwigo-display-rounded';
		}
		if ( $lightbox ) {
			$classes .= ' wp-piwigo-display-lightbox-enabled';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" style="--wpd-masonry-columns:<?php echo esc_attr( (string) $columns ); ?>;--wpd-masonry-gap:<?php echo esc_attr( (string) $gap ); ?>px;">
			<?php foreach ( $images as $image ) : ?>
				<?php
				if ( ! is_array( $image ) ) {
					continue;
				}

				$image_url = self::image_url( $image );
				if ( '' === $image_url ) {
					continue;
				}

				$large_url   = self::large_url( $image );
				$title       = self::title( $image );
				$description = self::description( $image );
				$caption     = self::caption_text( $title, $description, $caption_mode );
				?>
				<figure class="wp-piwigo-display-masonry-item">
					<a href="<?php echo esc_url( '' !== $large_url ? $large_url : $image_url ); ?>" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr( $caption ); ?>">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
					</a>
					<?php echo self::render_caption( $title, $description, $caption_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped markup. ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Resolves the preferred thumbnail URL for an image.
	 *
	 * @param array<string, mixed> $image Piwigo image record.
	 * @return string Thumbnail URL or an empty string.
	 */
	private static function image_url( array $image ): string {
		$paths = array(
			array( 'derivatives', 'medium', 'url' ),
			array( 'derivatives', 'small', 'url' ),
			array( 'derivatives', 'thumb', 'url' ),
		);

		foreach ( $paths as $path ) {
			if ( isset( $image[ $path[0] ][ $path[1] ][ $path[2] ] ) ) {
				return (string) $image[ $path[0] ][ $path[1] ][ $path[2] ];
			}
		}

		return isset( $image['element_url'] ) ? (string) $image['element_url'] : '';
	}

	/**
	 * Resolves the large image URL used by the lightbox.
	 *
	 * @param array<string, mixed> $image Piwigo image record.
	 * @return string Large image URL or the preferred thumbnail URL.
	 */
	private static function large_url( array $image ): string {
		if ( isset( $image['derivatives']['large']['url'] ) ) {
			return (string) $image['derivatives']['large']['url'];
		}

		return self::image_url( $image );
	}

	/**
	 * Extracts a sanitized image title.
	 *
	 * @param array<string, mixed> $image Piwigo image record.
	 * @return string Image title.
	 */
	private static function title( array $image ): string {
		return wp_strip_all_tags( (string) ( $image['name'] ?? $image['file'] ?? '' ) );
	}

	/**
	 * Extracts a plain-text image description.
	 *
	 * @param array<string, mixed> $image Piwigo image record.
	 * @return string Image description.
	 */
	private static function description( array $image ): string {
		$value = (string) ( $image['comment'] ?? $image['description'] ?? '' );

		return trim( html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	/**
	 * Validates the requested caption mode.
	 *
	 * @param string $mode Requested caption mode.
	 * @return string Supported caption mode.
	 */
	private static function caption_mode( string $mode ): string {
		if ( 'default' === $mode ) {
			$mode = WPD_Settings::get_default_caption();
		}

		return in_array( $mode, array( 'none', 'title', 'description', 'title-description' ), true ) ? $mode : 'none';
	}

	/**
	 * Validates the requested visual style.
	 *
	 * @param string $style Requested visual style.
	 * @return string Supported visual style.
	 */
	private static function style( string $style ): string {
		return in_array( $style, array( 'default', 'theme', 'minimal', 'none' ), true ) ? $style : 'default';
	}

	/**
	 * Builds the text used by lightbox metadata.
	 *
	 * @param string $title       Image title.
	 * @param string $description Image description.
	 * @param string $mode        Caption mode.
	 * @return string Caption text.
	 */
	private static function caption_text( string $title, string $description, string $mode ): string {
		if ( 'title' === $mode ) {
			return $title;
		}
		if ( 'description' === $mode ) {
			return $description;
		}
		if ( 'title-description' === $mode ) {
			return trim( implode( ' — ', array_filter( array( $title, $description ) ) ) );
		}

		return '';
	}

	/**
	 * Renders an escaped figcaption when required.
	 *
	 * @param string $title       Image title.
	 * @param string $description Image description.
	 * @param string $mode        Caption mode.
	 * @return string Escaped figcaption markup or an empty string.
	 */
	private static function render_caption( string $title, string $description, string $mode ): string {
		$show_title       = in_array( $mode, array( 'title', 'title-description' ), true ) && '' !== $title;
		$show_description = in_array( $mode, array( 'description', 'title-description' ), true ) && '' !== $description;

		if ( ! $show_title && ! $show_description ) {
			return '';
		}

		$html = '<figcaption class="wp-piwigo-display-caption">';
		if ( $show_title ) {
			$html .= '<span class="wp-piwigo-display-caption-title">' . esc_html( $title ) . '</span>';
		}
		if ( $show_description ) {
			$html .= '<span class="wp-piwigo-display-caption-description">' . esc_html( $description ) . '</span>';
		}

		return $html . '</figcaption>';
	}
}
