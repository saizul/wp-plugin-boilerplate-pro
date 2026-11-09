/* global wp */
( function ( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'wp-plugin-boilerplate-pro/items', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var set = props.setAttributes;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Items', 'wp-plugin-boilerplate-pro' ) },
						el( RangeControl, {
							label: __( 'Number of items', 'wp-plugin-boilerplate-pro' ),
							value: attributes.count || 10,
							min: 1,
							max: 100,
							onChange: function ( value ) {
								set( { count: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Layout', 'wp-plugin-boilerplate-pro' ),
							value: attributes.layout || 'list',
							options: [
								{ label: __( 'List', 'wp-plugin-boilerplate-pro' ), value: 'list' },
								{ label: __( 'Grid', 'wp-plugin-boilerplate-pro' ), value: 'grid' },
							],
							onChange: function ( value ) {
								set( { layout: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Search term', 'wp-plugin-boilerplate-pro' ),
							value: attributes.search,
							onChange: function ( value ) {
								set( { search: value } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'wp-plugin-boilerplate-pro/items',
					attributes: attributes,
				} )
			);
		},
		save: function () {
			return null; // Dynamic block: rendered by PHP.
		},
	} );
} )( window.wp );
