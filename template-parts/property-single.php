<?php
/**
 * Single property — cloned from tulumholidayvillas' single-villa-legacy.php
 * (the real live THV villa template) per the clone-don't-rebuild rule: same
 * section flow, markup and CSS, recolored to the St Barts salt-rose palette and
 * re-pointed at this site's data model. Class prefix lvc- replaces thv-.
 *
 * THV's template is a standalone HTML document with its own header/footer;
 * here it renders inside the theme wrapper (get_header/get_footer) because
 * inc/template-router.php routes singular villa views to this file.
 *
 * Anguilla plumbing preserved (do not regress):
 *  - lvc_content() four-tier copy resolution + lvc_property_faq()/lvc_list_lines()
 *  - dual gallery via lvc_gallery_urls() (splits commas AND newlines)
 *  - inquiry via template-parts/inquiry-form (AJAX handler + Turnstile + honeypot)
 *  - schema via lvc_schema_property() — inc/seo/schema.php owns ALL JSON-LD,
 *    so THV's inline schema block is deliberately not ported
 *  - graceful no-image hero (38/50 villas have dead photo URLs)
 *  - sticky mobile "Request Availability" bar (.lvc-single__mobilebar, brand.css)
 *
 * Adaptations from THV, per the port contract:
 *  - breadcrumb archive crumb uses lvc_archive_url() (fixes THV's known
 *    hardcoded-'/luxury-villas/' breadcrumb bug — same URL here, but resolved
 *    from theme-config.php rather than hardcoded)
 *  - THV sections with no Anguilla data are dropped: beach_access strip stat,
 *    amenity-taxonomy grid, hardcoded brand reviews, concierge-services grid,
 *    4.9-rating hero badge
 *  - included/on-request lists use dot bullets (portfolio standard), not checks
 *  - primary CTA label is "Request Availability" (never "Inquire/Book Now")
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	// ═══════════════════════════════════════════════════════════════════════
	// DATA — THV field names mapped to this site's data layer.
	// ═══════════════════════════════════════════════════════════════════════
	$lvc_id    = get_the_ID();
	$lvc_title = get_the_title();
	$lvc_h1    = lvc_field( 'h1_title', $lvc_id, $lvc_title ); // THV: h1_title (same name here).

	// Display name for visible headings/alt text: imported post titles can carry
	// an SEO tail ("Villa Name | {area-slug}, Anguilla") — never print a pipe or
	// a raw slug into visible copy. Location, when needed, comes from the place
	// term's ->name ($lvc_area below), not from the title tail.
	$lvc_display = trim( (string) strtok( $lvc_title, '|' ) );
	if ( '' === $lvc_display ) {
		$lvc_display = $lvc_title;
	}

	$lvc_term     = lvc_primary_place_term( $lvc_id );
	$lvc_area     = $lvc_term instanceof WP_Term ? $lvc_term->name : '';
	$lvc_area_url = '';
	if ( $lvc_term instanceof WP_Term ) {
		$lvc_maybe_url = get_term_link( $lvc_term );
		if ( ! is_wp_error( $lvc_maybe_url ) ) {
			$lvc_area_url = $lvc_maybe_url;
		}
	}

	// Archive crumb — resolved, never hardcoded (THV breadcrumb bug fix).
	$lvc_archive_url = lvc_archive_url();

	// Facts. THV: bedrooms/bathrooms/max_guests → theme-core names.
	$lvc_beds   = lvc_field( 'bed_count', $lvc_id );
	$lvc_baths  = lvc_field( 'bath_count', $lvc_id );
	$lvc_guests = lvc_field( 'guests_max', $lvc_id );
	$lvc_rate   = function_exists( 'lvc_property_rate' ) ? lvc_property_rate( $lvc_id ) : array( 'amount' => 0, 'label' => 'Rates on request', 'minimum_stay' => 0, 'note' => 'Contact us for current rates and availability.' );

	// Editorial — four-tier fallback so nothing renders blank.
	$lvc_tagline = lvc_content( 'tagline', 'tagline', 'universal_tagline', $lvc_id, $lvc_term );          // THV: bridge_line.
	$lvc_intro   = lvc_content( 'intro_paragraph', 'intro', 'universal_property_intro', $lvc_id, $lvc_term ); // THV: primary_selling_point.
	$lvc_setting = lvc_content( 'setting_positioning', null, 'universal_setting', $lvc_id, $lvc_term );   // THV: view_type desc block.
	$lvc_editor  = lvc_content( 'editorial_text', null, 'universal_editorial', $lvc_id, $lvc_term );
	$lvc_desc    = lvc_field( 'property_descr', $lvc_id, get_the_content() ); // THV: property_description.
	$lvc_indoor  = lvc_field( 'indoor_living', $lvc_id );
	$lvc_outdoor = lvc_field( 'outdoor_living', $lvc_id );
	$lvc_bedrm   = lvc_field( 'bedroom_desc', $lvc_id );  // THV: bedroom_description.
	$lvc_cater   = lvc_field( 'catering_detail', $lvc_id );
	$lvc_view    = lvc_field( 'view_type', $lvc_id );
	$lvc_access  = lvc_field( 'access_type', $lvc_id );

	// Galleries — commas AND newlines split, non-URLs dropped (LESSONS §3).
	$lvc_squares    = lvc_gallery_urls( 'gallery_squares', $lvc_id );
	$lvc_slides_raw = lvc_gallery_urls( 'gallery_slider', $lvc_id );
	$lvc_slides     = $lvc_slides_raw ? $lvc_slides_raw : $lvc_squares;

	// Honest photo count for the hero button — unique union, not THV's raw sum.
	$lvc_photo_count = count( array_unique( array_merge( $lvc_squares, $lvc_slides_raw ) ) );

	// Hero image: curated ACF pick wins (LESSONS §2); first squares URL as a
	// last resort so a curated-image-less villa still gets a photo hero; if
	// nothing survives, the hero renders its dark-gradient fallback.
	$lvc_hero = lvc_property_image( $lvc_id, 'full', 'hero' );
	if ( ! $lvc_hero && $lvc_squares ) {
		$lvc_hero = lvc_priority_image_url( $lvc_squares[0] );
	}

	$lvc_wa    = lvc_whatsapp_url();
	$lvc_brand = lvc_config( 'brand_name', get_bloginfo( 'name' ) );

	// THV's data-derived "best for" line (no target_guest field on this site).
	$lvc_best_for = ( (int) $lvc_guests >= 10 ) ? 'Best for families and friend groups' : 'Best for couples and families';

	// Quick-fact chips — only claims the data confirms (no amenity taxonomy here).
	$lvc_chips = array();
	if ( ! empty( $lvc_rate['minimum_stay'] ) ) {
		$lvc_chips[] = (int) $lvc_rate['minimum_stay'] . '-night minimum stay';
	}
	if ( $lvc_guests ) {
		$lvc_chips[] = 'Sleeps up to ' . (int) $lvc_guests;
	}
	foreach ( array( $lvc_access, $lvc_view ) as $lvc_chip ) {
		if ( $lvc_chip ) {
			$lvc_chips[] = $lvc_chip;
		}
	}

	// Testimonials — real, verified reviews only. THV's hardcoded brand reviews
	// are NOT ported; the section renders this property's own repeater or hides.
	$lvc_testimonials = array();
	$lvc_t_raw        = lvc_field( 'testimonials', $lvc_id, array() );
	if ( is_array( $lvc_t_raw ) ) {
		foreach ( $lvc_t_raw as $lvc_t ) {
			$lvc_quote = isset( $lvc_t['quote'] ) ? trim( (string) $lvc_t['quote'] ) : '';
			if ( '' === $lvc_quote ) {
				continue;
			}
			$lvc_testimonials[] = array(
				'quote' => $lvc_quote,
				'name'  => isset( $lvc_t['guest_name'] ) ? trim( (string) $lvc_t['guest_name'] ) : '',
				'loc'   => isset( $lvc_t['guest_location'] ) ? trim( (string) $lvc_t['guest_location'] ) : '',
				'date'  => isset( $lvc_t['stay_date'] ) ? trim( (string) $lvc_t['stay_date'] ) : '',
			);
		}
	}

	// What's included — property/universal lists, newline-split (prose commas kept).
	$lvc_included = lvc_list_lines( lvc_content( 'included_items', null, 'universal_included', $lvc_id, $lvc_term ) );
	$lvc_request  = lvc_list_lines( lvc_content( 'on_request_items', null, 'universal_on_request', $lvc_id, $lvc_term ) );

	// FAQ — property repeater, else universal set.
	$lvc_faq = lvc_property_faq( $lvc_id );

	// "Where This Villa Fits" cards — this site's link hub: area term, bedrooms
	// term, full collection. Term hero images feed the card art (single source);
	// the villa's own hero is the guaranteed non-blank fallback.
	$lvc_explore = array();
	if ( $lvc_area && $lvc_area_url ) {
		$lvc_explore[] = array(
			'url'    => $lvc_area_url,
			'anchor' => 'Villas in ' . $lvc_area,
			'image'  => lvc_priority_image_url( (string) lvc_field( 'hero_image_url', 'term_' . $lvc_term->term_id ) ),
		);
	}
	$lvc_bed_terms = get_the_terms( $lvc_id, 'bedrooms' );
	if ( $lvc_bed_terms && ! is_wp_error( $lvc_bed_terms ) ) {
		$lvc_bed_url = get_term_link( $lvc_bed_terms[0] );
		if ( ! is_wp_error( $lvc_bed_url ) ) {
			$lvc_explore[] = array(
				'url'    => $lvc_bed_url,
				'anchor' => $lvc_bed_terms[0]->name . ' in ' . lvc_config( 'region' ),
				'image'  => lvc_priority_image_url( (string) lvc_field( 'hero_image_url', 'term_' . $lvc_bed_terms[0]->term_id ) ),
			);
		}
	}
	if ( $lvc_archive_url ) {
		$lvc_explore[] = array(
			'url'    => $lvc_archive_url,
			'anchor' => 'All ' . lvc_config( 'region' ) . ' Villas',
			'image'  => '',
		);
	}
	foreach ( $lvc_explore as $lvc_ei => $lvc_e ) {
		if ( '' === $lvc_e['image'] && $lvc_hero ) {
			$lvc_explore[ $lvc_ei ]['image'] = $lvc_hero;
		}
	}

	// Similar villas — deterministic ID-rotated pick (same rationale as THV's
	// rotation: spreads link equity, cache-stable), via the core helper.
	$lvc_related = function_exists( 'lvc_related_properties' ) ? lvc_related_properties( $lvc_id, 3 ) : array();

	// Schema — inc/seo/schema.php owns JSON-LD (VacationRental + breadcrumb).
	if ( function_exists( 'lvc_schema_property' ) ) {
		lvc_schema_property( $lvc_id );
	}
	?>

<?php if ( $lvc_hero ) : ?>
<?php if ( '' !== (string) $lvc_hero ) : ?>
<link rel="preload" as="image" href="<?php echo esc_url( $lvc_hero ); ?>" fetchpriority="high">
<?php endif; ?>
<?php endif; ?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
	ANGUILLA BEACH LUXURY VILLAS — VILLA SINGLE (cloned from THV single-villa)
	St Barts palette: volcanic basalt ground, salt-rose accent, salt-crust text.
	═══════════════════════════════════════════════════════════════════════════ */

