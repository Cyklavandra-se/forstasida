<?php
/**
 * Uppdateringar från GitHub-releaser.
 *
 * Pluginhuvudet har "Update URI: https://github.com/…", så WordPress frågar inte
 * wordpress.org utan anropar filtret update_plugins_github.com (WP 5.8+). Vi hämtar
 * senaste releasen via GitHubs API och erbjuder den bifogade cyklavandra-forstasida.zip.
 * Releaser utan zip (t.ex. medan GitHub Actions fortfarande bygger) hoppas över.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CVFS_GITHUB_REPO', 'Cyklavandra-se/forstasida' );
define( 'CVFS_UPDATE_ASSET', 'cyklavandra-forstasida.zip' );
define( 'CVFS_UPDATE_SLUG', 'cyklavandra-forstasida' );

/**
 * Senaste releasen som har en färdig zip, eller null.
 * Frågar först GitHubs API (ger releasetexten). Om det misslyckas – t.ex. för att
 * webbhotellets IP-adress slagit i API:ets gräns på 60 anrop/timme – läses
 * versionen i stället från omdirigeringen på github.com/…/releases/latest.
 * Cachas 6 timmar (1 timme om inget hittades). Senaste kollen sparas i
 * optionen cvfs_gh_status så att den kan visas i admin.
 *
 * @return array|null { version, package, url, changelog, published }
 */
function cvfs_updater_release( $force = false ) {
	$cached = get_site_transient( 'cvfs_gh_release' );
	if ( ! $force && is_array( $cached ) ) {
		return empty( $cached['version'] ) ? null : $cached;
	}

	// Först github.com (ingen gräns på 60 anrop/timme per IP, som API:et har på delade
	// webbhotell), sedan API:et som reserv. Releasetexten hämtas från API:et först vid "Visa detaljer".
	$errors  = array();
	$release = cvfs_updater_from_web( $errors );
	if ( ! $release ) {
		$release = cvfs_updater_from_api( $errors );
	}

	update_option(
		'cvfs_gh_status',
		array(
			'time'    => time(),
			'version' => $release ? $release['version'] : '',
			'source'  => $release ? $release['source'] : '',
			'errors'  => $errors,
		),
		false
	);

	// Ett misslyckande sparas också (som tom version), annars frågar varje sidvisning GitHub igen.
	set_site_transient( 'cvfs_gh_release', $release ? $release : array( 'version' => '' ), $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
	return $release ? $release : null;
}

function cvfs_updater_http_args( $extra = array(), $api = false ) {
	$headers = array( 'User-Agent' => 'cyklavandra-forstasida/' . CVFS_VERSION );
	if ( $api ) {
		$headers['Accept'] = 'application/vnd.github+json';
	}
	return array_merge( array( 'timeout' => 15, 'headers' => $headers ), $extra );
}

function cvfs_updater_http_error( $label, $res ) {
	if ( is_wp_error( $res ) ) {
		return $label . ': ' . $res->get_error_message();
	}
	$msg  = $label . ': HTTP ' . wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( is_array( $body ) && ! empty( $body['message'] ) ) {
		$msg .= ' – ' . mb_substr( (string) $body['message'], 0, 160 );
	}
	return $msg;
}

/** Via GitHubs API. */
function cvfs_updater_from_api( &$errors ) {
	$res = wp_remote_get( 'https://api.github.com/repos/' . CVFS_GITHUB_REPO . '/releases/latest', cvfs_updater_http_args( array(), true ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		$errors[] = cvfs_updater_http_error( 'GitHub API', $res );
		return null;
	}
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
		$errors[] = 'GitHub API: oväntat svar.';
		return null;
	}
	foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
		if ( isset( $asset['name'], $asset['browser_download_url'] ) && CVFS_UPDATE_ASSET === $asset['name'] ) {
			return array(
				'version'   => ltrim( (string) $data['tag_name'], 'vV' ),
				'package'   => (string) $asset['browser_download_url'],
				'url'       => (string) ( $data['html_url'] ?? 'https://github.com/' . CVFS_GITHUB_REPO ),
				'changelog' => (string) ( $data['body'] ?? '' ),
				'published' => (string) ( $data['published_at'] ?? '' ),
				'source'    => 'api',
			);
		}
	}
	$errors[] = 'GitHub API: releasen ' . $data['tag_name'] . ' saknar ' . CVFS_UPDATE_ASSET . '.';
	return null;
}

