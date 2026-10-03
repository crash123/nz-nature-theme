# NZ Nature — personal WordPress blog

A personal blog built on the latest WordPress, with a custom full-site-editing
(block) theme inspired by Aotearoa New Zealand: pounamu forests, alpine peaks,
kōwhai blossoms and the southern coast.

- **WordPress** 7.1.2 (PHP 8.3)
- **Database** MariaDB 11
- **Theme** `nz-nature` — a block/FSE theme with generated SVG art
- **Runs** locally with Docker Compose, deployed to Unraid with Docker Compose Manager

---

## Requirements

- Docker with the Compose plugin (`docker compose`)
- For deployment: an Unraid server with the **Docker Compose Manager** plugin
- For the optional seed content: nothing extra — `wp-cli` runs in a container

---

## Project structure

```
personal_blog/
├── docker-compose.yml         # local development stack
├── unraid/docker-compose.yml  # Unraid / Compose Manager stack
├── .env.example               # copy to .env and set secrets
├── scripts/init-wordpress.sh  # install WP + seed sample content
├── data/                      # local WP core + DB (gitignored)
└── theme/nz-nature/           # the custom block theme
    ├── theme.json             # design tokens (colour, type, spacing)
    ├── templates/             # FSE templates (index, single, front-page…)
    ├── parts/                 # header + footer
    ├── patterns/              # reusable block patterns
    └── assets/                # theme.css + generated SVGs + screenshot
```

---

## Quick start (local)

```bash
cp .env.example .env          # then edit the passwords
./scripts/init-wordpress.sh
```

That starts MariaDB + WordPress, installs WordPress, activates the theme and
creates a few sample posts. Then open:

- Site: <http://localhost:8080>
- Admin: <http://localhost:8080/wp-admin/>

Manual equivalent:

```bash
docker compose up -d
docker compose --profile tools run --rm wpcli theme activate nz-nature
```

Stop / reset:

```bash
docker compose down            # stop (keeps data)
docker compose down -v         # stop and remove containers/networks
rm -rf data                    # wipe WordPress + database (destructive)
```

---

## Working on the theme

The theme source lives in `theme/nz-nature/` and is bind-mounted straight into
the container, so edits appear immediately.

| File / folder        | Purpose                                              |
| -------------------- | ---------------------------------------------------- |
| `theme.json`         | Colours, gradients, fonts, spacing, block styles      |
| `assets/css/theme.css` | Layout, hero/motif art, block styles                |
| `templates/`         | Page templates edited in **Appearance → Editor**      |
| `parts/`             | Header and footer                                     |
| `patterns/`          | Insert from the pattern inserter ("NZ Nature" category) |
| `assets/images/`     | Generated SVGs: mountains, fern, koru, southern cross |

The SVG art is generated procedurally (no third-party photos), so there are no
licensing concerns. Swap in your own photography with **Media → Add New** and
set featured images on posts.

Regenerate `screenshot.png` if the design changes (requires `rsvg-convert`):

```bash
rsvg-convert -w 1200 -h 900 screenshot.svg -o theme/nz-nature/screenshot.png
```

---

## Deploy to Unraid (Docker Compose Manager)

1. **Install the plugin**: Unraid → Community Applications → *Docker Compose Manager*.
2. **Clone the repo onto the array** so the theme mount resolves:

   ```bash
   git clone git@github.com:crash123/nz-nature-theme.git \
     /mnt/user/appdata/nz-nature-theme/repo
   ```

3. **Create a stack**: in Docker Compose Manager choose *Add New Stack*, name it
   `nz-nature-theme`, and paste the contents of `unraid/docker-compose.yml`.
4. **Provide environment variables**: place a `.env` next to the stack (or use
   the plugin's env editor) based on `.env.example`. Set `WP_HOME`/`WP_SITEURL`
   to your real `https://` domain.
5. **Start the stack.** The site is then reachable on the configured port.

### Reverse proxy / TLS

If you front the site with SWAG, Nginx Proxy Manager or similar:

- Point the proxy at the Unraid host and the WordPress port (`8080` by default).
- Set `WP_HOME` / `WP_SITEURL` to the public `https://` URL.
- Uncomment the `FORCE_SSL_ADMIN` / `HTTP_X_FORWARDED_PROTO` lines in
  `unraid/docker-compose.yml`.

### Moving an existing local site

If you already have local content, dump and import it:

```bash
docker compose --profile tools run --rm wpcli db export - > backup.sql
# on Unraid, after the stack is up:
docker compose --profile tools run --rm wpcli db import - < backup.sql
docker compose --profile tools run --rm wpcli search-replace \
  'http://localhost:8080' 'https://blog.example.com' --all-tables
```

---

## Security notes

- `.env` is **gitignored** — never commit it.
- Change every password in `.env` before deploying; use long random values.
- `DISALLOW_FILE_EDIT` is enabled to block the in-dashboard theme/plugin editor.
- The Unraid stack pins `wordpress:7.1.2-php8.3-apache` and `mariadb:11`; update
  deliberately and take a database backup first.

---

## Git & GitHub

This repository (`nz-nature-theme`, private) holds both the theme and the
Docker/Unraid configuration.

```bash
git remote -v           # origin → github.com:crash123/nz-nature-theme.git
git add -A
git commit -m "Update theme"
git push
```

---

## License

Theme: GNU General Public License v2 or later.
