<?php
/**
 * Resekort: från sparade val (sid-id + ev. egna texter) till färdiga kortdata.
 *
 * Titel, bild och länk hämtas från sidan (i aktuellt språk via WPML) om fälten är tomma.
 * Metaraden byggs av taggarna (guidad, hike1–4/bike1–4) och antal dagar om fältet är tomt.
 * Antal dagar och pris läses ur resesidans faktaruta ("Längd 5 dagar / 4 nätter",
 * "Pris från 725 €"). Det som skrivs in per kort i admin går alltid före.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Taggar som räknas som resor (svenska namn i standardspråket). */
function cvfs_trip_tags() {
	return apply_filters(
		'cvfs_trip_tags',
		array(
			'guided' => array( 'guidad' ),
			'self'   => array( 'självguidad', 'sjalvguidad' ),
		)
	);
}

/**
 * Typ och nivå ur sidans taggar.
 *
 * @return array{ guided: bool, kind: string, level: int }  kind = hike|bike|''.
 */
function cvfs_trip_facts( $post_id ) {
	static $cache = array();
	$post_id = (int) $post_id;
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	$out = array(
		'guided' => false,
		'kind'   => '',
		'level'  => 0,
	);

	// Typ och nivå läses från originalsidan på standardspråket: översatta sidor saknar ofta
	// nivåtaggarna (hike1–4/bike1–4) eller har dem under andra namn.
	$ids  = array_unique( array_filter( array( cvfs_original_id( $post_id ), $post_id ) ) );
	$tags = array();
	foreach ( $ids as $id ) {
		$t = get_the_tags( $id );
		if ( $t && ! is_wp_error( $t ) ) {
			$tags = $t;
			break;
		}
	}

	$guided = array_map( 'sanitize_title', cvfs_trip_tags()['guided'] );
	$levels = array();
	foreach ( $tags as $t ) {
		$slug = $t->slug;
		// WPML-översatta taggar kan heta t.ex. "guidad-2" eller "hike2-2".
		$base = preg_replace( '/-\d+$/', '', $slug );
		if ( in_array( $base, $guided, true ) || in_array( sanitize_title( $t->name ), $guided, true ) ) {
			$out['guided'] = true;
		}
		if ( preg_match( '/^(hike|bike)([1-4])(?:-[a-z0-9]+)?$/', $slug, $m ) ) {
			$levels[ $m[1] ][] = (int) $m[2];
		}
	}
	if ( $levels ) {
		// Vid både vandring och cykling: det som har flest nivåtaggar.
		uasort(
			$levels,
			function ( $a, $b ) {
				return count( $b ) - count( $a );
			}
		);
		$out['kind']  = (string) array_key_first( $levels );
		$out['level'] = min( $levels[ $out['kind'] ] );
	}
	if ( ! $out['kind'] ) {
		// Reserv: sidans adress (bara sökvägen – domänen "cyklavandra.se" innehåller "/cykla").
		foreach ( $ids as $id ) {
			$path = '/' . trim( (string) wp_parse_url( (string) get_permalink( $id ), PHP_URL_PATH ), '/' ) . '/';
			if ( preg_match( '#/(cykla|sykling|sykle|cycling|bike|radreisen|pyoraily)#i', $path ) ) {
				$out['kind'] = 'bike';
				break;
			}
			if ( preg_match( '#/(vandra|vandring|vandre|walking|hiking|wandern)#i', $path ) ) {
				$out['kind'] = 'hike';
				break;
			}
		}
	}
	$cache[ $post_id ] = $out;
	return $out;
}

/** Sidans id på standardspråket (samma id utan WPML eller om originalet saknas). */
function cvfs_original_id( $post_id ) {
	if ( ! cvfs_has_wpml() ) {
		return (int) $post_id;
	}
	$id = apply_filters( 'wpml_object_id', (int) $post_id, 'page', true, cvfs_default_lang() );
	return $id ? (int) $id : (int) $post_id;
}

