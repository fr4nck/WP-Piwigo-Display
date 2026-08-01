<?php
/**
 * Security regression checks for privileged entry points and remote requests.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$checks = array(
    'includes/class-wpd-plugin.php' => array(
        'admin_post_wpd_clear_cache',
        'admin_post_wpd_test_connection',
        'admin_post_wpd_export_diagnostic',
        "check_ajax_referer( 'wpd_get_albums', 'nonce' )",
        "current_user_can( 'manage_options' )",
        "check_admin_referer( 'wpd_clear_cache' )",
        "check_admin_referer( 'wpd_test_connection' )",
        'wp_safe_redirect(',
    ),
    'includes/class-wpd-service-account.php' => array(
        'admin_post_wpd_test_service_account',
        "check_ajax_referer( 'wpd_get_albums', 'nonce' )",
        "current_user_can( 'manage_options' )",
        "check_admin_referer( 'wpd_test_service_account' )",
        'wp_safe_redirect(',
    ),
    'includes/class-wpd-diagnostic.php' => array(
        "current_user_can( 'manage_options' )",
        "check_admin_referer( 'wpd_export_diagnostic' )",
        'sanitize_file_name(',
        'nocache_headers(',
        'wp_safe_remote_get(',
        "'timeout'     => 10",
        "'redirection' => 3",
    ),
    'includes/class-wpd-api.php' => array(
        'esc_url_raw(',
        'wp_http_validate_url(',
        'wp_safe_remote_post(',
        "array( 'http', 'https' )",
        "'timeout'     => 10",
        "'redirection' => 3",
    ),
    'includes/class-wpd-service-api.php' => array(
        'wp_safe_remote_post(',
        "'https' !== wp_parse_url( $url, PHP_URL_SCHEME )",
        "'timeout'     => 10",
        "'redirection' => 0",
        "'sslverify'   => true",
    ),
);

$forbidden = array(
    'includes/class-wpd-api.php'         => array('wp_remote_post('),
    'includes/class-wpd-service-api.php' => array('wp_remote_post('),
    'includes/class-wpd-diagnostic.php'  => array('wp_remote_get('),
);

$failures = array();

foreach ($checks as $relativePath => $needles) {
    $path = $root . '/' . $relativePath;
    $contents = file_get_contents($path);

    if (false === $contents) {
        $failures[] = sprintf('Unreadable file: %s', $relativePath);
        continue;
    }

    foreach ($needles as $needle) {
        if (false === strpos($contents, $needle)) {
            $failures[] = sprintf('Missing security invariant in %s: %s', $relativePath, $needle);
        }
    }
}

foreach ($forbidden as $relativePath => $needles) {
    $contents = file_get_contents($root . '/' . $relativePath);
    if (false === $contents) {
        continue;
    }

    foreach ($needles as $needle) {
        $unsafeNeedle = str_replace('wp_remote_', 'wp_remote_', $needle);
        $safeNeedle = str_replace('wp_remote_', 'wp_safe_remote_', $needle);
        $withoutSafeCalls = str_replace($safeNeedle, '', $contents);
        if (false !== strpos($withoutSafeCalls, $unsafeNeedle)) {
            $failures[] = sprintf('Unsafe HTTP call in %s: %s', $relativePath, $needle);
        }
    }
}

if (array() !== $failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Security entry-point and HTTP checks passed.\n";