:root {
	--lvc-bg        : #12100f;
	--lvc-bg2       : #1a1715;
	--lvc-bg3       : #1c1917;
	--lvc-card      : #1a1715;
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
}

/* ═══════════════════════════════════════════════════════════════════════
	RESET & BASE — scoped to the page wrapper, not body (theme header stays)
	═══════════════════════════════════════════════════════════════════════ */
.lvc-wrap *, .lvc-wrap *::before, .lvc-wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }

.lvc-wrap {
	font-family    : var(--lvc-fb);
	background     : var(--lvc-bg);
	color          : var(--lvc-text);
	line-height    : 1.6;
	-webkit-font-smoothing: antialiased;
	overflow-x     : hidden;
}

.lvc-wrap img { max-width: 100%; height: auto; display: block; }
.lvc-wrap a   { text-decoration: none; color: inherit; }
.lvc-wrap ul  { list-style: none; }

/* Re-assert brand.css component spacing that the scoped reset above would
	otherwise strip: `.lvc-wrap *` ties single-class .lvc-* rules on
	specificity and this inline sheet loads after brand.css. */
.lvc-wrap .lvc-btn { padding: 0.95rem 1.9rem; }
.lvc-wrap .lvc-faq__a { padding: 0; }
.lvc-wrap .lvc-faq__a p { margin: 0 0 0.9rem; }
@media (max-width: 900px) {
	.lvc-wrap .lvc-single__mobilebar { padding: 0.7rem 1rem calc(0.7rem + env(safe-area-inset-bottom)); }
}

/* ═══════════════════════════════════════════════════════════════════════
	TYPOGRAPHY UTILITIES
	═══════════════════════════════════════════════════════════════════════ */
.lvc-label {
	font-size     : 0.68rem;
	font-weight   : 500;
	letter-spacing: 0.15em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	margin-bottom : 0.75rem;
	display       : block;
}

.lvc-heading {
	font-family   : var(--lvc-fd);
	font-size     : clamp(1.75rem, 4vw, 2.25rem);
	font-weight   : 400;
	color         : var(--lvc-text);
	line-height   : 1.2;
	margin-bottom : 1.25rem;
}

.lvc-heading em { font-style: italic; color: var(--lvc-accent); }

.lvc-body {
	font-size  : 0.95rem;
	color      : var(--lvc-soft);
	line-height: 1.85;
}

/* ═══════════════════════════════════════════════════════════════════════
	BUTTONS
	═══════════════════════════════════════════════════════════════════════ */
.lvc-btn {
	display        : inline-flex;
	align-items    : center;
	justify-content: center;
	gap            : 0.5rem;
	padding        : 1rem 1.75rem;
	font-family    : var(--lvc-fb);
	font-size      : 0.72rem;
	font-weight    : 500;
	letter-spacing : 0.1em;
	text-transform : uppercase;
	border         : none;
	cursor         : pointer;
	transition     : all 0.3s ease;
	white-space    : nowrap;
}

.lvc-btn--accent   { background: var(--lvc-accent); color: #12100f; font-weight: 600; }
.lvc-btn--accent:hover { background: var(--lvc-accent-h); color: #12100f; box-shadow: 0 4px 20px rgba(194,129,140,0.2); }

.lvc-btn--outline  { background: transparent; border: 1px solid var(--lvc-accent); color: var(--lvc-accent); }
.lvc-btn--outline:hover { background: var(--lvc-accent); color: #12100f; }

.lvc-btn--ghost    { background: transparent; border: 1px solid rgba(255,255,255,0.4); color: #fff; }
.lvc-btn--ghost:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.7); }

/* ═══════════════════════════════════════════════════════════════════════
	HERO — FULL BLEED + BOTTOM STRIP + TRUST BADGES
	═══════════════════════════════════════════════════════════════════════ */
.lvc-hero {
	position: relative;
	height: 100vh;
	min-height: 100vh;
	max-height: none;
	overflow: hidden;
	background: var(--lvc-bg);
}

/* Static hero. The Tulum property template this one mirrors has no hero
   animation, and a 20s scale on a full-bleed background keeps the largest
   element on the page compositing for the whole of it. inset returns to 0
   because the negative inset only existed to hide the pan's edges. */
.lvc-hero__bg {
	position: absolute;
	inset: 0;
	background-size: cover;
	background-position: center;
	z-index: 0;
}

/* Imageless villa — dark gradient stands in for the photo. */
.lvc-hero--noimg .lvc-hero__bg {
	background: radial-gradient(ellipse at 30% 20%, var(--lvc-bg3) 0%, var(--lvc-bg2) 45%, var(--lvc-bg) 100%);
}

/* Top fade — header legibility */
.lvc-hero__fade-top {
	position: absolute;
	top: 0; left: 0; right: 0;
	height: 30%;
	background: linear-gradient(180deg, rgba(0,0,0,0.65) 0%, transparent 100%);
	z-index: 1;
}

/* Bottom fade — villa name legibility */
.lvc-hero__fade-bottom {
	position: absolute;
	bottom: 0; left: 0; right: 0;
	height: 70%;
	background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.45) 45%, transparent 100%);
	z-index: 1;
}

/* Photo count — top right */
.lvc-hero__photo-btn {
	position: absolute;
	top: 90px;
	right: 2rem;
	z-index: 10;
	display: flex;
	align-items: center;
	gap: 8px;
	background: rgba(18,16,15,0.5);
	backdrop-filter: blur(8px);
	border: 1px solid rgba(245,240,234,0.15);
	color: var(--lvc-text);
	font-size: 0.68rem;
	font-weight: 500;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	padding: 0.5rem 0.9rem;
	cursor: pointer;
	transition: all 0.3s ease;
}

.lvc-hero__photo-btn:hover { background: rgba(18,16,15,0.8); border-color: var(--lvc-accent); }
.lvc-hero__photo-btn svg { flex-shrink: 0; }

/* Villa identity — above the strip */
.lvc-hero__identity {
	position: absolute;
	bottom: 110px;
	left: 2rem;
	right: 2rem;
	z-index: 10;
}

.lvc-hero__name {
	font-family: var(--lvc-fd);
	font-size: clamp(2.5rem, 5.5vw, 4.25rem);
	font-weight: 400;
	color: var(--lvc-text);
	line-height: 1.05;
	margin-bottom: 0.6rem;
	text-shadow: 0 2px 24px rgba(0,0,0,0.4);
	animation: lvcFadeUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
}

.lvc-hero__location {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.75rem;
	font-weight: 500;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	animation: lvcFadeUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.35s both;
}

.lvc-hero__location svg { stroke: var(--lvc-accent); flex-shrink: 0; }

@keyframes lvcFadeUp {
	0% { opacity: 0; transform: translateY(18px); }
	100% { opacity: 1; transform: translateY(0); }
}

/* Trust badges */
.lvc-hero__badges {
	display: flex;
	align-items: center;
	gap: 1rem;
	margin-top: 1.25rem;
	animation: lvcFadeUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.5s both;
}

.lvc-hero__badge {
	display: flex;
	align-items: center;
	gap: 0.4rem;
	font-size: 0.75rem;
	color: rgba(245,240,234,0.85);
}

.lvc-hero__badge svg {
	width: 14px;
	height: 14px;
	stroke: var(--lvc-accent);
	fill: none;
	stroke-width: 2;
}

.lvc-hero__badge-separator {
	color: rgba(245,240,234,0.3);
	font-size: 0.7rem;
}

/* Bottom strip */
.lvc-hero__strip {
	position: absolute;
	bottom: 0; left: 0; right: 0;
	z-index: 10;
	background: rgba(18,16,15,0.7);
	backdrop-filter: blur(12px);
	border-top: 1px solid rgba(245,240,234,0.08);
	display: flex;
	align-items: center;
	justify-content: space-between;
	flex-wrap: wrap;
	padding: 1.25rem 2rem;
	gap: 1rem;
	animation: lvcFadeUp 1s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both;
}

