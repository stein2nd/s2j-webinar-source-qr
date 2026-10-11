<?php
/**
 * save_post guards (re-normalize deferred to REST apply/draft in v0.0.1 skeleton).
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skips autosave and non-event post types; future hook for CoverArt-style re-normalize.
 */
class S2J_Webinar_Source_Qr_Meta_Save_Post {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'maybe_touch_post' ), 10, 2 );
	}

	/**
	 * Placeholder for explicit post update re-normalize (persistence_spec).
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function maybe_touch_post( $post_id, $post ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( ! post_type_supports( $post->post_type, 'gatherpress-event-date' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Skeleton: REST draft/apply owns normalization; save_post hook reserved for step-2 work.
	}
}
