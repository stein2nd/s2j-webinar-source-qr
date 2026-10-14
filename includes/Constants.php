<?php
/**
 * Plugin-wide constants.
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta key and REST namespace (docs_mod/data_dictionary.md).
 */
final class S2J_Webinar_Source_Qr_Constants {

	const META_KEY = '_s2j_webinar_source_qr';

	const REST_NAMESPACE = 's2j-webinar-source-qr/v1';

	const WEBINAR_REST_NAMESPACE = 's2j-webinar/v1';
}
