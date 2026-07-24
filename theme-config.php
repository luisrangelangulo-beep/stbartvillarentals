<?php
/**
 * Luxury Villa Theme Core — BRAND CONFIGURATION
 * ─────────────────────────────────────────────────────────────────────────
 * This is the ONE file you edit to spin up a new site (plus assets/brand.css).
 * Every template and include reads brand-specific values from here via
 * lvc_config('key'). Nothing brand-specific should be hardcoded anywhere else.
 *
 * You can also override any value without editing this file by hooking the
 * 'lvc_config' filter from a small site plugin or mu-plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_config' ) ) {
	/**
	 * Accessor for the brand configuration.
	 *
	 * @param string|null $key     Config key, or null for the whole array.
	 * @param mixed       $default Fallback when the key is missing.
	 * @return mixed
	 */
	function lvc_config( $key = null, $default = '' ) {
		static $config = null;

		if ( null === $config ) {
			$config = apply_filters( 'lvc_config', array(

				/* ── Brand identity ───────────────────────────────────── */
				'brand_name'     => 'St Barts Villa Rentals',
				'brand_tagline'  => 'Private villas across St Barthélemy, booked direct.',
				'brand_logo_svg' => '', // Inline SVG markup; empty = render brand_name as text.

				/* ── Contact / inquiry routing ────────────────────────── */
				// TODO before launch: this is the WP admin address, which points
				// at the rmoceanfrontrentals domain and is a placeholder, not a
				// decision. Set a real St Barts mailbox and confirm the domain has
				// MX records — a no-MX domain bounces inquiries silently.
				'support_email'  => 'luis@rmoceanfrontrentals.com',
				'owner_email'    => '', // Owner leads; empty = falls back to support_email.
				'phone'          => '', // e.g. '+1 (000) 000-0000'; empty = hide.
				// Left empty deliberately: this brand has no WhatsApp line yet and
				// the button hides itself when unset. Do not borrow another
				// brand's number — it routes guests to the wrong concierge.
				'whatsapp_url'   => '',
				'response_time'  => 'within 24 hours',
				'region'         => 'St Barthélemy', // Page schema areaServed.
				// Social profile URLs → schema sameAs (knowledge-panel signal). Empty = omitted.
				'social_profiles' => array(
					'https://www.facebook.com/rmoceanfront/',
					'https://twitter.com/rmvillarentals',
					'https://www.instagram.com/luxuryoceanfrontrentals/',
					'https://in.pinterest.com/retreatsluxuryoceanfront/',
					'https://www.youtube.com/channel/UCJMopNTDTjs4Qp89n5T_Orw',
				),

				/* ── Property model (CPT) ─────────────────────────────── */
				'cpt'              => 'villa',          // villa | chalet | condo | property
				'cpt_singular'     => 'Villa',
				'cpt_plural'       => 'Villas',
				'cpt_archive_slug' => 'luxury-villas',  // /luxury-villas/ (CPT archive)
				'cpt_rewrite_slug' => 'luxury-villas',  // single: /luxury-villas/{slug}/
				// TRUE here, unlike Anguilla: this site has no CPT UI plugin and
				// no villa CPT at all (cptui_post_types is empty, verified
				// 2026-07-22), so the theme owns the registration. Nothing exists
				// to preserve — there is no villa content on this domain yet.
				'register_cpt'     => true,

				/* ── Taxonomies: slug => [ plural label, singular label ] ─ */
				// No `destination` taxonomy: St Barthélemy is a single island, so
				// `area` carries the geography — same shape as Anguilla.
				'taxonomies' => array(
					'area'         => array( 'Areas', 'Area' ),
					'collection'   => array( 'Collections', 'Collection' ),
					'bedrooms'     => array( 'Bedrooms', 'Bedrooms' ),
					'beach_access' => array( 'Beach Access', 'Beach Access' ),
					'amenity'      => array( 'Amenities', 'Amenity' ),
				),
				'register_taxonomies' => true,

				/* ── Page slugs (nav + internal links) ────────────────── */
				// Intended slugs. NONE of these pages exist yet — the 15 pages on
				// this domain belong to the previous project. Create them (and
				// assign the matching page-templates/) before activating the
				// theme, or the nav links 404.
				'pages' => array(
					'contact'  => 'contact',
					'request'  => 'villa-request',
					'about'    => 'about',
					'how'      => 'how-it-works',
					'owners'   => 'list-your-villa',
					'magazine' => 'magazine',
				),

				/* ── Inquiry engine ───────────────────────────────────── */
				'inquiry_action' => 'lvc_inquiry', // AJAX action + nonce name.

				/* ── SEO posture ──────────────────────────────────────── */
				'theme_owns_schema'  => true, // Suppress Rank Math schema; theme emits JSON-LD.
				'noindex_thin_terms' => true, // noindex taxonomy terms under min_index_count.
				// Minimum properties an archive needs before it may be indexed. 3
				// is the portfolio default: below it an archive is thin enough to
				// read as a doorway page. Raising this is safe; dropping it to 1
				// re-opens the gap it exists to close.
				'min_index_count'    => 3,
				// Taxonomy mega menu. Each entry becomes a column of term links in
				// the header panel, giving every taxonomy archive an inbound link
				// from every page — without one they are orphans that cannot rank.
				//   label    column heading
				//   limit    max terms (0 = all)
				//   counts   show the property count beside each term
				//   compact  render as chips rather than rows (for numeric facets)
				//   range    [min,max] numeric slug filter, e.g. to skip thin
				//            10–12 bedroom terms that are deliberately noindexed
				// Every column self-hides while its taxonomy has no terms, so the
				// whole panel stays absent until content lands — no empty menu.
				'nav_mega'           => array(
					'area'       => array( 'label' => 'Areas', 'limit' => 0, 'counts' => true ),
					'collection' => array( 'label' => 'Browse by Style', 'limit' => 8, 'counts' => true ),
					'bedrooms'   => array( 'label' => 'Browse by Size', 'limit' => 0, 'counts' => false, 'compact' => true, 'range' => array( 3, 9 ) ),
				),
				'geo'                => array( 'lat' => '17.9000', 'lng' => '-62.8333' ), // St Barthelemy.
			) );
		}

		if ( null === $key ) {
			return $config;
		}

		return array_key_exists( $key, $config ) ? $config[ $key ] : $default;
	}
}
