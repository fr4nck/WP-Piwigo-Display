<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
return [
    'dependencies' => ['wp-blocks', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-data', 'wp-element', 'wp-i18n', 'wp-server-side-render'],
    'version' => is_file( __DIR__ . '/index.js' ) ? (string) filemtime( __DIR__ . '/index.js' ) : WPD_VERSION,
];
