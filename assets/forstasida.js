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

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
