<?php
/**
 * Plugin Name: Cykla & Vandra – Förstasida
 * Description: Bygger förstasidan på cyklavandra.se från inställningar per språk (WPML). Använd shortcoden [cv_forstasida] på startsidan. Inställningar → Förstasida.
 * Version:     0.1.0
 * Author:      Cykla & Vandra
 * Text Domain: cvfs
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Update URI:  https://github.com/Cyklavandra-se/forstasida
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Skydd om pluginen råkar finnas installerad två gånger (t.ex. "Source code"-zip från GitHub).
if ( defined( 'CVFS_VERSION' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p><strong>Förstasida</strong> är installerad två gånger. Avaktivera och radera den ena kopian under Tillägg.</p></div>';
		}
	);
	return;
}

define( 'CVFS_VERSION', '0.1.0' );
define( 'CVFS_FILE', __FILE__ );
define( 'CVFS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CVFS_URL', plugin_dir_url( __FILE__ ) );

require_once CVFS_DIR . 'includes/lang.php';
require_once CVFS_DIR . 'includes/settings.php';
require_once CVFS_DIR . 'includes/trips.php';
require_once CVFS_DIR . 'includes/render.php';
require_once CVFS_DIR . 'includes/updater.php';

if ( is_admin() ) {
	require_once CVFS_DIR . 'includes/admin.php';
}
