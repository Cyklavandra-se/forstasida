<?php
/**
 * "Översätt ändringarna till alla språk": standardspråkets (svenskans) texter översätts med
 * Claude till de andra språken.
 *
 * Varje översatt text sparas med en kod (md5) av den svenska källtexten i optionen
 * cvfs_translations[lang][fält] = { src, out }. Nästa gång översätts bara det som ändrats:
 *   - svenskan ändrad och texten orörd           → översätts (förvald)
 *   - texten ändrad för hand på språket ("justerad") → nytt förslag visas, inte förvalt
 *   - svenskan oförändrad                          → hoppas över
 * Inget sparas förrän Pär har sett förslagen och klickat "Spara valda översättningar".
 *
 * API-nyckeln är densamma som AI-sök använder (CVAI_API_KEY i wp-config.php eller
 * AI-söks inställning). Egen nyckel kan sättas med CVFS_API_KEY.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CVFS_TR_OPTION', 'cvfs_translations' );

function cvfs_api_key() {
	if ( defined( 'CVFS_API_KEY' ) && CVFS_API_KEY ) {
		return CVFS_API_KEY;
	}
	if ( function_exists( 'cvai_opt' ) ) {
		return (string) cvai_opt( 'api_key' );
	}
	return defined( 'CVAI_API_KEY' ) ? (string) CVAI_API_KEY : '';
}

function cvfs_tr_model() {
	return (string) apply_filters( 'cvfs_translate_model', 'claude-opus-5-5' );
}

/* -------------------------------------------------------------------------
 * Fält och sökvägar
 * ---------------------------------------------------------------------- */

/**
 * Översättningsbara texter för $lang, med svensk källtext.
 *
 * @return array path => array( label, src )
 */
function cvfs_text_fields( $lang ) {
	$m   = cvfs_get( cvfs_default_lang() );
	$s   = cvfs_get( $lang );
	$f   = (array) ( $s['follow'] ?? array() );
	$gm  = cvfs_guided_mode( $s );
	$gd  = 'hidden' !== $gm; // Rutan visas (fullt eller som referens).
	$gs  = 'full' === $gm;   // Sektionen med guidade resor visas.
	$out = array();
	$add = function ( $path, $label, $src ) use ( &$out ) {
		$out[ $path ] = array(
			'label' => $label,
			'src'   => (string) $src,
		);
	};

	$add( 'hero.title', 'Hero: rubrik', $m['hero']['title'] );
	$add( 'hero.lead', 'Hero: underrubrik', $m['hero']['lead'] );
	$add( 'hero.placeholder', 'AI-sök: platshållare', $m['hero']['placeholder'] );
	$add( 'hero.button', 'AI-sök: knapptext', $m['hero']['button'] );

	$pnames = array(
		'guided' => 'Ruta Guidade resor',
		'bike'   => 'Ruta Cykla',
		'hike'   => 'Ruta Vandra',
	);
	foreach ( $pnames as $k => $name ) {
		if ( 'guided' === $k && ! $gd ) {
			continue;
		}
		$add( "paths.$k.title", "$name: rubrik", $m['paths'][ $k ]['title'] );
		$add( "paths.$k.text", "$name: text", $m['paths'][ $k ]['text'] );
		$add( "paths.$k.link_text", "$name: länktext", $m['paths'][ $k ]['link_text'] );
	}

	foreach ( $m['facts'] as $i => $fact ) {
		$n = $i + 1;
		$add( "facts.$i.title", "Faktarad $n: rubrik", $fact['title'] );
		$add( "facts.$i.sub", "Faktarad $n: underrad", $fact['sub'] );
	}

	$snames = array(
		'guided'  => 'Aktuella guidade resor',
		'popular' => 'Populära resor',
		'news'    => 'Nyheter',
	);
	foreach ( $snames as $sec => $name ) {
		if ( 'guided' === $sec && ! $gs ) {
			continue;
		}
		$add( "$sec.title", "$name: rubrik", $m[ $sec ]['title'] );
		$add( "$sec.link_text", "$name: länktext", $m[ $sec ]['link_text'] );
		if ( 'news' === $sec ) {
			$add( 'news.label', 'Nyheter: etikett i metaraden', $m['news']['label'] );
		}
		// Kortens rubrik och metarad: resor som språket följer eller själv har valt.
		$own = wp_list_pluck( (array) $s[ $sec ]['trips'], 'id' );
		foreach ( (array) $m[ $sec ]['trips'] as $t ) {
			if ( empty( $f[ $sec ] ) && ! in_array( (int) $t['id'], array_map( 'intval', $own ), true ) ) {
				continue;
			}
			$tn = cvfs_trip_name( $t );
			$add( "trip.$sec.{$t['id']}.title", "$name – $tn: kortrubrik", $t['title'] );
			$add( "trip.$sec.{$t['id']}.meta", "$name – $tn: metarad", $t['meta'] );
		}
	}

	$add( 'blog.title', 'Blogg: rubrik', $m['blog']['title'] );
	$add( 'blog.link_text', 'Blogg: länktext', $m['blog']['link_text'] );

	$add( 'band.title', 'Band: rubrik', $m['band']['title'] );
	$add( 'band.text', 'Band: text', $m['band']['text'] );
	foreach ( $m['band']['buttons'] as $i => $b ) {
		$add( "band.buttons.$i.text", 'Band: knapp ' . ( $i + 1 ), $b['text'] );
	}
	foreach ( $m['band']['images'] as $i => $im ) {
		$add( "band.images.$i.alt", 'Band: bild ' . ( $i + 1 ) . ', alt-text', $im['alt'] );
	}

	// Orden i automatiska metarader finns inbyggda för några språk; övriga översätts.
	if ( ! in_array( cvfs_norm_lang( $lang ), array_keys( cvfs_all_labels() ), true ) ) {
		foreach ( $m['labels'] as $k => $v ) {
			$add( "labels.$k", "Ord i metaraden: $v", $v );
		}
	}

	return $out;
}

