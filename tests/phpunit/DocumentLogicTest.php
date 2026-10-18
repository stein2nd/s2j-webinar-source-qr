<?php
/**
 * Pure document normalization tests (no WordPress bootstrap).
 *
 * @package S2J_Webinar_Source_Qr
 */

use PHPUnit\Framework\TestCase;

/**
 * Document helper tests.
 */
class DocumentLogicTest extends TestCase {

	/**
	 * Sample Webinar catalog.
	 *
	 * @return array<int, array{source_name: string, label: string}>
	 */
	private function sample_catalog() {
		return array(
			array(
				'source_name' => 'newsletter',
				'label'       => 'Newsletter',
			),
			array(
				'source_name' => 'social',
				'label'       => 'Social',
			),
		);
	}

	/**
	 * Aligns rows to catalog order and drops unknown ids.
	 */
	public function test_align_channels_to_catalog() {
		$incoming = array(
			array(
				'id'               => 'social',
				'label'            => 'Old',
				'registration_url' => 'https://example.com/s',
				'qr_attachment_id' => 0,
			),
		);

		$rows = s2j_wsq_align_channels_to_catalog( $this->sample_catalog(), $incoming );

		$this->assertCount( 2, $rows );
		$this->assertSame( 'newsletter', $rows[0]['id'] );
		$this->assertSame( '', $rows[0]['registration_url'] );
		$this->assertSame( 'social', $rows[1]['id'] );
		$this->assertSame( 'https://example.com/s', $rows[1]['registration_url'] );
		$this->assertSame( 'Social', $rows[1]['label'] );
	}

	/**
	 * Draft normalize allows empty URLs.
	 */
	public function test_normalize_for_draft_allows_partial_urls() {
		$document = array(
			'channels' => array(),
			'style'    => s2j_wsq_default_style(),
		);

		$result = s2j_wsq_normalize_for_draft( $document, $this->sample_catalog(), null );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'draft', $result['document']['status'] );
		$this->assertSame( '', $result['document']['channels'][0]['registration_url'] );
	}

	/**
	 * Invalid URL returns invalid_registration_url.
	 */
	public function test_normalize_rejects_bad_url() {
		$document = array(
			'channels' => array(
				array(
					'id'               => 'social',
					'registration_url' => 'not-a-url',
					'qr_attachment_id' => 0,
				),
			),
			'style'    => s2j_wsq_default_style(),
		);

		$result = s2j_wsq_normalize_for_draft( $document, $this->sample_catalog(), null );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'invalid_registration_url', $result['error']['code'] );
	}

	/**
	 * Invalid style hex returns invalid_document.
	 */
	public function test_normalize_rejects_invalid_style_hex() {
		$document = array(
			'channels' => array(),
			'style'    => array(
				'foreground_hex'     => 'bad',
				'size_px'            => 512,
				'icon_attachment_id' => 0,
			),
		);

		$result = s2j_wsq_normalize_for_draft( $document, $this->sample_catalog(), null );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'invalid_document', $result['error']['code'] );
	}

	/**
	 * Import merge assigns only known source names.
	 */
	public function test_merge_tracking_items_unassigned() {
		$document = s2j_wsq_merge_document(
			array(
				'channels' => array(
					array(
						'id'               => 'newsletter',
						'registration_url' => '',
						'qr_attachment_id' => 0,
					),
				),
			)
		);

		$merged = s2j_wsq_merge_tracking_items(
			$document,
			array(
				array(
					'source_name'      => 'newsletter',
					'registration_url' => 'https://example.com/n',
				),
				array(
					'source_name'      => 'unknown',
					'registration_url' => 'https://example.com/u',
				),
			)
		);

		$this->assertCount( 1, $merged['unassigned'] );
		$this->assertSame(
			'https://example.com/n',
			$merged['document']['channels'][0]['registration_url']
		);
	}
}
