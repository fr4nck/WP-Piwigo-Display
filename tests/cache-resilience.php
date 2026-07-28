<?php

$cache = file_get_contents(__DIR__ . '/../includes/class-wpd-cache.php');

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
};

$assert(strpos($cache, 'get_stale_key') !== false, 'Une clé de secours doit être générée.');
$assert(strpos($cache, 'max(DAY_IN_SECONDS, $duration * 7)') !== false, 'La copie de secours doit vivre plus longtemps que le cache principal.');
$assert(strpos($cache, 'acquire_lock') !== false, 'Un verrou anti-concurrence doit être acquis.');
$assert(strpos($cache, 'if ($lock_acquired)') !== false, 'Seul le propriétaire du verrou doit le libérer.');
$assert(strpos($cache, "'_transient_wpd_stale_'") !== false, 'La purge doit couvrir les copies de secours.');
$assert(strpos($cache, "'_transient_wpd_lock_'") !== false, 'La purge doit couvrir les verrous.');
$assert(strpos($cache, 'is_array($stale)') !== false, 'Une réponse périmée valide doit pouvoir servir de repli.');
