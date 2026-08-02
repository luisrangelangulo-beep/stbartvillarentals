<?php
/**
 * Luxury Villa Theme Core — taxonomy TERM ACF fields.
 * ─────────────────────────────────────────────────────────────────────────
 * Gives archive/landing terms a hero image, intro copy, and (for places) geo +
 * hierarchy — so destination/area/collection pages can be skinned and ranked.
 * Image URLs are Cloudflare R2 (no media-library upload). No-op without ACF.
 *
 * The same field NAMES (hero_image_url, intro, …) are reused across groups with
 * distinct keys. Term IDs are unique across taxonomies and term meta is keyed by
 * term ID, so the shared names cannot collide. Read them UNPREFIXED —
 * lvc_field( 'intro', 'term_' . $id ), never 'area_intro'.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'lvc_register_term_fields' );

function lvc_register_term_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// Shared across every archive term: long-form body copy rendered BELOW the
	// property grid, an FAQ repeater, and the search-visibility switch.
	//
	// intro vs body is a deliberate split, learned on Punta Mita: `intro` is the
	// short lede beside the H1, `body` is the depth pass that makes the archive
	// rank for its own term instead of being a bare grid. Keeping them separate
	// means the hero stays short while the page still carries real content.
	// Keys are namespaced by $prefix because this runs once per group and ACF
	// field keys — including repeater sub-fields — must be globally unique.
	$lvc_depth_fields = function ( $prefix ) {
		$faq_subfields = array(
			array( 'key' => 'field_lvc_' . $prefix . '_faq_q', 'label' => 'Question', 'name' => 'question', 'type' => 'text', 'wrapper' => array( 'width' => '50' ) ),
			array( 'key' => 'field_lvc_' . $prefix . '_faq_a', 'label' => 'Answer', 'name' => 'answer', 'type' => 'textarea', 'rows' => 3, 'wrapper' => array( 'width' => '50' ) ),
		);

		return array(
			array(
				'key'          => 'field_lvc_' . $prefix . '_body',
				'label'        => 'Body Copy (below the grid)',
				'name'         => 'body',
				'type'         => 'wysiwyg',
				'tabs'         => 'visual',
				'media_upload' => 0,
				'instructions' => 'Long-form copy shown under the property grid. Supply your own H2/H3 headings — the template adds none, because the term name is usually already the H1. Leave blank to hide the section. Do NOT write property counts or rates into this copy; they go stale within hours (see docs/LESSONS_LEARNED.md).',
			),
			array(
				'key'          => 'field_lvc_' . $prefix . '_faq',
				'label'        => 'FAQ Items',
				'name'         => 'faq_items',
				'type'         => 'repeater',
				'layout'       => 'row',
				'button_label' => 'Add FAQ',
				'sub_fields'   => $faq_subfields,
				'instructions' => 'Needs 2+ complete rows before the FAQ section and its FAQPage schema render — a single-question FAQPage reads as thin to Google.',
			),
			array(
				'key'           => 'field_lvc_' . $prefix . '_visible',
				'label'         => 'Show in search (index)',
				'name'          => 'search_visible',
				'type'          => 'true_false',
				'default_value' => 1,
				'ui'            => 1,
				'instructions'  => 'On = this archive may appear in Google. Off = noindex. Archives with too few properties are noindexed automatically regardless of this setting (min_index_count in theme-config.php).',
			),
		);
	};

	/* ── Place terms: destination + area — hero, intro, geo, hierarchy ── */
	acf_add_local_field_group( array(
		'key'    => 'group_lvc_place_terms',
		'title'  => 'Place — Hero, Intro & Geo',
		'fields' => array(
			array( 'key' => 'field_lvc_term_hero', 'label' => 'Hero Image URL', 'name' => 'hero_image_url', 'type' => 'url', 'instructions' => 'Cloudflare R2 image. Single source for BOTH this archive hero and the term cards on the homepage — set it once here.' ),
			array( 'key' => 'field_lvc_term_tagline', 'label' => 'Tagline', 'name' => 'tagline', 'type' => 'text', 'instructions' => 'One line shown under the name on term cards, e.g. "Oceanfront estates of six to ten bedrooms."' ),
			array( 'key' => 'field_lvc_term_intro', 'label' => 'Intro / Landing Copy', 'name' => 'intro', 'type' => 'wysiwyg', 'tabs' => 'visual', 'media_upload' => 0 ),
			array( 'key' => 'field_lvc_term_lat', 'label' => 'Latitude', 'name' => 'geo_lat', 'type' => 'text', 'instructions' => 'For schema areaServed / geo.' ),
			array( 'key' => 'field_lvc_term_lng', 'label' => 'Longitude', 'name' => 'geo_lng', 'type' => 'text' ),
			array( 'key' => 'field_lvc_term_parent_dest', 'label' => 'Parent Destination', 'name' => 'parent_destination', 'type' => 'text', 'instructions' => 'Areas only — the destination slug this area sits under (e.g. dominican-republic). Used for breadcrumbs/nav.' ),
			array( 'key' => 'field_lvc_term_primary_beach', 'label' => 'Primary Beach', 'name' => 'primary_beach', 'type' => 'text', 'instructions' => 'The beach every villa in this area inherits as its nearest, e.g. "Flamands Beach". Leave BLANK for hillside or interior areas with no obvious nearest beach — blank renders nothing, which is better than a wrong beach on a villa page.' ),
			array( 'key' => 'field_lvc_term_primary_beach_note', 'label' => 'Primary Beach — Note', 'name' => 'primary_beach_note', 'type' => 'text', 'maxlength' => 90, 'instructions' => 'Optional qualifier shown after the beach name, e.g. "a short drive". Do NOT invent walk times: a wrong "5-minute walk" is worse than no claim at all.' ),
			...$lvc_depth_fields( 'place' ),
		),
		'location' => array(
			array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'destination' ) ),
			array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'area' ) ),
		),
	) );

	/* ── Landing terms: the collection-style pages — hero + intro only ── */
	$landing = array( 'collection', 'bedrooms', 'beach_access' );
	$location = array();
	foreach ( $landing as $tax ) {
		$location[] = array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => $tax ) );
	}

	acf_add_local_field_group( array(
		'key'    => 'group_lvc_landing_terms',
		'title'  => 'Landing — Hero & Intro',
		'fields' => array(
			array( 'key' => 'field_lvc_lterm_hero', 'label' => 'Hero Image URL', 'name' => 'hero_image_url', 'type' => 'url', 'instructions' => 'Cloudflare R2 image for the landing hero.' ),
			array( 'key' => 'field_lvc_lterm_intro', 'label' => 'Intro / Landing Copy', 'name' => 'intro', 'type' => 'wysiwyg', 'tabs' => 'visual', 'media_upload' => 0 ),
			...$lvc_depth_fields( 'landing' ),
		),
		'location' => $location,
	) );
}
