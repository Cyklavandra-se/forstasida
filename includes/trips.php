<?php
/**
 * Resekort: från sparade val (sid-id + ev. egna texter) till färdiga kortdata.
 *
 * Titel, bild och länk hämtas från sidan (i aktuellt språk via WPML) om fälten är tomma.
 * Metaraden byggs av taggarna (guidad, hike1–4/bike1–4) om fältet är tomt.
 * Pris och antal dagar finns ännu inte som egna fält på resesidorna – de skrivs in per kort.
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
	$out  = array(
		'guided' => false,
		'kind'   => '',
		'level'  => 0,
	);
	$tags = get_the_tags( $post_id );
	if ( ! $tags || is_wp_error( $tags ) ) {
		return $out;
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
		if ( preg_match( '/^(hike|bike)([1-4])(?:-\d+)?$/', $slug, $m ) ) {
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
		$link        = (string) get_permalink( $post_id );
		$out['kind'] = false !== strpos( $link, '/cykla' ) ? 'bike' : ( false !== strpos( $link, '/vandra' ) ? 'hike' : '' );
	}
	return $out;
}

/**
 * Automatisk metarad, t.ex. "Vandring · Nivå 1", "Guidad vandring" eller "Cykling · Nyhet 2027".
 *
 * @param string $extra Text som läggs sist i stället för nivån (nyhetsetiketten).
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
	} elseif ( $f['level'] && ! $f['guided'] ) {
		$parts[] = trim( ( $labels['level'] ?? '' ) . ' ' . $f['level'] );
	}
	return implode( ' · ', array_filter( $parts ) );
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
 * @return array[] { url, title, image, alt, meta, price }
 */
function cvfs_cards( $trips, $s, $lang, $extra = '' ) {
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
			'price' => '' !== ( $t['price'] ?? '' ) ? trim( $s['labels']['from'] . ' ' . $t['price'] ) : '',
		);
	}
	return $cards;
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
			$q   = new WP_Query( $args );
			$out = array();
			foreach ( $q->posts as $p ) {
				$f     = cvfs_trip_facts( $p->ID );
				$out[] = array(
					'id'    => $p->ID,
					'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
					'url'   => get_permalink( $p ),
					'meta'  => cvfs_auto_meta( $p->ID, cvfs_get( $lang )['labels'] ),
					'image' => cvfs_trip_image( $p->ID )[0],
				);
			}
			return $out;
		}
	);
}
