<?php
/**
 * S-01 security regression on the exact stable 3.0.1 lineage.
 *
 * Run in a disposable real WordPress installation via WP-CLI only.
 * Uses synthetic Piwigo metadata and never requests a real Piwigo server.
 *
 * @package WP_Piwigo_Display
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	fwrite( STDERR, "S01_STABLE=BLOCKED: disposable WP-CLI only\n" );
	exit( 2 );
}
if ( ! class_exists( 'WPD_Renderer' ) || ! class_exists( 'WPD_Masonry' ) ) {
	fwrite( STDERR, "S01_STABLE=BLOCKED: active Piwigo Display plugin required\n" );
	exit( 2 );
}

$probe_calls = 0;
add_shortcode(
	'wpd_s01_stable_probe',
	static function () use ( &$probe_calls ): string {
		++$probe_calls;
		return 'S01_STABLE_PROBE_EXECUTED';
	}
);
$title       = 'Photo [wpd_s01_stable_probe] & "originale"';
$description = 'Texte [wpd_s01_stable_probe] <b>imprimé</b>';
$image       = array(
	'id'          => 41902,
	'name'        => $title,
	'comment'     => $description,
	'file'        => 'stable-fixture.jpg',
	'element_url' => 'http://127.0.0.1:8090/synthetic-stable.jpg',
	'width'       => 640,
	'height'      => 480,
	'derivatives' => array(
		'medium' => array( 'url' => 'http://127.0.0.1:8090/synthetic-stable.jpg' ),
		'large'  => array( 'url' => 'http://127.0.0.1:8090/synthetic-stable.jpg' ),
	),
);

$base = array(
	'type'     => 'gallery',
	'layout'   => '',
	'caption'  => 'title-description',
	'lightbox' => 'false',
	'sort'     => 'manual',
	'order'    => 'asc',
	'fit'      => 'contain',
);
$cases = array(
	'gallery' => array(),
	'slider'  => array( 'type' => 'slider' ),
	'masonry' => array( 'layout' => 'masonry' ),
);
$failures = array();
foreach ( $cases as $layout => $override ) {
	$probe_calls = 0;
	$html        = WPD_Renderer::render( array( $image ), array_merge( $base, $override ) );
	$page        = apply_filters( 'the_content', "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->" );

	if ( 0 !== $probe_calls || str_contains( $page, 'S01_STABLE_PROBE_EXECUTED' ) ) {
		$failures[] = "$layout: remote metadata executed as a WordPress shortcode";
	}
	if ( str_contains( $html, '[wpd_s01_stable_probe]' ) || ! str_contains( $html, '&#91;wpd_s01_stable_probe&#93;' ) ) {
		$failures[] = "$layout: remote shortcode delimiters not neutralized";
	}
	if ( ! str_contains( html_entity_decode( $page, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), '[wpd_s01_stable_probe]' ) ) {
		$failures[] = "$layout: original readable metadata lost";
	}
	if ( ! str_contains( $html, 'alt=' ) || ! str_contains( $html, 'data-wpd-title=' ) ) {
		$failures[] = "$layout: image metadata attributes missing";
	}
}
$before = $probe_calls;
$normal = apply_filters( 'the_content', '[wpd_s01_stable_probe]' );
if ( $before + 1 !== $probe_calls || ! str_contains( $normal, 'S01_STABLE_PROBE_EXECUTED' ) ) {
	$failures[] = 'legitimate author-authored shortcode was disabled';
}
if ( $failures ) {
	fwrite( STDERR, 'S01_STABLE=FAIL ' . implode( ' | ', $failures ) . "\n" );
	exit( 1 );
}
echo "S01_STABLE=PASS real WordPress pipeline gallery/slider/masonry, alt/data/captions, normal shortcodes\n";
