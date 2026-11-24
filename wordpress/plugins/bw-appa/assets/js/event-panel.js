/* "Termin" and "Anmeldung und Preise" panels for events – no build step, uses the wp.* globals. */
( function ( wp, config ) {
	const { createElement: el, Fragment } = wp.element;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { TextControl, SelectControl, ToggleControl } = wp.components;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const keys = config.keys;
	const common = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true };

	function EventPanels() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		if ( 'appa_event' !== postType || ! meta ) {
			return null;
		}
		const value = ( key ) => meta[ keys[ key ] ] || '';
		const set = ( key ) => ( next ) => setMeta( { ...meta, [ keys[ key ] ]: next } );
		const field = ( key, label, type, help ) =>
			el( TextControl, { label, type, help, value: value( key ), onChange: set( key ), ...common } );
		const audience = value( 'audience' ) || 'open';
		const registration = '1' === value( 'registration' );

		return el(
			Fragment,
			null,
			el(
				PluginDocumentSettingPanel,
				{ name: 'bw-appa-event', title: 'Termin', className: 'bw-appa-event-panel' },
				el( 'div', { style: { display: 'grid', gap: '16px' } },
					field( 'date', 'Datum', 'date', 'Termine ab heute werden auf der Website angezeigt.' ),
					field( 'time', 'Uhrzeit', 'time' ),
					field( 'link', 'Zoom-Link', 'url', 'Sehen nur Berechtigte: bei Anmeldung die Angemeldeten, sonst Mitglieder.' ),
					field( 'access', 'Zoom-Zugangsdaten', 'text', 'Zum Beispiel Meeting-ID und Kenncode.' )
				)
			),
			el(
				PluginDocumentSettingPanel,
				{ name: 'bw-appa-event-registration', title: 'Anmeldung und Preise', className: 'bw-appa-event-panel' },
				el( 'div', { style: { display: 'grid', gap: '16px' } },
					el( SelectControl, {
						label: 'Art der Veranstaltung',
						value: audience,
						options: Object.keys( config.audiences ).map( ( k ) => ( { value: k, label: config.audiences[ k ] } ) ),
						onChange: set( 'audience' ),
						...common,
					} ),
					el( ToggleControl, {
						label: 'Anmeldung über die Website',
						help: 'Legt die Anmeldung mit Bezahlung über die Kasse an (Plugin „APPA Mitgliedschaft“).',
						checked: registration,
						onChange: ( on ) => set( 'registration' )( on ? '1' : '' ),
						__nextHasNoMarginBottom: true,
					} ),
					registration && field( 'priceMember', 'Preis für Mitglieder (€)', 'text', 'free_members' === audience ? 'Für Mitglieder gratis: 0 eintragen.' : '' ),
					registration && 'members' !== audience && field( 'pricePublic', 'Preis für Nichtmitglieder (€)', 'text' ),
					registration && field( 'capacity', 'Plätze', 'number', 'Leer = unbegrenzt.' )
				)
			)
		);
	}

	wp.plugins.registerPlugin( 'bw-appa-event', { render: EventPanels } );
} )( window.wp, window.bwAppaEvent );
