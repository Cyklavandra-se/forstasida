<?php
/**
 * "Följ svenska": andra språk hämtar resor, bilder och länkar från standardspråket.
 *
 * Bara det som inte behöver översättas följer (resor och deras ordning, bilder, länkar,
 * av/på-val). Texterna är alltid språkets egna – de fylls i med knappen
 * "Översätt ändringarna till alla språk" (includes/translate.php) eller för hand.
 *
 * Länkar till sidor och kategorier översätts till språket via WPML. Kortens rubrik och
 * metarad tas alltid från språkets egen rad för samma resa (aldrig den svenska texten).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Sektioner som kan följa standardspråket, med etikett för admin. */
function cvfs_follow_sections() {
	return array( 'hero', 'paths', 'guided', 'popular', 'news', 'blog', 'band' );
}

/** True om språket är standardspråket (svenska), som de andra följer. */
function cvfs_is_master( $lang ) {
	return $lang === cvfs_default_lang();
}

/**
 * Inställningarna som faktiskt gäller för ett språk: språkets egna, med standardspråkets
 * resor, bilder och länkar i de sektioner som följer.
 */
function cvfs_effective( $lang ) {
	$s = cvfs_get( $lang );
	if ( cvfs_is_master( $lang ) ) {
		return $s;
	}
	$m = cvfs_get( cvfs_default_lang() );
	$f = (array) ( $s['follow'] ?? array() );

	if ( ! empty( $f['hero'] ) ) {
		$s['hero']['images']        = $m['hero']['images'];
		$s['hero']['images_mobile'] = $m['hero']['images_mobile'];
	}
	if ( ! empty( $f['paths'] ) ) {
		foreach ( $s['paths'] as $k => $p ) {
			$s['paths'][ $k ]['url'] = cvfs_translate_url( $m['paths'][ $k ]['url'] ?? '', $lang );
		}
	}
	foreach ( array( 'guided', 'popular', 'news' ) as $sec ) {
		if ( empty( $f[ $sec ] ) ) {
			continue;
		}
		$s[ $sec ]['url']        = cvfs_translate_url( $m[ $sec ]['url'], $lang );
		$s[ $sec ]['auto_price'] = $m[ $sec ]['auto_price'];
		$s[ $sec ]['scroll_desktop'] = $m[ $sec ]['scroll_desktop'];
		$s[ $sec ]['scroll_mobile']  = $m[ $sec ]['scroll_mobile'];
		if ( 'guided' === $sec ) {
			$s['guided']['mode'] = $m['guided']['mode'];
		}
		if ( 'news' === $sec ) {
			$s['news']['tag']   = $m['news']['tag'];
			$s['news']['tag_auto'] = $m['news']['tag_auto'];
			$s['news']['count'] = $m['news']['count'];
			$s['news']['mode']  = 'manual'; // Resorna nedan är redan standardspråkets urval.
			$s['news']['shuffle'] = 'random' === $m['news']['mode'] ? 1 : 0;
		}
		$master_trips = cvfs_master_trips( $sec, $m );
		$s[ $sec ]['trips'] = cvfs_follow_trips( $master_trips, $s[ $sec ]['trips'] );
	}
	if ( ! empty( $f['blog'] ) ) {
		foreach ( array( 'show', 'count', 'words' ) as $k ) {
			$s['blog'][ $k ] = $m['blog'][ $k ];
		}
		$s['blog']['url']      = cvfs_translate_url( $m['blog']['url'], $lang );
		$s['blog']['category'] = cvfs_translate_category( $m['blog']['category'], $lang );
	}
	if ( ! empty( $f['band'] ) ) {
		$s['band']['show_map'] = $m['band']['show_map'];
		foreach ( $s['band']['buttons'] as $i => $b ) {
			$s['band']['buttons'][ $i ]['url']     = cvfs_translate_url( $m['band']['buttons'][ $i ]['url'] ?? '', $lang );
			$s['band']['buttons'][ $i ]['new_tab'] = $m['band']['buttons'][ $i ]['new_tab'] ?? 0;
		}
		foreach ( $s['band']['images'] as $i => $im ) {
			$s['band']['images'][ $i ]['url'] = $m['band']['images'][ $i ]['url'] ?? '';
		}
	}
	return $s;
}

/**
 * Standardspråkets resor i en sektion. För nyheter i automatiskt läge: samma resor som
 * visas på standardspråket (inte språkets egen taggsökning, som kan ge andra resor när de
 * översatta sidorna har andra taggar).
 */
function cvfs_master_trips( $sec, $m = null ) {
	$m = $m ? $m : cvfs_get( cvfs_default_lang() );
	if ( 'guided' === $sec && 'events' === $m['guided']['mode'] ) {
		return cvfs_event_trip_rows();
	}
	if ( 'news' === $sec && in_array( $m['news']['mode'], array( 'auto', 'random' ), true ) ) {
		return cvfs_in_lang(
			cvfs_default_lang(),
			function () use ( $m ) {
				return cvfs_auto_news( $m, cvfs_default_lang() );
			}
		);
	}
	return $m[ $sec ]['trips'];
}

