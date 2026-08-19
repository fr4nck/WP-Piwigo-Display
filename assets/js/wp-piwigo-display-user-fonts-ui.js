( function () {
	'use strict';

	const fonts = Array.isArray( window.WPDUserFonts ) ? window.WPDUserFonts : [];
	if ( ! fonts.length ) {
		return;
	}

	function appendFontGroup( select, source, label ) {
		const matchingFonts = fonts.filter( ( font ) => font && font.source === source );
		if ( ! matchingFonts.length ) {
			return;
		}

		const group = document.createElement( 'optgroup' );
		group.label = label;
		matchingFonts.forEach( ( font ) => {
			if ( ! font.value || ! font.name || select.querySelector( 'option[value="' + font.value + '"]' ) ) {
				return;
			}
			const option = document.createElement( 'option' );
			option.value = font.value;
			option.textContent = font.name;
			if ( font.family ) {
				option.style.fontFamily = '"' + font.family + '", sans-serif';
			}
			group.appendChild( option );
		} );

		if ( group.children.length ) {
			select.appendChild( group );
		}
	}

	function appendFonts( select ) {
		if ( ! select || select.dataset.wpdUserFontsReady === '1' ) {
			return;
		}

		appendFontGroup( select, 'bundled', 'Polices incluses' );
		appendFontGroup( select, 'user', 'Polices locales' );
		select.dataset.wpdUserFontsReady = '1';
	}

	function wire() {
		document.querySelectorAll( '[data-wpd="photo_text_font"], #wpd-c-photo-text-font' ).forEach( appendFonts );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', wire, { once: true } );
	} else {
		wire();
	}

	const observer = new MutationObserver( wire );
	observer.observe( document.documentElement, { childList: true, subtree: true } );
}() );
