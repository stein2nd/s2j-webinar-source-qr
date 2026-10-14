<?php
/**
 * REST draft / catalog guard / apply (wp-env).
 *
 * @package S2J_Webinar_Source_Qr
 */

require_once dirname( __DIR__ ) . '/stubs/wordpress-test-stubs.php';

/**
 * Document persistence REST tests.
 */
class RestDocumentTest extends WP_UnitTestCase {

	const WEBINAR_PLUGIN = 's2j-webinar/s2j-webinar.php';

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * GatherPress event post ID.
	 *
	 * @var int
	 */
	private $event_id;

	/**
	 * Set up user and event.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$event_type = $this->gatherpress_event_post_type();
		if ( ! $event_type ) {
			$this->markTestSkipped( 'No post type with gatherpress-event-date support (GatherPress missing?).' );
		}

		$this->event_id = self::factory()->post->create(
			array(
				'post_type'   => $event_type,
				'post_status' => 'draft',
				'post_title'  => 'REST integration event',
			)
		);
	}

	/**
	 * POST draft returns 503 when S2J Webinar is inactive (catalog guard).
	 */
	public function test_save_draft_returns_503_when_catalog_unavailable() {
		deactivate_plugins( self::WEBINAR_PLUGIN );

		$response = $this->dispatch_document_request(
			'POST',
			'/document/draft',
			array(
				'status'   => 'draft',
				'channels' => array(),
				'style'    => array(
					'foreground_hex'     => '#112233',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		activate_plugins( self::WEBINAR_PLUGIN );

		$this->assertSame( 503, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'catalog_unavailable', $data['code'] );
	}

	/**
	 * POST draft persists meta when catalog is available.
	 */
	public function test_save_draft_returns_200_and_persists_meta() {
		$response = $this->dispatch_document_request(
			'POST',
			'/document/draft',
			array(
				'status'   => 'draft',
				'channels' => array(),
				'style'    => array(
					'foreground_hex'     => '#445566',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'draft', $data['status'] );
		$this->assertCount( 2, $data['channels'] );
		$this->assertSame( '#445566', $data['style']['foreground_hex'] );

		$stored = get_post_meta( $this->event_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true );
		$this->assertIsArray( $stored );
		$this->assertSame( '#445566', $stored['style']['foreground_hex'] );
	}

	/**
	 * POST apply returns 501 until QR PNG pipeline exists.
	 */
	public function test_apply_returns_501_qr_not_implemented() {
		$body = array(
			'status'   => 'draft',
			'channels' => array(
				array(
					'id'               => 'web',
					'registration_url' => 'https://example.com/register-web',
				),
				array(
					'id'               => 'sns',
					'registration_url' => 'https://example.com/register-sns',
				),
			),
			'style'    => array(
				'foreground_hex'     => '#000000',
				'size_px'            => 512,
				'icon_attachment_id' => 0,
			),
		);

		$response = $this->dispatch_document_request( 'POST', '/document/apply', $body );

		$this->assertSame( 501, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'qr_not_implemented', $data['code'] );
	}

	/**
	 * @return string|null
	 */
	private function gatherpress_event_post_type() {
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( post_type_supports( $type, 'gatherpress-event-date' ) ) {
				return $type;
			}
		}
		return null;
	}

	/**
	 * @param string               $method HTTP method.
	 * @param string               $suffix Route after /events/{id}.
	 * @param array<string, mixed> $body   JSON body.
	 * @return WP_REST_Response
	 */
	private function dispatch_document_request( $method, $suffix, array $body ) {
		wp_set_current_user( $this->admin_id );

		$path = '/' . S2J_Webinar_Source_Qr_Constants::REST_NAMESPACE . '/events/' . $this->event_id . $suffix;
		$request = new WP_REST_Request( $method, $path );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_get_server()->dispatch( $request );
	}
}
