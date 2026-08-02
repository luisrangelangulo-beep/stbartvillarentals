<?php
/**
 * Anguilla Beach Luxury Villas — Generic taxonomy term archive.
 *
 * Cloned from tulumholidayvillas' taxonomy-area.php (v2.0 dark theme) per the
 * clone-don't-rebuild rule: same hero / band / grid / siblings / inquiry /
 * final-CTA sections, markup and CSS, recolored to the St Barts salt-rose palette.
 * Class prefix lvc- replaces thva-.
 *
 * Routed here by inc/template-router.php for EVERY property taxonomy term —
 * both `area` and `bedrooms` — so everything renders generically off
 * get_queried_object() and the taxonomy's own labels.
 *
 * Differences from the THV source, on purpose:
 * - Uses the MAIN query (pagination + router contract preserved), not a
 *   custom WP_Query.
 * - Single-destination site: no destination level, no within-destination
 *   parent lookup — the "part of" band points at the full villa archive.
 * - THV's Why/Experiences/Attractions/Best-time sections dropped: they read
 *   THV-only ACF fields that don't exist on this site's term field group
 *   (hero_image_url / tagline / intro / body / faq_items — see
 *   inc/property/term-fields.php).
 * - Kept from the previous Anguilla template: intro (fallback
 *   term_description), body-below-grid, FAQ section w/ 2-row minimum +
 *   FAQPage via lvc_jsonld, sibling band, lvc_schema_collection().
 * - No other inline JSON-LD — inc/seo/schema.php owns page schema.
 * - THV's inline thv_inquiry form replaced by the shared
 *   template-parts/inquiry-form part (AJAX handler + Turnstile).
 * - Breadcrumb archive link built with lvc_archive_url(), not a hardcoded path
 *   (known THV bug). That helper resolves the CPT from theme-config.php and
 *   falls back to the configured archive slug, so it survives both a rename of
 *   the post type and a registration with no archive.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// BOOTSTRAP
// ═══════════════════════════════════════════════════════════════════════════

$lvc_term = get_queried_object();
if ( ! $lvc_term instanceof WP_Term ) {
	wp_safe_redirect( home_url() );
	exit;
}

$lvc_tax  = $lvc_term->taxonomy;
$lvc_obj  = get_taxonomy( $lvc_tax );
$lvc_name = $lvc_term->name;
$lvc_key  = 'term_' . $lvc_term->term_id;

$lvc_singular = $lvc_obj ? $lvc_obj->labels->singular_name : ucfirst( $lvc_tax );
$lvc_plural   = $lvc_obj ? $lvc_obj->labels->name : ucfirst( $lvc_tax );

// ── Term ACF fields (UNPREFIXED names — see inc/property/term-fields.php) ──
$lvc_intro   = lvc_field( 'intro', $lvc_key, term_description() );
$lvc_hero    = lvc_priority_image_url( (string) lvc_field( 'hero_image_url', $lvc_key ) );
$lvc_tagline = (string) lvc_field( 'tagline', $lvc_key );
$lvc_body    = lvc_field( 'body', $lvc_key );

// FAQ: keep only complete rows; require 2+ before rendering (thin-content guard).
$lvc_faq = array();
foreach ( (array) lvc_field( 'faq_items', $lvc_key, array() ) as $lvc_row ) {
	$lvc_q = isset( $lvc_row['question'] ) ? trim( (string) $lvc_row['question'] ) : '';
	$lvc_a = isset( $lvc_row['answer'] ) ? trim( (string) $lvc_row['answer'] ) : '';
	if ( '' !== $lvc_q && '' !== $lvc_a ) {
		$lvc_faq[] = array( 'question' => $lvc_q, 'answer' => $lvc_a );
	}
}
$lvc_show_faq = ( count( $lvc_faq ) >= 2 );

// ── Hero image fallback chain: term hero → homepage hero → live villa image ──
$lvc_hero_bg = $lvc_hero;
if ( '' === $lvc_hero_bg ) {
	$lvc_hero_bg = lvc_priority_image_url( (string) lvc_field( 'home_hero_image_url', (int) get_option( 'page_on_front' ) ) );
}
if ( '' === $lvc_hero_bg ) {
	$lvc_hero_bg = ''; // No brand hero yet — sections guard on empty and fall back to a dark surface.
}

// ── H1 — per taxonomy, generic beyond that ──────────────────────────────────
if ( 'area' === $lvc_tax ) {
	$lvc_h1_html = 'Luxury Villas in <em>' . esc_html( $lvc_name ) . '</em>';
} else {
	$lvc_h1_html = '<em>' . esc_html( $lvc_name ) . '</em> in ' . esc_html( lvc_config( 'region' ) );
}

// ── Peers / siblings within the SAME taxonomy ───────────────────────────────
$lvc_peer_total = get_terms(
	array(
		'taxonomy'   => $lvc_tax,
		'hide_empty' => true,
		'fields'     => 'count',
	)
);
$lvc_peer_total = is_wp_error( $lvc_peer_total ) ? 0 : (int) $lvc_peer_total;

$lvc_siblings = get_terms(
	array(
		'taxonomy'   => $lvc_tax,
		'hide_empty' => true,
		'exclude'    => array( $lvc_term->term_id ),
		'number'     => 6,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
$lvc_siblings        = is_wp_error( $lvc_siblings ) ? array() : $lvc_siblings;
$lvc_single_sib_mode = ( 1 === count( $lvc_siblings ) );

// ── Counts / URLs ───────────────────────────────────────────────────────────
$lvc_current_page = max( 1, (int) get_query_var( 'paged' ) );
$lvc_total_villas = (int) $GLOBALS['wp_query']->found_posts;
$lvc_archive_url  = lvc_archive_url();
$lvc_whatsapp     = (string) lvc_config( 'whatsapp_url', '' );

// ═══════════════════════════════════════════════════════════════════════════
// OUTPUT — page schema (CollectionPage/ItemList) is owned by inc/seo/schema.php.
// ═══════════════════════════════════════════════════════════════════════════
if ( function_exists( 'lvc_schema_collection' ) ) {
	lvc_schema_collection();
}

get_header();
?>

<?php if ( '' !== (string) $lvc_hero_bg ) : ?>
<link rel="preload" as="image" href="<?php echo esc_url( $lvc_hero_bg ); ?>" fetchpriority="high">
<?php endif; ?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
	TERM ARCHIVE STYLES — ANGUILLA BEACH LUXURY VILLAS (cloned from THV v2.0)
	Namespace: lvc-* . St Barts palette: volcanic basalt ground, salt-rose accent.
	═══════════════════════════════════════════════════════════════════════════ */

