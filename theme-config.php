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
				// Inquiry recipient AND the address printed on the contact and
				// legal pages.
				//
				// ⚠️ HOW THIS DELIVERS TODAY (checked 2026-08-01): the domain's MX
				// points at itself — cPanel's local mail exchanger — and there is
				// NO mailbox called support@. Delivery works only because
				// /etc/valiases carries a catch-all (`*: stbartvillarenta`), so
				// mail lands in the cPanel account's own inbox, readable via
				// cPanel webmail. It does NOT bounce, but nothing notifies anyone.
				//
				// Create a real support@ mailbox or a forwarder to a monitored
				// address in cPanel → Email Accounts / Forwarders. Until then,
				// check that inbox — a villa enquiry is worth $30k–45k and will
				// sit there unread.
				'support_email'  => 'support@stbartvillarentals.com',
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
				// These MUST match the published pages exactly — lvc_page_url()
				// builds links straight from them, so a mismatch is a silent 404
				// in the nav rather than an error anywhere.
				//
				// Corrected 2026-08-01: 'request' said `villa-request` and 'about'
				// said `about`, while the live pages are `st-barts-villa-request`
				// and `about-us`. The request link — the inquiry page, i.e. the
				// conversion target — was a hard 404 from the villa archive.
				// Fixed here rather than by renaming the pages, because the pages
				// are published and in the sitemap; changing config costs nothing
				// while changing slugs would need redirects.
				'pages' => array(
					'contact'  => 'contact',
					'request'  => 'st-barts-villa-request',
					'about'    => 'about-us',
					'how'      => 'how-it-works',
					'owners'   => 'list-your-villa',
					'magazine' => 'magazine',
					// Must match a real published Page with this exact slug —
					// page-areas.php (WP template hierarchy) renders it.
					'areas'    => 'areas',
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
				// Taxonomies whose term archives are DATA, not landing pages —
				// noindexed wholesale and kept out of the XML sitemap regardless
				// of how many properties they hold.
				//
				// `amenity` qualifies: "villas with air conditioning" is near-zero
				// commercial intent, and 18–26 such archives are near-duplicates
				// of each other and of the main archive. That is the exact shape
				// that left 63 of Punta Mita's pages "crawled, not indexed" —
				// weak URLs spending the crawl budget the villa pages need.
				//
				// `beach_access` deliberately stays indexable: beachfront vs
				// oceanfront is high-intent and is the distinction the brand
				// makes precisely. `area` is core geography.
				'noindex_taxonomies' => array( 'amenity' ),
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
