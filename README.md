# NZ Nature — photography blog

A personal photography gallery site built on the latest WordPress, with a
custom full-site-editing (block) theme inspired by Aotearoa New Zealand.
Masonry galleries, an EXIF/GPS lightbox and embedded photo maps — all driven
from one Git repository.

- **WordPress** 7.1.2 (PHP 8.3) · **MariaDB** 11
- **Theme** `nz-nature` — block/FSE, with generated SVG art and Leaflet maps
- **Plugin** Meow Lightbox (pinned) — in-lightbox EXIF + GPS
- **Maps** Leaflet + OpenStreetMap/TopoMap (vendored, no API key)
- **Runs** locally with Docker Compose, deploys to Unraid with Compose Manager

---

## Requirements

- Docker with the Compose plugin (`docker compose`)
- For Unraid: the **Docker Compose Manager** plugin
- Optional (only to regenerate placeholder art): `rsvg-convert`

---

## Project structure

```
personal_blog/
├── docker-compose.yml           # local development stack
├── docker/php/uploads.ini       # raises PHP upload limits for photos
├── unraid/docker-compose.yml    # Unraid / Compose Manager stack (+ wp-cli)
├── plugins.txt                  # pinned plugins
├── .env.example                 # copy to .env and set secrets
├── scripts/
│   ├── init-wordpress.sh        # install WP, theme, plugins, seed content
│   ├── install-plugins.sh       # install plugins from plugins.txt
│   ├── seed.php                 # sample galleries, pages, post
│   └── generate-placeholders.py # regenerate the placeholder "photos"
├── data/                        # local WP core + DB (gitignored)
└── theme/nz-nature/
    ├── theme.json               # design tokens
    ├── functions.php            # loads inc/
    ├── inc/                     # setup, collections, gps, meta, maps
    ├── templates/               # FSE templates
    ├── parts/ patterns/         # header, footer, block patterns
    └── assets/                  # css, js, vendored Leaflet, images, placeholders
```

---

## Quick start (local)

```bash
cp .env.example .env          # then edit the passwords
./scripts/init-wordpress.sh
```

This starts MariaDB + WordPress, installs and activates the theme, installs
Meow Lightbox, and seeds sample galleries, pages and a post.

- Site: <http://localhost:8080>
- Admin: <http://localhost:8080/wp-admin/> (credentials in `.env`)

Stop / reset:

```bash
docker compose down            # stop (keeps data)
rm -rf data                    # wipe WordPress + database (destructive)
```

---

## How content is organised

The site uses **Posts as galleries** and **Categories as collections**:

| Concept      | Implementation                                             |
| ------------ | ---------------------------------------------------------- |
| Gallery      | A Post with a featured image (cover) + a Gallery block      |
| Collection   | A Category (Travel, Landscape, Wildlife…)                   |
| Posts        | The `posts` category — written posts, kept off Galleries    |

The homepage shows **Collections** (category cards), **Latest photos**, a
**map** and a posts call-to-action. The **Galleries** posts page and category
archives use masonry cover grids.

### Adding a gallery

1. **Media → Add New** to upload your photos.
2. **Posts → Add New**: give it a title, set a **Featured image** (the cover),
   assign a **Category** (collection).
3. Insert a **Gallery** block, add your photos, and set its style to
   **Masonry** (block toolbar → Styles). Set link destination to *Media file*
   so the lightbox works.
4. Optionally set a **Gallery location** (see maps below).

### Uploading photos

- PHP limits are raised to **128 MB** per file (`docker/php/uploads.ini`).
  Behind a reverse proxy also set `client_max_body_size 140m;`.
- Recommended: export JPEGs at **2000–2560 px on the long edge**, quality ~85.
  WordPress generates the smaller sizes it needs.
- EXIF is read from **JPEG/TIFF** originals. RAW files are not supported by
  WordPress — export to JPEG first.
