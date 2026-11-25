/* Editor UI for bw-appa/form – no build step, uses the wp.* globals. */
( function ( wp ) {
	const { createElement: el } = wp.element;
	const { __ } = wp.i18n;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, Disabled } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'bw-appa/form', {
		edit( { attributes, setAttributes } ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Formular', 'bw-appa' ) },
						el( SelectControl, {
							label: __( 'Formular', 'bw-appa' ),
							value: attributes.form,
							options: [
								{ label: __( 'Kontakt', 'bw-appa' ), value: 'contact' },
								{ label: __( 'Mitgliedsantrag', 'bw-appa' ), value: 'membership' },
							],
							onChange: ( form ) => setAttributes( { form } ),
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( Disabled, null, el( ServerSideRender, { block: 'bw-appa/form', attributes } ) )
			);
		},
		save: () => null,
	} );
} )( window.wp );
