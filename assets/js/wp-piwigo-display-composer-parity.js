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

	const sliderRow = root.querySelector( '.wpd-c-slider' );
	if ( sliderRow && ! document.getElementById( 'wpd-c-transition' ) ) {
		const transition = document.createElement( 'label' );
		transition.innerHTML = ' Effet <select id="wpd-c-transition"><option value="slide">Glissement</option><option value="fade">Fondu</option><option value="none">Sans animation</option></select>';
		sliderRow.querySelector( 'td' ).appendChild( transition );
		const direction = document.createElement( 'label' );
		direction.innerHTML = ' Direction <select id="wpd-c-direction"><option value="ltr">Vers la gauche</option><option value="rtl">Vers la droite</option></select>';
		sliderRow.querySelector( 'td' ).appendChild( direction );
	}

	const outputRow = output.closest( 'tr' );

	if ( ! document.getElementById( 'wpd-c-shape' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-shape';
		row.innerHTML = '<th>Forme</th><td><label>Forme <select id="wpd-c-shape"><option value="rectangle">Rectangle</option><option value="rounded">Rectangle arrondi</option><option value="circle">Cercle</option><option value="oval">Ovale</option><option value="pill">Pilule</option><option value="star">Étoile</option><option value="hexagon">Hexagone</option><option value="diamond">Losange</option><option value="cloud">Nuage</option><option value="heart">Cœur</option><option value="drop">Goutte</option><option value="triangle">Triangle</option><option value="pentagon">Pentagone</option><option value="octagon">Octogone</option><option value="card-spade">Carte — Pique ♠</option><option value="card-heart">Carte — Cœur ♥</option><option value="card-diamond">Carte — Carreau ♦</option><option value="card-club">Carte — Trèfle ♣</option></select></label> <label id="wpd-c-radius-wrap">Arrondi <input id="wpd-c-radius" class="small-text" type="number" min="0" max="50" value="8"> %</label></td>';
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	const shapeSelect = document.getElementById( 'wpd-c-shape' );
	if ( shapeSelect && ! root.querySelector( '.wpd-shape-picker-grid' ) ) {
		const picker = document.createElement( 'div' );
		picker.className = 'wpd-shape-picker-grid';
		picker.setAttribute( 'role', 'group' );
		picker.setAttribute( 'aria-label', 'Choisir une forme' );
		Array.from( shapeSelect.options ).forEach( ( option ) => {
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'wpd-shape-picker-button';
			button.dataset.wpdShapeValue = option.value;
			button.setAttribute( 'aria-label', option.textContent );
			button.setAttribute( 'title', option.textContent );
			button.setAttribute( 'aria-pressed', 'false' );
			const preview = document.createElement( 'span' );
			preview.className = 'wpd-shape-picker-preview wpd-shape-preview-' + option.value;
			preview.setAttribute( 'aria-hidden', 'true' );
			const label = document.createElement( 'span' );
			label.textContent = option.textContent;
			button.append( preview, label );
			picker.appendChild( button );
		} );
		shapeSelect.parentNode.insertAdjacentElement( 'afterend', picker );
	}

	if ( ! document.getElementById( 'wpd-c-masonry-columns' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-masonry';
		row.innerHTML = '<th>Masonry</th><td><label>Colonnes <input id="wpd-c-masonry-columns" class="small-text" type="number" min="2" max="6" value="4"></label> <label>Espacement <input id="wpd-c-masonry-gap" class="small-text" type="number" min="0" max="64" value="16"> px</label></td>';
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	if ( ! document.getElementById( 'wpd-c-justified-row-height' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-justified';
		row.innerHTML = '<th>Galerie justifiée</th><td><label>Hauteur cible <input id="wpd-c-justified-row-height" class="small-text" type="number" min="100" max="600" value="220"> px</label> <label>Espacement <input id="wpd-c-justified-gap" class="small-text" type="number" min="0" max="64" value="8"> px</label></td>';
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	if ( ! document.getElementById( 'wpd-c-collage-seed' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-collage';
		row.innerHTML = '<th>Collage / Pêle-mêle</th><td><label>Graine <input id="wpd-c-collage-seed" class="small-text" type="number" value="0"></label> <label>Rotation <input id="wpd-c-collage-rotation" class="small-text" type="number" min="0" max="15" value="6">°</label> <label>Dispersion <input id="wpd-c-collage-spread" class="small-text" type="number" min="0" max="50" value="18"> px</label> <label>Chevauchement <input id="wpd-c-collage-overlap" class="small-text" type="number" min="0" max="40" value="12"> px</label> <label>Taille moyenne <input id="wpd-c-collage-size" class="small-text" type="number" min="120" max="420" value="220"> px</label> <label>Variation <input id="wpd-c-collage-variation" class="small-text" type="number" min="0" max="50" value="20"> %</label></td>';
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	if ( ! document.getElementById( 'wpd-c-photo-text' ) ) {
		const row = document.createElement( 'tr' );
		row.className = 'wpd-c-photo-text';
		row.innerHTML = '<th>Texte rempli de photos</th><td><label>Texte <textarea id="wpd-c-photo-text" rows="3">PÊLE-MÊLE</textarea><span class="description">Jusqu’à quatre lignes.</span></label> <label>Graine <input id="wpd-c-photo-text-seed" class="small-text" type="text" value="0"></label> <label>Police <select id="wpd-c-photo-text-font"><option value="inherit">Police du thème</option><option value="system">Système</option><option value="serif">Serif</option><option value="mono">Monospace</option></select></label> <label>Graisse <input id="wpd-c-photo-text-weight" class="small-text" type="number" min="100" max="900" step="100" value="800"></label> <label>Taille <input id="wpd-c-photo-text-size" class="small-text" type="number" min="120" max="300" value="230"></label> <label>Interlettrage <input id="wpd-c-photo-text-letter-spacing" class="small-text" type="number" min="-20" max="80" value="0"></label> <label>Hauteur ligne <input id="wpd-c-photo-text-line-height" class="small-text" type="number" min="70" max="160" step="5" value="100"> %</label> <label>Largeur max <input id="wpd-c-photo-text-max-width" class="small-text" type="number" min="20" max="100" value="100"> %</label> <label>Alignement <select id="wpd-c-photo-text-align"><option value="left">Gauche</option><option value="center" selected>Centre</option><option value="right">Droite</option></select></label> <label>Remplissage <select id="wpd-c-photo-text-fill-mode"><option value="grid">Grille</option><option value="masonry">Masonry</option><option value="collage">Pêle-mêle</option></select></label> <label>Densité <input id="wpd-c-photo-text-density" class="small-text" type="number" min="50" max="200" step="10" value="100"> %</label> <span class="wpd-c-photo-text-collage"><label>Rotation <input id="wpd-c-photo-text-rotation" class="small-text" type="number" min="0" max="15" value="6">°</label> <label>Dispersion <input id="wpd-c-photo-text-spread" class="small-text" type="number" min="0" max="50" value="18"></label></span> <label>Photos max <input id="wpd-c-photo-text-max-images" class="small-text" type="number" min="1" max="40" value="20"></label> <label><input id="wpd-c-photo-text-outline" type="checkbox" checked> Contour</label> <label>Épaisseur <input id="wpd-c-photo-text-outline-width" class="small-text" type="number" min="0" max="12" value="3"></label> <label>Couleur contour <input id="wpd-c-photo-text-outline-color" type="text" value="#ffffff"></label> <label>Fond <input id="wpd-c-photo-text-background" type="text" value="transparent"></label></td>';
		outputRow.parentNode.insertBefore( row, outputRow );
	}

	const escapeValue = ( value ) => String( value ).replace( /\\/g, '\\\\' ).replace( /\r?\n/g, '\\n' ).replace( /"/g, '\\"' );
	const removeAttribute = ( shortcode, key ) => shortcode.replace( new RegExp( '\\s+' + key + '="(?:\\\\.|[^"])*"', 'g' ), '' );
	const appendAttribute = ( shortcode, key, value ) => shortcode.replace( /\]$/, ' ' + key + '="' + escapeValue( value ) + '"]' );
	const clamp = ( value, min, max, fallback ) => {
		const parsed = parseInt( value, 10 );
		return Number.isFinite( parsed ) ? Math.min( max, Math.max( min, parsed ) ) : fallback;
	};

	function syncShapePicker( shape ) {
		root.querySelectorAll( '[data-wpd-shape-value]' ).forEach( ( button ) => {
			button.setAttribute( 'aria-pressed', button.dataset.wpdShapeValue === shape ? 'true' : 'false' );
		} );
	}

	function syncParity() {
		document.querySelectorAll( '.wpd-c-masonry' ).forEach( ( row ) => {
			row.style.display = type.value === 'masonry' ? 'table-row' : 'none';
		} );
		document.querySelectorAll( '.wpd-c-justified' ).forEach( ( row ) => {
			row.style.display = type.value === 'justified' ? 'table-row' : 'none';
		} );
		document.querySelectorAll( '.wpd-c-collage' ).forEach( ( row ) => {
			row.style.display = type.value === 'collage' ? 'table-row' : 'none';
		} );
		document.querySelectorAll( '.wpd-c-photo-text' ).forEach( ( row ) => {
			row.style.display = type.value === 'photo-text' ? 'table-row' : 'none';
		} );

		const shape = document.getElementById( 'wpd-c-shape' ).value;
		document.getElementById( 'wpd-c-radius-wrap' ).style.display = shape === 'rounded' ? 'inline' : 'none';
		syncShapePicker( shape );
		const photoFillMode = document.getElementById( 'wpd-c-photo-text-fill-mode' ).value;
		root.querySelectorAll( '.wpd-c-photo-text-collage' ).forEach( ( group ) => {
			group.style.display = type.value === 'photo-text' && photoFillMode === 'collage' ? 'inline' : 'none';
		} );

		let shortcode = output.value;
		[ 'transition', 'direction', 'masonry_columns', 'masonry_gap', 'justified_row_height', 'justified_gap', 'collage_seed', 'collage_rotation', 'collage_spread', 'collage_overlap', 'collage_size', 'collage_variation', 'photo_text', 'photo_text_seed', 'photo_text_font', 'photo_text_weight', 'photo_text_size', 'photo_text_letter_spacing', 'photo_text_line_height', 'photo_text_max_width', 'photo_text_align', 'photo_text_fill_mode', 'photo_text_density', 'photo_text_rotation', 'photo_text_spread', 'photo_text_outline', 'photo_text_outline_width', 'photo_text_outline_color', 'photo_text_background', 'photo_text_max_images', 'shape', 'radius' ].forEach( ( key ) => {
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
		if ( type.value === 'collage' ) {
			shortcode = appendAttribute( shortcode, 'collage_seed', parseInt( document.getElementById( 'wpd-c-collage-seed' ).value || '0', 10 ) || 0 );
			shortcode = appendAttribute( shortcode, 'collage_rotation', clamp( document.getElementById( 'wpd-c-collage-rotation' ).value, 0, 15, 6 ) );
			shortcode = appendAttribute( shortcode, 'collage_spread', clamp( document.getElementById( 'wpd-c-collage-spread' ).value, 0, 50, 18 ) );
			shortcode = appendAttribute( shortcode, 'collage_overlap', clamp( document.getElementById( 'wpd-c-collage-overlap' ).value, 0, 40, 12 ) );
			shortcode = appendAttribute( shortcode, 'collage_size', clamp( document.getElementById( 'wpd-c-collage-size' ).value, 120, 420, 220 ) );
			shortcode = appendAttribute( shortcode, 'collage_variation', clamp( document.getElementById( 'wpd-c-collage-variation' ).value, 0, 50, 20 ) );
		}
		if ( type.value === 'photo-text' ) {
			shortcode = appendAttribute( shortcode, 'photo_text', document.getElementById( 'wpd-c-photo-text' ).value || 'PÊLE-MÊLE' );
			shortcode = appendAttribute( shortcode, 'photo_text_seed', document.getElementById( 'wpd-c-photo-text-seed' ).value || '0' );
			shortcode = appendAttribute( shortcode, 'photo_text_font', document.getElementById( 'wpd-c-photo-text-font' ).value );
			shortcode = appendAttribute( shortcode, 'photo_text_weight', clamp( document.getElementById( 'wpd-c-photo-text-weight' ).value, 100, 900, 800 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_size', clamp( document.getElementById( 'wpd-c-photo-text-size' ).value, 120, 300, 230 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_letter_spacing', clamp( document.getElementById( 'wpd-c-photo-text-letter-spacing' ).value, -20, 80, 0 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_line_height', clamp( document.getElementById( 'wpd-c-photo-text-line-height' ).value, 70, 160, 100 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_max_width', clamp( document.getElementById( 'wpd-c-photo-text-max-width' ).value, 20, 100, 100 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_align', document.getElementById( 'wpd-c-photo-text-align' ).value );
			shortcode = appendAttribute( shortcode, 'photo_text_fill_mode', photoFillMode );
			shortcode = appendAttribute( shortcode, 'photo_text_density', clamp( document.getElementById( 'wpd-c-photo-text-density' ).value, 50, 200, 100 ) );
			if ( photoFillMode === 'collage' ) {
				shortcode = appendAttribute( shortcode, 'photo_text_rotation', clamp( document.getElementById( 'wpd-c-photo-text-rotation' ).value, 0, 15, 6 ) );
				shortcode = appendAttribute( shortcode, 'photo_text_spread', clamp( document.getElementById( 'wpd-c-photo-text-spread' ).value, 0, 50, 18 ) );
			}
			shortcode = appendAttribute( shortcode, 'photo_text_max_images', clamp( document.getElementById( 'wpd-c-photo-text-max-images' ).value, 1, 40, 20 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_outline', document.getElementById( 'wpd-c-photo-text-outline' ).checked ? 'true' : 'false' );
			shortcode = appendAttribute( shortcode, 'photo_text_outline_width', clamp( document.getElementById( 'wpd-c-photo-text-outline-width' ).value, 0, 12, 3 ) );
			shortcode = appendAttribute( shortcode, 'photo_text_outline_color', document.getElementById( 'wpd-c-photo-text-outline-color' ).value || '#ffffff' );
			shortcode = appendAttribute( shortcode, 'photo_text_background', document.getElementById( 'wpd-c-photo-text-background' ).value || 'transparent' );
		}

		output.value = shortcode;
	}

	root.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( '[data-wpd-shape-value]' );
		if ( ! button ) {
			return;
		}
		shapeSelect.value = button.dataset.wpdShapeValue;
		shapeSelect.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
	root.addEventListener( 'input', () => window.setTimeout( syncParity, 0 ) );
	root.addEventListener( 'change', () => window.setTimeout( syncParity, 0 ) );
	window.setTimeout( syncParity, 0 );
}() );
