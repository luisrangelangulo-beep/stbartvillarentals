<?php
/**
 * St Barts Villa Rentals — Single Post (magazine article).
 *
 * Cloned from tulumholidayvillas-theme single.php (v3.0 refined editorial dark
 * luxe) per the clone-don't-rebuild rule: same hero / byline / typography /
 * sticky sidebar (trust bar, featured villa, contact, TOC) / keep-reading /
 * villas sections, recolored to the St Barts salt-rose palette (lvc- prefix).
 *
 * Adapted to this site's data model:
 * - Every post IS a magazine article here (no magazine-CPT conditional).
 * - Hero image = post thumbnail; graceful dark surface when absent.
 * - Schema stays with inc/seo/schema.php — the template calls
 *   lvc_schema_article() exactly as the previous single.php did (load-bearing:
 *   templates own the schema calls in this theme). No inline JSON-LD.
 * - Category breadcrumb uses get_category_link() — ONE URL source, avoiding
 *   THV's visible-vs-schema breadcrumb divergence.
 * - THV's ACF inline-CTA repeater / featured-villa-override / read-time
 *   machinery doesn't exist on this site: the TOC processor is ported without
 *   the CTA-injection pass, and the sidebar villa + bottom grid are fed by
 *   lvc_related_properties_for_post() (shared area terms), preserving the
 *   previous single.php's SEO-to-booking bridge.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build villa card data array (sidebar mini + bottom grid) from this site's
 * property model: card_title / bed_count / bath_count / guests_max fields,
 * `area` + `bedrooms` taxonomies, lvc_property_image() for the curated image.
 *
 * @param int $villa_id Villa post ID.
 * @return array|null
 */
if ( ! function_exists( 'lvc_villa_card_data' ) ) {
	function lvc_villa_card_data( $villa_id ) {
		$villa_id = (int) $villa_id;
		if ( ! $villa_id || ! get_post( $villa_id ) ) {
			return null;
		}

		$name = (string) lvc_field( 'card_title', $villa_id, get_the_title( $villa_id ) );
		$img  = function_exists( 'lvc_property_image' ) ? lvc_property_image( $villa_id ) : get_the_post_thumbnail_url( $villa_id, 'medium_large' );

		$area_terms = get_the_terms( $villa_id, 'area' );
		$location   = ( ! empty( $area_terms ) && ! is_wp_error( $area_terms ) )
			? $area_terms[0]->name . ', ' . lvc_config( 'region' )
			: lvc_config( 'region' );

		$bedrooms = (string) lvc_field( 'bed_count', $villa_id );
		if ( '' === $bedrooms ) {
			$bt       = get_the_terms( $villa_id, 'bedrooms' );
			$bedrooms = ( $bt && ! is_wp_error( $bt ) ) ? preg_replace( '/\D/', '', $bt[0]->name ) : '';
		}
		$guests = (string) lvc_field( 'guests_max', $villa_id );
		$rate   = function_exists( 'lvc_property_rate' ) ? lvc_property_rate( $villa_id ) : array( 'label' => 'Rates on request' );

		$specs = array();
		if ( $bedrooms ) {
			$specs[] = $bedrooms . ' BR';
		}
		if ( $guests ) {
			$specs[] = 'Sleeps ' . $guests;
		}

		return array(
			'id'       => $villa_id,
			'url'      => get_permalink( $villa_id ),
			'name'     => $name,
			'image'    => $img,
			'location' => $location,
			'specs'    => implode( ' · ', $specs ),
			'rate'     => $rate['label'],
		);
	}
}

/**
 * Process article HTML:
 *   1. Auto-generate IDs on H2s for jump-link anchors
 *   2. Build TOC array (label respects optional data-toc-label override)
 *
 * Returns: ['content' => HTML, 'toc' => [['id', 'label'], ...]]
 *
 * (THV's inline-CTA placeholder pass is intentionally not ported — its ACF
 * repeater fields don't exist on this site.)
 *
 * @param string $content Filtered HTML from apply_filters('the_content', ...).
 * @return array
 */
if ( ! function_exists( 'lvc_process_article_content' ) ) {
	function lvc_process_article_content( $content ) {
		$result = array(
			'content' => $content,
			'toc'     => array(),
		);
		if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
			return $result;
		}

		// The template owns the page H1. Imported article copy occasionally still
		// starts with another H1; demote only that first body heading before the
		// TOC pass so every article has one clear document heading.
		$content = preg_replace( '/<h1(\\s[^>]*)?>/i', '<h2$1>', $content, 1 );
		$content = preg_replace( '/<\\/h1>/i', '</h2>', $content, 1 );

		// Suppress libxml warnings (HTML5 elements like <figure> etc.).
		$prev = libxml_use_internal_errors( true );

		$dom = new DOMDocument( '1.0', 'UTF-8' );
		// Wrap to make inner extraction simple. UTF-8 hint via declaration.
		$wrapped = '<?xml encoding="UTF-8"?><div id="lvc-wrap">' . $content . '</div>';
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		$toc      = array();
		$used_ids = array();

		// Snapshot H2 list (NodeList is live; iterate a copy safely).
		$h2_nodes = array();
		$h2s      = $dom->getElementsByTagName( 'h2' );
		foreach ( $h2s as $h2 ) {
			$h2_nodes[] = $h2;
		}

		foreach ( $h2_nodes as $h2 ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$text = trim( $h2->textContent );
			if ( '' === $text ) {
				continue;
			}

			$custom_label = $h2->getAttribute( 'data-toc-label' );
			$existing_id  = $h2->getAttribute( 'id' );
			$slug         = ( $existing_id ? $existing_id : sanitize_title( $text ) );

			// Disambiguate duplicate IDs.
			$base = $slug;
			$n    = 2;
			while ( isset( $used_ids[ $slug ] ) ) {
				$slug = $base . '-' . $n;
				++$n;
			}
			$used_ids[ $slug ] = true;

			if ( ! $existing_id ) {
				$h2->setAttribute( 'id', $slug );
			}
			if ( $custom_label ) {
				$h2->removeAttribute( 'data-toc-label' );
			}

			$toc[] = array(
				'id'    => $slug,
				'label' => ( '' !== $custom_label ) ? $custom_label : $text,
			);
		}

		// Save inner contents of #lvc-wrap.
		$wrap   = $dom->getElementById( 'lvc-wrap' );
		$output = '';
		if ( $wrap ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			foreach ( $wrap->childNodes as $child ) {
				$output .= $dom->saveHTML( $child );
			}
		} else {
			$output = $dom->saveHTML();
		}

		$result['content'] = $output;
		$result['toc']     = $toc;
		return $result;
	}
}