/**
 * Antal dagar och pris ur resesidans faktaruta, t.ex.
 *   <div …>Längd</div><div …>5 dagar / 4 nätter</div>
 *   <div …>Pris</div><div …>från 725 €</div>
 * Fungerar på alla språk (Lengde/fra, Length/from …). Guidade resor saknar oftast rutan.
 *
 * @return array{ days: int, price: string }  price utan "från", t.ex. "725 €".
 */
function cvfs_trip_details( $post_id ) {
	static $cache = array();
	$post_id = (int) $post_id;
	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}
	$out     = array(
		'days'  => 0,
		'price' => '',
	);
	$len_l   = array( 'längd', 'lengde', 'længde', 'length', 'länge', 'dauer', 'lengte', 'durée', 'kesto', 'varighet', 'varighed', 'varaktighet', 'duration', 'reiselengde', 'reiselengd' );
	$price_l = array( 'pris', 'price', 'preis', 'prijs', 'prix', 'hinta' );
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( $content && preg_match_all( '#>\s*([^<>]{2,40}?)\s*</div>\s*<div[^>]*>\s*([^<>]{1,100}?)\s*</div>#u', $content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $x ) {
			$label = mb_strtolower( trim( html_entity_decode( $x[1], ENT_QUOTES, 'UTF-8' ) ) );
			$value = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $x[2], ENT_QUOTES, 'UTF-8' ) ) );
			if ( ! $out['days'] && in_array( $label, $len_l, true ) && preg_match( '/(\d+)\s*(dagar|dager|dage|days?|tage?|dagen|jours?|päivää)/iu', $value, $d ) ) {
				$out['days'] = (int) $d[1];
			}
			if ( '' === $out['price'] && in_array( $label, $price_l, true ) && preg_match( '/\d/', $value ) ) {
				$out['price'] = trim( preg_replace( '/^(från|fra|from|ab|vanaf|à partir de|alkaen)\s+/iu', '', $value ) );
			}
		}
	}
	$cache[ $post_id ] = apply_filters( 'cvfs_trip_details', $out, $post_id );
	return $cache[ $post_id ];
}

/**
 * Automatisk metarad, t.ex. "Vandring · 5 dagar · Nivå 1", "Guidad vandring · 8 dagar"
 * eller "Cykling · Nyhet 2027".
 *
 * @param string $extra Text som läggs efter typen i stället för dagar och nivå (nyhetsetiketten).
 */
function cvfs_auto_meta( $post_id, $labels, $extra = '' ) {
	$f     = cvfs_trip_facts( $post_id );
	$parts = array();
	if ( $f['kind'] ) {
		$key     = ( $f['guided'] ? 'guided_' : '' ) . $f['kind'];
		$parts[] = $labels[ $key ] ?? '';
	}
	if ( '' !== $extra ) {
		$parts[] = $extra;
	} else {
		$days = cvfs_trip_details( $post_id )['days'];
		if ( $days ) {
			$parts[] = $days . ' ' . ( $labels['days'] ?? '' );
		}
		if ( $f['level'] && ! $f['guided'] ) {
			$parts[] = trim( ( $labels['level'] ?? '' ) . ' ' . $f['level'] );
		}
	}
	return implode( ' · ', array_filter( array_map( 'trim', $parts ) ) );
}

/**
 * Bild-URL för ett kort: eget val, annars utvald bild i storleken "large".
 */
function cvfs_trip_image( $post_id ) {
	$thumb = get_post_thumbnail_id( $post_id );
	if ( ! $thumb ) {
		return array( '', '' );
	}
	$url = wp_get_attachment_image_url( $thumb, 'large' );
	$alt = (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true );
	return array( $url ? $url : '', $alt );
}

/**
 * Kortdata för en lista sparade resor.
 *
 * @param array  $trips  Sparade rader (id, title, meta, price, image).
 * @param array  $s      Språkets inställningar.
 * @param string $lang   Språkkod.
 * @param string $extra  Nyhetsetikett för automatisk metarad (bara nyhetssektionen).
 * @param bool   $auto_price Hämta priset från faktarutan när fältet Pris är tomt.
 * @return array[] { url, title, image, alt, meta, price }
 */
