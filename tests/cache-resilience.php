<?php
/**
 * Static regression checks for cache resilience.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$cache   = file_get_contents( __DIR__ . '/../includes/class-wpd-cache.php' );
$compact = preg_replace( '/\s+/', '', (string) $cache );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( (string) $cache, 'get_stale_key' ), 'Une clé de secours doit être générée.' );
$assert( false !== strpos( (string) $compact, 'max(DAY_IN_SECONDS,$duration*7)' ), 'La copie de secours doit vivre plus longtemps que le cache principal.' );
$assert( false !== strpos( (string) $cache, 'acquire_lock' ), 'Un verrou anti-concurrence doit être acquis.' );
$assert( false !== strpos( (string) $compact, 'if($lock_acquired)' ), 'Seul le propriétaire du verrou doit le libérer.' );
$assert( false !== strpos( (string) $cache, "'_transient_wpd_stale_'" ), 'La purge doit couvrir les copies de secours.' );
$assert( false !== strpos( (string) $cache, "'_transient_wpd_lock_'" ), 'La purge doit couvrir les verrous.' );
$assert( false !== strpos( (string) $compact, 'is_array($stale)' ), 'Une réponse périmée valide doit pouvoir servir de repli.' );
$assert( substr_count( (string) $cache, "cache_hit( 'stale' )" ) >= 2, 'Les deux chemins de repli stale doivent être comptabilisés dans les métriques.' );

echo "Cache resilience checks passed.\n";
