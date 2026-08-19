<?php
/**
 * Strict SVG sanitizer for user-defined photo masks.
 *
 * @package WP_Piwigo_Display
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes a deliberately small SVG subset suitable for photo masking.
 */
final class WPD_SVG_Mask_Sanitizer {
	/** Maximum accepted SVG payload size in bytes. */
	private const MAX_BYTES = 262144;

	/**
	 * Allowed SVG element names.
	 *
	 * @var string[]
	 */
	private const ALLOWED_ELEMENTS = array( 'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'polygon', 'polyline' );

	/**
	 * Allowed geometry and presentation attributes.
	 *
	 * @var string[]
	 */
	private const ALLOWED_ATTRIBUTES = array(
		'viewBox',
		'd',
		'cx',
		'cy',
		'r',
		'rx',
		'ry',
		'x',
		'y',
		'width',
		'height',
		'points',
		'transform',
		'fill',
		'fill-rule',
		'clip-rule',
	);

	/**
	 * Sanitizes an SVG document and returns canonical local-only markup.
	 *
	 * @param string $svg Raw SVG payload.
	 * @return string|WP_Error Sanitized SVG or validation error.
	 */
	public static function sanitize( string $svg ) {
		if ( '' === trim( $svg ) ) {
			return new WP_Error( 'wpd_svg_empty', __( 'Le fichier SVG est vide.', 'wp-piwigo-display' ) );
		}

		if ( strlen( $svg ) > self::MAX_BYTES ) {
			return new WP_Error( 'wpd_svg_too_large', __( 'Le masque SVG dépasse la taille maximale autorisée.', 'wp-piwigo-display' ) );
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			return new WP_Error( 'wpd_svg_dom_missing', __( 'Le serveur ne fournit pas le parseur XML requis pour sécuriser les SVG.', 'wp-piwigo-display' ) );
		}

		if ( preg_match( '/<!DOCTYPE|<!ENTITY|<\?xml-stylesheet/i', $svg ) ) {
			return new WP_Error( 'wpd_svg_active_xml', __( 'Le SVG contient des constructions XML interdites.', 'wp-piwigo-display' ) );
		}

		$document                     = new DOMDocument();
		$previous_use_internal_errors = libxml_use_internal_errors( true );
		$loaded                       = $document->loadXML( $svg, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_use_internal_errors );

		// DOM extension property names are defined by PHP and cannot be converted to snake_case.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( ! $loaded || ! $document->documentElement ) {
			return new WP_Error( 'wpd_svg_invalid_xml', __( 'Le fichier n’est pas un SVG XML valide.', 'wp-piwigo-display' ) );
		}
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$root = $document->documentElement;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( 'svg' !== strtolower( $root->localName ) ) {
			return new WP_Error( 'wpd_svg_invalid_xml', __( 'Le fichier n’est pas un SVG XML valide.', 'wp-piwigo-display' ) );
		}

		if ( ! self::sanitize_element( $root ) ) {
			return new WP_Error( 'wpd_svg_forbidden_content', __( 'Le SVG contient un élément ou un attribut non autorisé.', 'wp-piwigo-display' ) );
		}

		$view_box = self::normalize_view_box( $root );
		if ( is_wp_error( $view_box ) ) {
			return $view_box;
		}

		while ( $root->attributes && $root->attributes->length > 0 ) {
			$root->removeAttributeNode( $root->attributes->item( 0 ) );
		}
		$root->setAttribute( 'viewBox', $view_box );
		$root->setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );

		return trim( (string) $document->saveXML( $root ) );
	}

	/**
	 * Recursively validates and strips one SVG element.
	 *
	 * @param DOMElement $element Element to sanitize.
	 * @return bool Whether the subtree is accepted.
	 */
	private static function sanitize_element( DOMElement $element ): bool {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$name = strtolower( $element->localName );
		if ( ! in_array( $name, self::ALLOWED_ELEMENTS, true ) ) {
			return false;
		}

		$attributes_to_remove = array();
		foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
			$attribute_name  = $attribute->name;
			$attribute_value = trim( $attribute->value );

			if ( 0 === stripos( $attribute_name, 'on' ) || 'style' === strtolower( $attribute_name ) ) {
				return false;
			}

			if ( ! in_array( $attribute_name, self::ALLOWED_ATTRIBUTES, true ) || self::contains_external_reference( $attribute_value ) ) {
				$attributes_to_remove[] = $attribute_name;
			}
		}

		foreach ( $attributes_to_remove as $attribute_name ) {
			$element->removeAttribute( $attribute_name );
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		foreach ( iterator_to_array( $element->childNodes ) as $child ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( XML_TEXT_NODE === $child->nodeType ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( '' !== trim( (string) $child->nodeValue ) ) {
					return false;
				}
				$element->removeChild( $child );
				continue;
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( XML_ELEMENT_NODE !== $child->nodeType || ! self::sanitize_element( $child ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks whether an attribute may reference active or external content.
	 *
	 * @param string $value Attribute value.
	 * @return bool Whether the value contains an external or active reference.
	 */
	private static function contains_external_reference( string $value ): bool {
		return (bool) preg_match( '/(?:https?:|data:|javascript:|url\s*\(|@import|\\x00)/i', $value );
	}

	/**
	 * Resolves and normalizes the root viewBox.
	 *
	 * @param DOMElement $root SVG root element.
	 * @return string|WP_Error
	 */
	private static function normalize_view_box( DOMElement $root ) {
		$view_box = trim( $root->getAttribute( 'viewBox' ) );
		if ( '' === $view_box ) {
			$width  = self::numeric_dimension( $root->getAttribute( 'width' ) );
			$height = self::numeric_dimension( $root->getAttribute( 'height' ) );
			if ( null === $width || null === $height || $width <= 0 || $height <= 0 ) {
				return new WP_Error( 'wpd_svg_viewbox_missing', __( 'Le SVG doit fournir un viewBox ou des dimensions numériques exploitables.', 'wp-piwigo-display' ) );
			}
			return '0 0 ' . self::format_number( $width ) . ' ' . self::format_number( $height );
		}

		$parts = preg_split( '/[\s,]+/', $view_box );
		if ( 4 !== count( $parts ) ) {
			return new WP_Error( 'wpd_svg_viewbox_invalid', __( 'Le viewBox du SVG est invalide.', 'wp-piwigo-display' ) );
		}

		$numbers = array_map( 'floatval', $parts );
		if ( $numbers[2] <= 0 || $numbers[3] <= 0 ) {
			return new WP_Error( 'wpd_svg_viewbox_invalid', __( 'Le viewBox du SVG doit avoir une largeur et une hauteur positives.', 'wp-piwigo-display' ) );
		}

		return implode( ' ', array_map( array( self::class, 'format_number' ), $numbers ) );
	}

	/**
	 * Parses a positive numeric width or height without units.
	 *
	 * @param string $value Dimension value.
	 * @return float|null Parsed dimension.
	 */
	private static function numeric_dimension( string $value ): ?float {
		$value = trim( $value );
		if ( '' === $value || ! preg_match( '/^\d+(?:\.\d+)?$/', $value ) ) {
			return null;
		}
		return (float) $value;
	}

	/**
	 * Formats normalized numeric SVG values.
	 *
	 * @param float $number Number to format.
	 * @return string Normalized number.
	 */
	private static function format_number( float $number ): string {
		$formatted = rtrim( rtrim( number_format( $number, 4, '.', '' ), '0' ), '.' );
		return '-0' === $formatted ? '0' : $formatted;
	}
}
