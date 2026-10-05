#!/usr/bin/env bash
#
# Install the pinned plugins listed in plugins.txt using wp-cli.
# Idempotent: re-running only reinstalls when the version differs.
#
# Usage: ./scripts/install-plugins.sh
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

if [ -f .env ]; then
	set -a
	# shellcheck disable=SC1091
	source .env
	set +a
fi

wp() {
	"${DC[@]}" run --rm wpcli wp "$@"
}

if ! wp core is-installed >/dev/null 2>&1; then
	echo "WordPress is not installed yet — run scripts/init-wordpress.sh first." >&2
	exit 1
fi

while read -r slug version _; do
	[ -z "${slug:-}" ] && continue
	case "$slug" in \#*) continue ;; esac
	[ -z "${version:-}" ] && continue

	current="$(wp plugin get "$slug" --field=version 2>/dev/null | tail -1 || true)"
	if [ "$current" = "$version" ] && wp plugin is-installed "$slug" >/dev/null 2>&1; then
		echo "✓ ${slug} ${version} already installed"
	else
		echo "→ Installing ${slug} ${version}…"
		wp plugin install "$slug" --version="$version" --activate --force
	fi

	wp plugin activate "$slug" >/dev/null 2>&1 || true
done < plugins.txt

echo "Plugins ready."
