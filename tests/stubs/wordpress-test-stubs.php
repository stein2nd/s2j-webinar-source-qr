<?php
/**
 * IDE / static-analysis stubs for the WordPress PHPUnit test suite.
 *
 * Not loaded by tests/bootstrap.wp-env.php. Real classes come from wp-env at runtime.
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( class_exists( 'WP_UnitTestCase', false ) ) {
	return;
}

/**
 * WordPress core PHPUnit base test case (stub).
 */
abstract class WP_UnitTestCase extends \PHPUnit\Framework\TestCase {

	/**
	 * WordPress test data factory.
	 *
	 * @var WP_UnitTest_Factory
	 */
	protected $factory;
}

/**
 * WordPress test factory (stub).
 */
class WP_UnitTest_Factory {

	/**
	 * @var WP_UnitTest_Factory_For_User
	 */
	public $user;
}

/**
 * User factory (stub).
 */
class WP_UnitTest_Factory_For_User {

	/**
	 * @param array<string, mixed> $args User arguments.
	 * @return int User ID.
	 */
	public function create( array $args = array() ) {
		return 0;
	}
}

/**
 * REST API server (stub).
 */
class WP_REST_Server {

	/**
	 * @return array<string, mixed>
	 */
	public function get_routes() {
		return array();
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function dispatch( WP_REST_Request $request ) {
		unset( $request );
		return new WP_REST_Response();
	}
}

/**
 * REST API request (stub).
 */
class WP_REST_Request {

	/**
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 */
	public function __construct( string $method = 'GET', string $route = '' ) {
		unset( $method, $route );
	}
}

/**
 * REST API response (stub).
 */
class WP_REST_Response {

	/**
	 * @return int HTTP status code.
	 */
	public function get_status() {
		return 200;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_data() {
		return array();
	}
}
