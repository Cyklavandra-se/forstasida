<?php
/**
 * Inställningar per språk: standardvärden, läsning och sparning.
 *
 * Allt ligger i optionen cvfs_settings som array( 'sv' => array(...), 'no' => array(...) ).
 * Nycklarna är WPML:s språkkoder. Saknade fält fylls alltid i från standardvärdena,
 * så nya inställningar fungerar direkt efter en uppdatering.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CVFS_OPTION', 'cvfs_settings' );

/** Bas-URL för standardvärdena (sajtens egen adress, så att de fungerar även lokalt). */
function cvfs_u( $path ) {
	// get_option i stället för home_url(), så att WPML inte lägger till språkprefix.
	return untrailingslashit( (string) get_option( 'home' ) ) . $path;
}

function cvfs_up( $path ) {
	return content_url( 'uploads/' . $path );
}

/** Ett resekort i standardvärdena. Tomma fält hämtas automatiskt från sidan. */
function cvfs_trip( $id, $title = '', $meta = '', $price = '' ) {
	return array(
		'id'    => (int) $id,
		'title' => $title,
		'meta'  => $meta,
		'price' => $price,
		'image' => '',
	);
}

/**
 * Standardvärden. Svenska = texterna i prototypen (sidan "Ny förstasida", page 36205).
 * Andra språk startar med samma upplägg, men utan svenska kortrubriker/priser och –
 * utom norska – utan guidade resor.
 */