.lvc-strip__stats {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.lvc-strip__stat {
	padding: 0 1.5rem;
	display: flex;
	flex-direction: column;
	gap: 4px;
	border-right: 1px solid rgba(245,240,234,0.1);
}

.lvc-strip__stat:first-child { padding-left: 0; }
.lvc-strip__stat:last-child  { border-right: none; }

.lvc-strip__value {
	font-family: var(--lvc-fd);
	font-size: 1.8rem;
	font-weight: 400;
	color: var(--lvc-text);
	line-height: 1;
}

.lvc-strip__value--sm { font-size: 1.1rem; padding-top: 4px; }
.lvc-strip__value--rate { font-size: 1.05rem; padding-top: 4px; white-space: nowrap; }

.lvc-strip__label {
	font-size: 0.65rem;
	font-weight: 500;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: rgba(245,240,234,0.5);
}

.lvc-strip__ctas {
	display: flex;
	align-items: center;
	gap: 0.75rem;
	flex-shrink: 0;
}

/* Hero quick facts */
.lvc-quickfacts {
	background    : var(--lvc-bg2);
	border-top    : 1px solid var(--lvc-border);
	border-bottom : 1px solid var(--lvc-border);
	padding       : 1rem 2rem;
}

.lvc-quickfacts__inner {
	max-width     : 1600px;
	margin        : 0 auto;
	display       : flex;
	flex-wrap     : wrap;
	gap           : 0.6rem;
}

.lvc-quickfacts__chip {
	display       : inline-flex;
	align-items   : center;
	font-size     : 0.72rem;
	font-weight   : 500;
	letter-spacing: 0.07em;
	text-transform: uppercase;
	color         : var(--lvc-text);
	padding       : 0.45rem 0.7rem;
	border        : 1px solid var(--lvc-border);
	background    : rgba(255,255,255,0.02);
}

/* ═══════════════════════════════════════════════════════════════════════
	BRIDGE LINE
	═══════════════════════════════════════════════════════════════════════ */
.lvc-bridge {
	background: var(--lvc-bg2);
	padding: 2.5rem 2rem;
	text-align: center;
	border-bottom: 1px solid var(--lvc-border);
}

.lvc-bridge__text {
	font-family: var(--lvc-fd);
	font-size: 0.9rem;
	font-weight: 500;
	letter-spacing: 0.22em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin: 0;
}

/* ═══════════════════════════════════════════════════════════════════════
	BREADCRUMBS
	═══════════════════════════════════════════════════════════════════════ */
.lvc-breadcrumb {
	background    : var(--lvc-bg2);
	border-bottom : 1px solid var(--lvc-border);
	padding       : 0.9rem 2.5rem;
}

.lvc-breadcrumb__list {
	display       : flex;
	align-items   : center;
	gap           : 0.5rem;
	flex-wrap     : wrap;
	max-width     : 1600px;
	margin        : 0 auto;
}

.lvc-breadcrumb__list li {
	display       : flex;
	align-items   : center;
	gap           : 0.5rem;
	font-size     : 0.72rem;
	color         : var(--lvc-muted);
	letter-spacing: 0.04em;
}

.lvc-breadcrumb__list li::after {
	content       : '›';
	color         : var(--lvc-border-h);
	font-size     : 0.9rem;
}

.lvc-breadcrumb__list li:last-child::after { display: none; }
.lvc-breadcrumb__list li:last-child { color: var(--lvc-soft); }

.lvc-breadcrumb__list a {
	color         : var(--lvc-muted);
	transition    : color 0.2s ease;
}

.lvc-breadcrumb__list a:hover { color: var(--lvc-accent); }

/* ═══════════════════════════════════════════════════════════════════════
	INTRO / SELLING POINT
	═══════════════════════════════════════════════════════════════════════ */
.lvc-intro {
	background    : var(--lvc-bg2);
	padding       : 4.5rem 2rem;
	text-align    : center;
	border-bottom : 1px solid var(--lvc-border);
}

.lvc-intro__inner {
	max-width     : 680px;
	margin        : 0 auto;
}

.lvc-intro__quote {
	font-family   : var(--lvc-fd);
	font-size     : clamp(1.5rem, 3.5vw, 2rem);
	font-style    : italic;
	font-weight   : 400;
	color         : var(--lvc-text);
	line-height   : 1.45;
	margin-bottom : 1.5rem;
	position      : relative;
	padding-bottom: 1.5rem;
}

.lvc-intro__quote::after {
	content       : '';
	position      : absolute;
	bottom        : 0;
	left          : 50%;
	transform     : translateX(-50%);
	width         : 40px;
	height        : 1px;
	background    : var(--lvc-accent);
}

.lvc-intro__target {
	font-size     : 0.72rem;
	font-weight   : 500;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color         : var(--lvc-accent);
	margin-top    : 1.5rem;
	display       : block;
}

/* ═══════════════════════════════════════════════════════════════════════
	GALLERY SQUARES — THV custom-squares-gallery clone: max 6 uniform 1:1
	tiles, cover-fit images (no empty card bottoms), hover zoom + gradient
	overlay. THV's green tints become seaglass. Square corners, 4px gap.
	═══════════════════════════════════════════════════════════════════════ */
.lvc-squares {
	padding       : 0 0 5rem;
	background    : var(--lvc-bg);
}

.lvc-squares__inner {
	max-width     : 1200px;
	margin        : 0 auto;
	padding       : 0 2rem;
}

.lvc-squares__header {
	text-align    : center;
	padding       : 4rem 0 2.5rem;
}

.lvc-squares__grid {
	display               : grid;
	grid-template-columns : repeat(3, 1fr);
	gap                   : 4px;
	width                 : 100%;
	align-items           : stretch;
	overflow              : hidden;
}

@media (max-width: 980px) {
	.lvc-squares__grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 560px) {
	.lvc-squares__grid { grid-template-columns: 1fr; }
}

@media (max-width: 420px) {
	.lvc-squares__grid { gap: 3px; }
}

.lvc-squares__tile {
	position      : relative;
	margin        : 0;
	aspect-ratio  : 1 / 1;
	overflow      : hidden;
	background    : var(--lvc-bg3);
	transition    : transform 0.16s ease;
}

.lvc-squares__tile img {
	position      : absolute;
	inset         : 0;
	width         : 100%;
	height        : 100%;
	object-fit    : cover;
	display       : block;
	transition    : transform 0.32s ease, filter 0.24s ease;
}

.lvc-squares__overlay {
	position      : absolute;
	inset         : 0;
	background    : linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.04) 60%, rgba(0,0,0,0.18) 100%);
	transition    : background 0.18s ease;
	pointer-events: none;
}

.lvc-squares__tile:hover { transform: scale(1.01); z-index: 1; }

.lvc-squares__tile:hover img {
	transform     : scale(1.04);
	filter        : saturate(1.05) contrast(1.02);
}

.lvc-squares__tile:hover .lvc-squares__overlay {
	background    : linear-gradient(180deg, rgba(194,129,140,0.04) 0%, rgba(0,0,0,0.08) 60%, rgba(0,0,0,0.25) 100%);
}

/* ═══════════════════════════════════════════════════════════════════════
	CONTENT SECTION
	═══════════════════════════════════════════════════════════════════════ */
.lvc-content {
	max-width     : 1100px;
	margin        : 0 auto;
	padding       : 5rem 2rem;
}

.lvc-content__h1 {
	font-family   : var(--lvc-fd);
	font-size     : clamp(1.85rem, 4.5vw, 2.5rem);
	font-weight   : 400;
	color         : var(--lvc-text);
	line-height   : 1.2;
	margin-bottom : 1.5rem;
}

.lvc-content .lvc-body p { margin-bottom: 1.3rem; }
.lvc-content .lvc-body p:last-child { margin-bottom: 0; }

/* ═══════════════════════════════════════════════════════════════════════
	DESCRIPTION GRID — 2×2
	═══════════════════════════════════════════════════════════════════════ */
.lvc-desc-grid {
	display               : grid;
	grid-template-columns : repeat(2, 1fr);
	gap                   : 2.5rem;
	margin-top            : 3rem;
	padding-top           : 3rem;
	border-top            : 1px solid var(--lvc-border);
}

.lvc-desc-block:last-child:nth-child(odd) {
	grid-column           : 1 / -1;
}

@media (max-width: 560px) {
	.lvc-desc-grid        { grid-template-columns: 1fr; }
	.lvc-desc-block:last-child:nth-child(odd) { grid-column: auto; }
}

.lvc-desc-block__title {
	font-family   : var(--lvc-fd);
	font-size     : 1.2rem;
	font-weight   : 500;
	color         : var(--lvc-text);
	margin-bottom : 0.75rem;
	padding-bottom: 0.6rem;
	border-bottom : 1px solid var(--lvc-border);
	display       : flex;
	align-items   : center;
	gap           : 0.6rem;
}

.lvc-desc-block__title svg { stroke: var(--lvc-accent); flex-shrink: 0; }

.lvc-desc-block__text {
	font-size     : 0.9rem;
	color         : var(--lvc-soft);
	line-height   : 1.85;
}

.lvc-desc-block__text p { margin-bottom: 1rem; }
.lvc-desc-block__text p:last-child { margin-bottom: 0; }

/* Mid-page conversion assist */
.lvc-assist {
	background    : var(--lvc-bg2);
	padding       : 2.5rem 2rem;
	border-top    : 1px solid var(--lvc-border);
	border-bottom : 1px solid var(--lvc-border);
}

.lvc-assist__inner {
	max-width     : 1100px;
	margin        : 0 auto;
	display       : grid;
	grid-template-columns: minmax(0, 1fr) auto;
	align-items   : center;
	gap           : 1rem;
}

.lvc-assist__copy {
	max-width     : 560px;
}

