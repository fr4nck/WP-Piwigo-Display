<?php
/**
 * S-01: real WordPress the_content / block / shortcode pipeline regression.
 *
 * This is a CI-only WP-CLI fixture. No private Piwigo or production host is
 * accessed. The supplied image records and shortcode probe are synthetic.
 *
 * @package WP_Piwigo_Display
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
    fwrite( STDERR, "S01=BLOCKED: disposable WP-CLI only\n" );
    exit( 2 );
}

$test_calls = 0;
add_shortcode(
    'wpd_caption_probe',
    static function () use ( &$test_calls ): string {
        ++$test_calls;
        return 'WPD_PROBE_EXECUTED';
    }
);

$hostile_title = 'Album [wpd_caption_probe] & "photo"';
$hostile_description = 'Description [wpd_caption_probe] <script>no</script> & fin';
$image = array(
    'id'          => 7781,
    'name'        => $hostile_title,
    'comment'     => $hostile_description,
    'file'        => 'synthetic.jpg',
    'element_url' => 'http://127.0.0.1:8090/ci-synthetic.jpg',
    'width'       => 640,
    'height'      => 480,
    'derivatives' => array(
        'medium' => array( 'url' => 'http://127.0.0.1:8090/ci-synthetic.jpg' ),
        'large'  => array( 'url' => 'http://127.0.0.1:8090/ci-synthetic.jpg' ),
    ),
);
$base = array(
    'caption'      => 'title-description',
    'click_action' => 'default',
    'lightbox'     => 'false',
    'type'         => 'gallery',
    'layout'       => '',
    'sort'         => 'manual',
    'order'        => 'asc',
    'fit'          => 'contain',
);

$cases = array(
    'gallery'   => array(),
    'slider'    => array( 'type' => 'slider' ),
    'masonry'   => array( 'layout' => 'masonry' ),
    'justified' => array( 'layout' => 'justified' ),
    'collage'   => array( 'layout' => 'collage' ),
);
$failures = array();

foreach ( $cases as $layout => $overrides ) {
    $test_calls = 0;
    $markup = WPD_Renderer::render( array( $image ), array_merge( $base, $overrides ) );

    // A real WordPress HTML block mimics where the dynamic Piwigo block's
    // escaped HTML enters core the_content at do_blocks (priority 9).
    // Core do_shortcode at priority 11 must not interpret remote metadata.
    $page = apply_filters( 'the_content', "<!-- wp:html -->\n" . $markup . "\n<!-- /wp:html -->" );

    if ( 0 !== $test_calls || str_contains( $page, 'WPD_PROBE_EXECUTED' ) ) {
        $failures[] = "$layout: externally supplied caption was executed ($test_calls)";
    }
    if ( ! str_contains( $markup, '&#91;wpd_caption_probe&#93;' ) ) {
        $failures[] = "$layout: escaped shortcode delimiters not found";
    }
    if ( str_contains( $markup, '[wpd_caption_probe]' ) ) {
        $failures[] = "$layout: raw shortcode delimiters remain in markup";
    }
    if ( ! str_contains( html_entity_decode( $page, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), '[wpd_caption_probe]' ) ) {
        $failures[] = "$layout: the human-readable caption was lost";
    }
    if ( ! str_contains( $page, 'alt=' ) || ! str_contains( $page, 'data-wpd-title=' ) ) {
        $failures[] = "$layout: expected metadata attributes are missing";
    }
}

// Guard against an overbroad fix that disables legitimate author-owned shortcodes.
$before = $test_calls;
$legitimate = apply_filters( 'the_content', '[wpd_caption_probe]' );
if ( $test_calls !== $before + 1 || ! str_contains( $legitimate, 'WPD_PROBE_EXECUTED' ) ) {
    $failures[] = 'ordinary author-authored shortcode was disabled';
}

if ( $failures ) {
    fwrite( STDERR, 'S01=FAIL ' . implode( ' | ', $failures ) . "\n" );
    exit( 1 );
}

echo "S01=PASS real WordPress pipeline, five layouts, alt/data/captions and normal shortcodes\n";