/** Kort namn på en resa för etiketter (kortrubrik eller sidans titel). */
function cvfs_trip_name( $t ) {
	if ( '' !== (string) ( $t['title'] ?? '' ) ) {
		return $t['title'];
	}
	return html_entity_decode( get_the_title( (int) $t['id'] ), ENT_QUOTES, 'UTF-8' );
}

/** Värdet på en sökväg i ett språks inställningar ('' om det saknas). */
function cvfs_path_get( $s, $path ) {
	$p = explode( '.', $path );
	if ( 'trip' === $p[0] ) {
		foreach ( (array) ( $s[ $p[1] ]['trips'] ?? array() ) as $t ) {
			if ( (int) $t['id'] === (int) $p[2] ) {
				return (string) ( $t[ $p[3] ] ?? '' );
			}
		}
		return '';
	}
	$v = $s;
	foreach ( $p as $k ) {
		if ( ! is_array( $v ) || ! array_key_exists( $k, $v ) ) {
			return '';
		}
		$v = $v[ $k ];
	}
	return is_array( $v ) ? '' : (string) $v;
}

/** Sätt en sökväg. Resor som saknas i språkets lista läggs till sist. */
function cvfs_path_set( &$s, $path, $value ) {
	$p = explode( '.', $path );
	if ( 'trip' === $p[0] ) {
		$sec = $p[1];
		foreach ( $s[ $sec ]['trips'] as $i => $t ) {
			if ( (int) $t['id'] === (int) $p[2] ) {
				$s[ $sec ]['trips'][ $i ][ $p[3] ] = $value;
				return;
			}
		}
		$t            = cvfs_trip( (int) $p[2] );
		$t[ $p[3] ]   = $value;
		$s[ $sec ]['trips'][] = $t;
		return;
	}
	$ref = &$s;
	$last = array_pop( $p );
	foreach ( $p as $k ) {
		if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
			$ref[ $k ] = array();
		}
		$ref = &$ref[ $k ];
	}
	$ref[ $last ] = $value;
}

function cvfs_tr_meta( $lang ) {
	$all = get_option( CVFS_TR_OPTION, array() );
	return is_array( $all ) && isset( $all[ $lang ] ) && is_array( $all[ $lang ] ) ? $all[ $lang ] : array();
}

function cvfs_tr_hash( $text ) {
	return md5( (string) $text );
}

/* -------------------------------------------------------------------------
 * Plan: vad behöver översättas?
 * ---------------------------------------------------------------------- */

/**
 * @return array[] { path, label, src, cur, status, checked }
 *   status: new | changed | adjusted | own | clear
 */
