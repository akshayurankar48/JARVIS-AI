/**
 * WP Agent — Scroll Animation Observer.
 *
 * Watches elements with wpa- animation classes and adds .wpa-visible
 * when they enter the viewport. Fires once per element.
 * Also handles parallax scroll, marquee duplication, and dark/light detection.
 *
 * @package
 * @since   1.0.0
 */
( function () {
	if ( typeof IntersectionObserver === 'undefined' ) {
		return;
	}

	const selector = [
		'.wpa-fade-up', '.wpa-fade-down', '.wpa-fade-left', '.wpa-fade-right',
		'.wpa-slide-left', '.wpa-slide-right', '.wpa-zoom-in',
		'.wpa-scale-up', '.wpa-scale-down', '.wpa-rotate-in',
		'.wpa-flip-up', '.wpa-flip-left', '.wpa-blur-in',
		'.wpa-clip-up', '.wpa-clip-left', '.wpa-clip-right', '.wpa-clip-circle',
		'.wpa-elastic-up', '.wpa-elastic-scale', '.wpa-text-reveal',
		'.wpa-stagger-children', '.wpa-stagger-left',
	].join( ',' );

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'wpa-visible' );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.15 }
	);

	/**
	 * Detect if first full-width section has a dark background.
	 * Returns 'dark' or 'light'.
	 */
	function detectPageTheme() {
		const firstSection = document.querySelector( '.alignfull, [class*="wp-block-group"].alignfull, [class*="wp-block-cover"].alignfull' );
		if ( ! firstSection ) {
			return 'dark'; // fallback to dark for backwards compat
		}
		const bg = window.getComputedStyle( firstSection ).backgroundColor;
		const match = bg.match( /rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)/ );
		if ( ! match ) {
			return 'dark';
		}
		// Relative luminance approximation.
		const luminance = ( parseInt( match[ 1 ], 10 ) * 299 + parseInt( match[ 2 ], 10 ) * 587 + parseInt( match[ 3 ], 10 ) * 114 ) / 1000;
		return luminance < 128 ? 'dark' : 'light';
	}

	function observe() {
		const elements = document.querySelectorAll( selector );

		if ( elements.length > 0 ) {
			const theme = detectPageTheme();
			if ( theme === 'dark' ) {
				document.body.classList.add( 'wpa-page-dark' );
				document.body.classList.add( 'wpa-page' ); // backwards compat
			} else {
				document.body.classList.add( 'wpa-page-light' );
			}
		}

		elements.forEach( function ( el ) {
			if ( ! el.classList.contains( 'wpa-visible' ) ) {
				observer.observe( el );
			}
		} );

		// Parallax: update CSS custom property on scroll.
		const parallaxEls = document.querySelectorAll( '.wpa-parallax-slow' );
		if ( parallaxEls.length > 0 ) {
			let ticking = false;
			window.addEventListener( 'scroll', function () {
				if ( ! ticking ) {
					window.requestAnimationFrame( function () {
						const scrollY = window.pageYOffset;
						parallaxEls.forEach( function ( el ) {
							el.style.setProperty( '--wpa-scroll-y', scrollY + 'px' );
						} );
						ticking = false;
					} );
					ticking = true;
				}
			}, { passive: true } );
		}

		// Marquee: duplicate children for seamless infinite scroll.
		document.querySelectorAll( '.wpa-marquee, .wpa-marquee-reverse' ).forEach( function ( marquee ) {
			const inner = marquee.firstElementChild;
			if ( inner && ! inner.dataset.wpaCloned ) {
				const clone = inner.cloneNode( true );
				clone.setAttribute( 'aria-hidden', 'true' );
				inner.dataset.wpaCloned = '1';
				marquee.appendChild( clone );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', observe );
	} else {
		observe();
	}
}() );
