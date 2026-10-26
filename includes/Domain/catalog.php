<?php

/**
 * Webinar source catalog (definitions REST) helpers.
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Load plugin.php helpers when needed.
 */
function s2j_wsq_ensure_plugin_admin_api()
{
    if (! function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
}

/**
 * S2J Webinar plugin basename.
 *
 * @return string
 */
function s2j_wsq_webinar_plugin_file()
{
    return 's2j-webinar/s2j-webinar.php';
}

/**
 * Whether S2J Webinar is present in the plugins list (uploaded).
 *
 * @return bool
 */
function s2j_wsq_is_webinar_plugin_installed()
{
    if (defined('S2J_WEBINAR_VERSION')) {
        return true;
    }

    s2j_wsq_ensure_plugin_admin_api();
    $plugins = get_plugins();

    return isset($plugins[ s2j_wsq_webinar_plugin_file() ]);
}

/**
 * Whether S2J Webinar plugin is active.
 *
 * @return bool
 */
function s2j_wsq_is_webinar_plugin_active()
{
    if (defined('S2J_WEBINAR_VERSION')) {
        return true;
    }

    s2j_wsq_ensure_plugin_admin_api();

    return is_plugin_active(s2j_wsq_webinar_plugin_file());
}

/**
 * Nonce URL to activate S2J Webinar (empty when not allowed or already active).
 *
 * @return string
 */
function s2j_wsq_webinar_plugin_activate_url()
{
    if (! current_user_can('activate_plugins') || s2j_wsq_is_webinar_plugin_active()) {
        return '';
    }

    $file = s2j_wsq_webinar_plugin_file();
    s2j_wsq_ensure_plugin_admin_api();

    return wp_nonce_url(
        admin_url('plugins.php?action=activate&plugin=' . $file),
        'activate-plugin_' . $file
    );
}

/**
 * Installation guide URL (GitHub until wp.org listing).
 *
 * @return string
 */
function s2j_wsq_webinar_plugin_install_guide_url()
{
    return 'https://github.com/stein2nd/s2j-webinar#installation';
}

/**
 * Fetch source definitions for an event via Webinar REST (in-process).
 *
 * @param int $post_id Event post ID.
 * @return array<int, array{source_name: string, label: string}>|WP_Error 503 catalog_unavailable on failure.
 */
function s2j_wsq_fetch_source_definitions($post_id)
{
    if (! s2j_wsq_is_webinar_plugin_active()) {
        return new WP_Error(
            'catalog_unavailable',
            __('Source catalog is unavailable.', 's2j-webinar-source-qr'),
            array( 'status' => 503 )
        );
    }

    $path = '/' . S2J_Webinar_Source_Qr_Constants::WEBINAR_REST_NAMESPACE
        . '/events/' . (int) $post_id . '/tracking-source-definitions';
    $request  = new WP_REST_Request('GET', $path);
    $response = rest_do_request($request);

    if ($response->is_error()) {
        return new WP_Error(
            'catalog_unavailable',
            __('Source catalog is unavailable.', 's2j-webinar-source-qr'),
            array( 'status' => 503 )
        );
    }

    $data    = $response->get_data();
    $sources = isset($data['sources']) && is_array($data['sources']) ? $data['sources'] : array();

    if (count($sources) === 0) {
        return new WP_Error(
            'catalog_unavailable',
            __('Source catalog is unavailable.', 's2j-webinar-source-qr'),
            array( 'status' => 503 )
        );
    }

    $normalized = array();
    foreach ($sources as $row) {
        if (! is_array($row) || empty($row['source_name'])) {
            continue;
        }
        $normalized[] = array(
            'source_name' => (string) $row['source_name'],
            'label'       => isset($row['label']) ? (string) $row['label'] : '',
        );
    }

    if (count($normalized) === 0) {
        return new WP_Error(
            'catalog_unavailable',
            __('Source catalog is unavailable.', 's2j-webinar-source-qr'),
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
function s2j_wsq_catalog_to_channel_stubs(array $sources)
{
    $out = array();
    foreach ($sources as $row) {
        $out[] = array(
            'id'    => $row['source_name'],
            'label' => $row['label'],
        );
    }
    return $out;
}
