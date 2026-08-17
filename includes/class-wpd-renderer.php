<?php
/**
 * Public gallery and slider rendering.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Piwigo images for galleries and sliders.
 */
final class WPD_Renderer {
	/**
	 * Renders the requested public display.
	 *
	 * @param array<int, array<string, mixed>> $images Piwigo image data.
	 * @param array<string, mixed>             $atts   Display attributes.
	 * @return string
	 */
	public static function render( array $images, array $atts ): string {
		$images = self::prepare_images( $images, $atts );
		$type   = isset( $atts['type'] ) ? sanitize_key( (string) $atts['type'] ) : 'gallery';

		/**
		 * Filters the complete public rendering output.
		 *
		 * Returning a string bypasses the built-in renderer.
		 *
		 * @param string|null                      $custom_render Custom output or null.
		 * @param array<int, array<string, mixed>> $images        Prepared image data.
		 * @param array<string, mixed>             $atts          Display attributes.
		 * @param string                           $type          Display type.
		 */
		$custom_render = apply_filters( 'wp_piwigo_display_render', null, $images, $atts, $type );
		if ( is_string( $custom_render ) ) {
			return $custom_render;
		}

		if ( 'slider' === $type ) {
			return self::render_slider( $images, $atts );
		}

		return self::render_gallery( $images, $atts );
	}