function cvfs_defaults( $lang = 'sv' ) {
	$d = array(
		'show_guided' => 1,

		'hero'        => array(
			'title'       => 'Var vill du cykla eller vandra?',
			'lead'        => 'Beskriv din drömresa med egna ord – vi hittar resorna som passar.',
			'images'      => array(
				cvfs_up( '2020/12/12924658334_3daa5efc52_k.jpg' ),
				cvfs_up( '2020/12/44334843835_2e38fdab86_k.jpg' ),
				cvfs_up( '2024/09/PXL_20240826_093340787.jpg' ),
				cvfs_up( '2025/10/eurobike_radreisen_donauradweg_weingarten_radgruppe-21.jpg' ),
			),
			// Mobil (≤ 700 px): egna, gärna stående bilder. Tom lista = samma som ovan.
			'images_mobile' => array(),
			'placeholder' => 'T.ex. lätt cykling med vinprovning i september, två par',
			'button'      => 'Hitta resor',
		),

		'paths'       => array(
			'guided' => array(
				'title'     => 'Guidade resor',
				'text'      => 'Svensk guide, trevligt sällskap och fasta avresedatum. Vi tar hand om allt.',
				'link_text' => 'Se alla guidade resor →',
				'url'       => cvfs_u( '/vandra/guidade/' ),
			),
			'bike'   => array(
				'title'     => 'Cykla på egen hand',
				'text'      => 'Längs floder, vingårdar och kuster i din egen takt. Vi bokar hotell och flyttar bagaget.',
				'link_text' => 'Se alla cykelresor →',
				'url'       => cvfs_u( '/cykla/cykla-pa-egen-hand/' ),
			),
			'hike'   => array(
				'title'     => 'Vandra på egen hand',
				'text'      => 'Kustleder och bergsstigar med navigeringsapp, bagagetransport och support under resan.',
				'link_text' => 'Se alla vandringsresor →',
				'url'       => cvfs_u( '/vandra/pa-egen-hand/' ),
			),
		),

		'facts'       => array(
			array(
				'title' => 'Cykel- och vandringsresor sedan 2009',
				'sub'   => 'Erfarenhet av cykel- och vandringsresor',
			),
			array(
				'title' => 'Tusentals nöjda resenärer',
				'sub'   => 'Från ett tjugotal länder',
			),
			array(
				'title' => 'Support under resan',
				'sub'   => 'På alla resor på egen hand',
			),
			array(
				'title' => 'Bagagetransport och app',
				'sub'   => 'Ingår i alla resor på egen hand',
			),
		),

		'guided'      => array(
			'title'     => 'Aktuella guidade resor',
			'link_text' => 'Alla guidade resor →',
			'url'        => cvfs_u( '/vandra/guidade/' ),
			// events = alla resor med kommande avgång i The Events Calendar, sorterade efter nästa
			// avresa (listan nedan används då bara för egna rubriker/priser per resa); manual = listan.
			'mode'       => 'events',
			// Rad i sidled (fler kort åt höger) på dator respektive mobil. Av = högst 5 kort.
			'scroll_desktop' => 1,
			'scroll_mobile'  => 1,
			// Hämta priset ur faktarutan när kortets prisfält är tomt.
			'auto_price' => 1,
			// Guidade resor har ingen faktaruta – dagar och pris står här.
			'trips'      => array(
				cvfs_trip( 1821, 'Vandra i Andorra – magiska Pyrenéerna', 'Guidad vandring · 8 dagar', '21 950 kr' ),
				cvfs_trip( 19019, 'Vandringsparadiset La Gomera', 'Guidad vandring · 8 dagar', '25 950 kr' ),
				cvfs_trip( 9772, 'Rioja & San Sebastián', 'Guidad vandring · 8 dagar', '29 750 kr' ),
				cvfs_trip( 2468, 'Piemonte & Ligurien', 'Guidad vandring · 7 dagar', '23 800 kr' ),
				cvfs_trip( 3370, 'Korfu, Paxos och Albanien', 'Guidad vandring · 8 dagar', '25 700 kr' ),
			),
		),

		'popular'     => array(
			'title'     => 'Populära resor på egen hand',
			'link_text' => 'Alla resor på egen hand →',
			'url'        => cvfs_u( '/sjalvguidade/' ),
			'scroll_desktop' => 1,
			'scroll_mobile'  => 1,
			'auto_price' => 1,
			'trips'      => array(
				// Dagar och pris läses ur faktarutan på resesidan.
				cvfs_trip( 18881, 'Costa Bravas pärlor – kustvandring' ),
				cvfs_trip( 22059, 'Canal du Midi – Toulouse till Medelhavet' ),
				cvfs_trip( 16884, 'Vincykling längs Mosel' ),
				cvfs_trip( 3877, 'San Sebastián – kustvandring längs pilgrimsleden' ),
				// Sidan har taggen bike2, men prototypen säger Nivå 1.
				cvfs_trip( 33232, 'Donau i lugn takt – Wachau och Wien', 'Cykling · 5 dagar · Nivå 1' ),
			),
		),

		'news'        => array(
			// {år} byts mot säsongens år (se cvfs_season_year()).
			'title'     => 'Nyheter för säsongen {år}',
			'link_text' => 'Se alla nyheter →',
			'url'       => cvfs_u( '/information/hitta-resa/destinationer/nyheter/' ),
			// manual = korten nedan; auto = sidor med taggen nedan + självguidad, nyast först;
			// random = samma sidor, men webbläsaren väljer och blandar `count` av dem vid varje besök.
			'mode'      => 'manual',
			'tag'       => '2027',
			// Taggen = säsongens år automatiskt (byts 1 september till nästa år).
			'tag_auto'  => 1,
			// Antal kort när raden i sidled är av (och antal i slumpläget).
			'count'     => 4,
			'label'     => 'Nyhet {år}',
			'scroll_desktop' => 1,
			'scroll_mobile'  => 1,
			// Prototypen visar inget pris på nyheterna.
			'auto_price' => 0,
			'trips'     => array(
				// Metaraden blir automatiskt typ + etiketten, t.ex. "Cykling · Nyhet 2027".
				cvfs_trip( 35210, 'Madeira runt på cykel' ),
				cvfs_trip( 35098, 'Cykla på Lanzarote – vulkanernas ö' ),
				cvfs_trip( 34196, 'Mallorca – Tramuntana till fots' ),
				cvfs_trip( 34064, 'Mellan Alperna och Provence – Vercors' ),
			),
		),

		'blog'        => array(
			'show'        => 1,
			'title'       => 'Nyhetsbrev',
			'link_text'   => 'Alla inlägg →',
			'url'         => cvfs_u( '/category/nyheter/' ),
			'category'    => 'nyheter',
			// Antal inlägg när raden i sidled är av.
			'count'       => 3,
			// Rad i sidled (dator/mobil) och antal inlägg i raden.
			'scroll_desktop' => 1,
			'scroll_mobile'  => 1,
			'rail_count'     => 12,
			'words'       => 17,
			'date_format' => 'j F Y',
		),

		'band'        => array(
			// Kartan över resorna ([resekarta]) under bandet.
			'show_map' => 1,
			'title'   => 'Följ oss och få inspiration!',
			'text'    => 'Bilder, tips och nyheter från våra resor.',
			'buttons' => array(
				array(
					'text'    => 'Nyhetsbrev',
					'url'     => cvfs_u( '/information/nyhetsbrev/' ),
					'new_tab' => 0,
				),
				array(
					'text'    => 'Instagram',
					'url'     => 'https://www.instagram.com/cyklavandra.se',
					'new_tab' => 1,
				),
				array(
					'text'    => 'Facebook',
					'url'     => 'https://www.facebook.com/cyklavandra/',
					'new_tab' => 1,
				),
			),
			'images'  => array(
				array(
					'url' => cvfs_up( '2026/11/Algarve-latt-vandring-1024x538.jpg' ),
					'alt' => 'Algarve',
				),
				array(
					'url' => cvfs_up( '2026/11/Julia-alperna-1024x538.jpg' ),
					'alt' => 'Julianska alperna',
				),
				array(
					'url' => cvfs_up( '2026/10/Skotska-Hoglanderna-1024x538.jpg' ),
					'alt' => 'Skotska högländerna',
				),
				array(
					'url' => cvfs_up( '2026/10/Balaton-1024x538.jpg' ),
					'alt' => 'Balaton',
				),
			),
		),

		// Ord som används när kortens metarad byggs automatiskt (tomt fält "Metarad").
		'labels'      => cvfs_default_labels( $lang ),
	);

	if ( 'sv' !== $lang ) {
		// Kortrubriker, metarader och priser i standardvärdena är svenska – hämta i stället
		// titeln från den översatta sidan och bygg metaraden automatiskt.
		foreach ( array( 'guided', 'popular', 'news' ) as $sec ) {
			foreach ( $d[ $sec ]['trips'] as $i => $t ) {
				$d[ $sec ]['trips'][ $i ] = cvfs_trip( $t['id'] );
			}
		}
	}

	if ( 'nb' === cvfs_norm_lang( $lang ) ) {
		$d = cvfs_defaults_nb( $d );
	}

	// Datum i blogglistan enligt språkets skrivsätt (23 november 2026 / 23. november 2026 / 23.11.2026).
	$dates = array(
		'nb' => 'j. F Y',
		'da' => 'j. F Y',
		'de' => 'j. F Y',
		'is' => 'j. F Y',
		'fi' => 'j.n.Y',
		'en' => 'j F Y',
	);
	if ( isset( $dates[ cvfs_norm_lang( $lang ) ] ) ) {
		$d['blog']['date_format'] = $dates[ cvfs_norm_lang( $lang ) ];
	}

	// Språkregeln: guidade resor bara på svenska och norska (guidningen sker på svenska).
	$d['show_guided'] = in_array( cvfs_norm_lang( $lang ), array( 'sv', 'nb' ), true ) ? 1 : 0;

	// Andra språk följer standardspråkets resor, bilder och länkar (se includes/follow.php).
	$d['follow'] = array_fill_keys( cvfs_follow_sections(), 1 );

	return apply_filters( 'cvfs_defaults', $d, $lang );
}

