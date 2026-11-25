/* "Termin" and "Anmeldung und Preise" panels for events (opened on load) – no build step, uses the wp.* globals. */
( function ( wp, config ) {
	const { createElement: el, Fragment } = wp.element;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { TextControl, SelectControl, ToggleControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { useEffect } = wp.element;
	const { useEntityProp } = wp.coreData;
	const keys = config.keys;
	const common = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true };
	const PANELS = [ 'bw-appa-event/bw-appa-event', 'bw-appa-event/bw-appa-event-registration' ];

	function EventPanels() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		const opened = useSelect( ( select ) => select( 'core/editor' ).isEditorPanelOpened( PANELS[ 0 ] ), [] );
		const { toggleEditorPanelOpened } = useDispatch( 'core/editor' );
		// Open both panels once per page load, so the fields are visible without searching the sidebar.
		useEffect( () => {
			if ( 'appa_event' === postType && ! opened && ! window.bwAppaEventPanelsOpened ) {
				window.bwAppaEventPanelsOpened = true;
				PANELS.forEach( ( id ) => toggleEditorPanelOpened( id ) );
			}
		}, [ postType ] );
		if ( 'appa_event' !== postType || ! meta ) {
			return null;
		}
		const value = ( key ) => meta[ keys[ key ] ] || '';
		const set = ( key ) => ( next ) => setMeta( { ...meta, [ keys[ key ] ]: next } );
		const field = ( key, label, type, help ) =>
			el( TextControl, { label, type, help, value: value( key ), onChange: set( key ), ...common } );
		const audience = value( 'audience' ) || 'open';
		const format = value( 'format' );
		const online = 'online' === format || 'hybrid' === format || ( '' === format && '' !== value( 'link' ) );
		const registration = '1' === value( 'registration' );

		return el(
			Fragment,
			null,
			el(
				PluginDocumentSettingPanel,
				{ name: 'bw-appa-event', title: 'Termin', className: 'bw-appa-event-panel' },
				el( 'div', { style: { display: 'grid', gap: '16px' } },
					field( 'date', 'Datum', 'date', 'Termine ab heute werden auf der Website angezeigt.' ),
					field( 'time', 'Beginn', 'time' ),
					field( 'endTime', 'Ende', 'time' ),
					field( 'endDate', 'Letzter Tag (bei mehrtägigen Veranstaltungen)', 'date' ),
					el( SelectControl, {
						label: 'Format',
						value: format,
						options: [ { value: '', label: '– bitte wählen –' } ].concat(
							Object.keys( config.formats ).map( ( k ) => ( { value: k, label: config.formats[ k ] } ) )
						),
						onChange: set( 'format' ),
						...common,
					} ),
					'online' !== format && field( 'location', 'Ort', 'text', 'Zum Beispiel: Universität Graz, Hörsaal 12.' ),
					online && field( 'link', 'Zoom-Link', 'url', 'Nie in Übersichten. Sehen nur Angemeldete (ohne Anmeldung: Mitglieder) auf der Veranstaltungsseite, in „Meine Anmeldungen“ und in der Erinnerungs-E-Mail.' ),
					online && field( 'access', 'Zoom-Zugangsdaten', 'text', 'Zum Beispiel Meeting-ID und Kenncode.' )
				)
			),
			el(
				PluginDocumentSettingPanel,
				{ name: 'bw-appa-event-registration', title: 'Anmeldung und Preise', className: 'bw-appa-event-panel' },
				el( 'div', { style: { display: 'grid', gap: '16px' } },
					el( SelectControl, {
						label: 'Art der Veranstaltung',
						value: audience,
						// "Für Mitglieder gratis" is not offered at the moment (answer V4); kept for events that use it.
						options: Object.keys( config.audiences )
							.filter( ( k ) => 'free_members' !== k || 'free_members' === audience )
							.map( ( k ) => ( { value: k, label: config.audiences[ k ] } ) ),
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
					registration && field( 'capacity', 'Plätze', 'number', 'Leer = unbegrenzt. Ist alles ausgebucht, können sich Interessierte auf die Warteliste setzen.' ),
					registration && field( 'deadline', 'Anmeldeschluss', 'date', 'Letzter Tag der Anmeldung. Leer = bis zum Termin.' ),
					registration && field( 'reminder', 'Erinnerung (Tage vorher)', 'number', 'E-Mail an alle Angemeldeten. Leer = 2 Tage, 0 = keine Erinnerung.' ),
					registration && field( 'cancel', 'Selbst abmelden bis (Tage vorher)', 'number', 'Bei kostenpflichtigen Anmeldungen mit voller Rückerstattung. Leer = 14 Tage. Kostenlose Anmeldungen können bis zum Termin abgemeldet werden.' )
				)
			)
		);
	}

	wp.plugins.registerPlugin( 'bw-appa-event', { render: EventPanels } );
} )( window.wp, window.bwAppaEvent );
