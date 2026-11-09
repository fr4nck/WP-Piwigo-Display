<?php
/** Vérifie le bouton indépendant de la permission d'envoi de médias. */
declare(strict_types=1);
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function current_user_can( $cap ): bool { return in_array( $cap, $GLOBALS['caps'], true ); }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $value, $domain = '' ): string { return $value; }
final class WPD_Test_Screen {
    public function is_block_editor(): bool { return $GLOBALS['block_editor']; }
}
function get_current_screen(): object { return new WPD_Test_Screen(); }
require ABSPATH . 'includes/class-wpd-classic-editor.php';

$cases = array(
    array( 'caps' => array( 'edit_posts' ), 'block' => false, 'button' => true ),
    array( 'caps' => array( 'edit_posts' ), 'block' => true, 'button' => false ),
    array( 'caps' => array( 'edit_posts', 'upload_files' ), 'block' => false, 'button' => false ),
    array( 'caps' => array(), 'block' => false, 'button' => false ),
);
foreach ( $cases as $case ) {
    $GLOBALS['caps']         = $case['caps'];
    $GLOBALS['block_editor'] = $case['block'];
    ob_start();
    WPD_Classic_Editor::render_contributor_button( (object) array( 'ID' => 1 ) );
    $output = (string) ob_get_clean();
    $has_button = false !== strpos( $output, 'wpd-open-builder' );
    if ( $has_button !== $case['button'] ) {
        throw new RuntimeException( 'Bouton Classic Editor incorrect pour ' . implode( ',', $case['caps'] ) );
    }
}
echo "Classic Editor : contributeur, bloc, téléversement et refus PASS.\n";
