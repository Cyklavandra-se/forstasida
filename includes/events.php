<?php
/**
 * Guidade resor automatiskt från The Events Calendar: alla resor med kommande avgång,
 * sorterade efter nästa avresa.
 *
 * Varje avgång (tribe_events) pekar på sin resesida via fältet "Webbplats" (_EventURL).
 * Flera avgångar för samma resa blir ett kort (den närmaste avgången). Avgångar utan
 * resesida blir ett kort som länkar till avgången i kalendern.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kommande avgångar på standardspråket, en per resa, i datumordning.
 *
 * @return array[] { page: int (0 = ingen resesida), event: int, start: 'Y-m-d', end: 'Y-m-d', cost: string }
 */
function cvfs_event_departures() {
	$key = 'cvfs_ev_' . cvfs_cache_ver() . '_' . current_time( 'Ymd' );
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return $hit;
	}

	$out = cvfs_in_lang(
		cvfs_default_lang(),
		function () {
			$now    = current_time( 'Y-m-d H:i:s' );
			$events = array();
			if ( function_exists( 'tribe_get_events' ) ) {
				$events = tribe_get_events(
					array(
						'start_date'       => $now,
						'posts_per_page'   => 300,
						'eventDisplay'     => 'list',
						'suppress_filters' => false,
					)
				);
			} elseif ( post_type_exists( 'tribe_events' ) ) {
				$q      = new WP_Query(
					array(
						'post_type'        => 'tribe_events',
						'post_status'      => 'publish',
						'posts_per_page'   => 300,
						'no_found_rows'    => true,
						'suppress_filters' => false,
						'meta_key'         => '_EventStartDate', // phpcs:ignore WordPress.DB.SlowDBQuery
						'orderby'          => 'meta_value',
						'order'            => 'ASC',
						'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery
							array(
								'key'     => '_EventStartDate',
								'value'   => $now,
								'compare' => '>=', // "Y-m-d H:i:s" sorteras rätt som text.
							),
						),
					)
				);
				$events = $q->posts;
			}

			$rows = array();
			foreach ( (array) $events as $e ) {
				$start = function_exists( 'tribe_get_start_date' ) ? tribe_get_start_date( $e, false, 'Y-m-d' ) : substr( (string) get_post_meta( $e->ID, '_EventStartDate', true ), 0, 10 );
				$end   = function_exists( 'tribe_get_end_date' ) ? tribe_get_end_date( $e, false, 'Y-m-d' ) : substr( (string) get_post_meta( $e->ID, '_EventEndDate', true ), 0, 10 );
				$url   = function_exists( 'tribe_get_event_website_url' ) ? tribe_get_event_website_url( $e ) : get_post_meta( $e->ID, '_EventURL', true );
				$cost  = function_exists( 'tribe_get_cost' ) ? tribe_get_cost( $e ) : get_post_meta( $e->ID, '_EventCost', true );
				$page  = cvfs_url_to_page_id( trim( (string) $url ) );
				$k     = $page ? 'p' . $page : 'e' . $e->ID;
				if ( isset( $rows[ $k ] ) || ! $start ) {
					continue; // Bara närmaste avgången per resa.
				}
				$rows[ $k ] = array(
					'page'  => $page,
					'event' => (int) $e->ID,
					'start' => $start,
					'end'   => $end ? $end : $start,
					'cost'  => trim( wp_strip_all_tags( (string) $cost ) ),
				);
			}
			uasort(
				$rows,
				function ( $a, $b ) {
					return strcmp( $a['start'], $b['start'] );
				}
			);
			return array_slice( array_values( $rows ), 0, 40 );
		}
	);

	set_transient( $key, $out, HOUR_IN_SECONDS );
	return $out;
}

/** Sidans id för en adress på sajten (standardspråket), 0 om den inte hittas. */
function cvfs_url_to_page_id( $url ) {
	if ( '' === $url ) {
		return 0;
	}
	$home = wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST );
	if ( wp_parse_url( $url, PHP_URL_HOST ) !== $home ) {
		return 0;
	}
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$page = '' !== $path ? get_page_by_path( $path, OBJECT, 'page' ) : null;
	if ( $page ) {
		return (int) $page->ID;
	}
	$id = url_to_postid( $url );
	return ( $id && 'page' === get_post_type( $id ) ) ? (int) $id : 0;
}

/** "25950kr" → "25 950 kr", "1185 €" → "1 185 €". Okänt format returneras som det är. */
function cvfs_format_cost( $cost ) {
	$cost = trim( (string) $cost );
	if ( '' === $cost || ! preg_match( '/(\d[\d\s.,]*)/u', $cost, $m ) ) {
		return '';
	}
	$num = (int) preg_replace( '/\D/', '', $m[1] );
	if ( ! $num ) {
		return '';
	}
	$cur = preg_match( '/€|eur/i', $cost ) ? '€' : ( preg_match( '/kr|sek/i', $cost ) ? 'kr' : '' );
	return trim( number_format( $num, 0, ',', ' ' ) . ' ' . $cur );
}

