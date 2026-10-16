<?php

/**
 * Block editor script mount (GatherPress event Fill).
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enqueues Gutenberg panel assets on event post types.
 */
class S2J_Webinar_Source_Qr_Gutenberg_Mount
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', array( $this, 'enqueue' ));
    }

    /**
     * Enqueue when editing a GatherPress event.
     */
    public function enqueue()
    {
        if (! function_exists('get_post_types_by_support')) {
            return;
        }

        $post_type = get_post_type();
        if (! $post_type || ! post_type_supports($post_type, 'gatherpress-event-date')) {
            return;
        }

        $script = S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR . 'dist/js/s2j-webinar-source-qr-gutenberg.js';
        $style  = S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR . 'dist/css/s2j-webinar-source-qr-gutenberg.css';

        if (! file_exists($script)) {
            return;
        }

        wp_enqueue_script(
            's2j-webinar-source-qr-gutenberg',
            S2J_WEBINAR_SOURCE_QR_PLUGIN_URL . 'dist/js/s2j-webinar-source-qr-gutenberg.js',
            array(
                'wp-plugins',
                'wp-edit-post',
                'wp-editor',
                'wp-element',
                'wp-components',
                'wp-i18n',
                'wp-data',
                'wp-api-fetch',
            ),
            S2J_WEBINAR_SOURCE_QR_VERSION,
            true
        );

        if (file_exists($style)) {
            wp_enqueue_style(
                's2j-webinar-source-qr-gutenberg',
                S2J_WEBINAR_SOURCE_QR_PLUGIN_URL . 'dist/css/s2j-webinar-source-qr-gutenberg.css',
                array( 'wp-components' ),
                S2J_WEBINAR_SOURCE_QR_VERSION
            );
        }

        wp_set_script_translations(
            's2j-webinar-source-qr-gutenberg',
            's2j-webinar-source-qr',
            S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR . 'languages'
        );

        $webinar_ns = S2J_Webinar_Source_Qr_Constants::WEBINAR_REST_NAMESPACE;

        wp_localize_script(
            's2j-webinar-source-qr-gutenberg',
            's2jWebinarSourceQrData',
            array(
                'restNamespace'                  => S2J_Webinar_Source_Qr_Constants::REST_NAMESPACE,
                'metaKey'                        => S2J_Webinar_Source_Qr_Constants::META_KEY,
                's2jWebinarActive'               => s2j_wsq_is_webinar_plugin_active(),
                'webinarDefinitionsPathTemplate' => '/' . $webinar_ns . '/events/%d/tracking-source-definitions',
                'webinarTrackingPathTemplate'    => '/' . $webinar_ns . '/events/%d/tracking-sources',
            )
        );
    }
}
