<?php
/**
 * Anguilla Beach Luxury Villas â€” Villa CPT Archive.
 *
 * Cloned from tulumholidayvillas' archive-villa.php (v1.0 dark theme) per the
 * clone-don't-rebuild rule: same sections, markup and CSS, recolored to the
 * Shoal Bay palette and re-pointed at this site's data model. Class prefix
 * lvc- replaces thva-.
 *
 * Differences from the THV source, on purpose:
 * - Uses the MAIN query: inc/template-router.php already applies the sanitized
 *   ?area=&bedrooms= GET filters via pre_get_posts, so no custom WP_Query here.
 * - Filter bar reduced to the two taxonomies that exist on this site
 *   (area + bedrooms â€” destination/beach_access/amenity/ideal_for do not).
 * - THV's sort dropdown dropped: nothing in the router handles an orderby
 *   param, and dead UI is worse than no UI.
 * - No inline JSON-LD â€” inc/seo/schema.php owns all schema (lvc_schema_collection).
 * - Card image via lvc_property_image() with the "Photography coming soon"
 *   placeholder (most villas have no photos yet; expected, not a bug).
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// READ FILTER PARAMS (display only â€” the router applies them to the query).
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter form; values sanitized below.
$f_area     = isset( $_GET['area'] ) ? sanitize_title( wp_unslash( $_GET['area'] ) ) : '';
$f_bedrooms = isset( $_GET['bedrooms'] ) ? sanitize_title( wp_unslash( $_GET['bedrooms'] ) ) : '';
$f_guests   = ! empty( $_GET['guests'] ) ? min( 30, max( 1, absint( $_GET['guests'] ) ) ) : 0;
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$f_trip      = function_exists( 'lvc_trip_context' ) ? lvc_trip_context() : array();
$f_arrival   = ! empty( $f_trip['dates_valid'] ) ? $f_trip['arrival'] : '';
$f_departure = ! empty( $f_trip['dates_valid'] ) ? $f_trip['departure'] : '';
$f_today     = current_time( 'Y-m-d' );

$current_page = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$total_found  = (int) $GLOBALS['wp_query']->found_posts;

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// FILTER DROPDOWN OPTIONS â€” only the taxonomies this site actually has.
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
$area_terms = get_terms(
	array(
		'taxonomy'   => 'area',
		'hide_empty' => true,
	)
);
if ( is_wp_error( $area_terms ) ) {
	$area_terms = array();
}

$bedroom_terms = get_terms(
	array(
		'taxonomy'   => 'bedrooms',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
if ( is_wp_error( $bedroom_terms ) ) {
	$bedroom_terms = array();
}

$active_filter_count = ( $f_area ? 1 : 0 ) + ( $f_bedrooms ? 1 : 0 ) + ( $f_guests ? 1 : 0 ) + ( $f_arrival ? 1 : 0 );

$archive_url = lvc_archive_url();
$whatsapp    = (string) lvc_config( 'whatsapp_url', '' );

// Hero image â€” same single source as the homepage hero (ACF on the front page),
// with the same live-image fallback. No THV stock imagery.
$hero_image = lvc_priority_image_url( (string) lvc_field( 'home_hero_image_url', (int) get_option( 'page_on_front' ) ) );
if ( '' === $hero_image ) {
	$hero_image = ''; // No brand hero yet — sections guard on empty and fall back to a dark surface.
}

if ( function_exists( 'lvc_schema_collection' ) ) {
	lvc_schema_collection();
}
?>

<?php if ( '' !== (string) $hero_image ) : ?>
<link rel="preload" as="image" href="<?php echo esc_url( $hero_image ); ?>" fetchpriority="high">
<?php endif; ?>

<style>
/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
	VILLA ARCHIVE STYLES â€” ANGUILLA BEACH LUXURY VILLAS (cloned from THV v1.0)
	Namespace: lvc-* . St Barts palette: volcanic basalt ground, salt-rose accent.
	â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

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
	--lvc-shadow    : 0 4px 24px rgba(0,0,0,0.5);
	--lvc-shadow-h  : 0 8px 32px rgba(0,0,0,0.7);
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
	width        : 100%;
}
.lvc-wrap a { text-decoration: none; color: inherit; }

.lvc-eyebrow {
	display       : inline-flex;
	align-items   : center;
	gap           : 10px;
	font-size     : 0.68rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	margin-bottom : 0.85rem;
	font-weight   : 500;
}
.lvc-eyebrow::before {
	content   : '';
	width     : 18px; height: 1px;
	background: var(--lvc-accent);
	opacity   : 0.5;
	flex-shrink: 0;
}

/* HERO */
.lvc-hero {
	position      : relative;
	padding       : 7rem var(--lvc-px) 5rem;
	min-height    : clamp(680px, 84vh, 920px);
	display       : flex;
	align-items   : flex-end;
	border-bottom : 1px solid var(--lvc-border);
	background    : var(--lvc-bg);
	overflow      : hidden;
	isolation     : isolate;
}
.lvc-hero::before {
	content       : '';
	position      : absolute;
	inset         : 0;
	background-image: var(--lvc-hero-img, none);
	background-size : cover;
	background-position: center 35%;
	z-index       : -2;
	opacity       : 0.55;
}
.lvc-hero::after {
	content       : '';
	position      : absolute;
	inset         : 0;
	background    : linear-gradient(180deg, rgba(18,16,15,0.72) 0%, rgba(18,16,15,0.82) 50%, var(--lvc-bg) 100%);
	z-index       : -1;
}
.lvc-hero__inner { max-width: 1600px; margin: 0 auto; position: relative; width: 100%; }
.lvc-hero__breadcrumb {
	font-size     : 0.7rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
	margin-bottom : 1.25rem;
}
.lvc-hero__breadcrumb a { color: var(--lvc-muted); transition: color 180ms var(--lvc-ease); }
.lvc-hero__breadcrumb a:hover { color: var(--lvc-accent); }
.lvc-hero__breadcrumb span { margin: 0 8px; opacity: 0.4; }

