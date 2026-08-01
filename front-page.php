<?php
/**
 * St Barts Villa Rentals — Homepage.
 *
 * Ported from anguilla-repo 2026-08-01, which had itself cloned
 * tulumholidayvillas' front-page.php (v3.0 dark theme): same sections, markup
 * and CSS, recolored to the St Barts salt-rose palette and pointed at this
 * site's data model (villa CPT + area/bedrooms taxonomies).
 * Class prefix lvc- replaces Anguilla's ablv-.
 *
 * Place names read lvc_config('region') rather than being hardcoded, so this
 * file ports again without another find-and-replace. The two lines that could
 * not be parameterised — the hero's named bays and the direct-booking brand —
 * were rewritten for St Barts rather than left as another island's copy.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// DATA
// ═══════════════════════════════════════════════════════════════════════════

// Hero: the Homepage ACF field, or nothing. Anguilla hardcoded one of its own
// villa photographs from the rmof CDN as the fallback here; carried across that
// would have published another island's property as this site's hero image.
// Sections guard on empty and fall back to a dark surface, which is the honest
// state until a St Barts hero is set on the front page.
$hero_image = (string) lvc_field( 'home_hero_image_url', (int) get_option( 'page_on_front' ) );

// The CPT name is config, not a constant — theme-config.php offers villa,
// chalet, condo or property, so reading it here is what keeps this template
// brand-agnostic. `?? 0` covers the window before any post of that type exists:
// wp_count_posts() has no ->publish to read then, and the count is printed into
// visible CTA copy ("View All N Villas") where a warning would surface.
$lvc_cpt     = lvc_config( 'cpt', 'villa' );
$villa_count = (int) ( wp_count_posts( $lvc_cpt )->publish ?? 0 );
$archive_url = get_post_type_archive_link( $lvc_cpt );
$whatsapp    = (string) lvc_config( 'whatsapp_url', '' );

// Areas: the two strongest WITH imagery become the big cards; every area with
// enough villas to be indexable joins the compact grid below.
$area_terms = get_terms(
	array(
		'taxonomy'   => 'area',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
$area_terms = is_wp_error( $area_terms ) ? array() : $area_terms;

$featured_areas = array();
$compact_areas  = array();
foreach ( $area_terms as $t ) {
	$img = (string) lvc_field( 'hero_image_url', 'term_' . $t->term_id );
	if ( count( $featured_areas ) < 2 && '' !== $img ) {
		$featured_areas[] = array( 'term' => $t, 'image' => $img );
		continue;
	}
	if ( (int) $t->count >= (int) lvc_config( 'min_index_count', 3 ) ) {
		$compact_areas[] = $t;
	}
}
$compact_areas = array_slice( $compact_areas, 0, 10 );

// "Collections" band: this site's experiences taxonomy has no terms yet, so
// the honest curated set is villa SIZE — real bedrooms-term archives with
// live counts, imaged from villas that actually have photos.
$size_terms = get_terms(
	array(
		'taxonomy'   => 'bedrooms',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
$size_terms = is_wp_error( $size_terms ) ? array() : $size_terms;
$size_cards = array();
foreach ( $size_terms as $t ) {
	if ( (int) $t->count < (int) lvc_config( 'min_index_count', 3 ) ) {
		continue;
	}
	$size_cards[] = array(
		'term'  => $t,
		'image' => (string) lvc_field( 'hero_image_url', 'term_' . $t->term_id ),
	);
}
// Imaged cards lead; the first card spans full width (THV pattern).
usort( $size_cards, function ( $a, $b ) {
	return ( '' === $a['image'] ) <=> ( '' === $b['image'] );
} );
$size_cards = array_slice( $size_cards, 0, 5 );

// Featured villas: villas WITH a featured image lead while most photo sets
// are still uploading; falls back to recent villas if fewer than six.
$featured_query = new WP_Query(
	array(
		'post_type'      => 'villa',
		'posts_per_page' => 6,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'meta_query'     => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ),
	)
);
if ( $featured_query->post_count < 6 ) {
	$featured_query = new WP_Query(
		array(
			'post_type'      => 'villa',
			'posts_per_page' => 6,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
}

get_header();
?>

<link rel="preload" as="image" href="<?php echo esc_url( $hero_image ); ?>" fetchpriority="high">

<style>
/* ═══════════════════════════════════════════════════════════════════════════
	ST BARTS VILLA RENTALS — HOMEPAGE (cloned from THV v3.0 dark theme)
	St Barts palette: volcanic basalt ground, salt-rose accent, salt-crust text.
	═══════════════════════════════════════════════════════════════════════════ */

:root {
	--lvc-bg        : #12100f;
	--lvc-bg2       : #1a1715;
	--lvc-bg3       : #1c1917;
	--lvc-primary   : #2a1c20;
	--lvc-primary-h : #3a262b;
	--lvc-accent    : #c2818c;
	--lvc-accent-h  : #d9a0a9;
	--lvc-text      : #f5f0ea;
	--lvc-soft      : #c3b8b0;
	--lvc-muted     : #9c918b;
	--lvc-border    : rgba(245,240,234,0.06);
	--lvc-border-h  : rgba(194,129,140,0.25);
	--lvc-shadow    : 0 4px 24px rgba(0,0,0,0.5);
	--lvc-shadow-h  : 0 8px 32px rgba(0,0,0,0.7);
	--lvc-fd        : 'Gilda Display', Georgia, serif;
	--lvc-fb        : 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
	--lvc-ease      : cubic-bezier(0.25, 0.46, 0.45, 0.94);
	--lvc-px        : clamp(1.25rem, 5vw, 4rem);
}

.lvc-wrap *, .lvc-wrap *::before, .lvc-wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }
.lvc-wrap {
	background          : var(--lvc-bg);
	color               : var(--lvc-text);
	font-family         : var(--lvc-fb);
	font-weight         : 300;
	line-height         : 1.6;
	overflow-x          : hidden;
	-webkit-font-smoothing: antialiased;
}
.lvc-wrap a { text-decoration: none; color: inherit; }
.lvc-wrap img { max-width: 100%; height: auto; display: block; }