:root {
	--lvc-bg        : #12100f;
	--lvc-bg2       : #1a1715;
	--lvc-card      : #1c1917;
	--lvc-primary   : #2a1c20;
	--lvc-primary-h : #3a262b;
	--lvc-accent    : #c2818c;
	--lvc-accent-h  : #d9a0a9;
	--lvc-text      : #f5f0ea;
	--lvc-soft      : #c3b8b0;
	--lvc-muted     : #9c918b;
	--lvc-border    : rgba(245,240,234,0.06);
	--lvc-border-h  : rgba(194,129,140,0.3);
	--lvc-shadow    : 0 4px 24px rgba(0,0,0,0.4);
	--lvc-shadow-h  : 0 8px 32px rgba(0,0,0,0.5);
	--lvc-fd        : 'Gilda Display', Georgia, serif;
	--lvc-fb        : 'Albert Sans', -apple-system, BlinkMacSystemFont, sans-serif;
	--lvc-ease      : cubic-bezier(0.25, 0.46, 0.45, 0.94);
	--lvc-px        : clamp(1.25rem, 5vw, 4rem);
}

.lvc-wrap *, .lvc-wrap *::before, .lvc-wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }
.lvc-wrap {
	background   : var(--lvc-bg);
	color        : var(--lvc-text);
	font-family  : var(--lvc-fb);
	font-weight  : 300;
	line-height  : 1.6;
	overflow-x   : hidden;
	-webkit-font-smoothing: antialiased;
}
.lvc-wrap img { max-width: 100%; height: auto; display: block; }
.lvc-wrap a   { text-decoration: none; color: inherit; }

/* ── REUSABLE UTILITIES ─────────────────────────────────────────────────── */
.lvc-eyebrow {
	display        : inline-flex;
	align-items    : center;
	gap            : 10px;
	font-size      : 0.68rem;
	letter-spacing : 0.18em;
	text-transform : uppercase;
	color          : var(--lvc-accent);
	margin-bottom  : 0.85rem;
	font-family    : var(--lvc-fb);
	font-weight    : 500;
	margin: 0 0 0.9rem; /* folded from brand.css */
}
.lvc-eyebrow::before {
	content    : '';
	width      : 18px; height: 1px;
	background : var(--lvc-accent);
	opacity    : 0.5;
	flex-shrink: 0;
	display: inline-block; /* folded from brand.css */
	margin-right: 0.7rem; /* folded from brand.css */
	vertical-align: middle; /* folded from brand.css */
}

