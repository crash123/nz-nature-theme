#!/usr/bin/env bash
#
# Set up the local NZ Nature WordPress stack:
#   1. bring up MariaDB + WordPress
#   2. install WordPress (if needed) and activate the theme
#   3. install pinned plugins
#   4. seed sample galleries, pages and a post
#
# Usage: ./scripts/init-wordpress.sh
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if docker compose version >/dev/null 2>&1; then
	DC=(docker compose)
elif command -v docker-compose >/dev/null 2>&1; then
	DC=(docker-compose)
else
	echo "Error: docker compose not found." >&2
	exit 1
fi

if [ ! -f .env ]; then
	echo "No .env found — creating one from .env.example."
	echo "Review and change the passwords in .env, then re-run if needed."
	cp .env.example .env
fi

set -a
# shellcheck disable=SC1091
source .env
set +a

URL="${WP_HOME:-http://localhost:8080}"
SITE_TITLE="${SITE_TITLE:-NZ Nature}"
ADMIN_USER="${WP_ADMIN_USER:-admin}"
ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-change-me-admin-password}"
ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.com}"

echo "Starting database and WordPress..."
"${DC[@]}" up -d db wordpress

echo "Waiting for wp-config.php to be generated..."
for _ in $(seq 1 60); do
	if "${DC[@]}" exec -T wordpress test -f /var/www/html/wp-config.php >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

wp() {
	"${DC[@]}" run --rm wpcli wp "$@"
}

echo "Waiting for the database to accept connections..."
for _ in $(seq 1 60); do
	if wp db check >/dev/null 2>&1; then
		break
	fi
	sleep 2
done

if ! wp core is-installed >/dev/null 2>&1; then
	echo "Installing WordPress at ${URL}..."
	wp core install \
		--url="$URL" \
		--title="$SITE_TITLE" \
		--admin_user="$ADMIN_USER" \
		--admin_password="$ADMIN_PASSWORD" \
		--admin_email="$ADMIN_EMAIL" \
		--skip-email
else
	echo "WordPress already installed."
fi

echo "Activating the NZ Nature theme..."
wp theme activate nz-nature
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

echo "Installing plugins..."
"$ROOT/scripts/install-plugins.sh"

echo "Seeding content..."
wp eval-file /scripts/seed.php

echo "Scanning existing media for GPS (harmless if none)..."
wp eval 'echo "GPS-tagged attachments: " . nz_nature_gps_backfill() . "\n";'

echo
echo "Done. Visit: ${URL}"
echo "Admin:  ${URL}/wp-admin/"
echo "Login:  ${ADMIN_USER} / ${ADMIN_PASSWORD}"