get_header();

while ( have_posts() ) :
	the_post();

	// ═══════════════════════════════════════════════════════════════════════
	// SETUP.
	// ═══════════════════════════════════════════════════════════════════════

	$article_post_id = get_the_ID();

	// Schema: inc/seo/schema.php owns it; templates make the call (same
	// contract as the previous single.php — do not emit inline JSON-LD here).
	if ( function_exists( 'lvc_schema_article' ) ) {
		lvc_schema_article( $article_post_id );
	}

	$hero_img    = function_exists( 'lvc_blog_image_url' ) ? lvc_blog_image_url( $article_post_id, 'full' ) : get_the_post_thumbnail_url( $article_post_id, 'full' );
	$read_time   = function_exists( 'lvc_article_read_time' ) ? lvc_article_read_time( $article_post_id ) : (string) lvc_field( 'read_time', $article_post_id );
	$author_name = (string) lvc_field( 'author_name', $article_post_id, lvc_brand() . ' Team' );
	$lede        = has_excerpt( $article_post_id ) ? get_the_excerpt( $article_post_id ) : '';

	$magazine_url  = lvc_page_url( 'magazine' );
	$request_url   = lvc_page_url( 'request' );
	$whatsapp_url  = lvc_whatsapp_url();
	$support_email = (string) lvc_config( 'support_email', '' );

	// ONE URL source for the category (visible breadcrumb + any schema use):
	// standard get_category_link(), no custom magazine-category routes.
	$article_cats = get_the_category( $article_post_id );
	$article_cat  = ( ! empty( $article_cats ) && ! is_wp_error( $article_cats ) ) ? $article_cats[0] : null;
	$cat_url      = '';
	if ( $article_cat ) {
		$maybe_cat_url = get_category_link( $article_cat );
		$cat_url       = is_wp_error( $maybe_cat_url ) ? '' : $maybe_cat_url;
	}

	// Process content (H2 anchors + TOC).
	$raw_content = apply_filters( 'the_content', get_the_content() );
	$raw_content = str_replace( ']]>', ']]&gt;', $raw_content );
	$processed   = lvc_process_article_content( $raw_content );

	// Related properties bridge (shared area terms): first villa feeds the
	// sidebar mini, the full set feeds the bottom grid.
	$related_villa_ids   = function_exists( 'lvc_related_properties_for_post' ) ? lvc_related_properties_for_post( $article_post_id, 3 ) : array();
	$featured_villa_data = $related_villa_ids ? lvc_villa_card_data( $related_villa_ids[0] ) : null;

	// Related guides (3 latest in same category, exclude current).
	$related_guides = array();
	if ( $article_cat ) {
		$rg_query = new WP_Query(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 3,
				'post__not_in'   => array( $article_post_id ),
				'cat'            => $article_cat->term_id,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		if ( $rg_query->have_posts() ) {
			foreach ( $rg_query->posts as $rg ) {
				$rg_cat           = get_the_category( $rg->ID );
				$related_guides[] = array(
					'url'   => get_permalink( $rg->ID ),
					'title' => get_the_title( $rg->ID ),
					'image' => function_exists( 'lvc_blog_image_url' ) ? lvc_blog_image_url( $rg->ID, 'medium' ) : get_the_post_thumbnail_url( $rg->ID, 'medium' ),
					'cat'   => ( ! empty( $rg_cat ) && ! is_wp_error( $rg_cat ) ) ? $rg_cat[0]->name : '',
				);
			}
		}
		wp_reset_postdata();
	}
	?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	MAGAZINE ARTICLE — cloned from THV v3.0 editorial dark luxe, St Barts salt-rose palette
	═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ───────────────────────────────────────────────────────────── tokens */
.lvc-mag {
	--lvc-bg:           #12100f;
	--lvc-rule:         rgba(245,240,234,0.06);
	--lvc-rule-strong:  rgba(255,255,255,0.18);
	--lvc-text:         rgba(255,255,255,0.78);
	--lvc-text-soft:    rgba(255,255,255,0.55);
	--lvc-text-mute:    rgba(255,255,255,0.35);
	--lvc-text-strong:  #fff;
	--lvc-accent:       #c2818c;
	--lvc-accent-soft:  rgba(194,129,140,0.10);
	--lvc-fd:           'Gilda Display', Georgia, serif;
	--lvc-fb:           'Outfit', system-ui, sans-serif;
	--lvc-ease:         cubic-bezier(.2,.8,.2,1);
}

/* ───────────────────────────────────────────────────────────── reset overrides */
.lvc-mag * { box-sizing: border-box; }

/* ───────────────────────────────────────────────────────────── page backdrop
	Cover the article page with the dark ground regardless of the body default. */
body { background: var(--lvc-bg, #12100f) !important; }
.lvc-mag {
	background: var(--lvc-bg);
	position: relative;
	color: var(--lvc-text);
}

/* ───────────────────────────────────────────────────────────── reading progress */
.lvc-progress {
	position: fixed; top: 0; left: 0;
	height: 2px; width: 0%;
	background: var(--lvc-accent);
	z-index: 9999;
	transition: width 0.1s ease-out;
	pointer-events: none;
}

/* ───────────────────────────────────────────────────────────── breadcrumbs */
.lvc-crumbs {
	max-width: 1240px; margin: 0 auto;
	padding: 110px var(--lvc-px, 2rem) 0.5rem;
	font-family: var(--lvc-fb);
	font-size: 0.74rem; color: var(--lvc-text-mute);
	letter-spacing: 0.04em;
	display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;
}
.lvc-crumbs a {
	color: var(--lvc-text-mute);
	text-decoration: none;
	transition: color 0.2s ease;
	border: 0;
}
.lvc-crumbs a:hover { color: var(--lvc-accent); }
.lvc-crumbs__sep { color: var(--lvc-text-mute); opacity: 0.5; }
.lvc-crumbs__current { color: var(--lvc-text-soft); }

/* ───────────────────────────────────────────────────────────── hero */
.lvc-mag-hero {
	position: relative;
	height: 70vh; min-height: 480px; max-height: 760px;
	margin-bottom: 5rem; overflow: hidden;
	background: var(--lvc-bg);
}
.lvc-mag-hero__bg {
	position: absolute; inset: 0;
	background-size: cover; background-position: center;
	z-index: 0;
}
.lvc-mag-hero__veil {
	position: absolute; inset: 0;
	background:
		linear-gradient(180deg, rgba(18,16,15,0.2) 0%, rgba(18,16,15,0.55) 55%, rgba(18,16,15,0.95) 100%);
	z-index: 1;
}
.lvc-mag-hero__content {
	position: absolute; bottom: 0; left: 0; right: 0;
	padding: 3.5rem var(--lvc-px, 2rem) 4rem;
	max-width: 1240px; margin: 0 auto;
	z-index: 2;
}
.lvc-mag-hero__meta {
	display: flex; align-items: center; gap: 0.7rem;
	margin-bottom: 1.5rem;
	font-family: var(--lvc-fb);
	font-size: 0.7rem; letter-spacing: 0.16em; text-transform: uppercase;
	color: var(--lvc-accent); font-weight: 500;
	flex-wrap: wrap;
}
.lvc-mag-hero__meta-sep {
	color: var(--lvc-text-mute);
	font-weight: 300;
}
.lvc-mag-hero__title {
	font-family: var(--lvc-fd);
	font-size: clamp(2.25rem, 5.5vw, 4.25rem);
	font-weight: 400;
	color: #fff;
	line-height: 1.05;
	letter-spacing: -0.01em;
	margin: 0 0 1.25rem;
	max-width: 880px;
}
.lvc-mag-hero__lede {
	font-family: var(--lvc-fb);
	font-size: clamp(1rem, 1.4vw, 1.15rem);
	color: rgba(255,255,255,0.78);
	line-height: 1.7;
	max-width: 660px;
	margin: 0;
}

/* ───────────────────────────────────────────────────────────── frame */
.lvc-frame {
	max-width: 1240px;
	margin: 0 auto;
	padding: 0 var(--lvc-px, 2rem) 5rem;
	display: grid;
	grid-template-columns: minmax(0, 1fr) 300px;
	gap: 5rem;
	align-items: start; /* CRITICAL for sticky to work */
}
.lvc-mag-main { min-width: 0; }

/* ───────────────────────────────────────────────────────────── byline */
.lvc-byline {
	display: flex; align-items: center; gap: 1rem;
	padding-bottom: 2rem; margin-bottom: 3rem;
	border-bottom: 1px solid var(--lvc-rule);
	max-width: 720px;
}
.lvc-byline__person { flex: 1; min-width: 0; }
.lvc-byline__name {
	font-family: var(--lvc-fd);
	font-size: 1rem;
	color: var(--lvc-text-strong);
	margin: 0;
	font-weight: 500;
}
.lvc-byline__role {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	color: var(--lvc-text-mute);
	margin: 0.25rem 0 0;
	letter-spacing: 0.1em;
	text-transform: uppercase;
}
.lvc-byline__date {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-text-mute);
	flex-shrink: 0;
}

/* ───────────────────────────────────────────────────────────── article body */
.lvc-article {
	font-family: var(--lvc-fb);
	font-size: 1.0625rem; /* 17px */
	line-height: 1.8;
	color: var(--lvc-text);
	max-width: 720px;
	overflow-wrap: anywhere;
}

/* drop cap on first paragraph */
.lvc-article > p:first-of-type::first-letter {
	font-family: var(--lvc-fd);
	float: left;
	font-size: 5em;
	line-height: 0.85;
	padding: 0.08em 0.12em 0 0;
	color: var(--lvc-text-strong);
	font-weight: 400;
}

.lvc-article p { margin: 0 0 1.5em; }

.lvc-article h2 {
	font-family: var(--lvc-fd);
	font-size: clamp(1.65rem, 2.6vw, 2.05rem);
	color: var(--lvc-text-strong);
	font-weight: 400;
	line-height: 1.2;
	letter-spacing: -0.005em;
	margin: 3.75rem 0 1.25rem;
}
.lvc-article h2::before {
	content: '';
	display: block;
	width: 36px;
	height: 1px;
	background: var(--lvc-accent);
	margin-bottom: 1.25rem;
}

.lvc-article h3 {
	font-family: var(--lvc-fd);
	font-size: 1.45rem;
	color: var(--lvc-text-strong);
	font-weight: 500;
	line-height: 1.3;
	margin: 2.5rem 0 0.85rem;
}

.lvc-article a {
	color: var(--lvc-accent);
	text-decoration: none;
	border-bottom: 1px solid rgba(194,129,140,0.4);
	transition: border-color 0.2s, color 0.2s;
}
.lvc-article a:hover {
	color: #fff;
	border-bottom-color: #fff;
}

.lvc-article ul, .lvc-article ol {
	margin: 0 0 1.6em;
	padding-left: 1.4em;
}
.lvc-article li { margin: 0 0 0.55em; }
.lvc-article li::marker { color: var(--lvc-accent); }

.lvc-article blockquote {
	margin: 3em 0;
	padding: 0;
	border: 0;
	font-family: var(--lvc-fd);
	font-style: italic;
	font-size: clamp(1.35rem, 2.2vw, 1.6rem);
	line-height: 1.4;
	color: var(--lvc-text-strong);
	text-align: center;
}
.lvc-article blockquote::before,
.lvc-article blockquote::after {
	content: '';
	display: block;
	width: 56px;
	height: 1px;
	background: var(--lvc-accent);
	margin: 1.4em auto;
}
.lvc-article blockquote p { margin: 0; }

.lvc-article img {
	max-width: 100%;
	height: auto;
	margin: 2.25em auto;
	display: block;
	border-radius: 2px;
}
.lvc-article figure { margin: 2.25em 0; }
.lvc-article figcaption {
	font-family: var(--lvc-fb);
	font-size: 0.78rem;
	color: var(--lvc-text-mute);
	text-align: center;
	margin-top: 0.75rem;
	letter-spacing: 0.04em;
}

/* defensive styles for content elements WP authors commonly use */
.lvc-article hr {
	border: 0;
	height: 1px;
	background: var(--lvc-rule-strong);
	margin: 3rem auto;
	max-width: 200px;
}

.lvc-article table {
	width: 100%;
	border-collapse: collapse;
	margin: 2rem 0;
	font-family: var(--lvc-fb);
	font-size: 0.92rem;
}
.lvc-article th,
.lvc-article td {
	padding: 0.85rem 1rem;
	text-align: left;
	border-bottom: 1px solid var(--lvc-rule);
	background: transparent;
}
.lvc-article th {
	font-size: 0.7rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-text-mute);
	font-weight: 500;
	border-bottom-color: var(--lvc-rule-strong);
}
.lvc-article td { color: var(--lvc-text); }
.lvc-article tr:last-child td { border-bottom: 0; }

.lvc-article code {
	font-family: 'SF Mono', Menlo, Monaco, 'Courier New', monospace;
	font-size: 0.88em;
	background: rgba(245,240,234,0.06);
	padding: 0.15em 0.4em;
	border-radius: 2px;
	color: var(--lvc-text-strong);
}
.lvc-article pre {
	background: rgba(255,255,255,0.04);
	padding: 1.25rem 1.5rem;
	border-radius: 2px;
	overflow-x: auto;
	margin: 2rem 0;
	border: 1px solid var(--lvc-rule);
}
.lvc-article pre code {
	background: transparent;
	padding: 0;
	font-size: 0.85rem;
	color: var(--lvc-text);
}

/* offset for sticky header anchor jumps */
.lvc-article h2[id] { scroll-margin-top: 100px; }

/* ───────────────────────────────────────────────────────────── final CTA */
.lvc-finalcta {
	margin-top: 5rem;
	padding-top: 4rem;
	border-top: 1px solid var(--lvc-rule-strong);
	max-width: 720px;
}
.lvc-finalcta__eyebrow {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin: 0 0 1rem;
	font-weight: 500;
}
.lvc-finalcta__title {
	font-family: var(--lvc-fd);
	font-size: clamp(1.85rem, 3.2vw, 2.5rem);
	color: var(--lvc-text-strong);
	font-weight: 400;
	line-height: 1.1;
	letter-spacing: -0.005em;
	margin: 0 0 1.25rem;
}
.lvc-finalcta__sub {
	font-family: var(--lvc-fb);
	font-size: 1rem;
	color: var(--lvc-text-soft);
	line-height: 1.7;
	margin: 0 0 2.25rem;
	max-width: 560px;
}
.lvc-finalcta__btns {
	display: flex; gap: 0.85rem; flex-wrap: wrap;
}

/* ───────────────────────────────────────────────────────────── buttons */
.lvc-mag-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
	padding: 0.95rem 1.6rem;
	font-family: var(--lvc-fb);
	font-size: 0.72rem;
	font-weight: 600;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	text-decoration: none !important;
	border-radius: 1px;
	transition: all 0.2s ease;
	cursor: pointer;
	border: 1px solid transparent !important;
	white-space: nowrap;
}
.lvc-mag-btn--solid {
	background: var(--lvc-accent);
	color: #12100f !important;
	border-color: var(--lvc-accent) !important;
}
.lvc-mag-btn--solid:hover {
	background: #d9a0a9;
	border-color: #d9a0a9 !important;
	transform: translateY(-1px);
}
.lvc-mag-btn--ghost {
	background: transparent;
	color: var(--lvc-text-strong) !important;
	border-color: rgba(255,255,255,0.25) !important;
}
.lvc-mag-btn--ghost:hover {
	border-color: rgba(255,255,255,0.6) !important;
	background: rgba(255,255,255,0.04);
}
.lvc-mag-btn--full { width: 100%; }

