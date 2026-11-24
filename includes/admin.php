<?php
/**
 * Admin: Inställningar → Förstasida, en flik per aktivt WPML-språk.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	function () {
		$hook = add_options_page( 'Förstasida', 'Förstasida', 'manage_options', 'cvfs', 'cvfs_admin_page' );
		add_action( 'admin_print_scripts-' . $hook, 'cvfs_admin_assets' );
		add_action( 'admin_print_styles-' . $hook, 'cvfs_admin_assets' );
	}
);

add_filter(
	'plugin_action_links_' . plugin_basename( CVFS_FILE ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=cvfs' ) ) . '">Inställningar</a>' );
		return $links;
	}
);

function cvfs_admin_assets() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	wp_enqueue_media();
	wp_enqueue_style( 'cvfs-admin', CVFS_URL . 'assets/admin.css', array(), CVFS_VERSION );
	wp_enqueue_script( 'cvfs-admin', CVFS_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), CVFS_VERSION, true );
	wp_localize_script(
		'cvfs-admin',
		'cvfsAdmin',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'cvfs_search' ),
		)
	);
}

/** Aktuell flik (språkkod). */
function cvfs_admin_lang() {
	$langs = cvfs_active_langs();
	$lang  = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return isset( $langs[ $lang ] ) ? $lang : (string) array_key_first( $langs );
}

/* -------------------------------------------------------------------------
 * Spara / återställ
 * ---------------------------------------------------------------------- */

add_action(
	'admin_post_cvfs_save',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Behörighet saknas.' );
		}
		check_admin_referer( 'cvfs_save' );
		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		if ( ! $lang || ! isset( cvfs_active_langs()[ $lang ] ) ) {
			wp_die( 'Okänt språk.' );
		}
		if ( isset( $_POST['cvfs_reset'] ) ) {
			cvfs_reset( $lang );
			$msg = 'reset';
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- tvättas fält för fält i cvfs_sanitize().
			cvfs_save( $lang, cvfs_sanitize( $_POST['cvfs'] ?? array(), $lang ) );
			$msg = 'saved';
		}
		do_action( 'cvfs_saved', $lang );
		wp_safe_redirect( admin_url( 'options-general.php?page=cvfs&lang=' . $lang . '&cvfs_msg=' . $msg ) );
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Resväljarens sökning (AJAX)
 * ---------------------------------------------------------------------- */

