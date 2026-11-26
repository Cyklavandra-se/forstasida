<?php
/**
 * Shortcoden [cv_forstasida]: hela förstasidan för aktuellt WPML-språk.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'cv_forstasida', 'cvfs_shortcode' );

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_register_style( 'cvfs', CVFS_URL . 'assets/forstasida.css', array(), CVFS_VERSION );
		wp_register_script( 'cvfs', CVFS_URL . 'assets/forstasida.js', array(), CVFS_VERSION, true );
		// Ladda CSS:en i <head> på sidor som har shortcoden, så att sidan inte hoppar.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && has_shortcode( (string) $post->post_content, 'cv_forstasida' ) ) {
				wp_enqueue_style( 'cvfs' );
			}
		}
	}
);

/**
 * @param array $atts Stöder lang="sv" för att visa ett visst språk (t.ex. vid test).
 */
function cvfs_shortcode( $atts = array() ) {
	$atts = shortcode_atts( array( 'lang' => '' ), $atts, 'cv_forstasida' );
	$lang = $atts['lang'] ? sanitize_key( $atts['lang'] ) : cvfs_current_lang();

	wp_enqueue_style( 'cvfs' );
	wp_enqueue_script( 'cvfs' );

	return cvfs_render( $lang );
}

/** Data till mallen: inställningar + färdiga kort och blogginlägg. */
function cvfs_view_data( $lang ) {
	$s = cvfs_effective( $lang );

	$news_trips = 'auto' === $s['news']['mode'] ? cvfs_auto_news( $s, $lang ) : $s['news']['trips'];

	return array(
		'lang'    => $lang,
		's'       => $s,
		'guided'  => ! empty( $s['show_guided'] ),
		'search'  => cvfs_search_box( $s ),
		'cards'   => array(
			'guided'  => ! empty( $s['show_guided'] ) ? cvfs_cards( $s['guided']['trips'], $s, $lang, '', ! empty( $s['guided']['auto_price'] ) ) : array(),
			'popular' => cvfs_cards( $s['popular']['trips'], $s, $lang, '', ! empty( $s['popular']['auto_price'] ) ),
			'news'    => cvfs_cards( $news_trips, $s, $lang, $s['news']['label'], ! empty( $s['news']['auto_price'] ) ),
		),
		'posts'   => ! empty( $s['blog']['show'] ) ? cvfs_blog_posts( $s, $lang ) : array(),
		'map'     => ! empty( $s['band']['show_map'] ) && shortcode_exists( 'resekarta' ) ? do_shortcode( '[resekarta]' ) : '',
	);
}

/** AI-sökningen (pluginen cyklavandra-ai-sok). Tom om den inte är aktiv. */
function cvfs_search_box( $s ) {
	if ( ! shortcode_exists( 'cv_ai_sok' ) ) {
		return '';
	}
	return do_shortcode(
		sprintf(
			'[cv_ai_sok platshallare="%s" knapp="%s"]',
			str_replace( array( '"', '[', ']' ), '', $s['hero']['placeholder'] ),
			str_replace( array( '"', '[', ']' ), '', $s['hero']['button'] )
		)
	);
}

/**
 * Senaste inläggen i bloggkategorin (i aktuellt språk).
 *
 * @return array[] { url, title, image, date, excerpt }
 */
function cvfs_blog_posts( $s, $lang ) {
	$b = $s['blog'];
	return cvfs_in_lang(
		$lang,
		function () use ( $b ) {
			$q   = new WP_Query(
				array(
					'post_type'           => 'post',
					'post_status'         => 'publish',
					'category_name'       => $b['category'],
					'posts_per_page'      => (int) $b['count'],
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'suppress_filters'    => false,
				)
			);
			$out = array();
			foreach ( $q->posts as $p ) {
				$thumb = get_post_thumbnail_id( $p );
				$img   = $thumb ? wp_get_attachment_image_url( $thumb, 'large' ) : '';
				if ( has_excerpt( $p ) ) {
					// Eget utdrag visas helt, som i Avadas blogglista.
					$excerpt = wp_strip_all_tags( $p->post_excerpt );
				} else {
					$excerpt = wp_trim_words( cvfs_plain_text( $p->post_content ), (int) $b['words'], ' [...]' );
				}
				$out[] = array(
					'url'     => get_permalink( $p ),
					'title'   => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
					'image'   => $img ? $img : '',
					'date'    => get_the_date( $b['date_format'], $p ),
					'excerpt' => trim( html_entity_decode( $excerpt, ENT_QUOTES, 'UTF-8' ) ),
				);
			}
			return $out;
		}
	);
}

/**
 * Ren text ur ett inlägg byggt med Avada. strip_shortcodes() tar bort hela Avadas
 * behållare (och därmed all text), så här tas bara kortkodstaggarna bort och texten
 * inuti behålls. Bild-/kodkortkoder och adresser rensas bort.
 */
function cvfs_plain_text( $content ) {
	$t = (string) $content;
	// Kortkoder vars innehåll inte är text (bilder, kod, kartor, video …).
	$t = preg_replace( '#\[(fusion_imageframe|fusion_code|fusion_gallery|fusion_image|fusion_video|fusion_youtube|fusion_vimeo|fusion_map|fusion_google_map|fusion_slider|fusion_slide|caption)\b[^\]]*\].*?\[/\1\]#is', ' ', $t );
	$t = preg_replace( '#\[/?[a-zA-Z_][^\]]*\]#', ' ', $t ); // Övriga kortkodstaggar, innehållet behålls.
	$t = wp_strip_all_tags( $t );
	$t = html_entity_decode( $t, ENT_QUOTES, 'UTF-8' );
	$t = preg_replace( '#https?://\S+#', ' ', $t );
	$t = preg_replace( '#\S{40,}#', ' ', $t ); // Kodsträngar o.d.
	return trim( preg_replace( '/\s+/u', ' ', $t ) );
}

/**
 * Rendera mallen. Radbrytningar mellan taggar tas bort, så att wpautop i Avadas
 * textmodul inte kan lägga in <p>/<br> i utdata.
 */
function cvfs_render( $lang ) {
	$data = cvfs_view_data( $lang );
	$tpl  = apply_filters( 'cvfs_template', CVFS_DIR . 'templates/forstasida.php', $lang );

	ob_start();
	( function () use ( $tpl, $data ) {
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract
		include $tpl;
	} )();
	$html = ob_get_clean();

	$html = preg_replace( '/>\s+</', '><', trim( $html ) );
	return preg_replace( '/\s*\n\s*/', ' ', $html );
}