/* ───────────────────────────────────────────────────────────── sidebar */
.lvc-aside {
	position: relative;
}
.lvc-aside__sticky {
	position: sticky;
	top: 100px;
	display: flex;
	flex-direction: column;
	gap: 2rem;
	/* No max-height constraint — sidebar should fit naturally on desktop.
		On taller content, the bottom widgets scroll with the page (acceptable). */
}

/* trust bar */
.lvc-trust {
	padding-bottom: 2rem;
}
.lvc-trust__list {
	list-style: none;
	margin: 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 0.85rem;
}
.lvc-trust__item {
	display: flex;
	align-items: flex-start;
	gap: 0.75rem;
	font-family: var(--lvc-fb);
	font-size: 0.85rem;
	color: var(--lvc-text);
	line-height: 1.5;
}
.lvc-trust__icon {
	flex-shrink: 0;
	width: 18px;
	height: 18px;
	border-radius: 50%;
	background: rgba(194,129,140,0.12);
	color: var(--lvc-accent);
	font-size: 0.7rem;
	font-weight: 700;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	margin-top: 1px;
}

/* contact buttons (sidebar) */
.lvc-contact__btns {
	display: flex;
	flex-direction: column;
	gap: 0.6rem;
}
.lvc-mag-btn--wa {
	background: #25D366 !important;
	color: #fff !important;
	border-color: #25D366 !important;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.55rem;
}
.lvc-mag-btn--wa:hover {
	background: #1ebd5b !important;
	border-color: #1ebd5b !important;
	transform: translateY(-1px);
}
.lvc-mag-btn__icon {
	flex-shrink: 0;
	display: inline-block;
	line-height: 0;
}
.lvc-aside__sticky::-webkit-scrollbar { width: 4px; }
.lvc-aside__sticky::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 2px; }
.lvc-aside__sticky::-webkit-scrollbar-track { background: transparent; }

