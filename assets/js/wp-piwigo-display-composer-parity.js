( function () {
	'use strict';

	const root = document.getElementById( 'wpd-admin-composer' );
	const type = document.getElementById( 'wpd-c-type' );
	const output = document.getElementById( 'wpd-c-output' );

	if ( ! root || ! type || ! output ) {
		return;
	}

	[ [ 'masonry', 'Masonry' ], [ 'justified', 'Galerie justifiée' ] ].forEach( ( item ) => {
		if ( ! type.querySelector( 'option[value="' + item[ 0 ] + '"]' ) ) {
			const option = document.createElement( 'option' );
			option.value = item[ 0 ];
			option.textContent = item[ 1 ];
			type.appendChild( option );
		}
	} );

	const sliderRow = root.querySelector( '.wpd-c-slider' );
	if ( sliderRow && ! document.getElementById( 'wpd-c-transition' ) ) {
		const transition = document.createElement( 'label' );
		transition.innerHTML = ' Effet <select id="wpd-c-transition"><option value="slide">Glissement</option><option value="fade">Fondu</option><option value="none">Sans animation</option></select>';
		sliderRow.querySelector( 'td' ).appendChild( transition );

		const direction = document.createElement( 'label' );
		direction.innerHTML = ' Direction <select id="wpd-c-direction"><option value="ltr">Vers la gauche</option><option value="rtl">Vers la droite</option></select>';
		sliderRow.querySelector( 'td' ).appendChild( direction );
	}

	if ( ! document.getElementById( 'wpd-c-shape' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-shape';
		row.innerHTML = '<th>Forme</th><td><label>Forme <select id="wpd-c-shape"><option value="rectangle">Rectangle</option><option value="rounded">Rectangle arrondi</option><option value="circle">Cercle</option><option value="oval">Ovale</option><option value="pill">Pilule</option><option value="star">Étoile</option><option value="hexagon">Hexagone</option><option value="diamond">Losange</option></select></label> <label id="wpd-c-radius-wrap">Arrondi <input id="wpd-c-radius" class="small-text" type="number" min="0" max="50" value="8"> %</label></td>';
		const outputRow = output.closest( 'tr' );
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	if ( ! document.getElementById( 'wpd-c-masonry-columns' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-masonry';
		row.innerHTML = '<th>Masonry</th><td><label>Colonnes <input id="wpd-c-masonry-columns" class="small-text" type="number" min="2" max="6" value="4"></label> <label>Espacement <input id="wpd-c-masonry-gap" class="small-text" type="number" min="0" max="64" value="16"> px</label></td>';
		const outputRow = output.closest( 'tr' );
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	if ( ! document.getElementById( 'wpd-c-justified-row-height' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-justified';
		row.innerHTML = '<th>Galerie justifiée</th><td><label>Hauteur cible <input id="wpd-c-justified-row-height" class="small-text" type="number" min="100" max="600" value="220"> px</label> <label>Espacement <input id="wpd-c-justified-gap" class="small-text" type="number" min="0" max="64" value="8"> px</label></td>';
		const outputRow = output.closest( 'tr' );
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
		document.querySelectorAll( '.wpd-c-masonry' ).forEach( ( row ) => {
			row.style.display = type.value === 'masonry' ? 'table-row' : 'none';
		} );
		document.querySelectorAll( '.wpd-c-justified' ).forEach( ( row ) => {
			row.style.display = type.value === 'justified' ? 'table-row' : 'none';
		} );

		const shape = document.getElementById( 'wpd-c-shape' ).value;
		document.getElementById( 'wpd-c-radius-wrap' ).style.display = shape === 'rounded' ? 'inline' : 'none';

		let shortcode = output.value;
		[ 'transition', 'direction', 'masonry_columns', 'masonry_gap', 'justified_row_height', 'justified_gap', 'shape', 'radius' ].forEach( ( key ) => {
			shortcode = removeAttribute( shortcode, key );
		} );

		shortcode = appendAttribute( shortcode, 'shape', shape );
		if ( shape === 'rounded' ) {
			shortcode = appendAttribute( shortcode, 'radius', clamp( document.getElementById( 'wpd-c-radius' ).value, 0, 50, 8 ) );
		}

		if ( type.value === 'slider' ) {
			shortcode = appendAttribute( shortcode, 'transition', document.getElementById( 'wpd-c-transition' ).value );
			shortcode = appendAttribute( shortcode, 'direction', document.getElementById( 'wpd-c-direction' ).value );
		}

		if ( type.value === 'masonry' ) {
			shortcode = appendAttribute( shortcode, 'masonry_columns', clamp( document.getElementById( 'wpd-c-masonry-columns' ).value, 2, 6, 4 ) );
			shortcode = appendAttribute( shortcode, 'masonry_gap', clamp( document.getElementById( 'wpd-c-masonry-gap' ).value, 0, 64, 16 ) );
		}

		if ( type.value === 'justified' ) {
			shortcode = appendAttribute( shortcode, 'justified_row_height', clamp( document.getElementById( 'wpd-c-justified-row-height' ).value, 100, 600, 220 ) );
			shortcode = appendAttribute( shortcode, 'justified_gap', clamp( document.getElementById( 'wpd-c-justified-gap' ).value, 0, 64, 8 ) );
		}

		output.value = shortcode;
	}

	root.addEventListener( 'input', () => window.setTimeout( syncParity, 0 ) );
	root.addEventListener( 'change', () => window.setTimeout( syncParity, 0 ) );
	window.setTimeout( syncParity, 0 );
}() );
