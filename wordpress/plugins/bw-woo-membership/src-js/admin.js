/**
 * Admin app "Mitglieder": member list, member details, new manual member, proof review.
 * WordPress packages only; data via the REST routes bw-wm/v1.
 */
import { createRoot, useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	CheckboxControl,
	Notice,
	SearchControl,
	SelectControl,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';

const config = window.bwWmAdmin || {};
const field = { __next40pxDefaultSize: true, __nextHasNoMarginBottom: true };

/* Routing: ?page=bw-wm-members&path=/… – the same address PHP builds, no reload inside the app. */
function currentPath() {
	return new URLSearchParams( window.location.search ).get( 'path' ) || '/';
}
function useRoute() {
	const [ path, setPath ] = useState( currentPath() );
	useEffect( () => {
		const onPop = () => setPath( currentPath() );
		window.addEventListener( 'popstate', onPop );
		return () => window.removeEventListener( 'popstate', onPop );
	}, [] );
	const go = useCallback( ( next ) => {
		window.history.pushState( {}, '', addQueryArgs( config.page, next === '/' ? {} : { path: next } ) );
		setPath( next );
		window.scrollTo( 0, 0 );
	}, [] );
	return [ path, go ];
}

function Header( { title, children } ) {
	return (
		<div className="bwo-header">
			<h1 className="wp-heading-inline">{ title }</h1>
			<div className="bwo-header__actions">{ children }</div>
		</div>
	);
}

function StatusBadge( { status, label } ) {
	return <span className={ `bwo-badge bwo-badge--${ status }` }>{ label }</span>;
}

function ErrorNotice( { error } ) {
	return error ? <Notice status="error" isDismissible={ false }>{ error }</Notice> : null;
}

/* ── List ──────────────────────────────────────────────────────────────── */
const STATUS_FILTERS = [
	[ '', __( 'Alle', 'bw-woo-membership' ), 'all' ],
	[ 'active', __( 'Aktiv', 'bw-woo-membership' ), 'active' ],
	[ 'pending', __( 'Zahlung offen', 'bw-woo-membership' ), 'pending' ],
	[ 'on-hold', __( 'Pausiert', 'bw-woo-membership' ), 'on-hold' ],
	[ 'expired', __( 'Abgelaufen', 'bw-woo-membership' ), 'expired' ],
];

/**
 * Fee journal of a period for the bookkeeping (CSV download); default: previous month.
 */
function JournalExport() {
	const day = ( d ) => `${ d.getFullYear() }-${ String( d.getMonth() + 1 ).padStart( 2, '0' ) }-${ String( d.getDate() ).padStart( 2, '0' ) }`;
	const now = new Date();
	const [ from, setFrom ] = useState( day( new Date( now.getFullYear(), now.getMonth() - 1, 1 ) ) );
	const [ to, setTo ] = useState( day( new Date( now.getFullYear(), now.getMonth(), 0 ) ) );
	const lastYear = () => {
		setFrom( `${ now.getFullYear() - 1 }-01-01` );
		setTo( `${ now.getFullYear() - 1 }-12-31` );
	};
	return (
		<span className="bwo-journal">
			<span className="bwo-journal__label">{ __( 'Beitragsjournal', 'bw-woo-membership' ) }</span>
			<label htmlFor="bwo-journal-from">{ __( 'von', 'bw-woo-membership' ) }</label>
			<input id="bwo-journal-from" type="date" value={ from } max={ to } onChange={ ( e ) => setFrom( e.target.value ) } />
			<label htmlFor="bwo-journal-to">{ __( 'bis', 'bw-woo-membership' ) }</label>
			<input id="bwo-journal-to" type="date" value={ to } min={ from } onChange={ ( e ) => setTo( e.target.value ) } />
			<Button variant="link" onClick={ lastYear }>{ __( 'Vorjahr', 'bw-woo-membership' ) }</Button>
			<Button variant="secondary" href={ addQueryArgs( config.journalUrl, { from, to } ) }>{ __( 'Herunterladen (CSV)', 'bw-woo-membership' ) }</Button>
		</span>
	);
}

function MemberList( { go } ) {
	const [ status, setStatus ] = useState( '' );
	const [ type, setType ] = useState( '' );
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ data, setData ] = useState( null );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		const timer = setTimeout( () => {
			apiFetch( { path: addQueryArgs( '/bw-wm/v1/members', { status, type, search, page, per_page: 50 } ) } )
				.then( ( result ) => {
					setData( result );
					setError( '' );
				} )
				.catch( ( e ) => setError( e.message ) );
		}, 250 );
		return () => clearTimeout( timer );
	}, [ status, type, search, page ] );

	const pages = data ? Math.max( 1, Math.ceil( data.total / 50 ) ) : 1;

	return (
		<>
			<Header title={ __( 'Mitglieder', 'bw-woo-membership' ) }>
				<Button variant="secondary" onClick={ () => go( '/new' ) }>{ __( 'Ehren- oder institutionelles Mitglied anlegen', 'bw-woo-membership' ) }</Button>
				<Button variant="secondary" href={ config.exportUrl }>{ __( 'Export (CSV)', 'bw-woo-membership' ) }</Button>
				<JournalExport />
			</Header>
			<ErrorNotice error={ error } />
			{ data && data.counts.proofs > 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ /* translators: %d: number of proofs */ sprintf( __( '%d Studiennachweis(e) warten auf Prüfung.', 'bw-woo-membership' ), data.counts.proofs ) }{ ' ' }
					<Button variant="link" onClick={ () => go( '/proofs' ) }>{ __( 'Nachweise prüfen', 'bw-woo-membership' ) }</Button>
				</Notice>
			) }
			<div className="bwo-toolbar">
				<ul className="subsubsub">
					{ STATUS_FILTERS.map( ( [ value, label, key ], index ) => (
						<li key={ key }>
							<Button variant="link" className={ status === value ? 'current' : '' } onClick={ () => { setStatus( value ); setPage( 1 ); } }>
								{ label } <span className="count">({ data ? data.counts[ key ] ?? 0 : '…' })</span>
							</Button>
							{ index < STATUS_FILTERS.length - 1 ? ' | ' : '' }
						</li>
					) ) }
				</ul>
				<div className="bwo-filters">
					<SelectControl
						label={ __( 'Mitgliedsart', 'bw-woo-membership' ) }
						hideLabelFromVision
						value={ type }
						options={ [
							{ value: '', label: __( 'Alle Mitgliedsarten', 'bw-woo-membership' ) },
							{ value: 'regular', label: __( 'Ordentlich', 'bw-woo-membership' ) },
							{ value: 'student', label: __( 'Studierende', 'bw-woo-membership' ) },
							{ value: 'honorary', label: __( 'Ehrenmitglied', 'bw-woo-membership' ) },
							{ value: 'institutional', label: __( 'Institutionell', 'bw-woo-membership' ) },
						] }
						onChange={ ( v ) => { setType( v ); setPage( 1 ); } }
						{ ...field }
					/>
					<SearchControl label={ __( 'Mitglieder durchsuchen', 'bw-woo-membership' ) } value={ search } onChange={ ( v ) => { setSearch( v ); setPage( 1 ); } } placeholder={ __( 'Name oder E-Mail', 'bw-woo-membership' ) } __nextHasNoMarginBottom />
				</div>
			</div>
			{ ! data ? <Spinner /> : (
				<table className="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style={ { width: '7em' } }>{ __( 'Nr.', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Name', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Mitgliedsart', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Status', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Mitglied seit', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Verlängerung / gültig bis', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Studiennachweis', 'bw-woo-membership' ) }</th>
							<th>{ __( 'Verzeichnis', 'bw-woo-membership' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ data.items.length === 0 && <tr><td colSpan="8">{ __( 'Keine Mitglieder gefunden.', 'bw-woo-membership' ) }</td></tr> }
						{ data.items.map( ( m ) => (
							<tr key={ m.id }>
								<td>{ m.number || '–' }</td>
								<td>
									<Button variant="link" onClick={ () => go( `/member/${ m.id }` ) }><strong>{ m.name }</strong></Button>
									<div className="bwo-muted">{ m.email }</div>
								</td>
								<td>{ m.type || '–' }</td>
								<td><StatusBadge status={ m.status } label={ m.status_l } /></td>
								<td>{ m.since || '–' }</td>
								<td>{ m.renewal || '–' }</td>
								<td>{ m.proof }</td>
								<td>{ m.directory ? __( 'sichtbar', 'bw-woo-membership' ) : '–' }</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
			{ data && pages > 1 && (
				<div className="bwo-pager">
					<Button variant="secondary" size="compact" disabled={ page <= 1 } onClick={ () => setPage( page - 1 ) }>{ __( '‹ Zurück', 'bw-woo-membership' ) }</Button>
					<span>{ /* translators: 1: page, 2: number of pages */ sprintf( __( 'Seite %1$d von %2$d', 'bw-woo-membership' ), page, pages ) }</span>
					<Button variant="secondary" size="compact" disabled={ page >= pages } onClick={ () => setPage( page + 1 ) }>{ __( 'Weiter ›', 'bw-woo-membership' ) }</Button>
				</div>
			) }
		</>
	);
}

/* ── Member details ───────────────────────────────────────────────────── */
function MemberEdit( { id, go } ) {
	const [ member, setMember ] = useState( null );
	const [ form, setForm ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ saved, setSaved ] = useState( false );
	const [ busy, setBusy ] = useState( false );

	const load = useCallback( () => {
		apiFetch( { path: `/bw-wm/v1/members/${ id }` } )
			.then( ( m ) => {
				setMember( m );
				setForm( { first: m.first, last: m.last, email: m.email, manual: m.manual, profile: m.profile, directory: m.row.directory } );
			} )
			.catch( ( e ) => setError( e.message ) );
	}, [ id ] );
	useEffect( load, [ load ] );

	if ( error && ! member ) {
		return <><Header title={ __( 'Mitglied', 'bw-woo-membership' ) } /><ErrorNotice error={ error } /><Button variant="link" onClick={ () => go( '/' ) }>{ __( '← Alle Mitglieder', 'bw-woo-membership' ) }</Button></>;
	}
	if ( ! member || ! form ) {
		return <Spinner />;
	}
	const set = ( key ) => ( value ) => setForm( { ...form, [ key ]: value } );
	const setProfile = ( key ) => ( value ) => setForm( { ...form, profile: { ...form.profile, [ key ]: value } } );
	const toggleGroup = ( key ) => ( on ) => setProfile( 'groups' )( on ? [ ...form.profile.groups, key ] : form.profile.groups.filter( ( g ) => g !== key ) );

	const save = () => {
		setBusy( true );
		setSaved( false );
		apiFetch( { path: `/bw-wm/v1/members/${ id }`, method: 'POST', data: form } )
			.then( ( m ) => {
				setMember( m );
				setSaved( true );
				setError( '' );
			} )
			.catch( ( e ) => setError( e.message ) )
			.finally( () => setBusy( false ) );
	};

	const row = member.row;
	return (
		<>
			<Header title={ /* translators: %s: member name */ sprintf( __( 'Mitglied bearbeiten: %s', 'bw-woo-membership' ), row.name ) }>
				<span className="bwo-muted">{ row.number ? /* translators: %s: membership number */ sprintf( __( 'Nr. %s', 'bw-woo-membership' ), row.number ) : __( 'noch keine Nummer', 'bw-woo-membership' ) }</span>
				<Button variant="link" onClick={ () => go( '/' ) }>{ __( '← Alle Mitglieder', 'bw-woo-membership' ) }</Button>
			</Header>
			<ErrorNotice error={ error } />
			{ saved && <Notice status="success" onRemove={ () => setSaved( false ) }>{ __( 'Gespeichert.', 'bw-woo-membership' ) }</Notice> }
			<div className="bwo-columns">
				<div className="bwo-main">
					<Card>
						<CardHeader><h2>{ __( 'Kontaktdaten', 'bw-woo-membership' ) }</h2></CardHeader>
						<CardBody className="bwo-grid">
							<TextControl label={ __( 'Vorname', 'bw-woo-membership' ) } value={ form.first } onChange={ set( 'first' ) } { ...field } />
							<TextControl label={ __( 'Nachname', 'bw-woo-membership' ) } value={ form.last } onChange={ set( 'last' ) } { ...field } />
							<TextControl label={ __( 'E-Mail', 'bw-woo-membership' ) } type="email" value={ form.email } onChange={ set( 'email' ) } { ...field } />
						</CardBody>
					</Card>
					<Card>
						<CardHeader><h2>{ __( 'Profil im öffentlichen Mitgliederverzeichnis', 'bw-woo-membership' ) }</h2></CardHeader>
						<CardBody className="bwo-stack">
							<CheckboxControl
								label={ __( 'Im Verzeichnis anzeigen', 'bw-woo-membership' ) }
								help={ __( 'Nur mit Einwilligung des Mitglieds – normalerweise schaltet das Mitglied selbst im Mitgliederbereich um.', 'bw-woo-membership' ) }
								checked={ !! form.directory }
								onChange={ set( 'directory' ) }
								__nextHasNoMarginBottom
							/>
							<TextControl label={ __( 'Titel / akademischer Grad', 'bw-woo-membership' ) } value={ form.profile.title } onChange={ setProfile( 'title' ) } { ...field } />
							<TextControl label={ __( 'Website', 'bw-woo-membership' ) } type="url" value={ form.profile.website } onChange={ setProfile( 'website' ) } { ...field } />
							<TextControl label={ __( 'Fachliche Schwerpunkte', 'bw-woo-membership' ) } value={ form.profile.focus } onChange={ setProfile( 'focus' ) } { ...field } />
							<fieldset className="bwo-checks">
								<legend>{ __( 'Fachgruppen', 'bw-woo-membership' ) }</legend>
								{ Object.entries( member.groups ).map( ( [ key, label ] ) => (
									<CheckboxControl key={ key } label={ label } checked={ form.profile.groups.includes( key ) } onChange={ toggleGroup( key ) } __nextHasNoMarginBottom />
								) ) }
							</fieldset>
							<TextareaControl label={ __( 'Kurzprofil', 'bw-woo-membership' ) } value={ form.profile.bio } onChange={ setProfile( 'bio' ) } __nextHasNoMarginBottom />
						</CardBody>
					</Card>
					{ member.application && ( member.application.occupation || member.application.statutes ) && (
						<Card>
							<CardHeader><h2>{ __( 'Angaben beim Beitritt', 'bw-woo-membership' ) }</h2></CardHeader>
							<CardBody>
								<dl className="bwo-facts">
									<dt>{ __( 'Beruf / Studienrichtung', 'bw-woo-membership' ) }</dt>
									<dd>{ member.application.occupation || '–' }</dd>
									<dt>{ __( 'Statuten bestätigt', 'bw-woo-membership' ) }</dt>
									<dd>{ member.application.statutes ? member.application.statutes.split( '-' ).reverse().join( '.' ) : '–' }</dd>
								</dl>
							</CardBody>
						</Card>
					) }
					<Card>
						<CardHeader><h2>{ __( 'Bestellungen', 'bw-woo-membership' ) }</h2></CardHeader>
						<CardBody>
							{ member.orders.length === 0 ? <p>{ __( 'Keine Bestellungen.', 'bw-woo-membership' ) }</p> : (
								<table className="widefat striped">
									<tbody>
										{ member.orders.map( ( o ) => (
											<tr key={ o.id }>
												<td><a href={ o.url }>#{ o.id }</a></td>
												<td>{ o.date }</td>
												<td>{ o.items }</td>
												<td>{ o.status }</td>
												<td>{ o.total }</td>
											</tr>
										) ) }
									</tbody>
								</table>
							) }
						</CardBody>
					</Card>
				</div>
				<div className="bwo-side">
					<Card>
						<CardHeader><h2>{ __( 'Speichern', 'bw-woo-membership' ) }</h2></CardHeader>
						<CardBody><Button variant="primary" isBusy={ busy } disabled={ busy } onClick={ save }>{ __( 'Aktualisieren', 'bw-woo-membership' ) }</Button></CardBody>
					</Card>
					<Card>
						<CardHeader><h2>{ __( 'Mitgliedschaft', 'bw-woo-membership' ) }</h2></CardHeader>
						<CardBody className="bwo-stack">
							<dl className="bwo-facts">
								<dt>{ __( 'Status', 'bw-woo-membership' ) }</dt><dd><StatusBadge status={ row.status } label={ row.status_l } /></dd>
								<dt>{ __( 'Mitgliedsart', 'bw-woo-membership' ) }</dt><dd>{ row.type || '–' }</dd>
								<dt>{ __( 'Mitglied seit', 'bw-woo-membership' ) }</dt><dd>{ row.since || '–' }</dd>
								<dt>{ __( 'Verlängerung / gültig bis', 'bw-woo-membership' ) }</dt><dd>{ row.renewal || '–' }</dd>
								<dt>{ __( 'Quelle', 'bw-woo-membership' ) }</dt><dd>{ { order: __( 'Bestellung', 'bw-woo-membership' ), subscription: __( 'Abonnement', 'bw-woo-membership' ), manual: __( 'manuell', 'bw-woo-membership' ), none: '–' }[ row.source ] || row.source }</dd>
							</dl>
							<SelectControl
								label={ __( 'Manuelle Mitgliedschaft', 'bw-woo-membership' ) }
								help={ __( 'Für Ehren- und institutionelle Mitglieder (ohne Bezahlung über die Website). Leer = aus Bestellung/Abo.', 'bw-woo-membership' ) }
								value={ form.manual.type }
								options={ [ { value: '', label: __( '– keine –', 'bw-woo-membership' ) }, ...Object.entries( member.types ).map( ( [ value, label ] ) => ( { value, label } ) ) ] }
								onChange={ ( v ) => setForm( { ...form, manual: { ...form.manual, type: v } } ) }
								{ ...field }
							/>
							{ form.manual.type && (
								<TextControl label={ __( 'Gültig bis (leer = unbefristet)', 'bw-woo-membership' ) } type="date" value={ form.manual.until } onChange={ ( v ) => setForm( { ...form, manual: { ...form.manual, until: v } } ) } { ...field } />
							) }
						</CardBody>
					</Card>
					<Card>
						<CardHeader><h2>{ __( 'Studiennachweis', 'bw-woo-membership' ) }</h2></CardHeader>
						<CardBody>
							<dl className="bwo-facts">
								<dt>{ __( 'Status', 'bw-woo-membership' ) }</dt><dd>{ member.proof.label }</dd>
								{ member.proof.uploaded && <><dt>{ __( 'Hochgeladen', 'bw-woo-membership' ) }</dt><dd>{ member.proof.uploaded }</dd></> }
								{ member.proof.valid_until && <><dt>{ __( 'Gültig bis', 'bw-woo-membership' ) }</dt><dd>{ member.proof.valid_until }</dd></> }
								{ member.proof.reviewed && <><dt>{ __( 'Geprüft', 'bw-woo-membership' ) }</dt><dd>{ member.proof.reviewed }</dd></> }
								{ member.proof.reason && <><dt>{ __( 'Grund', 'bw-woo-membership' ) }</dt><dd>{ member.proof.reason }</dd></> }
							</dl>
							{ member.proof.status === 'pending' && <Button variant="secondary" onClick={ () => go( '/proofs' ) }>{ __( 'Zur Prüfung', 'bw-woo-membership' ) }</Button> }
						</CardBody>
					</Card>
				</div>
			</div>
		</>
	);
}

/* ── New manual member ────────────────────────────────────────────────── */
function MemberNew( { go } ) {
	const [ form, setForm ] = useState( { first: '', last: '', email: '', type: 'honorary', until: '' } );
	const [ error, setError ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const set = ( key ) => ( value ) => setForm( { ...form, [ key ]: value } );
	const create = () => {
		setBusy( true );
		apiFetch( { path: '/bw-wm/v1/members', method: 'POST', data: form } )
			.then( ( m ) => go( `/member/${ m.row.id }` ) )
			.catch( ( e ) => setError( e.message ) )
			.finally( () => setBusy( false ) );
	};
	return (
		<>
			<Header title={ __( 'Ehren- oder institutionelles Mitglied anlegen', 'bw-woo-membership' ) }>
				<Button variant="link" onClick={ () => go( '/' ) }>{ __( '← Alle Mitglieder', 'bw-woo-membership' ) }</Button>
			</Header>
			<p>{ __( 'Ordentliche und studentische Mitglieder entstehen automatisch beim Bezahlen über die Website. Hier legst du Mitglieder ohne Bezahlung über die Website an; sie bekommen eine E-Mail, um ihr Passwort zu setzen.', 'bw-woo-membership' ) }</p>
			<ErrorNotice error={ error } />
			<Card className="bwo-narrow">
				<CardBody className="bwo-stack">
					<TextControl label={ __( 'Vorname', 'bw-woo-membership' ) } value={ form.first } onChange={ set( 'first' ) } { ...field } />
					<TextControl label={ __( 'Nachname bzw. Organisation', 'bw-woo-membership' ) } value={ form.last } onChange={ set( 'last' ) } { ...field } />
					<TextControl label={ __( 'E-Mail', 'bw-woo-membership' ) } type="email" value={ form.email } onChange={ set( 'email' ) } { ...field } />
					<SelectControl label={ __( 'Mitgliedsart', 'bw-woo-membership' ) } value={ form.type } options={ [ { value: 'honorary', label: __( 'Ehrenmitgliedschaft', 'bw-woo-membership' ) }, { value: 'institutional', label: __( 'Institutionelle Mitgliedschaft', 'bw-woo-membership' ) } ] } onChange={ set( 'type' ) } { ...field } />
					<TextControl label={ __( 'Gültig bis (leer = unbefristet)', 'bw-woo-membership' ) } type="date" value={ form.until } onChange={ set( 'until' ) } { ...field } />
					<div><Button variant="primary" isBusy={ busy } disabled={ busy || ! form.email } onClick={ create }>{ __( 'Mitglied anlegen', 'bw-woo-membership' ) }</Button></div>
				</CardBody>
			</Card>
		</>
	);
}

/* ── Proof review ─────────────────────────────────────────────────────── */
function ProofRow( { item, onDone } ) {
	const [ until, setUntil ] = useState( item.proof.suggested );
	const [ reason, setReason ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const decide = ( decision ) => {
		setBusy( true );
		apiFetch( { path: `/bw-wm/v1/proofs/${ item.id }`, method: 'POST', data: { decision, valid_until: until, reason } } )
			.then( onDone )
			.catch( ( e ) => setError( e.message ) )
			.finally( () => setBusy( false ) );
	};
	return (
		<Card>
			<CardBody className="bwo-proof">
				<div>
					<strong>{ item.name }</strong> <span className="bwo-muted">{ item.email } · { item.number || __( 'ohne Nr.', 'bw-woo-membership' ) }</span>
					<div className="bwo-muted">{ item.type } · { /* translators: %s: upload date */ sprintf( __( 'hochgeladen %s', 'bw-woo-membership' ), item.proof.uploaded ) }</div>
					<p><a href={ addQueryArgs( config.proofUrl, { user: item.id } ) } target="_blank" rel="noreferrer">{ /* translators: %s: file name */ sprintf( __( 'Nachweis ansehen (%s)', 'bw-woo-membership' ), item.proof.name ) }</a></p>
					<ErrorNotice error={ error } />
				</div>
				<div className="bwo-proof__actions">
					<TextControl label={ __( 'Gültig bis', 'bw-woo-membership' ) } type="date" value={ until } onChange={ setUntil } { ...field } />
					<Button variant="primary" isBusy={ busy } disabled={ busy } onClick={ () => decide( 'approve' ) }>{ __( 'Freigeben', 'bw-woo-membership' ) }</Button>
					<TextControl label={ __( 'Grund bei Ablehnung', 'bw-woo-membership' ) } value={ reason } onChange={ setReason } { ...field } />
					<Button variant="secondary" isDestructive disabled={ busy } onClick={ () => decide( 'reject' ) }>{ __( 'Ablehnen', 'bw-woo-membership' ) }</Button>
				</div>
			</CardBody>
		</Card>
	);
}

function Proofs( { go } ) {
	const [ items, setItems ] = useState( null );
	const [ error, setError ] = useState( '' );
	const load = useCallback( () => {
		apiFetch( { path: '/bw-wm/v1/proofs' } ).then( setItems ).catch( ( e ) => setError( e.message ) );
	}, [] );
	useEffect( load, [ load ] );
	return (
		<>
			<Header title={ __( 'Studiennachweise prüfen', 'bw-woo-membership' ) }>
				<Button variant="link" onClick={ () => go( '/' ) }>{ __( '← Alle Mitglieder', 'bw-woo-membership' ) }</Button>
			</Header>
			<p>{ __( 'Freigeben schickt dem Mitglied eine Bestätigung, Ablehnen eine Bitte um einen neuen Nachweis. Die Datei wird danach gelöscht.', 'bw-woo-membership' ) }</p>
			<ErrorNotice error={ error } />
			{ ! items ? <Spinner /> : items.length === 0 ? <Notice status="success" isDismissible={ false }>{ __( 'Keine Nachweise offen.', 'bw-woo-membership' ) }</Notice> : (
				<div className="bwo-stack">{ items.map( ( item ) => <ProofRow key={ item.id } item={ item } onDone={ load } /> ) }</div>
			) }
		</>
	);
}

function App() {
	const [ path, go ] = useRoute();
	const member = path.match( /^\/member\/(\d+)$/ );
	if ( member ) {
		return <MemberEdit key={ member[ 1 ] } id={ member[ 1 ] } go={ go } />;
	}
	if ( path === '/new' ) {
		return <MemberNew go={ go } />;
	}
	if ( path === '/proofs' ) {
		return <Proofs go={ go } />;
	}
	return <MemberList go={ go } />;
}

const root = document.getElementById( 'bw-wm-admin' );
if ( root ) {
	createRoot( root ).render( <App /> );
}
