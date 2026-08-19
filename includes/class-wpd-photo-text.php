<?php
/**
 * Photo-filled text layout integration.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders semantic text whose visible glyphs are filled with Piwigo photos.
 */
final class WPD_Photo_Text {
	/** @var int Unique SVG instance counter. */
	private static int $instance = 0;

	/** Registers photo-text hooks. */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'shortcode_atts_piwigo', array( self::class, 'normalize_shortcode' ), 10, 4 );
		add_filter( 'wp_piwigo_display_render', array( self::class, 'render' ), 10, 4 );
	}

	/**
	 * Adds photo-text shortcode defaults.
	 *
	 * @param array<string,mixed> $defaults Existing defaults.
	 * @return array<string,mixed>
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['photo_text']              = $defaults['photo_text'] ?? 'PÊLE-MÊLE';
		$defaults['photo_text_seed']         = $defaults['photo_text_seed'] ?? '0';
		$defaults['photo_text_font']         = $defaults['photo_text_font'] ?? 'inherit';
		$defaults['photo_text_weight']       = $defaults['photo_text_weight'] ?? '800';
		$defaults['photo_text_outline']      = $defaults['photo_text_outline'] ?? 'true';
		$defaults['photo_text_outline_width'] = $defaults['photo_text_outline_width'] ?? '3';
		$defaults['photo_text_outline_color'] = $defaults['photo_text_outline_color'] ?? '#ffffff';
		$defaults['photo_text_background']   = $defaults['photo_text_background'] ?? 'transparent';
		$defaults['photo_text_max_images']   = $defaults['photo_text_max_images'] ?? '20';

		return $defaults;
	}

	/**
	 * Normalizes photo-text aliases.
	 *
	 * @param array<string,mixed> $out       Normalized attributes.
	 * @param array<string,mixed> $pairs     Defaults, unused.
	 * @param array<string,mixed> $atts      Raw attributes.
	 * @param string              $shortcode Shortcode name, unused.
	 * @return array<string,mixed>
	 */
	public static function normalize_shortcode( array $out, array $pairs, array $atts, string $shortcode ): array {
		unset( $pairs, $shortcode );

		if ( ( $atts['type'] ?? '' ) === 'photo-text' ) {
			$out['type']   = 'gallery';
			$out['layout'] = 'photo-text';
		}

		if ( ( $atts['layout'] ?? '' ) === 'photo-text' ) {
			$out['layout'] = 'photo-text';
		}

		return $out;
	}

	/**
	 * Renders photo-filled text as accessible inline SVG.
	 *
	 * The SVG is decorative. The same text remains present as semantic HTML
	 * for assistive technologies.
	 *
	 * @param string|null         $html   Existing renderer output.
	 * @param array<int,mixed>    $images Piwigo images.
	 * @param array<string,mixed> $atts   Normalized attributes.
	 * @param string              $type   Requested type, unused.
	 * @return string|null
	 */
	public static function render( ?string $html, array $images, array $atts, string $type ): ?string {
		unset( $type );

		if ( ( $atts['layout'] ?? '' ) !== 'photo-text' ) {
			return $html;
		}

		$text = trim( sanitize_text_field( (string) ( $atts['photo_text'] ?? '' ) ) );
		if ( '' === $text ) {
			return $html;
		}

		$urls = self::image_urls( $images, min( 40, max( 1, absint( $atts['photo_text_max_images'] ?? 20 ) ) ) );
		if ( array() === $urls ) {
			return $html;
		}

		$urls = self::seeded_urls( $urls, (string) ( $atts['photo_text_seed'] ?? '0' ) );
		$font = self::font_stack( (string) ( $atts['photo_text_font'] ?? 'inherit' ) );
		$weight = min( 900, max( 100, absint( $atts['photo_text_weight'] ?? 800 ) ) );
		$outline = filter_var( $atts['photo_text_outline'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
		$outline_width = min( 12, max( 0, absint( $atts['photo_text_outline_width'] ?? 3 ) ) );
		$outline_color = sanitize_hex_color( (string) ( $atts['photo_text_outline_color'] ?? '#ffffff' ) ) ?: '#ffffff';
		$background = self::background( (string) ( $atts['photo_text_background'] ?? 'transparent' ) );

		++self::$instance;
		$id = 'wpd-photo-text-' . self::$instance . '-' . substr( md5( $text . ':' . (string) ( $atts['photo_text_seed'] ?? '0' ) ), 0, 8 );
		$clip_id = $id . '-clip';
		$columns = min( 6, max( 2, (int) ceil( sqrt( count( $urls ) * 1.5 ) ) ) );
		$rows = (int) ceil( count( $urls ) / $columns );
		$tile_width = 1200 / $columns;
		$tile_height = 360 / max( 1, $rows );

		wp_enqueue_style(
			'wp-piwigo-display-photo-text',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-photo-text.css',
			array( 'wp-piwigo-display' ),
			WPD_VERSION
		);

		ob_start();
		?>
		<div class="wp-piwigo-display wp-piwigo-display-photo-text" style="--wpd-photo-text-background:<?php echo esc_attr( $background ); ?>;">
			<span class="wpd-photo-text-semantic"><?php echo esc_html( $text ); ?></span>
			<svg class="wpd-photo-text-svg" viewBox="0 0 1200 360" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
				<defs>
					<clipPath id="<?php echo esc_attr( $clip_id ); ?>">
						<text x="600" y="190" text-anchor="middle" dominant-baseline="middle" textLength="1100" lengthAdjust="spacingAndGlyphs" style="font-family:<?php echo esc_attr( $font ); ?>;font-size:230px;font-weight:<?php echo esc_attr( (string) $weight ); ?>;"><?php echo esc_html( $text ); ?></text>
					</clipPath>
				</defs>
				<g clip-path="url(#<?php echo esc_attr( $clip_id ); ?>)">
					<?php foreach ( $urls as $index => $url ) : ?>
						<?php
						$column = $index % $columns;
						$row = (int) floor( $index / $columns );
						?>
						<image href="<?php echo esc_url( $url ); ?>" x="<?php echo esc_attr( (string) round( $column * $tile_width, 2 ) ); ?>" y="<?php echo esc_attr( (string) round( $row * $tile_height, 2 ) ); ?>" width="<?php echo esc_attr( (string) round( $tile_width + 1, 2 ) ); ?>" height="<?php echo esc_attr( (string) round( $tile_height + 1, 2 ) ); ?>" preserveAspectRatio="xMidYMid slice" />
					<?php endforeach; ?>
				</g>
				<?php if ( $outline && $outline_width > 0 ) : ?>
					<text x="600" y="190" text-anchor="middle" dominant-baseline="middle" textLength="1100" lengthAdjust="spacingAndGlyphs" fill="none" stroke="<?php echo esc_attr( $outline_color ); ?>" stroke-width="<?php echo esc_attr( (string) $outline_width ); ?>" paint-order="stroke" vector-effect="non-scaling-stroke" style="font-family:<?php echo esc_attr( $font ); ?>;font-size:230px;font-weight:<?php echo esc_attr( (string) $weight ); ?>;"><?php echo esc_html( $text ); ?></text>
				<?php endif; ?>
			</svg>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Collects displayable image URLs.
	 *
	 * @param array<int,mixed> $images Piwigo images.
	 * @param int              $limit  Maximum number of URLs.
	 * @return array<int,string>
	 */
	private static function image_urls( array $images, int $limit ): array {
		$urls = array();

		foreach ( $images as $image ) {
			if ( ! is_array( $image ) ) {
				continue;
			}

			$url = self::image_url( $image );
			if ( '' === $url ) {
				continue;
			}

			$urls[] = $url;
			if ( count( $urls ) >= $limit ) {
				break;
			}
		}

		return $urls;
	}

	/**
	 * Applies a deterministic rotation to the source image list.
	 *
	 * @param array<int,string> $urls Source URLs.
	 * @param string            $seed Stable user seed.
	 * @return array<int,string>
	 */
	private static function seeded_urls( array $urls, string $seed ): array {
		$count = count( $urls );
		if ( $count < 2 ) {
			return $urls;
		}

		$offset = (int) ( sprintf( '%u', crc32( $seed ) ) % $count );

		return array_merge( array_slice( $urls, $offset ), array_slice( $urls, 0, $offset ) );
	}

	/**
	 * Resolves a local/theme-safe font stack.
	 *
	 * @param string $font Requested font mode.
	 * @return string
	 */
	private static function font_stack( string $font ): string {
		return match ( sanitize_key( $font ) ) {
			'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'serif'  => 'Georgia, "Times New Roman", serif',
			'mono'   => 'ui-monospace, "SFMono-Regular", Consolas, monospace',
			default  => 'inherit',
		};
	}

	/**
	 * Sanitizes the surrounding background value.
	 *
	 * @param string $background Requested background.
	 * @return string
	 */
	private static function background( string $background ): string {
		if ( 'transparent' === strtolower( trim( $background ) ) ) {
			return 'transparent';
		}

		return sanitize_hex_color( $background ) ?: 'transparent';
	}

	/**
	 * Resolves the preferred display image URL.
	 *
	 * @param array<string,mixed> $image Piwigo image record.
	 * @return string
	 */
	private static function image_url( array $image ): string {
		foreach ( array( 'medium', 'small', 'thumb' ) as $size ) {
			if ( isset( $image['derivatives'][ $size ]['url'] ) ) {
				return (string) $image['derivatives'][ $size ]['url'];
			}
		}

		return isset( $image['element_url'] ) ? (string) $image['element_url'] : '';
	}
}