/**
 * Norska standardvärden: samma upplägg, resor och bilder som den svenska sidan, med
 * översatta texter och norska adresser (från sidornas hreflang-länkar, nov 2026).
 * Kortens dagar och priser hämtas ur de norska resesidornas faktaruta ("Lengde", "Pris").
 */
function cvfs_defaults_nb( $d ) {
	$d['hero']['title']       = 'Hvor vil du sykle eller vandre?';
	$d['hero']['lead']        = 'Beskriv drømmereisen med egne ord – vi finner reisene som passer.';
	$d['hero']['placeholder'] = 'F.eks. lett sykling med vinsmaking i september, to par';
	$d['hero']['button']      = 'Finn reiser';

	$d['paths']['guided'] = array(
		'title'     => 'Guidede reiser',
		'text'      => 'Svensk guide, hyggelig reisefølge og faste avreisedatoer. Vi tar oss av alt.',
		'link_text' => 'Se alle guidede reiser →',
		'url'       => cvfs_u( '/no/vandring/guidede-turer/' ),
	);
	$d['paths']['bike']   = array(
		'title'     => 'Sykle på egen hånd',
		'text'      => 'Langs elver, vingårder og kyster i ditt eget tempo. Vi bestiller hotell og flytter bagasjen.',
		'link_text' => 'Se alle sykkelreiser →',
		'url'       => cvfs_u( '/no/sykling/sykling-pa-egen-hand/' ),
	);
	$d['paths']['hike']   = array(
		'title'     => 'Vandre på egen hånd',
		'text'      => 'Kyststier og fjellstier med navigasjonsapp, bagasjetransport og support underveis.',
		'link_text' => 'Se alle vandreturer →',
		'url'       => cvfs_u( '/no/vandring/vandreferie-pa-egen-hand/' ),
	);

	$d['facts'] = array(
		array(
			'title' => 'Sykkel- og vandreturer siden 2009',
			'sub'   => 'Erfaring med sykkel- og vandreturer',
		),
		array(
			'title' => 'Tusenvis av fornøyde reisende',
			'sub'   => 'Fra rundt tjue land',
		),
		array(
			'title' => 'Support underveis',
			'sub'   => 'På alle reiser på egen hånd',
		),
		array(
			'title' => 'Bagasjetransport og app',
			'sub'   => 'Inngår i alle reiser på egen hånd',
		),
	);

	$d['guided']['title']     = 'Aktuelle guidede reiser';
	$d['guided']['link_text'] = 'Alle guidede reiser →';
	$d['guided']['url']       = cvfs_u( '/no/vandring/guidede-turer/' );
	// Guidede reiser har ingen faktaruta. Prisene står i kronor også på de norske sidene.
	$d['guided']['trips'] = array(
		cvfs_trip( 1821, 'Vandre i Andorra – magiske Pyreneene', 'Guidet vandring · 8 dager', '21 950 kr' ),
		cvfs_trip( 19019, 'Vandreparadiset La Gomera', 'Guidet vandring · 8 dager', '25 950 kr' ),
		cvfs_trip( 9772, 'Rioja & San Sebastián', 'Guidet vandring · 8 dager', '29 750 kr' ),
		cvfs_trip( 2468, 'Piemonte & Liguria', 'Guidet vandring · 7 dager', '23 800 kr' ),
		cvfs_trip( 3370, 'Korfu, Paxos og Albania', 'Guidet vandring · 8 dager', '25 700 kr' ),
	);

	$d['popular']['title']     = 'Populære reiser på egen hånd';
	$d['popular']['link_text'] = 'Alle reiser på egen hånd →';
	$d['popular']['url']       = cvfs_u( '/no/reise-pa-egen-hand/' );
	$d['popular']['trips']     = array(
		cvfs_trip( 18881, 'Costa Bravas perler – kystvandring' ),
		cvfs_trip( 22059, 'Canal du Midi – Toulouse til Middelhavet' ),
		cvfs_trip( 16884, 'Vinsykling langs Mosel' ),
		// Den norske siden mangler "Lengde" i faktaruten.
		cvfs_trip( 3877, 'San Sebastián – kystvandring langs pilegrimsleden', 'Vandring · 5 dager · Nivå 2' ),
		// Som på svensk: taggen er bike2, men prototypen sier Nivå 1.
		cvfs_trip( 33232, 'Donau i rolig tempo – Wachau og Wien', 'Sykling · 5 dager · Nivå 1' ),
	);

	$d['news']['title']     = 'Nyheter for sesongen {år}';
	$d['news']['link_text'] = 'Se alle nyheter →';
	$d['news']['url']       = cvfs_u( '/no/informasjon-om-programmet/finn-en-reise-for-sykling-og-fotturer/reisemal/nyheter-nye-destinasjoner-denne-sesongen/' );
	$d['news']['label']     = 'Nyhet {år}';
	$d['news']['trips']     = array(
		cvfs_trip( 35210, 'Madeira rundt på sykkel' ),
		cvfs_trip( 35098, 'Sykle på Lanzarote – vulkanenes øy' ),
		cvfs_trip( 34196, 'Mallorca – Tramuntana til fots' ),
		cvfs_trip( 34064, 'Mellom Alpene og Provence – Vercors' ),
	);

	// De norske innleggene ligger i kategorien "Nyheter" (slug nyheter-no, oversettelsen av "nyheter").
	$d['blog']['title']       = 'Nyhetsbrev';
	$d['blog']['link_text']   = 'Alle innlegg →';
	$d['blog']['url']         = cvfs_u( '/no/category/nyheter-no/' );
	$d['blog']['category']    = 'nyheter-no';
	$d['blog']['date_format'] = 'j. F Y';

	$d['band']['title']              = 'Følg oss og bli inspirert!';
	$d['band']['text']               = 'Bilder, tips og nyheter fra våre reiser.';
	$d['band']['buttons'][0]['url']  = cvfs_u( '/no/informasjon-om-programmet/nyhetsbrev/' );
	$d['band']['images'][1]['alt']   = 'De julianske alpene';
	$d['band']['images'][2]['alt']   = 'Det skotske høylandet';

	return $d;
}

