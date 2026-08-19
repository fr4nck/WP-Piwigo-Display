<?php

$bootstrap = file_get_contents( __DIR__ . '/../wp-piwigo-display.php' );
$sanitizer = file_get_contents( __DIR__ . '/../includes/class-wpd-svg-mask-sanitizer.php' );

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, $message . PHP_EOL );
		exit( 1 );
	}
};

$assert( false !== strpos( $bootstrap, 'class-wpd-svg-mask-sanitizer.php' ), 'Le sanitizer SVG doit être chargé par le plugin.' );
$assert( false !== strpos( $sanitizer, 'LIBXML_NONET' ), 'Le parseur XML doit interdire les accès réseau.' );
$assert( false !== strpos( $sanitizer, "'script'" ) || false === strpos( $sanitizer, "'script'," ), 'Les scripts ne doivent pas appartenir à la liste blanche.' );
$assert( false !== strpos( $sanitizer, "0 === stripos( \$attribute_name, 'on' )" ), 'Les gestionnaires d’événements on* doivent être refusés.' );
$assert( false !== strpos( $sanitizer, "'style' === strtolower( \$attribute_name )" ), 'Les attributs style doivent être refusés.' );
$assert( false !== strpos( $sanitizer, 'https?:|data:|javascript:|url\\s*\\(|@import' ), 'Les références actives ou distantes doivent être détectées.' );
$assert( false !== strpos( $sanitizer, '<!DOCTYPE|<!ENTITY|<\\?xml-stylesheet' ), 'DOCTYPE, ENTITY et feuilles de style XML doivent être refusés.' );
$assert( false !== strpos( $sanitizer, 'MAX_BYTES = 262144' ), 'La taille des SVG doit être bornée.' );
$assert( false !== strpos( $sanitizer, 'normalize_view_box' ), 'Le viewBox doit être normalisé.' );
$assert( false !== strpos( $sanitizer, "'path', 'circle', 'ellipse', 'rect', 'polygon', 'polyline'" ), 'La liste blanche doit rester limitée aux primitives géométriques utiles.' );

fwrite( STDOUT, "Strict custom SVG mask sanitizer invariants: OK\n" );