function cvfs_tr_plan( $lang ) {
	$s    = cvfs_get( $lang );
	$d    = cvfs_defaults( $lang );
	$meta = cvfs_tr_meta( $lang );
	$rows = array();

	foreach ( cvfs_text_fields( $lang ) as $path => $f ) {
		$src  = $f['src'];
		$cur  = cvfs_path_get( $s, $path );
		$tr   = $meta[ $path ] ?? null;
		$hash = cvfs_tr_hash( $src );

		if ( $tr ) {
			$touched = $cur !== (string) $tr['out'];
			if ( $hash === $tr['src'] ) {
				continue; // Svenskan oförändrad sedan senaste översättningen.
			}
			if ( '' === $src ) {
				$status = $touched ? 'adjusted' : 'clear';
			} else {
				$status = $touched ? 'adjusted' : 'changed';
			}
		} else {
			$default = cvfs_path_get( $d, $path );
			if ( '' === $src ) {
				if ( '' !== $cur ) {
					continue; // Språkets egen text där svenskan är tom (t.ex. manuell metarad) – behåll.
				}
				continue;
			}
			$status = ( '' === $cur || $cur === $default || $cur === $src ) ? 'new' : 'own';
		}

		$rows[] = array(
			'path'    => $path,
			'label'   => $f['label'],
			'src'     => $src,
			'cur'     => $cur,
			'status'  => $status,
			'checked' => in_array( $status, array( 'new', 'changed', 'clear' ), true ),
		);
	}
	return $rows;
}

/* -------------------------------------------------------------------------
 * Claude
 * ---------------------------------------------------------------------- */

/** Språkets namn för prompten, t.ex. "norska (bokmål)" eller "Suomi (fi_FI)". */
function cvfs_tr_lang_desc( $lang ) {
	$known = array(
		'nb' => 'norsk bokmål',
		'da' => 'danska',
		'en' => 'engelska (brittisk)',
		'de' => 'tyska',
		'fi' => 'finska',
		'nl' => 'nederländska',
		'fr' => 'franska',
		'es' => 'spanska',
		'it' => 'italienska',
	);
	$n = cvfs_norm_lang( $lang );
	if ( isset( $known[ $n ] ) ) {
		return $known[ $n ];
	}
	$langs = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
	$l     = is_array( $langs ) ? ( $langs[ $lang ] ?? array() ) : array();
	return trim( ( $l['native_name'] ?? $lang ) . ' (' . ( $l['default_locale'] ?? $lang ) . ')' );
}

/**
 * Översätt texter från svenska till $lang.
 *
 * @param array $items path => svensk text.
 * @return array|WP_Error path => översättning.
 */