/* ── UTILITIES ─────────────────────────────────────────────────────────── */
.lvc-eyebrow {
	display        : inline-flex;
	align-items    : center;
	gap            : 10px;
	font-size      : 0.68rem;
	letter-spacing : 0.18em;
	text-transform : uppercase;
	color          : var(--lvc-accent);
	margin-bottom  : 0.85rem;
	font-weight    : 500;
	font-family    : var(--lvc-fb);
}
.lvc-eyebrow::before { content: ''; width: 18px; height: 1px; background: var(--lvc-accent); opacity: 0.5; flex-shrink: 0; }
.lvc-eyebrow--center { justify-content: center; }
.lvc-eyebrow--center::before { display: none; }

.lvc-btn-accent {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	gap            : 0.5rem;
	padding        : 1rem 2rem;
	background     : var(--lvc-accent);
	color          : #12100f;
	font-family    : var(--lvc-fb);
	font-size      : 0.72rem;
	font-weight    : 600;
	letter-spacing : 0.1em;
	text-transform : uppercase;
	border         : 1px solid var(--lvc-accent);
	transition     : all 0.25s var(--lvc-ease);
	cursor         : pointer;
}
.lvc-btn-accent:hover { background: var(--lvc-accent-h); border-color: var(--lvc-accent-h); color: #12100f; box-shadow: 0 4px 20px rgba(194,129,140,0.2); }

.lvc-btn-outline-light {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	gap            : 0.5rem;
	padding        : 0.9rem 2rem;
	background     : rgba(255,255,255,0.05);
	color          : rgba(255,255,255,0.85);
	font-family    : var(--lvc-fb);
	font-size      : 0.72rem;
	font-weight    : 500;
	letter-spacing : 0.1em;
	text-transform : uppercase;
	border         : 1px solid rgba(255,255,255,0.25);
	transition     : all 0.25s var(--lvc-ease);
}
.lvc-btn-outline-light:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.5); color: #fff; }

/* ── HERO ───────────────────────────────────────────────────────────────── */
.lvc-hero { position: relative; min-height: min(82vh, 820px); display: flex; align-items: center; overflow: hidden; }
.lvc-hero__bg {
	position            : absolute; inset: 0;
	background-image    : url('<?php echo esc_url( $hero_image ); ?>');
	background-size     : cover;
	background-position : center;
	z-index             : 0;
}
.lvc-hero__overlay {
	position  : absolute; inset: 0;
	background: linear-gradient(to bottom, rgba(18,16,15,0.6) 0%, transparent 15%, transparent 85%, rgba(18,16,15,0.6) 100%);
	z-index   : 1;
}
.lvc-hero__overlay-left {
	position  : absolute; inset: 0;
	background: linear-gradient(to right, rgba(18,16,15,0.92) 0%, rgba(18,16,15,0.72) 30%, rgba(18,16,15,0.32) 58%, transparent 80%);
	z-index   : 1;
	pointer-events: none;
}
.lvc-hero__content { position: relative; z-index: 2; width: 100%; padding: 7rem var(--lvc-px) 4.5rem; max-width: 1600px; margin: 0 auto; }
.lvc-hero__eyebrow {
	font-size     : 0.68rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	margin-bottom : 1.25rem;
	display       : flex;
	align-items   : center;
	gap           : 10px;
	font-weight   : 500;
}
.lvc-hero__eyebrow::before { content: ''; width: 18px; height: 1px; background: var(--lvc-accent); opacity: 0.6; }
.lvc-hero__h1 {
	font-family   : var(--lvc-fd);
	font-size     : clamp(2.75rem, 6vw, 5.5rem);
	font-weight   : 400;
	line-height   : 1;
	letter-spacing: -0.01em;
	color         : #fff;
	margin        : 0 0 1.25rem 0;
	max-width     : 820px;
	text-shadow   : 0 2px 24px rgba(0,0,0,0.4);
}
.lvc-hero__h1 em { font-style: italic; color: var(--lvc-accent); }
.lvc-hero__divider { width: 60px; height: 1px; background: var(--lvc-accent); margin: 1.5rem 0; opacity: 0.7; }
.lvc-hero__intro { font-size: 1.05rem; color: rgba(255,255,255,0.72); line-height: 1.75; max-width: 600px; margin: 0 0 2.5rem 0; font-weight: 300; }
.lvc-hero__ctas { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
.lvc-hero__trust {
	display              : grid;
	grid-template-columns: repeat(3, auto);
	gap                  : 0;
	margin-top           : 2.5rem;
	padding              : 1.1rem 1.25rem;
	max-width            : 760px;
	background           : var(--lvc-bg2);
	border               : 1px solid var(--lvc-border);
}
.lvc-hero__trust-item {
	padding-right : 1.25rem;
	margin-right  : 1.25rem;
	border-right  : 1px solid rgba(255,255,255,0.1);
	font-size     : 0.66rem;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color         : rgba(255,255,255,0.82);
	font-weight   : 500;
}
.lvc-hero__trust-item:last-child { padding-right: 0; margin-right: 0; border-right: none; }
.lvc-hero__routes { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem; max-width: 760px; }
.lvc-hero__route {
	padding       : 0.5rem 0.8rem;
	font-size     : 0.62rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : rgba(255,255,255,0.78);
	border        : 1px solid rgba(255,255,255,0.16);
	background    : var(--lvc-bg2);
	border-radius : 4px;
	transition    : all 0.2s;
}
.lvc-hero__route:hover { border-color: rgba(194,129,140,0.5); color: var(--lvc-accent); }


/* ── VILLA FINDER ──────────────────────────────────────────────────────── */
.lvc-search {
	padding: 1.35rem var(--lvc-px);
	background: var(--lvc-bg2);
	border-top: 1px solid var(--lvc-border);
	border-bottom: 1px solid var(--lvc-border);
	position: relative;
	z-index: 5;
}
.lvc-search__inner {
	max-width: 1600px;
	margin: 0 auto;
	display: grid;
	grid-template-columns: minmax(180px, 1.1fr) repeat(5, minmax(125px, 0.8fr)) auto;
	gap: 0.8rem;
	align-items: end;
}
.lvc-search__intro { align-self: center; padding-right: 1rem; }
.lvc-search__intro strong {
	display: block;
	font-family: var(--lvc-fd);
	font-size: 1.25rem;
	font-weight: 400;
	color: var(--lvc-text);
	line-height: 1.15;
}
.lvc-search__intro span { display: block; margin-top: 0.25rem; font-size: 0.75rem; color: var(--lvc-soft); }
.lvc-search__field { display: flex; flex-direction: column; gap: 0.35rem; }
.lvc-search__field label {
	font-size: 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-soft);
	font-weight: 500;
}
.lvc-search__field select,
.lvc-search__field input {
	width: 100%;
	min-height: 48px;
	appearance: none;
	-webkit-appearance: none;
	padding: 0.75rem 2.25rem 0.75rem 0.9rem;
	border: 1px solid var(--lvc-border-h);
	border-radius: 4px;
	background-color: var(--lvc-bg);
	background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'><path fill='%237cc8c0' d='M6 8L0 0h12z'/></svg>");
	background-repeat: no-repeat;
	background-position: right 0.8rem center;
	color: var(--lvc-text);
	font: 400 0.85rem var(--lvc-fb);
	color-scheme: dark;
}
.lvc-search__field input {
	appearance: auto;
	-webkit-appearance: auto;
	padding-right: 0.75rem;
	background-image: none;
}
.lvc-search__submit { min-height: 48px; white-space: nowrap; padding-inline: 1.35rem; }
.lvc-wrap a:focus-visible,
.lvc-wrap button:focus-visible,
.lvc-wrap select:focus-visible,
.lvc-wrap input:focus-visible {
	outline: 2px solid var(--lvc-accent);
	outline-offset: 3px;
}