/** Reserv utan API: följ inte omdirigeringen, läs taggen ur Location. */
function cvfs_updater_from_web( &$errors ) {
	$base = 'https://github.com/' . CVFS_GITHUB_REPO . '/releases';
	$res  = wp_remote_head( $base . '/latest', cvfs_updater_http_args( array( 'redirection' => 0 ) ) );
	$loc  = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_header( $res, 'location' );
	if ( ! preg_match( '#/releases/tag/(v?\d+(?:\.\d+)*)$#', $loc, $m ) ) {
		$errors[] = $loc ? 'github.com: oväntad adress ' . $loc : cvfs_updater_http_error( 'github.com', $res );
		return null;
	}
	$tag     = $m[1];
	$package = $base . '/download/' . $tag . '/' . CVFS_UPDATE_ASSET;
	$check   = wp_remote_head( $package, cvfs_updater_http_args( array( 'redirection' => 0 ) ) );
	$code    = is_wp_error( $check ) ? 0 : (int) wp_remote_retrieve_response_code( $check );
	if ( $code < 200 || $code >= 400 ) {
		$errors[] = cvfs_updater_http_error( 'github.com zip', $check );
		return null;
	}
	return array(
		'version'   => ltrim( $tag, 'vV' ),
		'package'   => $package,
		'url'       => $base . '/tag/' . $tag,
		'changelog' => '',
		'published' => '',
		'source'    => 'web',
	);
}

/** Uppdateringsobjektet WordPress vill ha för vår plugin. */
function cvfs_updater_item( $release ) {
	return (object) array(
		'id'           => 'https://github.com/' . CVFS_GITHUB_REPO,
		'slug'         => CVFS_UPDATE_SLUG,
		'plugin'       => plugin_basename( CVFS_FILE ),
		'version'      => $release['version'],
		'new_version'  => $release['version'],
		'url'          => $release['url'],
		'package'      => $release['package'],
		'requires_php' => '7.4',
		'banners'      => array(),
		'tested'       => '',
	);
}

/**
 * Extra säkerhet: WordPress hoppar över Update URI-kollen om anropet till
 * wordpress.org misslyckas. Lägg därför in vår uppdatering när listan läses
 * (bara i admin och cron, och bara från cachen eller en kontroll per timme).
 */
add_filter(
	'site_transient_update_plugins',
	function ( $value ) {
		if ( ! is_object( $value ) || ! ( is_admin() || wp_doing_cron() ) ) {
			return $value;
		}
		$file = plugin_basename( CVFS_FILE );
		if ( isset( $value->response[ $file ] ) ) {
			return $value;
		}
		$release = cvfs_updater_release();
		if ( $release && version_compare( $release['version'], CVFS_VERSION, '>' ) ) {
			if ( ! isset( $value->response ) || ! is_array( $value->response ) ) {
				$value->response = array();
			}
			$value->response[ $file ] = cvfs_updater_item( $release );
			unset( $value->no_update[ $file ] );
		}
		return $value;
	}
);

/**
 * Svarar WordPress när den letar efter uppdateringar för tillägg med Update URI på github.com.
 */
add_filter(
	'update_plugins_github.com',
	function ( $update, $plugin_data, $plugin_file ) {
		if ( plugin_basename( CVFS_FILE ) !== $plugin_file ) {
			return $update;
		}
		// "Sök igen" under Kontrollpanel → Uppdateringar hämtar färskt från GitHub.
		$force   = is_admin() && ! empty( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$release = cvfs_updater_release( $force );
		if ( ! $release ) {
			return $update;
		}
		return (array) cvfs_updater_item( $release );
	},
	10,
	3
);

/**
 * "Visa detaljer" i tilläggslistan: visar releasens text från GitHub.
 */
add_filter(
	'plugins_api',
	function ( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || CVFS_UPDATE_SLUG !== $args->slug ) {
			return $result;
		}
		$release = cvfs_updater_release();
		if ( ! $release ) {
			return $result;
		}
		if ( '' === trim( $release['changelog'] ) ) {
			// Releasetexten finns bara i API:et; ett misslyckat försök gör inget.
			$ignored = array();
			$api     = cvfs_updater_from_api( $ignored );
			if ( $api && $api['version'] === $release['version'] ) {
				$release['changelog'] = $api['changelog'];
				$release['published'] = $api['published'];
			}
		}
		$changelog = '' !== trim( $release['changelog'] )
			? wpautop( esc_html( $release['changelog'] ) )
			: '<p>Se <a href="' . esc_url( $release['url'] ) . '">releasen på GitHub</a>.</p>';

		return (object) array(
			'name'          => 'Cykla & Vandra – Förstasida',
			'slug'          => CVFS_UPDATE_SLUG,
			'version'       => $release['version'],
			'author'        => 'Cykla &amp; Vandra',
			'homepage'      => 'https://github.com/' . CVFS_GITHUB_REPO,
			'download_link' => $release['package'],
			'requires_php'  => '7.4',
			'last_updated'  => $release['published'],
			'sections'      => array(
				'changelog' => $changelog,
			),
		);
	},
	10,
	3
);

