<?php
/**
 * IDE / static-analysis stubs for the WordPress PHPUnit test suite.
 *
 * Not loaded by tests/bootstrap.php. Real classes come from the WordPress test suite at runtime.
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
	 * @var WP_UnitTest_Factory|null
	 */
	protected static $factory_object = null;

	/**
	 * WordPress test data factory (static entry point; matches core WP_UnitTestCase).
	 *
	 * @return WP_UnitTest_Factory
	 */
	public static function factory() {
		if ( null === self::$factory_object ) {
			self::$factory_object = new WP_UnitTest_Factory();
		}
		return self::$factory_object;
	}
}

/**
 * WordPress test factory (stub).
 */
class WP_UnitTest_Factory {

	/**
	 * @var WP_UnitTest_Factory_For_User
	 */
	public $user;

	/**
	 * @var WP_UnitTest_Factory_For_Post
	 */
	public $post;

	public function __construct() {
		$this->user = new WP_UnitTest_Factory_For_User();
		$this->post = new WP_UnitTest_Factory_For_Post();
	}
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
		unset( $args );
		return 0;
	}
}

/**
 * Post factory (stub).
 */
class WP_UnitTest_Factory_For_Post {

	/**
	 * @param array<string, mixed> $args Post arguments.
	 * @return int Post ID.
	 */
	public function create( array $args = array() ) {
		unset( $args );
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
