<?php
/**
 * Deterministic collage layout integration.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Collage / Pêle-mêle layout.
 */
final class WPD_Collage {
	/** Registers Collage hooks. */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'shortcode_atts_piwigo', array( self::class, 'normalize_shortcode' ), 10, 4 );
		add_filter( 'wp_piwigo_display_render', array( self::class, 'render' ), 10, 4 );
	}

	/**
	 * Adds Collage shortcode defaults.
	 *
	 * @param array<string,mixed> $defaults Existing defaults.
	 * @return array<string,mixed>
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['collage_seed']     = $defaults['collage_seed'] ?? '0';
		$defaults['collage_rotation'] = $defaults['collage_rotation'] ?? '6';
		$defaults['collage_spread']   = $defaults['collage_spread'] ?? '18';
		$defaults['collage_overlap']  = $defaults['collage_overlap'] ?? '12';
		$defaults['collage_size']     = $defaults['collage_size'] ?? '220';
		$defaults['collage_variation'] = $defaults['collage_variation'] ?? '20';
		return $defaults;
	}

	/**
	 * Normalizes Collage aliases.
	 *
	 * @param array<string,mixed> $out Normalized attributes.
	 * @param array<string,mixed> $pairs Defaults, unused.
	 * @param array<string,mixed> $atts Raw attributes.
	 * @param string $shortcode Shortcode name, unused.
	 * @return array<string,mixed>
	 */
	public static function normalize_shortcode( array $out, array $pairs, array $atts, string $shortcode ): array {
		unset( $pairs, $shortcode );
		if ( ( $atts['type'] ?? '' ) === 'collage' ) {
			$out['type'] = 'gallery';
			$out['layout'] = 'collage';
		}
		if ( ( $atts['layout'] ?? '' ) === 'collage' ) {
			$out['layout'] = 'collage';
		}
		return $out;
	}

	/**
	 * Renders a stable CSS collage.
	 *
	 * @param string|null $html Existing renderer output.
	 * @param array<int,mixed> $images Piwigo images.
	 * @param array<string,mixed> $atts Normalized attributes.
	 * @param string $type Requested type, unused.
	 * @return string|null
	 */
	public static function render( ?string $html, array $images, array $atts, string $type ): ?string {
		unset( $type );
		if ( ( $atts['layout'] ?? '' ) !== 'collage' ) {
			return $html;
		}

		wp_enqueue_style( 'wp-piwigo-display-collage', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-collage.css', array( 'wp-piwigo-display' ), WPD_VERSION );
		$lightbox = filter_var( $atts['lightbox'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
		if ( $lightbox ) {
			wp_enqueue_script( 'wp-piwigo-display' );
		}

		$seed      = (string) ( $atts['collage_seed'] ?? '0' );
		$rotation  = min( 15, max( 0, absint( $atts['collage_rotation'] ?? 6 ) ) );
		$spread    = min( 50, max( 0, absint( $atts['collage_spread'] ?? 18 ) ) );
		$overlap   = min( 40, max( 0, absint( $atts['collage_overlap'] ?? 12 ) ) );
		$base_size = min( 420, max( 120, absint( $atts['collage_size'] ?? 220 ) ) );
		$variation = min( 50, max( 0, absint( $atts['collage_variation'] ?? 20 ) ) );

		ob_start();
		?>
		<div class="wp-piwigo-display wp-piwigo-display-collage<?php echo $lightbox ? ' wp-piwigo-display-lightbox-enabled' : ''; ?>" style="--wpd-collage-overlap:<?php echo esc_attr( (string) $overlap ); ?>px;">
			<?php foreach ( $images as $index => $image ) : ?>
				<?php
				if ( ! is_array( $image ) ) {
					continue;
				}
				$url = self::image_url( $image );
				if ( '' === $url ) {
					continue;
				}
				$id = (string) ( $image['id'] ?? $index );
				$hash = hexdec( substr( md5( $seed . ':' . $id ), 0, 8 ) );
				$rotate = self::signed_value( $hash, $rotation, 0 );
				$x = self::signed_value( $hash, $spread, 8 );
				$y = self::signed_value( $hash, $spread, 16 );
				$size_delta = self::signed_value( $hash, $variation, 24 );
				$size = (int) round( $base_size * ( 1 + ( $size_delta / 100 ) ) );
				$title = wp_strip_all_tags( (string) ( $image['name'] ?? $image['file'] ?? '' ) );
				$large = isset( $image['derivatives']['large']['url'] ) ? (string) $image['derivatives']['large']['url'] : $url;
				?>
				<figure class="wp-piwigo-display-collage-item" style="--wpd-collage-rotate:<?php echo esc_attr( (string) $rotate ); ?>deg;--wpd-collage-x:<?php echo esc_attr( (string) $x ); ?>px;--wpd-collage-y:<?php echo esc_attr( (string) $y ); ?>px;--wpd-collage-size:<?php echo esc_attr( (string) $size ); ?>px;--wpd-collage-z:<?php echo esc_attr( (string) ( 1 + ( $hash % 17 ) ) ); ?>;">
					<a href="<?php echo esc_url( $large ); ?>" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr( $title ); ?>">
						<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
					</a>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/** Returns a deterministic signed value from one hash byte. */
	private static function signed_value( int $hash, int $maximum, int $shift ): int {
		if ( 0 === $maximum ) {
			return 0;
		}
		$byte = ( $hash >> $shift ) & 0xff;
		return (int) round( ( ( $byte / 255 ) * 2 - 1 ) * $maximum );
	}

	/** Resolves the preferred display image URL. */
	private static function image_url( array $image ): string {
		foreach ( array( 'medium', 'small', 'thumb' ) as $size ) {
			if ( isset( $image['derivatives'][ $size ]['url'] ) ) {
				return (string) $image['derivatives'][ $size ]['url'];
			}
		}
		return isset( $image['element_url'] ) ? (string) $image['element_url'] : '';
	}
}