- WordPress downscales images above 2560 px ("big image" threshold); originals
  are kept.

---

## Maps

Photos are plotted on a Leaflet map using GPS coordinates read from each
original file (cached in attachment meta). Maps appear on:

- each gallery page (that gallery's photos),
- the homepage and the **Places** page (all photos).

Shortcode (place in a Shortcode block):

```
[nz_photo_map scope="gallery|all" height="460" tile="osm|topo"]
```

- `scope="gallery"` — the current post's photos; `scope="all"` — the whole site.
- `tile="osm"` — OpenStreetMap, `tile="topo"` — OpenTopoMap (nice for tramping).

**No GPS?** Most cameras don't record it. Use the **Gallery location** meta box
on the post (place name + `lat, lng`) as a fallback pin. Copy coordinates from
Google Maps.

Map tiles require attribution (shown on the map) and are subject to the
OpenStreetMap/OpenTopoMap usage policies — fine for a personal site.

---

## Working on the theme

| File / folder          | Purpose                                             |
| ---------------------- | --------------------------------------------------- |
| `theme.json`           | Colours, gradients, fonts, spacing, block styles    |
| `inc/setup.php`        | Supports, image sizes, block styles, posts filter    |
| `inc/collections.php`  | `[nz_collections]`, `[nz_latest_photos]` shortcodes |
| `inc/gps.php`          | GPS extraction + backfill                            |
| `inc/meta.php`         | Gallery location meta box                            |
| `inc/maps.php`         | `[nz_photo_map]` shortcode + Leaflet                |
| `assets/css/theme.css` | Layout, masonry, collections, maps                  |

After changing the theme, refresh the Site Editor (**Appearance → Editor**).
Regenerate the placeholder art with `python3 scripts/generate-placeholders.py`.

---

## Plugin management

Plugins are pinned in `plugins.txt` and installed by `scripts/install-plugins.sh`
(idempotent). They live in `wp-content/plugins` (in `data/`, not Git). To change
a version, edit `plugins.txt` and re-run the script.

---

## Deploy to Unraid (Docker Compose Manager)

1. Install **Docker Compose Manager** from Community Applications.
2. Clone the repo onto the array so the mounts resolve:

   ```bash
   git clone git@github.com:crash123/nz-nature-theme.git \
     /mnt/user/appdata/nz-nature-theme/repo
   ```

3. In Compose Manager, **Add New Stack** → `nz-nature-theme` and paste
   `unraid/docker-compose.yml`.
4. Provide a `.env` (based on `.env.example`); set `WP_HOME`/`WP_SITEURL` to
   your real `https://` domain.
5. Start the stack. Run one-off commands with the bundled helper service:

   ```bash
   docker compose --profile tools run --rm wpcli wp plugin list
   ```

**Reverse proxy / TLS:** point SWAG/NPM at the WordPress port, set the public
URL in `.env`, set `client_max_body_size 140m;`, and uncomment the `FORCE_SSL_ADMIN`
lines in `unraid/docker-compose.yml`.

**Migrating local content:** `wp db export` locally, then `wp db import` on the
server, followed by `wp search-replace 'http://localhost:8080' 'https://your.domain' --all-tables`.

---

## Security notes

- `.env` is gitignored — never commit it. Change every password before deploy.
- `DISALLOW_FILE_EDIT` blocks the in-dashboard theme/plugin editor.
- Images are pinned (`wordpress:7.1.2-php8.3-apache`, `mariadb:11`); update
  deliberately and back up the database first.

---

## Git & GitHub

Private repo: `github.com/crash123/nz-nature-theme` (theme + Docker/Unraid).

```bash
git add -A && git commit -m "Update" && git push
```

---

## License

Theme: GNU General Public License v2 or later.
Vendored Leaflet (BSD-2) and Leaflet.markercluster (MIT) — see
`theme/nz-nature/assets/vendor/leaflet/NOTICE.txt`.