.lvc-assist__title {
	font-family   : var(--lvc-fd);
	font-size     : clamp(1.35rem, 3vw, 1.8rem);
	font-weight   : 500;
	color         : var(--lvc-text);
	margin-bottom : 0.4rem;
}

.lvc-assist__sub {
	font-size     : 0.92rem;
	line-height   : 1.7;
	color         : var(--lvc-soft);
}

.lvc-assist__ctas {
	display       : flex;
	gap           : 0.75rem;
	flex-wrap     : wrap;
	justify-content: flex-end;
	margin-left   : auto;
	justify-self  : end;
	width         : max-content;
}

/* ═══════════════════════════════════════════════════════════════════════
	REVIEWS SECTION — this property's verified guest reviews
	═══════════════════════════════════════════════════════════════════════ */
.lvc-reviews {
	background: var(--lvc-bg);
	padding: 4rem 2rem;
	border-top: 1px solid var(--lvc-border);
}

.lvc-reviews__inner {
	max-width: 1600px;
	margin: 0 auto;
}

.lvc-reviews__header {
	text-align: center;
	margin-bottom: 2.5rem;
}

.lvc-reviews__grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 1.5rem;
}

@media (max-width: 900px) {
	.lvc-reviews__grid {
		grid-template-columns: 1fr;
	}
}

.lvc-review-card {
	background: var(--lvc-card);
	border: 1px solid var(--lvc-border);
	padding: 1.75rem;
	display: flex;
	flex-direction: column;
	transition: all 0.3s ease;
	height: 100%;
	border-radius: 4px;
}

.lvc-review-card:hover {
	border-color: var(--lvc-accent);
	box-shadow: var(--lvc-shadow);
}

.lvc-review-card__quote {
	font-family: var(--lvc-fd);
	font-size: 1rem;
	font-style: italic;
	color: var(--lvc-text);
	line-height: 1.6;
	margin: 0 0 1.25rem 0;
	flex-grow: 1;
}

.lvc-review-card__footer {
	border-top: 1px solid var(--lvc-border);
	padding-top: 1rem;
	margin-top: auto;
}

.lvc-review-card__name {
	display: block;
	font-size: 0.85rem;
	font-weight: 500;
	color: var(--lvc-accent);
}

.lvc-review-card__meta {
	display: block;
	font-size: 0.75rem;
	color: var(--lvc-muted);
	margin-top: 2px;
}

/* ═══════════════════════════════════════════════════════════════════════
	GALLERY SLIDER — THV pc-slider clone: full-width uniform-height slides
	with cover-fit images, 44px round night-sea arrows (seaglass accent on
	hover — never the conch counterpoint), counter as an overlay pill.
	theme.js still drives the [data-lvc-slider] scroll-snap track/counter.
	THV heights: 700px desktop (template override), 420px ≤768px, 330px
	≤560px (shortcode 0.7/0.55 tiers of the 600px base).
	═══════════════════════════════════════════════════════════════════════ */
.lvc-slider {
	position: relative;
	width: 100%;
	max-width: 1200px;
	margin: 0 auto;
	overflow: hidden;
	background: var(--lvc-bg);
}

.lvc-slider__track {
	display: flex;
	overflow-x: auto;
	scroll-snap-type: x mandatory;
	scroll-behavior: smooth;
	scrollbar-width: none;
}

.lvc-slider__track::-webkit-scrollbar { display: none; }

.lvc-slider__slide {
	flex: 0 0 100%;
	width: 100%;
	margin: 0;
	height: 700px;
	scroll-snap-align: start;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
	background: var(--lvc-bg3);
}

@media (max-width: 768px) {
	.lvc-slider__slide { height: 420px; }
}

@media (max-width: 560px) {
	.lvc-slider__slide { height: 330px; }
}

.lvc-slider__slide img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center;
	display: block;
}

.lvc-slider__nav {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	z-index: 2;
	width: 44px;
	height: 44px;
	display: grid;
	place-items: center;
	padding: 0;
	font-size: 1.5rem;
	line-height: 1;
	color: var(--lvc-text);
	background: rgba(18, 16, 15, 0.92);
	border: 1px solid var(--lvc-border);
	border-radius: 50%;
	cursor: pointer;
	transition: background 0.2s, border-color 0.2s, opacity 0.2s;
}

.lvc-slider__nav:hover {
	background: var(--lvc-bg3);
	border-color: var(--lvc-accent);
}

.lvc-slider__nav:disabled { opacity: 0.3; cursor: default; }
.lvc-slider__nav--prev { left: 1rem; }
.lvc-slider__nav--next { right: 1rem; }

.lvc-slider__count {
	position: absolute;
	right: 1rem;
	bottom: 1rem;
	z-index: 2;
	margin: 0;
	padding: 0.35rem 0.8rem;
	font-size: 0.7rem;
	letter-spacing: 0.14em;
	font-variant-numeric: tabular-nums;
	color: var(--lvc-text);
	background: rgba(18, 16, 15, 0.85);
	border: 1px solid var(--lvc-border);
	border-radius: 999px;
	pointer-events: none;
}

/* ═══════════════════════════════════════════════════════════════════════
	WHAT'S INCLUDED — dot bullets, never checkmarks (portfolio standard)
	═══════════════════════════════════════════════════════════════════════ */
.lvc-included {
	background: var(--lvc-bg);
	padding: 4rem 2rem;
	border-top: 1px solid var(--lvc-border);
}

.lvc-included__inner {
	max-width: 1100px;
	margin: 0 auto;
}

.lvc-included__header {
	margin-bottom: 2.5rem;
}

.lvc-included__groups {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 2.5rem;
}

@media (max-width: 640px) {
	.lvc-included__groups { grid-template-columns: 1fr; }
}

.lvc-included__group-label {
	font-size: 0.68rem;
	font-weight: 500;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: var(--lvc-soft);
	margin-bottom: 1rem;
	padding-bottom: 0.6rem;
	border-bottom: 1px solid var(--lvc-border);
	display: block;
}

.lvc-included__group:first-child .lvc-included__group-label {
	color: var(--lvc-accent);
	font-weight: 600;
}

.lvc-included__list {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
}

.lvc-included__item {
	display: flex;
	align-items: center;
	gap: 0.65rem;
	padding: 0.65rem 0.85rem;
	background: var(--lvc-bg2);
	border: 1px solid var(--lvc-border);
	font-size: 0.82rem;
	color: var(--lvc-text);
	font-weight: 500;
	border-radius: 4px;
}

.lvc-included__item::before {
	content: '';
	width: 6px;
	height: 6px;
	border-radius: 50%;
	background: var(--lvc-accent);
	flex-shrink: 0;
}

.lvc-included__item--optional {
	background: transparent;
	border: 1px solid var(--lvc-border);
	color: var(--lvc-muted);
	font-weight: 300;
}

.lvc-included__item--optional::before {
	background: transparent;
	border: 1px solid var(--lvc-muted);
}

/* ═══════════════════════════════════════════════════════════════════════
	GALLERY + FAQ — merged split section, mirroring the Tulum property
	template. The slider at roughly half width renders its sources sharp
	(full-bleed left them with no DPR headroom) and the FAQ fills the other
	half instead of sitting as a thin column further down the page.
	═══════════════════════════════════════════════════════════════════════ */
.lvc-media-faq {
	background: var(--lvc-bg);
	padding: 4rem 2rem;
	border-top: 1px solid var(--lvc-border);
}

.lvc-media-faq__grid {
	max-width: 1400px;
	margin: 0 auto;
	display: grid;
	grid-template-columns: 1.1fr 1fr;
	gap: 3.5rem;
	align-items: start;
}

/* One column present only — centre it rather than leaving a dead half. */
.lvc-media-faq__grid--single {
	grid-template-columns: 1fr;
	max-width: 800px;
}

.lvc-media-faq__media {
	position: sticky;
	top: 90px;
}

@media (max-width: 980px) {
	.lvc-media-faq__grid { grid-template-columns: 1fr; gap: 2.5rem; }
	.lvc-media-faq__media { position: static; }
}

/* FAQ — "Good to Know". A plain definition list, not an accordion: every
   answer is visible without a click, which is how Tulum renders it. */
.lvc-faq__header {
	text-align: left;
	margin-bottom: 2rem;
}

.lvc-faq__list { margin: 0; }

.lvc-faq__item {
	border-bottom: 1px solid var(--lvc-border);
	padding: 1.5rem 0;
}

.lvc-faq__item:last-child { border-bottom: none; }

.lvc-faq__q {
	font-family: var(--lvc-fd);
	font-size: 1.15rem;
	color: var(--lvc-text);
	margin: 0 0 0.6rem;
}

.lvc-faq__a {
	font-size: 0.95rem;
	color: var(--lvc-soft);
	line-height: 1.7;
	margin: 0;
}

/* ═══════════════════════════════════════════════════════════════════════
	INQUIRY FORM
	═══════════════════════════════════════════════════════════════════════ */
.lvc-inquiry {
	background: var(--lvc-bg2);
	padding: 5.5rem 2rem;
	position: relative;
	overflow: hidden;
}

.lvc-inquiry__inner {
	max-width: 780px;
	margin: 0 auto;
	position: relative;
	z-index: 1;
}

.lvc-inquiry__header {
	text-align: center;
	margin-bottom: 2.5rem;
}

.lvc-inquiry__label {
	font-size: 0.68rem;
	font-weight: 500;
	letter-spacing: 0.15em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin-bottom: 0.75rem;
	display: block;
}

.lvc-inquiry__title {
	font-family: var(--lvc-fd);
	font-size: clamp(1.75rem, 4vw, 2.25rem);
	font-weight: 400;
	color: var(--lvc-text);
	line-height: 1.2;
	margin-bottom: 0.5rem;
}

