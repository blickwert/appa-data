/* Editor UI for bw-appa/form – no build step, uses the wp.* globals. */
( function ( wp ) {
	const { createElement: el } = wp.element;
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
						{ title: 'Formular' },
						el( SelectControl, {
							label: 'Formular',
							value: attributes.form,
							options: [
								{ label: 'Kontakt', value: 'contact' },
								{ label: 'Mitgliedsantrag', value: 'membership' },
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