/**
 * Kort för guidade resor från kalendern, i språket $lang.
 *
 * @param array $s Språkets (effektiva) inställningar; guided.trips ger egna texter/priser per resa.
 * @return array[] Samma format som cvfs_cards().
 */
function cvfs_event_cards( $s, $lang ) {
	$own = array();
	foreach ( (array) $s['guided']['trips'] as $t ) {
		$own[ (int) $t['id'] ] = $t;
	}
	$labels = $s['labels'];
	$cards  = array();
	$seen   = array();

	foreach ( cvfs_event_departures() as $d ) {
		$o = $d['page'] ? ( $own[ $d['page'] ] ?? array() ) : array();
		if ( $d['page'] ) {
			$id = cvfs_object_id( $d['page'], $lang );
			$p  = $id ? get_post( $id ) : null;
			if ( ! $p || 'publish' !== $p->post_status ) {
				continue;
			}
			list( $img, $alt ) = cvfs_trip_image( $id );
			$title             = get_the_title( $id );
			$url               = get_permalink( $id );
			$facts             = cvfs_trip_facts( $id );
		} else {
			$id = cvfs_object_id( $d['event'], $lang, false, 'tribe_events' );
			$p  = $id ? get_post( $id ) : null;
			if ( ! $p || 'publish' !== $p->post_status ) {
				continue;
			}
			$thumb = get_post_thumbnail_id( $id );
			$img   = $thumb ? (string) wp_get_attachment_image_url( $thumb, 'large' ) : '';
			$title = get_the_title( $id );
			$url   = get_permalink( $id );
			$facts = array( 'kind' => '' );
		}
		if ( isset( $seen[ $url ] ) ) {
			continue;
		}
		$seen[ $url ] = true;

		if ( '' !== (string) ( $o['title'] ?? '' ) ) {
			$title = $o['title'];
		}
		if ( '' !== (string) ( $o['image'] ?? '' ) ) {
			$img = $o['image'];
		}
		$title = html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' );

		// Metarad: egen text, annars typ · antal dagar. Nästa avresa läggs alltid till.
		$meta = (string) ( $o['meta'] ?? '' );
		if ( '' === $meta ) {
			$kind  = $facts['kind'] ?? '';
			$parts = array( $kind ? ( $labels[ 'guided_' . $kind ] ?? '' ) : ( $labels['guided'] ?? '' ) );
			$days  = (int) round( ( strtotime( $d['end'] ) - strtotime( $d['start'] ) ) / DAY_IN_SECONDS ) + 1;
			if ( $days > 1 ) {
				$parts[] = $days . ' ' . ( $labels['days'] ?? '' );
			}
			$meta = implode( ' · ', array_filter( array_map( 'trim', $parts ) ) );
		}
		$ts     = strtotime( $d['start'] );
		$format = ( $ts - current_time( 'timestamp' ) > 300 * DAY_IN_SECONDS ) ? 'j M Y' : 'j M'; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
		$date   = cvfs_in_lang(
			$lang,
			function () use ( $format, $ts ) {
				return date_i18n( $format, $ts );
			}
		);
		$meta = trim( $meta . ' · ' . trim( ( $labels['next'] ?? '' ) . ' ' . $date ), ' ·' );

		// Pris: eget, annars avgångens kostnad, annars resesidans faktaruta.
		$price = trim( (string) ( $o['price'] ?? '' ) );
		if ( '' === $price ) {
			$price = cvfs_format_cost( $d['cost'] );
		}
		if ( '' === $price && $d['page'] && ! empty( $s['guided']['auto_price'] ) ) {
			$price = cvfs_trip_details( $id )['price'];
		}

		$cards[] = array(
			'url'   => $url,
			'title' => $title,
			'image' => $img,
			'alt'   => $title,
			'meta'  => $meta,
			'price' => '' === $price ? '' : trim( ( $labels['from'] ?? '' ) . ' ' . $price ),
		);
	}
	return $cards;
}

/**
 * Resesidorna (sid-id) med kommande avgång, i datumordning – för admin och "Följ svenska".
 *
 * @return array[] Rader i samma format som sparade resor.
 */
function cvfs_event_trip_rows() {
	$rows = array();
	foreach ( cvfs_event_departures() as $d ) {
		if ( $d['page'] ) {
			$rows[] = cvfs_trip( $d['page'] );
		}
	}
	return $rows;
}
