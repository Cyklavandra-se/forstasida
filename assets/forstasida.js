/*
 * Cykla & Vandra – Förstasida
 *
 * När AI-sökningen visar resultat krymper herobilden mjukt så att den slutar 24 px under
 * sökformuläret, och resultaten hamnar på ljus bakgrund (klassen cvfs-has-res och
 * CSS-variabeln --cvfs-hero-h). Samma logik som i prototypen.
 *
 * Bildslumpningen ligger inline direkt efter heron (se templates/forstasida.php), så att
 * bilden väljs innan sidan ritas.
 */
(function () {
	'use strict';

	function init() {
		var h = document.querySelector( '.cvfs-hero' );
		if ( ! h ) {
			return;
		}
		var o = h.querySelector( '.cvai-out' );
		var f = h.querySelector( '.cvai-form' );
		if ( ! o || ! f ) {
			return;
		}

		function update() {
			var has = o.textContent.trim() !== '';
			h.classList.toggle( 'cvfs-has-res', has );
			if ( has ) {
				var b = f.getBoundingClientRect().bottom - h.getBoundingClientRect().top + 24;
				h.style.setProperty( '--cvfs-hero-h', Math.round( b ) + 'px' );
			} else {
				h.style.removeProperty( '--cvfs-hero-h' );
			}
		}

		new MutationObserver( update ).observe( o, { childList: true, subtree: true, characterData: true } );
		window.addEventListener( 'resize', update );
		update();
	}

	/*
	 * Rad i sidled på dator: pilar ‹ › bredvid länken "Alla … →". De visas bara när raden
	 * går att scrolla, och den pil som inte leder någonstans gråas.
	 */
	function initRails() {
		var desk = window.matchMedia ? window.matchMedia( '(min-width:701px)' ) : null;
		Array.prototype.forEach.call( document.querySelectorAll( '.cvfs-rail-d' ), function ( g ) {
			var head = g.previousElementSibling;
			if ( ! head || ! head.classList.contains( 'cvfs-head' ) ) {
				return;
			}
			var box = document.createElement( 'div' );
			box.className = 'cvfs-arrows';
			box.innerHTML = '<button type="button" class="cvfs-arrow" data-dir="-1" aria-label="&#8249;">&#8249;</button><button type="button" class="cvfs-arrow" data-dir="1" aria-label="&#8250;">&#8250;</button>';
			var link = head.querySelector( '.cvfs-headlink' );
			if ( link ) {
				link.insertBefore( box, link.firstChild );
			} else {
				head.appendChild( box );
			}
			var prev = box.children[ 0 ];
			var next = box.children[ 1 ];

			function update() {
				var max = g.scrollWidth - g.clientWidth;
				box.classList.toggle( 'is-on', max > 4 && ( ! desk || desk.matches ) );
				prev.disabled = g.scrollLeft <= 2;
				next.disabled = g.scrollLeft >= max - 2;
			}
			box.addEventListener( 'click', function ( e ) {
				var b = e.target.closest( '.cvfs-arrow' );
				if ( ! b ) {
					return;
				}
				var dir = +b.getAttribute( 'data-dir' );
				if ( g.scrollBy ) {
					g.scrollBy( { left: dir * ( g.clientWidth + 20 ), behavior: 'smooth' } );
				} else {
					g.scrollLeft += dir * ( g.clientWidth + 20 );
				}
			} );
			g.addEventListener( 'scroll', update, { passive: true } );
			window.addEventListener( 'resize', update );
			update();
		} );
	}

	function start() {
		init();
		initRails();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
})();
