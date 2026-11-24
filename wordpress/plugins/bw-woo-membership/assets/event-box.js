/**
 * Loads the personal registration box of an event page (price for members, own registration) after the page,
 * so that the page itself may come from a cache.
 */
( () => {
	const config = window.bwWmEventBox || {};
	document.querySelectorAll( '[data-bwm-event]' ).forEach( ( slot ) => {
		const url = `${ config.url }?action=bw_wm_event_box&event=${ encodeURIComponent( slot.dataset.bwmEvent ) }`;
		fetch( url, { credentials: 'same-origin', cache: 'no-store' } )
			.then( ( response ) => ( response.ok ? response.text() : Promise.reject( response.status ) ) )
			.then( ( html ) => {
				if ( html.trim() ) {
					slot.outerHTML = html;
				} else {
					slot.remove();
				}
			} )
			.catch( () => {
				slot.querySelector( '.bwm-sub' ).textContent = 'Die Anmeldung konnte nicht geladen werden. Bitte lade die Seite neu.';
			} );
	} );
} )();