.lvc-widget {
	padding-bottom: 2.25rem;
	border-bottom: 1px solid var(--lvc-rule);
}
.lvc-widget:last-child { border-bottom: 0; padding-bottom: 0; }

.lvc-widget__eyebrow {
	font-family: var(--lvc-fb);
	font-size: 0.66rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin: 0 0 0.85rem;
	font-weight: 500;
}
.lvc-widget__title {
	font-family: var(--lvc-fd);
	font-size: 1.35rem;
	color: var(--lvc-text-strong);
	font-weight: 400;
	line-height: 1.2;
	margin: 0 0 0.85rem;
}
.lvc-widget__text {
	font-family: var(--lvc-fb);
	font-size: 0.86rem;
	color: var(--lvc-text-soft);
	line-height: 1.7;
	margin: 0 0 1.25rem;
}

/* villa mini (sidebar) */
.lvc-villa-mini {
	display: block;
	text-decoration: none !important;
	color: inherit;
	border: 0 !important;
}
.lvc-villa-mini__media {
	aspect-ratio: 4 / 3;
	background-color: rgba(255,255,255,0.04);
	background-size: cover;
	background-position: center;
	margin-bottom: 1rem;
	overflow: hidden;
	transition: transform 0.5s var(--lvc-ease);
}
.lvc-villa-mini:hover .lvc-villa-mini__media { transform: scale(1.02); }
.lvc-villa-mini__loc {
	font-family: var(--lvc-fb);
	font-size: 0.62rem;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--lvc-text-mute);
	margin: 0 0 0.4rem;
}
.lvc-villa-mini__name {
	font-family: var(--lvc-fd);
	font-size: 1.3rem;
	color: var(--lvc-text-strong);
	font-weight: 400;
	line-height: 1.15;
	margin: 0 0 0.5rem;
	transition: color 0.2s;
}
.lvc-villa-mini:hover .lvc-villa-mini__name { color: var(--lvc-accent); }
.lvc-villa-mini__specs {
	font-family: var(--lvc-fb);
	font-size: 0.76rem;
	color: var(--lvc-text-soft);
	margin: 0 0 0.85rem;
	letter-spacing: 0.04em;
}
.lvc-villa-mini__cta {
	font-family: var(--lvc-fb);
	font-size: 0.66rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	font-weight: 600;
}