/* ── AREAS ─────────────────────────────────────────────────────────────── */
.lvc-destinations { padding: 6rem var(--lvc-px); background: var(--lvc-bg2); border-top: 1px solid var(--lvc-border); }
.lvc-destinations__inner { max-width: 1600px; margin: 0 auto; }
.lvc-destinations__header { margin-bottom: 3rem; }
.lvc-destinations__micro { max-width: 720px; font-size: 0.92rem; line-height: 1.75; color: var(--lvc-soft); margin-top: 0.8rem; }
.lvc-destinations__title { font-family: var(--lvc-fd); font-size: clamp(1.75rem, 3vw, 2.75rem); font-weight: 400; color: var(--lvc-text); margin: 0; line-height: 1.15; }
.lvc-destinations__title em { font-style: italic; color: var(--lvc-accent); }

.lvc-dest-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem; }
.lvc-dest-card { position: relative; height: 320px; overflow: hidden; display: block; background-color: var(--lvc-bg3); border-radius: 4px; }
.lvc-dest-card:hover .lvc-dest-card__bg { transform: scale(1.04); }
.lvc-dest-card__bg { position: absolute; inset: 0; background-size: cover; background-position: center; transition: transform 0.6s var(--lvc-ease); }
.lvc-dest-card__overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 50%, rgba(18,16,15,0.9) 100%); }
.lvc-dest-card__body { position: absolute; bottom: 0; left: 0; right: 0; padding: 1.75rem 2rem; z-index: 2; }
.lvc-dest-card__count { font-size: 0.62rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--lvc-accent); margin-bottom: 0.4rem; font-weight: 500; }
.lvc-dest-card__name { font-family: var(--lvc-fd); font-size: 1.85rem; font-weight: 400; color: #fff; margin: 0 0 0.5rem 0; line-height: 1.1; }
.lvc-dest-card__cta { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.5); font-weight: 500; transition: color 0.2s; }
.lvc-dest-card:hover .lvc-dest-card__cta { color: var(--lvc-accent); }

.lvc-area-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; }
.lvc-area-card {
	background     : var(--lvc-bg);
	border         : 1px solid var(--lvc-border);
	display        : flex;
	justify-content: space-between;
	align-items    : center;
	padding        : 1rem 1.25rem;
	transition     : all 0.2s var(--lvc-ease);
	border-radius  : 4px;
	gap            : 0.75rem;
}
.lvc-area-card:hover { border-color: var(--lvc-accent); background: var(--lvc-bg3); box-shadow: 0 4px 16px rgba(0,0,0,0.3); }
.lvc-area-card__name { font-family: var(--lvc-fd); font-size: 1.1rem; font-weight: 400; color: var(--lvc-text); }
.lvc-area-card__count { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--lvc-muted); white-space: nowrap; }
.lvc-area-card:hover .lvc-area-card__count { color: var(--lvc-accent); }

/* ── SIZE COLLECTIONS ──────────────────────────────────────────────────── */
.lvc-experiences { padding: 6rem var(--lvc-px); background: var(--lvc-bg); border-top: 1px solid var(--lvc-border); }
.lvc-experiences__inner { max-width: 1600px; margin: 0 auto; }
.lvc-experiences__header { text-align: center; margin-bottom: 3.5rem; }
.lvc-experiences__title { font-family: var(--lvc-fd); font-size: clamp(1.75rem, 3vw, 2.75rem); font-weight: 400; color: var(--lvc-text); margin: 0; line-height: 1.15; }
.lvc-experiences__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-experiences__grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.75rem; max-width: 1600px; margin: 0 auto; }
.lvc-experiences__grid .lvc-exp-card:nth-child(1) { grid-column: 1 / -1; }
.lvc-exp-card {
	position       : relative;
	height         : 420px;
	overflow       : hidden;
	display        : flex;
	flex-direction : column;
	justify-content: flex-end;
	background-color: var(--lvc-bg3);
	border-radius  : 4px;
	border         : 1px solid var(--lvc-border);
	transition     : all 0.3s var(--lvc-ease);
}
.lvc-exp-card__bg { position: absolute; inset: 0; background-size: cover; background-position: center; transition: transform 0.6s var(--lvc-ease); z-index: 0; }
.lvc-exp-card:hover .lvc-exp-card__bg { transform: scale(1.05); }
.lvc-exp-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.5); border-color: rgba(194,129,140,0.15); }
.lvc-exp-card__overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(18,16,15,0.1) 0%, rgba(18,16,15,0.9) 100%); z-index: 1; }
.lvc-exp-card__body { position: relative; z-index: 2; padding: 2rem; }
.lvc-exp-card__label { font-family: var(--lvc-fd); font-size: 1.5rem; font-weight: 400; color: #fff; margin: 0 0 0.5rem 0; line-height: 1.15; text-shadow: 0 1px 8px rgba(0,0,0,0.5); }
.lvc-exp-card__desc { font-size: 0.82rem; color: var(--lvc-text); opacity: 0.8; line-height: 1.65; margin: 0 0 1rem 0; }
.lvc-exp-card__cta { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--lvc-accent); font-weight: 500; }