.lvc-inquiry__sub {
	font-size: 0.85rem;
	color: var(--lvc-muted);
	margin-top: 0.5rem;
}

/* THV form look mapped onto the core .lvc-form markup (labels + groups). */
.lvc-inquiry .lvc-form {
	display: flex;
	flex-direction: column;
	gap: 0.9rem;
}

.lvc-inquiry .lvc-form__row {
	display: grid;
	grid-template-columns: 1fr;
	gap: 0.9rem;
}

@media (min-width: 500px) {
	.lvc-inquiry .lvc-form__row { grid-template-columns: 1fr 1fr; }
}

.lvc-inquiry .lvc-form__group { display: flex; flex-direction: column; gap: 0.35rem; }

.lvc-inquiry .lvc-form__group label {
	font-size: 0.63rem;
	font-weight: 500;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: var(--lvc-muted);
}

.lvc-inquiry .lvc-form input[type="text"],
.lvc-inquiry .lvc-form input[type="email"],
.lvc-inquiry .lvc-form input[type="tel"],
.lvc-inquiry .lvc-form input[type="number"],
.lvc-inquiry .lvc-form input[type="date"],
.lvc-inquiry .lvc-form select,
.lvc-inquiry .lvc-form textarea {
	width: 100%;
	padding: 0.95rem 1rem;
	font-family: var(--lvc-fb);
	font-size: 0.875rem;
	font-weight: 300;
	background: var(--lvc-bg);
	border: 1px solid var(--lvc-border-h);
	color: var(--lvc-text);
	transition: all 0.3s ease;
	outline: none;
	border-radius: 4px;
	-webkit-appearance: none;
	color-scheme: dark;
}

.lvc-inquiry .lvc-form ::placeholder { color: var(--lvc-muted); }

.lvc-inquiry .lvc-form input:focus,
.lvc-inquiry .lvc-form select:focus,
.lvc-inquiry .lvc-form textarea:focus {
	border-color: var(--lvc-accent);
	background: var(--lvc-bg3);
	box-shadow: 0 0 0 3px rgba(194,129,140,0.1);
}

.lvc-inquiry .lvc-form textarea {
	min-height: 120px;
	resize: vertical;
}

/* Honeypot — offscreen; bots fill it, humans never see it. */
.lvc-inquiry .lvc-form .lvc-form__hp {
	position: absolute !important;
	left: -9999px !important;
	top: -9999px !important;
	width: 1px !important;
	height: 1px !important;
	overflow: hidden !important;
}

.lvc-inquiry .lvc-form .lvc-form__submit {
	margin-top: 0.5rem;
	width: 100%;
	background: var(--lvc-accent);
	color: #12100f;
	padding: 1.1rem;
	font-family: var(--lvc-fb);
	font-size: 0.75rem;
	font-weight: 600;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	border: none;
	cursor: pointer;
	transition: background 0.3s ease;
	border-radius: 4px;
}