/* TOC */
.lvc-toc {
	list-style: none;
	margin: 0;
	padding: 0;
	counter-reset: tocnum;
}
.lvc-toc__item { margin: 0; }
.lvc-toc__item a {
	display: flex;
	align-items: baseline;
	gap: 0.85rem;
	padding: 0.7rem 0;
	color: var(--lvc-text-soft);
	text-decoration: none !important;
	border: 0 !important;
	border-bottom: 1px solid var(--lvc-rule) !important;
	transition: color 0.2s;
	font-family: var(--lvc-fb);
	font-size: 0.85rem;
	line-height: 1.4;
}
.lvc-toc__item:last-child a { border-bottom: 0 !important; }
.lvc-toc__item a:hover { color: var(--lvc-text-strong); }
.lvc-toc__item--active a { color: var(--lvc-text-strong); }
.lvc-toc__num {
	font-family: var(--lvc-fb);
	font-size: 0.66rem;
	color: var(--lvc-text-mute);
	letter-spacing: 0.1em;
	flex-shrink: 0;
	width: 22px;
	transition: color 0.2s;
}
.lvc-toc__item--active .lvc-toc__num { color: var(--lvc-accent); }
.lvc-toc__label { flex: 1; min-width: 0; }

/* ───────────────────────────────────────────────────────────── keep reading */
.lvc-keepreading {
	padding: 5rem var(--lvc-px, 2rem) 1rem;
	max-width: 1240px;
	margin: 0 auto;
}
.lvc-keepreading__inner {
	border-top: 1px solid var(--lvc-rule);
	padding-top: 4rem;
}
.lvc-keepreading__eyebrow {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin: 0 0 1rem;
	font-weight: 500;
}
.lvc-keepreading__title {
	font-family: var(--lvc-fd);
	font-size: clamp(1.75rem, 3vw, 2.25rem);
	color: var(--lvc-text-strong);
	font-weight: 400;
	margin: 0 0 3rem;
	letter-spacing: -0.005em;
	line-height: 1.15;
}
.lvc-keepreading__grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 2rem;
}
.lvc-keepreading__card {
	display: block;
	text-decoration: none !important;
	color: inherit;
	border: 0 !important;
	transition: opacity 0.2s;
}
.lvc-keepreading__card:hover { opacity: 0.92; }
.lvc-keepreading__img {
	aspect-ratio: 16 / 10;
	background-color: rgba(255,255,255,0.04);
	background-size: cover;
	background-position: center;
	border-radius: 2px;
	margin-bottom: 1.25rem;
	overflow: hidden;
	transition: transform 0.5s var(--lvc-ease);
}
.lvc-keepreading__card:hover .lvc-keepreading__img { transform: scale(1.015); }
.lvc-keepreading__cat {
	font-family: var(--lvc-fb);
	font-size: 0.66rem;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin: 0 0 0.5rem;
	font-weight: 500;
}
.lvc-keepreading__cardtitle {
	font-family: var(--lvc-fd);
	font-size: 1.35rem;
	color: var(--lvc-text-strong);
	font-weight: 400;
	line-height: 1.2;
	margin: 0 0 0.75rem;
	transition: color 0.25s;
}
.lvc-keepreading__card:hover .lvc-keepreading__cardtitle { color: var(--lvc-accent); }
.lvc-keepreading__readmore {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	font-weight: 600;
}
@media (max-width: 980px) {
	.lvc-keepreading__grid { grid-template-columns: repeat(2, 1fr); gap: 1.75rem; }
	.lvc-keepreading { padding: 3.5rem var(--lvc-px, 1.5rem) 1rem; }
}
@media (max-width: 640px) {
	.lvc-keepreading__grid { grid-template-columns: 1fr; }
	.lvc-keepreading { padding: 3rem 1.25rem 1rem; }
}

