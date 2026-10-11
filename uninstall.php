<?php
/**
 * Uninstall: remove plugin-owned meta only (keep QR PNG media).
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Constants.php';

if ( ! function_exists( 'get_post_types_by_support' ) ) {
	return;
}

$post_types = get_post_types_by_support( 'gatherpress-event-date' );
if ( empty( $post_types ) ) {
	return;
}

$post_ids = get_posts(
	array(
		'post_type'      => $post_types,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

foreach ( $post_ids as $post_id ) {
	delete_post_meta( (int) $post_id, S2J_Webinar_Source_Qr_Constants::META_KEY );
}
