<?php
/**
 * Shortcode rendering entry point.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the public Piwigo shortcode.
 */
final class WPD_Shortcode {

	/**
	 * Registers the shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'piwigo', array( self::class, 'render' ) );
	}

	/**
	 * Renders a Piwigo gallery or slider.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public static function render( array $atts = array() ): string {
		$defaults = array_merge(
			array(
				'album'       => '',
				'preset'      => '',
				'latest'      => '0',
				'random'      => '0',
				'max'         => '0',
				'limit'       => '0',
				'sort'        => 'manual',
				'order'       => 'desc',
				'url'         => '',
				'recursive'   => 'false',
				'depth'       => '10',
				'caption'     => 'default',
				'style'       => 'default',
				'orientation' => 'all',
				'tag'         => '',
				'tags'        => '',
				'tag_mode'    => 'any',
				'width'       => '100%',
				'align'       => 'center',
			),
			WPD_Settings::get_shortcode_defaults()
		);

		/**
		 * Filters the default shortcode attributes.
		 *
		 * @param array<string, mixed> $defaults Default attributes.
		 */
		$defaults = apply_filters( 'wp_piwigo_display_shortcode_defaults', $defaults );
		$atts     = shortcode_atts( $defaults, $atts, 'piwigo' );
		$atts     = self::apply_preset( $atts );
		$atts     = self::sanitize_atts( $atts );

		$album_value = trim( (string) $atts['album'] );

		if ( '' === $album_value ) {
			return self::render_error( __( 'Aucun album Piwigo n’a été indiqué. Exemple : [piwigo album="154"].', 'wp-piwigo-display' ) );
		}

		$piwigo_url = isset( $atts['url'] ) && '' !== $atts['url']
			? (string) $atts['url']
			: WPD_Settings::get_piwigo_url();

		if ( '' === $piwigo_url ) {
			return self::render_error( __( 'URL de la galerie Piwigo non configurée. Vérifiez les réglages du plugin.', 'wp-piwigo-display' ) );
		}

		$api      = new WPD_Api( $piwigo_url );
		$album_id = $api->resolve_album_id( $album_value );

		if ( is_wp_error( $album_id ) ) {
			return self::render_error( $album_id->get_error_message() );
		}

		$recursive                 = filter_var( $atts['recursive'], FILTER_VALIDATE_BOOLEAN );
		$depth                     = max( 0, absint( $atts['depth'] ) );
		$tag_filter                = self::normalize_tag_filter( (string) ( $atts['tag'] ?? '' ), (string) ( $atts['tags'] ?? '' ) );
		$has_tag_filter            = ! empty( $tag_filter );
		$fetch_max                 = 'all' === (string) $atts['orientation'] && ! $has_tag_filter ? absint( $atts['max'] ) : 0;
		$images_prefiltered_by_tag = false;
		$images                    = WPD_Cache::get_album_images( absint( $album_id ), $fetch_max, $piwigo_url, $recursive, $depth );

		if ( is_wp_error( $images ) ) {
			return self::render_error( $images->get_error_message() );
		}

		if ( empty( $images ) ) {
			return self::render_error( __( 'Aucune image n’a été trouvée dans cet album Piwigo.', 'wp-piwigo-display' ) );
		}

		if ( $has_tag_filter && ! self::images_contain_tag_data( $images ) ) {
			$images = WPD_Cache::get_album_images_by_tags(
				absint( $album_id ),
				$tag_filter,
				(string) $atts['tag_mode'],
				$piwigo_url,
				$recursive,
				$depth
			);

			if ( is_wp_error( $images ) ) {
				return self::render_error( $images->get_error_message() );
			}

			$images_prefiltered_by_tag = true;
		}

		if ( ! $images_prefiltered_by_tag ) {
			$images = self::filter_images_by_tags( $images, $tag_filter, (string) $atts['tag_mode'] );
		}

