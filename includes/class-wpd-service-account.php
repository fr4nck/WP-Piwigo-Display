<?php
/**
 * Piwigo service-account settings and administration handlers.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and exposes the Piwigo service-account configuration.
 *
 * Constants defined in wp-config.php take precedence over database options.
 * Passwords are never rendered back into the administration interface or
 * returned by the public status method.
 */
final class WPD_Service_Account {

	/** Database option containing the account configuration. */
	public const OPTION_NAME = 'wp_piwigo_display_service_account';

	/** Optional wp-config.php constant controlling account activation. */
	public const ENABLED_CONSTANT = 'WPD_PIWIGO_SERVICE_ENABLED';

	/** Optional wp-config.php constant containing the account username. */
	public const USERNAME_CONSTANT = 'WPD_PIWIGO_SERVICE_USERNAME';

	/** Optional wp-config.php constant containing the account password. */
	public const PASSWORD_CONSTANT = 'WPD_PIWIGO_SERVICE_PASSWORD';

	/**
	 * Registers settings and administration hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'register_settings' ), 20 );
		add_action( 'wp_ajax_wpd_get_albums', array( self::class, 'ajax_get_albums' ), 1 );
		add_action( 'admin_post_wpd_test_service_account', array( self::class, 'test_connection' ) );
		add_action( 'admin_notices', array( self::class, 'render_notice' ) );
	}

	/**
	 * Registers the service-account settings section and fields.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		register_setting(
			'wp_piwigo_display',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_options' ),
				'default'           => array(
					'enabled'  => 'false',
					'username' => '',
					'password' => '',
				),
			)
		);

		add_settings_section(
			'wp_piwigo_display_service_account',
			__( 'Compte de service Piwigo', 'wp-piwigo-display' ),
			array( self::class, 'render_section' ),
			'wp-piwigo-display'
		);
		add_settings_field( 'wpd_service_enabled', __( 'Activer', 'wp-piwigo-display' ), array( self::class, 'render_enabled_field' ), 'wp-piwigo-display', 'wp_piwigo_display_service_account' );
		add_settings_field( 'wpd_service_username', __( 'Utilisateur Piwigo', 'wp-piwigo-display' ), array( self::class, 'render_username_field' ), 'wp-piwigo-display', 'wp_piwigo_display_service_account' );
		add_settings_field( 'wpd_service_password', __( 'Mot de passe Piwigo', 'wp-piwigo-display' ), array( self::class, 'render_password_field' ), 'wp-piwigo-display', 'wp_piwigo_display_service_account' );
	}

	/**
	 * Sanitizes the saved account configuration.
	 *
	 * An empty submitted password preserves the existing secret. WordPress does
	 * not provide a portable encryption API for plugin options; administrators
	 * requiring secrets outside the database can use the wp-config.php constants.
	 *
	 * @param mixed $options Submitted option value.
	 * @return array Sanitized account configuration.
	 */
	public static function sanitize_options( $options ): array {
		$options  = is_array( $options ) ? $options : array();
		$previous = self::get_options();
		$password = isset( $options['password'] ) ? (string) $options['password'] : '';

		$sanitized = array(
			'enabled'  => filter_var( $options['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN ) ? 'true' : 'false',
			'username' => sanitize_text_field( (string) ( $options['username'] ?? '' ) ),
			'password' => '' !== $password ? $password : (string) ( $previous['password'] ?? '' ),
		);

		$changed = ( $previous['enabled'] ?? 'false' ) !== $sanitized['enabled']
			|| ( $previous['username'] ?? '' ) !== $sanitized['username']
			|| ( $previous['password'] ?? '' ) !== $sanitized['password'];

		if ( $changed && class_exists( 'WPD_Cache' ) ) {
			WPD_Cache::clear_all();
		}

		return $sanitized;
	}

	/**
	 * Renders the service-account settings description.
	 *
	 * @return void
	 */
	public static function render_section(): void {
		echo '<p>' . esc_html__( 'Ce compte permet à WordPress de publier des albums privés autorisés dans Piwigo. Les visiteurs ne se connectent pas à Piwigo.', 'wp-piwigo-display' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Attention : les photos sélectionnées deviennent publiques sur la page WordPress.', 'wp-piwigo-display' ) . '</strong></p>';
		if ( self::is_managed_by_constants() ) {
			echo '<p class="description">' . esc_html__( 'Les identifiants sont définis dans wp-config.php et sont prioritaires sur les champs ci-dessous.', 'wp-piwigo-display' ) . '</p>';
		}
	}

	/**
	 * Renders the account-enabled field.
	 *
	 * @return void
	 */
	public static function render_enabled_field(): void {
		printf(
			'<label><input type="checkbox" name="%1$s[enabled]" value="true" %2$s %3$s> %4$s</label>',
			esc_attr( self::OPTION_NAME ),
			checked( self::is_enabled(), true, false ),
			disabled( defined( self::ENABLED_CONSTANT ), true, false ),
			esc_html__( 'Utiliser le compte technique pour les albums privés', 'wp-piwigo-display' )
		);
	}

	/**
	 * Renders the service-account username field.
	 *
	 * @return void
	 */
	public static function render_username_field(): void {
		printf(
			'<input type="text" class="regular-text" name="%1$s[username]" value="%2$s" autocomplete="off" %3$s>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( self::get_username() ),
			disabled( defined( self::USERNAME_CONSTANT ), true, false )
		);
	}

	/**
	 * Renders the write-only password field and connection-test action.
	 *
	 * @return void
	 */
	public static function render_password_field(): void {
		$placeholder = '' !== self::get_password()
			? __( 'Mot de passe enregistré — laisser vide pour le conserver', 'wp-piwigo-display' )
			: '';

		printf(
			'<input type="password" class="regular-text" name="%1$s[password]" value="" placeholder="%2$s" autocomplete="new-password" %3$s><p class="description">%4$s</p>',
			esc_attr( self::OPTION_NAME ),
			esc_attr( $placeholder ),
			disabled( defined( self::PASSWORD_CONSTANT ), true, false ),
			esc_html__( 'Le mot de passe n’est jamais réaffiché dans l’administration.', 'wp-piwigo-display' )
		);

		$url = wp_nonce_url( admin_url( 'admin-post.php?action=wpd_test_service_account' ), 'wpd_test_service_account' );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Tester le compte de service', 'wp-piwigo-display' ) . '</a></p>';
	}

	/**
	 * Tests the configured account and redirects to a signed status notice.
	 *
	 * @return void
	 */
	public static function test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'wp-piwigo-display' ) );
		}
		check_admin_referer( 'wpd_test_service_account' );

