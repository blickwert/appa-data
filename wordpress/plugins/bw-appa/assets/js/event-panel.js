/* "Termin" panel for events – no build step, uses the wp.* globals. */
( function ( wp, config ) {
	const { createElement: el } = wp.element;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { TextControl } = wp.components;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const keys = config.keys;

	function EventPanel() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		if ( 'appa_event' !== postType || ! meta ) {
			return null;
		}
		const field = ( key, label, type, help ) =>
			el( TextControl, {
				label,
				type,
				help,
				value: meta[ keys[ key ] ] || '',
				onChange: ( value ) => setMeta( { ...meta, [ keys[ key ] ]: value } ),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true,
			} );

		return el(
			PluginDocumentSettingPanel,
			{ name: 'bw-appa-event', title: 'Termin', className: 'bw-appa-event-panel' },
			el( 'div', { style: { display: 'grid', gap: '16px' } },
				field( 'date', 'Datum', 'date', 'Termine ab heute werden auf der Website angezeigt.' ),
				field( 'time', 'Uhrzeit', 'time' ),
				field( 'link', 'Zoom-Link', 'url' ),
				field( 'access', 'Zoom-Zugangsdaten', 'text', 'Zum Beispiel Meeting-ID und Kenncode.' )
			)
		);
	}

	wp.plugins.registerPlugin( 'bw-appa-event', { render: EventPanel } );
} )( window.wp, window.bwAppaEvent );
