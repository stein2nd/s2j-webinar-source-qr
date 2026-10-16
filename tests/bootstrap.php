<?php
/**
 * PHPUnit bootstrap for WordPress integration tests (local MySQL or wp-env).
 *
 * @package S2J_Webinar_Source_Qr
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'TESTS_PLUGIN_FILE', TESTS_PLUGIN_DIR . '/s2j-webinar-source-qr.php' );

$polyfills = TESTS_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills ) ) {
	require_once $polyfills;
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = TESTS_PLUGIN_DIR . '/tests/.wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find ' . $_tests_dir . '/includes/functions.php.' . PHP_EOL;
	echo 'Local (no Docker): copy .env.tests.example to .env.tests, then run npm run test:unit:php:local' . PHP_EOL;
	echo 'Or: bash bin/install-wp-tests.sh <db> <user> <pass> [host]' . PHP_EOL;
	echo 'CI / Docker: npm run test:unit:php' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';
require_once __DIR__ . '/stubs/wordpress-test-functions.php';

/**
 * Load plugin under test.
 *
 * @return void
 */
function _s2j_wsq_tests_load_plugin(): void {
	require TESTS_PLUGIN_FILE;
}

tests_add_filter( 'muplugins_loaded', '_s2j_wsq_tests_load_plugin' );

/**
 * Load GatherPress + Webinar REST stub without activate_plugin() on plugins_loaded.
 *
 * activate_plugin() before after_setup_theme makes core translate plugin headers (WP 6.7+ notice).
 *
 * @see https://github.com/GatherPress/gatherpress/issues/2426#issuecomment-6214838497
 *
 * @return void
 */
function _s2j_wsq_tests_require_integration_plugins(): void {
	foreach ( array( 'gatherpress/gatherpress.php', 's2j-webinar/s2j-webinar.php' ) as $plugin ) {
		$path = WP_PLUGIN_DIR . '/' . $plugin;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}

tests_add_filter( 'plugins_loaded', '_s2j_wsq_tests_require_integration_plugins', 0 );

/**
 * GatherPress DB setup + mark integration plugins active (on init, after i18n is allowed).
 *
 * @return void
 */
function _s2j_wsq_tests_bootstrap_integration_plugins_on_init(): void {
	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( class_exists( 'GatherPress\Core\Setup' ) ) {
		GatherPress\Core\Setup::get_instance()->activate_gatherpress_plugin( false );
	}

	$webinar = 's2j-webinar/s2j-webinar.php';
	if ( is_readable( WP_PLUGIN_DIR . '/' . $webinar ) && ! is_plugin_active( $webinar ) ) {
		activate_plugin( $webinar, '', false, true );
	}
}

tests_add_filter( 'init', '_s2j_wsq_tests_bootstrap_integration_plugins_on_init', 0 );

require $_tests_dir . '/includes/bootstrap.php';
