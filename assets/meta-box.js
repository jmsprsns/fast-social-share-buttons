/**
 * "Social Sharing" meta box: the Pinterest image picker.
 * Loaded only on post edit screens.
 */

/* global fssbMetaBox */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var box = event.target.closest( '.fssb-pin-image' );

		if ( ! box ) {
			return;
		}

		var input = box.querySelector( '[data-fssb-pin-input]' );
		var preview = box.querySelector( '[data-fssb-pin-preview]' );
		var remove = box.querySelector( '[data-fssb-pin-remove]' );

		if ( event.target.closest( '[data-fssb-pin-choose]' ) ) {
			event.preventDefault();

			var frame = window.wp.media( {
				title: fssbMetaBox.title,
				button: { text: fssbMetaBox.button },
				library: { type: 'image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var image = frame.state().get( 'selection' ).first().toJSON();
				var size = image.sizes && image.sizes.medium ? image.sizes.medium : image;

				input.value = image.id;
				preview.querySelector( 'img' ).src = size.url;
				preview.hidden = false;
				remove.hidden = false;
			} );

			frame.open();
		} else if ( event.target.closest( '[data-fssb-pin-remove]' ) ) {
			event.preventDefault();
			input.value = '';
			preview.hidden = true;
			remove.hidden = true;
		}
	} );
} )();
