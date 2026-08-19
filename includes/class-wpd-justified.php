<?php
/**
 * Justified gallery layout integration.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Justified gallery layout.
 */
final class WPD_Justified {
	/**
	 * Registers defaults, aliases and rendering hook.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'shortcode_atts_piwigo', array( self::class, 'normalize_shortcode' ), 10, 4 );
		add_filter( 'wp_piwigo_display_render', array( self::class, 'render' ), 20, 4 );
	}

	/**
	 * Adds Justified-specific defaults.
	 *
	 * @param array<string, mixed> $defaults Existing defaults.
	 * @return array<string, mixed>
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['layout']               = $defaults['layout'] ?? '';
		$defaults['justified_row_height'] = $defaults['justified_row_height'] ?? '220';
		$defaults['justified_gap']        = $defaults['justified_gap'] ?? '8';

		return $defaults;
	}

	/**
	 * Normalizes shortcode aliases.
	 *
	 * @param array<string, mixed> $out       Normalized attributes.
	 * @param array<string, mixed> $pairs     Registered defaults, unused.
	 * @param array<string, mixed> $atts      Raw attributes.
	 * @param string               $shortcode Shortcode name, unused.
	 * @return array<string, mixed>
	 */
	public static function normalize_shortcode( array $out, array $pairs, array $atts, string $shortcode ): array {
		unset( $pairs, $shortcode );

		if ( ( $atts['type'] ?? '' ) === 'justified' ) {
			$out['type']   = 'gallery';
			$out['layout'] = 'justified';
		}

		if ( ( $atts['layout'] ?? '' ) === 'justified' ) {
			$out['layout'] = 'justified';
		}

		if ( isset( $atts['row_height'] ) && ! isset( $atts['justified_row_height'] ) ) {
			$out['justified_row_height'] = $atts['row_height'];
		}

		if ( isset( $atts['gap'] ) && ! isset( $atts['justified_gap'] ) ) {
			$out['justified_gap'] = $atts['gap'];
		}

		return $out;
	}

	/**
	 * Renders a Justified gallery when requested.
	 *
	 * @param string|null                 $html   Existing output.
	 * @param array<int, array<mixed>>    $images Prepared Piwigo images.
	 * @param array<string, mixed>        $atts   Normalized attributes.
	 * @param string                      $type   Requested display type, unused.
	 * @return string|null
	 */
	public static function render( ?string $html, array $images, array $atts, string $type ): ?string {
		unset( $type );

		if ( ( $atts['layout'] ?? '' ) !== 'justified' ) {
			return $html;
		}

		wp_enqueue_style(
			'wp-piwigo-display-justified',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-justified.css',
			array( 'wp-piwigo-display' ),
			WPD_VERSION
		);

		$lightbox = filter_var( $atts['lightbox'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
		if ( $lightbox ) {
			wp_enqueue_script( 'wp-piwigo-display' );
		}

		$row_height   = min( 600, max( 100, absint( $atts['justified_row_height'] ?? 220 ) ) );
		$gap          = min( 64, max( 0, absint( $atts['justified_gap'] ?? 8 ) ) );
		$caption_mode = self::caption_mode( (string) ( $atts['caption'] ?? 'default' ) );
		$rounded      = filter_var( $atts['rounded'] ?? 'false', FILTER_VALIDATE_BOOLEAN );
		$style        = self::style( (string) ( $atts['style'] ?? 'default' ) );
		$classes      = 'wp-piwigo-display wp-piwigo-display-justified wp-piwigo-display-style-' . $style;

		if ( $rounded ) {
			$classes .= ' wp-piwigo-display-rounded';
		}
		if ( $lightbox ) {
			$classes .= ' wp-piwigo-display-lightbox-enabled';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" style="--wpd-justified-row-height:<?php echo esc_attr( (string) $row_height ); ?>px;--wpd-justified-gap:<?php echo esc_attr( (string) $gap ); ?>px;">
			<?php foreach ( $images as $image ) : ?>
				<?php
				if ( ! is_array( $image ) ) {
					continue;
				}

				$image_url = self::image_url( $image );
				if ( '' === $image_url ) {
					continue;
				}

				$dimensions = self::dimensions( $image );
				$ratio      = $dimensions['width'] / $dimensions['height'];
				$basis      = max( 1, (int) round( $row_height * $ratio ) );
				$large_url  = self::large_url( $image );
				$title      = self::title( $image );
				$description = self::description( $image );
				$caption     = self::caption_text( $title, $description, $caption_mode );
				?>
				<figure class="wp-piwigo-display-justified-item" style="--wpd-justified-grow:<?php echo esc_attr( (string) $basis ); ?>;--wpd-justified-ratio:<?php echo esc_attr( (string) $ratio ); ?>;">
					<a href="<?php echo esc_url( '' !== $large_url ? $large_url : $image_url ); ?>" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr( $caption ); ?>">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" width="<?php echo esc_attr( (string) $dimensions['width'] ); ?>" height="<?php echo esc_attr( (string) $dimensions['height'] ); ?>" loading="lazy" decoding="async" />
					</a>
					<?php echo self::render_caption( $title, $description, $caption_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped markup. ?>
				</figure>
			<?php endforeach; ?>
			<span class="wp-piwigo-display-justified-tail" aria-hidden="true"></span>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/** @return array{width:int,height:int} */
	private static function dimensions( array $image ): array {
		$width  = absint( $image['width'] ?? 0 );
		$height = absint( $image['height'] ?? 0 );

		if ( ( 0 === $width || 0 === $height ) && isset( $image['derivatives']['medium'] ) && is_array( $image['derivatives']['medium'] ) ) {
			$width  = absint( $image['derivatives']['medium']['width'] ?? $width );
			$height = absint( $image['derivatives']['medium']['height'] ?? $height );
		}

		if ( 0 === $width || 0 === $height ) {
			return array( 'width' => 4, 'height' => 3 );
		}

		return array( 'width' => $width, 'height' => $height );
	}

	private static function image_url( array $image ): string {
		foreach ( array( 'medium', 'small', 'thumb' ) as $size ) {
			if ( isset( $image['derivatives'][ $size ]['url'] ) ) {
				return (string) $image['derivatives'][ $size ]['url'];
			}
		}
		return isset( $image['element_url'] ) ? (string) $image['element_url'] : '';
	}

	private static function large_url( array $image ): string {
		return isset( $image['derivatives']['large']['url'] ) ? (string) $image['derivatives']['large']['url'] : self::image_url( $image );
	}

	private static function title( array $image ): string {
		return wp_strip_all_tags( (string) ( $image['name'] ?? $image['file'] ?? '' ) );
	}

	private static function description( array $image ): string {
		$value = (string) ( $image['comment'] ?? $image['description'] ?? '' );
		return trim( html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}

	private static function caption_mode( string $mode ): string {
		if ( 'default' === $mode ) {
			$mode = WPD_Settings::get_default_caption();
		}
		return in_array( $mode, array( 'none', 'title', 'description', 'title-description' ), true ) ? $mode : 'none';
	}

	private static function style( string $style ): string {
		return in_array( $style, array( 'default', 'theme', 'minimal', 'none' ), true ) ? $style : 'default';
	}

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
