<?php

/**
 * save_post re-normalize (CoverArt / Survey SavePostHandler 同型).
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Re-aligns stored source QR meta to the current Webinar catalog when the event post is saved.
 */
class S2J_Webinar_Source_Qr_Meta_Save_Post
{
    /**
     * Prevents nested save_post handling when meta update runs.
     *
     * @var bool
     */
    private static $running = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('save_post', array( $this, 'maybe_touch_post' ), 20, 2);
    }

    /**
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function maybe_touch_post($post_id, $post)
    {
        if (self::$running) {
            return;
        }

        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        $post_type = get_post_type($post_id);
        if (! is_string($post_type) || ! post_type_supports($post_type, 'gatherpress-event-date')) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        self::$running = true;
        s2j_wsq_renormalize_meta_on_post_save((int) $post_id);
        self::$running = false;
    }
}