		if ( empty( $images ) && $has_tag_filter ) {
			return self::render_error( __( 'Aucune image ne correspond aux tags demandés.', 'wp-piwigo-display' ) );
		}

		$images = self::filter_images_by_orientation( $images, (string) $atts['orientation'] );

		if ( empty( $images ) ) {
			return self::render_error( __( 'Aucune image ne correspond à l’orientation demandée.', 'wp-piwigo-display' ) );
		}

		$html = WPD_Renderer::render( $images, $atts );

		if ( 'slider' === (string) ( $atts['type'] ?? '' ) ) {
			$width = (string) ( $atts['width'] ?? '100%' );
			$align = (string) ( $atts['align'] ?? 'center' );
			$html  = sprintf(
				'<div class="wpd-slider-layout wpd-slider-align-%1$s" style="--wpd-slider-width:%2$s">%3$s</div>',
				esc_attr( $align ),
				esc_attr( $width ),
				$html
			);
		}

		if ( WPD_Settings::get_debug_mode() && current_user_can( 'manage_options' ) ) {
			$html .= self::render_debug( absint( $album_id ), $piwigo_url, $atts, is_array( $images ) ? count( $images ) : 0 );
		}

		return $html;
	}

	/**
	 * Checks whether at least one image contains tag data.
	 *
	 * @param array<int, mixed> $images Images returned by Piwigo.
	 * @return bool Whether tag data is available.
	 */
	private static function images_contain_tag_data( array $images ): bool {
		foreach ( $images as $image ) {
			if ( is_array( $image ) && array_key_exists( 'tags', $image ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Filters images by requested tags.
	 *
	 * @param array<int, array<string, mixed>> $images         Images.
	 * @param array<int, string>               $requested_tags Requested tags.
	 * @param string                           $tag_mode       Matching mode.
	 * @return array<int, array<string, mixed>> Filtered images.
	 */
	private static function filter_images_by_tags( array $images, array $requested_tags, string $tag_mode ): array {
		if ( empty( $requested_tags ) ) {
			return $images;
		}

		return array_values(
			array_filter(
				$images,
				static function ( array $image ) use ( $requested_tags, $tag_mode ): bool {
					$image_tags = self::get_normalized_image_tags( $image );

					if ( empty( $image_tags ) ) {
						return false;
					}

					$matches = array_intersect( $requested_tags, $image_tags );

					if ( 'all' === $tag_mode ) {
						return count( $matches ) === count( $requested_tags );
					}

					return ! empty( $matches );
				}
			)
		);
	}

	/**
	 * Normalizes shortcode tag filters.
	 *
	 * @param string $tag  Single tag attribute.
	 * @param string $tags Multiple tag attribute.
	 * @return array<int, string> Normalized tags.
	 */
	private static function normalize_tag_filter( string $tag, string $tags ): array {
		$values     = array_merge( explode( ',', $tag ), explode( ',', $tags ) );
		$normalized = array();

		foreach ( $values as $value ) {
			$tag_name = self::normalize_tag_name( $value );

			if ( '' !== $tag_name ) {
				$normalized[ $tag_name ] = $tag_name;
			}
		}

		return array_values( $normalized );
	}

	/**
	 * Extracts and normalizes image tags.
	 *
	 * @param array<string, mixed> $image Image data.
	 * @return array<int, string> Normalized tags.
	 */
	private static function get_normalized_image_tags( array $image ): array {
		if ( ! isset( $image['tags'] ) ) {
			return array();
		}

		$tags = $image['tags'];

		if ( is_string( $tags ) ) {
			$tags = explode( ',', $tags );
		}

		if ( ! is_array( $tags ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $tags as $tag ) {
			$value = '';

			if ( is_array( $tag ) ) {
				foreach ( array( 'name', 'url_name', 'value' ) as $key ) {
					if ( isset( $tag[ $key ] ) ) {
						$value = (string) $tag[ $key ];
						break;
					}
				}
			} elseif ( is_scalar( $tag ) ) {
				$value = (string) $tag;
			}

			$tag_name = self::normalize_tag_name( $value );

			if ( '' !== $tag_name ) {
				$normalized[ $tag_name ] = $tag_name;
			}
		}

		return array_values( $normalized );
	}

	/**
	 * Normalizes one tag name.
	 *
	 * @param string $tag Tag name.
	 * @return string Normalized tag name.
	 */
	private static function normalize_tag_name( string $tag ): string {
		$tag = trim( wp_strip_all_tags( $tag ) );

		if ( '' === $tag ) {
			return '';
		}

		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $tag, 'UTF-8' ) : strtolower( $tag );
	}

	/**
	 * Filters images by orientation.
	 *
	 * @param array<int, array<string, mixed>> $images      Images.
	 * @param string                           $orientation Requested orientation.
	 * @return array<int, array<string, mixed>> Filtered images.
	 */
	private static function filter_images_by_orientation( array $images, string $orientation ): array {
		$orientation = self::sanitize_orientation( $orientation );

		if ( 'all' === $orientation ) {
			return $images;
		}

		$orientations = explode( ',', $orientation );

		return array_values(
			array_filter(
				$images,
				static function ( array $image ) use ( $orientations ): bool {
					$width  = absint( $image['width'] ?? 0 );
					$height = absint( $image['height'] ?? 0 );

					if ( 0 >= $width || 0 >= $height ) {
						return false;
					}

					if ( $height > $width ) {
						return in_array( 'portrait', $orientations, true );
					}

					if ( $width > $height ) {
						return in_array( 'landscape', $orientations, true );
					}

					return in_array( 'square', $orientations, true );
				}
			)
		);
	}

	/**
	 * Sanitizes the orientation attribute and its aliases.
	 *
	 * @param string $value Raw orientation value.
	 * @return string Sanitized orientation list.
	 */
	private static function sanitize_orientation( string $value ): string {
		$value        = sanitize_text_field( $value );
		$orientations = array();

		foreach ( explode( ',', $value ) as $orientation ) {
			$orientation = trim( $orientation );
			$orientation = function_exists( 'mb_strtolower' ) ? mb_strtolower( $orientation, 'UTF-8' ) : strtolower( $orientation );

			if ( 'all' === $orientation ) {
				return 'all';
			}

			$normalized = array(
				'portrait'  => 'portrait',
				'paysage'   => 'landscape',
				'landscape' => 'landscape',
				'carré'     => 'square',
				'carre'     => 'square',
				'square'    => 'square',
			)[ $orientation ] ?? '';

			if ( '' !== $normalized ) {
				$orientations[ $normalized ] = $normalized;
			}
		}

		return empty( $orientations ) ? 'all' : implode( ',', array_values( $orientations ) );
	}

	/**
	 * Sanitizes shortcode attributes.
	 *
	 * @param array<string, mixed> $atts Raw attributes.
	 * @return array<string, mixed> Sanitized attributes.
	 */
	private static function sanitize_atts( array $atts ): array {
		$atts['album']       = isset( $atts['album'] ) ? sanitize_text_field( (string) $atts['album'] ) : '';
		$atts['preset']      = isset( $atts['preset'] ) ? sanitize_key( (string) $atts['preset'] ) : '';
		$atts['type']        = self::sanitize_choice( (string) ( $atts['type'] ?? 'gallery' ), array( 'gallery', 'slider' ), 'gallery' );
		$atts['sort']        = self::sanitize_choice( (string) ( $atts['sort'] ?? 'manual' ), array( 'manual', 'date', 'name', 'id', 'random' ), 'manual' );
		$atts['order']       = self::sanitize_choice( (string) ( $atts['order'] ?? 'desc' ), array( 'asc', 'desc' ), 'desc' );
		$atts['fit']         = self::sanitize_choice( (string) ( $atts['fit'] ?? 'contain' ), array( 'cover', 'contain', 'auto', 'raw' ), 'contain' );
		$atts['navigation']  = self::sanitize_choice( (string) ( $atts['navigation'] ?? 'thumbnails' ), array( 'thumbnails', 'dots', 'none' ), 'thumbnails' );
		$atts['caption']     = self::sanitize_choice( (string) ( $atts['caption'] ?? 'default' ), array( 'default', 'none', 'title', 'description', 'title-description' ), 'default' );
		$atts['style']       = self::sanitize_choice( (string) ( $atts['style'] ?? 'default' ), array( 'default', 'theme', 'minimal', 'none' ), 'default' );
		$atts['orientation'] = self::sanitize_orientation( (string) ( $atts['orientation'] ?? 'all' ) );
		$atts['tag']         = isset( $atts['tag'] ) ? sanitize_text_field( (string) $atts['tag'] ) : '';
		$atts['tags']        = isset( $atts['tags'] ) ? sanitize_text_field( (string) $atts['tags'] ) : '';
		$atts['tag_mode']    = self::sanitize_choice( (string) ( $atts['tag_mode'] ?? 'any' ), array( 'any', 'all' ), 'any' );

		$width         = (string) ( $atts['width'] ?? '100%' );
		$atts['width'] = 1 === preg_match( '/^\d{1,3}%$/', $width )
			? min( 100, max( 20, absint( $width ) ) ) . '%'
			: '100%';
		$atts['align'] = self::sanitize_choice( (string) ( $atts['align'] ?? 'center' ), array( 'left', 'right', 'center' ), 'center' );

		$ratio          = (string) ( $atts['ratio'] ?? '16/9' );
		$height         = (string) ( $atts['height'] ?? '' );
		$atts['ratio']  = 1 === preg_match( '/^\d+\/\d+$/', $ratio ) ? $ratio : '16/9';
		$atts['height'] = 1 === preg_match( '/^\d+(px|rem|em|vh|vw|%)$/', $height ) ? $height : '';

		$atts['autoplay']   = self::sanitize_bool_string( $atts['autoplay'] ?? 'true' );
		$atts['rounded']    = self::sanitize_bool_string( $atts['rounded'] ?? 'false' );
		$atts['lightbox']   = self::sanitize_bool_string( $atts['lightbox'] ?? 'true' );
		$atts['thumbnails'] = self::sanitize_bool_string( $atts['thumbnails'] ?? 'true' );
		$atts['recursive']  = self::sanitize_bool_string( $atts['recursive'] ?? 'false' );
		$atts['interval']   = (string) max( 1000, absint( $atts['interval'] ?? 5000 ) );
		$atts['speed']      = (string) max( 0, absint( $atts['speed'] ?? 500 ) );
		$atts['limit']      = (string) absint( $atts['limit'] ?? 0 );
		$atts['max']        = (string) absint( $atts['max'] ?? 0 );
		$atts['latest']     = (string) absint( $atts['latest'] ?? 0 );
		$atts['random']     = (string) absint( $atts['random'] ?? 0 );
		$atts['depth']      = (string) min( 10, absint( $atts['depth'] ?? 10 ) );

		if ( isset( $atts['url'] ) && '' !== (string) $atts['url'] ) {
			$url         = esc_url_raw( (string) $atts['url'] );
			$scheme      = '' !== $url ? wp_parse_url( $url, PHP_URL_SCHEME ) : '';
			$atts['url'] = in_array( $scheme, array( 'http', 'https' ), true ) ? untrailingslashit( $url ) : '';
		} else {
			$atts['url'] = '';
		}

		return $atts;
	}

	/**
	 * Sanitizes a value against an allow-list.
	 *
	 * @param string             $value          Raw value.
	 * @param array<int, string> $allowed        Allowed values.
	 * @param string             $fallback_value Fallback value.
	 * @return string Sanitized value.
	 */
	private static function sanitize_choice( string $value, array $allowed, string $fallback_value ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback_value;
	}

	/**
	 * Converts a mixed boolean value to a shortcode boolean string.
	 *
	 * @param mixed $value Raw value.
	 * @return string Boolean string.
	 */
	private static function sanitize_bool_string( $value ): string {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false';
	}

	/**
	 * Applies a named preset to unset shortcode attributes.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return array<string, mixed> Updated attributes.
	 */
	private static function apply_preset( array $atts ): array {
		$preset  = sanitize_key( (string) ( $atts['preset'] ?? '' ) );
		$presets = array(
			'galerie'    => array(
				'type'     => 'gallery',
				'fit'      => 'contain',
				'lightbox' => 'true',
			),
			'slider'     => array(
				'type'       => 'slider',
				'fit'        => 'contain',
				'navigation' => 'thumbnails',
				'autoplay'   => 'true',
			),
			'actualites' => array(
				'type'       => 'slider',
				'fit'        => 'contain',
				'navigation' => 'thumbnails',
				'sort'       => 'date',
				'order'      => 'desc',
				'limit'      => '12',
				'autoplay'   => 'true',
			),
		);

		/**
		 * Filters available shortcode presets.
		 *
		 * @param array<string, array<string, string>> $presets Presets.
		 */
		$presets = apply_filters( 'wp_piwigo_display_presets', $presets );

		if ( '' === $preset || ! isset( $presets[ $preset ] ) || ! is_array( $presets[ $preset ] ) ) {
			return $atts;
		}

		foreach ( $presets[ $preset ] as $key => $value ) {
			if ( ! isset( $atts[ $key ] ) || '' === $atts[ $key ] ) {
				$atts[ $key ] = (string) $value;
			}
		}

		return $atts;
	}

	/**
	 * Renders an escaped public error.
	 *
	 * @param string $message Error message.
	 * @return string Error HTML.
	 */
	private static function render_error( string $message ): string {
		return '<div class="wp-piwigo-display-error">' . esc_html( $message ) . '</div>';
	}

	/**
	 * Renders administrator-only debug information.
	 *
	 * @param int                  $album_id   Album ID.
	 * @param string               $piwigo_url Piwigo URL.
	 * @param array<string, mixed> $atts       Sanitized attributes.
	 * @param int                  $count      Image count.
	 * @return string Debug HTML.
	 */
	private static function render_debug( int $album_id, string $piwigo_url, array $atts, int $count ): string {
		ob_start();
		?>
		<details class="wp-piwigo-display-debug">
			<summary><?php esc_html_e( 'Debug WP Piwigo Display', 'wp-piwigo-display' ); ?></summary>
			<ul>
				<?php /* translators: %d: Piwigo album ID. */ ?>
				<li><?php echo esc_html( sprintf( __( 'Album : %d', 'wp-piwigo-display' ), $album_id ) ); ?></li>
				<?php /* translators: %s: Piwigo gallery URL. */ ?>
				<li><?php echo esc_html( sprintf( __( 'URL Piwigo : %s', 'wp-piwigo-display' ), $piwigo_url ) ); ?></li>
				<?php /* translators: %d: Number of fetched images. */ ?>
				<li><?php echo esc_html( sprintf( __( 'Images récupérées : %d', 'wp-piwigo-display' ), $count ) ); ?></li>
				<?php /* translators: %s: Rendered display type. */ ?>
				<li><?php echo esc_html( sprintf( __( 'Type : %s', 'wp-piwigo-display' ), (string) ( $atts['type'] ?? '' ) ) ); ?></li>
				<?php /* translators: 1: Sort criterion. 2: Sort order. */ ?>
				<li><?php echo esc_html( sprintf( __( 'Tri : %1$s / %2$s', 'wp-piwigo-display' ), (string) ( $atts['sort'] ?? '' ), (string) ( $atts['order'] ?? '' ) ) ); ?></li>
			</ul>
		</details>
		<?php
		return (string) ob_get_clean();
	}
}
