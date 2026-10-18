<?php
/**
 * REST draft / catalog guard / apply (integration: local MySQL or wp-env).
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
	 * POST apply returns 200 with ready status and PNG attachments.
	 */
	public function test_apply_returns_200_and_ready_with_attachments() {
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

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'ready', $data['status'] );
		$this->assertCount( 2, $data['channels'] );

		foreach ( $data['channels'] as $row ) {
			$this->assertNotEmpty( $row['qr_attachment_id'] );
			$mime = get_post_mime_type( (int) $row['qr_attachment_id'] );
			$this->assertSame( 'image/png', $mime );
		}

		$stored = get_post_meta( $this->event_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true );
		$this->assertIsArray( $stored );
		$this->assertSame( 'ready', $stored['status'] );
	}

	/**
	 * POST preview returns data_url PNG without persisting meta.
	 */
	public function test_preview_returns_data_url_and_does_not_persist_meta() {
		$before = get_post_meta( $this->event_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true );

		$response = $this->dispatch_document_request(
			'POST',
			'/document/preview',
			array(
				'registration_url' => 'https://example.com/register-preview',
				'style'            => array(
					'foreground_hex'     => '#112233',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'data_url', $data );
		$this->assertIsString( $data['data_url'] );
		$this->assertStringStartsWith( 'data:image/png;base64,', $data['data_url'] );

		$after = get_post_meta( $this->event_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true );
		$this->assertSame( $before, $after );
	}

	/**
	 * Preview rejects empty registration URL.
	 */
	public function test_preview_returns_400_for_empty_url() {
		$response = $this->dispatch_document_request(
			'POST',
			'/document/preview',
			array(
				'registration_url' => '',
				'style'            => array(
					'foreground_hex'     => '#000000',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Apply composites style.icon_attachment_id at QR center (white plate + icon).
	 */
	public function test_apply_with_center_icon_marks_center_bright() {
		$icon_id = $this->create_test_png_attachment();

		$response = $this->dispatch_document_request(
			'POST',
			'/document/apply',
			array(
				'status'   => 'draft',
				'channels' => array(
					array(
						'id'               => 'web',
						'registration_url' => 'https://example.com/register-web-icon',
					),
					array(
						'id'               => 'sns',
						'registration_url' => 'https://example.com/register-sns-icon',
					),
				),
				'style'    => array(
					'foreground_hex'     => '#000000',
					'size_px'            => 512,
					'icon_attachment_id' => $icon_id,
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$qr_id  = (int) $data['channels'][0]['qr_attachment_id'];
		$this->assertGreaterThan( 0, $qr_id );

		$path = get_attached_file( $qr_id );
		$this->assertIsString( $path );
		$this->assertFileExists( $path );

		$image = imagecreatefrompng( $path );
		$this->assertNotFalse( $image );

		$cx  = (int) floor( imagesx( $image ) / 2 );
		$cy  = (int) floor( imagesy( $image ) / 2 );
		$rgb = imagecolorat( $image, $cx, $cy );
		$r = ( $rgb >> 16 ) & 0xFF;

		$this->assertGreaterThan( 240, $r, 'Center pixel should lie on the white icon plate.' );
	}

	/**
	 * Apply returns 400 when any catalog row lacks a registration URL.
	 */
	public function test_apply_returns_400_for_missing_registration_url() {
		$response = $this->dispatch_document_request(
			'POST',
			'/document/apply',
			array(
				'status'   => 'draft',
				'channels' => array(
					array(
						'id'               => 'web',
						'registration_url' => 'https://example.com/register-web',
					),
					array(
						'id'               => 'sns',
						'registration_url' => '',
					),
				),
				'style'    => array(
					'foreground_hex'     => '#000000',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'invalid_registration_url', $data['code'] );

		$stored = get_post_meta( $this->event_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true );
		$this->assertSame( '', $stored );
	}

	/**
	 * Draft returns 400 for malformed registration URL.
	 */
	public function test_draft_returns_400_for_invalid_registration_url() {
		$response = $this->dispatch_document_request(
			'POST',
			'/document/draft',
			array(
				'status'   => 'draft',
				'channels' => array(
					array(
						'id'               => 'web',
						'registration_url' => 'ftp://example.com/register',
					),
				),
				'style'    => array(
					'foreground_hex'     => '#112233',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'invalid_registration_url', $data['code'] );
	}

	/**
	 * Draft returns 400 for invalid style hex.
	 */
	public function test_draft_returns_400_for_invalid_document_style() {
		$response = $this->dispatch_document_request(
			'POST',
			'/document/draft',
			array(
				'status'   => 'draft',
				'channels' => array(),
				'style'    => array(
					'foreground_hex'     => 'not-hex',
					'size_px'            => 512,
					'icon_attachment_id' => 0,
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'invalid_document', $data['code'] );
	}

	/**
	 * Direct meta update is rejected when catalog is unavailable.
	 */
	public function test_direct_meta_update_blocked_when_catalog_unavailable() {
		deactivate_plugins( self::WEBINAR_PLUGIN );

		$payload = array(
			'status'   => 'draft',
			'channels' => array(),
			'style'    => array(
				'foreground_hex'     => '#112233',
				'size_px'            => 512,
				'icon_attachment_id' => 0,
			),
		);

		$result = update_post_meta(
			$this->event_id,
			S2J_Webinar_Source_Qr_Constants::META_KEY,
			$payload
		);

		activate_plugins( self::WEBINAR_PLUGIN );

		$this->assertFalse( $result );
		$stored = get_post_meta( $this->event_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true );
		$this->assertSame( '', $stored );
	}

	/**
	 * Direct meta update cannot set ready without REST apply bypass.
	 */
	public function test_direct_meta_update_rejects_ready_status() {
		$payload = array(
			'status'   => 'ready',
			'channels' => array(
				array(
					'id'               => 'web',
					'registration_url' => 'https://example.com/r',
				),
			),
			'style'    => array(
				'foreground_hex'     => '#112233',
				'size_px'            => 512,
				'icon_attachment_id' => 0,
			),
		);

		$result = update_post_meta(
			$this->event_id,
			S2J_Webinar_Source_Qr_Constants::META_KEY,
			$payload
		);

		$this->assertFalse( $result );
	}

	/**
	 * Apply fails when icon_attachment_id does not reference an image.
	 */
	public function test_apply_returns_400_for_invalid_center_icon() {
		$response = $this->dispatch_document_request(
			'POST',
			'/document/apply',
			array(
				'status'   => 'draft',
				'channels' => array(
					array(
						'id'               => 'web',
						'registration_url' => 'https://example.com/register-web-bad-icon',
					),
					array(
						'id'               => 'sns',
						'registration_url' => 'https://example.com/register-sns-bad-icon',
					),
				),
				'style'    => array(
					'foreground_hex'     => '#000000',
					'size_px'            => 512,
					'icon_attachment_id' => 999999,
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertSame( 'qr_generation_failed', $data['code'] );
	}

	/**
	 * @return int Attachment ID.
	 */
	private function create_test_png_attachment() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$image = imagecreatetruecolor( 32, 32 );
		$white = imagecolorallocate( $image, 255, 255, 255 );
		imagefilledrectangle( $image, 0, 0, 31, 31, $white );
		ob_start();
		imagepng( $image );
		$bytes = ob_get_clean();

		$upload = wp_upload_bits( 'wsq-test-icon.png', null, $bytes );
		$this->assertEmpty( $upload['error'] );

		$attach_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_title'     => 'wsq-test-icon',
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload['file'],
			$this->event_id
		);
		$this->assertIsInt( $attach_id );
		$this->assertGreaterThan( 0, $attach_id );

		$metadata = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attach_id, $metadata );
		}

		return (int) $attach_id;
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
