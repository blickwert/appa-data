/**
 * Sticky header and jump navigation: publishes their heights as CSS variables (position of the jump band,
 * scroll padding for anchors) and marks the link of the section in view with aria-current.
 */
( () => {
	const root = document.documentElement;
	const header = document.querySelector( '.bw-site-header' );
	const band = document.querySelector( '.bw-jump-band' );

	const measure = () => {
		root.style.setProperty( '--bw-header-h', ( header ? header.offsetHeight : 0 ) + 'px' );
		root.style.setProperty( '--bw-jump-h', ( band ? band.offsetHeight : 0 ) + 'px' );
	};
	measure();
	if ( 'ResizeObserver' in window ) {
		const observer = new ResizeObserver( measure );
		[ header, band ].forEach( ( el ) => el && observer.observe( el ) );
	}

	const links = [ ...document.querySelectorAll( '.bw-jump-nav a[href^="#"]' ) ];
	const sections = links.map( ( a ) => document.getElementById( decodeURIComponent( a.hash.slice( 1 ) ) ) );
	if ( ! links.length ) {
		return;
	}

	let current = -1;
	const update = () => {
		const line = ( band || header ).getBoundingClientRect().bottom + 32;
		let index = -1;
		sections.forEach( ( section, i ) => {
			if ( section && section.getBoundingClientRect().top <= line ) {
				index = i;
			}
		} );
		// At the very end the last section counts even if it is too short to reach the line.
		if ( index >= 0 && window.innerHeight + window.scrollY >= root.scrollHeight - 2 ) {
			index = sections.length - 1;
		}
		if ( index === current ) {
			return;
		}
		current = index;
		links.forEach( ( a, i ) => {
			if ( i === index ) {
				a.setAttribute( 'aria-current', 'true' );
				const list = a.closest( '.bw-jump-nav' );
				const li = a.parentElement;
				if ( list.scrollWidth > list.clientWidth && ( li.offsetLeft < list.scrollLeft || li.offsetLeft + li.offsetWidth > list.scrollLeft + list.clientWidth ) ) {
					list.scrollTo( { left: li.offsetLeft - 16, behavior: 'smooth' } );
				}
			} else {
				a.removeAttribute( 'aria-current' );
			}
		} );
	};

	let queued = false;
	const schedule = () => {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( () => {
				queued = false;
				update();
			} );
		}
	};
	window.addEventListener( 'scroll', schedule, { passive: true } );
	window.addEventListener( 'resize', schedule );
	update();
} )();