function cvfs_claude_translate( $lang, $items ) {
	$key = cvfs_api_key();
	if ( ! $key ) {
		return new WP_Error( 'cvfs_no_key', 'API-nyckel saknas. Lägg CVAI_API_KEY (som AI-sök använder) eller CVFS_API_KEY i wp-config.php.' );
	}
	if ( ! $items ) {
		return array();
	}

	$target = cvfs_tr_lang_desc( $lang );
	$system = "Du översätter texter på förstasidan för Cykla & Vandra, en svensk researrangör av cykel- och vandringsresor i Europa – på egen hand (självguidade, med bagagetransport och app) och guidade resor med svensk guide.\n\n"
		. "Översätt varje text från svenska till $target. Regler:\n"
		. "- Skriv naturlig, säljande text för en förstasida på målspråket – inte ordagrant. Behåll tonen: vänlig, kort och konkret.\n"
		. "- Ungefär samma längd som originalet. Kortrubriker för resor ska vara lika korta som de svenska.\n"
		. "- Behåll tecken och format: pilar (→), mellanpunkter (·), tankstreck (–), siffror, priser och valutor.\n"
		. "- Ändra inte varumärket \"Cykla & Vandra\" eller namn som Instagram och Facebook. Använd målspråkets vedertagna form av ortnamn.\n"
		. "- \"Nivå 2\" i en metarad är resans svårighetsgrad; \"Nyhet 2027\" betyder ny resa för säsongen 2027.\n"
		. "- \"Svensk guide\" betyder att guiden är svensk – översätt så, även på andra språk.\n"
		. "- Platshållare inom klammerparenteser, t.ex. {år}, ska stå kvar exakt som de är.\n"
		. "- Returnera exakt samma id:n som du fick, en översättning per id.";

	$list = array();
	foreach ( $items as $id => $text ) {
		$list[] = array(
			'id'   => $id,
			'text' => $text,
		);
	}

	$body = array(
		'model'         => cvfs_tr_model(),
		'max_tokens'    => 16000,
		'system'        => $system,
		'messages'      => array(
			array(
				'role'    => 'user',
				'content' => wp_json_encode( array( 'texter' => $list ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ),
			),
		),
		'output_config' => array(
			'effort' => 'low',
			'format' => array(
				'type'   => 'json_schema',
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'oversattningar' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'   => array( 'type' => 'string' ),
									'text' => array( 'type' => 'string' ),
								),
								'required'             => array( 'id', 'text' ),
								'additionalProperties' => false,
							),
						),
					),
					'required'             => array( 'oversattningar' ),
					'additionalProperties' => false,
				),
			),
		),
		// Om säkerhetsfiltret skulle neka: låt API:et försöka med en reservmodell.
		'fallbacks'     => 'default',
	);

	$resp = cvfs_claude_post( $key, $body, array( 'server-side-fallback-2026-07-01' ) );
	if ( ! is_wp_error( $resp ) && 400 === wp_remote_retrieve_response_code( $resp ) ) {
		// Reserv: utan fallbacks, om kontot eller modellen inte har betan.
		unset( $body['fallbacks'] );
		$resp = cvfs_claude_post( $key, $body, array() );
	}
	if ( is_wp_error( $resp ) ) {
		return $resp;
	}
	$code = wp_remote_retrieve_response_code( $resp );
	$json = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( 200 !== $code ) {
		$msg = $json['error']['message'] ?? wp_remote_retrieve_body( $resp );
		error_log( '[cvfs] API-fel ' . $code . ': ' . mb_substr( (string) $msg, 0, 500 ) );
		return new WP_Error( 'cvfs_api', 'Claude svarade med fel ' . $code . ': ' . mb_substr( (string) $msg, 0, 200 ) );
	}
	if ( 'refusal' === ( $json['stop_reason'] ?? '' ) ) {
		return new WP_Error( 'cvfs_refusal', 'Claude avböjde att översätta texterna.' );
	}
	if ( 'max_tokens' === ( $json['stop_reason'] ?? '' ) ) {
		return new WP_Error( 'cvfs_long', 'Svaret blev för långt. Försök igen.' );
	}

	$text = '';
	foreach ( (array) ( $json['content'] ?? array() ) as $block ) {
		$type = $block['type'] ?? '';
		if ( 'fallback' === $type ) {
			$text = ''; // Reservmodellen tog över – använd bara dess svar.
		} elseif ( 'text' === $type ) {
			$text .= $block['text'];
		}
	}
	$data = json_decode( $text, true );
	if ( ! is_array( $data ) || ! isset( $data['oversattningar'] ) ) {
		error_log( '[cvfs] Kunde inte tolka svaret: ' . mb_substr( $text, 0, 500 ) );
		return new WP_Error( 'cvfs_parse', 'Kunde inte tolka svaret från Claude.' );
	}
	$out = array();
	foreach ( (array) $data['oversattningar'] as $row ) {
		$id = (string) ( $row['id'] ?? '' );
		if ( isset( $items[ $id ] ) ) {
			$out[ $id ] = sanitize_text_field( (string) ( $row['text'] ?? '' ) );
		}
	}
	return $out;
}

function cvfs_claude_post( $key, $body, $betas ) {
	$headers = array(
		'x-api-key'         => $key,
		'anthropic-version' => '2023-06-01',
		'content-type'      => 'application/json',
	);
	if ( $betas ) {
		$headers['anthropic-beta'] = implode( ',', $betas );
	}
	return wp_remote_post(
		'https://api.anthropic.com/v1/messages',
		array(
			'timeout' => 120,
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
		)
	);
}

/* -------------------------------------------------------------------------
 * AJAX: förslag för ett språk
 * ---------------------------------------------------------------------- */

add_action(
	'wp_ajax_cvfs_translate_lang',
	function () {
		check_ajax_referer( 'cvfs_translate', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Behörighet saknas.' ), 403 );
		}
		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		if ( ! $lang || cvfs_is_master( $lang ) || ! isset( cvfs_active_langs()[ $lang ] ) ) {
			wp_send_json_error( array( 'message' => 'Okänt språk.' ) );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}

		$rows  = cvfs_tr_plan( $lang );
		$items = array();
		foreach ( $rows as $r ) {
			if ( 'clear' !== $r['status'] ) {
				$items[ $r['path'] ] = $r['src'];
			}
		}
		$tr = cvfs_claude_translate( $lang, $items );
		if ( is_wp_error( $tr ) ) {
			wp_send_json_error( array( 'message' => $tr->get_error_message() ) );
		}
		foreach ( $rows as $i => $r ) {
			$rows[ $i ]['new'] = 'clear' === $r['status'] ? '' : ( $tr[ $r['path'] ] ?? '' );
			if ( 'clear' !== $r['status'] && '' === $rows[ $i ]['new'] ) {
				$rows[ $i ]['checked'] = false; // Saknas i svaret – välj inte.
			}
		}
		wp_send_json_success( array( 'rows' => $rows ) );
	}
);