.lvc-hero h1 {
	font-family : var(--lvc-fd);
	font-weight : 400;
	font-size   : clamp(2.25rem, 5vw, 3.75rem);
	line-height : 1.08;
	margin      : 0 0 1rem 0;
	color       : var(--lvc-text);
}
.lvc-hero h1 em { font-style: italic; color: var(--lvc-accent); }
.lvc-hero__lede {
	max-width  : 720px;
	font-size  : clamp(0.95rem, 1.15vw, 1.05rem);
	color      : var(--lvc-soft);
	line-height: 1.7;
}
.lvc-hero__summary {
	display       : inline-flex;
	align-items   : center;
	gap           : 14px;
	margin-top    : 1.75rem;
	padding       : 0.65rem 1.1rem;
	border        : 1px solid var(--lvc-border);
	border-radius : 999px;
	font-size     : 0.82rem;
	color         : var(--lvc-soft);
	background    : rgba(255,255,255,0.02);
}
.lvc-hero__summary strong { color: var(--lvc-accent); font-weight: 500; }

/* FILTER BAR */
.lvc-filters {
	padding    : 2rem var(--lvc-px);
	background : rgba(26,23,21,0.92);
	border-bottom: 1px solid var(--lvc-border);
	position   : sticky;
	top        : 0;
	z-index    : 50;
	backdrop-filter: blur(16px) saturate(1.2);
	-webkit-backdrop-filter: blur(16px) saturate(1.2);
}
.lvc-filters__inner {
	max-width: 1600px;
	margin   : 0 auto;
	display  : grid;
	grid-template-columns: repeat(5, minmax(0, 1fr)) auto auto;
	gap      : 0.75rem 0.85rem;
	align-items: end;
}
.lvc-filter-group { display: flex; flex-direction: column; gap: 6px; }
.lvc-filter-group label {
	font-size     : 0.65rem;
	letter-spacing: 0.15em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
	font-weight   : 500;
}
.lvc-filter-group select,
.lvc-filter-group input {
	appearance    : none;
	-webkit-appearance: none;
	background    : var(--lvc-bg);
	border        : 1px solid var(--lvc-border);
	color         : var(--lvc-text);
	font-family   : var(--lvc-fb);
	font-size     : 0.85rem;
	font-weight   : 400;
	padding       : 0.75rem 2.25rem 0.75rem 0.95rem;
	border-radius : 4px;
	cursor        : pointer;
	transition    : border-color 180ms var(--lvc-ease);
	background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'><path fill='%237cc8c0' d='M6 8L0 0h12z'/></svg>");
	background-repeat: no-repeat;
	background-position: right 0.85rem center;
}
.lvc-filter-group input {
	appearance: auto;
	-webkit-appearance: auto;
	min-height: 44px;
	padding-right: 0.75rem;
	background-image: none;
	color-scheme: dark;
}
.lvc-filter-group select:hover,
.lvc-filter-group select:focus,
.lvc-filter-group input:hover,
.lvc-filter-group input:focus {
	border-color: var(--lvc-border-h);
	outline     : none;
}
.lvc-filter-group select option { color: var(--lvc-text); background: var(--lvc-bg); }
.lvc-filters__apply { min-height: 44px; align-self: end; padding: 0.75rem 1rem; white-space: nowrap; }