/** Ord i automatiska metarader, per språk. */
function cvfs_default_labels( $lang ) {
	$all = cvfs_all_labels();
	$l   = cvfs_norm_lang( $lang );
	return $all[ $l ] ?? $all['en'];
}

/** Inbyggda ord för automatiska metarader. Språk som saknas här översätts med knappen. */
function cvfs_all_labels() {
	return array(
		'sv' => array(
			'from'        => 'från',
			'hike'        => 'Vandring',
			'bike'        => 'Cykling',
			'guided_hike' => 'Guidad vandring',
			'guided_bike' => 'Guidad cykling',
			'level'       => 'Nivå',
			'days'        => 'dagar',
			'guided'      => 'Guidad resa',
			'next'        => 'Nästa avresa',
		),
		'nb' => array(
			'from'        => 'fra',
			'hike'        => 'Vandring',
			'bike'        => 'Sykling',
			'guided_hike' => 'Guidet vandring',
			'guided_bike' => 'Guidet sykling',
			'level'       => 'Nivå',
			'days'        => 'dager',
			'guided'      => 'Guidet reise',
			'next'        => 'Neste avreise',
		),
		'da' => array(
			'from'        => 'fra',
			'hike'        => 'Vandring',
			'bike'        => 'Cykling',
			'guided_hike' => 'Guidet vandring',
			'guided_bike' => 'Guidet cykling',
			'level'       => 'Niveau',
			'days'        => 'dage',
			'guided'      => 'Guidet rejse',
			'next'        => 'Næste afrejse',
		),
		'de' => array(
			'from'        => 'ab',
			'hike'        => 'Wandern',
			'bike'        => 'Radfahren',
			'guided_hike' => 'Geführte Wanderung',
			'guided_bike' => 'Geführte Radtour',
			'level'       => 'Stufe',
			'days'        => 'Tage',
			'guided'      => 'Geführte Reise',
			'next'        => 'Nächste Abreise',
		),
		'fi' => array(
			'from'        => 'alk.',
			'hike'        => 'Vaellus',
			'bike'        => 'Pyöräily',
			'guided_hike' => 'Opastettu vaellus',
			'guided_bike' => 'Opastettu pyöräily',
			'level'       => 'Taso',
			'days'        => 'päivää',
			'guided'      => 'Opastettu matka',
			'next'        => 'Seuraava lähtö',
		),
		'is' => array(
			'from'        => 'frá',
			'hike'        => 'Gönguferð',
			'bike'        => 'Hjólaferð',
			'guided_hike' => 'Leiðsögð gönguferð',
			'guided_bike' => 'Leiðsögð hjólaferð',
			'level'       => 'Erfiðleikastig',
			'days'        => 'dagar',
			'guided'      => 'Leiðsögð ferð',
			'next'        => 'Næsta brottför',
		),
		'en' => array(
			'from'        => 'from',
			'hike'        => 'Walking',
			'bike'        => 'Cycling',
			'guided_hike' => 'Guided walking',
			'guided_bike' => 'Guided cycling',
			'level'       => 'Level',
			'days'        => 'days',
			'guided'      => 'Guided tour',
			'next'        => 'Next departure',
		),
	);
}

