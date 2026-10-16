#!/usr/bin/env bash
#
# Run PHPUnit locally without wp-env/Docker.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

export WP_TESTS_DIR="${WP_TESTS_DIR:-${PLUGIN_DIR}/tests/.wordpress-tests-lib}"
export WP_CORE_DIR="${WP_CORE_DIR:-${PLUGIN_DIR}/tests/.wordpress}"

if [ -f "${PLUGIN_DIR}/.env.tests" ]; then
	# shellcheck disable=SC1091
	set -a
	source "${PLUGIN_DIR}/.env.tests"
	set +a
fi

DB_NAME="${WP_TESTS_DB_NAME:-wordpress_test}"
DB_USER="${WP_TESTS_DB_USER:-root}"
DB_PASSWORD="${WP_TESTS_DB_PASSWORD:-root}"
DB_HOST="${WP_TESTS_DB_HOST:-localhost}"
WP_VERSION="${WP_TESTS_WP_VERSION:-latest}"
SKIP_DB_CREATE="${WP_TESTS_SKIP_DB_CREATE:-false}"

if [ "${DB_NAME}" = "local" ]; then
	echo "Refusing WP_TESTS_DB_NAME=local (Local site database)." >&2
	echo "Use a separate test database, e.g. wordpress_test (see .env.tests.example)." >&2
	exit 1
fi

cd "${PLUGIN_DIR}"

echo "Running unit tests (no WordPress DB)…"
vendor/bin/phpunit -c phpunit.unit.xml.dist "$@"

if [ ! -f "${WP_TESTS_DIR}/includes/functions.php" ]; then
	echo ""
	echo "WordPress test library not found. Installing (MySQL + svn once)…"
	bash "${SCRIPT_DIR}/install-wp-tests.sh" \
		"${DB_NAME}" "${DB_USER}" "${DB_PASSWORD}" "${DB_HOST}" "${WP_VERSION}" "${SKIP_DB_CREATE}"
fi

prepare_integration_plugins() {
	local PLUGINS_DIR="${WP_CORE_DIR}/wp-content/plugins"
	mkdir -p "${PLUGINS_DIR}"

	ln -sfn "${PLUGIN_DIR}" "${PLUGINS_DIR}/s2j-webinar-source-qr"
	ln -sfn "${PLUGIN_DIR}/tests/fixtures/s2j-webinar" "${PLUGINS_DIR}/s2j-webinar"

	local GP_MAIN="${PLUGINS_DIR}/gatherpress/gatherpress.php"
	if [ ! -f "${GP_MAIN}" ]; then
		echo "Downloading GatherPress for integration tests…"
		local TMP
		TMP="$(mktemp -d)"
		curl -sL "https://downloads.wordpress.org/plugin/gatherpress.zip" -o "${TMP}/gatherpress.zip"
		unzip -q "${TMP}/gatherpress.zip" -d "${PLUGINS_DIR}"
		rm -rf "${TMP}"
	fi
}

prepare_integration_plugins

echo ""
echo "Running integration tests (WordPress test suite + MySQL)…"
vendor/bin/phpunit -c phpunit.xml.dist "$@"