	/**
	 * Renders a gallery.
	 *
	 * @param array<int, array<string, mixed>> $images Piwigo image data.
	 * @param array<string, mixed>             $atts   Display attributes.
	 * @return string
	 */
	private static function render_gallery( array $images, array $atts ): string {
		wp_enqueue_style( 'wp-piwigo-display' );

		if ( self::is_enabled( $atts['lightbox'] ?? 'true' ) ) {
			wp_enqueue_script( 'wp-piwigo-display' );
		}

		$fit            = self::sanitize_fit( $atts['fit'] ?? 'cover' );
		$height         = self::sanitize_height( (string) ( $atts['height'] ?? '' ), '180px' );
		$rounded_class  = self::is_enabled( $atts['rounded'] ?? 'false' ) ? ' wp-piwigo-display-rounded' : '';
		$raw_class      = 'raw' === $fit ? ' wp-piwigo-display-raw' : '';
		$lightbox_class = self::is_enabled( $atts['lightbox'] ?? 'true' ) ? ' wp-piwigo-display-lightbox-enabled' : '';
		$style_class    = ' wp-piwigo-display-style-' . self::sanitize_style( (string) ( $atts['style'] ?? 'default' ) );
		$caption_mode   = self::resolve_caption_mode( (string) ( $atts['caption'] ?? 'default' ) );

		ob_start();
		?>
		<div class="wp-piwigo-display wp-piwigo-display-gallery<?php echo esc_attr( $rounded_class . $raw_class . $lightbox_class . $style_class ); ?>" style="--wpd-image-fit: <?php echo esc_attr( $fit ); ?>; --wpd-image-height: <?php echo esc_attr( $height ); ?>;">
			<?php foreach ( $images as $image ) : ?>
				<?php
				$image_url = self::get_image_url( $image );
				if ( '' === $image_url ) {
					continue;
				}

				$large_url        = self::get_large_url( $image );
				$title            = self::get_image_title( $image );
				$description      = self::get_image_description( $image );
				$lightbox_caption = self::get_caption_text( $title, $description, $caption_mode );
				$orientation      = self::get_orientation_class( $image );
				$image_fit        = self::get_image_fit( $image, $fit );
				?>
				<figure class="wp-piwigo-display-item <?php echo esc_attr( $orientation ); ?>" style="--wpd-current-image-fit: <?php echo esc_attr( $image_fit ); ?>;">
					<a href="<?php echo esc_url( '' !== $large_url ? $large_url : $image_url ); ?>" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr( $lightbox_caption ); ?>">
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
					</a>
					<?php echo self::render_caption( $title, $description, $caption_mode, 'figcaption' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped markup. ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Renders a slider.
	 *
	 * @param array<int, array<string, mixed>> $images Piwigo image data.
	 * @param array<string, mixed>             $atts   Display attributes.
	 * @return string
	 */
	private static function render_slider( array $images, array $atts ): string {
		wp_enqueue_style( 'wp-piwigo-display' );
		wp_enqueue_style( 'wpd-splide' );
		wp_enqueue_script( 'wp-piwigo-display-slider' );

		if ( self::is_enabled( $atts['lightbox'] ?? 'true' ) ) {
			wp_enqueue_script( 'wp-piwigo-display' );
		}

		$fit = self::sanitize_fit( $atts['fit'] ?? 'contain' );
		if ( in_array( $fit, array( 'raw', 'auto' ), true ) ) {
			$fit = 'contain';
		}

		$height         = self::sanitize_height( (string) ( $atts['height'] ?? '' ), '' );
		$ratio          = self::sanitize_ratio( (string) ( $atts['ratio'] ?? '16/9' ) );
		$autoplay       = self::is_enabled( $atts['autoplay'] ?? 'true' );
		$interval       = max( 1000, absint( $atts['interval'] ?? 5000 ) );
		$speed          = max( 0, absint( $atts['speed'] ?? 500 ) );
		$rounded_class  = self::is_enabled( $atts['rounded'] ?? 'false' ) ? ' wp-piwigo-display-rounded' : '';
		$lightbox_class = self::is_enabled( $atts['lightbox'] ?? 'true' ) ? ' wp-piwigo-display-lightbox-enabled' : '';
		$style_class    = ' wp-piwigo-display-style-' . self::sanitize_style( (string) ( $atts['style'] ?? 'default' ) );
		$navigation     = self::sanitize_navigation( (string) ( $atts['navigation'] ?? 'thumbnails' ) );
		$thumbnails     = 'thumbnails' === $navigation;
		$caption_mode   = self::resolve_caption_mode( (string) ( $atts['caption'] ?? 'default' ) );
		$slider_images  = array_values(
			array_filter(
				$images,
				static function ( array $image ): bool {
					return '' !== self::get_large_url( $image );
				}
			)
		);
		$slider_id      = 'wpd-slider-' . wp_generate_uuid4();

		ob_start();
		?>
		<div id="<?php echo esc_attr( $slider_id ); ?>"
			class="wp-piwigo-display wp-piwigo-display-slider splide<?php echo esc_attr( $rounded_class . $lightbox_class . $style_class ); ?>"
			style="--wpd-slider-height: <?php echo esc_attr( $height ); ?>; --wpd-slider-ratio: <?php echo esc_attr( $ratio ); ?>; --wpd-image-fit: <?php echo esc_attr( $fit ); ?>;"
			data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
			data-interval="<?php echo esc_attr( (string) $interval ); ?>"
			data-speed="<?php echo esc_attr( (string) $speed ); ?>"
			data-navigation="<?php echo esc_attr( $navigation ); ?>"
			aria-label="<?php esc_attr_e( 'Diaporama Piwigo', 'wp-piwigo-display' ); ?>">
			<div class="splide__track">
				<ul class="splide__list">
					<?php foreach ( $slider_images as $image ) : ?>
						<?php
						$image_url        = self::get_large_url( $image );
						$title            = self::get_image_title( $image );
						$description      = self::get_image_description( $image );
						$lightbox_caption = self::get_caption_text( $title, $description, $caption_mode );
						?>
						<li class="splide__slide">
							<a href="<?php echo esc_url( $image_url ); ?>" class="wp-piwigo-display-slide-link" rel="noopener" data-wpd-lightbox="true" data-wpd-title="<?php echo esc_attr( $lightbox_caption ); ?>">
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
							</a>
							<?php echo self::render_caption( $title, $description, $caption_mode, 'div' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method returns escaped markup. ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<?php if ( $thumbnails ) : ?>
				<div class="wp-piwigo-display-slider-thumbnails" aria-label="<?php esc_attr_e( 'Miniatures du diaporama', 'wp-piwigo-display' ); ?>">
					<?php foreach ( $slider_images as $index => $image ) : ?>
						<?php
						$thumb_url = self::get_image_url( $image );
						$title     = self::get_image_title( $image );
						if ( '' === $thumb_url ) {
							$thumb_url = self::get_large_url( $image );
						}
						?>
						<?php // translators: %d: Image position in the slider. ?>
						<button type="button" class="wp-piwigo-display-slider-thumbnail<?php echo 0 === $index ? ' is-active' : ''; ?>" data-slide-index="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Afficher l’image %d', 'wp-piwigo-display' ), $index + 1 ) ); ?>">
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async" />
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Applies sorting, ordering and limits to images.
	 *
	 * @param array<int, array<string, mixed>> $images Piwigo image data.
	 * @param array<string, mixed>             $atts   Display attributes.
	 * @return array<int, array<string, mixed>>
	 */
	private static function prepare_images( array $images, array $atts ): array {
		$sort   = self::sanitize_sort( (string) ( $atts['sort'] ?? 'manual' ) );
		$order  = self::sanitize_order( (string) ( $atts['order'] ?? 'desc' ) );
		$limit  = absint( $atts['limit'] ?? 0 );
		$latest = absint( $atts['latest'] ?? 0 );
		$random = absint( $atts['random'] ?? 0 );
		$max    = absint( $atts['max'] ?? 0 );

		if ( 0 < $random ) {
			$sort  = 'random';
			$limit = $random;
		}

		if ( 0 < $latest ) {
			$sort  = 'date';
			$order = 'desc';
			$limit = $latest;
		}

		if ( 0 >= $limit && 0 < $max ) {
			$limit = $max;
		}

		switch ( $sort ) {
			case 'random':
				shuffle( $images );
				break;

			case 'name':
				usort(
					$images,
					static function ( array $first, array $second ): int {
						return strnatcasecmp(
							(string) ( $first['name'] ?? $first['file'] ?? '' ),
							(string) ( $second['name'] ?? $second['file'] ?? '' )
						);
					}
				);
				break;

			case 'date':
				usort(
					$images,
					static function ( array $first, array $second ): int {
						return strcmp(
							(string) ( $first['date_available'] ?? $first['date_creation'] ?? '' ),
							(string) ( $second['date_available'] ?? $second['date_creation'] ?? '' )
						);
					}
				);
				break;

			case 'id':
				usort(
					$images,
					static function ( array $first, array $second ): int {
						return absint( $first['id'] ?? 0 ) <=> absint( $second['id'] ?? 0 );
					}
				);
				break;

			case 'manual':
			default:
				break;
		}

		if ( 'random' !== $sort && 'desc' === $order ) {
			$images = array_reverse( $images );
		}

		if ( 0 < $limit ) {
			$images = array_slice( $images, 0, $limit );
		}

		return $images;
	}

	/**
	 * Sanitizes a style identifier.
	 *
	 * @param string $style Requested style.
	 * @return string
	 */
	private static function sanitize_style( string $style ): string {
		return in_array( $style, array( 'default', 'theme', 'minimal', 'none' ), true ) ? $style : 'default';
	}

	/**
	 * Sanitizes a sort identifier.
	 *
	 * @param string $sort Requested sort.
	 * @return string
	 */
	private static function sanitize_sort( string $sort ): string {
		return in_array( $sort, array( 'manual', 'date', 'name', 'random', 'id' ), true ) ? $sort : 'manual';
	}

	/**
	 * Sanitizes a sort order.
	 *
	 * @param string $order Requested order.
	 * @return string
	 */
	private static function sanitize_order( string $order ): string {
		return in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'desc';
	}

	/**
	 * Resolves the preferred gallery image URL.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return string
	 */
	private static function get_image_url( array $image ): string {
		foreach ( array( 'medium', 'small', 'thumb' ) as $size ) {
			if ( isset( $image['derivatives'][ $size ]['url'] ) ) {
				return (string) $image['derivatives'][ $size ]['url'];
			}
		}

		return isset( $image['element_url'] ) ? (string) $image['element_url'] : '';
	}

	/**
	 * Resolves the preferred large image URL.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return string
	 */
	private static function get_large_url( array $image ): string {
		foreach ( array( 'large', 'medium' ) as $size ) {
			if ( isset( $image['derivatives'][ $size ]['url'] ) ) {
				return (string) $image['derivatives'][ $size ]['url'];
			}
		}

		return isset( $image['element_url'] ) ? (string) $image['element_url'] : '';
	}

	/**
	 * Resolves a safe image title.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return string
	 */
	private static function get_image_title( array $image ): string {
		foreach ( array( 'name', 'file' ) as $key ) {
			if ( isset( $image[ $key ] ) && '' !== (string) $image[ $key ] ) {
				return wp_strip_all_tags( (string) $image[ $key ] );
			}
		}

		return '';
	}

	/**
	 * Resolves a safe image description.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return string
	 */
	private static function get_image_description( array $image ): string {
		foreach ( array( 'comment', 'description' ) as $key ) {
			if ( ! isset( $image[ $key ] ) || '' === (string) $image[ $key ] ) {
				continue;
			}

			$description = html_entity_decode(
				wp_strip_all_tags( (string) $image[ $key ] ),
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			);

			return trim( $description );
		}

		return '';
	}

	/**
	 * Resolves the effective caption mode.
	 *
	 * @param string $caption Requested caption mode.
	 * @return string
	 */
	private static function resolve_caption_mode( string $caption ): string {
		if ( 'default' === $caption ) {
			return WPD_Settings::get_default_caption();
		}

		return in_array( $caption, array( 'none', 'title', 'description', 'title-description' ), true )
			? $caption
			: WPD_Settings::get_default_caption();
	}

	/**
	 * Builds plain text for lightbox captions.
	 *
	 * @param string $title       Image title.
	 * @param string $description Image description.
	 * @param string $mode        Caption mode.
	 * @return string
	 */
	private static function get_caption_text( string $title, string $description, string $mode ): string {
		if ( 'title' === $mode ) {
			return $title;
		}

		if ( 'description' === $mode ) {
			return $description;
		}

		if ( 'title-description' === $mode ) {
			return trim(
				implode(
					' — ',
					array_filter(
						array( $title, $description ),
						static fn( string $value ): bool => '' !== $value
					)
				)
			);
		}

		return '';
	}

	/**
	 * Renders escaped caption markup.
	 *
	 * @param string $title       Image title.
	 * @param string $description Image description.
	 * @param string $mode        Caption mode.
	 * @param string $element     Requested wrapper element.
	 * @return string
	 */
	private static function render_caption( string $title, string $description, string $mode, string $element ): string {
		$show_title       = in_array( $mode, array( 'title', 'title-description' ), true ) && '' !== $title;
		$show_description = in_array( $mode, array( 'description', 'title-description' ), true ) && '' !== $description;

		if ( ! $show_title && ! $show_description ) {
			return '';
		}

		$tag   = 'figcaption' === $element ? 'figcaption' : 'div';
		$class = 'figcaption' === $tag
			? 'wp-piwigo-display-caption'
			: 'wp-piwigo-display-slide-caption wp-piwigo-display-caption';
		$html  = '<' . $tag . ' class="' . esc_attr( $class ) . '">';

		if ( $show_title ) {
			$html .= '<span class="wp-piwigo-display-caption-title">' . esc_html( $title ) . '</span>';
		}

		if ( $show_description ) {
			$html .= '<span class="wp-piwigo-display-caption-description">' . esc_html( $description ) . '</span>';
		}

		return $html . '</' . $tag . '>';
	}

	/**
	 * Resolves the image fit for one gallery item.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @param string               $fit   Requested fit.
	 * @return string
	 */
	private static function get_image_fit( array $image, string $fit ): string {
		if ( 'raw' === $fit ) {
			return 'contain';
		}

		if ( 'auto' !== $fit ) {
			return $fit;
		}

		return self::is_portrait( $image ) ? 'contain' : 'cover';
	}

	/**
	 * Resolves the orientation CSS class.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return string
	 */
	private static function get_orientation_class( array $image ): string {
		if ( self::is_portrait( $image ) ) {
			return 'wp-piwigo-display-portrait';
		}

		if ( self::is_landscape( $image ) ) {
			return 'wp-piwigo-display-landscape';
		}

		if ( self::is_square( $image ) ) {
			return 'wp-piwigo-display-square';
		}

		return 'wp-piwigo-display-orientation-unknown';
	}

	/**
	 * Determines whether an image is portrait.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return bool
	 */
	private static function is_portrait( array $image ): bool {
		$width  = absint( $image['width'] ?? 0 );
		$height = absint( $image['height'] ?? 0 );

		return 0 < $width && 0 < $height && $height > $width;
	}

	/**
	 * Determines whether an image is landscape.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return bool
	 */
	private static function is_landscape( array $image ): bool {
		$width  = absint( $image['width'] ?? 0 );
		$height = absint( $image['height'] ?? 0 );

		return 0 < $width && 0 < $height && $width > $height;
	}

	/**
	 * Determines whether an image is square.
	 *
	 * @param array<string, mixed> $image Piwigo image data.
	 * @return bool
	 */
	private static function is_square( array $image ): bool {
		$width  = absint( $image['width'] ?? 0 );
		$height = absint( $image['height'] ?? 0 );

		return 0 < $width && 0 < $height && $width === $height;
	}

	/**
	 * Sanitizes slider navigation.
	 *
	 * @param string $navigation Requested navigation.
	 * @return string
	 */
	private static function sanitize_navigation( string $navigation ): string {
		return in_array( $navigation, array( 'thumbnails', 'dots', 'none' ), true ) ? $navigation : 'thumbnails';
	}

	/**
	 * Sanitizes image fit.
	 *
	 * @param string $fit Requested fit.
	 * @return string
	 */
	private static function sanitize_fit( string $fit ): string {
		return in_array( $fit, array( 'cover', 'contain', 'auto', 'raw' ), true ) ? $fit : 'raw';
	}

	/**
	 * Converts a mixed setting value to a boolean.
	 *
	 * @param mixed $value Value to test.
	 * @return bool
	 */
	private static function is_enabled( $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitizes a CSS length.
	 *
	 * @param string $height   Requested height.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private static function sanitize_height( string $height, string $fallback ): string {
		$height = trim( $height );

		return 1 === preg_match( '/^\d+(px|rem|em|vh|vw|%)$/', $height ) ? $height : $fallback;
	}

	/**
	 * Sanitizes an aspect ratio.
	 *
	 * @param string $ratio Requested ratio.
	 * @return string
	 */
	private static function sanitize_ratio( string $ratio ): string {
		$ratio = trim( $ratio );

		return 1 === preg_match( '/^\d+\/\d+$/', $ratio ) ? $ratio : '16/9';
	}
}
