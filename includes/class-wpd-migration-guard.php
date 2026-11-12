<?php
/**
 * Guard Piwigo Display during a transition between distribution directories.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Protects activation and warns about inconsistent states without modifying
 * network or site activation options in the background.
 */
final class WPD_Migration_Guard {

	private const LEGACY_PLUGIN = 'wp-piwigo-display/wp-piwigo-display.php';

	/**
	 * Track plugins already registered during this request.
	 *
	 * @var array<string, bool>
	 */
	private static array $registered = array();

	/**
	 * Register activation controls and non-destructive notices for one copy.
	 *
	 * @param string $entrypoint Absolute path to the plugin entrypoint.
	 */
	public static function register( string $entrypoint ): void {
		$self = plugin_basename( $entrypoint );
		if ( isset( self::$registered[ $self ] ) ) {
			return;
		}
		self::$registered[ $self ] = true;

		register_activation_hook(
			$entrypoint,
			static function ( bool $network_wide = false ) use ( $entrypoint ): void {
				if ( array() !== self::conflicting_active_plugins( $entrypoint, $network_wide ) ) {
					self::block_activation();
				}
			}
		);

		// The immutable 3.0.0 has no guard. Intercept attempts to activate it
		// when this newer copy is already active in the relevant scope.
		add_action(
			'activate_plugin',
			static function ( string $candidate, bool $network_wide = false ): void {
				if ( self::is_family_plugin( $candidate )
					&& array() !== self::conflicting_active_plugins( $candidate, $network_wide )
				) {
					self::block_activation();
				}
			},
			10,
			2
		);

		// The legacy 3.0.0 uninstaller cannot be patched retroactively. In a
		// request where this newer copy is active, abort that destructive action
		// instead of pretending that returning from the hook can stop core.
		add_action(
			'pre_uninstall_plugin',
			static function ( string $candidate ) use ( $self ): void {
				if ( self::LEGACY_PLUGIN !== $candidate || $self === $candidate ) {
					return;
				}

				if ( ! function_exists( 'get_plugins' ) ) {
					return;
				}

				$wpd_legacy  = get_plugins()[ $candidate ] ?? array();
				$wpd_version = trim( (string) ( $wpd_legacy['Version'] ?? '' ) );
				if ( '' === $wpd_version || version_compare( $wpd_version, '3.0.0', '<=' ) ) {
					wp_die(
						esc_html__( 'Suppression interdite : mettez d’abord à jour Piwigo Display 3.0.0 vers 3.0.1 dans son dossier historique, sans désinstallation.', 'wp-piwigo-display' ),
						esc_html__( 'Migration Piwigo Display non sécurisée', 'wp-piwigo-display' ),
						array(
							'response'  => 409,
							'back_link' => true,
						)
					);
				}
			},
			10,
			2
		);

		// Never silently deactivate a sitewide or network copy on admin_init.
		// In particular, an ordinary subscriber must not be able to mutate the
		// plugin state for other sites by opening their own profile.
		$wpd_notice = static function () use ( $entrypoint, $self ): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			if ( ! in_array( $self, self::active_plugins_on_current_site(), true ) ) {
				return;
			}
			if ( array() === self::conflicting_active_plugins( $entrypoint ) ) {
				return;
			}
			echo '<div class="notice notice-warning"><p>'
				. esc_html__( 'Deux copies actives de Piwigo Display ont été détectées. Un administrateur doit désactiver manuellement la copie concurrente ; ne supprimez pas la version 3.0.0.', 'wp-piwigo-display' )
				. '</p></div>';
		};
		add_action( 'admin_notices', $wpd_notice );
		add_action( 'network_admin_notices', $wpd_notice );
	}

	/**
	 * Find other active, existing copies in the scope of an activation.
	 *
	 * @param string $entrypoint Plugin absolute or basename path.
	 * @param bool   $network_wide Whether activation targets the whole network.
	 * @return string[] Conflicting plugin basenames.
	 */
	public static function conflicting_active_plugins( string $entrypoint, bool $network_wide = false ): array {
		$self   = plugin_basename( $entrypoint );
		$active = self::active_plugins_on_current_site();

		if ( $network_wide && is_multisite() ) {
			foreach ( get_sites(
				array(
					'fields' => 'ids',
					'number' => 0,
				)
			) as $site_id ) {
				switch_to_blog( (int) $site_id );
				try {
					$active = array_merge( $active, (array) get_option( 'active_plugins', array() ) );
				} finally {
					restore_current_blog();
				}
			}
		}

		$conflicts = array();
		foreach ( array_unique( $active ) as $candidate ) {
			if ( ! is_string( $candidate ) || $candidate === $self ) {
				continue;
			}
			if ( self::is_family_plugin( $candidate ) && self::plugin_file_exists( $candidate ) ) {
				$conflicts[] = $candidate;
			}
		}

		sort( $conflicts, SORT_STRING );
		return $conflicts;
	}

	/**
	 * Detect an installed distribution without assuming an approved slug.
	 *
	 * @param string $candidate Plugin basename.
	 * @return bool True for a known Piwigo Display package.
	 */
	private static function is_family_plugin( string $candidate ): bool {
		if ( 'wp-piwigo-display.php' === basename( $candidate ) ) {
			return true;
		}

		$installed = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$data      = $installed[ $candidate ] ?? array();
		$name      = trim( (string) ( $data['Name'] ?? '' ) );
		$domain    = (string) ( $data['TextDomain'] ?? '' );
		$uri       = (string) ( $data['PluginURI'] ?? '' );

		return 0 === stripos( $name, 'Piwigo Display' )
			|| 'wp-piwigo-display' === $domain
			|| 0 === strpos( $uri, 'https://github.com/fr4nck/WP-Piwigo-Display' );
	}

	/**
	 * Reject stale active_plugins entries whose files have been removed.
	 *
	 * @param string $candidate Plugin basename.
	 * @return bool Whether the main file is still present.
	 */
	private static function plugin_file_exists( string $candidate ): bool {
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			return is_file( rtrim( WP_PLUGIN_DIR, '/\\' ) . '/' . $candidate );
		}
		// Fallback for isolated tests without WordPress filesystem constants.
		return true;
	}

	/**
	 * Return locally and network-active plugin basenames.
	 *
	 * @return string[] Active plugin basenames.
	 */
	private static function active_plugins_on_current_site(): array {
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active = array_merge(
				$active,
				array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) )
			);
		}
		return array_values( array_filter( $active, 'is_string' ) );
	}

	/** Reject a conflicting activation without changing plugin state. */
	private static function block_activation(): void {
		wp_die(
			esc_html__( 'Désactivez d’abord l’autre copie de Piwigo Display. Ne la supprimez pas : sa désinstallation pourrait effacer les réglages partagés.', 'wp-piwigo-display' ),
			esc_html__( 'Activation de Piwigo Display bloquée', 'wp-piwigo-display' ),
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}
}
