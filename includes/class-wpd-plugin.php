<?php
/**
 * Main plugin bootstrap.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin hooks, assets, settings, and administrative actions.
 */
final class WPD_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Initializes the plugin singleton.
	 *
	 * @return self Plugin instance.
	 */
	public static function init(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers plugin hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_album_picker' ) );
		add_action( 'wp_ajax_wpd_get_albums', array( $this, 'ajax_get_albums' ) );
		add_action( 'admin_post_wpd_clear_cache', array( $this, 'clear_cache' ) );
		add_action( 'admin_post_wpd_test_connection', array( $this, 'test_connection' ) );
		add_action( 'admin_post_wpd_export_diagnostic', array( WPD_Diagnostic::class, 'export' ) );
	}

	/**
	 * Loads the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wp-piwigo-display',
			false,
			dirname( plugin_basename( WPD_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Registers public shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		WPD_Shortcode::register();
	}

	/**
	 * Registers the Gutenberg block.
	 *
	 * @return void
	 */
	public function register_block(): void {
		WPD_Block::register();
	}

	/**
	 * Registers public styles and scripts.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style( 'wp-piwigo-display', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display.css', array(), WPD_VERSION );
		wp_add_inline_style( 'wp-piwigo-display', '.wpd-slider-layout{width:var(--wpd-slider-width,100%);max-width:100%;box-sizing:border-box}.wpd-slider-layout.wpd-slider-align-left{float:left;margin:0 1.5rem 1rem 0}.wpd-slider-layout.wpd-slider-align-right{float:right;margin:0 0 1rem 1.5rem}.wpd-slider-layout.wpd-slider-align-center{margin-left:auto;margin-right:auto}@media(max-width:782px){.wpd-slider-layout{float:none!important;width:100%!important;margin-left:0!important;margin-right:0!important}}' );
		wp_register_style( 'wpd-splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css', array(), '4.1.4' );
		wp_register_script( 'wpd-splide', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', array(), '4.1.4', true );
		wp_register_script( 'wp-piwigo-display-slider', WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-slider.js', array( 'wpd-splide' ), WPD_VERSION, true );
		wp_register_script( 'wp-piwigo-display', WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display.js', array(), WPD_VERSION, true );
	}

	/**
	 * Registers plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		WPD_Settings::register();
	}

	/**
	 * Registers administration pages.
	 *
	 * @return void
	 */
	public function register_settings_page(): void {
		WPD_Settings::register_page();
		WPD_Diagnostic::register_page();
	}

	/**
	 * Enqueues the album picker on supported administration screens.
	 *
	 * @param string $hook Current administration hook suffix.
	 * @return void
	 */
	public function enqueue_admin_album_picker( string $hook ): void {
		$screen         = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_editor      = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_plugin_page = $screen && false !== strpos( (string) $screen->id, 'wp-piwigo-display' );

		if ( ! $is_editor && ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'wpd-album-picker',
			WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-album-picker.css',
			array(),
			WPD_VERSION
		);
		wp_enqueue_script(
			'wpd-album-picker',
			WPD_PLUGIN_URL . 'assets/js/wp-piwigo-display-album-picker.js',
			array( 'jquery' ),
			WPD_VERSION,
			true
		);
		wp_localize_script(
			'wpd-album-picker',
			'WPDAlbumPickerConfig',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wpd_get_albums' ),
				'labels'  => array(
					'loading' => __( 'Chargement des albums…', 'wp-piwigo-display' ),
					'error'   => __( 'Impossible de charger les albums. La saisie manuelle reste disponible.', 'wp-piwigo-display' ),
					'empty'   => __( 'Aucun album trouvé.', 'wp-piwigo-display' ),
					'search'  => __( 'Rechercher un album…', 'wp-piwigo-display' ),
				),
			)
		);
	}

	/**
	 * Returns albums for the administration picker.
	 *
	 * @return void
	 */
	public function ajax_get_albums(): void {
		check_ajax_referer( 'wpd_get_albums', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Accès refusé.', 'wp-piwigo-display' ) ), 403 );
		}

		$url = WPD_Settings::get_piwigo_url();
		if ( '' === $url ) {
			wp_send_json_error( array( 'message' => __( 'URL Piwigo non configurée.', 'wp-piwigo-display' ) ), 400 );
		}

		$categories = ( new WPD_Api( $url ) )->get_all_categories();
		if ( is_wp_error( $categories ) ) {
			wp_send_json_error( array( 'message' => $categories->get_error_message() ), 502 );
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

			$path_ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) ( $category['uppercats'] ?? $id ) ) ) ) );
			if ( empty( $path_ids ) ) {
				$path_ids = array( $id );
			}

			$path_names = array();
			foreach ( $path_ids as $path_id ) {
				if ( isset( $names[ $path_id ] ) ) {
					$path_names[] = $names[ $path_id ];
				}
			}

			$albums[] = array(
				'id'     => $id,
				'name'   => $names[ $id ] ?? ( 'Album ' . $id ),
				'path'   => implode( '/', $path_names ),
				'depth'  => max( 0, count( $path_ids ) - 1 ),
				'images' => absint( $category['nb_images'] ?? $category['total_nb_images'] ?? 0 ),
			);
		}

		usort(
			$albums,
			static function ( array $a, array $b ): int {
				return strnatcasecmp( (string) $a['path'], (string) $b['path'] );
			}
		);

		wp_send_json_success( array( 'albums' => $albums ) );
	}

	/**
	 * Clears the plugin image cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'wp-piwigo-display' ) );
		}

		check_admin_referer( 'wpd_clear_cache' );
		$deleted = WPD_Cache::clear_all();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => 'wp-piwigo-display-settings',
					'wpd_cache_cleared' => (string) $deleted,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Tests the configured Piwigo connection.
	 *
	 * @return void
	 */
	public function test_connection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Accès refusé.', 'wp-piwigo-display' ) );
		}

		check_admin_referer( 'wpd_test_connection' );

		$result = 'unknown_error';
		$url    = WPD_Settings::get_piwigo_url();

		if ( '' === $url ) {
			$result = 'missing_url';
		} else {
			try {
				$api      = new WPD_Api( $url );
				$response = $api->test_connection();

				if ( is_wp_error( $response ) ) {
					$result = $this->get_connection_test_result( $response );
				} else {
					$result = 'success';
				}
			} catch ( Throwable $exception ) {
				$result = 'internal_error';

				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'WP Piwigo Display connection test: ' . $exception->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Logged only when WP_DEBUG is enabled.
				}
			}
		}

		$redirect_url = add_query_arg(
			array(
				'page'                => 'wp-piwigo-display-settings',
				'wpd_connection_test' => $result,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Maps an API error to a connection-test result code.
	 *
	 * @param WP_Error $error API error.
	 * @return string Result code.
	 */
	private function get_connection_test_result( WP_Error $error ): string {
		switch ( $error->get_error_code() ) {
			case 'wpd_http_error':
				return 'http_error';

			case 'wpd_http_status':
				return 'http_status';

			case 'wpd_invalid_json':
				return 'invalid_response';

			case 'wpd_api_error':
				return 'api_error';

			default:
				return 'unknown_error';
		}
	}
}
