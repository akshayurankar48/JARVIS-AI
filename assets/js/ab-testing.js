/**
 * WP Agent A/B Testing Frontend.
 *
 * Lightweight script (<2KB) for cookie-based variant assignment
 * and click tracking on pages with active A/B tests.
 *
 * @package
 * @since 1.1.0
 */
( function() {
	'use strict';

	const COOKIE_PREFIX = 'wp_agent_ab_';
	const REST_URL = ( window.wpAgentAB && window.wpAgentAB.restUrl ) || '/wp-json/wp-agent/v1/ab-track';
	const TESTS = ( window.wpAgentAB && window.wpAgentAB.tests ) || [];

	if ( ! TESTS.length ) {
		return;
	}

	function getCookie( name ) {
		const match = document.cookie.match( new RegExp( '(^| )' + name + '=([^;]+)' ) );
		return match ? match[ 2 ] : null;
	}

	function setCookie( name, value, days ) {
		const d = new Date();
		d.setTime( d.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
		document.cookie = name + '=' + value + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
	}

	function track( testId, event, variant ) {
		const data = JSON.stringify( { test_id: testId, event, variant } );
		if ( navigator.sendBeacon ) {
			navigator.sendBeacon( REST_URL, new Blob( [ data ], { type: 'application/json' } ) );
		} else {
			const xhr = new XMLHttpRequest();
			xhr.open( 'POST', REST_URL, true );
			xhr.setRequestHeader( 'Content-Type', 'application/json' );
			xhr.send( data );
		}
	}

	TESTS.forEach( function( test ) {
		const cookieName = COOKIE_PREFIX + test.id;
		let variant = getCookie( cookieName );

		if ( ! variant ) {
			variant = Math.random() < 0.5 ? 'a' : 'b';
			setCookie( cookieName, variant, 30 );
		}

		// Track impression.
		track( test.id, 'impression', variant );

		// Track clicks on elements with data-ab-track attribute.
		document.querySelectorAll( '[data-ab-track="' + test.id + '"]' ).forEach( function( el ) {
			el.addEventListener( 'click', function() {
				track( test.id, 'click', variant );
			}, { once: true } );
		} );
	} );
}() );
