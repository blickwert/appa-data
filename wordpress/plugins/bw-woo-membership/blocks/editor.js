/* Editor UI for the member area blocks – no build step; the blocks render on the server for the current user. */
( function ( wp ) {
	const { createElement: el } = wp.element;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, RangeControl, Notice } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const names = [ 'user', 'overview', 'events', 'profile', 'documents', 'proof', 'join-proof', 'event-registration' ];

	names.forEach( ( name ) => {
		wp.blocks.registerBlockType( 'bw-woo-membership/' + name, {
			edit( { attributes, setAttributes } ) {
				const controls = attributes.limit !== undefined
					? el( InspectorControls, null, el( PanelBody, { title: 'Anzeige' }, el( RangeControl, {
						label: 'Anzahl',
						min: 1,
						max: 12,
						value: attributes.limit,
						onChange: ( limit ) => setAttributes( { limit } ),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} ) ) )
					: null;
				return el(
					'div',
					useBlockProps(),
					controls,
					el( Notice, { status: 'info', isDismissible: false }, 'Vorschau mit deinen Daten – Mitglieder sehen hier ihre eigenen.' ),
					el( ServerSideRender, { block: 'bw-woo-membership/' + name, attributes } )
				);
			},
			save: () => null,
		} );
	} );
} )( window.wp );