.lvc-filters__clear {
	align-self    : end;
	display       : inline-flex;
	align-items   : center;
	gap           : 6px;
	padding       : 0.75rem 0.9rem;
	font-family   : var(--lvc-fb);
	font-size     : 0.7rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color         : var(--lvc-soft);
	background    : transparent;
	border        : 1px solid var(--lvc-border);
	border-radius : 4px;
	cursor        : pointer;
	transition    : all 180ms var(--lvc-ease);
	text-decoration: none;
}
.lvc-filters__clear:hover {
	color       : var(--lvc-text);
	border-color: var(--lvc-accent);
}
.lvc-filters__clear[aria-disabled="true"] {
	opacity: 0.35;
	pointer-events: none;
}

/* RESULT BAR */
.lvc-results-bar {
	max-width: 1600px;
	margin   : 0 auto;
	padding  : 1.5rem var(--lvc-px) 0;
	display  : flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap      : 1rem;
}
.lvc-results-bar__count {
	font-size: 0.85rem;
	color    : var(--lvc-soft);
}
.lvc-results-bar__count strong {
	color      : var(--lvc-text);
	font-weight: 500;
}

/* VILLA GRID */
.lvc-grid {
	max-width: 1600px;
	margin   : 0 auto;
	padding  : 2rem var(--lvc-px) 4rem;
	display  : grid;
	grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
	gap      : 1.75rem;
}
.lvc-card {
	display      : flex;
	flex-direction: column;
	background   : var(--lvc-card);
	border       : 1px solid var(--lvc-border);
	border-radius: 6px;
	overflow     : hidden;
	transition   : transform 260ms var(--lvc-ease), border-color 260ms var(--lvc-ease), box-shadow 260ms var(--lvc-ease);
	box-shadow   : var(--lvc-shadow);
}
.lvc-card:hover {
	transform    : translateY(-4px);
	border-color : var(--lvc-border-h);
	box-shadow   : var(--lvc-shadow-h), 0 0 0 1px rgba(194,129,140,0.08);
}
.lvc-card__img {
	position      : relative;
	aspect-ratio  : 4/3;
	background-color: var(--lvc-bg);
	background-size: cover;
	background-position: center;
	display       : flex;
	align-items   : center;
	justify-content: center;
}
.lvc-card__placeholder {
	font-size     : 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-muted);
}
.lvc-card__body { padding: 1.2rem 1.35rem 1.35rem; flex: 1; display: flex; flex-direction: column; }
.lvc-card__loc {
	font-size     : 0.68rem;
	letter-spacing: 0.15em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	margin-bottom : 0.5rem;
	font-weight   : 500;
}
.lvc-card__name {
	font-family : var(--lvc-fd);
	font-weight : 400;
	font-size   : 1.35rem;
	line-height : 1.2;
	color       : var(--lvc-text);
	margin-bottom: 0.4rem;
}
.lvc-card__specs {
	font-size: 0.82rem;
	color    : var(--lvc-soft);
	margin-bottom: 1rem;
}
.lvc-card__foot {
	margin-top: auto;
	padding-top: 0.85rem;
	border-top: 1px solid var(--lvc-border);
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.lvc-card__rate {
	font-size: 0.78rem;
	color: var(--lvc-text);
	line-height: 1.35;
}
.lvc-card__cta {
	font-size     : 0.72rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	font-weight   : 500;
}

/* EMPTY STATE */
.lvc-empty {
	max-width  : 640px;
	margin     : 4rem auto;
	padding    : 3rem var(--lvc-px);
	text-align : center;
}
.lvc-empty h2 {
	font-family: var(--lvc-fd);
	font-weight: 400;
	font-size  : 2rem;
	line-height: 1.2;
	margin-bottom: 1rem;
	color      : var(--lvc-text);
}
.lvc-empty h2 em { font-style: italic; color: var(--lvc-accent); }
.lvc-empty p {
	color      : var(--lvc-soft);
	font-size  : 0.95rem;
	line-height: 1.7;
	margin-bottom: 1.75rem;
}

/* PAGINATION */
.lvc-pagination {
	max-width: 1600px;
	margin   : 0 auto 4rem;
	padding  : 0 var(--lvc-px);
	display  : flex;
	justify-content: center;
	align-items: center;
	gap      : 0.4rem;
	flex-wrap: wrap;
}
.lvc-pagination a,
.lvc-pagination span.page-numbers {
	display       : inline-flex;
	align-items   : center;
	justify-content: center;
	min-width     : 40px;
	height        : 40px;
	padding       : 0 0.9rem;
	font-size     : 0.82rem;
	color         : var(--lvc-soft);
	border        : 1px solid var(--lvc-border);
	border-radius : 4px;
	transition    : all 180ms var(--lvc-ease);
	font-family   : var(--lvc-fb);
}
.lvc-pagination a:hover {
	color       : var(--lvc-text);
	border-color: var(--lvc-accent);
}
.lvc-pagination span.current {
	background  : var(--lvc-accent);
	border-color: var(--lvc-accent);
	color       : #12100f;
	font-weight : 500;
}
.lvc-pagination .dots { border: none; color: var(--lvc-muted); }

/* FINAL CTA */
.lvc-final {
	padding    : 5rem var(--lvc-px);
	background : linear-gradient(180deg, var(--lvc-bg) 0%, var(--lvc-bg2) 100%);
	text-align : center;
	border-top : 1px solid var(--lvc-border);
}
.lvc-final__inner { max-width: 1600px; margin: 0 auto; }
.lvc-final h2 {
	font-family: var(--lvc-fd);
	font-weight: 400;
	font-size  : clamp(2rem, 3.5vw, 2.75rem);
	line-height: 1.1;
	margin     : 0 0 1rem 0;
	color      : var(--lvc-text);
}
.lvc-final h2 em { font-style: italic; color: var(--lvc-accent); }
.lvc-final p {
	font-size  : 0.95rem;
	color      : var(--lvc-soft);
	line-height: 1.8;
	margin     : 0 0 2rem 0;
}
.lvc-final__actions {
	display       : inline-flex;
	flex-wrap     : wrap;
	gap           : 1rem;
	justify-content: center;
}
.lvc-btn-accent,
.lvc-btn-ghost {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	gap            : 0.5rem;
	padding        : 1rem 2rem;
	font-family    : var(--lvc-fb);
	font-size      : 0.72rem;
	font-weight    : 500;
	letter-spacing : 0.14em;
	text-transform : uppercase;
	border-radius  : 3px;
	cursor         : pointer;
	transition     : all 200ms var(--lvc-ease);
	border         : 1px solid transparent;
}
.lvc-btn-accent { background: var(--lvc-accent); color: #12100f; }
.lvc-btn-accent:hover { background: var(--lvc-accent-h); transform: translateY(-2px); box-shadow: 0 6px 24px rgba(194,129,140,0.3); }
.lvc-btn-ghost {
	background: transparent;
	color     : var(--lvc-text);
	border    : 1px solid var(--lvc-border);
}
.lvc-btn-ghost:hover { border-color: var(--lvc-accent); color: var(--lvc-accent); }

/* RESPONSIVE */
@media (max-width: 1200px) {
	.lvc-filters__inner { grid-template-columns: repeat(3, minmax(0, 1fr)); }
	.lvc-filters__apply,
	.lvc-filters__clear { justify-self: stretch; justify-content: center; }
}
@media (max-width: 768px) {
	.lvc-hero     { padding: 3.5rem var(--lvc-px) 2.5rem; }
	.lvc-filters  { position: static; }
	.lvc-filters__inner { grid-template-columns: repeat(2, 1fr); }
	.lvc-filters__apply,
	.lvc-filters__clear { justify-self: stretch; justify-content: center; text-align: center; }
	.lvc-grid     { grid-template-columns: 1fr; gap: 1.25rem; padding: 1.5rem var(--lvc-px) 3rem; }
	.lvc-results-bar { padding: 1.25rem var(--lvc-px) 0; }
	.lvc-final    { padding: 3.5rem var(--lvc-px); }
}
@media (max-width: 480px) {
	.lvc-filters__inner { grid-template-columns: 1fr; }
}
</style>

<div class="lvc-wrap">

	<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• HERO â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
	<section class="lvc-hero" style="--lvc-hero-img: url('<?php echo esc_url( $hero_image ); ?>');">
		<div class="lvc-hero__inner">
			<nav class="lvc-hero__breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span>&rsaquo;</span>
				<span>All Villas</span>
			</nav>
			<p class="lvc-eyebrow">Browse the Collection</p>
			<h1>All <em>Luxury Villas</em></h1>
			<p class="lvc-hero__lede">Filter the full portfolio by travel dates, area, villa size, and guests. Every villa is hand-picked, with direct booking, no platform markups, and concierge support from inquiry to arrival.</p>
			<div class="lvc-hero__summary">
				Showing <strong><?php echo esc_html( $total_found ); ?></strong> villa<?php echo ( 1 === $total_found ) ? '' : 's'; ?>
				<?php if ( $active_filter_count > 0 ) : ?>
					&middot; <?php echo esc_html( $active_filter_count ); ?> filter<?php echo ( 1 === $active_filter_count ) ? '' : 's'; ?> active
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• FILTER BAR â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
	<form class="lvc-filters" method="get" action="<?php echo esc_url( $archive_url ); ?>" id="lvc-filter-form">
		<div class="lvc-filters__inner">
			<div class="lvc-filter-group">
				<label for="lvc-filter-arrival">Check-in</label>
				<input id="lvc-filter-arrival" type="date" name="arrival" min="<?php echo esc_attr( $f_today ); ?>" value="<?php echo esc_attr( $f_arrival ); ?>">
			</div>

			<div class="lvc-filter-group">
				<label for="lvc-filter-departure">Check-out</label>
				<input id="lvc-filter-departure" type="date" name="departure" min="<?php echo esc_attr( $f_arrival ?: $f_today ); ?>" value="<?php echo esc_attr( $f_departure ); ?>">
			</div>

			<div class="lvc-filter-group">
				<label for="lvc-filter-area">Area</label>
				<select id="lvc-filter-area" name="area">
					<option value="">All areas</option>
					<?php foreach ( $area_terms as $at ) : ?>
						<option value="<?php echo esc_attr( $at->slug ); ?>" <?php selected( $f_area, $at->slug ); ?>>
							<?php echo esc_html( $at->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="lvc-filter-group">
				<label for="lvc-filter-bedrooms">Bedrooms</label>
				<select id="lvc-filter-bedrooms" name="bedrooms">
					<option value="">Any size</option>
					<?php foreach ( $bedroom_terms as $bt ) : ?>
						<option value="<?php echo esc_attr( $bt->slug ); ?>" <?php selected( $f_bedrooms, $bt->slug ); ?>>
							<?php echo esc_html( $bt->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>


			<div class="lvc-filter-group">
				<label for="lvc-filter-guests">Guests</label>
				<select id="lvc-filter-guests" name="guests">
					<option value="">Any group</option>
					<?php foreach ( array( 2, 4, 6, 8, 10, 12, 14, 16 ) as $guest_count ) : ?>
						<option value="<?php echo esc_attr( (string) $guest_count ); ?>" <?php selected( $f_guests, $guest_count ); ?>>
							<?php echo esc_html( (string) $guest_count ); ?>+ guests
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<button type="submit" class="lvc-btn-accent lvc-filters__apply">Update results</button>

			<?php if ( $active_filter_count > 0 ) : ?>
				<a class="lvc-filters__clear" href="<?php echo esc_url( $archive_url ); ?>">Clear filters &times;</a>
			<?php else : ?>
				<span class="lvc-filters__clear" aria-disabled="true">No filters active</span>
			<?php endif; ?>

		</div>
	</form>

	<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• RESULTS BAR â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
	<div class="lvc-results-bar">
		<div class="lvc-results-bar__count">
			<strong><?php echo esc_html( $total_found ); ?></strong> villa<?php echo ( 1 === $total_found ) ? '' : 's'; ?> match<?php echo ( 1 === $total_found ) ? 'es' : ''; ?>
			<?php if ( $f_arrival ) : ?>
				&middot; Trip dates will be pre-filled; live availability is confirmed by our concierge.
			<?php endif; ?>
		</div>
	</div>

	<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• VILLA GRID (main query â€” router-filtered) â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
	<?php if ( have_posts() ) : ?>

		<div class="lvc-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				$vid         = get_the_ID();
				$v_name      = get_the_title( $vid );
				$v_bedrooms  = get_post_meta( $vid, 'bed_count', true );
				$v_guests    = get_post_meta( $vid, 'guests_max', true );
				$v_bathrooms = get_post_meta( $vid, 'bath_count', true );
				$v_rate      = function_exists( 'lvc_property_rate' ) ? lvc_property_rate( $vid ) : array( 'label' => 'Rates on request' );

				$v_area_terms = get_the_terms( $vid, 'area' );
				$v_area       = ( ! empty( $v_area_terms ) && ! is_wp_error( $v_area_terms ) ) ? $v_area_terms[0]->name : '';
				$loc_label    = $v_area ? $v_area . ', ' . lvc_config( 'region' ) : lvc_config( 'region' );

				// Card image â€” deliberately NO gallery fallback; placeholder is expected.
				$v_thumb     = function_exists( 'lvc_property_image' ) ? lvc_property_image( $vid ) : get_the_post_thumbnail_url( $vid, 'large' );
				$v_img_style = ( $v_thumb ? "background-image:url('" . esc_url( $v_thumb ) . "');" : '' );
				?>
				<a href="<?php echo esc_url( lvc_trip_url( get_permalink( $vid ) ) ); ?>" class="lvc-card">
					<div class="lvc-card__img" style="<?php echo esc_attr( $v_img_style ); ?>">
						<?php if ( ! $v_thumb ) : ?>
							<span class="lvc-card__placeholder">Photography coming soon</span>
						<?php endif; ?>
					</div>
					<div class="lvc-card__body">
						<p class="lvc-card__loc"><?php echo esc_html( $loc_label ); ?></p>
						<h3 class="lvc-card__name"><?php echo esc_html( $v_name ); ?></h3>
						<p class="lvc-card__specs">
							<?php
							$v_spec_parts = array();
							if ( $v_bedrooms ) {
								$v_spec_parts[] = esc_html( $v_bedrooms ) . ' Bedrooms';
							}
							if ( $v_guests ) {
								$v_spec_parts[] = esc_html( $v_guests ) . ' Guests';
							}
							if ( $v_bathrooms ) {
								$v_spec_parts[] = esc_html( $v_bathrooms ) . ' Baths';
							}
							echo implode( ' &middot; ', $v_spec_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts escaped above.
							?>
						</p>
						<div class="lvc-card__foot">
							<span class="lvc-card__rate"><?php echo esc_html( $v_rate['label'] ); ?></span>
							<span class="lvc-card__cta">View Villa &#x2192;</span>
						</div>
					</div>
				</a>
				<?php
			endwhile;
			?>
		</div>

		<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PAGINATION (main query; GET filters carry via get_pagenum_link) â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
		<?php
		$pagination_links = paginate_links(
			array(
				'current'   => $current_page,
				'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
				'prev_text' => '&#x2190; Prev',
				'next_text' => 'Next &#x2192;',
				'type'      => 'array',
				'end_size'  => 1,
				'mid_size'  => 2,
				'add_args'  => array_filter(
					array(
						'area'      => $f_area,
						'bedrooms'  => $f_bedrooms,
						'guests'    => $f_guests,
						'arrival'   => $f_arrival,
						'departure' => $f_departure,
					)
				),
			)
		);
		if ( $pagination_links ) :
			?>
			<nav class="lvc-pagination" aria-label="Villa pages">
				<?php
				foreach ( $pagination_links as $page_link ) {
					echo wp_kses_post( $page_link );
				}
				?>
			</nav>
		<?php endif; ?>

	<?php else : ?>

		<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• EMPTY STATE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
		<div class="lvc-empty">
			<h2>No villas match your <em>filters</em></h2>
			<p>Try clearing a filter, or broaden your selection. You can also speak with our concierge team &mdash; they&rsquo;ll match you with a villa that fits what you&rsquo;re looking for, even if it&rsquo;s not in the current view.</p>
			<div class="lvc-final__actions">
				<a href="<?php echo esc_url( $archive_url ); ?>" class="lvc-btn-accent">View all villas &rarr;</a>
				<?php if ( $whatsapp ) : ?>
					<a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-btn-ghost">WhatsApp Concierge</a>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>

	<?php // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• FINAL CTA â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• ?>
	<section class="lvc-final">
		<div class="lvc-final__inner">
			<p class="lvc-eyebrow">Concierge Matching</p>
			<h2>Can&rsquo;t find what you&rsquo;re <em>looking for?</em></h2>
			<p>Our team knows every villa in the collection personally. Tell us what matters most &mdash; dates, group size, beach, style &mdash; and we&rsquo;ll hand-pick the right fit for your group.</p>
			<div class="lvc-final__actions">
				<a href="<?php echo esc_url( lvc_trip_url( lvc_page_url( 'request' ) ) ); ?>" class="lvc-btn-accent">Start a concierge inquiry &rarr;</a>
				<?php if ( $whatsapp ) : ?>
					<a href="<?php echo esc_url( $whatsapp ); ?>" target="_blank" rel="noopener" class="lvc-btn-ghost">WhatsApp us directly</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

</div><!-- .lvc-wrap -->

<script>
(function () {
	var form = document.getElementById('lvc-filter-form');
	if (!form) return;
	var arrival = form.querySelector('[name="arrival"]');
	var departure = form.querySelector('[name="departure"]');
	var syncDates = function () {
		departure.min = arrival.value || arrival.min;
		if (departure.value && departure.value <= arrival.value) departure.value = '';
	};
	arrival.addEventListener('change', syncDates);
	form.addEventListener('submit', function (event) {
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
})();
</script>

<?php
get_footer();


