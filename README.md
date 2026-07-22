# St Barts Villa Rentals — child theme

Villa-rental site for **stbartvillarentals.com**, built on
[`luxury-villa-theme-core`](https://github.com/luisrangelangulo-beep/luxury-villa-theme-core).

This repo previously held a "Christian Clinic / Lighthouse Network" starter
theme. That has been removed — the git history still has it (through commit
`6096c87`) if anything needs recovering.

## Status

**Skeleton stage.** Structure, query logic, schema and inquiry handling are in
place. No styling yet (`assets/brand.css` is deliberately absent), and no villa
content exists on the site.

## ⚠️ The domain is currently serving a different project

`stbartvillarentals.com` runs the **Lighthouse Network** site — 15 published
pages of Christian mental-health and addiction-support content, with its own
front page and 6 categories, on the same WordPress install.

It is `blog_public = 0`, so Google cannot see it, but it is real work and it is
still live on that host.

**Deploying this theme is safe** — it lands in its own directory beside the
active `hello-elementor` parent and renders nothing until activated.
**Activating it replaces that site's front end.** Do not activate until the
clinic project has been moved or retired, as an explicit decision.

## Greenfield, unlike Anguilla

There is no `villa` CPT, no CPT UI plugin, and no villa taxonomies on this
install — so `register_cpt` and `register_taxonomies` are both **`true`** and
this theme owns the registration. There are no existing URLs to preserve and no
rankings at risk.

| | |
|---|---|
| Villas | 0 |
| Villa URL (once live) | `/luxury-villas/{slug}/` |
| Taxonomies | `area`, `collection`, `bedrooms`, `beach_access`, `amenity` — registered by this theme |
| Region | St Barthélemy (single island, so `area` carries the geography — no `destination` taxonomy) |
| Stack | Hello Elementor + Elementor Pro, ACF Pro, Rank Math (free), WP Rocket, Redis |

## Before launch

1. **Decide what happens to the Lighthouse Network site** (see above). This
   gates everything else.
2. **Site settings.** Permalinks are currently `/%year%/%monthnum%/%day%/%postname%/`
   — set to `/%postname%/`. Keep `blog_public = 0` until launch.
3. **Create the pages** named in `theme-config.php` (`contact`, `villa-request`,
   `about`, `how-it-works`, `list-your-villa`, `magazine`) and assign the
   matching templates from `page-templates/`. None of them exist yet, so the nav
   links 404 until they do.
4. **Inquiry routing.** `support_email` is a placeholder pointing at the
   rmoceanfrontrentals domain. Set a real St Barts mailbox and confirm MX
   records exist — a no-MX domain bounces inquiries silently.
5. **WhatsApp** is intentionally empty; the button hides itself. Do not borrow
   another brand's number.
6. **Rank Math** has no knowledge-graph name set (`My Blog`). Configure it, and
   note this install has Rank Math free, not Pro, unlike Anguilla.
7. **Content.** Villas, areas and collections all need importing.

## Deploy

`.github/workflows/deploy-theme.yml`, manual trigger only. Targets
`/home/stbartvillarenta/public_html/wp-content/themes/st-barts-villa-rentals`
and refuses any other path, or any path resolving to the active parent theme.
Syncs with `--delete`. Rewrite flushing is gated on the child theme actually
being active.

Repository secrets `SFTP_HOST`, `SFTP_USER`, `SFTP_PORT` and `SFTP_PRIVATE_KEY`
are already set (from the previous project, same cPanel account —
`stbartvillarenta` @ `50.6.226.162`). `SFTP_PASSPHRASE` is not set, which is
fine if the key has no passphrase.

The smoke test only asserts the homepage until a villa CPT and content exist —
before that, the villa URLs legitimately do not resolve.

## Docs

- `docs/TOKEN_CONTRACT.md` — CSS variables and class hooks for `brand.css`
- `docs/NEW_SITE_SETUP.md` — configuration reference
- `docs/LESSONS_LEARNED.md` — portfolio defects; read §10–13 before touching indexation