add_action(
	'wp_ajax_cvfs_search_trips',
	function () {
		check_ajax_referer( 'cvfs_search', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : cvfs_default_lang();
		wp_send_json_success( cvfs_search_trips( $term, $lang, 30 ) );
	}
);

/* -------------------------------------------------------------------------
 * Fälthjälpare
 * ---------------------------------------------------------------------- */

function cvfs_f_text( $name, $value, $label, $args = array() ) {
	$wide = ! empty( $args['wide'] ) ? ' large-text' : ' regular-text';
	$type = $args['type'] ?? 'text';
	?>
	<tr>
		<th scope="row"><label><?php echo esc_html( $label ); ?></label></th>
		<td>
			<input type="<?php echo esc_attr( $type ); ?>" class="<?php echo esc_attr( trim( $wide ) ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<?php if ( ! empty( $args['desc'] ) ) : ?>
				<p class="description"><?php echo esc_html( $args['desc'] ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function cvfs_f_check( $name, $value, $label, $desc = '' ) {
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( (int) $value, 1 ); ?> /> <?php echo esc_html( $desc ); ?></label>
		</td>
	</tr>
	<?php
}

/** Rubrik + länktext + URL för en sektion. */
function cvfs_f_head( $key, $sec ) {
	cvfs_f_text( "cvfs[$key][title]", $sec['title'], 'Rubrik', array( 'wide' => true ) );
	cvfs_f_text( "cvfs[$key][link_text]", $sec['link_text'], 'Länktext' );
	cvfs_f_text( "cvfs[$key][url]", $sec['url'], 'Länk (URL)', array( 'wide' => true ) );
	if ( isset( $sec['auto_price'] ) ) {
		cvfs_f_check( "cvfs[$key][auto_price]", $sec['auto_price'], 'Pris från resesidan', 'Visa priset ur resesidans faktaruta när kortets prisfält är tomt' );
	}
}

/** En rad i resväljaren. $t = sparad rad, $info = sidans titel/bild/länk. */
function cvfs_trip_row( $base, $i, $t, $info ) {
	?>
	<li class="cvfs-trip" data-base="<?php echo esc_attr( $base ); ?>">
		<span class="cvfs-handle dashicons dashicons-menu" title="Dra för att sortera"></span>
		<span class="cvfs-thumb"><?php if ( $info['image'] ) : ?><img src="<?php echo esc_url( $info['image'] ); ?>" alt="" /><?php endif; ?></span>
		<div class="cvfs-trip-main">
			<div class="cvfs-trip-page">
				<input type="hidden" data-f="id" name="<?php echo esc_attr( $base . '[' . (int) $i . '][id]' ); ?>" value="<?php echo esc_attr( $t['id'] ); ?>" />
				<?php if ( $info['url'] ) : ?>
					<a href="<?php echo esc_url( $info['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $info['title'] ); ?></a>
				<?php else : ?>
					<em><?php echo esc_html( $info['title'] ); ?></em>
				<?php endif; ?>
				<span class="cvfs-trip-auto"><?php echo esc_html( $info['meta'] ); ?></span>
			</div>
			<div class="cvfs-trip-fields">
				<label>Kortrubrik <input type="text" data-f="title" name="<?php echo esc_attr( $base . '[' . (int) $i . '][title]' ); ?>" value="<?php echo esc_attr( $t['title'] ); ?>" placeholder="<?php echo esc_attr( $info['title'] ); ?>" /></label>
				<label>Metarad <input type="text" data-f="meta" name="<?php echo esc_attr( $base . '[' . (int) $i . '][meta]' ); ?>" value="<?php echo esc_attr( $t['meta'] ); ?>" placeholder="<?php echo esc_attr( $info['meta'] ); ?>" /></label>
				<label>Pris <input type="text" data-f="price" name="<?php echo esc_attr( $base . '[' . (int) $i . '][price]' ); ?>" class="cvfs-short" value="<?php echo esc_attr( $t['price'] ); ?>" placeholder="<?php echo esc_attr( '' !== ( $info['price'] ?? '' ) ? $info['price'] : 'Inget i faktarutan' ); ?>" /></label>
				<label>Egen bild <input type="text" data-f="image" name="<?php echo esc_attr( $base . '[' . (int) $i . '][image]' ); ?>" class="cvfs-img-url" value="<?php echo esc_attr( $t['image'] ); ?>" placeholder="Utvald bild" /></label>
				<button type="button" class="button cvfs-pick-one">Välj bild</button>
			</div>
		</div>
		<button type="button" class="button-link cvfs-remove" title="Ta bort">✕</button>
	</li>
	<?php
}

/** Sidinfo för admin (titel, länk, bild, automatisk metarad) i språket $lang. */
function cvfs_trip_info( $id, $lang, $labels ) {
	$tid     = cvfs_object_id( $id, $lang, true );
	$missing = ! cvfs_object_id( $id, $lang );
	$p       = $tid ? get_post( $tid ) : null;
	if ( ! $p ) {
		return array(
			'title' => 'Sidan ' . (int) $id . ' finns inte',
			'url'   => '',
			'image' => '',
			'meta'  => '',
		);
	}
	$title = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
	if ( 'publish' !== $p->post_status ) {
		$title .= ' (ej publicerad – visas inte)';
	} elseif ( $missing ) {
		$title .= ' (översättning saknas – visas inte)';
	}
	return array(
		'title' => $title,
		'url'   => get_permalink( $p ),
		'image' => cvfs_trip_image( $tid )[0],
		'meta'  => cvfs_auto_meta( $tid, $labels ),
		'price' => cvfs_trip_details( $tid )['price'],
	);
}

/** Resväljare för en sektion. */
function cvfs_f_trips( $key, $trips, $lang, $labels, $label = 'Resor' ) {
	$base = "cvfs[$key][trips]";
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $label ); ?></th>
		<td>
			<div class="cvfs-trips" data-base="<?php echo esc_attr( $base ); ?>" data-lang="<?php echo esc_attr( $lang ); ?>">
				<ul class="cvfs-trip-list">
					<?php
					foreach ( array_values( $trips ) as $i => $t ) {
						cvfs_trip_row( $base, $i, $t, cvfs_trip_info( $t['id'], $lang, $labels ) );
					}
					?>
				</ul>
				<div class="cvfs-trip-add">
					<input type="search" class="regular-text cvfs-trip-search" placeholder="Sök resa att lägga till (guidad/självguidad) …" />
					<ul class="cvfs-trip-results"></ul>
				</div>
				<p class="description">Dra för att sortera. Tomma fält hämtas från resesidan (förslaget syns i grått): rubrik och bild från sidan, metaraden från taggarna (typ, nivå) och faktarutans "Längd", priset från faktarutans "Pris". Priset skrivs ut som "<?php echo esc_html( $labels['from'] ); ?> …".</p>
			</div>
		</td>
	</tr>
	<?php
}

/* -------------------------------------------------------------------------
 * Sidan
 * ---------------------------------------------------------------------- */

function cvfs_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$langs = cvfs_active_langs();
	$lang  = cvfs_admin_lang();
	$s     = cvfs_get( $lang );
	$saved = get_option( CVFS_OPTION, array() );
	$msg   = isset( $_GET['cvfs_msg'] ) ? sanitize_key( wp_unslash( $_GET['cvfs_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$l     = $s['labels'];
	?>
	<div class="wrap cvfs-admin">
		<h1>Förstasida</h1>
		<p>Lägg shortcoden <code>[cv_forstasida]</code> på startsidan i varje språk (mall "100% Width"). Den visar inställningarna för sidans språk.</p>

		<?php if ( 'saved' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p>Sparat. Töm sidcachen (WP Fastest Cache) om ändringen inte syns.</p></div>
		<?php elseif ( 'reset' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p>Fliken är återställd till standardvärdena.</p></div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $langs as $code => $name ) : ?>
				<a class="nav-tab <?php echo $code === $lang ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'options-general.php?page=cvfs&lang=' . $code ) ); ?>">
					<?php echo esc_html( $name ); ?>
					<?php if ( empty( $saved[ $code ] ) ) : ?><span class="cvfs-badge" title="Inte sparad – standardvärden visas">standard</span><?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cvfs-form">
			<input type="hidden" name="action" value="cvfs_save" />
			<input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>" />
			<?php wp_nonce_field( 'cvfs_save' ); ?>

			<h2>Allmänt</h2>
			<table class="form-table" role="presentation">
				<?php cvfs_f_check( 'cvfs[show_guided]', $s['show_guided'], 'Visa guidade resor', 'Rutan "Guidade resor", sektionen med aktuella guidade resor. Standard: på för svenska och norska, av för övriga språk (guidningen sker på svenska).' ); ?>
			</table>

			<h2>1. Hero</h2>
			<table class="form-table" role="presentation">
				<?php
				cvfs_f_text( 'cvfs[hero][title]', $s['hero']['title'], 'Rubrik', array( 'wide' => true ) );
				cvfs_f_text( 'cvfs[hero][lead]', $s['hero']['lead'], 'Underrubrik', array( 'wide' => true ) );
				?>
				<tr>
					<th scope="row">Bakgrundsbilder</th>
					<td>
						<ul class="cvfs-imgs" data-name="cvfs[hero][images][]">
							<?php foreach ( $s['hero']['images'] as $url ) : ?>
								<li class="cvfs-img-item">
									<img src="<?php echo esc_url( $url ); ?>" alt="" />
									<input type="hidden" name="cvfs[hero][images][]" value="<?php echo esc_attr( $url ); ?>" />
									<button type="button" class="button-link cvfs-remove" title="Ta bort">✕</button>
								</li>
							<?php endforeach; ?>
						</ul>
						<button type="button" class="button cvfs-pick-many">Lägg till bilder</button>
						<p class="description">En av bilderna slumpas vid varje besök. Dra för att sortera. Välj gärna bilder med bara landskap eller med motivet vid sidan.</p>
					</td>
				</tr>
				<?php
				cvfs_f_text( 'cvfs[hero][placeholder]', $s['hero']['placeholder'], 'AI-sök: platshållare', array( 'wide' => true ) );
				cvfs_f_text( 'cvfs[hero][button]', $s['hero']['button'], 'AI-sök: knapptext', array( 'desc' => 'Exempelfrågorna ställs in i pluginen AI-sök.' ) );
				?>
			</table>

			<h2>2. Tre rutor</h2>
			<?php
			$paths = array(
				'guided' => 'Guidade resor (visas bara när guidade resor är på)',
				'bike'   => 'Cykla på egen hand',
				'hike'   => 'Vandra på egen hand',
			);
			foreach ( $paths as $k => $name ) :
				$p = $s['paths'][ $k ];
				?>
				<h3><?php echo esc_html( $name ); ?></h3>
				<table class="form-table" role="presentation">
					<?php
					cvfs_f_text( "cvfs[paths][$k][title]", $p['title'], 'Rubrik' );
					cvfs_f_text( "cvfs[paths][$k][text]", $p['text'], 'Text', array( 'wide' => true ) );
					cvfs_f_text( "cvfs[paths][$k][link_text]", $p['link_text'], 'Länktext' );
					cvfs_f_text( "cvfs[paths][$k][url]", $p['url'], 'Länk (URL)', array( 'wide' => true ) );
					?>
				</table>
			<?php endforeach; ?>

			<h2>3. Faktarad</h2>
			<p class="description">Fyra kolumner under rutorna. Döljs på mobil.</p>
			<table class="form-table" role="presentation">
				<?php foreach ( $s['facts'] as $i => $f ) : ?>
					<tr>
						<th scope="row">Kolumn <?php echo (int) $i + 1; ?></th>
						<td>
							<input type="text" class="regular-text" name="cvfs[facts][<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr( $f['title'] ); ?>" placeholder="Rubrik" />
							<input type="text" class="regular-text" name="cvfs[facts][<?php echo (int) $i; ?>][sub]" value="<?php echo esc_attr( $f['sub'] ); ?>" placeholder="Underrad" />
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>4. Aktuella guidade resor</h2>
			<p class="description">Visas bara när "Visa guidade resor" är på.</p>
			<table class="form-table" role="presentation">
				<?php
				cvfs_f_head( 'guided', $s['guided'] );
				cvfs_f_trips( 'guided', $s['guided']['trips'], $lang, $l );
				?>
			</table>

			<h2>5. Populära resor på egen hand</h2>
			<table class="form-table" role="presentation">
				<?php
				cvfs_f_head( 'popular', $s['popular'] );
				cvfs_f_trips( 'popular', $s['popular']['trips'], $lang, $l );
				?>
			</table>

			<h2>6. Nyheter</h2>
			<table class="form-table" role="presentation">
				<?php cvfs_f_head( 'news', $s['news'] ); ?>
				<tr>
					<th scope="row">Urval</th>
					<td>
						<label><input type="radio" name="cvfs[news][mode]" value="auto" <?php checked( $s['news']['mode'], 'auto' ); ?> /> Automatiskt: sidor med taggen nedan och taggen självguidad, nyast först</label><br />
						<label><input type="radio" name="cvfs[news][mode]" value="manual" <?php checked( $s['news']['mode'], 'manual' ); ?> /> Manuellt: resorna i listan nedan</label>
					</td>
				</tr>
				<?php
				cvfs_f_text( 'cvfs[news][tag]', $s['news']['tag'], 'Tagg (automatiskt)', array( 'desc' => 'T.ex. 2027.' ) );
				cvfs_f_text( 'cvfs[news][count]', $s['news']['count'], 'Antal (automatiskt)', array( 'type' => 'number' ) );
				cvfs_f_text( 'cvfs[news][label]', $s['news']['label'], 'Etikett i metaraden', array( 'desc' => 'Används när metaraden byggs automatiskt, t.ex. "Cykling · Nyhet 2027".' ) );
				cvfs_f_trips( 'news', $s['news']['trips'], $lang, $l, 'Resor (manuellt)' );
				?>
			</table>

			<h2>7. Nyhetsbrev (blogginlägg)</h2>
			<table class="form-table" role="presentation">
				<?php
				cvfs_f_check( 'cvfs[blog][show]', $s['blog']['show'], 'Visa', 'Visa de senaste inläggen' );
				cvfs_f_head( 'blog', $s['blog'] );
				cvfs_f_text( 'cvfs[blog][category]', $s['blog']['category'], 'Kategori (slug)' );
				cvfs_f_text( 'cvfs[blog][count]', $s['blog']['count'], 'Antal inlägg', array( 'type' => 'number' ) );
				cvfs_f_text( 'cvfs[blog][words]', $s['blog']['words'], 'Ord i utdraget', array( 'type' => 'number', 'desc' => 'Gäller inlägg utan eget utdrag. Ett eget utdrag visas alltid helt.' ) );
				cvfs_f_text( 'cvfs[blog][date_format]', $s['blog']['date_format'], 'Datumformat', array( 'desc' => 'PHP-format, t.ex. j F Y → 23 november 2026.' ) );
				?>
			</table>

			<h2>8. Band "Följ oss"</h2>
			<table class="form-table" role="presentation">
				<?php
				cvfs_f_text( 'cvfs[band][title]', $s['band']['title'], 'Rubrik', array( 'wide' => true ) );
				cvfs_f_text( 'cvfs[band][text]', $s['band']['text'], 'Text', array( 'wide' => true ) );
				foreach ( $s['band']['buttons'] as $i => $b ) :
					?>
					<tr>
						<th scope="row">Knapp <?php echo (int) $i + 1; ?></th>
						<td>
							<input type="text" name="cvfs[band][buttons][<?php echo (int) $i; ?>][text]" value="<?php echo esc_attr( $b['text'] ); ?>" placeholder="Text" />
							<input type="text" class="regular-text" name="cvfs[band][buttons][<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr( $b['url'] ); ?>" placeholder="URL" />
							<label><input type="checkbox" name="cvfs[band][buttons][<?php echo (int) $i; ?>][new_tab]" value="1" <?php checked( (int) $b['new_tab'], 1 ); ?> /> Ny flik</label>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php foreach ( $s['band']['images'] as $i => $im ) : ?>
					<tr>
						<th scope="row">Bild <?php echo (int) $i + 1; ?></th>
						<td class="cvfs-one">
							<span class="cvfs-thumb"><?php if ( $im['url'] ) : ?><img src="<?php echo esc_url( $im['url'] ); ?>" alt="" /><?php endif; ?></span>
							<input type="text" class="regular-text cvfs-img-url" name="cvfs[band][images][<?php echo (int) $i; ?>][url]" value="<?php echo esc_attr( $im['url'] ); ?>" placeholder="Bild-URL" />
							<button type="button" class="button cvfs-pick-one">Välj bild</button>
							<input type="text" name="cvfs[band][images][<?php echo (int) $i; ?>][alt]" value="<?php echo esc_attr( $im['alt'] ); ?>" placeholder="Alt-text" />
						</td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2>Ord i automatiska metarader</h2>
			<table class="form-table" role="presentation">
				<?php
				$names = array(
					'from'        => 'Före priset ("från")',
					'hike'        => 'Vandring',
					'bike'        => 'Cykling',
					'guided_hike' => 'Guidad vandring',
					'guided_bike' => 'Guidad cykling',
					'level'       => 'Nivå',
					'days'        => 'Dagar ("5 dagar")',
				);
				foreach ( $names as $k => $name ) {
					cvfs_f_text( "cvfs[labels][$k]", $l[ $k ], $name );
				}
				?>
			</table>

			<p class="submit">
				<?php submit_button( 'Spara ' . $langs[ $lang ], 'primary', 'submit', false ); ?>
				<button type="submit" name="cvfs_reset" value="1" class="button cvfs-reset">Återställ fliken till standardvärden</button>
			</p>
		</form>

	</div>
	<?php
}
