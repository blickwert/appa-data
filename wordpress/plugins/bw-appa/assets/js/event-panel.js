/*
 * Event data in the block editor: an own sidebar "Veranstaltung" (calendar icon in the top bar) that opens when an
 * event is loaded, plus a short panel in the document settings with a button to it. No build step, wp.* globals.
 */
( function ( wp, config ) {
	const { createElement: el, Fragment, useEffect } = wp.element;
	const { __, sprintf } = wp.i18n;
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
		const places = '' === info.capacity ? __( 'unbegrenzt', 'bw-appa' ) : info.capacity;
		return el(
			PanelBody,
			{ title: __( 'Teilnehmende', 'bw-appa' ), initialOpen: true },
			el( 'p', { style: { fontSize: '15px', margin: '0 0 8px' } },
				/* translators: %s: number of registrations */
				el( 'strong', null, sprintf( __( '%s angemeldet', 'bw-appa' ), info.registered ) ),
				/* translators: %s: number of places */
				' · ' + sprintf( __( 'Plätze: %s', 'bw-appa' ), places ) +
					/* translators: %s: free places */
					( null !== info.left ? ' · ' + sprintf( __( 'frei: %s', 'bw-appa' ), info.left ) : '' )
			),
			/* translators: %s: people on the waiting list */
			info.waitlist > 0 && el( 'p', { style: { margin: '0 0 8px' } }, sprintf( __( 'Warteliste: %s', 'bw-appa' ), info.waitlist ) ),
			el( Button, { variant: 'secondary', href: info.url }, __( 'Teilnehmende ansehen und hinzufügen', 'bw-appa' ) )
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
			! value( 'date' ) && el( Notice, { status: 'warning', isDismissible: false }, __( 'Bitte ein Datum eintragen – erst dann erscheint die Veranstaltung auf der Website.', 'bw-appa' ) ),
			el( Participants ),
			el( PanelBody, { title: __( 'Termin', 'bw-appa' ), initialOpen: true },
				grid(
					field( 'date', __( 'Datum', 'bw-appa' ), 'date', __( 'Termine ab heute werden auf der Website angezeigt.', 'bw-appa' ) ),
					field( 'time', __( 'Beginn', 'bw-appa' ), 'time' ),
					field( 'endTime', __( 'Ende', 'bw-appa' ), 'time' ),
					field( 'endDate', __( 'Letzter Tag (bei mehrtägigen Veranstaltungen)', 'bw-appa' ), 'date' ),
					el( SelectControl, {
						label: __( 'Format', 'bw-appa' ),
						value: format,
						options: [ { value: '', label: __( '– bitte wählen –', 'bw-appa' ) } ].concat(
							Object.keys( config.formats ).map( ( k ) => ( { value: k, label: config.formats[ k ] } ) )
						),
						onChange: set( 'format' ),
						...common,
					} ),
					'online' !== format && field( 'location', __( 'Ort', 'bw-appa' ), 'text', __( 'Zum Beispiel: Universität Graz, Hörsaal 12.', 'bw-appa' ) ),
					online && field( 'link', __( 'Zoom-Link', 'bw-appa' ), 'url', __( 'Nie in Übersichten. Sehen nur Angemeldete (ohne Anmeldung: Mitglieder) auf der Veranstaltungsseite, in „Meine Anmeldungen“ und in der Erinnerungs-E-Mail.', 'bw-appa' ) ),
					online && field( 'access', __( 'Zoom-Zugangsdaten', 'bw-appa' ), 'text', __( 'Zum Beispiel Meeting-ID und Kenncode.', 'bw-appa' ) )
				)
			),
			el( PanelBody, { title: __( 'Anmeldung und Preise', 'bw-appa' ), initialOpen: true },
				grid(
					el( SelectControl, {
						label: __( 'Art der Veranstaltung', 'bw-appa' ),
						value: audience,
						// "Für Mitglieder gratis" is not offered at the moment (answer V4); kept for events that use it.
						options: Object.keys( config.audiences )
							.filter( ( k ) => 'free_members' !== k || 'free_members' === audience )
							.map( ( k ) => ( { value: k, label: config.audiences[ k ] } ) ),
						onChange: set( 'audience' ),
						...common,
					} ),
					el( ToggleControl, {
						label: __( 'Anmeldung über die Website', 'bw-appa' ),
						help: __( 'Anmeldung mit Bestätigung, Erinnerung und Abmeldung; kostenpflichtig über die Kasse.', 'bw-appa' ),
						checked: registration,
						onChange: ( on ) => set( 'registration' )( on ? '1' : '' ),
						__nextHasNoMarginBottom: true,
					} ),
					registration && field( 'priceMember', __( 'Preis für Mitglieder (€)', 'bw-appa' ), 'text', __( '0 = kostenlos (Anmeldung mit einem Klick).', 'bw-appa' ) ),
					registration && 'members' !== audience && field( 'pricePublic', __( 'Preis für Nichtmitglieder (€)', 'bw-appa' ), 'text' ),
					registration && field( 'capacity', __( 'Plätze (maximal)', 'bw-appa' ), 'number', __( 'Leer = unbegrenzt. Ist alles ausgebucht, können sich Interessierte auf die Warteliste setzen.', 'bw-appa' ) ),
					registration && field( 'deadline', __( 'Anmeldeschluss', 'bw-appa' ), 'date', __( 'Letzter Tag der Anmeldung. Leer = bis zum Termin.', 'bw-appa' ) ),
					registration && field( 'reminder', __( 'Erinnerung (Tage vorher)', 'bw-appa' ), 'number', __( 'E-Mail an alle Angemeldeten. Leer = 2 Tage, 0 = keine Erinnerung.', 'bw-appa' ) ),
					registration && field( 'cancel', __( 'Selbst abmelden bis (Tage vorher)', 'bw-appa' ), 'number', __( 'Bei kostenpflichtigen Anmeldungen mit voller Rückerstattung. Leer = 14 Tage. Kostenlose Anmeldungen können bis zum Termin abgemeldet werden.', 'bw-appa' ) )
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
			PluginSidebarMoreMenuItem && el( PluginSidebarMoreMenuItem, { target: 'bw-appa-event-sidebar', icon: 'calendar-alt' }, __( 'Veranstaltung: Termin und Anmeldung', 'bw-appa' ) ),
			el( PluginSidebar, { name: 'bw-appa-event-sidebar', title: __( 'Termin und Anmeldung', 'bw-appa' ), icon: 'calendar-alt' }, el( Fields, { meta, setMeta } ) ),
			PluginDocumentSettingPanel && el(
				PluginDocumentSettingPanel,
				{ name: 'bw-appa-event-link', title: __( 'Termin und Anmeldung', 'bw-appa' ) },
				el( 'p', null, __( 'Datum, Ort, Preise, Plätze und Teilnehmende bearbeitest du in der eigenen Seitenleiste (Kalender-Symbol oben rechts).', 'bw-appa' ) ),
				el( Button, { variant: 'primary', onClick: openSidebar }, __( 'Termin und Anmeldung öffnen', 'bw-appa' ) )
			)
		);
	}

	wp.plugins.registerPlugin( 'bw-appa-event', { render: EventSidebar } );
} )( window.wp, window.bwAppaEvent );
