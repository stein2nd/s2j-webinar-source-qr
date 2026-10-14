<?php
/**
 * Webinar source catalog (definitions REST) helpers.
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether S2J Webinar plugin is active.
 *
 * @return bool
 */
function s2j_wsq_is_webinar_plugin_active() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return is_plugin_active( 's2j-webinar/s2j-webinar.php' );
}

/**
 * Fetch source definitions for an event via Webinar REST (in-process).
 *
 * @param int $post_id Event post ID.
 * @return array<int, array{source_name: string, label: string}>|WP_Error 503 catalog_unavailable on failure.
 */
function s2j_wsq_fetch_source_definitions( $post_id ) {
	if ( ! s2j_wsq_is_webinar_plugin_active() ) {
		return new WP_Error(
			'catalog_unavailable',
			__( 'Source catalog is unavailable.', 's2j-webinar-source-qr' ),
			array( 'status' => 503 )
		);
	}

	$path     = '/' . S2J_Webinar_Source_Qr_Constants::WEBINAR_REST_NAMESPACE . '/events/' . (int) $post_id . '/tracking-source-definitions';
	$request  = new WP_REST_Request( 'GET', $path );
	$response = rest_do_request( $request );

	if ( $response->is_error() ) {
		return new WP_Error(
			'catalog_unavailable',
			__( 'Source catalog is unavailable.', 's2j-webinar-source-qr' ),
			array( 'status' => 503 )
		);
	}

	$data    = $response->get_data();
	$sources = isset( $data['sources'] ) && is_array( $data['sources'] ) ? $data['sources'] : array();

	if ( count( $sources ) === 0 ) {
		return new WP_Error(
			'catalog_unavailable',
			__( 'Source catalog is unavailable.', 's2j-webinar-source-qr' ),
			array( 'status' => 503 )
		);
	}

	$normalized = array();
	foreach ( $sources as $row ) {
		if ( ! is_array( $row ) || empty( $row['source_name'] ) ) {
			continue;
		}
		$normalized[] = array(
			'source_name' => (string) $row['source_name'],
			'label'       => isset( $row['label'] ) ? (string) $row['label'] : '',
		);
	}

	if ( count( $normalized ) === 0 ) {
		return new WP_Error(
			'catalog_unavailable',
			__( 'Source catalog is unavailable.', 's2j-webinar-source-qr' ),
			array( 'status' => 503 )
		);
	}

	return $normalized;
}

/**
 * Normalize catalog rows to channel id list (definition order).
 *
 * @param array<int, array{source_name: string, label: string}> $sources Catalog rows.
 * @return array<int, array{id: string, label: string}>
 */
function s2j_wsq_catalog_to_channel_stubs( array $sources ) {
	$out = array();
	foreach ( $sources as $row ) {
		$out[] = array(
			'id'    => $row['source_name'],
			'label' => $row['label'],
		);
	}
	return $out;
}
