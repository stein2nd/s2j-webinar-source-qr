<?php
/**
 * Plugin Name: S2J Source QR Studio definitions stub
 * Description: Mock Webinar tracking-source-definitions for wsq-smoke manual UI (U7 / catalogReady).
 *
 * Copy to Studio site: wp-content/mu-plugins/s2j-wsq-studio-definitions-stub.php
 *
 * @package S2J_Webinar_Source_Qr
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
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'callback'            => static function (): WP_REST_Response {
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
