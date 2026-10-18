<?php
/**
 * S2J Webinar Source QR
 *
 * @package S2J_Webinar_Source_Qr
 * @author Koutarou ISHIKAWA
 * @license GPL-3.0-or-later
 *
 * Plugin Name: S2J Webinar Source QR
 * Plugin URI: https://github.com/stein2nd/s2j-webinar-source-qr
 * Description: Source tracking registration URLs and QR PNG maintenance for GatherPress events.
 * Version: 0.0.1
 * Author: Koutarou ISHIKAWA
 * Author URI: https://stein2nd.wordpress.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: s2j-webinar-source-qr
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'S2J_WEBINAR_SOURCE_QR_VERSION', '0.0.1' );
define( 'S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'S2J_WEBINAR_SOURCE_QR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'S2J_WEBINAR_SOURCE_QR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$composer_autoload = S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR . 'vendor/autoload.php';
if ( is_readable( $composer_autoload ) ) {
	require_once $composer_autoload;
}

/**
 * Load plugin PHP modules.
 */
function s2j_webinar_source_qr_load_dependencies() {
	$dir = S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR . 'includes/';

	require_once $dir . 'Constants.php';
	require_once $dir . 'Domain/document.php';
	require_once $dir . 'Domain/catalog.php';
	require_once $dir . 'Domain/provider.php';
	require_once $dir . 'Domain/qr.php';
	require_once $dir . 'Meta/Registrar.php';
	require_once $dir . 'Meta/Guard.php';
	require_once $dir . 'Meta/SavePost.php';
	require_once $dir . 'RestController.php';
	require_once $dir . 'Editor/GutenbergMount.php';
}

/**
 * Bootstrap plugin components.
 */
function s2j_webinar_source_qr_bootstrap() {
	s2j_webinar_source_qr_load_dependencies();

	new S2J_Webinar_Source_Qr_Meta_Registrar();
	new S2J_Webinar_Source_Qr_Meta_Guard();
	new S2J_Webinar_Source_Qr_Meta_Save_Post();
	new S2J_Webinar_Source_Qr_Rest_Controller();
	new S2J_Webinar_Source_Qr_Gutenberg_Mount();
}

/**
 * Load text domain.
 */
function s2j_webinar_source_qr_load_textdomain() {
	load_plugin_textdomain(
		's2j-webinar-source-qr',
		false,
		dirname( S2J_WEBINAR_SOURCE_QR_PLUGIN_BASENAME ) . '/languages'
	);
}

add_action( 'plugins_loaded', 's2j_webinar_source_qr_load_textdomain' );
add_action( 'init', 's2j_webinar_source_qr_bootstrap' );
