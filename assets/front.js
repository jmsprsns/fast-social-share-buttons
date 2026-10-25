/**
 * Fast Social Share Buttons: front-end behaviour.
 *
 * Loaded with defer, only on pages that show buttons (front.min.js is the
 * built copy; run bin/build.sh after editing). No dependencies.
 *
 * - One delegated click listener handles every button: share popups, copy
 *   link, print, the browser's native share sheet and Mastodon.
 * - In-content buttons show labels only when every label fits; otherwise
 *   they switch to icons. Button sizes come from the CSS grid, so this never
 *   moves anything on the page.
 * - The floating sidebar and mobile bar arrive as inert <template>s and are
 *   only built the first time they need to show.
 * - Share links are ordinary links, so they still work if this never runs.
 */

/* global fssbConfig */
( function () {
	'use strict';

	var doc = document;
	var win = window;
	var cfg = win.fssbConfig || {};
	var i18n = cfg.i18n || {};
	var liveRegion;

	// Buttons -----------------------------------------------------------------

	function onClick( event ) {
		var btn = event.target.closest && event.target.closest( '[data-fssb-act]' );

		if ( ! btn || event.defaultPrevented ) {
			return;
		}

		var act = btn.getAttribute( 'data-fssb-act' );

		// Let ctrl/cmd/shift-click open links the browser's way.
		if ( btn.href && ( event.metaKey || event.ctrlKey || event.shiftKey || event.button ) ) {
			return;
		}

		if ( 'popup' === act ) {
			if ( popup( btn.href ) ) {
				event.preventDefault();
			}
		} else if ( 'copy' === act ) {
			event.preventDefault();
			copy( btn );
		} else if ( 'print' === act ) {
			event.preventDefault();
			win.print();
		} else if ( 'native' === act ) {
			event.preventDefault();
			nativeShare( btn );
		} else if ( 'mastodon' === act ) {
			event.preventDefault();
			mastodon( btn );
		}
	}

	/**
	 * Open a centred share window. On touch devices the link opens normally
	 * instead, so the network's app (or a new tab) can take it.
	 *
	 * @param {string} url Share URL.
	 * @return {boolean} Whether a window opened.
	 */
	function popup( url ) {
		if ( win.matchMedia( '(pointer: coarse)' ).matches ) {
			return false;
		}

		var width = 600;
		var height = 600;
		var left = Math.round( ( win.screenLeft || win.screenX || 0 ) + ( win.outerWidth - width ) / 2 );
		var top = Math.round( ( win.screenTop || win.screenY || 0 ) + ( win.outerHeight - height ) / 2 );
		var shareWindow = win.open( '', '_blank', 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes' );

		if ( ! shareWindow ) {
			return false;
		}

		// Cut the link back to this page before loading the network's.
		shareWindow.opener = null;
		shareWindow.location = url;

		return true;
	}

	function shareUrl( btn ) {
		var group = btn.closest( '[data-fssb-url]' );

		return btn.getAttribute( 'data-fssb-url' ) || ( group && group.getAttribute( 'data-fssb-url' ) ) || win.location.href;
	}

	function copy( btn ) {
		var url = shareUrl( btn );
		var done = function () {
			flash( btn, i18n.copied );
		};

		if ( navigator.clipboard && win.isSecureContext ) {
			navigator.clipboard.writeText( url ).then( done, function () {
				if ( legacyCopy( url ) ) {
					done();
				}
			} );
		} else if ( legacyCopy( url ) ) {
			done();
		}
	}

	function legacyCopy( text ) {
		var field = doc.createElement( 'textarea' );
		var ok = false;

		field.value = text;
		field.setAttribute( 'readonly', '' );
		field.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
		doc.body.appendChild( field );
		field.select();

		try {
			ok = doc.execCommand( 'copy' );
		} catch ( e ) {}

		doc.body.removeChild( field );

		if ( ! ok ) {
			win.prompt( i18n.copy, text );
		}

		return ok;
	}

	/**
	 * Briefly swap a button's icon for a tick and its label for a message,
	 * and announce the message to screen readers.
	 */
	function flash( btn, message ) {
		var label = btn.querySelector( '.fssb-l' );
		var icon = btn.querySelector( 'use' );

		if ( label && ! btn.hasAttribute( 'data-fssb-label' ) ) {
			btn.setAttribute( 'data-fssb-label', label.textContent );
			btn.setAttribute( 'data-fssb-icon', icon ? icon.getAttribute( 'href' ) : '' );
		}

		if ( label ) {
			label.textContent = message;
		}

		if ( icon ) {
			icon.setAttribute( 'href', '#fssb-check' );
		}

		announce( message );

		clearTimeout( btn.fssbTimer );
		btn.fssbTimer = setTimeout( function () {
			if ( label ) {
				label.textContent = btn.getAttribute( 'data-fssb-label' );
			}
			if ( icon ) {
				icon.setAttribute( 'href', btn.getAttribute( 'data-fssb-icon' ) );
			}
		}, 2000 );
	}

	function announce( message ) {
		if ( ! liveRegion ) {
			liveRegion = doc.createElement( 'span' );
			liveRegion.className = 'fssb-sr';
			liveRegion.setAttribute( 'role', 'status' );
			doc.body.appendChild( liveRegion );
		}

		liveRegion.textContent = '';
		setTimeout( function () {
			liveRegion.textContent = message;
		}, 50 );
	}

	function nativeShare( btn ) {
		var group = btn.closest( '[data-fssb-title]' );

		// No share sheet (e.g. desktop Firefox): copy the link instead.
		if ( ! navigator.share ) {
			copy( btn );
			return;
		}

		navigator.share( {
			title: group ? group.getAttribute( 'data-fssb-title' ) : doc.title,
			url: shareUrl( btn ),
		} ).catch( function () {} );
	}

	/**
	 * Mastodon has no central share page, so ask which server to use (once;
	 * it's remembered in this browser).
	 */
	function mastodon( btn ) {
		var saved = '';

		try {
			saved = win.localStorage.getItem( 'fssb-mastodon' ) || '';
		} catch ( e ) {}

		var host = win.prompt( i18n.mastodon, saved || 'mastodon.social' );

		if ( ! host ) {
			return;
		}

		// Accept "server.tld", "https://server.tld/..." or "@me@server.tld".
		host = host.trim().replace( /^https?:\/\//i, '' ).replace( /\/.*$/, '' ).replace( /^@?[^@]*@/, '' ).toLowerCase();

		if ( ! /^[a-z0-9-]+(\.[a-z0-9-]+)+$/.test( host ) ) {
			return;
		}

		try {
			win.localStorage.setItem( 'fssb-mastodon', host );
		} catch ( e ) {}

		var url = new URL( btn.href );
		url.host = host;

		// The prompt can use up the click's permission to open windows, so
		// fall back to a new tab, then to this one.
		if ( ! popup( url.href ) ) {
			var tab = win.open( url.href, '_blank' );

			if ( tab ) {
				tab.opener = null;
			} else {
				win.location.href = url.href;
			}
		}
	}

	// Labels: all or nothing ----------------------------------------------------

	/**
	 * Show labels only if every one fits its button; otherwise icons only.
	 * Re-checked when web fonts finish loading and when the content area
	 * changes width.
	 */
	function initLabels() {
		var groups = doc.querySelectorAll( '.fssb-inline:not(.fssb-nolabel):not(.fssb-ci)' );

		if ( ! groups.length ) {
			return;
		}

		function fit( group ) {
			var labels = group.querySelectorAll( '.fssb-b:not([hidden]) .fssb-l' );

			group.classList.remove( 'fssb-fit' );

			for ( var i = 0; i < labels.length; i++ ) {
				if ( labels[ i ].scrollWidth > labels[ i ].clientWidth + 1 ) {
					group.classList.add( 'fssb-fit' );
					return;
				}
			}
		}

		function fitAll() {
			groups.forEach( fit );
		}

		fitAll();

		if ( doc.fonts && doc.fonts.ready ) {
			doc.fonts.ready.then( fitAll );
		}

		// Watch the parent, not the group: switching to icons can resize the group.
		if ( 'ResizeObserver' in win ) {
			var observer = new ResizeObserver( fitAll );

			groups.forEach( function ( group ) {
				if ( group.parentElement ) {
					observer.observe( group.parentElement );
				}
			} );
		} else {
			win.addEventListener( 'resize', fitAll );
		}
	}

	// Floating sidebar and mobile bar ----------------------------------------

	function initFloating() {
		var side = doc.getElementById( 'fssb-tpl-side' );
		var bar = doc.getElementById( 'fssb-tpl-bar' );

		if ( ! side && ! bar ) {
			return;
		}

		var wide = win.matchMedia( '(min-width: ' + ( cfg.bp || 1024 ) + 'px)' );
		var inline = doc.querySelectorAll( '.fssb-inline' );
		var watch = 'always' !== cfg.float && inline.length && 'IntersectionObserver' in win;
		var ready = ! watch;
		var visible = 0;
		var built = {};

		// Build a template's buttons the first time they're needed.
		function build( tpl ) {
			if ( ! built[ tpl.id ] ) {
				var node = tpl.content.firstElementChild.cloneNode( true );

				doc.body.appendChild( node );
				node.getBoundingClientRect(); // Settle the off-screen position so it slides in.
				built[ tpl.id ] = node;
			}

			return built[ tpl.id ];
		}

		function update() {
			var active = wide.matches ? side : bar;
			var show = !! active && ready && 0 === visible;

			[ side, bar ].forEach( function ( tpl ) {
				if ( ! tpl ) {
					return;
				}

				if ( show && tpl === active ) {
					build( tpl ).classList.add( 'fssb-on' );
				} else if ( built[ tpl.id ] ) {
					built[ tpl.id ].classList.remove( 'fssb-on' );
				}
			} );

			doc.documentElement.classList.toggle( 'fssb-bar-on', show && active === bar );
		}

		// "Auto": stay out of the way while in-content buttons are on screen.
		if ( watch ) {
			var observer = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					entry.target.fssbVisible = entry.isIntersecting;
				} );

				visible = 0;
				inline.forEach( function ( node ) {
					visible += node.fssbVisible ? 1 : 0;
				} );

				ready = true;
				update();
			} );

			inline.forEach( function ( node ) {
				observer.observe( node );
			} );
		}

		if ( wide.addEventListener ) {
			wide.addEventListener( 'change', update );
		} else {
			wide.addListener( update );
		}

		update();
	}

	// Pinterest button on images ---------------------------------------------

	function initPin() {
		var marker = cfg.pin && doc.querySelector( '.fssb-pin-scope' );

		if ( ! marker || ! marker.parentElement || win.matchMedia( '(hover: none)' ).matches ) {
			return;
		}

		var scope = marker.parentElement;
		var btn = null;
		var current = null;
		var timer;

		function hide() {
			clearTimeout( timer );
			timer = setTimeout( function () {
				btn.classList.remove( 'fssb-on' );
				current = null;
			}, 150 );
		}

		function make() {
			btn = doc.createElement( 'a' );
			btn.className = 'fssb-pin';
			btn.target = '_blank';
			btn.rel = 'nofollow noopener noreferrer';
			btn.setAttribute( 'data-fssb', 'pinterest' );
			btn.setAttribute( 'data-fssb-act', 'popup' );
			btn.innerHTML = '<svg class="fssb-i" aria-hidden="true" focusable="false"><use href="#fssb-pinterest"></use></svg>';
			btn.appendChild( doc.createTextNode( i18n.save ) );
			btn.addEventListener( 'mouseenter', function () {
				clearTimeout( timer );
			} );
			btn.addEventListener( 'mouseleave', function ( event ) {
				if ( event.relatedTarget !== current ) {
					hide();
				}
			} );
			doc.body.appendChild( btn );
		}

		scope.addEventListener( 'mouseover', function ( event ) {
			var img = event.target;

			if ( 'IMG' !== img.tagName || img === current || img.closest( '.fssb, [data-pin-nopin]' ) || img.hasAttribute( 'data-pin-nopin' ) ) {
				return;
			}

			var rect = img.getBoundingClientRect();

			if ( rect.width < cfg.pin || rect.height < 100 ) {
				return;
			}

			if ( ! btn ) {
				make();
			}

			clearTimeout( timer );
			current = img;
			btn.href = 'https://www.pinterest.com/pin/create/button/?url=' + encodeURIComponent( cfg.url || win.location.href ) +
				'&media=' + encodeURIComponent( img.currentSrc || img.src ) +
				'&description=' + encodeURIComponent( img.getAttribute( 'data-pin-description' ) || img.alt || doc.title );
			btn.style.top = ( rect.top + win.pageYOffset + 12 ) + 'px';
			btn.style.left = ( rect.left + win.pageXOffset + 12 ) + 'px';
			btn.classList.add( 'fssb-on' );
		} );

		scope.addEventListener( 'mouseout', function ( event ) {
			if ( current && event.target === current && event.relatedTarget !== btn && ! ( btn && btn.contains( event.relatedTarget ) ) ) {
				hide();
			}
		} );
	}

	// Start -------------------------------------------------------------------

	function init() {
		doc.addEventListener( 'click', onClick );
		initLabels();
		initFloating();
		initPin();
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
