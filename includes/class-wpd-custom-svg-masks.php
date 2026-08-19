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
 * Stores, imports, deletes, exposes and applies sanitized SVG masks.
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
		add_action( 'admin_menu', array( self::class, 'register_page' ), 30 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ), 30 );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'localize_block_editor_masks' ), 30 );
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

	/** Registers the custom mask administration page. */
	public static function register_page(): void {
		add_submenu_page(
			'wp-piwigo-display',
			__( 'Masques SVG', 'wp-piwigo-display' ),
			__( 'Masques SVG', 'wp-piwigo-display' ),
			'manage_options',
			'wp-piwigo-display-masks',
			array( self::class, 'render_page' )
		);
	}

	/** Renders the sanitized custom mask library and upload form. */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$library = self::all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Masques SVG personnalisés', 'wp-piwigo-display' ); ?></h1>
			<p><?php esc_html_e( 'Les SVG sont filtrés avant stockage. Aucun script, style actif ni ressource externe n’est conservé.', 'wp-piwigo-display' ); ?></p>

			<div class="card" style="max-width:1050px">
				<h2><?php esc_html_e( 'Importer un masque', 'wp-piwigo-display' ); ?></h2>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="wpd_upload_svg_mask">
					<?php wp_nonce_field( 'wpd_upload_svg_mask' ); ?>
					<p><label><?php esc_html_e( 'Nom', 'wp-piwigo-display' ); ?> <input type="text" name="wpd_svg_mask_name" maxlength="80"></label></p>
					<p><label><?php esc_html_e( 'Fichier SVG', 'wp-piwigo-display' ); ?> <input type="file" name="wpd_svg_mask" accept="image/svg+xml,.svg" required></label></p>
					<p class="description"><?php esc_html_e( '256 Ko maximum. Les primitives géométriques SVG sûres sont conservées ; le contenu actif est refusé.', 'wp-piwigo-display' ); ?></p>
					<?php submit_button( __( 'Importer et sécuriser', 'wp-piwigo-display' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<h2><?php esc_html_e( 'Bibliothèque', 'wp-piwigo-display' ); ?></h2>
			<?php if ( array() === $library ) : ?>
				<p><?php esc_html_e( 'Aucun masque personnalisé pour le moment.', 'wp-piwigo-display' ); ?></p>
			<?php else : ?>
				<div class="wpd-shape-picker-grid" style="max-width:1050px">
					<?php foreach ( $library as $id => $mask ) : ?>
						<?php
						$payload = self::ui_mask( (string) $id, $mask );
						if ( null === $payload ) {
							continue;
						}
						?>
						<div class="wpd-shape-picker-button" style="cursor:default;min-height:130px">
							<span class="wpd-shape-picker-preview" style="background:#1d2327;-webkit-mask-image:url('<?php echo esc_attr( $payload['dataUri'] ); ?>');mask-image:url('<?php echo esc_attr( $payload['dataUri'] ); ?>');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-position:center;mask-position:center;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat" aria-hidden="true"></span>
							<strong><?php echo esc_html( $payload['name'] ); ?></strong>
							<code>custom-<?php echo esc_html( $payload['id'] ); ?></code>
							<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
								<input type="hidden" name="action" value="wpd_delete_svg_mask">
								<input type="hidden" name="wpd_svg_mask_id" value="<?php echo esc_attr( $payload['id'] ); ?>">
								<?php wp_nonce_field( 'wpd_delete_svg_mask' ); ?>
								<button type="submit" class="button-link-delete"><?php esc_html_e( 'Supprimer', 'wp-piwigo-display' ); ?></button>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Enqueues mask selection helpers in Classic Editor and the admin composer.
	 *
	 * @param string $hook Current administration screen hook.
	 */
	public static function enqueue_admin_assets( string $hook ): void {
		$is_classic = in_array( $hook, array( 'post.php', 'post-new.php' ), true );

		// The page query argument only selects an administration screen and does not mutate data.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page        = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$is_composer = 'wp-piwigo-display-compose' === $page;
		$is_library  = 'wp-piwigo-display-masks' === $page;

		if ( $is_library ) {
			wp_enqueue_style( 'wpd-shape-picker', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-shape-picker.css', array(), WPD_VERSION );
		}

		if ( ! $is_classic && ! $is_composer ) {
			return;
		}

		$dependencies = $is_classic ? array( 'jquery', 'wpd-classic-editor' ) : array( 'wpd-admin-composer-parity' );
		wp_enqueue_script(
			'wpd-custom-svg-mask-ui',
			WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-custom-masks-ui.js',
			$dependencies,
			WPD_VERSION,
			true
		);
		wp_localize_script( 'wpd-custom-svg-mask-ui', 'WPDCustomMasks', self::ui_masks() );
	}

	/** Adds sanitized mask metadata to the Gutenberg shape picker. */
	public static function localize_block_editor_masks(): void {
		wp_localize_script( 'wpd-shapes-editor', 'WPDCustomMasks', self::ui_masks() );
	}

	/**
	 * Handles an authenticated SVG mask upload.
	 *
	 * @return void
	 */
	public static function handle_upload(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’importer des masques SVG.', 'wp-piwigo-display' ), 403 );
		}

		check_admin_referer( 'wpd_upload_svg_mask' );

		$library = self::all();
		if ( count( $library ) >= self::MAX_MASKS ) {
			self::redirect_with_error( 'limit' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload metadata and temporary file are validated below.
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

		// This is a local PHP upload temporary file validated with is_uploaded_file() above.
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

	/**
	 * Handles deletion of one stored custom mask.
	 *
	 * @return void
	 */
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

		$id = self::resolve_mask_id( $attr );
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
	 * Resolves a custom mask id from explicit or shape-based shortcode syntax.
	 *
	 * @param array<string,mixed> $attr Shortcode attributes.
	 * @return string
	 */
	private static function resolve_mask_id( array $attr ): string {
		$explicit = sanitize_key( (string) ( $attr['custom_mask'] ?? '' ) );
		if ( '' !== $explicit ) {
			return $explicit;
		}

		$shape = sanitize_key( (string) ( $attr['shape'] ?? '' ) );
		if ( preg_match( '/^custom-([a-f0-9]{12})$/', $shape, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Returns masks prepared for safe editor previews.
	 *
	 * @return array<int,array{id:string,name:string,value:string,dataUri:string}>
	 */
	private static function ui_masks(): array {
		$result = array();
		foreach ( self::all() as $id => $mask ) {
			$payload = self::ui_mask( (string) $id, $mask );
			if ( null !== $payload ) {
				$result[] = $payload;
			}
		}
		return $result;
	}

	/**
	 * Prepares one stored mask for a local-only editor preview.
	 *
	 * @param string $id   Stored mask identifier.
	 * @param mixed  $mask Stored mask payload.
	 * @return array{id:string,name:string,value:string,dataUri:string}|null
	 */
	private static function ui_mask( string $id, $mask ): ?array {
		if ( ! is_array( $mask ) || ! isset( $mask['name'], $mask['svg'] ) ) {
			return null;
		}

		$id = sanitize_key( $id );
		if ( ! preg_match( '/^[a-f0-9]{12}$/', $id ) ) {
			return null;
		}

		$sanitized = WPD_SVG_Mask_Sanitizer::sanitize( (string) $mask['svg'] );
		if ( is_wp_error( $sanitized ) ) {
			return null;
		}

		return array(
			'id'      => $id,
			'name'    => sanitize_text_field( (string) $mask['name'] ),
			'value'   => 'custom-' . $id,
			'dataUri' => 'data:image/svg+xml,' . rawurlencode( $sanitized ),
		);
	}

	/**
	 * Detects uploaded file MIME type when Fileinfo is available.
	 *
	 * @param string $path Local temporary upload path.
	 * @return string
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
	 * Redirects back to the custom mask administration page.
	 *
	 * @param array<string,string> $args Query arguments.
	 * @return void
	 */
	private static function redirect( array $args = array() ): void {
		$url = add_query_arg( $args, admin_url( 'admin.php?page=wp-piwigo-display-masks' ) );
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
