<?php
/**
 * Provider resolution (data_dictionary).
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve provider for GET / draft / apply (ignores REST body override).
 *
 * @param int                  $post_id  Event post ID.
 * @param array<string, mixed> $document Merged document (may include stored provider).
 * @return string
 */
function s2j_wsq_resolve_provider( $post_id, array $document ) {
	$stored = isset( $document['provider'] ) ? trim( (string) $document['provider'] ) : '';
	if ( '' !== $stored ) {
		return $stored;
	}

	$webinar = get_post_meta( $post_id, '_s2j_webinar_provider', true );
	if ( is_string( $webinar ) && '' !== trim( $webinar ) ) {
		return trim( $webinar );
	}

	return 'zoom';
}
