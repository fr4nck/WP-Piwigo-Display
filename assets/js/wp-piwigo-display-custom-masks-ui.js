( function () {
	'use strict';

	const masks = Array.isArray( window.WPDCustomMasks ) ? window.WPDCustomMasks : [];
	if ( ! masks.length ) {
		return;
	}

	function applyPreview( preview, mask ) {
		preview.style.background = '#1d2327';
		preview.style.webkitMaskImage = 'url("' + mask.dataUri + '")';
		preview.style.maskImage = 'url("' + mask.dataUri + '")';
		preview.style.webkitMaskSize = 'contain';
		preview.style.maskSize = 'contain';
		preview.style.webkitMaskPosition = 'center';
		preview.style.maskPosition = 'center';
		preview.style.webkitMaskRepeat = 'no-repeat';
		preview.style.maskRepeat = 'no-repeat';
	}

	function appendOptions( select ) {
		if ( ! select || select.dataset.wpdCustomMasksReady === '1' ) {
			return;
		}

		const group = document.createElement( 'optgroup' );
		group.label = 'Masques SVG personnalisés';
		masks.forEach( ( mask ) => {
			if ( select.querySelector( 'option[value="' + mask.value + '"]' ) ) {
				return;
			}
			const option = document.createElement( 'option' );
			option.value = mask.value;
			option.textContent = mask.name;
			group.appendChild( option );
		} );
		if ( group.children.length ) {
			select.appendChild( group );
		}
		select.dataset.wpdCustomMasksReady = '1';
	}

	function appendPickerButtons( picker, select ) {
		if ( ! picker || picker.dataset.wpdCustomMasksReady === '1' ) {
			return;
		}

		masks.forEach( ( mask ) => {
			if ( picker.querySelector( '[data-wpd-shape-value="' + mask.value + '"]' ) ) {
				return;
			}
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'wpd-shape-picker-button';
			button.dataset.wpdShapeValue = mask.value;
			button.setAttribute( 'aria-label', mask.name );
			button.setAttribute( 'title', mask.name );
			button.setAttribute( 'aria-pressed', select && select.value === mask.value ? 'true' : 'false' );

			const preview = document.createElement( 'span' );
			preview.className = 'wpd-shape-picker-preview';
			preview.setAttribute( 'aria-hidden', 'true' );
			applyPreview( preview, mask );

			const label = document.createElement( 'span' );
			label.textContent = mask.name;
			button.append( preview, label );
			picker.appendChild( button );
		} );
		picker.dataset.wpdCustomMasksReady = '1';
	}

	function wireClassic() {
		const dialog = document.getElementById( 'wpd-classic-builder' );
		if ( ! dialog ) {
			return;
		}
		const select = dialog.querySelector( '[data-wpd="shape"]' );
		const picker = dialog.querySelector( '[data-wpd-shape-picker]' );
		appendOptions( select );
		appendPickerButtons( picker, select );
	}

	function wireComposer() {
		const root = document.getElementById( 'wpd-admin-composer' );
		if ( ! root ) {
			return;
		}
		const select = document.getElementById( 'wpd-c-shape' );
		const picker = root.querySelector( '.wpd-shape-picker-grid' );
		appendOptions( select );
		appendPickerButtons( picker, select );
	}

	if ( document.getElementById( 'wpd-classic-builder' ) && window.jQuery ) {
		window.jQuery( wireClassic );
	} else {
		wireClassic();
	}
	wireComposer();
}() );
