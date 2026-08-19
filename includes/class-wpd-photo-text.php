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
	/**
	 * Unique SVG instance counter.
	 *
	 * @var int
	 */
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
		$defaults['photo_text']                = $defaults['photo_text'] ?? 'PÊLE-MÊLE';
		$defaults['photo_text_seed']           = $defaults['photo_text_seed'] ?? '0';
		$defaults['photo_text_font']           = $defaults['photo_text_font'] ?? 'inherit';
		$defaults['photo_text_weight']         = $defaults['photo_text_weight'] ?? '800';
		$defaults['photo_text_size']           = $defaults['photo_text_size'] ?? '230';
		$defaults['photo_text_letter_spacing'] = $defaults['photo_text_letter_spacing'] ?? '0';
		$defaults['photo_text_max_width']      = $defaults['photo_text_max_width'] ?? '100';
		$defaults['photo_text_align']          = $defaults['photo_text_align'] ?? 'center';
		$defaults['photo_text_fill_mode']      = $defaults['photo_text_fill_mode'] ?? 'grid';
		$defaults['photo_text_density']        = $defaults['photo_text_density'] ?? '100';
		$defaults['photo_text_rotation']       = $defaults['photo_text_rotation'] ?? '6';
		$defaults['photo_text_spread']         = $defaults['photo_text_spread'] ?? '18';
		$defaults['photo_text_outline']        = $defaults['photo_text_outline'] ?? 'true';
		$defaults['photo_text_outline_width']  = $defaults['photo_text_outline_width'] ?? '3';
		$defaults['photo_text_outline_color']  = $defaults['photo_text_outline_color'] ?? '#ffffff';
		$defaults['photo_text_background']     = $defaults['photo_text_background'] ?? 'transparent';
		$defaults['photo_text_max_images']     = $defaults['photo_text_max_images'] ?? '20';

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

		$seed           = (string) ( $atts['photo_text_seed'] ?? '0' );
		$urls           = self::seeded_urls( $urls, $seed );
		$font           = WPD_User_Fonts::font_stack( (string) ( $atts['photo_text_font'] ?? 'inherit' ) );
		$weight         = min( 900, max( 100, absint( $atts['photo_text_weight'] ?? 800 ) ) );
		$font_size      = min( 300, max( 120, absint( $atts['photo_text_size'] ?? 230 ) ) );
		$letter_spacing = min( 80, max( -20, (int) ( $atts['photo_text_letter_spacing'] ?? 0 ) ) );
		$max_width      = min( 100, max( 20, absint( $atts['photo_text_max_width'] ?? 100 ) ) );
		$align          = self::alignment( (string) ( $atts['photo_text_align'] ?? 'center' ) );
		$fill_mode      = self::fill_mode( (string) ( $atts['photo_text_fill_mode'] ?? 'grid' ) );
		$density        = min( 200, max( 50, absint( $atts['photo_text_density'] ?? 100 ) ) );
		$rotation       = min( 15, max( 0, absint( $atts['photo_text_rotation'] ?? 6 ) ) );
		$spread         = min( 50, max( 0, absint( $atts['photo_text_spread'] ?? 18 ) ) );
		$outline        = filter_var( $atts['photo_text_outline'] ?? 'true', FILTER_VALIDATE_BOOLEAN );
		$outline_width  = min( 12, max( 0, absint( $atts['photo_text_outline_width'] ?? 3 ) ) );
		$outline_color  = sanitize_hex_color( (string) ( $atts['photo_text_outline_color'] ?? '#ffffff' ) );
		$background     = self::background( (string) ( $atts['photo_text_background'] ?? 'transparent' ) );

		if ( ! $outline_color ) {
			$outline_color = '#ffffff';
		}

		$text_position = self::text_position( $align );
		$tiles         = self::layout_tiles( $urls, $fill_mode, $density, $seed, $rotation, $spread );

		++self::$instance;
		$id         = 'wpd-photo-text-' . self::$instance . '-' . substr( md5( $text . ':' . $seed ), 0, 8 );
		$clip_id    = $id . '-clip';
		$text_style = sprintf(
			'font-family:%1$s;font-size:%2$dpx;font-weight:%3$d;letter-spacing:%4$dpx;',
			$font,
			$font_size,
			$weight,
			$letter_spacing
		);

		wp_enqueue_style(
			'wp-piwigo-display-photo-text',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-photo-text.css',
			array( 'wp-piwigo-display' ),
			WPD_VERSION
		);

		ob_start();
		?>
		<div class="wp-piwigo-display wp-piwigo-display-photo-text wpd-photo-text-align-<?php echo esc_attr( $align ); ?> wpd-photo-text-fill-<?php echo esc_attr( $fill_mode ); ?>" style="--wpd-photo-text-background:<?php echo esc_attr( $background ); ?>;--wpd-photo-text-max-width:<?php echo esc_attr( (string) $max_width ); ?>%;">
			<span class="wpd-photo-text-semantic"><?php echo esc_html( $text ); ?></span>
			<svg class="wpd-photo-text-svg" viewBox="0 0 1200 360" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
				<defs>
					<clipPath id="<?php echo esc_attr( $clip_id ); ?>">
						<text x="<?php echo esc_attr( (string) $text_position['x'] ); ?>" y="190" text-anchor="<?php echo esc_attr( $text_position['anchor'] ); ?>" dominant-baseline="middle" style="<?php echo esc_attr( $text_style ); ?>"><?php echo esc_html( $text ); ?></text>
					</clipPath>
				</defs>
				<g clip-path="url(#<?php echo esc_attr( $clip_id ); ?>)">
					<?php foreach ( $tiles as $tile ) : ?>
						<image href="<?php echo esc_url( $tile['url'] ); ?>" x="<?php echo esc_attr( self::number( $tile['x'] ) ); ?>" y="<?php echo esc_attr( self::number( $tile['y'] ) ); ?>" width="<?php echo esc_attr( self::number( $tile['width'] ) ); ?>" height="<?php echo esc_attr( self::number( $tile['height'] ) ); ?>" preserveAspectRatio="xMidYMid slice"<?php echo 0.0 !== $tile['rotate'] ? ' transform="' . esc_attr( 'rotate(' . self::number( $tile['rotate'] ) . ' ' . self::number( $tile['cx'] ) . ' ' . self::number( $tile['cy'] ) . ')' ) . '"' : ''; ?> />
					<?php endforeach; ?>
				</g>
				<?php if ( $outline && $outline_width > 0 ) : ?>
					<text x="<?php echo esc_attr( (string) $text_position['x'] ); ?>" y="190" text-anchor="<?php echo esc_attr( $text_position['anchor'] ); ?>" dominant-baseline="middle" fill="none" stroke="<?php echo esc_attr( $outline_color ); ?>" stroke-width="<?php echo esc_attr( (string) $outline_width ); ?>" paint-order="stroke" vector-effect="non-scaling-stroke" style="<?php echo esc_attr( $text_style ); ?>"><?php echo esc_html( $text ); ?></text>
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
	 * Produces deterministic SVG image tiles for the requested fill mode.
	 *
	 * @param array<int,string> $urls     Source image URLs.
	 * @param string            $mode     Fill mode.
	 * @param int               $density  Density percentage.
	 * @param string            $seed     Stable user seed.
	 * @param int               $rotation Maximum collage rotation.
	 * @param int               $spread   Maximum collage displacement.
	 * @return array<int,array{url:string,x:float,y:float,width:float,height:float,rotate:float,cx:float,cy:float}>
	 */
	private static function layout_tiles( array $urls, string $mode, int $density, string $seed, int $rotation, int $spread ): array {
		if ( 'masonry' === $mode ) {
			return self::masonry_tiles( $urls, $density, $seed );
		}

		if ( 'collage' === $mode ) {
			return self::collage_tiles( $urls, $density, $seed, $rotation, $spread );
		}

		return self::grid_tiles( $urls, $density );
	}

	/**
	 * Builds a gap-free regular grid, cycling source URLs when required.
	 *
	 * @param array<int,string> $urls    Source image URLs.
	 * @param int               $density Density percentage.
	 * @return array<int,array{url:string,x:float,y:float,width:float,height:float,rotate:float,cx:float,cy:float}>
	 */
	private static function grid_tiles( array $urls, int $density ): array {
		$target      = self::target_tile_count( count( $urls ), $density );
		$columns     = min( 8, max( 2, (int) ceil( sqrt( $target * 1.5 ) ) ) );
		$rows        = max( 1, (int) ceil( $target / $columns ) );
		$tile_width  = 1200 / $columns;
		$tile_height = 360 / $rows;
		$tiles       = array();
		$total       = $columns * $rows;

		for ( $index = 0; $index < $total; ++$index ) {
			$column  = $index % $columns;
			$row     = (int) floor( $index / $columns );
			$tiles[] = self::tile(
				$urls[ $index % count( $urls ) ],
				$column * $tile_width,
				$row * $tile_height,
				$tile_width + 1,
				$tile_height + 1
			);
		}

		return $tiles;
	}

	/**
	 * Builds deterministic variable-height columns that fully cover the SVG.
	 *
	 * @param array<int,string> $urls    Source image URLs.
	 * @param int               $density Density percentage.
	 * @param string            $seed    Stable user seed.
	 * @return array<int,array{url:string,x:float,y:float,width:float,height:float,rotate:float,cx:float,cy:float}>
	 */
	private static function masonry_tiles( array $urls, int $density, string $seed ): array {
		$target       = self::target_tile_count( count( $urls ), $density );
		$columns      = min( 6, max( 2, (int) ceil( sqrt( $target * 1.25 ) ) ) );
		$segments     = max( 2, (int) ceil( $target / $columns ) );
		$column_width = 1200 / $columns;
		$tiles        = array();
		$url_index    = 0;

		for ( $column = 0; $column < $columns; ++$column ) {
			$weights      = array();
			$total_weight = 0;
			for ( $row = 0; $row < $segments; ++$row ) {
				$hash      = hexdec( substr( md5( $seed . ':masonry:' . $column . ':' . $row ), 0, 8 ) );
				$weight    = 80 + ( $hash % 41 );
				$weights[] = $weight;

				$total_weight += $weight;
			}

			$y = 0.0;
			foreach ( $weights as $row => $weight ) {
				$height  = ( $row === $segments - 1 ) ? 360 - $y : 360 * ( $weight / $total_weight );
				$tiles[] = self::tile(
					$urls[ $url_index % count( $urls ) ],
					$column * $column_width,
					$y,
					$column_width + 1,
					$height + 1
				);
				$y      += $height;
				++$url_index;
			}
		}

		return $tiles;
	}

	/**
	 * Builds an overlapping deterministic mini-collage inside the text mask.
	 *
	 * @param array<int,string> $urls     Source image URLs.
	 * @param int               $density  Density percentage.
	 * @param string            $seed     Stable user seed.
	 * @param int               $rotation Maximum rotation in degrees.
	 * @param int               $spread   Maximum displacement in SVG units.
	 * @return array<int,array{url:string,x:float,y:float,width:float,height:float,rotate:float,cx:float,cy:float}>
	 */
	private static function collage_tiles( array $urls, int $density, string $seed, int $rotation, int $spread ): array {
		$target      = self::target_tile_count( count( $urls ), $density );
		$columns     = min( 7, max( 2, (int) ceil( sqrt( $target * 1.5 ) ) ) );
		$rows        = max( 1, (int) ceil( $target / $columns ) );
		$tile_width  = 1200 / $columns;
		$tile_height = 360 / $rows;
		$pad_x       = ( $tile_width * 0.22 ) + $spread;
		$pad_y       = ( $tile_height * 0.28 ) + $spread;
		$total       = $columns * $rows;
		$tiles       = array();

		for ( $index = 0; $index < $total; ++$index ) {
			$column  = $index % $columns;
			$row     = (int) floor( $index / $columns );
			$hash    = hexdec( substr( md5( $seed . ':collage:' . $index ), 0, 8 ) );
			$rotate  = self::signed_value( $hash, $rotation, 0 );
			$dx      = self::signed_value( $hash, $spread, 8 );
			$dy      = self::signed_value( $hash, $spread, 16 );
			$x       = ( $column * $tile_width ) - $pad_x + $dx;
			$y       = ( $row * $tile_height ) - $pad_y + $dy;
			$width   = $tile_width + ( 2 * $pad_x );
			$height  = $tile_height + ( 2 * $pad_y );
			$tiles[] = self::tile( $urls[ $index % count( $urls ) ], $x, $y, $width, $height, (float) $rotate );
		}

		return $tiles;
	}

	/**
	 * Returns a bounded target number of visual tiles.
	 *
	 * @param int $source_count Number of available source images.
	 * @param int $density      Density percentage.
	 * @return int
	 */
	private static function target_tile_count( int $source_count, int $density ): int {
		return min( 48, max( 4, (int) round( $source_count * ( $density / 100 ) ) ) );
	}

	/**
	 * Creates one normalized SVG tile.
	 *
	 * @param string $url    Image URL.
	 * @param float  $x      X coordinate.
	 * @param float  $y      Y coordinate.
	 * @param float  $width  Width.
	 * @param float  $height Height.
	 * @param float  $rotate Rotation in degrees.
	 * @return array{url:string,x:float,y:float,width:float,height:float,rotate:float,cx:float,cy:float}
	 */
	private static function tile( string $url, float $x, float $y, float $width, float $height, float $rotate = 0.0 ): array {
		return array(
			'url'    => $url,
			'x'      => $x,
			'y'      => $y,
			'width'  => $width,
			'height' => $height,
			'rotate' => $rotate,
			'cx'     => $x + ( $width / 2 ),
			'cy'     => $y + ( $height / 2 ),
		);
	}

	/**
	 * Returns a deterministic signed value from one hash byte.
	 *
	 * @param int $hash    Source hash.
	 * @param int $maximum Maximum absolute value.
	 * @param int $shift   Bit shift used to select the hash byte.
	 * @return int
	 */
	private static function signed_value( int $hash, int $maximum, int $shift ): int {
		if ( 0 === $maximum ) {
			return 0;
		}

		$byte = ( $hash >> $shift ) & 0xff;
		return (int) round( ( ( $byte / 255 ) * 2 - 1 ) * $maximum );
	}

	/**
	 * Formats SVG numbers without unstable long decimal tails.
	 *
	 * @param float $value Numeric value.
	 * @return string
	 */
	private static function number( float $value ): string {
		return rtrim( rtrim( number_format( $value, 2, '.', '' ), '0' ), '.' );
	}

	/**
	 * Sanitizes the fill mode.
	 *
	 * @param string $mode Requested fill mode.
	 * @return string
	 */
	private static function fill_mode( string $mode ): string {
		$mode = sanitize_key( $mode );
		return in_array( $mode, array( 'grid', 'masonry', 'collage' ), true ) ? $mode : 'grid';
	}

	/**
	 * Sanitizes photo-text alignment.
	 *
	 * @param string $align Requested alignment.
	 * @return string
	 */
	private static function alignment( string $align ): string {
		$align = sanitize_key( $align );
		return in_array( $align, array( 'left', 'center', 'right' ), true ) ? $align : 'center';
	}

	/**
	 * Returns SVG position data for the requested alignment.
	 *
	 * @param string $align Normalized alignment.
	 * @return array{x:int,anchor:string}
	 */
	private static function text_position( string $align ): array {
		if ( 'left' === $align ) {
			return array(
				'x'      => 50,
				'anchor' => 'start',
			);
		}

		if ( 'right' === $align ) {
			return array(
				'x'      => 1150,
				'anchor' => 'end',
			);
		}

		return array(
			'x'      => 600,
			'anchor' => 'middle',
		);
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

		$color = sanitize_hex_color( $background );
		return $color ? $color : 'transparent';
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
