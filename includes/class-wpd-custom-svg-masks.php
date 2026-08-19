<?php
/**
 * Custom sanitized SVG mask library.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores, imports, deletes and applies sanitized SVG masks.
 */
final class WPD_Custom_SVG_Masks {
	/** Option name used to store sanitized masks. */
	private const OPTION_NAME = 'wpd_custom_svg_masks';

	/** Maximum number of stored masks. */
	private const MAX_MASKS = 30;

	/** Registers custom mask hooks. */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_shortcode_defaults', array( self::class, 'add_defaults' ) );
		add_filter( 'do_shortcode_tag', array( self::class, 'apply_mask' ), 12, 4 );
		add_action( 'admin_post_wpd_upload_svg_mask', array( self::class, 'handle_upload' ) );
		add_action( 'admin_post_wpd_delete_svg_mask', array( self::class, 'handle_delete' ) );
	}

	/**
	 * Adds the custom mask shortcode attribute.
	 *
	 * @param array<string,mixed> $defaults Existing defaults.
	 * @return array<string,mixed>
	 */
	public static function add_defaults( array $defaults ): array {
		$defaults['custom_mask'] = $defaults['custom_mask'] ?? '';
		return $defaults;
	}

	/**
	 * Returns the sanitized mask library.
	 *
	 * @return array<string,array{name:string,svg:string}>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION_NAME, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Returns one sanitized mask.
	 *
	 * @param string $id Mask identifier.
	 * @return array{name:string,svg:string}|null
	 */
	public static function get( string $id ): ?array {
		$id      = sanitize_key( $id );
		$library = self::all();
		$mask    = $library[ $id ] ?? null;

		if ( ! is_array( $mask ) || ! isset( $mask['name'], $mask['svg'] ) ) {
			return null;
		}

		return array(
			'name' => sanitize_text_field( (string) $mask['name'] ),
			'svg'  => (string) $mask['svg'],
		);
	}

	/** Handles an authenticated SVG mask upload. */
	public static function handle_upload(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’importer des masques SVG.', 'wp-piwigo-display' ), 403 );
		}

		check_admin_referer( 'wpd_upload_svg_mask' );

		$library = self::all();
		if ( count( $library ) >= self::MAX_MASKS ) {
			self::redirect_with_error( 'limit' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload metadata and temporary file are validated below before any content is accepted.
		$file = isset( $_FILES['wpd_svg_mask'] ) && is_array( $_FILES['wpd_svg_mask'] ) ? $_FILES['wpd_svg_mask'] : null;
		if ( ! $file || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			self::redirect_with_error( 'upload' );
		}

		$name     = sanitize_text_field( wp_unslash( (string) ( $_POST['wpd_svg_mask_name'] ?? '' ) ) );
		$filename = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
		$tmp_name = (string) ( $file['tmp_name'] ?? '' );

		if ( 'svg' !== strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) || ! is_uploaded_file( $tmp_name ) ) {
			self::redirect_with_error( 'type' );
		}

		if ( isset( $file['size'] ) && (int) $file['size'] > 262144 ) {
			self::redirect_with_error( 'size' );
		}

		$mime = self::detect_mime( $tmp_name );
		if ( '' !== $mime && ! in_array( $mime, array( 'image/svg+xml', 'text/plain', 'text/xml', 'application/xml' ), true ) ) {
			self::redirect_with_error( 'mime' );
		}

		// This is a validated local PHP upload temporary file, never a remote URL.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $tmp_name );
		if ( false === $raw ) {
			self::redirect_with_error( 'read' );
		}

		$sanitized = WPD_SVG_Mask_Sanitizer::sanitize( $raw );
		if ( is_wp_error( $sanitized ) ) {
			self::redirect_with_error( sanitize_key( $sanitized->get_error_code() ) );
		}

		if ( '' === $name ) {
			$name = pathinfo( $filename, PATHINFO_FILENAME );
		}
		$name = mb_substr( sanitize_text_field( $name ), 0, 80 );

		$id             = substr( hash( 'sha256', $sanitized ), 0, 12 );
		$library[ $id ] = array(
			'name' => $name,
			'svg'  => $sanitized,
		);
		update_option( self::OPTION_NAME, $library, false );

		self::redirect( array( 'wpd_mask_added' => $id ) );
	}

	/** Handles deletion of one stored custom mask. */
	public static function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation de supprimer des masques SVG.', 'wp-piwigo-display' ), 403 );
		}

		check_admin_referer( 'wpd_delete_svg_mask' );
		$id      = sanitize_key( wp_unslash( (string) ( $_POST['wpd_svg_mask_id'] ?? '' ) ) );
		$library = self::all();

		if ( isset( $library[ $id ] ) ) {
			unset( $library[ $id ] );
			update_option( self::OPTION_NAME, $library, false );
		}

		self::redirect( array( 'wpd_mask_deleted' => $id ) );
	}

	/**
	 * Applies a stored mask to shortcode output.
	 *
	 * @param string              $output          Shortcode output.
	 * @param string              $tag             Shortcode tag.
	 * @param array<string,mixed> $attr            Shortcode attributes.
	 * @param array<int,mixed>    $shortcode_match Shortcode match data, unused.
	 * @return string
	 */
	public static function apply_mask( string $output, string $tag, array $attr, array $shortcode_match ): string {
		unset( $shortcode_match );

		if ( 'piwigo' !== $tag || '' === $output ) {
			return $output;
		}

		$id = sanitize_key( (string) ( $attr['custom_mask'] ?? '' ) );
		if ( '' === $id ) {
			return $output;
		}

		$mask = self::get( $id );
		if ( null === $mask ) {
			return $output;
		}

		$sanitized = WPD_SVG_Mask_Sanitizer::sanitize( $mask['svg'] );
		if ( is_wp_error( $sanitized ) ) {
			return $output;
		}

		wp_enqueue_style( 'wpd-shapes' );
		$data_uri = 'data:image/svg+xml,' . rawurlencode( $sanitized );

		return (string) preg_replace_callback(
			'/<div\b([^>]*\bclass="[^"]*\bwp-piwigo-display\b[^"]*"[^>]*)>/',
			static function ( array $matches ) use ( $data_uri ): string {
				$attributes = $matches[1];
				$attributes = preg_replace( '/\bclass="([^"]*)"/', 'class="$1 wpd-custom-svg-mask"', $attributes, 1 );
				$style      = '--wpd-custom-svg-mask:url(&quot;' . esc_attr( $data_uri ) . '&quot;);';

				if ( preg_match( '/\bstyle="([^"]*)"/', $attributes ) ) {
					$attributes = preg_replace( '/\bstyle="([^"]*)"/', 'style="$1 ' . $style . '"', $attributes, 1 );
				} else {
					$attributes .= ' style="' . $style . '"';
				}

				return '<div' . $attributes . '>';
			},
			$output,
			1
		);
	}

	/**
	 * Detects uploaded file MIME type when Fileinfo is available.
	 *
	 * @param string $path Local uploaded temporary path.
	 * @return string Detected MIME type or an empty string.
	 */
	private static function detect_mime( string $path ): string {
		if ( ! function_exists( 'finfo_open' ) ) {
			return '';
		}

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( false === $finfo ) {
			return '';
		}
		$mime = finfo_file( $finfo, $path );
		finfo_close( $finfo );
		return is_string( $mime ) ? strtolower( trim( $mime ) ) : '';
	}

	/**
	 * Redirects back to the plugin settings page.
	 *
	 * @param array<string,string> $args Query arguments.
	 * @return void
	 */
	private static function redirect( array $args = array() ): void {
		$url = add_query_arg( $args, admin_url( 'options-general.php?page=wp-piwigo-display' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirects with a sanitized import error code.
	 *
	 * @param string $code Error code.
	 * @return void
	 */
	private static function redirect_with_error( string $code ): void {
		self::redirect( array( 'wpd_mask_error' => sanitize_key( $code ) ) );
	}
}
