/**
 * Admin app "Mitglieder": member list, member details, new manual member, proof review.
 * WordPress packages only; data via the REST routes bw-wm/v1.
 */
import { createRoot, useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
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
	[ '', 'Alle', 'all' ],
	[ 'active', 'Aktiv', 'active' ],
	[ 'pending', 'Zahlung offen', 'pending' ],
	[ 'on-hold', 'Pausiert', 'on-hold' ],
	[ 'expired', 'Abgelaufen', 'expired' ],
];

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
			<Header title="Mitglieder">
				<Button variant="secondary" onClick={ () => go( '/new' ) }>Ehren- oder institutionelles Mitglied anlegen</Button>
				<Button variant="secondary" href={ config.exportUrl }>Export (CSV)</Button>
			</Header>
			<ErrorNotice error={ error } />
			{ data && data.counts.proofs > 0 && (
				<Notice status="warning" isDismissible={ false }>
					{ data.counts.proofs } Studiennachweis(e) warten auf Prüfung.{ ' ' }
					<Button variant="link" onClick={ () => go( '/proofs' ) }>Nachweise prüfen</Button>
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
						label="Mitgliedsart"
						hideLabelFromVision
						value={ type }
						options={ [
							{ value: '', label: 'Alle Mitgliedsarten' },
							{ value: 'regular', label: 'Ordentlich' },
							{ value: 'student', label: 'Studierende' },
							{ value: 'honorary', label: 'Ehrenmitglied' },
							{ value: 'institutional', label: 'Institutionell' },
						] }
						onChange={ ( v ) => { setType( v ); setPage( 1 ); } }
						{ ...field }
					/>
					<SearchControl label="Mitglieder durchsuchen" value={ search } onChange={ ( v ) => { setSearch( v ); setPage( 1 ); } } placeholder="Name oder E-Mail" __nextHasNoMarginBottom />
				</div>
			</div>
			{ ! data ? <Spinner /> : (
				<table className="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style={ { width: '7em' } }>Nr.</th>
							<th>Name</th>
							<th>Mitgliedsart</th>
							<th>Status</th>
							<th>Mitglied seit</th>
							<th>Verlängerung / gültig bis</th>
							<th>Studiennachweis</th>
							<th>Verzeichnis</th>
						</tr>
					</thead>
					<tbody>
						{ data.items.length === 0 && <tr><td colSpan="8">Keine Mitglieder gefunden.</td></tr> }
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
								<td>{ m.directory ? 'sichtbar' : '–' }</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
			{ data && pages > 1 && (
				<div className="bwo-pager">
					<Button variant="secondary" size="compact" disabled={ page <= 1 } onClick={ () => setPage( page - 1 ) }>‹ Zurück</Button>
					<span>Seite { page } von { pages }</span>
					<Button variant="secondary" size="compact" disabled={ page >= pages } onClick={ () => setPage( page + 1 ) }>Weiter ›</Button>
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
		return <><Header title="Mitglied" /><ErrorNotice error={ error } /><Button variant="link" onClick={ () => go( '/' ) }>← Alle Mitglieder</Button></>;
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
			<Header title={ `Mitglied bearbeiten: ${ row.name }` }>
				<span className="bwo-muted">{ row.number ? `Nr. ${ row.number }` : 'noch keine Nummer' }</span>
				<Button variant="link" onClick={ () => go( '/' ) }>← Alle Mitglieder</Button>
			</Header>
			<ErrorNotice error={ error } />
			{ saved && <Notice status="success" onRemove={ () => setSaved( false ) }>Gespeichert.</Notice> }
			<div className="bwo-columns">
				<div className="bwo-main">
					<Card>
						<CardHeader><h2>Kontaktdaten</h2></CardHeader>
						<CardBody className="bwo-grid">
							<TextControl label="Vorname" value={ form.first } onChange={ set( 'first' ) } { ...field } />
							<TextControl label="Nachname" value={ form.last } onChange={ set( 'last' ) } { ...field } />
							<TextControl label="E-Mail" type="email" value={ form.email } onChange={ set( 'email' ) } { ...field } />
						</CardBody>
					</Card>
					<Card>
						<CardHeader><h2>Profil im öffentlichen Mitgliederverzeichnis</h2></CardHeader>
						<CardBody className="bwo-stack">
							<CheckboxControl
								label="Im Verzeichnis anzeigen"
								help="Nur mit Einwilligung des Mitglieds – normalerweise schaltet das Mitglied selbst im Mitgliederbereich um."
								checked={ !! form.directory }
								onChange={ set( 'directory' ) }
								__nextHasNoMarginBottom
							/>
							<TextControl label="Titel / akademischer Grad" value={ form.profile.title } onChange={ setProfile( 'title' ) } { ...field } />
							<TextControl label="Website" type="url" value={ form.profile.website } onChange={ setProfile( 'website' ) } { ...field } />
							<TextControl label="Fachliche Schwerpunkte" value={ form.profile.focus } onChange={ setProfile( 'focus' ) } { ...field } />
							<fieldset className="bwo-checks">
								<legend>Fachgruppen</legend>
								{ Object.entries( member.groups ).map( ( [ key, label ] ) => (
									<CheckboxControl key={ key } label={ label } checked={ form.profile.groups.includes( key ) } onChange={ toggleGroup( key ) } __nextHasNoMarginBottom />
								) ) }
							</fieldset>
							<TextareaControl label="Kurzprofil" value={ form.profile.bio } onChange={ setProfile( 'bio' ) } __nextHasNoMarginBottom />
						</CardBody>
					</Card>
					{ member.application && ( member.application.occupation || member.application.statutes ) && (
						<Card>
							<CardHeader><h2>Angaben beim Beitritt</h2></CardHeader>
							<CardBody>
								<dl className="bwo-facts">
									<dt>Beruf / Studienrichtung</dt>
									<dd>{ member.application.occupation || '–' }</dd>
									<dt>Statuten bestätigt</dt>
									<dd>{ member.application.statutes ? member.application.statutes.split( '-' ).reverse().join( '.' ) : '–' }</dd>
								</dl>
							</CardBody>
						</Card>
					) }
					<Card>
						<CardHeader><h2>Bestellungen</h2></CardHeader>
						<CardBody>
							{ member.orders.length === 0 ? <p>Keine Bestellungen.</p> : (
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
						<CardHeader><h2>Speichern</h2></CardHeader>
						<CardBody><Button variant="primary" isBusy={ busy } disabled={ busy } onClick={ save }>Aktualisieren</Button></CardBody>
					</Card>
					<Card>
						<CardHeader><h2>Mitgliedschaft</h2></CardHeader>
						<CardBody className="bwo-stack">
							<dl className="bwo-facts">
								<dt>Status</dt><dd><StatusBadge status={ row.status } label={ row.status_l } /></dd>
								<dt>Mitgliedsart</dt><dd>{ row.type || '–' }</dd>
								<dt>Mitglied seit</dt><dd>{ row.since || '–' }</dd>
								<dt>Verlängerung / gültig bis</dt><dd>{ row.renewal || '–' }</dd>
								<dt>Quelle</dt><dd>{ { order: 'Bestellung', subscription: 'Abonnement', manual: 'manuell', none: '–' }[ row.source ] || row.source }</dd>
							</dl>
							<SelectControl
								label="Manuelle Mitgliedschaft"
								help="Für Ehren- und institutionelle Mitglieder (ohne Bezahlung über die Website). Leer = aus Bestellung/Abo."
								value={ form.manual.type }
								options={ [ { value: '', label: '– keine –' }, ...Object.entries( member.types ).map( ( [ value, label ] ) => ( { value, label } ) ) ] }
								onChange={ ( v ) => setForm( { ...form, manual: { ...form.manual, type: v } } ) }
								{ ...field }
							/>
							{ form.manual.type && (
								<TextControl label="Gültig bis (leer = unbefristet)" type="date" value={ form.manual.until } onChange={ ( v ) => setForm( { ...form, manual: { ...form.manual, until: v } } ) } { ...field } />
							) }
						</CardBody>
					</Card>
					<Card>
						<CardHeader><h2>Studiennachweis</h2></CardHeader>
						<CardBody>
							<dl className="bwo-facts">
								<dt>Status</dt><dd>{ member.proof.label }</dd>
								{ member.proof.uploaded && <><dt>Hochgeladen</dt><dd>{ member.proof.uploaded }</dd></> }
								{ member.proof.valid_until && <><dt>Gültig bis</dt><dd>{ member.proof.valid_until }</dd></> }
								{ member.proof.reviewed && <><dt>Geprüft</dt><dd>{ member.proof.reviewed }</dd></> }
								{ member.proof.reason && <><dt>Grund</dt><dd>{ member.proof.reason }</dd></> }
							</dl>
							{ member.proof.status === 'pending' && <Button variant="secondary" onClick={ () => go( '/proofs' ) }>Zur Prüfung</Button> }
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
			<Header title="Ehren- oder institutionelles Mitglied anlegen">
				<Button variant="link" onClick={ () => go( '/' ) }>← Alle Mitglieder</Button>
			</Header>
			<p>Ordentliche und studentische Mitglieder entstehen automatisch beim Bezahlen über die Website. Hier legst du Mitglieder ohne Bezahlung über die Website an; sie bekommen eine E-Mail, um ihr Passwort zu setzen.</p>
			<ErrorNotice error={ error } />
			<Card className="bwo-narrow">
				<CardBody className="bwo-stack">
					<TextControl label="Vorname" value={ form.first } onChange={ set( 'first' ) } { ...field } />
					<TextControl label="Nachname bzw. Organisation" value={ form.last } onChange={ set( 'last' ) } { ...field } />
					<TextControl label="E-Mail" type="email" value={ form.email } onChange={ set( 'email' ) } { ...field } />
					<SelectControl label="Mitgliedsart" value={ form.type } options={ [ { value: 'honorary', label: 'Ehrenmitgliedschaft' }, { value: 'institutional', label: 'Institutionelle Mitgliedschaft' } ] } onChange={ set( 'type' ) } { ...field } />
					<TextControl label="Gültig bis (leer = unbefristet)" type="date" value={ form.until } onChange={ set( 'until' ) } { ...field } />
					<div><Button variant="primary" isBusy={ busy } disabled={ busy || ! form.email } onClick={ create }>Mitglied anlegen</Button></div>
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
					<strong>{ item.name }</strong> <span className="bwo-muted">{ item.email } · { item.number || 'ohne Nr.' }</span>
					<div className="bwo-muted">{ item.type } · hochgeladen { item.proof.uploaded }</div>
					<p><a href={ addQueryArgs( config.proofUrl, { user: item.id } ) } target="_blank" rel="noreferrer">Nachweis ansehen ({ item.proof.name })</a></p>
					<ErrorNotice error={ error } />
				</div>
				<div className="bwo-proof__actions">
					<TextControl label="Gültig bis" type="date" value={ until } onChange={ setUntil } { ...field } />
					<Button variant="primary" isBusy={ busy } disabled={ busy } onClick={ () => decide( 'approve' ) }>Freigeben</Button>
					<TextControl label="Grund bei Ablehnung" value={ reason } onChange={ setReason } { ...field } />
					<Button variant="secondary" isDestructive disabled={ busy } onClick={ () => decide( 'reject' ) }>Ablehnen</Button>
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
			<Header title="Studiennachweise prüfen">
				<Button variant="link" onClick={ () => go( '/' ) }>← Alle Mitglieder</Button>
			</Header>
			<p>Freigeben schickt dem Mitglied eine Bestätigung, Ablehnen eine Bitte um einen neuen Nachweis. Die Datei wird danach gelöscht.</p>
			<ErrorNotice error={ error } />
			{ ! items ? <Spinner /> : items.length === 0 ? <Notice status="success" isDismissible={ false }>Keine Nachweise offen.</Notice> : (
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