		if ( ! self::is_configured() ) {
			$result = 'not_configured';
		} elseif ( '' === WPD_Settings::get_piwigo_url() ) {
			$result = 'missing_url';
		} else {
			$response = ( new WPD_Service_Api( WPD_Settings::get_piwigo_url() ) )->test_connection();
			$result   = is_wp_error( $response ) ? 'error' : 'success';
		}

		$notice_nonce = wp_create_nonce( 'wpd_service_test_notice' );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                     => 'wp-piwigo-display-settings',
					'wpd_service_test'         => $result,
					'wpd_service_test_notice'  => $notice_nonce,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Renders the signed result of a service-account connection test.
	 *
	 * @return void
	 */
	public static function render_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page         = isset( $_GET['page'] ) ? sanitize_key( (string) wp_unslash( $_GET['page'] ) ) : '';
		$result       = isset( $_GET['wpd_service_test'] ) ? sanitize_key( (string) wp_unslash( $_GET['wpd_service_test'] ) ) : '';
		$notice_nonce = isset( $_GET['wpd_service_test_notice'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['wpd_service_test_notice'] ) ) : '';

		if ( 'wp-piwigo-display-settings' !== $page || '' === $result ) {
			return;
		}

		if ( ! wp_verify_nonce( $notice_nonce, 'wpd_service_test_notice' ) ) {
			return;
		}