/**
 * Säsongens år för nyheter: innevarande år till och med augusti, nästa år från 1 september.
 * (Nov 2026 → 2027, jan–aug 2027 → 2027, sep 2027 → 2028.)
 */
function cvfs_season_year() {
	$y = (int) current_time( 'Y' );
	return (int) current_time( 'n' ) >= (int) apply_filters( 'cvfs_season_month', 9 ) ? $y + 1 : $y;
}

/** Byt {år} (eller {year}) mot säsongens år. */
function cvfs_year_text( $text ) {
	return str_replace( array( '{år}', '{ar}', '{year}' ), (string) cvfs_season_year(), (string) $text );
}

/** True om arrayen är en lista (0, 1, 2 …) och inte en associativ array. */
function cvfs_is_list( $a ) {
	return is_array( $a ) && ( array() === $a || array_keys( $a ) === range( 0, count( $a ) - 1 ) );
}

/**
 * Lägg sparade värden över standardvärdena. Associativa arrayer slås ihop rekursivt,
 * listor (bilder, resor, faktarad …) ersätts helt.
 */
function cvfs_merge( $defaults, $saved ) {
	if ( ! is_array( $saved ) ) {
		return $defaults;
	}
	$out = $defaults;
	foreach ( $defaults as $k => $v ) {
		if ( ! array_key_exists( $k, $saved ) ) {
			continue;
		}
		if ( is_array( $v ) && ! cvfs_is_list( $v ) ) {
			$out[ $k ] = cvfs_merge( $v, $saved[ $k ] );
		} elseif ( is_array( $v ) ) {
			$out[ $k ] = is_array( $saved[ $k ] ) ? array_values( $saved[ $k ] ) : $v;
		} else {
			$out[ $k ] = is_array( $saved[ $k ] ) ? $v : $saved[ $k ];
		}
	}
	return $out;
}

