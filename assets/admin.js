/**
 * Content Powered > Fast Social Share Buttons page behaviour. Only loaded on that page.
 *
 * Tabs, the sortable network list, the live preview and the Copy buttons.
 * fast_social_share_buttons_admin.option and .networks are passed in from PHP
 * (see Fast_Social_Share_Buttons_Admin::enqueue_assets).
 */

/* global fast_social_share_buttons_admin, jQuery */
( function () {
	'use strict';

	var data = window.fast_social_share_buttons_admin;

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof data === 'undefined' ) {
			return;
		}

		var form = document.querySelector( '.fssb-form' );
		var list = document.querySelector( '[data-fssb-networks]' );
		var preview = document.querySelector( '[data-fssb-preview] .fssb' );

		initTabs( form );
		initCopy();

		if ( ! form || ! list || ! preview ) {
			return;
		}

		// Preview buttons are for looking at, not sharing.
		preview.addEventListener( 'click', function ( event ) {
			event.preventDefault();
		} );

		initNetworks( list, function () {
			updatePreview( form, list, preview );
		} );

		form.addEventListener( 'input', function () {
			updatePreview( form, list, preview );
		} );
		form.addEventListener( 'change', function () {
			updatePreview( form, list, preview );
		} );
		window.addEventListener( 'resize', function () {
			fitLabels( preview );
		} );

		updatePreview( form, list, preview );
	} );

	// Tabs ---------------------------------------------------------------------

	function initTabs( form ) {
		var tabs = document.querySelectorAll( '[data-fssb-tab]' );
		var panels = document.querySelectorAll( '[data-fssb-panel]' );
		var referer = form ? form.querySelector( 'input[name="_wp_http_referer"]' ) : null;
		var refererBase = referer ? referer.value.split( '#' )[ 0 ] : '';

		function show( id ) {
			var found = false;

			tabs.forEach( function ( tab ) {
				var active = tab.getAttribute( 'data-fssb-tab' ) === id;

				found = found || active;
				tab.classList.toggle( 'is-active', active );
				tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );

			if ( ! found && tabs.length ) {
				show( tabs[ 0 ].getAttribute( 'data-fssb-tab' ) );
				return;
			}

			panels.forEach( function ( panel ) {
				panel.hidden = panel.getAttribute( 'data-fssb-panel' ) !== id;
			} );

			// Come back to this tab after saving.
			if ( referer ) {
				referer.value = refererBase + '#' + id;
			}
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function ( event ) {
				var id = tab.getAttribute( 'data-fssb-tab' );

				event.preventDefault();
				show( id );
				history.replaceState( null, '', '#' + id );
			} );
		} );

		// Back/forward and links to #style etc.
		window.addEventListener( 'hashchange', function () {
			show( window.location.hash.replace( '#', '' ) );
		} );

		show( window.location.hash.replace( '#', '' ) );
	}

	// Copy buttons ---------------------------------------------------------------

	function initCopy() {
		document.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-fssb-copy]' );

			if ( ! button ) {
				return;
			}

			var text = button.getAttribute( 'data-fssb-copy' );
			var label = button.textContent;
			var done = function () {
				button.textContent = data.copied;
				setTimeout( function () {
					button.textContent = label;
				}, 1500 );
			};

			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( done );
				return;
			}

			// Plain-http sites have no clipboard API.
			var field = document.createElement( 'textarea' );
			field.value = text;
			field.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
			document.body.appendChild( field );
			field.select();
			try {
				if ( document.execCommand( 'copy' ) ) {
					done();
				}
			} catch ( e ) {}
			document.body.removeChild( field );
		} );
	}

	// Network list ---------------------------------------------------------------

	function initNetworks( list, onChange ) {
		if ( window.jQuery && jQuery.fn.sortable ) {
			jQuery( list ).sortable( {
				handle: '.fssb-network-handle',
				axis: 'y',
				placeholder: 'fssb-network ui-sortable-placeholder',
				update: onChange,
			} );
		}

		// Arrow buttons, for keyboards and anyone who'd rather not drag.
		list.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( '[data-fssb-move]' );

			if ( ! button ) {
				return;
			}

			var item = button.closest( '.fssb-network' );
			var up = '-1' === button.getAttribute( 'data-fssb-move' );
			var sibling = up ? item.previousElementSibling : item.nextElementSibling;

			if ( sibling ) {
				list.insertBefore( item, up ? sibling : sibling.nextElementSibling );
				button.focus();
				onChange();
			}
		} );
	}

	// Preview --------------------------------------------------------------------

	function value( form, key ) {
		var field = form.querySelector( '[name="' + data.option + '[' + key + ']"]:checked' ) ||
			form.querySelector( 'input[type="text"][name="' + data.option + '[' + key + ']"]' );

		return field ? field.value : '';
	}

	/**
	 * Mirror Fast_Social_Share_Buttons_Renderer::render() for the inline buttons.
	 */
	function updatePreview( form, list, preview ) {
		var shape = value( form, 'shape' );
		var colors = value( form, 'colors' );
		var size = value( form, 'size' );
		var labels = 'circle' === shape ? 'never' : value( form, 'labels' );
		var align = value( form, 'align' );
		var labelStyle = value( form, 'label_style' );
		var heading = value( form, 'heading' );

		var classes = [
			'fssb',
			'fssb-inline',
			'stretch' !== align ? 'fssb-compact' : '',
			'center' === align || 'right' === align ? 'fssb-' + align : '',
			{ square: 'fssb-sq', pill: 'fssb-pl', circle: 'fssb-ci' }[ shape ] || '',
			'brand' !== colors ? 'fssb-' + colors : '',
			{ s: 'fssb-s', l: 'fssb-lg' }[ size ] || '',
			'never' === labels ? 'fssb-nolabel' : '',
		];

		preview.className = classes.filter( Boolean ).join( ' ' );

		// Heading.
		var head = preview.querySelector( '.fssb-h' );
		if ( heading && ! head ) {
			head = document.createElement( 'span' );
			head.className = 'fssb-h';
			preview.insertBefore( head, preview.firstChild );
		}
		if ( head ) {
			head.textContent = heading;
			head.hidden = ! heading;
		}

		// Networks: list order, ticked only, with the chosen label text.
		var count = 0;
		var longest = 0;

		list.querySelectorAll( '.fssb-network' ).forEach( function ( item ) {
			var key = item.getAttribute( 'data-fssb-network' );
			var button = preview.querySelector( '.fssb-b[data-fssb="' + key + '"]' );
			var on = item.querySelector( 'input' ).checked;

			if ( ! button ) {
				return;
			}

			button.hidden = ! on;
			preview.appendChild( button );

			var label = button.querySelector( '.fssb-l' );
			if ( label && data.networks[ key ] ) {
				label.textContent = data.networks[ key ][ 'action' === labelStyle ? 'action' : 'name' ];
			}

			if ( on ) {
				count++;
				longest = Math.max( longest, label ? label.textContent.length : 0 );
			}
		} );

		var counter = document.querySelector( '[data-fssb-count]' );
		if ( counter ) {
			counter.textContent = data.count.replace( '%1$s', count ).replace( '%2$s', list.children.length );
		}

		// Same sizing inputs as Fast_Social_Share_Buttons_Renderer::render().
		preview.style.setProperty( '--fssb-n', Math.max( 1, count ) );
		preview.style.setProperty( '--fssb-lw', Math.max( 6, 2.9 + 0.6 * longest ).toFixed( 1 ) + 'em' );

		fitLabels( preview );
	}

	// Icons only if any label doesn't fit, as on the front end.
	function fitLabels( group ) {
		group.classList.remove( 'fssb-fit' );

		if ( group.classList.contains( 'fssb-nolabel' ) || group.classList.contains( 'fssb-ci' ) ) {
			return;
		}

		var labels = group.querySelectorAll( '.fssb-b:not([hidden]) .fssb-l' );
		var range = document.createRange();

		for ( var i = 0; i < labels.length; i++ ) {
			range.selectNodeContents( labels[ i ] );

			// Sub-pixel comparison, as on the front end.
			if ( range.getBoundingClientRect().width > labels[ i ].getBoundingClientRect().width + 0.05 ) {
				group.classList.add( 'fssb-fit' );
				return;
			}
		}
	}
} )();