		$class    = 'success' === $result ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
		$messages = array(
			'success'        => __( 'Connexion du compte de service réussie.', 'wp-piwigo-display' ),
			'not_configured' => __( 'Compte de service incomplet ou désactivé.', 'wp-piwigo-display' ),
			'missing_url'    => __( 'URL Piwigo manquante.', 'wp-piwigo-display' ),
			'error'          => __( 'Échec de connexion. Vérifiez HTTPS, l’identifiant, le mot de passe et les droits Piwigo.', 'wp-piwigo-display' ),
		);
		$message  = $messages[ $result ] ?? $messages['error'];
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	/**
	 * Returns albums visible to the configured service account.
	 *
	 * @return void
	 */
	public static function ajax_get_albums(): void {
		check_ajax_referer( 'wpd_get_albums', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accès refusé.', 'wp-piwigo-display' ) ), 403 );
		}

		if ( ! self::is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'Compte de service Piwigo non configuré.', 'wp-piwigo-display' ) ), 400 );
		}

		$url        = WPD_Settings::get_piwigo_url();
		$categories = ( new WPD_Service_Api( $url ) )->get_all_categories();
		if ( is_wp_error( $categories ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Impossible de charger les albums privés depuis Piwigo.', 'wp-piwigo-display' ),
				),
				502
			);
		}

		$names = array();
		foreach ( $categories as $category ) {
			$id = absint( $category['id'] ?? 0 );
			if ( 0 < $id ) {
				$names[ $id ] = sanitize_text_field( (string) ( $category['name'] ?? ( 'Album ' . $id ) ) );
			}
		}

		$albums = array();
		foreach ( $categories as $category ) {
			$id = absint( $category['id'] ?? 0 );
			if ( 0 >= $id ) {
				continue;
			}

			$path_ids   = array_values( array_filter( array_map( 'absint', explode( ',', (string) ( $category['uppercats'] ?? $id ) ) ) ) );
			$path_names = array();
			$album_path = ! empty( $path_ids ) ? $path_ids : array( $id );

			foreach ( $album_path as $path_id ) {
				if ( isset( $names[ $path_id ] ) ) {
					$path_names[] = $names[ $path_id ];
				}
			}

			$albums[] = array(
				'id'             => $id,
				'name'           => $names[ $id ] ?? ( 'Album ' . $id ),
				'path'           => implode( '/', $path_names ),
				'depth'          => max( 0, count( $path_ids ) - 1 ),
				'images'         => absint( $category['nb_images'] ?? $category['total_nb_images'] ?? 0 ),
				'serviceAccount' => true,
			);
		}

		usort( $albums, static fn( array $a, array $b ): int => strnatcasecmp( (string) $a['path'], (string) $b['path'] ) );
		wp_send_json_success(
			array(
				'albums'         => $albums,
				'serviceAccount' => true,
			)
		);
	}

	/**
	 * Returns whether the service account is enabled.
	 *
	 * @return bool Whether the account is enabled.
	 */
	public static function is_enabled(): bool {
		if ( defined( self::ENABLED_CONSTANT ) ) {
			return filter_var( constant( self::ENABLED_CONSTANT ), FILTER_VALIDATE_BOOLEAN );
		}

		$options = self::get_options();
		return filter_var( $options['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Returns the configured service-account username.
	 *
	 * @return string Sanitized username.
	 */
	public static function get_username(): string {
		if ( defined( self::USERNAME_CONSTANT ) ) {
			return sanitize_text_field( (string) constant( self::USERNAME_CONSTANT ) );
		}

		$options = self::get_options();
		return sanitize_text_field( (string) ( $options['username'] ?? '' ) );
	}

	/**
	 * Returns the configured service-account password.
	 *
	 * This method is for internal server-side authentication only.
	 *
	 * @return string Account password.
	 */
	public static function get_password(): string {
		if ( defined( self::PASSWORD_CONSTANT ) ) {
			return (string) constant( self::PASSWORD_CONSTANT );
		}

		$options = self::get_options();
		return (string) ( $options['password'] ?? '' );
	}

	/**
	 * Returns whether all required account values are configured.
	 *
	 * @return bool Whether the account can be used.
	 */
	public static function is_configured(): bool {
		return self::is_enabled() && '' !== self::get_username() && '' !== self::get_password();
	}

	/**
	 * Returns whether any account value is managed through wp-config.php.
	 *
	 * @return bool Whether constants manage the account.
	 */
	public static function is_managed_by_constants(): bool {
		return defined( self::ENABLED_CONSTANT ) || defined( self::USERNAME_CONSTANT ) || defined( self::PASSWORD_CONSTANT );
	}

	/**
	 * Returns a non-secret hash identifying the current account context.
	 *
	 * @return string Account-context hash or anonymous marker.
	 */
	public static function get_context_hash(): string {
		if ( ! self::is_configured() ) {
			return 'anonymous';
		}

		return hash( 'sha256', WPD_Settings::get_piwigo_url() . '|' . self::get_username() );
	}

	/**
	 * Returns non-secret service-account status information.
	 *
	 * @return array Public status without credentials.
	 */
	public static function get_public_status(): array {
		return array(
			'enabled'    => self::is_enabled(),
			'configured' => self::is_configured(),
			'source'     => self::is_managed_by_constants() ? 'wp-config.php' : 'database',
		);
	}

	/**
	 * Returns the raw account option array.
	 *
	 * @return array Stored account configuration.
	 */
	private static function get_options(): array {
		$options = get_option( self::OPTION_NAME, array() );
		return is_array( $options ) ? $options : array();
	}
}