/**
 * Ser till att den uppackade mappen får samma namn som den installerade,
 * även om pluginen ligger i t.ex. "cyklavandra-forstasida-main".
 */
add_filter(
	'upgrader_source_selection',
	function ( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;
		if ( empty( $hook_extra['plugin'] ) || plugin_basename( CVFS_FILE ) !== $hook_extra['plugin'] || ! $wp_filesystem ) {
			return $source;
		}
		$wanted = trailingslashit( $remote_source ) . dirname( $hook_extra['plugin'] ) . '/';
		if ( '.' === dirname( $hook_extra['plugin'] ) || untrailingslashit( $source ) === untrailingslashit( $wanted ) ) {
			return $source;
		}
		if ( $wp_filesystem->move( $source, $wanted, true ) ) {
			return $wanted;
		}
		return new WP_Error( 'cvfs_rename_failed', 'Förstasida: kunde inte byta namn på uppdateringsmappen.' );
	},
	10,
	4
);

// Töm cachen efter en uppdatering så att nästa koll blir färsk.
add_action(
	'upgrader_process_complete',
	function ( $upgrader, $extra ) {
		// Enskild uppdatering skickar 'plugin', massuppdatering 'plugins'.
		$plugins = array_merge( (array) ( $extra['plugins'] ?? array() ), (array) ( $extra['plugin'] ?? array() ) );
		if ( isset( $extra['type'] ) && 'plugin' === $extra['type'] && in_array( plugin_basename( CVFS_FILE ), $plugins, true ) ) {
			delete_site_transient( 'cvfs_gh_release' );
		}
	},
	10,
	2
);

/**
 * Länken "Sök efter uppdatering" i tilläggslistan.
 */
add_filter(
	'plugin_action_links_' . plugin_basename( CVFS_FILE ),
	function ( $links ) {
		if ( current_user_can( 'update_plugins' ) ) {
			$url     = wp_nonce_url( admin_url( 'admin-post.php?action=cvfs_check_update' ), 'cvfs_check_update' );
			$links[] = '<a href="' . esc_url( $url ) . '">Sök efter uppdatering</a>';
		}
		return $links;
	}
);

add_action(
	'admin_post_cvfs_check_update',
	function () {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'Behörighet saknas.' );
		}
		check_admin_referer( 'cvfs_check_update' );
		delete_site_transient( 'cvfs_gh_release' );
		cvfs_updater_release( true );
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		wp_safe_redirect( admin_url( 'plugins.php?cvfs_checked=1' ) );
		exit;
	}
);

/**
 * Visar resultatet av "Sök efter uppdatering" överst i tilläggslistan.
 */
add_action(
	'admin_notices',
	function () {
		if ( empty( $_GET['cvfs_checked'] ) || ! current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$st      = get_option( 'cvfs_gh_status', array() );
		$found   = $st['version'] ?? '';
		$errors  = (array) ( $st['errors'] ?? array() );
		$newer   = $found && version_compare( $found, CVFS_VERSION, '>' );
		$class   = $found ? ( $newer ? 'notice-info' : 'notice-success' ) : 'notice-error';
		$msg     = '<strong>Förstasida:</strong> installerad version ' . esc_html( CVFS_VERSION ) . '. ';
		if ( ! $found ) {
			$msg .= 'Kunde inte läsa senaste versionen från GitHub.';
		} elseif ( $newer ) {
			$msg .= 'Version ' . esc_html( $found ) . ' finns – se raden för Förstasida nedan.';
		} else {
			$msg .= 'Senaste på GitHub är ' . esc_html( $found ) . ', du har den senaste.';
		}
		if ( $errors && ! $found ) { // Fel visas bara när ingen version hittades.
			$msg .= '<br><small>' . implode( '<br>', array_map( 'esc_html', $errors ) ) . '</small>';
		}
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . $msg . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
);
