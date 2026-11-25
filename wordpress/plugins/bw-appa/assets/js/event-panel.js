/*
 * Event data in the block editor: an own sidebar "Veranstaltung" (calendar icon in the top bar) that opens when an
 * event is loaded, plus a short panel in the document settings with a button to it. No build step, wp.* globals.
 */
( function ( wp, config ) {
	const { createElement: el, Fragment, useEffect } = wp.element;
	const editor = wp.editor || {};
	const editPost = wp.editPost || {};
	const PluginSidebar = editor.PluginSidebar || editPost.PluginSidebar;
	const PluginSidebarMoreMenuItem = editor.PluginSidebarMoreMenuItem || editPost.PluginSidebarMoreMenuItem;
	const PluginDocumentSettingPanel = editor.PluginDocumentSettingPanel || editPost.PluginDocumentSettingPanel;
	const { TextControl, SelectControl, ToggleControl, PanelBody, Button, Notice } = wp.components;
	const { useSelect, dispatch } = wp.data;
	const { useEntityProp } = wp.coreData;
	const keys = config.keys;
	const info = config.info || {};
	const common = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true };
	const SIDEBAR = 'bw-appa-event/bw-appa-event-sidebar';

	function openSidebar() {
		const area = dispatch( 'core/interface' );
		if ( area && area.enableComplementaryArea ) {
			area.enableComplementaryArea( 'core', SIDEBAR );
		} else if ( dispatch( 'core/edit-post' ) ) {
			dispatch( 'core/edit-post' ).openGeneralSidebar( SIDEBAR );
		}
	}

	function useEvent() {
		const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
		const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
		return { postType, meta, setMeta };
	}

	function Participants() {
		if ( ! info.enabled ) {
			return null;
		}
		const places = '' === info.capacity ? 'unbegrenzt' : info.capacity;
		return el(
			PanelBody,
			{ title: 'Teilnehmende', initialOpen: true },
			el( 'p', { style: { fontSize: '15px', margin: '0 0 8px' } },
				el( 'strong', null, info.registered + ' angemeldet' ),
				' · Plätze: ' + places + ( null !== info.left ? ' · frei: ' + info.left : '' )
			),
			info.waitlist > 0 && el( 'p', { style: { margin: '0 0 8px' } }, 'Warteliste: ' + info.waitlist ),
			el( Button, { variant: 'secondary', href: info.url }, 'Teilnehmende ansehen und hinzufügen' )
		);
	}

	function Fields( { meta, setMeta } ) {
		const value = ( key ) => meta[ keys[ key ] ] || '';
		const set = ( key ) => ( next ) => setMeta( { ...meta, [ keys[ key ] ]: next } );
		const field = ( key, label, type, help ) =>
			el( TextControl, { label, type, help, value: value( key ), onChange: set( key ), ...common } );
		const audience = value( 'audience' ) || 'open';
		const registration = '1' === value( 'registration' );
		const format = value( 'format' );
		const online = 'online' === format || 'hybrid' === format || ( '' === format && '' !== value( 'link' ) );
		const grid = ( ...children ) => el( 'div', { style: { display: 'grid', gap: '16px' } }, ...children );

		return el(
			Fragment,
			null,
			! value( 'date' ) && el( Notice, { status: 'warning', isDismissible: false }, 'Bitte ein Datum eintragen – erst dann erscheint die Veranstaltung auf der Website.' ),
			el( Participants ),
			el( PanelBody, { title: 'Termin', initialOpen: true },
				grid(
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
			el( PanelBody, { title: 'Anmeldung und Preise', initialOpen: true },
				grid(
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
						help: 'Anmeldung mit Bestätigung, Erinnerung und Abmeldung; kostenpflichtig über die Kasse.',
						checked: registration,
						onChange: ( on ) => set( 'registration' )( on ? '1' : '' ),
						__nextHasNoMarginBottom: true,
					} ),
					registration && field( 'priceMember', 'Preis für Mitglieder (€)', 'text', '0 = kostenlos (Anmeldung mit einem Klick).' ),
					registration && 'members' !== audience && field( 'pricePublic', 'Preis für Nichtmitglieder (€)', 'text' ),
					registration && field( 'capacity', 'Plätze (maximal)', 'number', 'Leer = unbegrenzt. Ist alles ausgebucht, können sich Interessierte auf die Warteliste setzen.' ),
					registration && field( 'deadline', 'Anmeldeschluss', 'date', 'Letzter Tag der Anmeldung. Leer = bis zum Termin.' ),
					registration && field( 'reminder', 'Erinnerung (Tage vorher)', 'number', 'E-Mail an alle Angemeldeten. Leer = 2 Tage, 0 = keine Erinnerung.' ),
					registration && field( 'cancel', 'Selbst abmelden bis (Tage vorher)', 'number', 'Bei kostenpflichtigen Anmeldungen mit voller Rückerstattung. Leer = 14 Tage. Kostenlose Anmeldungen können bis zum Termin abgemeldet werden.' )
				)
			)
		);
	}

	function EventSidebar() {
		const { postType, meta, setMeta } = useEvent();
		// Open the sidebar once per page load, so the fields are in view right away.
		useEffect( () => {
			if ( 'appa_event' === postType && ! window.bwAppaEventSidebarOpened ) {
				window.bwAppaEventSidebarOpened = true;
				// The editor opens its document sidebar while it starts; switch to ours once that has happened.
				[ 300, 1200, 2500 ].forEach( ( delay ) =>
					setTimeout( () => {
						const area = wp.data.select( 'core/interface' );
						const active = area && area.getActiveComplementaryArea ? area.getActiveComplementaryArea( 'core' ) : null;
						if ( ! window.bwAppaEventSidebarSettled && active !== SIDEBAR ) {
							openSidebar();
						} else if ( active === SIDEBAR ) {
							window.bwAppaEventSidebarSettled = delay >= 1200;
						}
					}, delay )
				);
			}
		}, [ postType ] );
		if ( 'appa_event' !== postType || ! meta ) {
			return null;
		}
		return el(
			Fragment,
			null,
			PluginSidebarMoreMenuItem && el( PluginSidebarMoreMenuItem, { target: 'bw-appa-event-sidebar', icon: 'calendar-alt' }, 'Veranstaltung: Termin und Anmeldung' ),
			el( PluginSidebar, { name: 'bw-appa-event-sidebar', title: 'Termin und Anmeldung', icon: 'calendar-alt' }, el( Fields, { meta, setMeta } ) ),
			PluginDocumentSettingPanel && el(
				PluginDocumentSettingPanel,
				{ name: 'bw-appa-event-link', title: 'Termin und Anmeldung' },
				el( 'p', null, 'Datum, Ort, Preise, Plätze und Teilnehmende bearbeitest du in der eigenen Seitenleiste (Kalender-Symbol oben rechts).' ),
				el( Button, { variant: 'primary', onClick: openSidebar }, 'Termin und Anmeldung öffnen' )
			)
		);
	}

	wp.plugins.registerPlugin( 'bw-appa-event', { render: EventSidebar } );
} )( window.wp, window.bwAppaEvent );