/* ───────────────────────────────────────────────────────────── villas section */
.lvc-mag-villas {
	background: var(--lvc-bg);
	padding: 6rem var(--lvc-px, 2rem) 7rem;
	border-top: 1px solid var(--lvc-rule);
	margin-top: 4rem;
}
.lvc-mag-villas__inner { max-width: 1280px; margin: 0 auto; }
.lvc-mag-villas__eyebrow {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	letter-spacing: 0.2em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	text-align: center;
	margin: 0 0 1.25rem;
	font-weight: 500;
}
.lvc-mag-villas__title {
	font-family: var(--lvc-fd);
	font-size: clamp(2.5rem, 5vw, 3.5rem);
	color: var(--lvc-text-strong);
	font-weight: 400;
	text-align: center;
	margin: 0 0 4rem;
	line-height: 1;
	letter-spacing: -0.01em;
}
.lvc-mag-villas__title em {
	color: var(--lvc-accent);
	font-style: italic;
}
.lvc-mag-villas__grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 3rem 2rem;
}

.lvc-vcard {
	display: block;
	text-decoration: none !important;
	color: inherit;
	border: 0 !important;
	position: relative;
}
.lvc-vcard__media {
	aspect-ratio: 3 / 2;
	background-color: rgba(255,255,255,0.04);
	background-size: cover;
	background-position: center;
	margin-bottom: 1.4rem;
	overflow: hidden;
	position: relative;
	transition: transform 0.5s var(--lvc-ease);
}
.lvc-vcard:hover .lvc-vcard__media { transform: scale(1.015); }
.lvc-vcard__loc {
	font-family: var(--lvc-fb);
	font-size: 0.66rem;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--lvc-text-mute);
	margin: 0 0 0.55rem;
}
.lvc-vcard__name {
	font-family: var(--lvc-fd);
	font-size: 1.7rem;
	color: var(--lvc-text-strong);
	font-weight: 400;
	line-height: 1.15;
	margin: 0 0 0.65rem;
	letter-spacing: -0.005em;
	transition: color 0.25s;
}
.lvc-vcard:hover .lvc-vcard__name { color: var(--lvc-accent); }
.lvc-vcard__specs {
	font-family: var(--lvc-fb);
	font-size: 0.85rem;
	color: var(--lvc-text-soft);
	margin: 0 0 1.1rem;
	letter-spacing: 0.03em;
}
.lvc-vcard__foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding-top: 1.1rem;
	border-top: 1px solid var(--lvc-rule);
}
.lvc-vcard__price-empty {
	font-family: var(--lvc-fb);
	font-size: 0.72rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-text-mute);
}
.lvc-vcard__arrow {
	font-size: 1.1rem;
	color: var(--lvc-accent);
	transition: transform 0.3s var(--lvc-ease);
	line-height: 1;
}
.lvc-vcard:hover .lvc-vcard__arrow { transform: translateX(5px); }

/* ───────────────────────────────────────────────────────────── responsive */
@media (max-width: 1100px) {
	.lvc-frame { gap: 3.5rem; grid-template-columns: minmax(0, 1fr) 280px; }
	.lvc-mag-villas__grid { gap: 2.5rem 1.75rem; }
}

@media (max-width: 980px) {
	.lvc-frame {
		grid-template-columns: minmax(0, 1fr);
		gap: 2.5rem;
		padding-bottom: 3rem;
	}
	.lvc-aside__sticky {
		position: relative;
		top: 0;
		max-height: none;
		overflow: visible;
		gap: 2rem;
	}
	.lvc-mag-villas__grid { grid-template-columns: repeat(2, 1fr); gap: 2.5rem 1.75rem; }
	.lvc-mag-hero { height: 60vh; min-height: 420px; margin-bottom: 3.5rem; }
	.lvc-mag-villas { padding: 4.5rem var(--lvc-px, 1.5rem) 5rem; }
}

@media (max-width: 640px) {
	.lvc-crumbs { font-size: 0.7rem; padding-top: 100px; }
	.lvc-mag-hero__content { padding: 2.5rem var(--lvc-px, 1.25rem) 2.5rem; }
	.lvc-byline { flex-wrap: wrap; gap: 0.85rem; }
	.lvc-byline__date { width: 100%; padding-top: 0.5rem; }
	.lvc-mag-villas__grid { grid-template-columns: 1fr; gap: 2.5rem; }
	.lvc-article > p:first-of-type::first-letter {
		font-size: 4em;
	}
	.lvc-mag-villas { padding: 3.5rem 1.25rem 4rem; }
}
</style>

