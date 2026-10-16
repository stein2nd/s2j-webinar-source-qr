<?php
/**
 * PHPUnit bootstrap for pure unit tests (no WordPress test suite / DB).
 *
 * @package S2J_Webinar_Source_Qr
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );

$polyfills = TESTS_PLUGIN_DIR . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( file_exists( $polyfills ) ) {
	require_once $polyfills;
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', TESTS_PLUGIN_DIR . '/' );
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Minimal esc_url_raw for document URL validation tests.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

require_once TESTS_PLUGIN_DIR . '/includes/Constants.php';
require_once TESTS_PLUGIN_DIR . '/includes/Domain/document.php';