/** Alla inställningar för ett språk (sparade + standardvärden). */
function cvfs_get( $lang = null ) {
	$lang = $lang ? $lang : cvfs_current_lang();
	$all  = get_option( CVFS_OPTION, array() );
	$saved = is_array( $all ) && isset( $all[ $lang ] ) ? $all[ $lang ] : array();
	return cvfs_merge( cvfs_defaults( $lang ), $saved );
}

/** Spara ett språk (redan tvättat). */
function cvfs_save( $lang, $settings ) {
	$all = get_option( CVFS_OPTION, array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}
	$all[ $lang ] = $settings;
	update_option( CVFS_OPTION, $all, false );
}

/** Återställ ett språk till standardvärdena. */
function cvfs_reset( $lang ) {
	$all = get_option( CVFS_OPTION, array() );
	if ( is_array( $all ) && isset( $all[ $lang ] ) ) {
		unset( $all[ $lang ] );
		update_option( CVFS_OPTION, $all, false );
	}
}

/* -------------------------------------------------------------------------
 * Tvättning av formulärdata
 * ---------------------------------------------------------------------- */

function cvfs_clean_text( $v ) {
	return sanitize_text_field( wp_unslash( (string) $v ) );
}

function cvfs_clean_url( $v ) {
	return esc_url_raw( trim( wp_unslash( (string) $v ) ) );
}

