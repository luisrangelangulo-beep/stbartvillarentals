# Lessons learned — defects found across the live portfolio

Each item below was a real bug found on a shipped site and fixed here so the next
site inherits the fix rather than the bug. Read this before "simplifying" any of
the code it describes.

---

## 1. Related properties: rotate, never `rand`, never `date`

**File:** `inc/property/data.php` — `lvc_rotate_pick()`

`orderby => 'date'` makes every property in an area link to the same newest few.
Measured across the portfolio before the fix:

| Site | Properties | Ever received a related link | Received none | Worst hoarder |
|---|---|---|---|---|
| Republic | 67 | 29 | **38** | 15 |
| Tulum | 105 | 34 | **71** | 20 |
| Los Cabos | 69 | 21 | **48** | **39** |
| Residencias Reef | 150 | 42 | **108** | **108** |

`orderby => 'rand'` avoids the hoarding but returns a different set on every
request, so Googlebot sees inconsistent internal links between crawls and the set
is not cache-stable.

**Rotating by post ID** is deterministic — same property, same set, cache-stable —
while each property starts at a different offset. After the fix Republic went
29 → 60 receiving links, Tulum 34 → 105, Los Cabos 21 → 59, Residencias 42 → 113.

## 2. Never derive a card image from gallery position

**Files:** `inc/sync/rest-sync.php`, `inc/helpers.php` — `lvc_property_image()`

The sync used to fall back to the **first gallery URL** when no hero was supplied,
and write it into the image the cards read. Photo 01 of a property folder is an
arbitrary frame — very often a bathroom or interior. Result before the fix:
**59 of 69** Los Cabos properties and **43 of 105** Tulum properties had photo 01
as their card image, and on Los Cabos hero and card were the identical shot on
all 69.

Rules now baked in:
- The sync writes `hero_image` **only** from an explicit synced value.
- The sync **never** writes `feature_image` — that is curated in the admin and
  must not be overwritten.
- `lvc_property_image()` has **no gallery fallback**. A property with no curated
  image returns `''` and callers render their imageless variant.

## 3. Gallery fields are comma-separated as often as newline-separated

Parse with `/[\r\n,]+/`, never `/\r\n|\r|\n/`. Republic stored galleries
comma-separated on **65 of 67** properties; a newline-only split resolved every
gallery to a single URL, so villas rendered 1 photo where the data held 6 squares
and 30 slider images.

Do **not** apply the comma split to prose list fields (`included_items`,
`on_request_items`, `points_of_interest`) — a comma is legitimate content there.

## 4. Do not scope a global reset to bare `article`

A `#content,.site-content,main,article{padding:0!important;background:transparent!important}`
reset intended for Hello Elementor's layout wrappers also matched card markup
built with `<article>`, collapsing every card on the site. Scope to
`article.hentry` — only WordPress-generated post wrappers carry that class.

## 5. Full-bleed belongs on browse pages, not the detail column

Landing, collection, area and archive wrappers can run edge to edge. The property
**detail** page keeps a capped content column (Los Cabos uses 1420px) — widening
it stretches the gallery rail so it shows nearly two full frames instead of the
intended one-and-a-peek. Readability caps (article body, prose, hero inner) stay
regardless.

## 6. Guard function declarations, not the file

If a file may load twice, wrap each declaration in `function_exists()`. A
file-level early return does **not** work: PHP hoists unconditional top-level
function declarations at compile time, so the guard sees its own functions and
returns before `add_action()` runs — silently unregistering REST routes while
looking correct.

## 7. Never publish a count you have to maintain by hand

"102 villas" was stale in 8 fields; "sixteen communities" was stale in 6 more.
Render counts from the database (see `page-templates/events.php`) or word the copy
so it cannot drift.

## 8. Verify rendered output by element, not by class name

These templates inline their CSS, so a class name appears in the stylesheet
*before* it appears in the markup. Searching rendered HTML for `.foo` matches the
CSS rule and reports "not rendered" for a section that is present. Match the
element form — `<section class="foo"` — instead.

## 9. Operational

- Deploy workflows should generate `build-id.txt` (sha/ref/run/time) at build
  time, never hand-maintain it, and run a **post-deploy smoke test** over real
  URLs. Discover a property URL via `wp-json/wp/v2/<cpt>?_fields=slug` — **not**
  `_fields=link`, whose JSON-escaped slashes curl cannot resolve.
- Live code belongs in the repo. Anything in `wp-content/novamira-sandbox/` is
  unversioned, invisible to deploys, and outside review.

## 10. A `wp_robots` filter does nothing on a Rank Math site

Rank Math emits its own robots tag and does not read `wp_robots`, so any guard
hooked only there silently never applies. Punta Mita's thin-term and
query-string rules were duplicated across `wp_robots` **and**
`rank_math/frontend/robots` for exactly this reason, and the core's original
thin-term guard — `wp_robots` only — was inert on every site that shipped with
Rank Math.

`lvc_term_should_noindex()` in `inc/seo/schema.php` is now the single predicate
both filters call. Add new robots rules there, never in one filter body, or the
two paths drift apart.

## 11. Indexability flags need a floor, and must never be write-only

Two failures from the same field, both live on Punta Mita:

- The per-collection index toggle defaulted to **off** while the filter reading
  it treated *unset* as **on**. The halves disagreed, so saving a collection in
  the admin silently noindexed it — a 127-property archive, the largest page on
  the site, sat hidden with its focus keyword set and unused.
- Two sibling toggles on other taxonomies were registered in ACF and carried in
  the sync allowlist but **read by no code at all**. Flipping them looked like
  it worked and did nothing.

So: default the switch to visible, pair it with a hard `min_index_count` floor
so "visible by default" cannot leak a thin page, and never ship a field the
render path does not read. If you add a toggle, wire it in the same commit.

## 12. Register term fields under the name the templates actually read

The core registered term fields as `intro` / `hero_image_url` while every
consumer read `area_intro` / `{$tax}_hero_image_url`. Nothing errored — ACF
returned null, the template fell through to its default, and term heroes and
intros simply never appeared. Field names are unprefixed; read them with
`lvc_field( 'intro', 'term_' . $id )`. Grep the consumers whenever you add a
field, and confirm a populated value renders — a silent fallback looks identical
to "no content yet".

## 13. A ranking page with no inbound links cannot rank

Punta Mita's `/bedrooms/` archives were built, given sensible thin-term
exclusions, and added to the sitemap — then left with zero internal links from
anywhere on the site. Taxonomy archives are the pages a destination site ranks
with, and a hand-built WP menu never keeps pace with them. `nav_mega` in
`theme-config.php` drives the header panel off the taxonomies themselves, so a
new term is linked from every page the moment it exists. Do not link terms you
deliberately noindex (the `range` key exists for this) — that spends crawl on
pages you have told Google to skip.
