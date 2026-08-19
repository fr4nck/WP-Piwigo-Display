( function () {
	'use strict';

	const root = document.getElementById( 'wpd-admin-composer' );
	const type = document.getElementById( 'wpd-c-type' );
	const output = document.getElementById( 'wpd-c-output' );

	if ( ! root || ! type || ! output ) {
		return;
	}

	[ [ 'masonry', 'Masonry' ], [ 'justified', 'Galerie justifiée' ], [ 'collage', 'Collage / Pêle-mêle' ], [ 'photo-text', 'Texte rempli de photos' ] ].forEach( ( item ) => {
		if ( ! type.querySelector( 'option[value="' + item[ 0 ] + '"]' ) ) {
			const option = document.createElement( 'option' );
			option.value = item[ 0 ];
			option.textContent = item[ 1 ];
			type.appendChild( option );
		}
	} );

	const outputRow = output.closest( 'tr' );

	if ( ! document.getElementById( 'wpd-c-photo-text' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-photo-text';
		row.innerHTML = '<th>Texte rempli de photos</th><td><label>Texte <input id="wpd-c-photo-text" type="text" value="PÊLE-MÊLE"></label> <label>Graine <input id="wpd-c-photo-text-seed" class="small-text" type="text" value="0"></label> <label>Police <select id="wpd-c-photo-text-font"><option value="inherit">Police du thème</option><option value="system">Système</option><option value="serif">Serif</option><option value="mono">Monospace</option></select></label> <label>Graisse <input id="wpd-c-photo-text-weight" class="small-text" type="number" min="100" max="900" step="100" value="800"></label> <label>Photos max <input id="wpd-c-photo-text-max-images" class="small-text" type="number" min="1" max="40" value="20"></label> <label><input id="wpd-c-photo-text-outline" type="checkbox" checked> Contour</label> <label>Épaisseur <input id="wpd-c-photo-text-outline-width" class="small-text" type="number" min="0" max="12" value="3"></label> <label>Couleur contour <input id="wpd-c-photo-text-outline-color" type="text" value="#ffffff"></label> <label>Fond <input id="wpd-c-photo-text-background" type="text" value="transparent"></label></td>';
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	const escapeValue = ( value ) => String( value ).replace( /\\/g, '\\\\' ).replace( /"/g, '\\"' );
	const removeAttribute = ( shortcode, key ) => shortcode.replace( new RegExp( '\\s+' + key + '="(?:\\\\.|[^"])*"', 'g' ), '' );
	const appendAttribute = ( shortcode, key, value ) => shortcode.replace( /\]$/, ' ' + key + '="' + escapeValue( value ) + '"]' );
	const clamp = ( value, min, max, fallback ) => {
		const parsed = parseInt( value, 10 );
		return Number.isFinite( parsed ) ? Math.min( max, Math.max( min, parsed ) ) : fallback;
	};

	function syncParity() {
		document.querySelectorAll( '.wpd-c-photo-text' ).forEach( ( row ) => {
			row.style.display = type.value === 'photo-text' ? 'table-row' : 'none';
		} );

		let shortcode = output.value;
		[ 'photo_text', 'photo_text_seed', 'photo_text_font', 'photo_text_weight', 'photo_text_outline', 'photo_text_outline_width', 'photo_text_outline_color', 'photo_text_background', 'photo_text_max_images' ].forEach( ( key ) => {
			shortcode = removeAttribute( shortcode, key );
		} );

		if ( type.value === 'photo-text' ) {
			shortcode = appendAttribute( shortcode, 'photo_text', document.getElementById( 'wpd-c-photo-text' ).value || 'PÊLE-MÊLE' );
			shortcode = appendAttribute( shortcode, 'photo_text_seed', document.getElementById( 'wpd-c-photo-text-seed' ).value || '0' );
			shortcode = appendAttribute( shortcode, 'photo_text_font', document.getElementById( 'wpd-c-photo-text-font' ).value );
			shortcode = appendAttribute( shortcode, 'photo_text_weight', clamp( document.getElementById( 'wpd-c-photo-text-weight' ).value, 100, 900, 800 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_max_images', clamp( document.getElementById( 'wpd-c-photo-text-max-images' ).value, 1, 40, 20 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_outline', document.getElementById( 'wpd-c-photo-text-outline' ).checked ? 'true' : 'false' );
			shortcode = appendAttribute( shortcode, 'photo_text_outline_width', clamp( document.getElementById( 'wpd-c-photo-text-outline-width' ).value, 0, 12, 3 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_outline_color', document.getElementById( 'wpd-c-photo-text-outline-color' ).value || '#ffffff' );
			shortcode = appendAttribute( shortcode, 'photo_text_background', document.getElementById( 'wpd-c-photo-text-background' ).value || 'transparent' );
		}

		output.value = shortcode;
	}

	root.addEventListener( 'input', () => window.setTimeout( syncParity, 0 ) );
	root.addEventListener( 'change', () => window.setTimeout( syncParity, 0 ) );
	window.setTimeout( syncParity, 0 );
}() );