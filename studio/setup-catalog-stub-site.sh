#!/usr/bin/env bash
#
# Studio / Local: install definitions stub + U7/U10 smoke event (catalogReady true).
#
# Usage:
#   export WP_PATH="/Users/you/Studio/your-site"
#   ./studio/setup-catalog-stub-site.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

if [[ -z "${WP_PATH:-}" ]]; then
	echo "FAIL: Set WP_PATH to the WordPress root (Studio site folder)." >&2
	exit 1
fi

if ! command -v wp >/dev/null 2>&1; then
	echo "FAIL: wp (WP-CLI) not found." >&2
	exit 1
fi

wp_cmd() {
	wp --path="${WP_PATH}" "$@"
}

if ! wp_cmd core is-installed >/dev/null 2>&1; then
	echo "FAIL: WordPress not reachable at WP_PATH=${WP_PATH}" >&2
	exit 1
fi

MU="${WP_PATH}/wp-content/mu-plugins"
mkdir -p "${MU}"
cp "${PLUGIN_ROOT}/studio/mu-plugin-definitions-stub.php" \
	"${MU}/s2j-wsq-studio-definitions-stub.php"
echo "OK: definitions stub → ${MU}/s2j-wsq-studio-definitions-stub.php"

EVENT_TYPE="$(wp_cmd post list --post_type=gatherpress_event --field=post_type --posts_per_page=1 2>/dev/null | head -1)"
if [[ -z "${EVENT_TYPE}" ]]; then
	EVENT_TYPE="gatherpress_event"
fi

U7_ID="$(wp_cmd post list --post_type="${EVENT_TYPE}" --title="S2J Source QR U7 smoke" --field=ID --format=ids 2>/dev/null | awk '{print $1}')"
if [[ -z "${U7_ID}" ]]; then
	U7_ID="$(wp_cmd post create \
		--post_type="${EVENT_TYPE}" \
		--post_status=draft \
		--post_title="S2J Source QR U7 smoke" \
		--post_excerpt="Studio stub catalog — U6–U7 / U10 / E4 Save draft" \
		--porcelain)"
fi

wp_cmd post meta update "${U7_ID}" _s2j_webinar_id 'studio-u7' >/dev/null
wp_cmd post meta update "${U7_ID}" _s2j_webinar_provider 'zoom' >/dev/null

DOC='{"status":"draft","provider":"zoom","channels":[{"id":"web","label":"Web","registration_url":"https://example.com/u7-web","qr_attachment_id":0},{"id":"sns","label":"SNS","registration_url":"","qr_attachment_id":0}],"style":{"icon_attachment_id":0,"foreground_hex":"#0c833c","size_px":512},"updated_at":"'"$(date -u +%Y-%m-%dT%H:%M:%S+00:00)"'"}'
wp_cmd post meta update "${U7_ID}" _s2j_webinar_source_qr "${DOC}" --format=json >/dev/null

wp_cmd eval "wp_set_current_user(1); \$r=rest_do_request(new WP_REST_Request('GET','/s2j-webinar/v1/events/${U7_ID}/tracking-source-definitions')); echo 'definitions GET '.\$r->get_status().PHP_EOL;" 2>/dev/null | grep -E '^definitions' || true

echo "OK: U7 smoke event post ID=${U7_ID}"
echo "Edit: ${WP_PATH} (browser) → post.php?post=${U7_ID}&action=edit"
