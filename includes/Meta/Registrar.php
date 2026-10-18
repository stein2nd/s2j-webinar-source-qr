<?php

/**
 * Register event post meta.
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers `_s2j_webinar_source_qr` on GatherPress event post types.
 */
class S2J_Webinar_Source_Qr_Meta_Registrar
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('init', array( $this, 'register_meta' ), 11);
    }

    /**
     * Register meta for each post type that supports event dates.
     */
    public function register_meta()
    {
        if (! function_exists('get_post_types_by_support')) {
            return;
        }

        $post_types = get_post_types_by_support('gatherpress-event-date');
        if (empty($post_types)) {
            return;
        }

        foreach ($post_types as $post_type) {
            register_post_meta(
                $post_type,
                S2J_Webinar_Source_Qr_Constants::META_KEY,
                array(
                    'single'            => true,
                    'type'              => 'object',
                    'show_in_rest'      => array(
                        'schema' => array(
                            'type'                 => 'object',
                            'additionalProperties' => true,
                        ),
                    ),
                    'auth_callback'     => array( $this, 'auth_callback' ),
                    'sanitize_callback' => array( $this, 'sanitize_callback' ),
                )
            );
        }
    }

    /**
     * Meta auth: edit_post on the object.
     *
     * @param bool   $allowed   Allowed flag (unused; required by WP).
     * @param string $meta_key  Meta key (unused; required by WP).
     * @param int    $object_id Post ID.
     * @param int    $user_id   User ID.
     * @return bool
     */
    public function auth_callback($allowed, $meta_key, $object_id, $user_id)
    {
        // Parameters $allowed and $meta_key are required by register_post_meta.
        return user_can((int) $user_id, 'edit_post', (int) $object_id);
    }

    /**
     * REST/meta sanitize: shape merge only; catalog + draft rules on meta guard and REST draft/apply.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    public function sanitize_callback($value)
    {
        return s2j_wsq_merge_document($value);
    }
}
