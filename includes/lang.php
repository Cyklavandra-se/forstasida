<?php
/**
 * WPML-hjälpare (fungerar också utan WPML). Samma upplägg som i AI-sök.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvfs_has_wpml() {
	return defined( 'ICL_SITEPRESS_VERSION' ) || has_filter( 'wpml_current_language' );
}

function cvfs_current_lang() {
	$lang = apply_filters( 'wpml_current_language', null );
	if ( ! $lang || 'all' === $lang ) {
		$lang = cvfs_default_lang();
	}
	return sanitize_key( $lang );
}

function cvfs_default_lang() {
	$lang = apply_filters( 'wpml_default_language', null );
	return $lang ? sanitize_key( $lang ) : substr( get_locale(), 0, 2 );
}

/** WPML-koder → våra språknycklar (norska "no" → "nb"). */
function cvfs_norm_lang( $lang ) {
	$lang = strtolower( (string) $lang );
	$map  = array(
		'no' => 'nb',
		'nn' => 'nb',
		'se' => 'sv',
	);
	return $map[ $lang ] ?? substr( $lang, 0, 2 );
}

/** Aktiva språk: array( 'sv' => 'Svenska', 'no' => 'Norska', … ), standardspråket först. */
function cvfs_active_langs() {
	$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
	$out   = array();
	if ( is_array( $langs ) && $langs ) {
		foreach ( $langs as $code => $l ) {
			$out[ sanitize_key( $code ) ] = $l['translated_name'] ?? ( $l['native_name'] ?? $code );
		}
	} else {
		$code         = cvfs_current_lang();
		$out[ $code ] = 'sv' === $code ? 'Svenska' : strtoupper( $code );
	}
	$def = cvfs_default_lang();
	if ( isset( $out[ $def ] ) ) {
		$out = array( $def => $out[ $def ] ) + $out;
	}
	return $out;
}

/** Kör $fn med WPML växlat till $lang, och växla sedan tillbaka. */
function cvfs_in_lang( $lang, $fn ) {
	if ( ! cvfs_has_wpml() || ! $lang ) {
		return $fn();
	}
	$prev = apply_filters( 'wpml_current_language', null );
	do_action( 'wpml_switch_language', $lang );
	try {
		return $fn();
	} finally {
		do_action( 'wpml_switch_language', $prev );
	}
}

/**
 * Sidans id i språket $lang. Saknas översättning: originalet om $fallback är sant,
 * annars 0 (så att t.ex. en svensk resesida inte dyker upp på den engelska förstasidan).
 * Utan WPML returneras alltid id:t.
 */
function cvfs_object_id( $id, $lang, $fallback = false, $type = 'page' ) {
	if ( ! cvfs_has_wpml() ) {
		return (int) $id;
	}
	$tr = apply_filters( 'wpml_object_id', (int) $id, $type, false, $lang );
	if ( $tr ) {
		return (int) $tr;
	}
	return $fallback ? (int) $id : 0;
}

/**
 * Taggens term-id:n i språket $lang. Söker på slug och namn i alla varianter
 * (t.ex. "självguidad" / "sjalvguidad") och översätter via WPML.
 *
 * @return int[]
 */
function cvfs_tag_ids( $names, $lang ) {
	$ids = array();
	foreach ( (array) $names as $name ) {
		$name = trim( (string) $name );
		if ( '' === $name ) {
			continue;
		}
		// Taggarna heter som på svenska i standardspråket; slå upp där och översätt sedan.
		$found = cvfs_in_lang(
			cvfs_default_lang(),
			function () use ( $name ) {
				$f = array();
				foreach ( array( 'slug' => sanitize_title( $name ), 'name' => $name ) as $field => $value ) {
					$t = get_term_by( $field, $value, 'post_tag' );
					if ( $t ) {
						$f[] = (int) $t->term_id;
					}
				}
				return $f;
			}
		);
		foreach ( $found as $tid ) {
			$tr    = apply_filters( 'wpml_object_id', $tid, 'post_tag', false, $lang );
			$ids[] = $tr ? (int) $tr : $tid;
		}
	}
	return array_values( array_unique( $ids ) );
}