.lvc-btn-accent {
	display         : inline-flex;
	align-items     : center;
	justify-content : center;
	gap             : 0.5rem;
	padding         : 1rem 2rem;
	background      : var(--lvc-accent);
	color           : #12100f;
	font-family     : var(--lvc-fb);
	font-size       : 0.72rem;
	font-weight     : 500;
	letter-spacing  : 0.1em;
	text-transform  : uppercase;
	border          : 1px solid var(--lvc-accent);
	border-radius   : 4px;
	transition      : all 0.25s var(--lvc-ease);
	cursor          : pointer;
}
.lvc-btn-accent:hover { background: var(--lvc-accent-h); border-color: var(--lvc-accent-h); color: #12100f; }

.lvc-btn-outline {
	display         : inline-flex;
	align-items     : center;
	justify-content : center;
	gap             : 0.5rem;
	padding         : 0.9rem 2rem;
	background      : transparent;
	color           : var(--lvc-text);
	font-family     : var(--lvc-fb);
	font-size       : 0.72rem;
	font-weight     : 500;
	letter-spacing  : 0.1em;
	text-transform  : uppercase;
	border          : 1px solid var(--lvc-border);
	border-radius   : 4px;
	transition      : all 0.25s var(--lvc-ease);
}
.lvc-btn-outline:hover { background: var(--lvc-accent); color: #12100f; border-color: var(--lvc-accent); }

/* Button arrow animation */
.lvc-btn-accent .arrow,
.lvc-btn-outline .arrow {
	display: inline-block;
	transition: transform 0.25s var(--lvc-ease);
}
.lvc-btn-accent:hover .arrow,
.lvc-btn-outline:hover .arrow {
	transform: translateX(4px);
}

/* ── HERO ───────────────────────────────────────────────────────────────── */
.lvc-hero {
	position   : relative;
	min-height : 100vh;
	max-height : none;
	display    : flex;
	align-items: center;
	overflow   : hidden;
}

.lvc-hero__bg {
	position           : absolute;
	inset              : 0;
	background-size    : cover;
	background-position: center;
	z-index            : 0;
}

.lvc-hero__fade-top {
	position  : absolute;
	top: 0; left: 0; right: 0;
	height    : 30%;
	background: linear-gradient(180deg, rgba(18,16,15,0.8) 0%, transparent 100%);
	z-index   : 1;
}

.lvc-hero__fade-bottom {
	position  : absolute;
	bottom: 0; left: 0; right: 0;
	height    : 65%;
	background: linear-gradient(0deg, rgba(18,16,15,0.95) 0%, rgba(18,16,15,0.6) 45%, transparent 100%);
	z-index   : 1;
}

.lvc-hero__content {
	position   : relative;
	z-index    : 3;
	width      : 100%;
	padding    : 8rem var(--lvc-px) 7rem;
	max-width  : 1600px;
	margin     : 0 auto;
}

.lvc-hero__breadcrumb {
	display       : flex;
	align-items   : center;
	gap           : 8px;
	font-size     : 0.68rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : rgba(245,240,234,0.4);
	margin-bottom : 2.5rem;
	flex-wrap     : wrap;
	font-family   : var(--lvc-fb);
}
.lvc-hero__breadcrumb a { color: rgba(245,240,234,0.4); transition: color 0.2s; }
.lvc-hero__breadcrumb a:hover { color: var(--lvc-accent); }
.lvc-hero__breadcrumb .sep { color: rgba(245,240,234,0.15); }
.lvc-hero__breadcrumb .current { color: rgba(245,240,234,0.6); }

.lvc-hero__eyebrow {
	font-size     : 0.68rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	margin-bottom : 1.25rem;
	display       : flex;
	align-items   : center;
	gap           : 10px;
	font-family   : var(--lvc-fb);
	font-weight   : 500;
}
.lvc-hero__eyebrow::before {
	content   : '';
	width     : 18px; height: 1px;
	background: var(--lvc-accent);
	opacity   : 0.6;
}

.lvc-hero__h1 {
	font-family  : var(--lvc-fd);
	font-size    : clamp(2.5rem, 5.5vw, 4.5rem);
	font-weight  : 400;
	line-height  : 1.0;
	letter-spacing: -0.02em;
	color        : #fff;
	margin       : 0 0 1.25rem 0;
	max-width    : 880px;
	text-shadow  : 0 2px 24px rgba(0,0,0,0.4);
}
.lvc-hero__h1 em { font-style: italic; color: var(--lvc-accent); }

.lvc-hero__divider {
	width     : 60px; height: 1px;
	background: var(--lvc-accent);
	margin    : 1.5rem 0;
	opacity   : 0.7;
}

.lvc-hero__bridge {
	font-size  : 1.05rem;
	color      : rgba(245,240,234,0.7);
	line-height: 1.75;
	max-width  : 640px;
	margin     : 0 0 2.5rem 0;
	font-weight: 300;
}

.lvc-hero__cta-row {
	display    : flex;
	gap        : 1rem;
	flex-wrap  : wrap;
	align-items: center;
}

.lvc-hero__tag {
	position      : absolute;
	bottom        : 2.5rem;
	right         : var(--lvc-px);
	z-index       : 4;
	display       : flex;
	align-items   : center;
	gap           : 10px;
	padding       : 0.7rem 1.1rem;
	background    : rgba(26,23,21,0.9);
	border        : 1px solid rgba(194,129,140,0.25);
	border-radius : 4px;
	font-size     : 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : rgba(245,240,234,0.6);
	backdrop-filter: blur(8px);
}
.lvc-hero__tag-dot {
	width        : 6px; height: 6px;
	border-radius: 50%;
	background   : var(--lvc-accent);
	animation    : lvcPulse 2s ease-in-out infinite;
}
@keyframes lvcPulse {
	0%, 100% { opacity: 1; }
	50%       { opacity: 0.4; }
}

/* ── WITHIN COLLECTION BAND ─────────────────────────────────────────────── */
.lvc-within {
	background   : var(--lvc-bg2);
	padding      : 1.75rem var(--lvc-px);
	border-bottom: 1px solid var(--lvc-border);
}
.lvc-within__inner {
	max-width      : 1600px;
	margin         : 0 auto;
	display        : flex;
	justify-content: space-between;
	align-items    : center;
	flex-wrap      : wrap;
	gap            : 1.25rem;
}
.lvc-within__label {
	font-size     : 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
	margin-bottom : 4px;
}
.lvc-within__text {
	font-family: var(--lvc-fd);
	font-size  : 1.15rem;
	font-style : italic;
	color      : var(--lvc-soft);
	font-weight: 400;
}
.lvc-within__text em { color: var(--lvc-accent); font-style: italic; }
.lvc-within__link {
	font-size     : 0.68rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : var(--lvc-text);
	border-bottom : 1px solid var(--lvc-border-h);
	padding-bottom: 3px;
	transition    : all 0.2s;
	font-weight   : 500;
}
.lvc-within__link:hover { color: var(--lvc-accent); border-color: var(--lvc-accent); }

/* ── ABOUT THIS TERM (intro) ────────────────────────────────────────────── */
.lvc-about {
	padding   : 6rem var(--lvc-px);
	background: var(--lvc-bg2);
	border-top: 1px solid var(--lvc-border);
}
.lvc-about__inner {
	max-width : 760px;
	margin    : 0 auto;
}
.lvc-about__title {
	font-family: var(--lvc-fd);
	font-size  : clamp(1.75rem, 3vw, 2.5rem);
	font-weight: 400;
	color      : var(--lvc-text);
	line-height: 1.2;
	margin     : 0 0 1.5rem 0;
}
.lvc-about__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-about__body {
	font-size  : 0.95rem;
	color      : var(--lvc-soft);
	line-height: 1.85;
}
.lvc-about__body p { margin: 0 0 1rem 0; }
.lvc-about__body p:last-child { margin-bottom: 0; }
.lvc-about__body a { color: var(--lvc-text); text-decoration: underline; text-decoration-color: rgba(194,129,140,0.45); text-underline-offset: 3px; }
.lvc-about__body a:hover { color: var(--lvc-accent); text-decoration-color: var(--lvc-accent); }

/* ── VILLA GRID ─────────────────────────────────────────────────────────── */
.lvc-villas {
	padding   : 6rem var(--lvc-px);
	background: var(--lvc-bg2);
}
.lvc-villas__inner { max-width: 1600px; margin: 0 auto; }
.lvc-villas__header {
	display        : flex;
	align-items    : flex-end;
	justify-content: space-between;
	margin-bottom  : 3rem;
	flex-wrap      : wrap;
	gap            : 1rem;
}
.lvc-villas__title {
	font-family: var(--lvc-fd);
	font-size  : clamp(1.75rem, 3vw, 2.5rem);
	font-weight: 400;
	line-height: 1.15;
	margin     : 0;
	color      : var(--lvc-text);
}
.lvc-villas__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-villas__count {
	font-size     : 0.72rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
}
.lvc-villas__count strong { color: var(--lvc-accent); font-weight: 500; }

.lvc-villa-grid {
	display              : grid;
	grid-template-columns: repeat(3, 1fr);
	gap                  : 1.5rem;
}
.lvc-villa-card {
	background     : var(--lvc-card);
	border         : 1px solid var(--lvc-border);
	border-radius  : 4px;
	overflow       : hidden;
	display        : block;
	text-decoration: none;
	color          : var(--lvc-text);
	transition     : all 0.3s var(--lvc-ease);
}
.lvc-villa-card:hover { border-color: var(--lvc-accent); transform: translateY(-4px); box-shadow: var(--lvc-shadow-h); }

.lvc-villa-card__img {
	height            : 240px;
	position          : relative;
	overflow          : hidden;
	background-color  : var(--lvc-bg);
	background-size   : cover;
	background-position: center;
	transition        : transform 0.5s var(--lvc-ease);
	display           : flex;
	align-items       : center;
	justify-content   : center;
}

/* Gradient overlay on villa images */
.lvc-villa-card__img::after {
	content: '';
	position: absolute;
	inset: 0;
	background: linear-gradient(to bottom, transparent 60%, rgba(18,16,15,0.4) 100%);
	pointer-events: none;
	z-index: 1;
}

.lvc-villa-card:hover .lvc-villa-card__img { transform: scale(1.04); }

.lvc-villa-card__placeholder {
	font-size     : 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
}

.lvc-villa-card__body   { padding: 1.4rem 1.5rem 1.6rem; }
.lvc-villa-card__loc {
	font-size     : 0.62rem;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
	margin        : 0 0 6px 0;
}
.lvc-villa-card__name {
	font-family: var(--lvc-fd);
	font-size  : 1.45rem;
	font-weight: 400;
	line-height: 1.1;
	margin     : 0 0 0.6rem 0;
	color      : var(--lvc-text);
}
.lvc-villa-card__specs {
	font-size  : 0.75rem;
	color      : var(--lvc-muted);
	margin     : 0 0 1rem 0;
}
.lvc-villa-card__foot {
	display        : flex;
	justify-content: space-between;
	align-items    : center;
	padding-top    : 0.85rem;
	border-top     : 1px solid var(--lvc-border);
}

/* Enhanced CTA button */
.lvc-villa-card__cta {
	display       : inline-flex;
	align-items   : center;
	gap           : 6px;
	padding       : 0.5rem 1rem;
	font-size     : 0.6rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
	background    : transparent;
	border        : 1px solid var(--lvc-border);
	border-radius : 4px;
	transition    : all 0.25s var(--lvc-ease);
	font-family   : var(--lvc-fb);
	font-weight   : 500;
}
.lvc-villa-card:hover .lvc-villa-card__cta {
	background : var(--lvc-accent);
	border-color: var(--lvc-accent);
	color      : #12100f;
}
.lvc-villa-card__cta .arrow {
	transition: transform 0.25s var(--lvc-ease);
}
.lvc-villa-card:hover .lvc-villa-card__cta .arrow {
	transform: translateX(3px);
}

/* Pagination */
.lvc-pagination {
	display        : flex;
	justify-content: center;
	align-items    : center;
	gap            : 0.4rem;
	margin-top     : 5rem;
	flex-wrap      : wrap;
}
.lvc-pagination a,
.lvc-pagination span {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	min-width      : 48px;
	height         : 48px;
	padding        : 0 14px;
	font-size      : 0.78rem;
	color          : var(--lvc-muted);
	border         : 1px solid var(--lvc-border);
	background     : var(--lvc-bg);
	border-radius  : 4px;
	transition     : all 0.2s;
	font-family    : var(--lvc-fb);
}
.lvc-pagination a:hover        { border-color: var(--lvc-accent); color: var(--lvc-accent); }
.lvc-pagination .current       { background: var(--lvc-accent); color: #12100f; border-color: var(--lvc-accent); font-weight: 500; }

/* ── BODY COPY (below the grid) ─────────────────────────────────────────── */
.lvc-term-body {
	padding   : 4.5rem var(--lvc-px);
	background: var(--lvc-bg);
	border-top: 1px solid var(--lvc-border);
}
.lvc-term-body__inner {
	max-width: 1100px;
	margin: 0 auto;
}
.lvc-term-body__copy {
	font-size: 0.95rem;
	line-height: 1.9;
	color: var(--lvc-soft);
}
.lvc-term-body__copy p { margin: 0 0 1rem 0; }
.lvc-term-body__copy p:last-child { margin-bottom: 0; }
.lvc-term-body__copy a {
	color: var(--lvc-text);
	font-weight: 500;
	text-decoration: underline;
	text-decoration-color: rgba(194,129,140,0.45);
	text-underline-offset: 3px;
}
.lvc-term-body__copy a:hover {
	color: var(--lvc-accent);
	text-decoration-color: var(--lvc-accent);
}

/* ── FAQ ────────────────────────────────────────────────────────────────── */
.lvc-faq {
	padding   : 6rem var(--lvc-px);
	background: var(--lvc-bg2);
	border-top: 1px solid var(--lvc-border);
	max-width: var(--lvc-max-narrow); /* folded from brand.css */
}
.lvc-faq__inner { max-width: 860px; margin: 0 auto; }
.lvc-faq__header { text-align: center; margin-bottom: 3rem; }
.lvc-faq__header .lvc-eyebrow { justify-content: center; }
.lvc-faq__header .lvc-eyebrow::before { display: none; }
.lvc-faq__title {
	font-family: var(--lvc-fd);
	font-size  : clamp(1.75rem, 3vw, 2.5rem);
	font-weight: 400;
	color      : var(--lvc-text);
	margin     : 0;
	line-height: 1.15;
}
.lvc-faq__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-faq__item {
	padding      : 1.75rem;
	background   : var(--lvc-bg);
	border       : 1px solid var(--lvc-border);
	border-radius: 4px;
	margin-bottom: 1rem;
	transition   : border-color 0.2s;
}
.lvc-faq__item:hover { border-color: var(--lvc-accent); }
.lvc-faq__q {
	font-family: var(--lvc-fd);
	font-size  : 1.25rem;
	font-weight: 400;
	color      : var(--lvc-text);
	margin     : 0 0 0.6rem 0;
	line-height: 1.25;
}
.lvc-faq__a {
	font-size  : 0.88rem;
	color      : var(--lvc-soft);
	line-height: 1.8;
	margin     : 0;
}

/* ── SIBLING TERMS ──────────────────────────────────────────────────────── */
.lvc-siblings {
	padding   : 6rem var(--lvc-px);
	background: var(--lvc-bg);
}
.lvc-siblings__inner { max-width: 1600px; margin: 0 auto; }
.lvc-siblings__header { text-align: center; margin-bottom: 3.5rem; }
.lvc-siblings__header .lvc-eyebrow { justify-content: center; }
.lvc-siblings__header .lvc-eyebrow::before { display: none; }
.lvc-siblings__title {
	font-family: var(--lvc-fd);
	font-size  : clamp(1.75rem, 3vw, 2.5rem);
	font-weight: 400;
	line-height: 1.15;
	margin     : 0 0 0.85rem 0;
	color      : var(--lvc-text);
}
.lvc-siblings__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-siblings__intro {
	max-width : 600px;
	margin    : 0 auto;
	font-size : 0.9rem;
	color     : var(--lvc-soft);
	line-height: 1.75;
}
.lvc-siblings__grid {
	display              : grid;
	grid-template-columns: repeat(3, 1fr);
	gap                  : 1.5rem;
	margin-top           : 3rem;
}
.lvc-siblings__grid--single { grid-template-columns: minmax(0, 380px); justify-content: center; }

.lvc-sibling-card {
	background     : var(--lvc-card);
	border         : 1px solid var(--lvc-border);
	border-radius  : 4px;
	overflow       : hidden;
	display        : block;
	text-decoration: none;
	color          : var(--lvc-text);
	transition     : all 0.3s var(--lvc-ease);
}
.lvc-sibling-card:hover { border-color: var(--lvc-accent); transform: translateY(-4px); box-shadow: var(--lvc-shadow-h); }

.lvc-sibling-card__img {
	height            : 180px;
	background-color  : var(--lvc-bg);
	background-size   : cover;
	background-position: center;
	position          : relative;
	overflow          : hidden;
}
.lvc-sibling-card__overlay {
	position  : absolute; inset: 0;
	background: linear-gradient(to bottom, transparent 40%, rgba(18,16,15,0.7) 100%);
}
.lvc-sibling-card__count-badge {
	position      : absolute;
	top: 12px; right: 12px;
	padding       : 4px 10px;
	font-size     : 0.6rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	background    : rgba(26,23,21,0.92);
	color         : var(--lvc-text);
	font-weight   : 500;
	font-family   : var(--lvc-fb);
	z-index       : 1;
	border-radius : 4px;
}
.lvc-sibling-card__body { padding: 1.4rem 1.6rem 1.6rem; }
.lvc-sibling-card__name {
	font-family: var(--lvc-fd);
	font-size  : 1.35rem;
	font-weight: 400;
	line-height: 1.1;
	margin     : 0 0 0.5rem 0;
	color      : var(--lvc-text);
}
.lvc-sibling-card__diff {
	font-size  : 0.82rem;
	color      : var(--lvc-soft);
	line-height: 1.65;
	margin     : 0 0 1.1rem 0;
}
.lvc-sibling-card__cta {
	display       : inline-flex;
	align-items   : center;
	gap           : 6px;
	font-size     : 0.65rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	padding-top   : 1rem;
	border-top    : 1px solid var(--lvc-border);
	width         : 100%;
	font-weight   : 500;
	font-family   : var(--lvc-fb);
	transition    : color 0.2s;
}
.lvc-sibling-card:hover .lvc-sibling-card__cta {
	color: var(--lvc-text);
}
.lvc-siblings__all { text-align: center; margin-top: 3rem; }
.lvc-siblings__all a {
	font-size     : 0.68rem;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color         : var(--lvc-text);
	border-bottom : 1px solid var(--lvc-border-h);
	padding-bottom: 4px;
	transition    : all 0.2s;
	font-weight   : 500;
}
.lvc-siblings__all a:hover { color: var(--lvc-accent); border-color: var(--lvc-accent); }

/* ── INQUIRY FORM ───────────────────────────────────────────────────────── */
.lvc-inquiry {
	padding   : 6rem var(--lvc-px);
	background: var(--lvc-bg2);
	border-top: 1px solid var(--lvc-border);
	position  : relative;
}
.lvc-inquiry__trust-strip {
	max-width      : 1200px;
	margin         : 0 auto 3rem;
	display        : flex;
	justify-content: center;
	gap            : 2.5rem;
	padding        : 1.1rem 1.5rem;
	border-top     : 1px solid rgba(194,129,140,0.2);
	border-bottom  : 1px solid rgba(194,129,140,0.2);
	flex-wrap      : wrap;
}
.lvc-inquiry__trust-item {
	display       : flex;
	align-items   : center;
	gap           : 8px;
	font-size     : 0.65rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
	font-family   : var(--lvc-fb);
	font-weight   : 500;
}
.lvc-inquiry__trust-item::before {
	content      : '';
	width        : 5px; height: 5px;
	border-radius: 50%;
	background   : var(--lvc-accent);
	flex-shrink  : 0;
}
.lvc-inquiry__inner {
	max-width             : 1200px;
	margin                : 0 auto;
	display               : grid;
	grid-template-columns : 1fr 1fr;
	gap                   : 5rem;
	align-items           : start;
}
.lvc-inquiry__left h2 {
	font-family: var(--lvc-fd);
	font-size  : clamp(2rem, 3.5vw, 2.75rem);
	font-weight: 400;
	line-height: 1.1;
	margin     : 0 0 1rem 0;
	color      : var(--lvc-text);
}
.lvc-inquiry__left h2 em { font-style: italic; color: var(--lvc-accent); }
.lvc-inquiry__left p {
	font-size  : 0.9rem;
	color      : var(--lvc-soft);
	line-height: 1.8;
	margin     : 0 0 2rem 0;
}
.lvc-inquiry__trust-list        { display: flex; flex-direction: column; gap: 0.75rem; }
.lvc-inquiry__trust-list-item {
	display    : flex;
	align-items: center;
	gap        : 10px;
	font-size  : 0.82rem;
	color      : var(--lvc-muted);
}
.lvc-inquiry__trust-list-item::before {
	content      : '';
	width        : 5px; height: 5px;
	border-radius: 50%;
	background   : var(--lvc-accent);
	opacity      : 0.7;
	flex-shrink  : 0;
}

/* Shared inquiry-form part (.lvc-form markup), styled to the THV form spec. */
.lvc-inquiry .lvc-form { display: flex; flex-direction: column; gap: 1.1rem; }
.lvc-inquiry .lvc-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.lvc-inquiry .lvc-form__group { display: flex; flex-direction: column; gap: 6px; }
.lvc-inquiry .lvc-form__group label {
	font-size     : 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	opacity       : 0.9;
	font-family   : var(--lvc-fb);
	font-weight   : 500;
}
.lvc-inquiry .lvc-form__group input,
.lvc-inquiry .lvc-form__group select,
.lvc-inquiry .lvc-form__group textarea {
	background        : rgba(255,255,255,0.04);
	border            : 1px solid rgba(245,240,234,0.06);
	color             : var(--lvc-text);
	font-family       : var(--lvc-fb);
	font-size         : 0.875rem;
	font-weight       : 300;
	padding           : 13px 15px;
	outline           : none;
	transition        : border-color 0.2s;
	width             : 100%;
	-webkit-appearance: none;
	border-radius     : 4px;
}
.lvc-inquiry .lvc-form__group input::placeholder,
.lvc-inquiry .lvc-form__group textarea::placeholder { color: var(--lvc-muted); }
.lvc-inquiry .lvc-form__group input:focus,
.lvc-inquiry .lvc-form__group select:focus,
.lvc-inquiry .lvc-form__group textarea:focus { border-color: rgba(194,129,140,0.4); background: rgba(245,240,234,0.06); }
.lvc-inquiry .lvc-form__group select {
	background-image   : url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237d948d' stroke-width='1.5' fill='none'/%3E%3C/svg%3E");
	background-repeat  : no-repeat;
	background-position: right 14px center;
	padding-right      : 36px;
	cursor             : pointer;
}
.lvc-inquiry .lvc-form__group textarea { resize: vertical; min-height: 110px; }
.lvc-inquiry .lvc-form__hp {
	position: absolute !important;
	left    : -9999px !important;
	width   : 1px; height: 1px;
	opacity : 0;
	overflow: hidden;
}
.lvc-inquiry .lvc-form__submit {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	width          : 100%;
	padding        : 1.1rem;
	background     : var(--lvc-accent);
	color          : #12100f;
	font-family    : var(--lvc-fb);
	font-size      : 0.75rem;
	font-weight    : 500;
	letter-spacing : 0.1em;
	text-transform : uppercase;
	border         : none;
	border-radius  : 4px;
	cursor         : pointer;
	transition     : all 0.25s var(--lvc-ease);
	margin-top     : 0.25rem;
}
.lvc-inquiry .lvc-form__submit:hover { background: var(--lvc-accent-h); }
.lvc-inquiry .lvc-form__status { font-size: 0.82rem; color: var(--lvc-soft); margin: 0; min-height: 1em; }
.lvc-inquiry .lvc-form__micro  { font-size: 0.72rem; color: var(--lvc-muted); margin: 0; text-align: center; }

.lvc-inquiry__wa {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	gap            : 8px;
	padding        : 0.8rem;
	background     : transparent;
	border         : 1px solid var(--lvc-border);
	color          : var(--lvc-muted);
	font-size      : 0.68rem;
	letter-spacing : 0.1em;
	text-transform : uppercase;
	transition     : all 0.2s;
	width          : 100%;
	font-family    : var(--lvc-fb);
	border-radius  : 4px;
	margin-top     : 0.75rem;
}
.lvc-inquiry__wa:hover { border-color: rgba(37,211,102,0.35); color: #25D366; }

/* ── FINAL CTA ──────────────────────────────────────────────────────────── */
.lvc-final {
	padding   : 7rem var(--lvc-px) 8rem;
	position  : relative;
	text-align: center;
	overflow  : hidden;
	background: var(--lvc-bg2);
	border-top: 1px solid var(--lvc-border);
}
.lvc-final__inner { position: relative; z-index: 2; max-width: 720px; margin: 0 auto; }
.lvc-final__title {
	font-family: var(--lvc-fd);
	font-size  : clamp(2.25rem, 4.5vw, 3.5rem);
	font-weight: 400;
	line-height: 1.05;
	margin     : 0.5rem 0 1.25rem 0;
	color      : var(--lvc-text);
}
.lvc-final__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-final__sub {
	font-size  : 0.95rem;
	color      : var(--lvc-soft);
	line-height: 1.75;
	margin     : 0 0 1.5rem 0;
}
.lvc-final__trust {
	display        : flex;
	justify-content: center;
	gap            : 2.5rem;
	margin-bottom  : 2.5rem;
	flex-wrap      : wrap;
}
.lvc-final__trust-item {
	display    : flex;
	align-items: center;
	gap        : 8px;
	font-size  : 0.78rem;
	color      : var(--lvc-muted);
}
.lvc-final__trust-item svg { width: 14px; height: 14px; stroke: var(--lvc-accent); fill: none; stroke-width: 2; }
.lvc-final__actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

/* ── RESPONSIVE ─────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
	.lvc-villa-grid       { grid-template-columns: repeat(2, 1fr); }
	.lvc-siblings__grid   { grid-template-columns: repeat(2, 1fr); }
	.lvc-inquiry__inner   { gap: 3rem; }
}

@media (max-width: 768px) {
	.lvc-hero              { min-height: 100svh; max-height: none; }
	.lvc-hero__content     { padding: 5rem var(--lvc-px) 4rem; }
	.lvc-hero__tag         { position: static; margin-top: 2rem; display: inline-flex; }
	.lvc-within__inner     { flex-direction: column; align-items: flex-start; }
	.lvc-about,
	.lvc-villas,
	.lvc-term-body,
	.lvc-faq,
	.lvc-siblings,
	.lvc-inquiry,
	.lvc-final             { padding: 4rem var(--lvc-px); }
	.lvc-villa-grid        { grid-template-columns: 1fr; }
	.lvc-siblings__grid    { grid-template-columns: 1fr; }
	.lvc-inquiry__inner    { grid-template-columns: 1fr; gap: 2.5rem; }
	.lvc-inquiry .lvc-form__row { grid-template-columns: 1fr; }
	.lvc-villas__header    { flex-direction: column; align-items: flex-start; }
	.lvc-final__trust      { gap: 1.25rem; flex-direction: column; align-items: center; }
	.lvc-final__actions    { flex-direction: column; width: 100%; }
	.lvc-final__actions .lvc-btn-accent,
	.lvc-final__actions .lvc-btn-outline { width: 100%; }
}
</style>

<div class="lvc-wrap">

<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 1: HERO
	═══════════════════════════════════════════════════════════════════════ -->
<section class="lvc-hero">
	<div class="lvc-hero__bg" style="background-image:url('<?php echo esc_url( $lvc_hero_bg ); ?>');"></div>
	<div class="lvc-hero__fade-top"></div>
	<div class="lvc-hero__fade-bottom"></div>

	<div class="lvc-hero__content">

		<nav class="lvc-hero__breadcrumb" aria-label="Breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
			<span class="sep">›</span>
			<a href="<?php echo esc_url( $lvc_archive_url ); ?>">Luxury Villas</a>
			<span class="sep">›</span>
			<span class="current"><?php echo esc_html( $lvc_name ); ?></span>
		</nav>

		<p class="lvc-hero__eyebrow"><?php echo esc_html( lvc_config( 'region' ) ); ?> &middot; Caribbean</p>

		<h1 class="lvc-hero__h1"><?php echo wp_kses_post( $lvc_h1_html ); ?></h1>

		<div class="lvc-hero__divider"></div>

		<?php if ( $lvc_tagline ) : ?>
		<p class="lvc-hero__bridge"><?php echo esc_html( $lvc_tagline ); ?></p>
		<?php endif; ?>

		<div class="lvc-hero__cta-row">
			<a href="#villas" class="lvc-btn-accent">
				View All Villas <span class="arrow">&#x2192;</span>
			</a>
			<?php if ( $lvc_whatsapp ) : ?>
			<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-btn-outline" style="border-color:rgba(255,255,255,0.15);color:#fff;">
				Speak With a Specialist <span class="arrow">&#x2192;</span>
			</a>
			<?php endif; ?>
		</div>

	</div>

	<div class="lvc-hero__tag">
		<span class="lvc-hero__tag-dot"></span>
		<span><?php echo esc_html( $lvc_name ); ?> &middot; <?php echo esc_html( lvc_config( 'region' ) ); ?> &middot; Caribbean</span>
	</div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 2: PART OF THE COLLECTION BAND
	═══════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_peer_total > 1 ) : ?>
<section class="lvc-within">
	<div class="lvc-within__inner">
		<div>
			<p class="lvc-within__label">Part of</p>
			<p class="lvc-within__text">
				One of <em><?php echo (int) $lvc_peer_total; ?> <?php echo esc_html( strtolower( $lvc_plural ) ); ?></em> across <?php echo esc_html( lvc_config( 'region' ) ); ?>
			</p>
		</div>
		<a href="<?php echo esc_url( $lvc_archive_url ); ?>" class="lvc-within__link">
			View All <?php echo esc_html( lvc_config( 'region' ) ); ?> Villas <span class="arrow">&#x2192;</span>
		</a>
	</div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 2B: ABOUT (term intro — falls back to term_description)
	═══════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_intro ) : ?>
<section class="lvc-about">
	<div class="lvc-about__inner">
		<p class="lvc-eyebrow">About</p>
		<h2 class="lvc-about__title">
			About <em><?php echo esc_html( $lvc_name ); ?></em>
		</h2>
		<div class="lvc-about__body"><?php echo wp_kses_post( wpautop( $lvc_intro ) ); ?></div>
	</div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 3: VILLA GRID (main query — pagination-safe)
	═══════════════════════════════════════════════════════════════════════ -->
<section class="lvc-villas" id="villas">
	<div class="lvc-villas__inner">

		<div class="lvc-villas__header">
			<div>
				<p class="lvc-eyebrow">The Collection</p>
				<h2 class="lvc-villas__title">
					<?php if ( 'area' === $lvc_tax ) : ?>
						Villas in <em><?php echo esc_html( $lvc_name ); ?></em>
					<?php else : ?>
						<em><?php echo esc_html( $lvc_name ); ?></em> in <?php echo esc_html( lvc_config( 'region' ) ); ?>
					<?php endif; ?>
				</h2>
			</div>
			<p class="lvc-villas__count">
				Showing <strong><?php echo (int) $lvc_total_villas; ?> <?php echo ( 1 === $lvc_total_villas ) ? 'villa' : 'villas'; ?></strong>
			</p>
		</div>

		<?php if ( have_posts() ) : ?>
		<div class="lvc-villa-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$vid         = get_the_ID();
				$v_name      = get_the_title( $vid );
				$v_url       = get_permalink( $vid );
				$v_bedrooms  = get_post_meta( $vid, 'bed_count', true );
				$v_guests    = get_post_meta( $vid, 'guests_max', true );
				$v_bathrooms = get_post_meta( $vid, 'bath_count', true );

				$v_spec_parts = array();
				if ( $v_bedrooms ) {
					$v_spec_parts[] = esc_html( $v_bedrooms ) . ' Bed';
				}
				if ( $v_guests ) {
					$v_spec_parts[] = esc_html( $v_guests ) . ' Guests';
				}
				if ( $v_bathrooms ) {
					$v_spec_parts[] = esc_html( $v_bathrooms ) . ' Bath';
				}
				$v_specs = implode( ' &middot; ', $v_spec_parts );

				$v_area_terms = get_the_terms( $vid, 'area' );
				$v_loc        = ( ! empty( $v_area_terms ) && ! is_wp_error( $v_area_terms ) ) ? $v_area_terms[0]->name . ', ' . lvc_config( 'region' ) : lvc_config( 'region' );

				// Card image — deliberately NO gallery fallback; placeholder is expected.
				$v_img = function_exists( 'lvc_property_image' ) ? lvc_property_image( $vid ) : get_the_post_thumbnail_url( $vid, 'large' );
				?>
			<a href="<?php echo esc_url( $v_url ); ?>" class="lvc-villa-card">
				<div class="lvc-villa-card__img"<?php echo $v_img ? ' style="' . esc_attr( "background-image:url('" . esc_url( $v_img ) . "')" ) . '"' : ''; ?>>
					<?php if ( ! $v_img ) : ?>
					<span class="lvc-villa-card__placeholder">Photography coming soon</span>
					<?php endif; ?>
				</div>
				<div class="lvc-villa-card__body">
					<p class="lvc-villa-card__loc"><?php echo esc_html( $v_loc ); ?></p>
					<h3 class="lvc-villa-card__name"><?php echo esc_html( $v_name ); ?></h3>
					<?php if ( $v_specs ) : ?>
					<p class="lvc-villa-card__specs"><?php echo wp_kses_post( $v_specs ); ?></p>
					<?php endif; ?>
					<div class="lvc-villa-card__foot">
						<span class="lvc-villa-card__cta">
							View Villa <span class="arrow">&#x2192;</span>
						</span>
					</div>
				</div>
			</a>
				<?php
			endwhile;
			?>
		</div>

			<?php if ( (int) $GLOBALS['wp_query']->max_num_pages > 1 ) : ?>
		<nav class="lvc-pagination" aria-label="Villa pages">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'current'   => $lvc_current_page,
							'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
							'prev_text' => '&#x2190;',
							'next_text' => '&#x2192;',
							'type'      => 'plain',
						)
					)
				);
				?>
		</nav>
		<?php endif; ?>

		<?php else : ?>
		<p style="color:var(--lvc-muted);text-align:center;padding:3rem 0;font-size:0.9rem;">
			No villas currently listed here. Please check back soon, or
			<a href="<?php echo esc_url( $lvc_archive_url ); ?>" style="color:var(--lvc-accent);">browse all <?php echo esc_html( lvc_config( 'region' ) ); ?> villas</a>.
		</p>
		<?php endif; ?>

	</div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 3B: BODY COPY (below the grid)
	═══════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_body ) : ?>
<section class="lvc-term-body">
	<div class="lvc-term-body__inner">
		<div class="lvc-term-body__copy"><?php echo wp_kses_post( $lvc_body ); ?></div>
	</div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 4: FAQ (2+ complete rows required; FAQPage schema via lvc_jsonld)
	═══════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_show_faq && function_exists( 'lvc_jsonld' ) ) : ?>
<section class="lvc-faq" aria-label="<?php echo esc_attr( $lvc_name ); ?> FAQs">
	<div class="lvc-faq__inner">
		<div class="lvc-faq__header">
			<p class="lvc-eyebrow">Good to Know</p>
			<h2 class="lvc-faq__title">Frequently Asked <em>Questions</em></h2>
		</div>
		<?php foreach ( $lvc_faq as $lvc_item ) : ?>
		<div class="lvc-faq__item">
			<h3 class="lvc-faq__q"><?php echo esc_html( $lvc_item['question'] ); ?></h3>
			<p class="lvc-faq__a"><?php echo esc_html( wp_strip_all_tags( $lvc_item['answer'] ) ); ?></p>
		</div>
		<?php endforeach; ?>
	</div>
</section>
	<?php
	lvc_jsonld(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				static function ( $item ) {
					return array(
						'@type'          => 'Question',
						'name'           => $item['question'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => wp_strip_all_tags( $item['answer'] ),
						),
					);
				},
				$lvc_faq
			),
		)
	);
	?>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 5: OTHER TERMS IN THIS TAXONOMY
	═══════════════════════════════════════════════════════════════════════ -->
<?php if ( ! empty( $lvc_siblings ) ) : ?>
<section class="lvc-siblings">
	<div class="lvc-siblings__inner">

		<div class="lvc-siblings__header">
			<p class="lvc-eyebrow">More to Explore</p>
			<h2 class="lvc-siblings__title">
				Other <em><?php echo esc_html( $lvc_plural ); ?></em> in <?php echo esc_html( lvc_config( 'region' ) ); ?>
			</h2>
			<p class="lvc-siblings__intro">
				Every corner of <?php echo esc_html( lvc_config( 'region' ) ); ?> has its own character.
				Explore the other <?php echo esc_html( strtolower( $lvc_plural ) ); ?> to find the setting that suits your group.
			</p>
		</div>

		<div class="lvc-siblings__grid<?php echo $lvc_single_sib_mode ? ' lvc-siblings__grid--single' : ''; ?>">
		<?php
		foreach ( $lvc_siblings as $lvc_sib ) :
			$lvc_sib_url = get_term_link( $lvc_sib );
			if ( is_wp_error( $lvc_sib_url ) ) {
				continue;
			}
			$lvc_sib_img   = lvc_priority_image_url( (string) lvc_field( 'hero_image_url', 'term_' . $lvc_sib->term_id ) );
			$lvc_sib_diff  = (string) lvc_field( 'tagline', 'term_' . $lvc_sib->term_id );
			$lvc_sib_count = (int) $lvc_sib->count;
			?>
			<a href="<?php echo esc_url( $lvc_sib_url ); ?>" class="lvc-sibling-card">
				<div class="lvc-sibling-card__img"<?php echo $lvc_sib_img ? ' style="' . esc_attr( "background-image:url('" . esc_url( $lvc_sib_img ) . "')" ) . '"' : ''; ?>>
					<div class="lvc-sibling-card__overlay"></div>
					<span class="lvc-sibling-card__count-badge">
						<?php echo esc_html( (string) $lvc_sib_count ); ?> <?php echo ( 1 === $lvc_sib_count ) ? 'Villa' : 'Villas'; ?>
					</span>
				</div>
				<div class="lvc-sibling-card__body">
					<h3 class="lvc-sibling-card__name"><?php echo esc_html( $lvc_sib->name ); ?></h3>
					<?php if ( $lvc_sib_diff ) : ?>
					<p class="lvc-sibling-card__diff"><?php echo esc_html( $lvc_sib_diff ); ?></p>
					<?php endif; ?>
					<span class="lvc-sibling-card__cta">
						Explore <?php echo esc_html( $lvc_sib->name ); ?> <span class="arrow">&#x2192;</span>
					</span>
				</div>
			</a>
		<?php endforeach; ?>
		</div>

		<?php if ( $lvc_peer_total > 7 ) : ?>
		<div class="lvc-siblings__all">
			<a href="<?php echo esc_url( $lvc_archive_url ); ?>">
				View All <?php echo esc_html( lvc_config( 'region' ) ); ?> Villas <span class="arrow">&#x2192;</span>
			</a>
		</div>
		<?php endif; ?>

	</div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 6: INQUIRY FORM (shared template part — AJAX + Turnstile)
	═══════════════════════════════════════════════════════════════════════ -->
<section class="lvc-inquiry" id="inquiry">

	<div class="lvc-inquiry__trust-strip">
		<span class="lvc-inquiry__trust-item">Direct Booking</span>
		<span class="lvc-inquiry__trust-item">No Platform Markups</span>
		<span class="lvc-inquiry__trust-item">Local Concierge Support</span>
		<span class="lvc-inquiry__trust-item">Hand-Picked Villas</span>
	</div>

	<div class="lvc-inquiry__inner">

		<div class="lvc-inquiry__left">
			<p class="lvc-eyebrow">Start Your Inquiry</p>
			<h2>Speak With a <em><?php echo esc_html( lvc_config( 'region' ) ); ?></em> Villa Specialist</h2>
			<p>Tell us your dates, group size, and priorities. Our concierge team shortlists the best-fit villas <?php echo ( 'area' === $lvc_tax ) ? 'in ' . esc_html( $lvc_name ) : 'across ' . esc_html( lvc_config( 'region' ) ); ?> &mdash; personally, not by algorithm. No platform markups, no queues, no third parties.</p>

			<div class="lvc-inquiry__trust-list">
				<div class="lvc-inquiry__trust-list-item">Concierge-shortlisted villa options based on your group and budget</div>
				<div class="lvc-inquiry__trust-list-item">Clear availability windows and pricing guidance up front</div>
				<div class="lvc-inquiry__trust-list-item">Direct booking flow with no platform markups</div>
				<div class="lvc-inquiry__trust-list-item">Local coordination support before arrival and during stay</div>
			</div>
		</div>

		<div class="lvc-inquiry__form">
			<?php
			get_template_part(
				'template-parts/inquiry-form',
				null,
				array( 'property_name' => $lvc_name . ' (' . $lvc_singular . ' page)' )
			);
			?>
			<?php if ( $lvc_whatsapp ) : ?>
			<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-inquiry__wa">Or WhatsApp Us Directly</a>
			<?php endif; ?>
		</div>

	</div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════════
	SECTION 7: FINAL CTA
	═══════════════════════════════════════════════════════════════════════ -->
<section class="lvc-final">
	<div class="lvc-final__inner">
		<p class="lvc-eyebrow" style="justify-content:center;">Reserve Direct</p>

		<h2 class="lvc-final__title">
			<?php if ( 'area' === $lvc_tax ) : ?>
				Your Villa in <em><?php echo esc_html( $lvc_name ); ?></em>
			<?php else : ?>
				Your <em><?php echo esc_html( lvc_config( 'region' ) ); ?></em> Villa, Booked Direct
			<?php endif; ?>
		</h2>

		<div class="lvc-final__trust">
			<div class="lvc-final__trust-item">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
				Direct Booking
			</div>
			<div class="lvc-final__trust-item">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
				No Platform Markups
			</div>
			<div class="lvc-final__trust-item">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
				Hand-Picked <?php echo esc_html( lvc_config( 'region' ) ); ?> Villas
			</div>
		</div>

		<div class="lvc-final__actions">
			<a href="#inquiry" class="lvc-btn-accent">
				Send an Inquiry <span class="arrow">&#x2192;</span>
			</a>
			<?php if ( $lvc_whatsapp ) : ?>
			<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-btn-outline">
				WhatsApp Direct <span class="arrow">&#x2192;</span>
			</a>
			<?php endif; ?>
		</div>
	</div>
</section>

</div><!-- .lvc-wrap -->

<?php
get_footer();
