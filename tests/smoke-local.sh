#!/usr/bin/env bash
#
# Stage-1 smoke test for Local (or any WP-CLI site). Run from Local Site Shell
# when possible so DB credentials match. Optional: export WP_PATH to WordPress root.
#
# Usage:
#   export WP_PATH="/Users/you/Local Sites/yoursite/app/public"
#   ./tests/smoke-local.sh
#
set -euo pipefail

PLUGIN_SLUG="s2j-webinar-source-qr"
PLUGIN_ENTRY="${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
GUTENBERG_JS="${PLUGIN_ROOT}/dist/js/s2j-webinar-source-qr-gutenberg.js"

# macOS bash 3.2 + `set -u`: empty "${WP_ARGS[@]}" is an error; use a wrapper instead.
wp_cmd() {
	if [[ -n "${WP_PATH:-}" ]]; then
		wp --path="${WP_PATH}" "$@"
	else
		wp "$@"
	fi
}

if ! command -v wp >/dev/null 2>&1; then
	echo "FAIL: wp (WP-CLI) not found. Use Local → Site Shell." >&2
	exit 1
fi

if ! wp_cmd core is-installed >/dev/null 2>&1; then
	echo "FAIL: WordPress not reachable. Set WP_PATH to the site public directory (Local Site Shell sets cwd)." >&2
	exit 1
fi

if ! wp_cmd plugin is-installed "${PLUGIN_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: Plugin ${PLUGIN_SLUG} is not installed under wp-content/plugins." >&2
	exit 1
fi

wp_cmd plugin activate "${PLUGIN_SLUG}" >/dev/null 2>&1 || true

if ! wp_cmd plugin is-active "${PLUGIN_SLUG}" >/dev/null 2>&1; then
	echo "FAIL: Plugin could not be activated." >&2
	exit 1
fi

if [[ ! -f "${GUTENBERG_JS}" ]]; then
	echo "WARN: ${GUTENBERG_JS} missing. Run npm run build:dev:gutenberg or npm run dist:build." >&2
fi

wp_cmd eval "
if ( ! defined( 'S2J_WEBINAR_SOURCE_QR_VERSION' ) ) {
	exit( 1 );
}
do_action( 'rest_api_init' );
\$route = '/' . S2J_Webinar_Source_Qr_Constants::REST_NAMESPACE . '/events/(?P<post_id>[\\d]+)/document';
\$routes = rest_get_server()->get_routes();
if ( ! isset( \$routes[ \$route ] ) ) {
	exit( 2 );
}
if ( ! function_exists( 's2j_wsq_merge_document' ) ) {
	exit( 3 );
}
echo 'OK: REST document route registered.' . PHP_EOL;
"

echo "PASS: Stage-1 smoke (plugin active, REST route, dist check)."