/**
 * Standardspråkets resor (urval och ordning) med språkets egna texter för samma resa.
 * Rubrik och metarad tas bara från språket (tomt = hämtas från den översatta sidan).
 * Pris och bild: språkets eget om det finns, annars standardspråkets.
 */
function cvfs_follow_trips( $master_trips, $own_trips, $fallback = true ) {
	$own = array();
	foreach ( (array) $own_trips as $t ) {
		$own[ (int) $t['id'] ] = $t;
	}
	$out = array();
	foreach ( (array) $master_trips as $t ) {
		$o     = $own[ (int) $t['id'] ] ?? array();
		$out[] = array(
			'id'    => (int) $t['id'],
			'title' => (string) ( $o['title'] ?? '' ),
			'meta'  => (string) ( $o['meta'] ?? '' ),
			'price' => '' !== (string) ( $o['price'] ?? '' ) || ! $fallback ? (string) ( $o['price'] ?? '' ) : (string) ( $t['price'] ?? '' ),
			'image' => '' !== (string) ( $o['image'] ?? '' ) || ! $fallback ? (string) ( $o['image'] ?? '' ) : (string) ( $t['image'] ?? '' ),
		);
	}
	return $out;
}

/** Cache-nyckelns version; höjs när inställningarna sparas. */
function cvfs_cache_ver() {
	return (int) get_option( 'cvfs_cache_ver', 1 );
}

function cvfs_bump_cache() {
	update_option( 'cvfs_cache_ver', cvfs_cache_ver() + 1, false );
}
add_action( 'cvfs_saved', 'cvfs_bump_cache' );

/**
 * En adress på standardspråket → samma sida/kategori på $lang (via WPML).
 * Externa adresser och sidor utan översättning returneras oförändrade.
 */
function cvfs_translate_url( $url, $lang ) {
	$url = (string) $url;
	if ( '' === $url || cvfs_is_master( $lang ) || ! cvfs_has_wpml() ) {
		return $url;
	}
	$home = wp_parse_url( (string) get_option( 'home' ), PHP_URL_HOST );
	if ( wp_parse_url( $url, PHP_URL_HOST ) !== $home ) {
		return $url;
	}

	static $mem = array();
	$key = 'cvfs_u_' . md5( $lang . '|' . $url . '|' . cvfs_cache_ver() );
	if ( isset( $mem[ $key ] ) ) {
		return $mem[ $key ];
	}
	$hit = get_transient( $key );
	if ( is_string( $hit ) && '' !== $hit ) {
		$mem[ $key ] = $hit;
		return $hit;
	}

	$out = cvfs_in_lang(
		cvfs_default_lang(),
		function () use ( $url, $lang ) {
			// Kategoriarkiv, t.ex. /category/nyheter/.
			$base = trim( (string) get_option( 'category_base' ), '/' );
			$base = '' !== $base ? $base : 'category';
			$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
			if ( preg_match( '#(^|/)' . preg_quote( $base, '#' ) . '/([^/]+)$#', $path, $m ) ) {
				$term = get_term_by( 'slug', $m[2], 'category' );
				if ( $term ) {
					$tr = (int) apply_filters( 'wpml_object_id', (int) $term->term_id, 'category', false, $lang );
					if ( $tr ) {
						return cvfs_in_lang(
							$lang,
							function () use ( $tr ) {
								$link = get_term_link( $tr, 'category' );
								return is_wp_error( $link ) ? '' : $link;
							}
						);
					}
				}
				return '';
			}
			// Sidor: först via sökvägen (t.ex. vandra/guidade), sedan WordPress egen tolkning.
			$page = '' !== $path ? get_page_by_path( $path, OBJECT, 'page' ) : null;
			$id   = $page ? (int) $page->ID : url_to_postid( $url );
			if ( ! $id ) {
				return '';
			}
			$tr = (int) apply_filters( 'wpml_object_id', $id, get_post_type( $id ), false, $lang );
			if ( ! $tr ) {
				return '';
			}
			return cvfs_in_lang(
				$lang,
				function () use ( $tr ) {
					return (string) get_permalink( $tr );
				}
			);
		}
	);
	if ( ! $out ) {
		// Hittades inte: visa originaladressen, men spara inte i cachen (prövas igen nästa gång).
		$mem[ $key ] = $url;
		return $url;
	}

	set_transient( $key, $out, DAY_IN_SECONDS );
	$mem[ $key ] = $out;
	return $out;
}

/** Bloggkategorins slug på standardspråket → slug på $lang (via WPML). */
function cvfs_translate_category( $slug, $lang ) {
	if ( '' === (string) $slug || cvfs_is_master( $lang ) || ! cvfs_has_wpml() ) {
		return $slug;
	}
	$term = cvfs_in_lang(
		cvfs_default_lang(),
		function () use ( $slug ) {
			return get_term_by( 'slug', $slug, 'category' );
		}
	);
	if ( ! $term ) {
		return $slug;
	}
	$tr = (int) apply_filters( 'wpml_object_id', (int) $term->term_id, 'category', false, $lang );
	if ( ! $tr ) {
		return $slug;
	}
	$t = get_term( $tr, 'category' );
	return ( $t && ! is_wp_error( $t ) ) ? $t->slug : $slug;
}