.lvc-inquiry .lvc-form .lvc-form__submit:hover { background: var(--lvc-accent-h); color: #12100f; }

.lvc-inquiry .lvc-form__status {
	font-size: 0.85rem;
	color: var(--lvc-soft);
	min-height: 1.2em;
}

.lvc-inquiry .lvc-form__micro {
	text-align: center;
	font-size: 0.75rem;
	color: var(--lvc-muted);
}

.lvc-inquiry__wa-alt {
	text-align: center;
	margin-top: 1.25rem;
	font-size: 0.78rem;
	color: var(--lvc-muted);
}

.lvc-inquiry__wa-alt a {
	color: var(--lvc-accent);
	font-weight: 500;
	transition: color 0.2s ease;
}

.lvc-inquiry__wa-alt a:hover { color: #25D366; }

/* ═══════════════════════════════════════════════════════════════════════
	LOCATION CONTEXT
	═══════════════════════════════════════════════════════════════════════ */
.lvc-location {
	background: var(--lvc-primary);
	padding: 3.5rem 2rem;
}

.lvc-location__inner {
	max-width: 1100px;
	margin: 0 auto;
	display: flex;
	align-items: flex-start;
	gap: 3rem;
	flex-wrap: wrap;
}

.lvc-location__main {
	flex: 1;
	min-width: 220px;
}

.lvc-location__label {
	font-size: 0.68rem;
	font-weight: 500;
	letter-spacing: 0.15em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	display: block;
	margin-bottom: 0.6rem;
}

.lvc-location__area {
	font-family: var(--lvc-fd);
	font-size: clamp(1.5rem, 3vw, 2rem);
	font-weight: 400;
	color: #fff;
	line-height: 1.2;
	margin-bottom: 0.5rem;
}

.lvc-location__destination {
	font-size: 0.78rem;
	color: rgba(255,255,255,0.55);
	letter-spacing: 0.08em;
	text-transform: uppercase;
}

.lvc-location__meta {
	display: flex;
	flex-direction: column;
	gap: 1rem;
	min-width: 200px;
}

.lvc-location__meta-item {
	display: flex;
	flex-direction: column;
	gap: 3px;
}

.lvc-location__meta-label {
	font-size: 0.63rem;
	font-weight: 500;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: rgba(255,255,255,0.4);
}

.lvc-location__meta-value {
	font-size: 0.85rem;
	color: rgba(255,255,255,0.85);
	font-weight: 400;
}

.lvc-location__context {
	flex: 2;
	min-width: 260px;
	border-left: 1px solid rgba(255,255,255,0.1);
	padding-left: 3rem;
}

.lvc-location__context p {
	font-size: 0.9rem;
	color: rgba(255,255,255,0.65);
	line-height: 1.8;
	font-weight: 300;
}

@media (max-width: 640px) {
	.lvc-location__inner  { flex-direction: column; gap: 1.5rem; }
	.lvc-location__context { border-left: none; padding-left: 0; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; }
}

/* Where This Villa Fits — image cards (mirrors homepage destination cards) */
.lvc-explore-cards {
	padding: 5rem clamp(1.25rem, 5vw, 4rem);
	background: var(--lvc-bg);
	border-top: 1px solid var(--lvc-border);
}

.lvc-explore-cards__inner {
	max-width: 1600px;
	margin: 0 auto;
}

.lvc-explore-cards__title {
	font-family: var(--lvc-fd);
	font-size: clamp(1.6rem, 3vw, 2.4rem);
	font-weight: 400;
	color: #fff;
	margin: 0.5rem 0 2.5rem 0;
	line-height: 1.1;
}
.lvc-explore-cards__title em { font-style: italic; color: var(--lvc-accent); }

.lvc-explore-cards__grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
	gap: 1.25rem;
}

.lvc-explore-card {
	position: relative;
	height: 240px;
	overflow: hidden;
	display: block;
	background-color: var(--lvc-bg3);
	border-radius: 4px;
}
.lvc-explore-card__bg {
	position: absolute; inset: 0;
	background-size: cover;
	background-position: center;
	transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.lvc-explore-card:hover .lvc-explore-card__bg { transform: scale(1.04); }
.lvc-explore-card__overlay {
	position: absolute; inset: 0;
	background: linear-gradient(to bottom, rgba(18,16,15,0.1) 30%, rgba(18,16,15,0.92) 100%);
}
.lvc-explore-card__body {
	position: absolute;
	bottom: 0; left: 0; right: 0;
	padding: 1.5rem 1.75rem;
	z-index: 2;
}
.lvc-explore-card__name {
	font-family: var(--lvc-fd);
	font-size: 1.35rem;
	font-weight: 400;
	color: #fff;
	margin: 0 0 0.4rem 0;
	line-height: 1.15;
}
.lvc-explore-card__cta {
	font-size: 0.62rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: rgba(255,255,255,0.55);
	font-weight: 500;
	transition: color 0.2s;
}
.lvc-explore-card:hover .lvc-explore-card__cta { color: var(--lvc-accent); }

/* ═══════════════════════════════════════════════════════════════════════
	SIMILAR VILLAS
	═══════════════════════════════════════════════════════════════════════ */
.lvc-similar {
	padding: 5.5rem 2rem;
	background: var(--lvc-bg);
	border-top: 1px solid var(--lvc-border);
}

.lvc-similar__inner {
	max-width: 1600px;
	margin: 0 auto;
}

.lvc-similar__header {
	text-align: center;
	margin-bottom: 3rem;
}

.lvc-similar__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 1.75rem;
}

.lvc-similar__card {
	display: block;
	background: var(--lvc-card);
	border: 1px solid var(--lvc-border);
	overflow: hidden;
	transition: all 0.3s ease;
	border-radius: 4px;
}

.lvc-similar__card:hover {
	border-color: var(--lvc-accent);
	box-shadow: var(--lvc-shadow-h);
	transform: translateY(-4px);
}

.lvc-similar__image {
	height: 220px;
	background-size: cover;
	background-position: center;
	background-color: var(--lvc-bg);
	overflow: hidden;
	transition: transform 0.5s ease;
}

.lvc-similar__image--empty {
	background-color: var(--lvc-bg);
	display: flex;
	align-items: center;
	justify-content: center;
}

.lvc-similar__image--empty::after {
	content: '';
	display: block;
	width: 40px;
	height: 40px;
	border: 1px solid var(--lvc-border-h);
	border-radius: 50%;
	opacity: 0.3;
}

.lvc-similar__card:hover .lvc-similar__image { transform: scale(1.04); }

.lvc-similar__content {
	padding: 1.5rem;
}

.lvc-similar__name {
	font-family: var(--lvc-fd);
	font-size: 1.25rem;
	font-weight: 500;
	color: var(--lvc-text);
	margin-bottom: 0.25rem;
}

.lvc-similar__area {
	font-size: 0.78rem;
	color: var(--lvc-muted);
	margin-bottom: 0.9rem;
}

.lvc-similar__meta {
	display: flex;
	gap: 1rem;
	flex-wrap: wrap;
	font-size: 0.75rem;
	color: var(--lvc-soft);
	border-top: 1px solid var(--lvc-border);
	padding-top: 0.9rem;
}

.lvc-similar__meta-item {
	display: flex;
	align-items: center;
	gap: 4px;
}

.lvc-similar__links {
	display: flex;
	align-items: center;
	justify-content: center;
	flex-wrap: wrap;
	gap: 0;
	margin-top: 3rem;
	border-top: 1px solid var(--lvc-border);
	padding-top: 2rem;
}

.lvc-similar__link {
	font-size: 0.8rem;
	font-weight: 500;
	color: var(--lvc-text);
	letter-spacing: 0.04em;
	padding: 0.4rem 1.5rem;
	border-right: 1px solid var(--lvc-border);
	transition: color 0.2s ease;
	white-space: nowrap;
}

.lvc-similar__link:last-child { border-right: none; }
.lvc-similar__link:hover      { color: var(--lvc-accent); }

/* ═══════════════════════════════════════════════════════════════════════
	RESPONSIVE
	═══════════════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
	.lvc-hero {
		display: flex;
		flex-direction: column;
		justify-content: flex-end;
		height: 100svh;
		min-height: 100svh;
		max-height: none;
	}

	.lvc-hero__identity {
		position: relative;
		bottom: auto;
		left: auto;
		right: auto;
		z-index: 10;
		padding: 0 1.25rem 1rem;
	}

	.lvc-hero__badges {
		flex-wrap: wrap;
		gap: 0.5rem 1rem;
	}

	.lvc-hero__badge-separator { display: none; }

	.lvc-hero__strip {
		position: relative;
		padding: 1rem 1.25rem;
		flex-wrap: wrap;
		gap: 0.75rem;
	}

	.lvc-strip__stats { gap: 0; flex-wrap: wrap; }
	.lvc-strip__stat { padding: 0 1rem; }
	.lvc-strip__ctas { width: 100%; }
	.lvc-strip__ctas .lvc-btn { flex: 1; justify-content: center; }

	.lvc-hero__photo-btn {
		top: 80px;
		right: 1.25rem;
	}

	.lvc-hero__fade-bottom {
		height: 80%;
	}

	.lvc-bridge { padding: 2rem 1.25rem; }
	.lvc-breadcrumb { padding: 0.75rem 1.25rem; }
	.lvc-content { padding: 3rem 1.25rem; }
	.lvc-inquiry { padding: 4rem 1.25rem; }
	.lvc-quickfacts { padding: 0.9rem 1.25rem; }
	.lvc-assist { padding: 2rem 1.25rem; }
	.lvc-assist__inner { grid-template-columns: 1fr; }
	.lvc-assist__ctas { width: 100%; justify-self: stretch; justify-content: flex-start; }
	.lvc-assist__ctas .lvc-btn { flex: 1; justify-content: center; }
	.lvc-similar__grid { grid-template-columns: 1fr; }
}

@media (max-width: 420px) {
	.lvc-strip__stats { gap: 0; }
	.lvc-strip__stat { padding: 0 0.65rem; }
	.lvc-strip__value { font-size: 1.25rem; }
}
</style>

<div class="lvc-wrap lvc-villa-single">

<!-- ═══════════════════════════════════════════════════════════════════════════
	HERO — FULL BLEED + BOTTOM STRIP + TRUST BADGES
	═══════════════════════════════════════════════════════════════════════════ -->
<section class="lvc-hero<?php echo $lvc_hero ? '' : ' lvc-hero--noimg'; ?>" id="lvcHero" aria-label="<?php echo esc_attr( $lvc_display ); ?>">

	<div class="lvc-hero__bg"<?php echo $lvc_hero ? ' style="background-image:url(\'' . esc_url( $lvc_hero ) . '\');" role="img" aria-label="' . esc_attr( $lvc_display . ' villa exterior' ) . '"' : ' aria-hidden="true"'; ?>></div>
	<div class="lvc-hero__fade-top" aria-hidden="true"></div>
	<div class="lvc-hero__fade-bottom" aria-hidden="true"></div>

	<?php if ( $lvc_photo_count > 0 ) : ?>
	<a href="#gallery-slider" class="lvc-hero__photo-btn" id="lvcPhotoBtn" aria-label="View all photos">
		<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
		</svg>
		<?php echo esc_html( (string) $lvc_photo_count ); ?> Photos
	</a>
	<?php endif; ?>

	<!-- Villa identity — above the strip -->
	<div class="lvc-hero__identity">
		<div class="lvc-hero__name"><?php echo esc_html( $lvc_display ); ?></div>
		<div class="lvc-hero__location">
			<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
			</svg>
			<?php echo esc_html( $lvc_area ? $lvc_area . ', ' . lvc_config( 'region' ) : lvc_config( 'region' ) . ', Caribbean' ); ?>
		</div>

		<!-- Trust badges (rating badge dropped — no per-villa rating data here) -->
		<div class="lvc-hero__badges">
			<span class="lvc-hero__badge">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
					<polyline points="22 4 12 14.01 9 11.01"/>
				</svg>
				Verified Property
			</span>
			<span class="lvc-hero__badge-separator">|</span>
			<span class="lvc-hero__badge">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
					<path d="M7 11V7a5 5 0 0 1 10 0v4"/>
				</svg>
				Direct Booking
			</span>
			<span class="lvc-hero__badge-separator">|</span>
			<span class="lvc-hero__badge">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
				</svg>
				Concierge Planning
			</span>
		</div>
	</div>

	<!-- Bottom stats strip -->
	<div class="lvc-hero__strip">
		<div class="lvc-strip__stats">
			<?php if ( $lvc_beds ) : ?>
			<div class="lvc-strip__stat">
				<span class="lvc-strip__value"><?php echo esc_html( $lvc_beds ); ?></span>
				<span class="lvc-strip__label">Bedrooms</span>
			</div>
			<?php endif; ?>

			<?php if ( $lvc_guests ) : ?>
			<div class="lvc-strip__stat">
				<span class="lvc-strip__value"><?php echo esc_html( $lvc_guests ); ?></span>
				<span class="lvc-strip__label">Guests</span>
			</div>
			<?php endif; ?>

			<?php if ( $lvc_baths ) : ?>
			<div class="lvc-strip__stat">
				<span class="lvc-strip__value"><?php echo esc_html( $lvc_baths ); ?></span>
				<span class="lvc-strip__label">Bathrooms</span>
			</div>
			<?php endif; ?>

			<?php if ( $lvc_area ) : // No beach_access taxonomy here — THV's own area fallback slot. ?>
			<div class="lvc-strip__stat">
				<span class="lvc-strip__value lvc-strip__value--sm"><?php echo esc_html( $lvc_area ); ?></span>
				<span class="lvc-strip__label">Area</span>
			</div>
			<?php endif; ?>

			<div class="lvc-strip__stat lvc-strip__stat--rate">
				<span class="lvc-strip__value lvc-strip__value--rate"><?php echo $lvc_rate['amount'] > 0 ? esc_html( '$' . number_format_i18n( $lvc_rate['amount'] ) ) : 'On request'; ?></span>
				<span class="lvc-strip__label"><?php echo $lvc_rate['amount'] > 0 ? 'From / night' : 'Nightly rate'; ?></span>
			</div>
		</div>

		<div class="lvc-strip__ctas">
			<?php if ( $lvc_wa ) : ?>
			<a href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-btn lvc-btn--ghost">
				<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
				</svg>
				WhatsApp
			</a>
			<?php endif; ?>
			<a href="#inquiry" class="lvc-btn lvc-btn--accent">
				Request Availability &rarr;
			</a>
		</div>
	</div>

</section>

<?php if ( ! empty( $lvc_chips ) || $lvc_best_for ) : ?>
<section class="lvc-quickfacts" aria-label="Booking highlights">
	<div class="lvc-quickfacts__inner">
		<?php foreach ( $lvc_chips as $lvc_chip ) : ?>
			<span class="lvc-quickfacts__chip"><?php echo esc_html( $lvc_chip ); ?></span>
		<?php endforeach; ?>
		<span class="lvc-quickfacts__chip"><?php echo esc_html( $lvc_best_for ); ?></span>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	BRIDGE LINE (THV bridge_line → tagline, four-tier resolved)
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_tagline ) : ?>
<section class="lvc-bridge" aria-label="Property highlight">
	<p class="lvc-bridge__text"><?php echo esc_html( wp_strip_all_tags( $lvc_tagline ) ); ?></p>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	BREADCRUMBS — archive crumb resolved from the CPT (THV bug fixed)
	═══════════════════════════════════════════════════════════════════════════ -->
<nav class="lvc-breadcrumb" aria-label="Breadcrumb">
	<ol class="lvc-breadcrumb__list">
		<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
		<?php if ( $lvc_archive_url ) : ?>
		<li><a href="<?php echo esc_url( $lvc_archive_url ); ?>">Luxury Villas</a></li>
		<?php endif; ?>
		<?php if ( $lvc_area && $lvc_area_url ) : ?>
		<li><a href="<?php echo esc_url( $lvc_area_url ); ?>"><?php echo esc_html( $lvc_area ); ?></a></li>
		<?php endif; ?>
		<li aria-current="page"><?php echo esc_html( $lvc_display ); ?></li>
	</ol>
</nav>

<!-- ═══════════════════════════════════════════════════════════════════════════
	INTRO / PRIMARY SELLING POINT (THV primary_selling_point → intro_paragraph)
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_intro ) : ?>
<section class="lvc-intro" aria-label="Property highlight">
	<div class="lvc-intro__inner">
		<p class="lvc-intro__quote"><?php echo esc_html( wp_strip_all_tags( $lvc_intro ) ); ?></p>
		<span class="lvc-intro__target"><?php echo esc_html( $lvc_best_for ); ?></span>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	GALLERY SQUARES — THV custom-squares-gallery clone (max 6 uniform 1:1
	tiles, first 3 eager-loaded like THV; full set lives in the slider below)
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_squares ) : ?>
<section class="lvc-squares" aria-label="Villa photo gallery">
	<div class="lvc-squares__inner">
		<div class="lvc-squares__header">
			<span class="lvc-label">Gallery</span>
			<h2 class="lvc-heading">Explore Every Space</h2>
		</div>
		<div class="lvc-squares__grid" role="list" aria-label="Villa image gallery">
			<?php foreach ( array_slice( $lvc_squares, 0, 6 ) as $lvc_sq_i => $lvc_g ) : ?>
			<figure class="lvc-squares__tile" role="listitem">
				<img src="<?php echo esc_url( $lvc_g ); ?>" alt="<?php echo esc_attr( $lvc_display . ' photo ' . ( $lvc_sq_i + 1 ) ); ?>"<?php echo $lvc_sq_i >= 3 ? ' loading="lazy"' : ''; ?> decoding="async">
				<span class="lvc-squares__overlay" aria-hidden="true"></span>
			</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	PROPERTY DESCRIPTION + H1
	═══════════════════════════════════════════════════════════════════════════ -->
<section class="lvc-content" id="description">

	<div class="lvc-section">
		<span class="lvc-label">About This <?php echo esc_html( lvc_config( 'cpt_singular', 'Villa' ) ); ?></span>
		<h1 class="lvc-content__h1"><?php echo esc_html( $lvc_h1 ); ?></h1>
		<?php if ( $lvc_desc ) : ?>
		<div class="lvc-body">
			<?php echo wp_kses_post( wpautop( $lvc_desc ) ); ?>
		</div>
		<?php endif; ?>
	</div>

	<!-- Description grid: Indoor / Outdoor / Bedrooms / Setting (+ Catering) -->
	<?php
	$lvc_desc_blocks = array(
		array(
			'title' => 'Indoor Design & Living',
			'text'  => $lvc_indoor,
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
		),
		array(
			'title' => 'Outdoor Spaces & Pool',
			'text'  => $lvc_outdoor,
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
		),
		array(
			'title' => 'Bedrooms & Guest Setup',
			'text'  => $lvc_bedrm,
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9V4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5"/><path d="M2 11v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-9"/><rect x="7" y="9" width="10" height="4" rx="1"/></svg>',
		),
		array(
			'title' => 'Setting & Positioning',
			'text'  => $lvc_setting, // Four-tier resolved — never blank.
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>',
		),
		array(
			'title' => 'Service & Catering',
			'text'  => $lvc_cater,
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h4v11h2V11h4a2 2 0 0 0 2-2V2h-2v5h-2V2h-2v5h-2V2H3z"/></svg>',
		),
	);
	$lvc_desc_blocks = array_values( array_filter( $lvc_desc_blocks, static function ( $b ) {
		return '' !== trim( (string) $b['text'] );
	} ) );
	?>

	<?php if ( $lvc_desc_blocks ) : ?>
	<div class="lvc-desc-grid" aria-label="Property spaces">
		<?php foreach ( $lvc_desc_blocks as $lvc_block ) : ?>
		<div class="lvc-desc-block">
			<h2 class="lvc-desc-block__title">
				<?php echo $lvc_block['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
				<?php echo esc_html( $lvc_block['title'] ); ?>
			</h2>
			<div class="lvc-desc-block__text"><?php echo wp_kses_post( wpautop( $lvc_block['text'] ) ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

</section>

<section class="lvc-assist" aria-label="Booking assistance">
	<div class="lvc-assist__inner">
		<div class="lvc-assist__copy">
			<h2 class="lvc-assist__title">Need help deciding if this villa fits your trip?</h2>
			<p class="lvc-assist__sub">Share your dates, group profile, and priorities. Our team will confirm fit, suggest alternatives if needed, and help you secure the right villa in <?php echo esc_html( lvc_config( 'region' ) ); ?> quickly.</p>
		</div>
		<div class="lvc-assist__ctas">
			<a href="#inquiry" class="lvc-btn lvc-btn--accent">Request Availability</a>
			<?php if ( $lvc_wa ) : ?>
			<a href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-btn lvc-btn--ghost">Talk to a Villa Specialist</a>
			<?php endif; ?>
		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
	GUEST REVIEWS — this property's verified reviews only (THV's hardcoded
	brand reviews are not ported; section self-hides without data)
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_testimonials ) : ?>
<section class="lvc-reviews" aria-label="Guest reviews">
	<div class="lvc-reviews__inner">
		<header class="lvc-reviews__header">
			<span class="lvc-label">Guest Experiences</span>
			<h2 class="lvc-heading">What Guests Say</h2>
		</header>

		<div class="lvc-reviews__grid">
			<?php foreach ( $lvc_testimonials as $lvc_t ) : ?>
			<div class="lvc-review-card">
				<blockquote class="lvc-review-card__quote">
					&ldquo;<?php echo esc_html( $lvc_t['quote'] ); ?>&rdquo;
				</blockquote>
				<footer class="lvc-review-card__footer">
					<?php if ( $lvc_t['name'] ) : ?>
					<span class="lvc-review-card__name"><?php echo esc_html( $lvc_t['name'] ); ?></span>
					<?php endif; ?>
					<?php
					$lvc_t_meta = implode( ' · ', array_filter( array( $lvc_t['loc'], $lvc_t['date'] ) ) );
					if ( $lvc_t_meta ) :
						?>
					<span class="lvc-review-card__meta"><?php echo esc_html( $lvc_t_meta ); ?></span>
					<?php endif; ?>
				</footer>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	GALLERY SLIDER — full photo set, THV pc-slider clone. theme.js still owns
	the behavior via the data-lvc-slider* attributes (scroll-snap + counter).
	═══════════════════════════════════════════════════════════════════════════ -->
<?php
$lvc_has_slider = count( $lvc_slides ) > 1;
$lvc_has_faq    = ( is_array( $lvc_faq ) && count( $lvc_faq ) >= 2 );
?>
<?php if ( $lvc_has_slider || $lvc_has_faq ) : ?>
<section class="lvc-media-faq" id="gallery-slider" aria-label="Photo gallery and frequently asked questions">
	<div class="lvc-media-faq__grid<?php echo ( $lvc_has_slider && $lvc_has_faq ) ? '' : ' lvc-media-faq__grid--single'; ?>">
		<?php if ( $lvc_has_slider ) : ?>
		<div class="lvc-media-faq__media">
			<div class="lvc-slider" data-lvc-slider>
				<div class="lvc-slider__track" data-lvc-slider-track tabindex="0" role="group" aria-label="Photo carousel, scrollable">
					<?php foreach ( $lvc_slides as $lvc_sl_i => $lvc_s ) : ?>
					<figure class="lvc-slider__slide"><img src="<?php echo esc_url( $lvc_s ); ?>" alt="<?php echo esc_attr( $lvc_display . ' photo ' . ( $lvc_sl_i + 1 ) ); ?>" loading="lazy" decoding="async"></figure>
					<?php endforeach; ?>
				</div>
				<button class="lvc-slider__nav lvc-slider__nav--prev" type="button" data-lvc-slider-prev aria-label="Previous photo">&#8249;</button>
				<button class="lvc-slider__nav lvc-slider__nav--next" type="button" data-lvc-slider-next aria-label="Next photo">&#8250;</button>
				<p class="lvc-slider__count"><span data-lvc-slider-current>1</span> / <?php echo (int) count( $lvc_slides ); ?></p>
			</div>
		</div>
		<?php endif; ?>
		<?php if ( $lvc_has_faq ) : ?>
		<div class="lvc-media-faq__faq">
			<header class="lvc-faq__header">
				<span class="lvc-label">Good to Know</span>
				<h2 class="lvc-heading">Questions About <?php echo esc_html( $lvc_display ); ?></h2>
			</header>
			<dl class="lvc-faq__list">
				<?php foreach ( $lvc_faq as $lvc_qa ) : ?>
				<div class="lvc-faq__item">
					<dt class="lvc-faq__q"><?php echo esc_html( $lvc_qa['question'] ); ?></dt>
					<dd class="lvc-faq__a"><?php echo wp_kses_post( wpautop( $lvc_qa['answer'] ) ); ?></dd>
				</div>
				<?php endforeach; ?>
			</dl>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	WHAT'S INCLUDED — two groups from real list fields (THV inferred these
	from its amenity taxonomy; here the lists are editorial, four-tier resolved)
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_included || $lvc_request ) : ?>
<section class="lvc-included" aria-label="What is included">
	<div class="lvc-included__inner">
		<header class="lvc-included__header">
			<span class="lvc-label">Direct Booking Benefits</span>
			<h2 class="lvc-heading">What's Included</h2>
		</header>

		<div class="lvc-included__groups">
			<?php if ( $lvc_included ) : ?>
			<div class="lvc-included__group">
				<span class="lvc-included__group-label">Included with direct booking</span>
				<div class="lvc-included__list">
					<?php foreach ( $lvc_included as $lvc_item ) : ?>
					<div class="lvc-included__item"><?php echo esc_html( $lvc_item ); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
			<?php if ( $lvc_request ) : ?>
			<div class="lvc-included__group">
				<span class="lvc-included__group-label">Available on request</span>
				<div class="lvc-included__list">
					<?php foreach ( $lvc_request as $lvc_item ) : ?>
					<div class="lvc-included__item lvc-included__item--optional"><?php echo esc_html( $lvc_item ); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	FAQ — "Good to Know" (property repeater, else universal set)
	═══════════════════════════════════════════════════════════════════════════ -->

<!-- ═══════════════════════════════════════════════════════════════════════════
	INQUIRY FORM — core AJAX form (handler + Turnstile + honeypot preserved)
	═══════════════════════════════════════════════════════════════════════════ -->
<section class="lvc-inquiry" id="inquiry" aria-label="Inquiry form">
	<div class="lvc-inquiry__inner">
		<header class="lvc-inquiry__header">
			<span class="lvc-inquiry__label">Direct Booking · No Platform Fees</span>
			<h2 class="lvc-inquiry__title">Plan Your Stay</h2>
			<?php if ( $lvc_editor ) : ?>
			<p class="lvc-inquiry__sub"><?php echo esc_html( wp_strip_all_tags( $lvc_editor ) ); ?></p>
			<?php endif; ?>
			<p class="lvc-inquiry__sub">We typically respond <?php echo esc_html( lvc_config( 'response_time', 'soon' ) ); ?>.</p>
		</header>

		<?php
		get_template_part( 'template-parts/inquiry-form', null, array(
			'property_name' => $lvc_title,
			'submit_label'  => 'Request Availability',
		) );
		?>

		<?php if ( $lvc_wa ) : ?>
		<p class="lvc-inquiry__wa-alt">
			Prefer to chat? <a href="<?php echo esc_url( $lvc_wa ); ?>" target="_blank" rel="noopener noreferrer">Message us on WhatsApp &#x2192;</a>
		</p>
		<?php endif; ?>
	</div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
	LOCATION / AREA CONTEXT (beach_access + airport_transfer dropped — no data)
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_area || $lvc_view || $lvc_access ) : ?>
<section class="lvc-location" aria-label="Location and area context">
	<div class="lvc-location__inner">

		<div class="lvc-location__main">
			<span class="lvc-location__label">Location</span>
			<h2 class="lvc-location__area"><?php echo esc_html( $lvc_area ? $lvc_area : lvc_config( 'region' ) ); ?></h2>
			<p class="lvc-location__destination"><?php echo esc_html( $lvc_area ? $lvc_area . ', ' . lvc_config( 'region' ) : lvc_config( 'region' ) . ', Caribbean' ); ?></p>
		</div>

		<div class="lvc-location__meta">
			<?php if ( $lvc_access ) : ?>
			<div class="lvc-location__meta-item">
				<span class="lvc-location__meta-label">Access</span>
				<span class="lvc-location__meta-value"><?php echo esc_html( $lvc_access ); ?></span>
			</div>
			<?php endif; ?>
			<?php if ( $lvc_beds ) : ?>
			<div class="lvc-location__meta-item">
				<span class="lvc-location__meta-label">Bedrooms</span>
				<span class="lvc-location__meta-value"><?php echo esc_html( $lvc_beds ); ?></span>
			</div>
			<?php endif; ?>
			<?php if ( $lvc_guests ) : ?>
			<div class="lvc-location__meta-item">
				<span class="lvc-location__meta-label">Max Guests</span>
				<span class="lvc-location__meta-value"><?php echo esc_html( $lvc_guests ); ?></span>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( $lvc_view ) : ?>
		<div class="lvc-location__context">
			<p><?php echo esc_html( wp_strip_all_tags( $lvc_view ) ); ?></p>
		</div>
		<?php endif; ?>

	</div>
</section>
<?php endif; ?>

<?php if ( ! empty( $lvc_explore ) ) : ?>
<section class="lvc-explore-cards" aria-label="Where this villa fits">
	<div class="lvc-explore-cards__inner">
		<span class="lvc-label">Where This Villa Fits</span>
		<h2 class="lvc-explore-cards__title">Explore Related <em>Villas</em></h2>
		<div class="lvc-explore-cards__grid">
			<?php foreach ( $lvc_explore as $lvc_e ) : ?>
			<a href="<?php echo esc_url( $lvc_e['url'] ); ?>" class="lvc-explore-card">
				<?php if ( ! empty( $lvc_e['image'] ) ) : ?>
				<div class="lvc-explore-card__bg" style="background-image:url('<?php echo esc_url( $lvc_e['image'] ); ?>')"></div>
				<?php endif; ?>
				<div class="lvc-explore-card__overlay"></div>
				<div class="lvc-explore-card__body">
					<h3 class="lvc-explore-card__name"><?php echo esc_html( $lvc_e['anchor'] ); ?></h3>
					<span class="lvc-explore-card__cta">Browse &#x2192;</span>
				</div>
			</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	SIMILAR VILLAS — deterministic rotated pick via lvc_related_properties()
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( $lvc_related ) : ?>
<section class="lvc-similar" aria-label="Similar villas">
	<div class="lvc-similar__inner">
		<header class="lvc-similar__header">
			<span class="lvc-label">Explore More</span>
			<h2 class="lvc-heading">
				<?php if ( $lvc_area ) : ?>
					More Villas in <em><?php echo esc_html( $lvc_area ); ?></em>
				<?php else : ?>
					More Luxury Villas
				<?php endif; ?>
			</h2>
		</header>

		<div class="lvc-similar__grid">
			<?php
			foreach ( $lvc_related as $lvc_rid ) :
				$lvc_r_name  = get_the_title( $lvc_rid );
				$lvc_r_beds  = lvc_field( 'bed_count', $lvc_rid );
				$lvc_r_gsts  = lvc_field( 'guests_max', $lvc_rid );
				$lvc_r_rate  = function_exists( 'lvc_property_rate' ) ? lvc_property_rate( $lvc_rid ) : array( 'label' => 'Rates on request' );
				$lvc_r_img   = lvc_property_image( $lvc_rid, 'large', 'card' );
				$lvc_r_terms = get_the_terms( $lvc_rid, 'area' );
				$lvc_r_area  = ( $lvc_r_terms && ! is_wp_error( $lvc_r_terms ) ) ? $lvc_r_terms[0]->name : '';
				?>
			<a href="<?php echo esc_url( get_permalink( $lvc_rid ) ); ?>" class="lvc-similar__card">
				<div class="lvc-similar__image<?php echo $lvc_r_img ? '' : ' lvc-similar__image--empty'; ?>"<?php echo $lvc_r_img ? ' style="background-image:url(\'' . esc_url( $lvc_r_img ) . '\')"' : ''; ?> role="img" aria-label="<?php echo esc_attr( $lvc_r_name ); ?> exterior"></div>
				<div class="lvc-similar__content">
					<h3 class="lvc-similar__name"><?php echo esc_html( $lvc_r_name ); ?></h3>
					<?php if ( $lvc_r_area ) : ?>
					<p class="lvc-similar__area"><?php echo esc_html( $lvc_r_area ); ?>, <?php echo esc_html( lvc_config( 'region' ) ); ?></p>
					<?php endif; ?>
					<div class="lvc-similar__meta">
						<?php if ( $lvc_r_beds ) : ?>
						<span class="lvc-similar__meta-item"><?php echo esc_html( $lvc_r_beds ); ?> Bed</span>
						<?php endif; ?>
						<?php if ( $lvc_r_gsts ) : ?>
						<span class="lvc-similar__meta-item"><?php echo esc_html( $lvc_r_gsts ); ?> Guests</span>
						<?php endif; ?>
						<span class="lvc-similar__meta-item"><?php echo esc_html( $lvc_r_rate['label'] ); ?></span>
					</div>
				</div>
			</a>
			<?php endforeach; ?>
		</div>

		<nav class="lvc-similar__links" aria-label="Explore more villas">
			<?php if ( $lvc_area && $lvc_area_url ) : ?>
			<a href="<?php echo esc_url( $lvc_area_url ); ?>" class="lvc-similar__link">
				<?php echo esc_html( 'Villas in ' . $lvc_area ); ?> &#x2192;
			</a>
			<?php endif; ?>
			<?php if ( $lvc_archive_url ) : ?>
			<a href="<?php echo esc_url( $lvc_archive_url ); ?>" class="lvc-similar__link">
				All <?php echo esc_html( lvc_config( 'region' ) ); ?> Villas &#x2192;
			</a>
			<?php endif; ?>
		</nav>
	</div>
</section>
<?php endif; ?>

<!-- Sticky mobile CTA bar — brand.css .lvc-single__mobilebar (shown < 900px) -->
<div class="lvc-single__mobilebar">
	<span class="lvc-single__mobilebar-name"><?php echo esc_html( $lvc_display ); ?></span>
	<a class="lvc-btn" href="#inquiry">Request Availability</a>
</div>

</div><!-- .lvc-wrap -->

<script>
(function () {
	// Smooth scroll for in-page anchors (photo-count button, CTA buttons).
	document.querySelectorAll('.lvc-wrap a[href^="#"]').forEach(function (link) {
		link.addEventListener('click', function (e) {
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

	// Check-in can't be in the past; auto-set min check-out from check-in.
	var arrivalInput   = document.querySelector('.lvc-inquiry input[name="checkin"]');
	var departureInput = document.querySelector('.lvc-inquiry input[name="checkout"]');
	if (arrivalInput && departureInput) {
		var lvcToday = new Date().toISOString().slice(0, 10);
		arrivalInput.min = lvcToday;
		arrivalInput.addEventListener('change', function () {
			if (this.value) {
				departureInput.min = this.value;
				if (departureInput.value && departureInput.value <= this.value) {
					departureInput.value = '';
				}
			}
		});
	}
})();
</script>

	<?php
endwhile;

get_footer();