function cvfs_clean_trips( $rows ) {
	$out  = array();
	$seen = array();
	if ( ! is_array( $rows ) ) {
		return $out;
	}
	foreach ( $rows as $r ) {
		$id = isset( $r['id'] ) ? absint( $r['id'] ) : 0;
		if ( ! $id || isset( $seen[ $id ] ) ) {
			continue;
		}
		$seen[ $id ] = true;
		$out[] = array(
			'id'    => $id,
			'title' => cvfs_clean_text( $r['title'] ?? '' ),
			'meta'  => cvfs_clean_text( $r['meta'] ?? '' ),
			'price' => cvfs_clean_text( $r['price'] ?? '' ),
			'image' => cvfs_clean_url( $r['image'] ?? '' ),
		);
	}
	return $out;
}

/**
 * Formulär (en språkflik) → inställningar. Okända fält ignoreras.
 *
 * @param array $in   $_POST['cvfs'].
 * @param string $lang Språkkod.
 */
function cvfs_sanitize( $in, $lang ) {
	$d   = cvfs_defaults( $lang );
	$in  = is_array( $in ) ? $in : array();
	$out = array();

	$out['show_guided'] = empty( $in['show_guided'] ) ? 0 : 1;

	$out['follow'] = array();
	foreach ( cvfs_follow_sections() as $k ) {
		$out['follow'][ $k ] = empty( $in['follow'][ $k ] ) ? 0 : 1;
	}

	$h           = $in['hero'] ?? array();
	$out['hero'] = array(
		'title'       => cvfs_clean_text( $h['title'] ?? '' ),
		'lead'        => cvfs_clean_text( $h['lead'] ?? '' ),
		'images'      => array_values( array_filter( array_map( 'cvfs_clean_url', (array) ( $h['images'] ?? array() ) ) ) ),
		'images_mobile' => array_values( array_filter( array_map( 'cvfs_clean_url', (array) ( $h['images_mobile'] ?? array() ) ) ) ),
		'placeholder' => cvfs_clean_text( $h['placeholder'] ?? '' ),
		'button'      => cvfs_clean_text( $h['button'] ?? '' ),
	);

	$out['paths'] = array();
	foreach ( array_keys( $d['paths'] ) as $k ) {
		$p                  = $in['paths'][ $k ] ?? array();
		$out['paths'][ $k ] = array(
			'title'     => cvfs_clean_text( $p['title'] ?? '' ),
			'text'      => cvfs_clean_text( $p['text'] ?? '' ),
			'link_text' => cvfs_clean_text( $p['link_text'] ?? '' ),
			'url'       => cvfs_clean_url( $p['url'] ?? '' ),
		);
	}

	$out['facts'] = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$f              = $in['facts'][ $i ] ?? array();
		$out['facts'][] = array(
			'title' => cvfs_clean_text( $f['title'] ?? '' ),
			'sub'   => cvfs_clean_text( $f['sub'] ?? '' ),
		);
	}

	foreach ( array( 'guided', 'popular', 'news' ) as $sec ) {
		$s           = $in[ $sec ] ?? array();
		$out[ $sec ] = array(
			'title'     => cvfs_clean_text( $s['title'] ?? '' ),
			'link_text' => cvfs_clean_text( $s['link_text'] ?? '' ),
			'url'       => cvfs_clean_url( $s['url'] ?? '' ),
			'auto_price' => empty( $s['auto_price'] ) ? 0 : 1,
			'scroll_desktop' => empty( $s['scroll_desktop'] ) ? 0 : 1,
			'scroll_mobile'  => empty( $s['scroll_mobile'] ) ? 0 : 1,
			'trips'      => cvfs_clean_trips( $s['trips'] ?? array() ),
		);
	}
	$out['guided']['mode'] = ( isset( $in['guided']['mode'] ) && 'manual' === $in['guided']['mode'] ) ? 'manual' : 'events';
	$n                    = $in['news'] ?? array();
	$out['news']['mode']  = ( isset( $n['mode'] ) && in_array( $n['mode'], array( 'auto', 'random' ), true ) ) ? $n['mode'] : 'manual';
	$out['news']['tag']   = sanitize_title( wp_unslash( (string) ( $n['tag'] ?? '' ) ) );
	$out['news']['tag_auto'] = empty( $n['tag_auto'] ) ? 0 : 1;
	$out['news']['count'] = max( 1, min( 12, absint( $n['count'] ?? 4 ) ) );
	$out['news']['label'] = cvfs_clean_text( $n['label'] ?? '' );

	$b           = $in['blog'] ?? array();
	$out['blog'] = array(
		'show'        => empty( $b['show'] ) ? 0 : 1,
		'title'       => cvfs_clean_text( $b['title'] ?? '' ),
		'link_text'   => cvfs_clean_text( $b['link_text'] ?? '' ),
		'url'         => cvfs_clean_url( $b['url'] ?? '' ),
		'category'    => sanitize_title( wp_unslash( (string) ( $b['category'] ?? '' ) ) ),
		'count'       => max( 1, min( 12, absint( $b['count'] ?? 3 ) ) ),
		'scroll_desktop' => empty( $b['scroll_desktop'] ) ? 0 : 1,
		'scroll_mobile'  => empty( $b['scroll_mobile'] ) ? 0 : 1,
		'rail_count'     => max( 3, min( 24, absint( $b['rail_count'] ?? 12 ) ) ),
		'words'       => max( 5, min( 100, absint( $b['words'] ?? 17 ) ) ),
		'date_format' => cvfs_clean_text( $b['date_format'] ?? 'j F Y' ),
	);

	$bd          = $in['band'] ?? array();
	$out['band'] = array(
		'show_map' => empty( $bd['show_map'] ) ? 0 : 1,
		'title'   => cvfs_clean_text( $bd['title'] ?? '' ),
		'text'    => cvfs_clean_text( $bd['text'] ?? '' ),
		'buttons' => array(),
		'images'  => array(),
	);
	for ( $i = 0; $i < 3; $i++ ) {
		$x                        = $bd['buttons'][ $i ] ?? array();
		$out['band']['buttons'][] = array(
			'text'    => cvfs_clean_text( $x['text'] ?? '' ),
			'url'     => cvfs_clean_url( $x['url'] ?? '' ),
			'new_tab' => empty( $x['new_tab'] ) ? 0 : 1,
		);
	}
	for ( $i = 0; $i < 4; $i++ ) {
		$x                       = $bd['images'][ $i ] ?? array();
		$out['band']['images'][] = array(
			'url' => cvfs_clean_url( $x['url'] ?? '' ),
			'alt' => cvfs_clean_text( $x['alt'] ?? '' ),
		);
	}

	$out['labels'] = array();
	foreach ( array_keys( $d['labels'] ) as $k ) {
		$out['labels'][ $k ] = cvfs_clean_text( $in['labels'][ $k ] ?? '' );
	}

	return $out;
}
