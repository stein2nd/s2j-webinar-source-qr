<?php

/**
 * Block direct meta writes that bypass REST draft/apply normalization.
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Meta update guard (catalog + draft normalization; persistence_spec).
 */
class S2J_Webinar_Source_Qr_Meta_Guard
{
    /**
     * When true, {@see update_post_metadata} guard is skipped (REST draft/apply already validated).
     *
     * @var bool
     */
    private static $bypass = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('update_post_metadata', array( $this, 'filter_update' ), 10, 5);
    }

    /**
     * Skip guard for trusted REST persistence.
     *
     * @param bool $on Whether to bypass.
     */
    public static function set_bypass($on)
    {
        self::$bypass = (bool) $on;
    }

    /**
     * @return bool
     */
    public static function is_bypassed()
    {
        return self::$bypass;
    }

    /**
     * Reject meta updates that fail draft-equivalent normalization.
     *
     * @param null|bool $check      Short-circuit return value.
     * @param int       $object_id  Post ID.
     * @param string    $meta_key   Meta key.
     * @param mixed     $meta_value New value.
     * @param mixed     $prev_value Previous value.
     * @return null|bool
     */
    public function filter_update($check, $object_id, $meta_key, $meta_value, $prev_value)
    {
        if (null !== $check) {
            return $check;
        }

        if (S2J_Webinar_Source_Qr_Constants::META_KEY !== $meta_key) {
            return $check;
        }

        if (self::$bypass) {
            return $check;
        }

        if (! s2j_wsq_meta_update_is_allowed((int) $object_id, $meta_value, $prev_value)) {
            return false;
        }

        return $check;
    }
}

/**
 * Whether a non-REST meta write may proceed (draft rules; `ready` only via apply + bypass).
 *
 * @param int   $post_id    Event post ID.
 * @param mixed $meta_value Incoming meta.
 * @param mixed $prev_value Stored meta.
 * @return bool
 */
function s2j_wsq_meta_update_is_allowed($post_id, $meta_value, $prev_value)
{
    $merged = s2j_wsq_merge_document($meta_value);
    if (isset($merged['status']) && 'ready' === $merged['status']) {
        return false;
    }

    $previous = is_array($prev_value) ? $prev_value : null;
    $prepared = s2j_wsq_prepare_document_for_draft_storage($post_id, $merged, $previous);

    return ! empty($prepared['ok']);
}

/**
 * @param bool $on Bypass flag.
 */
function s2j_wsq_meta_guard_set_bypass($on)
{
    S2J_Webinar_Source_Qr_Meta_Guard::set_bypass($on);
}