<div class="lvc-mag">

	<div class="lvc-progress" id="lvc-progress" aria-hidden="true"></div>

	<nav class="lvc-crumbs" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
		<span class="lvc-crumbs__sep">/</span>
		<a href="<?php echo esc_url( $magazine_url ); ?>">Magazine</a>
		<?php if ( $article_cat && $cat_url ) : ?>
			<span class="lvc-crumbs__sep">/</span>
			<a href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( $article_cat->name ); ?></a>
		<?php endif; ?>
		<span class="lvc-crumbs__sep">/</span>
		<span class="lvc-crumbs__current"><?php the_title(); ?></span>
	</nav>

	<div class="lvc-mag-hero">
		<?php if ( $hero_img ) : ?>
			<div class="lvc-mag-hero__bg" style="background-image: url('<?php echo esc_url( $hero_img ); ?>')"></div>
		<?php endif; ?>
		<div class="lvc-mag-hero__veil" aria-hidden="true"></div>
		<div class="lvc-mag-hero__content">
			<div class="lvc-mag-hero__meta">
				<?php if ( $article_cat ) : ?>
					<span><?php echo esc_html( $article_cat->name ); ?></span>
					<span class="lvc-mag-hero__meta-sep" aria-hidden="true">·</span>
				<?php endif; ?>
				<?php if ( $read_time ) : ?>
					<span><?php echo esc_html( $read_time ); ?></span>
					<span class="lvc-mag-hero__meta-sep" aria-hidden="true">·</span>
				<?php endif; ?>
				<span><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></span>
			</div>
			<h1 class="lvc-mag-hero__title"><?php the_title(); ?></h1>
			<?php if ( $lede ) : ?>
				<p class="lvc-mag-hero__lede"><?php echo esc_html( $lede ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="lvc-frame">

		<main class="lvc-mag-main">

			<div class="lvc-byline">
				<div class="lvc-byline__person">
					<p class="lvc-byline__name"><?php echo esc_html( $author_name ); ?></p>
					<p class="lvc-byline__role"><?php echo esc_html( lvc_config( 'region' ) ); ?> Villa Specialist</p>
				</div>
				<div class="lvc-byline__date"><?php echo esc_html( get_the_date( 'M Y' ) ); ?></div>
			</div>

			<article class="lvc-article">
				<?php
				// Output processed content (with H2 anchor IDs woven in).
				echo wp_kses_post( $processed['content'] );
				?>
			</article>

			<aside class="lvc-finalcta">
				<p class="lvc-finalcta__eyebrow">Plan your stay</p>
				<h2 class="lvc-finalcta__title">Find the right villa for your trip</h2>
				<p class="lvc-finalcta__sub">
					Our concierge team knows every villa on this island personally. Tell us your dates and group size &mdash; we&rsquo;ll send the <?php echo esc_html( lvc_config( 'region' ) ); ?> properties that fit.
				</p>
				<div class="lvc-finalcta__btns">
					<a href="<?php echo esc_url( $request_url ); ?>" class="lvc-mag-btn lvc-mag-btn--solid">Get Recommendations <span aria-hidden="true">&rarr;</span></a>
					<?php if ( $whatsapp_url ) : ?>
						<a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-mag-btn lvc-mag-btn--ghost">WhatsApp Us</a>
					<?php endif; ?>
				</div>
			</aside>

		</main>

		<aside class="lvc-aside" aria-label="Article sidebar">
			<div class="lvc-aside__sticky">

				<!-- Trust Bar -->
				<div class="lvc-widget lvc-trust">
					<ul class="lvc-trust__list">
						<li class="lvc-trust__item">
							<span class="lvc-trust__icon" aria-hidden="true">&#10003;</span>
							<span>Book direct, no platform fees</span>
						</li>
						<li class="lvc-trust__item">
							<span class="lvc-trust__icon" aria-hidden="true">&#10003;</span>
							<span>Local concierge support</span>
						</li>
						<li class="lvc-trust__item">
							<span class="lvc-trust__icon" aria-hidden="true">&#10003;</span>
							<span>Personal villa matching</span>
						</li>
					</ul>
				</div>

				<!-- Featured Villa -->
				<?php if ( $featured_villa_data ) : ?>
					<div class="lvc-widget">
						<p class="lvc-widget__eyebrow">Featured Villa</p>
						<a href="<?php echo esc_url( $featured_villa_data['url'] ); ?>" class="lvc-villa-mini">
							<?php if ( $featured_villa_data['image'] ) : ?>
								<div class="lvc-villa-mini__media" style="background-image:url('<?php echo esc_url( $featured_villa_data['image'] ); ?>')"></div>
							<?php endif; ?>
							<p class="lvc-villa-mini__loc"><?php echo esc_html( $featured_villa_data['location'] ); ?></p>
							<h4 class="lvc-villa-mini__name"><?php echo esc_html( $featured_villa_data['name'] ); ?></h4>
							<?php if ( $featured_villa_data['specs'] ) : ?>
								<p class="lvc-villa-mini__specs"><?php echo esc_html( $featured_villa_data['specs'] ); ?></p>
							<?php endif; ?>
							<span class="lvc-villa-mini__cta">View Villa &rarr;</span>
						</a>
					</div>
				<?php endif; ?>

				<!-- Contact Block -->
				<div class="lvc-widget">
					<p class="lvc-widget__eyebrow">Talk to us</p>
					<h3 class="lvc-widget__title">Talk to a local expert</h3>
					<p class="lvc-widget__text">Real humans who know <?php echo esc_html( lvc_config( 'region' ) ); ?>. Personal villa matching, no platform fees, fast response.</p>
					<div class="lvc-contact__btns">
						<?php if ( $whatsapp_url ) : ?>
							<a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-mag-btn lvc-mag-btn--wa lvc-mag-btn--full">
								<svg class="lvc-mag-btn__icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
								WhatsApp Us
							</a>
						<?php endif; ?>
						<?php if ( $support_email ) : ?>
							<a href="mailto:<?php echo esc_attr( $support_email ); ?>" class="lvc-mag-btn lvc-mag-btn--ghost lvc-mag-btn--full">
								Email Us
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Table of Contents -->
				<?php if ( ! empty( $processed['toc'] ) ) : ?>
					<div class="lvc-widget">
						<p class="lvc-widget__eyebrow">In This Guide</p>
						<ol class="lvc-toc">
							<?php foreach ( $processed['toc'] as $i => $item ) : ?>
								<li class="lvc-toc__item" data-lvc-toc-item="<?php echo esc_attr( $item['id'] ); ?>">
									<a href="#<?php echo esc_attr( $item['id'] ); ?>" data-lvc-toc-link="<?php echo esc_attr( $item['id'] ); ?>">
										<span class="lvc-toc__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
										<span class="lvc-toc__label"><?php echo esc_html( $item['label'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endif; ?>

			</div>
		</aside>
	</div>

	<?php if ( ! empty( $related_guides ) ) : ?>
		<section class="lvc-keepreading">
			<div class="lvc-keepreading__inner">
				<p class="lvc-keepreading__eyebrow">Keep Reading</p>
				<h2 class="lvc-keepreading__title">More from the magazine</h2>
				<div class="lvc-keepreading__grid">
					<?php foreach ( $related_guides as $g ) : ?>
						<a href="<?php echo esc_url( $g['url'] ); ?>" class="lvc-keepreading__card">
							<?php if ( $g['image'] ) : ?>
								<div class="lvc-keepreading__img" style="background-image:url('<?php echo esc_url( $g['image'] ); ?>')"></div>
							<?php else : ?>
								<div class="lvc-keepreading__img"></div>
							<?php endif; ?>
							<div class="lvc-keepreading__body">
								<?php if ( $g['cat'] ) : ?>
									<p class="lvc-keepreading__cat"><?php echo esc_html( $g['cat'] ); ?></p>
								<?php endif; ?>
								<h3 class="lvc-keepreading__cardtitle"><?php echo esc_html( $g['title'] ); ?></h3>
								<span class="lvc-keepreading__readmore">Read article &rarr;</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $related_villa_ids ) ) : ?>
		<section class="lvc-mag-villas">
			<div class="lvc-mag-villas__inner">
				<p class="lvc-mag-villas__eyebrow">Plan Your Stay</p>
				<h2 class="lvc-mag-villas__title">Villas for this <em>Experience</em></h2>
				<div class="lvc-mag-villas__grid">
					<?php
					foreach ( $related_villa_ids as $villa_id ) :
						$vd = lvc_villa_card_data( $villa_id );
						if ( ! $vd ) {
							continue;
						}
						?>
						<a href="<?php echo esc_url( $vd['url'] ); ?>" class="lvc-vcard">
							<?php if ( $vd['image'] ) : ?>
								<div class="lvc-vcard__media" style="background-image:url('<?php echo esc_url( $vd['image'] ); ?>')"></div>
							<?php else : ?>
								<div class="lvc-vcard__media" aria-hidden="true"></div>
							<?php endif; ?>
							<p class="lvc-vcard__loc"><?php echo esc_html( $vd['location'] ); ?></p>
							<h3 class="lvc-vcard__name"><?php echo esc_html( $vd['name'] ); ?></h3>
							<?php if ( $vd['specs'] ) : ?>
								<p class="lvc-vcard__specs"><?php echo esc_html( $vd['specs'] ); ?></p>
							<?php endif; ?>
							<div class="lvc-vcard__foot">
								<span class="lvc-vcard__price-empty"><?php echo esc_html( $vd['rate'] ); ?></span>
								<span class="lvc-vcard__arrow" aria-hidden="true">&rarr;</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

</div>

<script>
(function () {
	'use strict';

	// ─── Reading progress bar ────────────────────────────────────
	var bar = document.getElementById('lvc-progress');
	if (bar) {
		var updateProgress = function () {
			var scroll = window.pageYOffset || document.documentElement.scrollTop;
			var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
			var pct = height > 0 ? (scroll / height) * 100 : 0;
			bar.style.width = pct + '%';
		};
		window.addEventListener('scroll', updateProgress, { passive: true });
		updateProgress();
	}

	// ─── TOC scroll-spy ──────────────────────────────────────────
	var article = document.querySelector('.lvc-article');
	if (!article) return;

	var headings = article.querySelectorAll('h2[id]');
	var tocLinks = document.querySelectorAll('[data-lvc-toc-link]');
	if (!headings.length || !tocLinks.length) return;

	var itemMap = {};
	var allItems = document.querySelectorAll('[data-lvc-toc-item]');
	allItems.forEach(function (li) {
		itemMap[li.getAttribute('data-lvc-toc-item')] = li;
	});

	var setActive = function (id) {
		for (var key in itemMap) {
			if (itemMap.hasOwnProperty(key)) {
				itemMap[key].classList.remove('lvc-toc__item--active');
			}
		}
		if (id && itemMap[id]) {
			itemMap[id].classList.add('lvc-toc__item--active');
		}
	};

	var triggerOffset = 140; // distance from top of viewport
	var ticking = false;

	var updateActive = function () {
		var scrollY = window.pageYOffset || document.documentElement.scrollTop;
		var triggerY = scrollY + triggerOffset;
		var current = null;

		for (var i = 0; i < headings.length; i++) {
			var top = headings[i].getBoundingClientRect().top + scrollY;
			if (top <= triggerY) {
				current = headings[i];
			} else {
				break;
			}
		}

		// Before first H2: mark first as active so the TOC isn't blank.
		if (!current && headings.length) current = headings[0];
		if (current) setActive(current.id);
		ticking = false;
	};

	var onScroll = function () {
		if (!ticking) {
			window.requestAnimationFrame(updateActive);
			ticking = true;
		}
	};

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onScroll, { passive: true });
	updateActive();

	// Smooth scroll for TOC links (respects scroll-margin-top from CSS)
	tocLinks.forEach(function (link) {
		link.addEventListener('click', function (e) {
			var id = link.getAttribute('data-lvc-toc-link');
			var target = document.getElementById(id);
			if (!target) return;
			e.preventDefault();
			var top = target.getBoundingClientRect().top + window.pageYOffset - 100;
			window.scrollTo({ top: top, behavior: 'smooth' });
			// update URL without full jump.
			if (history.pushState) history.pushState(null, '', '#' + id);
		});
	});
})();
</script>

	<?php
endwhile;

get_footer();

