<?php
/**
 * REST route registration (wp-env).
 *
 * @package S2J_Webinar_Source_Qr
 */

require_once dirname( __DIR__ ) . '/stubs/wordpress-test-stubs.php';

/**
 * REST smoke tests.
 */
class RestRoutesTest extends WP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Set up REST server.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$this->server       = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	/**
	 * Document GET route is registered.
	 */
	public function test_document_route_exists() {
		$route = '/' . S2J_Webinar_Source_Qr_Constants::REST_NAMESPACE . '/events/(?P<post_id>[\\d]+)/document';
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( $route, $routes );
	}
}
