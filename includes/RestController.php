<?php

/**
 * REST facade for document load / draft / apply.
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST adapter (docs/rest_api_spec.md).
 */
class S2J_Webinar_Source_Qr_Rest_Controller
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('rest_api_init', array( $this, 'register_routes' ));
    }

    /**
     * Register REST routes.
     */
    public function register_routes()
    {
        $ns = S2J_Webinar_Source_Qr_Constants::REST_NAMESPACE;

        register_rest_route(
            $ns,
            '/events/(?P<post_id>[\d]+)/document',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_document' ),
                    'permission_callback' => array( $this, 'authorize_event' ),
                    'args'                => $this->post_id_args(),
                ),
            )
        );

        register_rest_route(
            $ns,
            '/events/(?P<post_id>[\d]+)/document/draft',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'save_draft' ),
                    'permission_callback' => array( $this, 'authorize_event' ),
                    'args'                => $this->post_id_args(),
                ),
            )
        );

        register_rest_route(
            $ns,
            '/events/(?P<post_id>[\d]+)/document/apply',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'apply_document' ),
                    'permission_callback' => array( $this, 'authorize_event' ),
                    'args'                => $this->post_id_args(),
                ),
            )
        );

        register_rest_route(
            $ns,
            '/events/(?P<post_id>[\d]+)/document/preview',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'preview_document' ),
                    'permission_callback' => array( $this, 'authorize_event' ),
                    'args'                => $this->post_id_args(),
                ),
            )
        );
    }

    /**
     * Route args for the event post ID parameter.
     *
     * @return array<string, array<string, mixed>>
     */
    private function post_id_args()
    {
        return array(
            'post_id' => array(
                'required'          => true,
                'type'              => 'integer',
                'validate_callback' => function ($value) {
                    return is_numeric($value) && (int) $value > 0;
                },
            ),
        );
    }

    /**
     * REST auth + event post type check.
     *
     * @param WP_REST_Request $request Request.
     * @return true|WP_Error
     */
    public function authorize_event($request)
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (! $nonce || ! wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'invalid_nonce',
                __('Security check failed.', 's2j-webinar-source-qr'),
                array( 'status' => 403 )
            );
        }

        $post_id = (int) $request['post_id'];
        $post    = get_post($post_id);
        if (! $post) {
            return new WP_Error(
                'rest_post_invalid_id',
                __('Invalid post ID.', 's2j-webinar-source-qr'),
                array( 'status' => 404 )
            );
        }

        if (! post_type_supports($post->post_type, 'gatherpress-event-date')) {
            return new WP_Error(
                'rest_invalid_post_type',
                __('Post is not a GatherPress event.', 's2j-webinar-source-qr'),
                array( 'status' => 400 )
            );
        }

        if (! current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'rest_forbidden',
                __('You cannot edit this event.', 's2j-webinar-source-qr'),
                array( 'status' => 403 )
            );
        }

        return true;
    }

    /**
     * GET document — meta merge + resolved provider only.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function get_document($request)
    {
        $post_id = (int) $request['post_id'];
        $stored  = get_post_meta($post_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true);
        $doc     = s2j_wsq_merge_document($stored);
        $doc['provider'] = s2j_wsq_resolve_provider($post_id, $doc);

        return new WP_REST_Response($doc, 200);
    }

    /**
     * POST draft save.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function save_draft($request)
    {
        return $this->persist_document($request, 'draft');
    }

    /**
     * POST apply (step 4) — QR PNG per channel, status ready.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function apply_document($request)
    {
        return $this->persist_document($request, 'apply');
    }

    /**
     * POST preview — PNG as JSON data_url (same renderer as apply; no meta write).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public function preview_document($request)
    {
        $body             = $request->get_json_params();
        $registration_url = isset($body['registration_url']) ? (string) $body['registration_url'] : '';
        $style            = isset($body['style']) && is_array($body['style']) ? $body['style'] : array();

        $result = s2j_wsq_preview_qr_data_url($registration_url, $style);
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    /**
     * Shared draft/apply persistence with catalog guard.
     *
     * @param WP_REST_Request $request Request.
     * @param string          $mode    draft|apply.
     * @return WP_REST_Response|WP_Error
     */
    private function persist_document($request, $mode)
    {
        $post_id  = (int) $request['post_id'];
        $body     = $request->get_json_params();
        $previous = get_post_meta($post_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true);
        $previous = is_array($previous) ? $previous : null;

        $prepared = s2j_wsq_prepare_document_for_draft_storage($post_id, $body, $previous);

        if (empty($prepared['ok'])) {
            $error = $prepared['error'];
            if (is_wp_error($error)) {
                return $error;
            }
            return new WP_Error(
                $error['code'],
                $error['message'],
                array( 'status' => 400 )
            );
        }

        $document = $prepared['document'];

        if ('apply' === $mode) {
            if (! s2j_wsq_all_channels_have_url($document)) {
                return new WP_Error(
                    'invalid_registration_url',
                    __('One or more registration URLs are invalid.', 's2j-webinar-source-qr'),
                    array( 'status' => 400 )
                );
            }

            $applied = s2j_wsq_apply_document_with_qr($post_id, $document);
            if (is_wp_error($applied)) {
                return $applied;
            }

            $document             = $applied;
            $document['provider'] = s2j_wsq_resolve_provider($post_id, $document);
        }

        s2j_wsq_meta_guard_set_bypass(true);
        $updated = update_post_meta($post_id, S2J_Webinar_Source_Qr_Constants::META_KEY, $document);
        s2j_wsq_meta_guard_set_bypass(false);

        if (false === $updated) {
            $current = get_post_meta($post_id, S2J_Webinar_Source_Qr_Constants::META_KEY, true);
            if ($current !== $document) {
                return new WP_Error(
                    'meta_update_failed',
                    __('Could not save the document.', 's2j-webinar-source-qr'),
                    array( 'status' => 500 )
                );
            }
        }

        return new WP_REST_Response($document, 200);
    }
}
