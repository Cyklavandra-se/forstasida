/* Förstasida – admin: resväljare, bildväljare och sortering. */
( function ( $ ) {
	'use strict';

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* ---------- Resväljare ---------- */

	// Namnen på fälten följer ordningen i listan: base[0][id], base[1][id] …
	function renumber( $box ) {
		var base = $box.data( 'base' );
		$box.find( '.cvfs-trip' ).each( function ( i ) {
			$( this ).find( '[data-f]' ).each( function () {
				this.name = base + '[' + i + '][' + $( this ).data( 'f' ) + ']';
			} );
		} );
	}

	function tripRow( t ) {
		return (
			'<li class="cvfs-trip">' +
			'<span class="cvfs-handle dashicons dashicons-menu" title="Dra för att sortera"></span>' +
			'<span class="cvfs-thumb">' + ( t.image ? '<img src="' + esc( t.image ) + '" alt="" />' : '' ) + '</span>' +
			'<div class="cvfs-trip-main">' +
			'<div class="cvfs-trip-page"><input type="hidden" data-f="id" value="' + esc( t.id ) + '" />' +
			'<a href="' + esc( t.url ) + '" target="_blank" rel="noopener">' + esc( t.title ) + '</a> ' +
			'<span class="cvfs-trip-auto">' + esc( t.meta ) + '</span></div>' +
			'<div class="cvfs-trip-fields">' +
			'<label>Kortrubrik <input type="text" data-f="title" value="" placeholder="' + esc( t.title ) + '" /></label> ' +
			'<label>Metarad <input type="text" data-f="meta" value="" placeholder="' + esc( t.meta ) + '" /></label> ' +
			'<label>Pris <input type="text" data-f="price" class="cvfs-short" value="" placeholder="' + esc( t.price || 'Inget i faktarutan' ) + '" /></label> ' +
			'<label>Egen bild <input type="text" data-f="image" class="cvfs-img-url" value="" placeholder="Utvald bild" /></label> ' +
			'<button type="button" class="button cvfs-pick-one">Välj bild</button>' +
			'</div></div>' +
			'<button type="button" class="button-link cvfs-remove" title="Ta bort">✕</button>' +
			'</li>'
		);
	}

	function initTrips() {
		$( '.cvfs-trips' ).each( function () {
			var $box = $( this ),
				$list = $box.find( '.cvfs-trip-list' ),
				$search = $box.find( '.cvfs-trip-search' ),
				$res = $box.find( '.cvfs-trip-results' ),
				timer = null,
				last = null;

			$list.sortable( {
				handle: '.cvfs-handle',
				axis: 'y',
				update: function () {
					renumber( $box );
				},
			} );

			function search() {
				var q = $search.val().trim();
				if ( q === last ) {
					return;
				}
				last = q;
				$res.html( '<li class="cvfs-muted">Söker …</li>' );
				$.get( cvfsAdmin.ajax, {
					action: 'cvfs_search_trips',
					nonce: cvfsAdmin.nonce,
					lang: $box.data( 'lang' ),
					q: q,
				} ).done( function ( r ) {
					if ( ! r || ! r.success ) {
						$res.html( '<li class="cvfs-muted">Sökningen misslyckades.</li>' );
						return;
					}
					if ( ! r.data.length ) {
						$res.html( '<li class="cvfs-muted">Inga resor hittades.</li>' );
						return;
					}
					$res.empty();
					r.data.forEach( function ( t ) {
						$( '<li class="cvfs-result"></li>' )
							.html( '<strong>' + esc( t.title ) + '</strong> <span class="cvfs-muted">' + esc( t.meta ) + '</span>' )
							.data( 'trip', t )
							.appendTo( $res );
					} );
				} );
			}

			$search.on( 'input focus', function () {
				clearTimeout( timer );
				timer = setTimeout( search, 250 );
			} );
			$search.on( 'keydown', function ( e ) {
				if ( e.key === 'Enter' ) {
					e.preventDefault(); // Skicka inte formuläret.
				}
			} );

			$res.on( 'click', '.cvfs-result', function () {
				$list.append( tripRow( $( this ).data( 'trip' ) ) );
				renumber( $box );
				$( this ).addClass( 'cvfs-added' );
			} );

			$list.on( 'click', '.cvfs-remove', function () {
				$( this ).closest( '.cvfs-trip' ).remove();
				renumber( $box );
			} );
		} );

		$( '.cvfs-form' ).on( 'submit', function () {
			$( '.cvfs-trips' ).each( function () {
				renumber( $( this ) );
			} );
		} );
	}

	/* ---------- Bilder ---------- */

	function pickUrl( att ) {
		// "large" räcker för korten och heron; annars originalet.
		var s = att.sizes || {};
		return ( s.large && s.large.url ) || att.url;
	}

	function pickUrlFull( att ) {
		return att.url;
	}

	function initImages() {
		$( '.cvfs-imgs' ).sortable( { axis: 'x' } );

		// Herobilder: flera på en gång, i originalstorlek (bilden täcker hela bredden).
		$( document ).on( 'click', '.cvfs-pick-many', function () {
			var $list = $( this ).prev( '.cvfs-imgs' ),
				name = $list.data( 'name' ),
				frame = wp.media( { title: 'Välj bakgrundsbilder', multiple: 'add', library: { type: 'image' }, button: { text: 'Lägg till' } } );
			frame.on( 'select', function () {
				frame.state().get( 'selection' ).each( function ( m ) {
					var url = pickUrlFull( m.toJSON() );
					$list.append(
						'<li class="cvfs-img-item"><img src="' + esc( url ) + '" alt="" />' +
						'<input type="hidden" name="' + esc( name ) + '" value="' + esc( url ) + '" />' +
						'<button type="button" class="button-link cvfs-remove" title="Ta bort">✕</button></li>'
					);
				} );
			} );
			frame.open();
		} );

		$( document ).on( 'click', '.cvfs-imgs .cvfs-remove', function () {
			$( this ).closest( 'li' ).remove();
		} );

		// En bild: resekortens egna bilder och bandets fyra bilder.
		$( document ).on( 'click', '.cvfs-pick-one', function () {
			var $wrap = $( this ).closest( '.cvfs-trip, .cvfs-one' ),
				$input = $wrap.find( '.cvfs-img-url' ).first(),
				frame = wp.media( { title: 'Välj bild', multiple: false, library: { type: 'image' }, button: { text: 'Välj' } } );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON(),
					url = pickUrl( att );
				$input.val( url ).trigger( 'change' );
				$wrap.find( '.cvfs-thumb' ).first().html( '<img src="' + esc( url ) + '" alt="" />' );
				var $alt = $wrap.find( 'input[name$="[alt]"]' );
				if ( $alt.length && ! $alt.val() && att.alt ) {
					$alt.val( att.alt );
				}
			} );
			frame.open();
		} );
	}

	/* ---------- Följ svenska ---------- */

	function initFollow() {
		$( document ).on( 'change', '.cvfs-follow-cb', function () {
			$( this ).closest( '.cvfs-sec' ).toggleClass( 'is-following', this.checked );
		} );
	}

	/* ---------- Översättning: förslag per språk ---------- */

	var statusText = {
		new: 'Ny',
		changed: 'Svenskan ändrad',
		clear: 'Svenskan tömd',
		adjusted: 'Du har ändrat här – svenskan har också ändrats',
		own: 'Egen text på språket',
	};

	function initTranslate() {
		var $langs = $( '.cvfs-tr-lang' );
		if ( ! $langs.length ) {
			return;
		}
		var $save = $( '#cvfs-tr-save' ),
			total = 0,
			queue = $langs.toArray();

		function next() {
			var el = queue.shift();
			if ( ! el ) {
				$save.prop( 'disabled', total === 0 );
				if ( total === 0 ) {
					$save.after( ' <span class="cvfs-muted">Inget att översätta – allt är redan översatt.</span>' );
				}
				return;
			}
			var $box = $( el ),
				lang = $box.data( 'lang' ),
				$st = $box.find( '.cvfs-tr-status' ),
				$body = $box.find( '.cvfs-tr-body' );
			$st.text( 'Översätter … (kan ta en halv minut)' ).addClass( 'is-busy' );
			$.post( cvfsAdmin.ajax, { action: 'cvfs_translate_lang', nonce: cvfsAdmin.trNonce, lang: lang } )
				.done( function ( r ) {
					$st.removeClass( 'is-busy' );
					if ( ! r || ! r.success ) {
						$st.text( 'Fel: ' + ( ( r && r.data && r.data.message ) || 'okänt fel' ) ).addClass( 'is-error' );
						return;
					}
					var rows = r.data.rows || [];
					if ( ! rows.length ) {
						$st.text( 'Inget har ändrats' );
						return;
					}
					total += rows.length;
					$st.text( rows.length + ' texter' );
					var h = '<table class="widefat striped cvfs-tr-table"><thead><tr><th class="check-column"></th><th>Fält</th><th>Svenska</th><th>Nu</th><th>Ny text</th></tr></thead><tbody>';
					rows.forEach( function ( row, i ) {
						var n = 'tr[' + lang + '][' + i + ']';
						h += '<tr class="cvfs-st-' + esc( row.status ) + '">' +
							'<th class="check-column"><input type="checkbox" name="' + n + '[on]" value="1"' + ( row.checked ? ' checked' : '' ) + ' /></th>' +
							'<td>' + esc( row.label ) + '<br><span class="cvfs-tr-st">' + esc( statusText[ row.status ] || row.status ) + '</span>' +
							'<input type="hidden" name="' + n + '[path]" value="' + esc( row.path ) + '" /></td>' +
							'<td>' + esc( row.src ) + '</td>' +
							'<td class="cvfs-muted">' + esc( row.cur ) + '</td>' +
							'<td><textarea name="' + n + '[text]" rows="2">' + esc( row.new ) + '</textarea></td>' +
							'</tr>';
					} );
					h += '</tbody></table>';
					$body.html( h );
				} )
				.fail( function () {
					$st.removeClass( 'is-busy' ).text( 'Fel: servern svarade inte (tidsgräns?). Ladda om sidan och försök igen.' ).addClass( 'is-error' );
				} )
				.always( next );
		}
		next();

		// Ändrad text = välj raden.
		$( document ).on( 'input', '.cvfs-tr-table textarea', function () {
			$( this ).closest( 'tr' ).find( 'input[type=checkbox]' ).prop( 'checked', true );
		} );
	}

	$( function () {
		initTrips();
		initImages();
		initFollow();
		initTranslate();
		$( '.cvfs-reset' ).on( 'click', function () {
			return window.confirm( 'Återställa hela fliken till standardvärdena? Det du har sparat för språket försvinner.' );
		} );
	} );
} )( jQuery );
