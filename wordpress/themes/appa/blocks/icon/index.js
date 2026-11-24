/* Editor UI for bw/icon – no build step, uses the wp.* globals. */
( function ( wp ) {
	const { createElement: el } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const icons = window.bwAppaIcons || [];

	wp.blocks.registerBlockType( 'bw/icon', {
		edit( { attributes, setAttributes } ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Icon' },
						el( SelectControl, {
							label: 'Symbol',
							value: attributes.icon,
							options: icons.map( ( name ) => ( { label: name, value: name } ) ),
							onChange: ( icon ) => setAttributes( { icon } ),
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: 'Größe',
							value: attributes.size,
							options: [ { label: 'Klein', value: 's' }, { label: 'Mittel', value: 'm' }, { label: 'Groß', value: 'l' } ],
							onChange: ( size ) => setAttributes( { size } ),
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} ),
						el( SelectControl, {
							label: 'Farbe',
							value: attributes.tone,
							options: [
								{ label: 'Rot', value: 'red' },
								{ label: 'Navy', value: 'navy' },
								{ label: 'Grün', value: 'green' },
								{ label: 'Gold', value: 'gold' },
								{ label: 'Blau', value: 'blue' },
							],
							onChange: ( tone ) => setAttributes( { tone } ),
							__next40pxDefaultSize: true,
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( ServerSideRender, { block: 'bw/icon', attributes } )
			);
		},
		save: () => null,
	} );
} )( window.wp );
