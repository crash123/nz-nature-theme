#!/usr/bin/env bash
#
# Initialise the local NZ Nature WordPress stack:
#   1. bring up MariaDB + WordPress
#   2. install WordPress (if not already installed)
#   3. activate the NZ Nature theme
#   4. create sample content and menus
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

echo "Configuring site..."
wp option update blogdescription "Field notes from Aotearoa New Zealand"
wp option update timezone_string "Pacific/Auckland"
wp option update blogpublic 1 >/dev/null 2>&1 || true
wp rewrite structure '/%postname%/' --hard >/dev/null 2>&1 || true

echo "Activating the NZ Nature theme..."
wp theme activate nz-nature

if ! wp post list --post_type=post --name="first-light-on-the-tongariro-alpine-crossing" --format=ids 2>/dev/null | grep -q '[0-9]'; then
	echo "Creating sample content..."

	wp post create --post_type=post --post_status=publish \
		--post_title="First light on the Tongariro Alpine Crossing" \
		--post_content='The alarm went off at 4:30am in a frosty car park. By the time the sun cleared Ngauruhoe, the whole plateau was glowing. Three volcanoes, one endless sky, and the quiet crunch of scoria underfoot.' \
		--post_excerpt="An early start for the best day walk in the North Island."

	wp post create --post_type=post --post_status=publish \
		--post_title="Rain and light in Fiordland" \
		--post_content='They say Fiordland gets seven metres of rain a year, and it shows. Between the downpours the valleys filled with mist and the waterfalls multiplied. Some places are not meant to be seen in the sun.' \
		--post_excerpt="Chasing waterfalls and silence in the deep south."

	wp post create --post_type=page --post_status=publish \
		--post_title="About" \
		--post_content='Kia ora. This is a personal blog about the wild places of Aotearoa New Zealand — tramping, photography, and the lessons the whenua teaches. Written from the bottom of the world.'

	wp post create --post_type=page --post_status=publish \
		--post_title="Contact" \
		--post_content='Ngā mihi for stopping by. You can reach me at hello@example.com.'
fi

if ! wp menu list --format=ids 2>/dev/null | grep -q '[0-9]'; then
	MENU_ID="$(wp menu create "Primary" --porcelain)"
	wp menu location assign "$MENU_ID" primary >/dev/null 2>&1 || true
fi

echo
echo "Done. Visit: ${URL}"
echo "Admin:  ${URL}/wp-admin/"
echo "Login:  ${ADMIN_USER} / ${ADMIN_PASSWORD}"
