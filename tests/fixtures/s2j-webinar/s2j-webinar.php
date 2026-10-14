<?php
/**
 * Plugin Name: S2J Webinar (wp-env test stub)
 * Description: Minimal REST stub for Source QR integration tests (tracking-source-definitions).
 * Version: 0.0.0-test
 *
 * @package S2J_Webinar_Source_Qr_Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			's2j-webinar/v1',
			'/events/(?P<post_id>[\d]+)/tracking-source-definitions',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					return new WP_REST_Response(
						array(
							'sources' => array(
								array(
									'source_name' => 'web',
									'label'       => 'Web',
								),
								array(
									'source_name' => 'sns',
									'label'       => 'SNS',
								),
							),
						),
						200
					);
				},
			)
		);
	}
);
