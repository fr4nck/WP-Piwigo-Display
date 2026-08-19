<?php
/**
 * Bundled free font library for photo-filled text.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exposes the small local font set shipped with the plugin.
 */
final class WPD_Bundled_Fonts {
	/**
	 * Bundled font catalog.
	 *
	 * @var array<string,array{name:string,family:string,path:string,format:string,weight:int}>
	 */
	private const FONTS = array(
		'bebas-neue' => array(
			'name'   => 'Bebas Neue',
			'family' => 'wpd-bundled-bebas-neue',
			'path'   => 'assets/fonts/bebas-neue/BebasNeue-Regular.woff2',
			'format' => 'woff2',
			'weight' => 400,
		),
		'bungee'     => array(
			'name'   => 'Bungee',
			'family' => 'wpd-bundled-bungee',
			'path'   => 'assets/fonts/bungee/Bungee-Regular.woff2',
			'format' => 'woff2',
			'weight' => 400,
		),
	);

	/**
	 * Already-enqueued bundled font identifiers.
	 *
	 * @var array<string,bool>
	 */
	private static array $enqueued = array();

	/** Registers bundled-font hooks. */
	public static function register(): void {
		add_filter( 'wp_piwigo_display_render', array( self::class, 'apply_photo_text_font' ), 11, 4 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_assets' ), 32 );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'localize_block_editor_fonts' ), 32 );
	}

	/**
	 * Returns editor-safe bundled font choices.
	 *
	 * @return array<int,array{id:string,name:string,value:string,family:string,source:string}>
	 */
	public static function ui_fonts(): array {
		$fonts = array();
		foreach ( self::FONTS as $id => $font ) {
			$fonts[] = array(
				'id'     => $id,
				'name'   => $font['name'],
				'value'  => 'bundled-' . $id,
				'family' => $font['family'],
				'source' => 'bundled',
			);
		}
		return $fonts;
	}

	/**
	 * Applies a bundled font to photo-text output after the base renderer.
	 *
	 * Unknown font identifiers are left untouched. The base photo-text renderer
	 * deliberately falls back to inherit for unknown modes, which gives this
	 * post-render step a narrow and deterministic replacement target.
	 *
	 * @param string|null         $html   Existing renderer output.
	 * @param array<int,mixed>    $images Piwigo images, unused.
	 * @param array<string,mixed> $atts   Normalized shortcode attributes.
	 * @param string              $type   Requested type, unused.
	 * @return string|null
	 */
	public static function apply_photo_text_font( ?string $html, array $images, array $atts, string $type ): ?string {
		unset( $images, $type );

		if ( null === $html || ( $atts['layout'] ?? '' ) !== 'photo-text' ) {
			return $html;
		}

		$requested = sanitize_key( (string) ( $atts['photo_text_font'] ?? '' ) );
		if ( ! str_starts_with( $requested, 'bundled-' ) ) {
			return $html;
		}

		$id   = sanitize_key( substr( $requested, 8 ) );
		$font = self::font( $id );
		if ( null === $font ) {
			return $html;
		}

		self::enqueue_font( $id );
		return str_replace( 'font-family:inherit;', 'font-family:' . $font['family'] . ';', $html );
	}

	/**
	 * Enqueues bundled fonts and editor choices on Classic/composer screens.
	 *
	 * @param string $hook Current administration screen hook.
	 * @return void
	 */
	public static function enqueue_admin_assets( string $hook ): void {
		$is_classic  = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_composer = str_contains( $hook, 'wp-piwigo-display-compose' );
		$is_library  = str_contains( $hook, 'wp-piwigo-display-fonts' );

		if ( $is_library ) {
			self::enqueue_all_fonts();
		}

		if ( ! $is_classic && ! $is_composer ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::enqueue_all_fonts();
		wp_localize_script( 'wpd-user-fonts-ui', 'WPDUserFonts', self::editor_fonts() );
	}

	/** Exposes bundled and imported fonts to Gutenberg controls. */
	public static function localize_block_editor_fonts(): void {
		self::enqueue_all_fonts();
		wp_localize_script( 'wpd-block-masonry-controls', 'WPDUserFonts', self::editor_fonts() );
	}

	/**
	 * Returns one normalized bundled font.
	 *
	 * @param string $id Font identifier.
	 * @return array{name:string,family:string,path:string,format:string,weight:int}|null
	 */
	private static function font( string $id ): ?array {
		$id   = sanitize_key( $id );
		$font = self::FONTS[ $id ] ?? null;
		return is_array( $font ) ? $font : null;
	}

	/**
	 * Returns the combined editor catalog without changing user-font storage.
	 *
	 * @return array<int,array<string,string>>
	 */
	private static function editor_fonts(): array {
		$user_fonts = array_map(
			static function ( array $font ): array {
				$font['source'] = 'user';
				return $font;
			},
			WPD_User_Fonts::ui_fonts()
		);
		return array_merge( self::ui_fonts(), $user_fonts );
	}

	/** Enqueues all bundled fonts for editor and library previews. */
	private static function enqueue_all_fonts(): void {
		foreach ( array_keys( self::FONTS ) as $id ) {
			self::enqueue_font( (string) $id );
		}
	}

	/**
	 * Enqueues one bundled local font-face rule.
	 *
	 * @param string $id Font identifier.
	 * @return void
	 */
	private static function enqueue_font( string $id ): void {
		$id = sanitize_key( $id );
		if ( isset( self::$enqueued[ $id ] ) ) {
			return;
		}

		$font = self::font( $id );
		if ( null === $font ) {
			return;
		}

		wp_enqueue_style( 'wpd-user-fonts', WPD_PLUGIN_URL . 'assets/css/wp-piwigo-display-user-fonts.css', array(), WPD_VERSION );
		$css = sprintf(
			'@font-face{font-family:%1$s;src:url("%2$s") format("%3$s");font-display:swap;font-style:normal;font-weight:%4$d;}',
			esc_attr( $font['family'] ),
			esc_url( WPD_PLUGIN_URL . $font['path'] ),
			esc_attr( $font['format'] ),
			$font['weight']
		);
		wp_add_inline_style( 'wpd-user-fonts', $css );
		self::$enqueued[ $id ] = true;
	}
}