/* ── FEATURED VILLAS ───────────────────────────────────────────────────── */
.lvc-villas { padding: 6rem var(--lvc-px); background: var(--lvc-bg2); border-top: 1px solid var(--lvc-border); }
.lvc-villas__inner { max-width: 1600px; margin: 0 auto; }
.lvc-villas__header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 3rem; flex-wrap: wrap; gap: 1rem; }
.lvc-villas__title { font-family: var(--lvc-fd); font-size: clamp(1.75rem, 3vw, 2.75rem); font-weight: 400; color: var(--lvc-text); margin: 0; line-height: 1.15; }
.lvc-villas__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-villas__see-all {
	font-size     : 0.68rem;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color         : var(--lvc-soft);
	border-bottom : 1px solid var(--lvc-border-h);
	padding-bottom: 3px;
	font-weight   : 500;
	white-space   : nowrap;
	transition    : all 0.2s;
}
.lvc-villas__see-all:hover { color: var(--lvc-accent); border-color: var(--lvc-accent); }

.lvc-villa-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; align-items: stretch; }
.lvc-villa-card { background: var(--lvc-bg); border: 1px solid var(--lvc-border); overflow: hidden; display: flex; flex-direction: column; height: 100%; transition: all 0.3s var(--lvc-ease); border-radius: 4px; }
.lvc-villa-card:hover { border-color: var(--lvc-accent); transform: translateY(-4px); box-shadow: var(--lvc-shadow-h); }
.lvc-villa-card__img {
	height              : 240px;
	background-color    : var(--lvc-bg3);
	background-size     : cover;
	background-position : center;
	overflow            : hidden;
	transition          : transform 0.5s var(--lvc-ease);
	position            : relative;
	display             : flex;
	align-items         : center;
	justify-content     : center;
}
.lvc-villa-card:hover .lvc-villa-card__img { transform: scale(1.04); }
.lvc-villa-card__placeholder { font-size: 0.62rem; letter-spacing: 0.14em; text-transform: uppercase; color: var(--lvc-muted); }
.lvc-villa-card__body { padding: 1.4rem 1.5rem 1.6rem; display: flex; flex: 1; flex-direction: column; }
.lvc-villa-card__loc { font-size: 0.62rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--lvc-muted); margin: 0 0 6px 0; }
.lvc-villa-card__name { font-family: var(--lvc-fd); font-size: 1.4rem; font-weight: 400; line-height: 1.1; margin: 0 0 0.6rem 0; color: var(--lvc-text); }
.lvc-villa-card__specs { font-size: 0.75rem; color: var(--lvc-muted); margin: 0 0 1rem 0; }
.lvc-villa-card__foot { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-top: auto; padding-top: 0.85rem; border-top: 1px solid var(--lvc-border); }
.lvc-villa-card__rate { font-size: 0.75rem; color: var(--lvc-text); line-height: 1.35; }
.lvc-villa-card__cta { font-size: 0.65rem; letter-spacing: 0.1em; text-transform: uppercase; color: var(--lvc-muted); font-weight: 500; transition: color 0.2s; }
.lvc-villa-card:hover .lvc-villa-card__cta { color: var(--lvc-accent); }

