<?php
/**
 * Security regression checks for input, output, SQL, HTTP, and file flows.
 *
 * @package WP_Piwigo_Display
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$files = array_merge(
    glob($root . '/*.php') ?: array(),
    glob($root . '/includes/*.php') ?: array()
);

$failures = array();
$forbidden = array(
    '$_REQUEST' => 'Ambiguous request superglobal',
    '$_FILES' => 'Unexpected upload surface',
    '$_COOKIE' => 'Unexpected raw cookie access',
    'wp_remote_get(' => 'Unsafe remote GET; use wp_safe_remote_get()',
    'wp_remote_post(' => 'Unsafe remote POST; use wp_safe_remote_post()',
    'file_put_contents(' => 'Direct filesystem write',
    'fwrite(' => 'Direct filesystem write',
    'unlink(' => 'Direct filesystem deletion',
    'eval(' => 'Dynamic code execution',
    'shell_exec(' => 'Shell execution',
    'exec(' => 'Shell execution',
    'system(' => 'Shell execution',
    'passthru(' => 'Shell execution',
);

$allowed_upload_surfaces = array(
    'includes/class-wpd-custom-svg-masks.php',
);

foreach ($files as $path) {
    $relative = ltrim(str_replace($root, '', $path), '/');
    $contents = file_get_contents($path);

    if (false === $contents) {
        $failures[] = sprintf('Unreadable file: %s', $relative);
        continue;
    }

    foreach ($forbidden as $needle => $reason) {
        if ('$_FILES' === $needle && in_array($relative, $allowed_upload_surfaces, true)) {
            continue;
        }
        if (false !== strpos($contents, $needle)) {
            $failures[] = sprintf('%s in %s: %s', $reason, $relative, $needle);
        }
    }

    if (preg_match('/echo\s+\$_(?:GET|POST|SERVER)/', $contents)) {
        $failures[] = sprintf('Raw superglobal output in %s', $relative);
    }

    if (preg_match('/\$wpdb->(?:query|get_var|get_row|get_results)\s*\(\s*["\'][^"\']*\$/s', $contents)) {
        $failures[] = sprintf('Potential interpolated SQL in %s', $relative);
    }
}

$required = array(
    'includes/class-wpd-settings.php' => array(
        'wp_unslash( $_GET',
        'sanitize_key(',
        'absint(',
        'esc_url_raw(',
    ),
    'includes/class-wpd-service-account.php' => array(
        'wp_unslash( $_GET',
        'sanitize_key(',
        'sanitize_text_field(',
        'wp_verify_nonce(',
    ),
    'includes/class-wpd-diagnostic.php' => array(
        '$wpdb->prepare(',
        '$wpdb->esc_like(',
        'sanitize_file_name(',
        'wp_safe_remote_get(',
    ),
    'includes/class-wpd-api.php' => array(
        'wp_safe_remote_post(',
        'wp_http_validate_url(',
        'sanitize_text_field(',
    ),
    'includes/class-wpd-service-api.php' => array(
        'wp_safe_remote_post(',
        "'sslverify'   => true",
        'sanitize_text_field(',
    ),
    'includes/class-wpd-custom-svg-masks.php' => array(
        "current_user_can( 'manage_options' )",
        "check_admin_referer( 'wpd_upload_svg_mask' )",
        'is_uploaded_file( $tmp_name )',
        "WPD_SVG_Mask_Sanitizer::sanitize( $raw )",
        "check_admin_referer( 'wpd_delete_svg_mask' )",
    ),
);

foreach ($required as $relative => $needles) {
    $contents = file_get_contents($root . '/' . $relative);
    if (false === $contents) {
        $failures[] = sprintf('Unreadable file: %s', $relative);
        continue;
    }

    foreach ($needles as $needle) {
        if (false === strpos($contents, $needle)) {
            $failures[] = sprintf('Missing data-flow invariant in %s: %s', $relative, $needle);
        }
    }
}

if (array() !== $failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "Security data-flow checks passed.\n";
