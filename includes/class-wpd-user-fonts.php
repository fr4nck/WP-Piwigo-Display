<?php
/**
 * User-provided local font library for photo-filled text.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports, stores, deletes and exposes local WOFF/WOFF2 fonts.
 */
final class WPD_User_Fonts {
	/** Option storing imported font metadata. */
	private const OPTION_NAME = 'wpd_user_fonts';

	/** Dedicated uploads subdirectory. */
	private const UPLOAD_SUBDIR = '/piwigo-display-fonts';

	/** Maximum number of stored user fonts. */
	private const MAX_FONTS = 20;

	/** Default maximum upload size in bytes. */
	private const DEFAULT_MAX_BYTES = 2097152;

	/**
	 * Already-enqueued font identifiers.
	 *
	 * @var array<string,bool>
	 */
	private static array $enqueued = array();

	/** Registers user-font hooks. */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'register_page' ), 31 );
		add_action( 'admin_post_wpd_upload_user_font', array( self::class, 'handle_upload' ) );
		add_action( 'admin_post_wpd_delete_user_font', array( self::class, 'handle_delete' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ), 31 );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'localize_block_editor_fonts' ), 31 );
	}

	/** Registers the font library administration page. */
	public static function register_page(): void {
		add_submenu_page(
			'wp-piwigo-display',
			__( 'Polices locales', 'wp-piwigo-display' ),
			__( 'Polices locales', 'wp-piwigo-display' ),
			'manage_options',
			'wp-piwigo-display-fonts',
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Returns stored font metadata.
	 *
	 * @return array<string,array{name:string,path:string,format:string}>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION_NAME, array() );
		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Returns one normalized stored font.
	 *
	 * @param string $id Font identifier.
	 * @return array{name:string,path:string,format:string}|null
	 */
	public static function get( string $id ): ?array {
		$id      = sanitize_key( $id );
		$library = self::all();
		$font    = $library[ $id ] ?? null;

		if ( ! is_array( $font ) || ! isset( $font['name'], $font['path'], $font['format'] ) ) {
			return null;
		}

		$format = sanitize_key( (string) $font['format'] );
		$path   = self::normalize_relative_path( (string) $font['path'] );
		if ( ! in_array( $format, array( 'woff2', 'woff' ), true ) || '' === $path ) {
			return null;
		}

		return array(
			'name'   => sanitize_text_field( (string) $font['name'] ),
			'path'   => $path,
			'format' => $format,
		);
	}

	/**
	 * Returns editor-safe font choices.
	 *
	 * @return array<int,array{id:string,name:string,value:string,family:string}>
	 */
	public static function ui_fonts(): array {
		$fonts = array();
		foreach ( array_keys( self::all() ) as $id ) {
			$normalized = self::get( (string) $id );
			if ( null === $normalized ) {
				continue;
			}

			$id      = sanitize_key( (string) $id );
			$fonts[] = array(
				'id'     => $id,
				'name'   => $normalized['name'],
				'value'  => 'user-' . $id,
				'family' => self::internal_font_family( $id ),
			);
		}

		return $fonts;
	}

	/**
	 * Resolves a built-in/theme font mode or imported local font stack.
	 *
	 * @param string $font Requested font identifier.
	 * @return string
	 */
	public static function font_stack( string $font ): string {
		$font = sanitize_key( $font );
		if ( str_starts_with( $font, 'user-' ) ) {
			$id = sanitize_key( substr( $font, 5 ) );
			if ( null !== self::get( $id ) ) {
				self::enqueue_font( $id );
				return '"' . self::internal_font_family( $id ) . '", sans-serif';
			}
		}

		return match ( $font ) {
			'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'serif'  => 'Georgia, "Times New Roman", serif',
			'mono'   => 'ui-monospace, "SFMono-Regular", Consolas, monospace',
			default  => 'inherit',
		};
	}

	/** Renders the local user-font library page. */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$library   = self::all();
		$max_bytes = self::max_bytes();
		/* translators: %s: maximum local font upload size. */
		$max_size_label = sprintf( __( 'Taille maximale : %s. WOFF2 est recommandé.', 'wp-piwigo-display' ), size_format( $max_bytes ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Polices locales — Texte rempli de photos', 'wp-piwigo-display' ); ?></h1>
			<p><?php esc_html_e( 'Les fichiers restent dans les uploads WordPress et ne sont jamais ajoutés au paquet du plugin. Importez uniquement une police que vous avez le droit d’utiliser.', 'wp-piwigo-display' ); ?></p>
			<div class="card" style="max-width:1050px">
				<h2><?php esc_html_e( 'Importer une police', 'wp-piwigo-display' ); ?></h2>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" name="action" value="wpd_upload_user_font">
					<?php wp_nonce_field( 'wpd_upload_user_font' ); ?>
					<p><label><?php esc_html_e( 'Nom affiché', 'wp-piwigo-display' ); ?> <input type="text" name="wpd_user_font_name" maxlength="80"></label></p>
					<p><label><?php esc_html_e( 'Fichier WOFF2 ou WOFF', 'wp-piwigo-display' ); ?> <input type="file" name="wpd_user_font" accept=".woff2,.woff,font/woff2,font/woff" required></label></p>
					<p class="description"><?php echo esc_html( $max_size_label ); ?></p>
					<?php submit_button( __( 'Importer la police', 'wp-piwigo-display' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<h2><?php esc_html_e( 'Bibliothèque', 'wp-piwigo-display' ); ?></h2>
			<?php if ( array() === $library ) : ?>
				<p><?php esc_html_e( 'Aucune police utilisateur pour le moment.', 'wp-piwigo-display' ); ?></p>
			<?php else : ?>
				<div class="wpd-user-font-grid">
					<?php foreach ( array_keys( $library ) as $id ) : ?>
						<?php
						$normalized = self::get( (string) $id );
						if ( null === $normalized ) {
							continue;
						}
						$id = sanitize_key( (string) $id );
						?>
						<div class="wpd-user-font-card">
							<strong><?php echo esc_html( $normalized['name'] ); ?></strong>
							<div class="wpd-user-font-preview" style="font-family:'<?php echo esc_attr( self::internal_font_family( $id ) ); ?>',sans-serif">PÊLE-MÊLE — Été 2026</div>
							<code>user-<?php echo esc_html( $id ); ?></code>
							<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
								<input type="hidden" name="action" value="wpd_delete_user_font">
								<input type="hidden" name="wpd_user_font_id" value="<?php echo esc_attr( $id ); ?>">
								<?php wp_nonce_field( 'wpd_delete_user_font' ); ?>
								<button type="submit" class="button-link-delete"><?php esc_html_e( 'Supprimer', 'wp-piwigo-display' ); ?></button>
							</form>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/** Handles a secured WOFF/WOFF2 upload. */
	public static function handle_upload(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation d’importer des polices.', 'wp-piwigo-display' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'wpd_upload_user_font' );
		$library = self::all();
		if ( count( $library ) >= self::MAX_FONTS ) {
			self::redirect_with_error( 'limit' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload metadata and temporary file are validated below.
		$file = isset( $_FILES['wpd_user_font'] ) && is_array( $_FILES['wpd_user_font'] ) ? $_FILES['wpd_user_font'] : null;
		if ( ! $file || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			self::redirect_with_error( 'upload' );
		}

		$name     = sanitize_text_field( wp_unslash( (string) ( $_POST['wpd_user_font_name'] ?? '' ) ) );
		$filename = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
		$tmp_name = (string) ( $file['tmp_name'] ?? '' );
		$size     = (int) ( $file['size'] ?? 0 );
		$format   = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( ! in_array( $format, array( 'woff2', 'woff' ), true ) || ! is_uploaded_file( $tmp_name ) ) {
			self::redirect_with_error( 'type' );
		}
		if ( $size <= 0 || $size > self::max_bytes() ) {
			self::redirect_with_error( 'size' );
		}
		if ( ! self::has_valid_signature( $tmp_name, $format ) || ! self::has_valid_mime( $tmp_name, $format ) ) {
			self::redirect_with_error( 'validation' );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		add_filter( 'upload_dir', array( self::class, 'filter_upload_dir' ) );
		$handled = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => array(
					'woff2' => 'font/woff2',
					'woff'  => 'font/woff',
				),
			)
		);
		remove_filter( 'upload_dir', array( self::class, 'filter_upload_dir' ) );

		if ( isset( $handled['error'] ) || empty( $handled['file'] ) ) {
			self::redirect_with_error( 'move' );
		}

		$path = wp_normalize_path( (string) $handled['file'] );
		$hash = hash_file( 'sha256', $path );
		if ( ! is_string( $hash ) || '' === $hash ) {
			wp_delete_file( $path );
			self::redirect_with_error( 'hash' );
		}
		$id = substr( $hash, 0, 12 );
		if ( isset( $library[ $id ] ) ) {
			wp_delete_file( $path );
			self::redirect( array( 'wpd_font_added' => $id ) );
		}

		$uploads  = wp_upload_dir();
		$base_dir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
		if ( ! str_starts_with( $path, $base_dir ) ) {
			wp_delete_file( $path );
			self::redirect_with_error( 'path' );
		}

		$relative = self::normalize_relative_path( substr( $path, strlen( $base_dir ) ) );
		if ( '' === $relative ) {
			wp_delete_file( $path );
			self::redirect_with_error( 'path' );
		}
		if ( '' === $name ) {
			$name = pathinfo( $filename, PATHINFO_FILENAME );
		}

		$library[ $id ] = array(
			'name'   => substr( sanitize_text_field( $name ), 0, 80 ),
			'path'   => $relative,
			'format' => $format,
		);
		update_option( self::OPTION_NAME, $library, false );
		self::redirect( array( 'wpd_font_added' => $id ) );
	}

	/** Handles deletion of one imported user font. */
	public static function handle_delete(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Vous n’avez pas l’autorisation de supprimer des polices.', 'wp-piwigo-display' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'wpd_delete_user_font' );
		$id      = sanitize_key( wp_unslash( (string) ( $_POST['wpd_user_font_id'] ?? '' ) ) );
		$library = self::all();
		$font    = self::get( $id );

		if ( null !== $font ) {
			$path = self::absolute_path( $font['path'] );
			if ( '' !== $path && is_file( $path ) ) {
				wp_delete_file( $path );
			}
			unset( $library[ $id ] );
			update_option( self::OPTION_NAME, $library, false );
		}

		self::redirect( array( 'wpd_font_deleted' => $id ) );
	}

	/**
	 * Redirects uploads into the dedicated font subdirectory.
	 *
	 * @param array<string,string> $dirs WordPress upload directories.
	 * @return array<string,string>
	 */
	public static function filter_upload_dir( array $dirs ): array {
		$dirs['path']   = $dirs['basedir'] . self::UPLOAD_SUBDIR;
		$dirs['url']    = $dirs['baseurl'] . self::UPLOAD_SUBDIR;
		$dirs['subdir'] = self::UPLOAD_SUBDIR;
		return $dirs;
	}

	/**
	 * Enqueues user-font UI data on relevant administration screens.
	 *
	 * @param string $hook Current administration screen hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( string $hook ): void {
		$is_classic = in_array( $hook, array( 'post.php', 'post-new.php' ), true );

		// The page query argument only selects an administration screen and does not mutate data.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page        = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$is_composer = 'wp-piwigo-display-compose' === $page;
		$is_library  = 'wp-piwigo-display-fonts' === $page;

		if ( $is_library ) {
			wp_enqueue_style( 'wpd-user-fonts', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-user-fonts.css', array(), WPD_VERSION );
			self::enqueue_all_fonts();
		}

		if ( ! $is_classic && ! $is_composer ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::enqueue_all_fonts();
		wp_enqueue_script(
			'wpd-user-fonts-ui',
			WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-user-fonts-ui.js',
			array(),
			WPD_VERSION,
			true
		);
		wp_localize_script( 'wpd-user-fonts-ui', 'WPDUserFonts', self::ui_fonts() );
	}

	/** Exposes imported fonts to Gutenberg controls. */
	public static function localize_block_editor_fonts(): void {
		self::enqueue_all_fonts();
		wp_localize_script( 'wpd-block-masonry-controls', 'WPDUserFonts', self::ui_fonts() );
	}

	/**
	 * Returns the filtered and bounded maximum upload size.
	 *
	 * @return int
	 */
	private static function max_bytes(): int {
		$max = (int) apply_filters( 'piwigo_display_user_font_max_bytes', self::DEFAULT_MAX_BYTES );
		return max( 65536, min( 8388608, $max ) );
	}

	/**
	 * Verifies the WOFF/WOFF2 magic signature.
	 *
	 * @param string $path   Uploaded temporary path.
	 * @param string $format Expected format.
	 * @return bool
	 */
	private static function has_valid_signature( string $path, string $format ): bool {
		// Local temporary upload already validated with is_uploaded_file().
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$signature = file_get_contents( $path, false, null, 0, 4 );
		$expected  = 'woff2' === $format ? 'wOF2' : 'wOFF';
		return $signature === $expected;
	}

	/**
	 * Verifies MIME when Fileinfo is available; magic bytes remain mandatory.
	 *
	 * @param string $path   Uploaded temporary path.
	 * @param string $format Expected format.
	 * @return bool
	 */
	private static function has_valid_mime( string $path, string $format ): bool {
		if ( ! function_exists( 'finfo_open' ) ) {
			return true;
		}

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		if ( false === $finfo ) {
			return true;
		}
		$mime = finfo_file( $finfo, $path );
		finfo_close( $finfo );
		if ( ! is_string( $mime ) ) {
			return false;
		}

		$allowed = 'woff2' === $format
			? array( 'font/woff2', 'application/font-woff2', 'application/octet-stream' )
			: array( 'font/woff', 'application/font-woff', 'application/x-font-woff', 'application/octet-stream' );
		return in_array( strtolower( trim( $mime ) ), $allowed, true );
	}

	/** Enqueues all stored local fonts for editor/library previews. */
	private static function enqueue_all_fonts(): void {
		foreach ( array_keys( self::all() ) as $id ) {
			self::enqueue_font( (string) $id );
		}
	}

	/**
	 * Enqueues one local font-face rule.
	 *
	 * @param string $id Font identifier.
	 * @return void
	 */
	private static function enqueue_font( string $id ): void {
		$id = sanitize_key( $id );
		if ( isset( self::$enqueued[ $id ] ) ) {
			return;
		}

		$font = self::get( $id );
		if ( null === $font ) {
			return;
		}
		$url = self::font_url( $font['path'] );
		if ( '' === $url ) {
			return;
		}

		wp_enqueue_style( 'wpd-user-fonts', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-user-fonts.css', array(), WPD_VERSION );
		$css = sprintf(
			'@font-face{font-family:"%1$s";src:url("%2$s") format("%3$s");font-display:swap;font-style:normal;font-weight:normal;}',
			esc_attr( self::internal_font_family( $id ) ),
			esc_url( $url ),
			esc_attr( $font['format'] )
		);
		wp_add_inline_style( 'wpd-user-fonts', $css );
		self::$enqueued[ $id ] = true;
	}

	/**
	 * Returns the deterministic internal CSS font-family name.
	 *
	 * @param string $id Font identifier.
	 * @return string
	 */
	private static function internal_font_family( string $id ): string {
		return 'wpd-user-font-' . sanitize_key( $id );
	}

	/**
	 * Validates and normalizes a stored relative upload path.
	 *
	 * @param string $relative Candidate relative path.
	 * @return string
	 */
	private static function normalize_relative_path( string $relative ): string {
		$relative = ltrim( wp_normalize_path( $relative ), '/' );
		$prefix   = ltrim( self::UPLOAD_SUBDIR, '/' ) . '/';
		if ( ! str_starts_with( $relative, $prefix ) || str_contains( $relative, '../' ) || str_contains( $relative, '..\\' ) || str_contains( $relative, ':' ) ) {
			return '';
		}
		return $relative;
	}

	/**
	 * Resolves a stored relative font path to a public local URL.
	 *
	 * @param string $relative Relative uploads path.
	 * @return string
	 */
	private static function font_url( string $relative ): string {
		$relative = self::normalize_relative_path( $relative );
		if ( '' === $relative ) {
			return '';
		}
		$uploads = wp_upload_dir();
		return esc_url_raw( trailingslashit( $uploads['baseurl'] ) . $relative );
	}

	/**
	 * Resolves a stored relative font path to a safe local absolute path.
	 *
	 * @param string $relative Relative uploads path.
	 * @return string
	 */
	private static function absolute_path( string $relative ): string {
		$relative = self::normalize_relative_path( $relative );
		if ( '' === $relative ) {
			return '';
		}

		$uploads  = wp_upload_dir();
		$base_dir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
		$path     = wp_normalize_path( $base_dir . $relative );
		$allowed  = $base_dir . ltrim( self::UPLOAD_SUBDIR, '/' ) . '/';
		return str_starts_with( $path, $allowed ) ? $path : '';
	}

	/**
	 * Redirects back to the font library page.
	 *
	 * @param array<string,string> $args Query arguments.
	 * @return void
	 */
	private static function redirect( array $args = array() ): void {
		$url = add_query_arg( $args, admin_url( 'admin.php?page=wp-piwigo-display-fonts' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirects with a normalized import error code.
	 *
	 * @param string $code Error code.
	 * @return void
	 */
	private static function redirect_with_error( string $code ): void {
		self::redirect( array( 'wpd_font_error' => sanitize_key( $code ) ) );
	}
}