/* -------------------------------------------------------------------------
 * Spara valda översättningar
 * ---------------------------------------------------------------------- */

add_action(
	'admin_post_cvfs_apply_translations',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Behörighet saknas.' );
		}
		check_admin_referer( 'cvfs_apply_translations' );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- tvättas per fält nedan.
		$in    = isset( $_POST['tr'] ) && is_array( $_POST['tr'] ) ? wp_unslash( $_POST['tr'] ) : array();
		$langs = cvfs_active_langs();
		$all   = get_option( CVFS_OPTION, array() );
		$all   = is_array( $all ) ? $all : array();
		$meta  = get_option( CVFS_TR_OPTION, array() );
		$meta  = is_array( $meta ) ? $meta : array();
		$count = 0;

		foreach ( $in as $lang => $rows ) {
			$lang = sanitize_key( $lang );
			if ( ! isset( $langs[ $lang ] ) || cvfs_is_master( $lang ) || ! is_array( $rows ) ) {
				continue;
			}
			$fields = cvfs_text_fields( $lang );
			$s      = cvfs_get( $lang );
			foreach ( $rows as $row ) {
				$path = (string) ( $row['path'] ?? '' );
				if ( empty( $row['on'] ) || ! isset( $fields[ $path ] ) ) {
					continue;
				}
				$text = sanitize_text_field( (string) ( $row['text'] ?? '' ) );
				cvfs_path_set( $s, $path, $text );
				$meta[ $lang ][ $path ] = array(
					'src' => cvfs_tr_hash( $fields[ $path ]['src'] ),
					'out' => $text,
				);
				$count++;
			}
			$all[ $lang ] = $s;
		}

		update_option( CVFS_OPTION, $all, false );
		update_option( CVFS_TR_OPTION, $meta, false );
		do_action( 'cvfs_saved', 'all' );
		wp_safe_redirect( admin_url( 'admin.php?page=cvfs&view=oversatt&cvfs_msg=tr_saved&n=' . $count ) );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Sidan med förslag (admin.php?page=cvfs&view=oversatt)
 * ---------------------------------------------------------------------- */

function cvfs_translate_page() {
	$langs  = cvfs_active_langs();
	$master = cvfs_default_lang();
	$msg    = isset( $_GET['cvfs_msg'] ) ? sanitize_key( wp_unslash( $_GET['cvfs_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$n      = isset( $_GET['n'] ) ? absint( $_GET['n'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap cvfs-admin cvfs-tr">
		<h1>Översätt ändringarna till alla språk</h1>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=cvfs&lang=' . $master ) ); ?>">← Tillbaka till inställningarna</a></p>

		<?php if ( 'tr_saved' === $msg ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $n . ' översättningar sparade. Töm sidcachen (WP Fastest Cache) om ändringen inte syns.' ); ?></p></div>
			<?php
			echo '</div>';
			return;
		endif;
		?>

		<?php if ( ! cvfs_api_key() ) : ?>
			<div class="notice notice-error"><p>API-nyckel saknas. Översättningen använder samma nyckel som AI-sök (<code>CVAI_API_KEY</code> i wp-config.php), eller <code>CVFS_API_KEY</code>.</p></div>
			<?php
			echo '</div>';
			return;
		endif;
		?>

		<p>Svenskans texter översätts med Claude. Bara det som ändrats sedan förra gången tas med. Kontrollera förslagen, ändra vid behov och spara de som är ikryssade. Texter du har justerat för hand på ett språk är <strong>inte</strong> ikryssade.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="cvfs-tr-form">
			<input type="hidden" name="action" value="cvfs_apply_translations" />
			<?php wp_nonce_field( 'cvfs_apply_translations' ); ?>
			<?php
			foreach ( $langs as $code => $name ) :
				if ( $code === $master ) {
					continue;
				}
				?>
				<div class="cvfs-tr-lang" data-lang="<?php echo esc_attr( $code ); ?>">
					<h2><?php echo esc_html( $name ); ?> <span class="cvfs-tr-status">Väntar …</span></h2>
					<div class="cvfs-tr-body"></div>
				</div>
			<?php endforeach; ?>
			<p class="submit">
				<button type="submit" class="button button-primary" id="cvfs-tr-save" disabled>Spara valda översättningar</button>
			</p>
		</form>
	</div>
	<?php
}