function cvfs_cards( $trips, $s, $lang, $extra = '', $auto_price = true ) {
	$cards = array();
	foreach ( (array) $trips as $t ) {
		$id = cvfs_object_id( $t['id'] ?? 0, $lang );
		$p  = $id ? get_post( $id ) : null;
		if ( ! $p || 'publish' !== $p->post_status ) {
			continue;
		}
		list( $img, $alt ) = cvfs_trip_image( $id );
		$title             = '' !== ( $t['title'] ?? '' ) ? $t['title'] : get_the_title( $id );
		if ( ! empty( $t['image'] ) ) {
			$img = $t['image'];
		}
		$cards[] = array(
			'url'   => get_permalink( $id ),
			'title' => html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' ),
			'image' => $img,
			// Prototypen har kortets titel som alt-text.
			'alt'   => html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' ),
			'meta'  => '' !== ( $t['meta'] ?? '' ) ? $t['meta'] : cvfs_auto_meta( $id, $s['labels'], $extra ),
			'price' => cvfs_card_price( $t, $id, $s['labels'], $auto_price ),
		);
	}
	return $cards;
}

/** Pristext för ett kort: eget pris, annars faktarutans (om $auto), med "från" först. */
function cvfs_card_price( $t, $post_id, $labels, $auto = true ) {
	$price = trim( (string) ( $t['price'] ?? '' ) );
	if ( '' === $price && $auto ) {
		$price = cvfs_trip_details( $post_id )['price'];
	}
	return '' === $price ? '' : trim( ( $labels['from'] ?? '' ) . ' ' . $price );
}

/**
 * Nyheter automatiskt: publicerade sidor med nyhetstaggen och taggen självguidad,
 * nyast först.
 *
 * @return array[] Rader i samma format som sparade resor.
 */
function cvfs_auto_news( $s, $lang ) {
	$tag  = $s['news']['tag'];
	$news = cvfs_tag_ids( array( $tag ), $lang );
	$self = cvfs_tag_ids( cvfs_trip_tags()['self'], $lang );
	if ( ! $news || ! $self ) {
		return array();
	}
	$ids = cvfs_in_lang(
		$lang,
		function () use ( $news, $self, $s ) {
			$q = new WP_Query(
				array(
					'post_type'           => 'page',
					'post_status'         => 'publish',
					'posts_per_page'      => (int) $s['news']['count'],
					'fields'              => 'ids',
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'suppress_filters'    => false,
					'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						'relation' => 'AND',
						array(
							'taxonomy' => 'post_tag',
							'terms'    => $news,
						),
						array(
							'taxonomy' => 'post_tag',
							'terms'    => $self,
						),
					),
				)
			);
			return $q->posts;
		}
	);
	return array_map( 'cvfs_trip', $ids );
}

/**
 * Sök resor (för resväljaren i admin): sidor med taggen guidad eller självguidad.
 *
 * @return array[] { id, title, url, meta }
 */
function cvfs_search_trips( $term, $lang, $limit = 20 ) {
	$tags = cvfs_tag_ids( array_merge( cvfs_trip_tags()['guided'], cvfs_trip_tags()['self'] ), $lang );
	if ( ! $tags ) {
		return array();
	}
	return cvfs_in_lang(
		$lang,
		function () use ( $term, $tags, $limit, $lang ) {
			$args = array(
				'post_type'        => 'page',
				'post_status'      => 'publish',
				'posts_per_page'   => $limit,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'post_tag',
						'terms'    => $tags,
					),
				),
			);
			if ( '' !== $term ) {
				$args['s'] = $term;
			}
			$q      = new WP_Query( $args );
			$out    = array();
			$labels = cvfs_get( $lang )['labels'];
			foreach ( $q->posts as $p ) {
				$f     = cvfs_trip_facts( $p->ID );
				$out[] = array(
					'id'    => $p->ID,
					'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
					'url'   => get_permalink( $p ),
					'meta'  => cvfs_auto_meta( $p->ID, $labels ),
					'price' => cvfs_trip_details( $p->ID )['price'],
					'image' => cvfs_trip_image( $p->ID )[0],
				);
			}
			return $out;
		}
	);
}