/* ── WHY BOOK DIRECT ───────────────────────────────────────────────────── */
.lvc-why { position: relative; padding: 6rem var(--lvc-px); overflow: hidden; border-top: 1px solid var(--lvc-border); }
.lvc-why__bg {
	position            : absolute;
	inset               : 0;
	background-image    : url('<?php echo esc_url( $hero_image ); ?>');
	background-size     : cover;
	background-position : center top;
	opacity             : 0.4;
	z-index             : 0;
}
.lvc-why__bg-overlay { position: absolute; inset: 0; background: rgba(18,16,15,0.78); z-index: 1; }
.lvc-why__inner { position: relative; z-index: 2; max-width: 1600px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; align-items: center; }
.lvc-why__left h2 { font-family: var(--lvc-fd); font-size: clamp(2rem, 4vw, 3rem); font-weight: 400; color: #fff; line-height: 1.1; margin: 0 0 1.25rem 0; }
.lvc-why__left h2 em { font-style: italic; color: var(--lvc-accent); }
.lvc-why__left p { font-size: 0.92rem; color: rgba(255,255,255,0.55); line-height: 1.8; margin: 0 0 2rem 0; }
.lvc-why__points { display: flex; flex-direction: column; gap: 1rem; }
.lvc-why__point {
	display    : flex;
	gap        : 1.25rem;
	align-items: flex-start;
	padding    : 1.5rem;
	background : rgba(18,16,15,0.5);
	border     : 1px solid rgba(255,255,255,0.06);
	transition : border-color 0.2s;
	border-radius: 4px;
}
.lvc-why__point:hover { border-color: rgba(194,129,140,0.2); }
.lvc-why__point-icon { width: 38px; height: 38px; flex-shrink: 0; margin-top: 2px; }
.lvc-why__point-icon svg { width: 100%; height: 100%; stroke: var(--lvc-accent); fill: none; stroke-width: 1.5; }
.lvc-why__point-title { font-family: var(--lvc-fd); font-size: 1.15rem; font-weight: 400; color: #fff; margin: 0 0 0.4rem 0; line-height: 1.2; }
.lvc-why__point-text { font-size: 0.85rem; color: rgba(255,255,255,0.45); line-height: 1.7; margin: 0; }

/* ── INQUIRY / FINAL CTA ───────────────────────────────────────────────── */
.lvc-inquiry { padding: 6rem var(--lvc-px); background: var(--lvc-bg); border-top: 1px solid var(--lvc-border); }
.lvc-inquiry__inner { max-width: 1600px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; align-items: start; }
.lvc-inquiry__left h2 { font-family: var(--lvc-fd); font-size: clamp(2rem, 3.5vw, 2.75rem); font-weight: 400; line-height: 1.1; margin: 0 0 1rem 0; color: var(--lvc-text); }
.lvc-inquiry__left h2 em { font-style: italic; color: var(--lvc-accent); }
.lvc-inquiry__left p { font-size: 0.92rem; color: var(--lvc-soft); line-height: 1.8; margin: 0 0 2rem 0; }
.lvc-inquiry__trust-list { display: flex; flex-direction: column; gap: 0.75rem; }
.lvc-inquiry__trust-item { display: flex; align-items: center; gap: 10px; font-size: 0.82rem; color: var(--lvc-muted); }
.lvc-inquiry__trust-item::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: var(--lvc-accent); opacity: 0.7; flex-shrink: 0; }
.lvc-inquiry__form .lvc-form { display: flex; flex-direction: column; gap: 1.1rem; }
.lvc-inquiry__wa {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	gap            : 8px;
	padding        : 0.85rem;
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
.lvc-inquiry__wa:hover { border-color: rgba(37,211,102,0.35); color: #25D366; background: rgba(37,211,102,0.05); }

/* ── MAGAZINE ──────────────────────────────────────────────────────────── */
.lvc-magazine { padding: 5rem var(--lvc-px); background: var(--lvc-bg2); border-top: 1px solid var(--lvc-border); }
.lvc-magazine__inner { max-width: 1600px; margin: 0 auto; }
.lvc-magazine__header { text-align: center; margin-bottom: 3rem; }
.lvc-magazine__title { font-family: var(--lvc-fd); font-size: clamp(1.8rem,3vw,2.6rem); font-weight: 400; color: var(--lvc-text); }
.lvc-magazine__title em { font-style: italic; color: var(--lvc-accent); }
.lvc-magazine__grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.75rem; }
.lvc-mag-card { display: flex; flex-direction: column; background: var(--lvc-bg); border: 1px solid var(--lvc-border); border-radius: 4px; overflow: hidden; transition: transform .3s ease, border-color .3s ease; }
.lvc-mag-card:hover { transform: translateY(-4px); border-color: var(--lvc-accent); }
.lvc-mag-card__media { aspect-ratio: 3/2; background: var(--lvc-bg3); overflow: hidden; }
.lvc-mag-card__media img { width: 100%; height: 100%; object-fit: cover; display: block; }
.lvc-mag-card__body { padding: 1.5rem; }
.lvc-mag-card__title { font-size: 1.15rem; line-height: 1.4; color: var(--lvc-text); margin: 0 0 .75rem; font-weight: 500; }
.lvc-mag-card__cta { font-size: .8rem; letter-spacing: .1em; text-transform: uppercase; color: var(--lvc-accent); }
.lvc-magazine__all { text-align: center; margin-top: 2.5rem; }
.lvc-magazine__all-link { color: var(--lvc-text); border-bottom: 1px solid var(--lvc-accent); padding-bottom: 2px; }

/* ── RESPONSIVE ────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
	.lvc-search__inner  { grid-template-columns: repeat(3, minmax(0, 1fr)); }
	.lvc-search__intro  { grid-column: 1 / -1; padding-right: 0; }
	.lvc-search__submit { align-self: end; }
}
@media (max-width: 1024px) {
	.lvc-dest-grid          { grid-template-columns: 1fr; }
	.lvc-experiences__grid  { grid-template-columns: repeat(2, 1fr); }
	.lvc-experiences__grid .lvc-exp-card:nth-child(1) { grid-column: 1 / -1; }
	.lvc-villa-grid         { grid-template-columns: repeat(2, 1fr); }
	.lvc-area-grid          { grid-template-columns: repeat(2, 1fr); }
	.lvc-why__inner         { gap: 3rem; }
	.lvc-inquiry__inner     { gap: 3rem; }
}
@media (max-width: 768px) {
	.lvc-hero               { min-height: auto; }
	.lvc-hero__content      { padding: 6rem var(--lvc-px) 3rem; }
	.lvc-hero__intro        { margin-bottom: 1.5rem; }
	.lvc-hero__trust        { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; padding: 0.85rem; margin-top: 1.25rem; background: rgba(18,16,15,0.72); border: 1px solid var(--lvc-border); align-items: stretch; }
	.lvc-hero__trust-item   { border: none; padding: 0; margin: 0; text-align: center; display: flex; justify-content: center; align-items: center; font-size: 0.54rem; line-height: 1.35; }
	.lvc-hero__trust-item::before { display: none; }
	.lvc-hero__routes       { display: none; }
	.lvc-destinations,
	.lvc-experiences,
	.lvc-villas,
	.lvc-why,
	.lvc-inquiry            { padding: 4rem var(--lvc-px); }
	.lvc-search__inner      { grid-template-columns: repeat(2, minmax(0, 1fr)); }
	.lvc-search__intro      { grid-column: 1 / -1; padding-right: 0; }
	.lvc-search__submit     { align-self: end; }
	.lvc-experiences__grid  { grid-template-columns: 1fr; }
	.lvc-experiences__grid .lvc-exp-card:nth-child(1) { grid-column: auto; }
	.lvc-villa-grid         { grid-template-columns: 1fr; }
	.lvc-area-grid          { grid-template-columns: 1fr; }
	.lvc-why__inner         { grid-template-columns: 1fr; gap: 2.5rem; }
	.lvc-inquiry__inner     { grid-template-columns: 1fr; gap: 2.5rem; }
	.lvc-villas__header     { flex-direction: column; align-items: flex-start; }
	.lvc-magazine__grid     { grid-template-columns: 1fr; }
	.lvc-search             { padding-block: 1.25rem; }
	.lvc-search__inner      { grid-template-columns: 1fr; }
	.lvc-search__intro      { grid-column: auto; }
	.lvc-search__submit     { width: 100%; }
	.lvc-villa-card:nth-child(n+4) { display: none; }
}
</style>


<div class="lvc-wrap">

<!-- ═══ SECTION 1: HERO ═══ -->
<section class="lvc-hero">
	<div class="lvc-hero__bg" style="background-image: url('<?php echo esc_url( $hero_image ); ?>');"></div>
	<div class="lvc-hero__overlay"></div>
	<div class="lvc-hero__overlay-left"></div>

	<div class="lvc-hero__content">

		<p class="lvc-hero__eyebrow"><?php echo esc_html( lvc_config( 'region' ) ); ?> &middot; Caribbean</p>

		<h1 class="lvc-hero__h1">
			Luxury Villa Rentals<br>in <em><?php echo esc_html( lvc_config( 'region' ) ); ?></em>
		</h1>

		<div class="lvc-hero__divider"></div>

		<p class="lvc-hero__intro">Beachfront estates and private villas across St Barth's most beautiful bays — Flamands, Gouverneur, Saline and beyond. Book direct with personalized villa matching and concierge support from inquiry to arrival.</p>

		<div class="lvc-hero__ctas">
			<a href="#inquiry" class="lvc-btn-accent">Find the Right Villa &#x2192;</a>
			<a href="<?php echo esc_url( $archive_url ); ?>" class="lvc-btn-outline-light">Browse Villas</a>
		</div>

		<div class="lvc-hero__trust" role="list" aria-label="Direct booking proof points">
			<div class="lvc-hero__trust-item" role="listitem">Direct Booking</div>
			<div class="lvc-hero__trust-item" role="listitem">Local Concierge Support</div>
			<div class="lvc-hero__trust-item" role="listitem">Hand-Picked <?php echo esc_html( lvc_config( 'region' ) ); ?> Villas</div>
		</div>

		<div class="lvc-hero__routes" aria-label="Quick browse routes">
			<?php foreach ( array_slice( $featured_areas, 0, 2 ) as $fa ) : ?>
			<a class="lvc-hero__route" href="<?php echo esc_url( get_term_link( $fa['term'] ) ); ?>">Browse <?php echo esc_html( $fa['term']->name ); ?></a>
			<?php endforeach; ?>
			<a class="lvc-hero__route" href="#collections">Explore by Villa Size</a>
		</div>

	</div>
</section>


<!-- ═══ VILLA SEARCH ═══ -->
<form class="lvc-search" method="get" action="<?php echo esc_url( $archive_url ); ?>" aria-label="Find an <?php echo esc_html( lvc_config( 'region' ) ); ?> villa">
	<div class="lvc-search__inner">
		<div class="lvc-search__intro">
			<strong>Find your <?php echo esc_html( lvc_config( 'region' ) ); ?> villa</strong>
			<span>Add your dates once; they carry through to your villa request.</span>
		</div>
		<div class="lvc-search__field">
			<label for="lvc-home-arrival">Check-in</label>
			<input id="lvc-home-arrival" type="date" name="arrival" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
		</div>
		<div class="lvc-search__field">
			<label for="lvc-home-departure">Check-out</label>
			<input id="lvc-home-departure" type="date" name="departure" min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
		</div>
		<div class="lvc-search__field">
			<label for="lvc-home-area">Area</label>
			<select id="lvc-home-area" name="area">
				<option value="">All areas</option>
				<?php foreach ( $area_terms as $area ) : ?>
					<option value="<?php echo esc_attr( $area->slug ); ?>"><?php echo esc_html( $area->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="lvc-search__field">
			<label for="lvc-home-bedrooms">Bedrooms</label>
			<select id="lvc-home-bedrooms" name="bedrooms">
				<option value="">Any size</option>
				<?php foreach ( $size_terms as $size ) : ?>
					<option value="<?php echo esc_attr( $size->slug ); ?>"><?php echo esc_html( $size->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="lvc-search__field">
			<label for="lvc-home-guests">Guests</label>
			<select id="lvc-home-guests" name="guests">
				<option value="">Any group</option>
				<?php foreach ( array( 2, 4, 6, 8, 10, 12, 14, 16 ) as $guest_count ) : ?>
					<option value="<?php echo esc_attr( (string) $guest_count ); ?>"><?php echo esc_html( (string) $guest_count ); ?>+ guests</option>
				<?php endforeach; ?>
			</select>
		</div>
		<button type="submit" class="lvc-btn-accent lvc-search__submit">Search Villas &#x2192;</button>
	</div>
</form>

<!-- ═══ SECTION 2: FEATURED VILLAS ═══ -->
<?php if ( $featured_query->have_posts() ) : ?>
<section class="lvc-villas">
	<div class="lvc-villas__inner">

		<div class="lvc-villas__header">
			<div>
				<p class="lvc-eyebrow">The Collection</p>
				<h2 class="lvc-villas__title">Featured <em>Luxury Villas</em></h2>
			</div>
			<a href="<?php echo esc_url( $archive_url ); ?>" class="lvc-villas__see-all">
				View All <?php echo esc_html( (string) $villa_count ); ?> Villas &#x2192;
			</a>
		</div>

		<div class="lvc-villa-grid">
		<?php
		while ( $featured_query->have_posts() ) :
			$featured_query->the_post();
			$vid          = get_the_ID();
			$v_name       = get_the_title();
			$v_bedrooms   = get_post_meta( $vid, 'bed_count', true );
			$v_guests     = get_post_meta( $vid, 'guests_max', true );
			$v_bathrooms  = get_post_meta( $vid, 'bath_count', true );
			$v_rate       = function_exists( 'lvc_property_rate' ) ? lvc_property_rate( $vid ) : array( 'label' => 'Rates on request' );
			$v_area_terms = get_the_terms( $vid, 'area' );
			$v_area       = ( ! empty( $v_area_terms ) && ! is_wp_error( $v_area_terms ) ) ? $v_area_terms[0]->name : lvc_config( 'region' );
			$v_img        = function_exists( 'lvc_property_image' ) ? lvc_property_image( $vid ) : get_the_post_thumbnail_url( $vid, 'large' );
			?>
			<a href="<?php echo esc_url( get_permalink( $vid ) ); ?>" class="lvc-villa-card">
				<div class="lvc-villa-card__img"<?php echo $v_img ? ' style="background-image:url(\'' . esc_url( $v_img ) . '\')"' : ''; ?>>
					<?php if ( ! $v_img ) : ?><span class="lvc-villa-card__placeholder">Photography coming soon</span><?php endif; ?>
				</div>
				<div class="lvc-villa-card__body">
					<p class="lvc-villa-card__loc"><?php echo esc_html( $v_area ); ?>, <?php echo esc_html( lvc_config( 'region' ) ); ?></p>
					<h3 class="lvc-villa-card__name"><?php echo esc_html( $v_name ); ?></h3>
					<p class="lvc-villa-card__specs">
						<?php if ( $v_bedrooms ) : ?><?php echo esc_html( $v_bedrooms ); ?> Bed<?php endif; ?>
						<?php if ( $v_bedrooms && $v_guests ) : ?>&middot;<?php endif; ?>
						<?php if ( $v_guests ) : ?><?php echo esc_html( $v_guests ); ?> Guests<?php endif; ?>
						<?php if ( $v_bathrooms ) : ?>&middot; <?php echo esc_html( $v_bathrooms ); ?> Bath<?php endif; ?>
					</p>
					<div class="lvc-villa-card__foot">
						<span class="lvc-villa-card__rate"><?php echo esc_html( $v_rate['label'] ); ?></span>
						<span class="lvc-villa-card__cta">View Villa &#x2192;</span>
					</div>
				</div>
			</a>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
		</div>

		<div style="text-align:center;margin-top:3rem;">
			<a href="<?php echo esc_url( $archive_url ); ?>" class="lvc-btn-accent">
				Browse All <?php echo esc_html( (string) $villa_count ); ?> Villas &#x2192;
			</a>
		</div>

	</div>
</section>
<?php endif; ?>


<!-- ═══ SECTION 3: AREAS ═══ -->
<section class="lvc-destinations">
	<div class="lvc-destinations__inner">

		<div class="lvc-destinations__header">
			<p class="lvc-eyebrow">Browse by Area</p>
			<h2 class="lvc-destinations__title">
				<?php echo esc_html( lvc_config( 'region' ) ); ?>'s <em>Bays &amp; Areas</em>
			</h2>
			<p class="lvc-destinations__micro">Start with the island's signature bays, then refine by the areas guests book most for beach access, privacy, and group-friendly layouts.</p>
		</div>

		<?php if ( ! empty( $featured_areas ) ) : ?>
		<div class="lvc-dest-grid">
			<?php
			foreach ( $featured_areas as $fa ) :
				$d_url = get_term_link( $fa['term'] );
				if ( is_wp_error( $d_url ) ) {
					continue;
				}
				$d_count = (int) $fa['term']->count;
				?>
			<a href="<?php echo esc_url( $d_url ); ?>" class="lvc-dest-card">
				<div class="lvc-dest-card__bg" style="background-image:url('<?php echo esc_url( $fa['image'] ); ?>')"></div>
				<div class="lvc-dest-card__overlay"></div>
				<div class="lvc-dest-card__body">
					<p class="lvc-dest-card__count"><?php echo esc_html( (string) $d_count ); ?> <?php echo ( 1 === $d_count ) ? 'Villa' : 'Villas'; ?></p>
					<h3 class="lvc-dest-card__name"><?php echo esc_html( $fa['term']->name ); ?></h3>
					<span class="lvc-dest-card__cta">Browse <?php echo esc_html( $fa['term']->name ); ?> &#x2192;</span>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $compact_areas ) ) : ?>
		<p class="lvc-eyebrow" style="margin-bottom:1rem;">Key Areas</p>
		<div class="lvc-area-grid">
			<?php
			foreach ( $compact_areas as $area ) :
				$a_url = get_term_link( $area );
				if ( is_wp_error( $a_url ) ) {
					continue;
				}
				$a_count = (int) $area->count;
				?>
			<a href="<?php echo esc_url( $a_url ); ?>" class="lvc-area-card">
				<span class="lvc-area-card__name"><?php echo esc_html( $area->name ); ?></span>
				<span class="lvc-area-card__count"><?php echo esc_html( (string) $a_count ); ?> <?php echo ( 1 === $a_count ) ? 'Villa' : 'Villas'; ?> &#x2192;</span>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

	</div>
</section>


<!-- ═══ SECTION 4: BROWSE BY SIZE (collections) ═══ -->
<?php if ( ! empty( $size_cards ) ) : ?>
<section class="lvc-experiences">
	<a id="collections" aria-hidden="true"></a>
	<div class="lvc-experiences__inner">

		<div class="lvc-experiences__header">
			<p class="lvc-eyebrow lvc-eyebrow--center">Browse by Group Size</p>
			<h2 class="lvc-experiences__title">Find Your <em>Villa Size</em></h2>
		</div>

		<div class="lvc-experiences__grid">
			<?php
			foreach ( $size_cards as $sc ) :
				$s_url = get_term_link( $sc['term'] );
				if ( is_wp_error( $s_url ) ) {
					continue;
				}
				$s_count = (int) $sc['term']->count;
				?>
			<a href="<?php echo esc_url( $s_url ); ?>" class="lvc-exp-card">
				<?php if ( $sc['image'] ) : ?>
				<div class="lvc-exp-card__bg" style="background-image:url('<?php echo esc_url( $sc['image'] ); ?>')"></div>
				<?php endif; ?>
				<div class="lvc-exp-card__overlay"></div>
				<div class="lvc-exp-card__body">
					<h3 class="lvc-exp-card__label"><?php echo esc_html( $sc['term']->name ); ?> in <?php echo esc_html( lvc_config( 'region' ) ); ?></h3>
					<p class="lvc-exp-card__desc"><?php echo esc_html( (string) $s_count ); ?> <?php echo ( 1 === $s_count ) ? 'villa' : 'villas'; ?> at this size, from beachfront estates to private hillside retreats.</p>
					<span class="lvc-exp-card__cta">Browse &#x2192;</span>
				</div>
			</a>
			<?php endforeach; ?>
		</div>

	</div>
</section>
<?php endif; ?>


<!-- ═══ SECTION 5: WHY BOOK DIRECT ═══ -->
<section class="lvc-why">
	<div class="lvc-why__bg"></div>
	<div class="lvc-why__bg-overlay"></div>

	<div class="lvc-why__inner">

		<div class="lvc-why__left">
			<p class="lvc-eyebrow">Direct Booking</p>
			<h2>Why Book <em>Direct</em> With Us</h2>
			<p>Skip the platform fees, the automated responses, and the third-party delays. Book direct with <?php echo esc_html( lvc_config( 'brand_name' ) ); ?> and work with a concierge team that knows every property personally.</p>

			<div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:0.5rem;">
				<a href="#inquiry" class="lvc-btn-accent">Start an Inquiry &#x2192;</a>
				<?php if ( $whatsapp ) : ?>
				<a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-btn-outline-light">WhatsApp Us</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="lvc-why__points">
			<div class="lvc-why__point">
				<div class="lvc-why__point-icon">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>
				</div>
				<div>
					<p class="lvc-why__point-title">No Platform Markups</p>
					<p class="lvc-why__point-text">Book direct and skip the fees third-party sites add on top of the villa rate.</p>
				</div>
			</div>
			<div class="lvc-why__point">
				<div class="lvc-why__point-icon">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11l9-7 9 7"/><path d="M5 10v9h14v-9"/><path d="M10 19v-6h4v6"/></svg>
				</div>
				<div>
					<p class="lvc-why__point-title">Local Villa Matching</p>
					<p class="lvc-why__point-text">Our team knows every property personally and shortlists the right fit for your group.</p>
				</div>
			</div>
			<div class="lvc-why__point">
				<div class="lvc-why__point-icon">
					<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18"/><path d="M8 3v4"/><path d="M16 3v4"/></svg>
				</div>
				<div>
					<p class="lvc-why__point-title">Direct Availability Guidance</p>
					<p class="lvc-why__point-text">Clear windows and pricing guidance up front — no waiting on a booking platform.</p>
				</div>
			</div>
		</div>

	</div>
</section>

<!-- ═══ SECTION 6: INQUIRY ═══ -->
<section class="lvc-inquiry" id="inquiry">
	<div class="lvc-inquiry__inner">

		<div class="lvc-inquiry__left">
			<p class="lvc-eyebrow">Concierge-Led Matching</p>
			<h2>Tell Us Your Trip. We&rsquo;ll Match the <em>Right Villas</em></h2>
			<p>Share your dates, group size, and travel style. Our concierge team shortlists the best-fit villas across <?php echo esc_html( lvc_config( 'region' ) ); ?> faster than manual browsing.</p>
			<div class="lvc-inquiry__trust-list">
				<div class="lvc-inquiry__trust-item">Concierge-shortlisted villa options based on your group and budget</div>
				<div class="lvc-inquiry__trust-item">Clear availability windows and pricing guidance up front</div>
				<div class="lvc-inquiry__trust-item">Direct booking flow with no platform markups</div>
				<div class="lvc-inquiry__trust-item">Local coordination support before arrival and during stay</div>
			</div>
		</div>

		<div class="lvc-inquiry__form">
			<?php get_template_part( 'template-parts/inquiry-form', null, array( 'property_name' => 'Homepage General Inquiry' ) ); ?>
			<?php if ( $whatsapp ) : ?>
			<a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-inquiry__wa">Or WhatsApp Us Directly</a>
			<?php endif; ?>
		</div>

	</div>
</section>

<!-- ═══ SECTION 7: FROM THE MAGAZINE ═══ -->
<?php
$lvc_guides = get_posts(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 3,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
if ( $lvc_guides ) :
	?>
<section class="lvc-magazine">
	<div class="lvc-magazine__inner">
		<div class="lvc-magazine__header">
			<p class="lvc-eyebrow lvc-eyebrow--center">Plan Your Trip</p>
			<h2 class="lvc-magazine__title"><?php echo esc_html( lvc_config( 'region' ) ); ?> Villa <em>Guides</em></h2>
		</div>
		<div class="lvc-magazine__grid">
			<?php
			foreach ( $lvc_guides as $g ) :
				$img = function_exists( 'lvc_blog_image_url' ) ? lvc_blog_image_url( $g->ID, 'large' ) : get_the_post_thumbnail_url( $g->ID, 'large' );
				?>
			<a href="<?php echo esc_url( get_permalink( $g->ID ) ); ?>" class="lvc-mag-card">
				<div class="lvc-mag-card__media">
					<?php if ( $img ) : ?>
					<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( get_the_title( $g->ID ) ); ?>" loading="lazy" width="600" height="400">
					<?php endif; ?>
				</div>
				<div class="lvc-mag-card__body">
					<h3 class="lvc-mag-card__title"><?php echo esc_html( get_the_title( $g->ID ) ); ?></h3>
					<span class="lvc-mag-card__cta">Read guide &#x2192;</span>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
		<p class="lvc-magazine__all">
			<a href="<?php echo esc_url( lvc_page_url( 'magazine' ) ); ?>" class="lvc-magazine__all-link">Browse all <?php echo esc_html( lvc_config( 'region' ) ); ?> villa guides &#x2192;</a>
		</p>
	</div>
</section>
<?php endif; ?>

</div><!-- .lvc-wrap -->

<script>
(function(){
	var finder = document.querySelector('.lvc-search');
	if (finder) {
		var arrival = finder.querySelector('[name="arrival"]');
		var departure = finder.querySelector('[name="departure"]');
		var syncDates = function () {
			departure.min = arrival.value || arrival.min;
			if (departure.value && departure.value <= arrival.value) departure.value = '';
		};
		arrival.addEventListener('change', syncDates);
		finder.addEventListener('submit', function (event) {
			arrival.setCustomValidity('');
			departure.setCustomValidity('');
			if (!!arrival.value !== !!departure.value) {
				var missing = arrival.value ? departure : arrival;
				missing.setCustomValidity('Please select both check-in and check-out dates.');
				missing.reportValidity();
				event.preventDefault();
			} else if (arrival.value && departure.value <= arrival.value) {
				departure.setCustomValidity('Check-out must be after check-in.');
				departure.reportValidity();
				event.preventDefault();
			}
		});
	}
	document.querySelectorAll('a[href^="#"]').forEach(function(link){
		link.addEventListener('click', function(e){
			var href = this.getAttribute('href');
			if (href.length > 1) {
				var target = document.querySelector(href);
				if (target) {
					e.preventDefault();
					target.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			}
		});
	});
})();
</script>

<?php get_footer(); ?>

