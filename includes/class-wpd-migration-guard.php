<?php
/**
 * Prevent two distributions of Piwigo Display from running together.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cross-directory activation guard for the GitHub/WordPress.org transition.
 */
final class WPD_Migration_Guard {

	/** Legacy distribution wins if incompatible copies are forcibly enabled. */
	private const LEGACY_PLUGIN = 'wp-piwigo-display/wp-piwigo-display.php';

	/**
	 * Registers activation refusal and an admin-only repair for corrupted activation lists.
	 *
	 * @param string $entrypoint Absolute path to the registering copy's entrypoint.
	 */
	public static function register( string $entrypoint ): void {
		register_activation_hook(
			$entrypoint,
			static function ( bool $network_wide = false ) use ( $entrypoint ): void {
				if ( array() !== self::conflicting_active_plugins( $entrypoint, $network_wide ) ) {
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
		);

		add_action(
			'admin_init',
			static function () use ( $entrypoint ): void {
				$self   = plugin_basename( $entrypoint );
				$active = self::active_plugins_on_current_site();
				if ( ! in_array( $self, $active, true ) ) {
					return;
				}

				$conflicts = self::conflicting_active_plugins( $entrypoint );
				if ( array() === $conflicts || self::preferred_plugin( array_merge( array( $self ), $conflicts ) ) === $self ) {
					return;
				}

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					$file = ABSPATH . 'wp-admin/includes/plugin.php';
					if ( ! is_readable( $file ) ) {
						return;
					}
					require_once $file;
				}

				// Fail safe: preserve the legacy plugin and never uninstall either copy.
				deactivate_plugins( $self, true, null );
				add_action(
					'admin_notices',
					static function (): void {
						echo '<div class="notice notice-warning"><p>' . esc_html__( 'Piwigo Display : une seconde copie active a été détectée. La nouvelle copie a été désactivée ; aucune donnée n’a été supprimée.', 'wp-piwigo-display' ) . '</p></div>';
					}
				);
			},
			1
		);
	}

	/**
	 * Finds competing, already active Piwigo Display entrypoints.
	 *
	 * On multisite network activation, check every site, not only the current blog.
	 *
	 * @param string $entrypoint Absolute path to this plugin's main file.
	 * @param bool   $network_wide Whether this is a network-wide activation attempt.
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

		$installed = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$conflicts = array();
		foreach ( array_unique( $active ) as $candidate ) {
			if ( ! is_string( $candidate ) || $candidate === $self ) {
				continue;
			}
			$matching_filename = 'wp-piwigo-display.php' === basename( $candidate );
			$matching_name     = isset( $installed[ $candidate ]['Name'] )
				&& 'Piwigo Display' === trim( (string) $installed[ $candidate ]['Name'] );
			if ( $matching_filename || $matching_name ) {
				$conflicts[] = $candidate;
			}
		}

		sort( $conflicts, SORT_STRING );
		return $conflicts;
	}

	/**
	 * Returns site and network activation lists without loading admin-only APIs.
	 *
	 * @return string[] Plugin basenames.
	 */
	private static function active_plugins_on_current_site(): array {
		$active = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network = (array) get_site_option( 'active_sitewide_plugins', array() );
			$active  = array_merge( $active, array_keys( $network ) );
		}

		return array_values( array_filter( $active, 'is_string' ) );
	}

	/**
	 * When lists were corrupted outside the activation UI, keep the legacy copy.
	 *
	 * @param string[] $plugins Active Piwigo Display basenames.
	 * @return string Preferred active plugin basename.
	 */
	private static function preferred_plugin( array $plugins ): string {
		if ( in_array( self::LEGACY_PLUGIN, $plugins, true ) ) {
			return self::LEGACY_PLUGIN;
		}
		sort( $plugins, SORT_STRING );
		return $plugins[0];
	}
}
