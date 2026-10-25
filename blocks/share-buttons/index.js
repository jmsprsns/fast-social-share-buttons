/**
 * Social Share Buttons block (editor side).
 *
 * Plain JavaScript, no build step. The block is rendered by PHP; the editor
 * shows a live server-side preview. Network names and option labels come
 * from PHP as window.fssbBlock (see Fast_Social_Share_Buttons_Block).
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var CheckboxControl = wp.components.CheckboxControl;
	var TextControl = wp.components.TextControl;
	var Disabled = wp.components.Disabled;
	var ServerSideRender = wp.serverSideRender;

	var data = window.fssbBlock || { networks: {}, choices: {} };
	var networkKeys = Object.keys( data.networks );

	function choiceControl( props, key, label ) {
		var choices = data.choices[ key ] || {};
		var options = [ { value: '', label: __( 'Default (from settings)', 'fast-social-share-buttons' ) } ];

		Object.keys( choices ).forEach( function ( value ) {
			options.push( { value: value, label: choices[ value ] } );
		} );

		return el( SelectControl, {
			label: label,
			value: props.attributes[ key ],
			options: options,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
			onChange: function ( value ) {
				var change = {};
				change[ key ] = value;
				props.setAttributes( change );
			},
		} );
	}

	function networkControls( props ) {
		var selected = props.attributes.networks;

		return networkKeys.map( function ( key ) {
			return el( CheckboxControl, {
				key: key,
				label: data.networks[ key ],
				checked: selected.indexOf( key ) !== -1,
				__nextHasNoMarginBottom: true,
				onChange: function ( checked ) {
					// Keep the plugin's network order, whatever order they're ticked in.
					var next = networkKeys.filter( function ( k ) {
						return k === key ? checked : selected.indexOf( k ) !== -1;
					} );
					props.setAttributes( { networks: next } );
				},
			} );
		} );
	}

	wp.blocks.registerBlockType( 'fast-social-share-buttons/share-buttons', {
		edit: function ( props ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Networks', 'fast-social-share-buttons' ) },
						el( 'p', null, __( 'Tick none to use the networks from the plugin settings.', 'fast-social-share-buttons' ) ),
						networkControls( props )
					),
					el(
						PanelBody,
						{ title: __( 'Appearance', 'fast-social-share-buttons' ) },
						el( TextControl, {
							label: __( 'Heading', 'fast-social-share-buttons' ),
							value: props.attributes.heading,
							__nextHasNoMarginBottom: true,
							__next40pxDefaultSize: true,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} ),
						choiceControl( props, 'layout', __( 'Layout', 'fast-social-share-buttons' ) ),
						choiceControl( props, 'shape', __( 'Shape', 'fast-social-share-buttons' ) ),
						choiceControl( props, 'colors', __( 'Colours', 'fast-social-share-buttons' ) ),
						choiceControl( props, 'size', __( 'Size', 'fast-social-share-buttons' ) ),
						choiceControl( props, 'labels', __( 'Labels', 'fast-social-share-buttons' ) ),
						choiceControl( props, 'align', __( 'Alignment', 'fast-social-share-buttons' ) )
					)
				),
				el(
					Disabled,
					null,
					el( ServerSideRender, {
						block: 'fast-social-share-buttons/share-buttons',
						attributes: props.attributes,
					} )
				)
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
