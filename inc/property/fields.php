<?php
/**
 * Luxury Villa Theme Core — core property ACF fields.
 * ─────────────────────────────────────────────────────────────────────────
 * Field NAMES are aligned 1:1 with the single-destination generator's sheet
 * columns (property_descr, indoor_living, …, faq_q1..faq_a4) so the sheet-sync
 * receiver maps straight in with no conversion. Brands extend this group via the
 * 'lvc_property_fields' filter rather than editing the core. No-op without ACF.
 *
 * Taxonomy-backed values (amenity, collection, catering, bedrooms, beach_access,
 * property_type, ideal_for) are assigned as TERMS by the sync — not stored here.
 * seo_title / meta_description are written to Rank Math post meta by the sync.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'lvc_register_property_fields' );

function lvc_register_property_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$cpt = lvc_config( 'cpt', 'villa' );

	$fields = apply_filters( 'lvc_property_fields', array(

		/* ── Identity ─────────────────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_identity', 'label' => 'Identity', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_community', 'label' => 'Community', 'name' => 'community', 'type' => 'text', 'instructions' => 'Stable community / development name (e.g. Las Palmas). community + lot = the permanent URL slug — survives marketing renames.' ),
		array( 'key' => 'field_lvc_lot', 'label' => 'Lot / Unit', 'name' => 'lot', 'type' => 'text', 'instructions' => 'Lot or unit number (e.g. 27).' ),
		array( 'key' => 'field_lvc_card_title', 'label' => 'Card / Display Title', 'name' => 'card_title', 'type' => 'text', 'instructions' => 'Marketing name shown on cards. Falls back to the post title.' ),
		array( 'key' => 'field_lvc_h1_title', 'label' => 'H1 (on-page heading)', 'name' => 'h1_title', 'type' => 'text', 'instructions' => 'SEO H1. Falls back to the post title.' ),
		array( 'key' => 'field_lvc_villa_aliases', 'label' => 'Aliases', 'name' => 'villa_aliases', 'type' => 'text', 'instructions' => 'Comma-separated former / marketing names → schema alternateName (catches searches for the old name).' ),

		/* ── Key Facts ────────────────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_facts', 'label' => 'Key Facts', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_bed_count', 'label' => 'Bedrooms', 'name' => 'bed_count', 'type' => 'number', 'min' => 0, 'max' => 50 ),
		array( 'key' => 'field_lvc_bath_count', 'label' => 'Bathrooms', 'name' => 'bath_count', 'type' => 'number', 'min' => 0, 'max' => 50, 'step' => '0.5' ),
		array( 'key' => 'field_lvc_guests_max', 'label' => 'Max Guests', 'name' => 'guests_max', 'type' => 'number', 'min' => 1, 'max' => 100 ),
		array( 'key' => 'field_lvc_nearby_beach', 'label' => 'Nearest Beach', 'name' => 'nearby_beach', 'type' => 'text', 'instructions' => 'Overrides the Area default only when this villa is genuinely nearer a different beach. Leave blank to inherit the Area — that is the normal case.' ),
		array( 'key' => 'field_lvc_nearby_beach_note', 'label' => 'Nearest Beach — Note', 'name' => 'nearby_beach_note', 'type' => 'text', 'maxlength' => 90, 'instructions' => 'Optional qualifier, e.g. "steps from the sand". Only state a distance or walk time if it is known to be true for THIS villa.' ),
		array(
			'key' => 'field_lvc_from_rate_tier', 'label' => 'From Rate Tier', 'name' => 'from_rate_tier', 'type' => 'select',
			'choices' => array( 'under-5k' => 'Under 5k', '5k-10k' => '5k-10k', '10k-20k' => '10k-20k', '20k-plus' => '20k-plus' ),
			'allow_null' => 1, 'return_format' => 'value',
		),
		array( 'key' => 'field_lvc_featured', 'label' => 'Featured', 'name' => 'featured', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0 ),

		/* ── Content (generated) ──────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_content', 'label' => 'Content', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_property_descr', 'label' => 'Overview', 'name' => 'property_descr', 'type' => 'wysiwyg', 'tabs' => 'visual', 'media_upload' => 0 ),
		array( 'key' => 'field_lvc_indoor_living', 'label' => 'Indoor Living', 'name' => 'indoor_living', 'type' => 'textarea', 'rows' => 4 ),
		array( 'key' => 'field_lvc_outdoor_living', 'label' => 'Outdoor Living', 'name' => 'outdoor_living', 'type' => 'textarea', 'rows' => 4 ),
		array( 'key' => 'field_lvc_bedroom_desc', 'label' => 'Bedrooms', 'name' => 'bedroom_desc', 'type' => 'textarea', 'rows' => 4 ),

		/* ── Experience & Service (generated) ─────────────────────── */
		array( 'key' => 'field_lvc_tab_service', 'label' => 'Experience & Service', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_travel_experience', 'label' => 'Travel Experience', 'name' => 'travel_experience', 'type' => 'text', 'instructions' => 'One of: beachfront, oceanfront, coastal-hillside, beach-town, island, golf-resort, marina-front. The sync derives the Collection term from this.' ),
		array( 'key' => 'field_lvc_catering_level', 'label' => 'Catering Level', 'name' => 'catering_level', 'type' => 'text', 'instructions' => 'e.g. self-catering, staffed, full-staff.' ),
		array( 'key' => 'field_lvc_catering_detail', 'label' => 'Catering Detail', 'name' => 'catering_detail', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_tags', 'label' => 'Tags', 'name' => 'tags', 'type' => 'text', 'instructions' => 'Comma-separated SEO tags.' ),

		/* ── Media ────────────────────────────────────────────────── */
		array( 'key' => 'field_lvc_tab_media', 'label' => 'Media', 'type' => 'tab' ),
		/* ── Curated images ───────────────────────────────────────────
		   Separate from the galleries on purpose. NOTHING derives these
		   from gallery position — photo 01 is an arbitrary frame, and on
		   Tulum all 46 populated properties ended up with the same shot
		   as both card and hero. See docs/LESSONS_LEARNED.md §2. */
		array( 'key' => 'field_lvc_feature_image', 'label' => 'Card Image', 'name' => 'feature_image', 'type' => 'url', 'instructions' => 'Curated image for grids, cards and social sharing. Always wins over the WordPress featured image.' ),
		array( 'key' => 'field_lvc_hero_image', 'label' => 'Hero Image', 'name' => 'hero_image', 'type' => 'url', 'instructions' => 'Curated banner image for the single page. Pick a DIFFERENT shot from the card image.' ),

		// Two galleries, matching the portfolio convention on every live site:
		// `gallery_squares` is a curated short set rendered as a grid, and
		// `gallery_slider` is the full shoot rendered as a carousel. Keep them
		// separate — the grid is an editorial pick, not the first N of the slider.
		array( 'key' => 'field_lvc_gallery', 'label' => 'Gallery — Squares (grid)', 'name' => 'gallery_squares', 'type' => 'textarea', 'instructions' => 'Curated set shown as the square grid, typically 6. One image URL per line, or comma-separated. NOT used as a card/hero image — those are curated separately.' ),
		array( 'key' => 'field_lvc_gallery_slider', 'label' => 'Gallery — Slider (full set)', 'name' => 'gallery_slider', 'type' => 'textarea', 'instructions' => 'The full photo set shown in the carousel. One image URL per line, or comma-separated. Falls back to the squares set when empty.' ),

		/* ── FAQ (flat — 1:1 with the generator) ──────────────────── */
		array( 'key' => 'field_lvc_tab_faq', 'label' => 'FAQ', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_faq_q1', 'label' => 'Q1', 'name' => 'faq_q1', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a1', 'label' => 'A1', 'name' => 'faq_a1', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_faq_q2', 'label' => 'Q2', 'name' => 'faq_q2', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a2', 'label' => 'A2', 'name' => 'faq_a2', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_faq_q3', 'label' => 'Q3', 'name' => 'faq_q3', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a3', 'label' => 'A3', 'name' => 'faq_a3', 'type' => 'textarea', 'rows' => 2 ),
		array( 'key' => 'field_lvc_faq_q4', 'label' => 'Q4', 'name' => 'faq_q4', 'type' => 'text' ),
		array( 'key' => 'field_lvc_faq_a4', 'label' => 'A4', 'name' => 'faq_a4', 'type' => 'textarea', 'rows' => 2 ),

		/* ── Editorial copy ───────────────────────────────────────────
		   Ported from Punta Mita. Each of these resolves through
		   lvc_content(): property → place term → universal option →
		   shipped default, so leaving one blank degrades gracefully
		   instead of rendering an empty section. */
		array( 'key' => 'field_lvc_tab_editorial', 'label' => 'Editorial', 'type' => 'tab' ),
		array( 'key' => 'field_lvc_tagline', 'label' => 'Tagline', 'name' => 'tagline', 'type' => 'text', 'maxlength' => 120, 'instructions' => 'One line under the H1. Falls back to the destination, then the universal tagline.' ),
		array( 'key' => 'field_lvc_intro_paragraph', 'label' => 'Intro Paragraph', 'name' => 'intro_paragraph', 'type' => 'textarea', 'rows' => 4, 'instructions' => 'Opening paragraph of the About section.' ),
		array( 'key' => 'field_lvc_setting_positioning', 'label' => 'Setting & Positioning', 'name' => 'setting_positioning', 'type' => 'textarea', 'rows' => 4, 'instructions' => 'Where the property sits and what that means for a stay.' ),
		array( 'key' => 'field_lvc_editorial_text', 'label' => 'Editorial Line', 'name' => 'editorial_text', 'type' => 'text', 'instructions' => 'Short pull-line used above the inquiry band.' ),
		array( 'key' => 'field_lvc_view_type', 'label' => 'View Type', 'name' => 'view_type', 'type' => 'text', 'instructions' => 'e.g. Ocean view, Beachfront. Shown as a highlight chip.' ),
		array( 'key' => 'field_lvc_access_type', 'label' => 'Access Type', 'name' => 'access_type', 'type' => 'text', 'instructions' => 'e.g. Beachfront, Walk to beach. Shown as a highlight chip.' ),

		/* ── What's included ──────────────────────────────────────── */
		array( 'key' => 'field_lvc_included_items', 'label' => 'Included', 'name' => 'included_items', 'type' => 'textarea', 'rows' => 6, 'instructions' => 'One item per line. Commas are content here, so lines only.' ),
		array( 'key' => 'field_lvc_on_request_items', 'label' => 'On Request', 'name' => 'on_request_items', 'type' => 'textarea', 'rows' => 6, 'instructions' => 'One item per line. Arranged but not included in the rate.' ),


		/* ── Rates & flags ────────────────────────────────────────── */
		array( 'key' => 'field_lvc_nightly_rate_from', 'label' => 'Nightly Rate From', 'name' => 'nightly_rate_from', 'type' => 'number', 'instructions' => 'Numeric, no currency symbol. Used for sorting and schema, not printed as a promise.' ),
		array( 'key' => 'field_lvc_minimum_stay', 'label' => 'Minimum Stay (nights)', 'name' => 'minimum_stay', 'type' => 'number', 'min' => 1, 'max' => 60, 'step' => 1, 'instructions' => 'This villa\'s normal minimum only. Holiday, event, and peak-season rules may differ. Leave empty if it varies.' ),
		array( 'key' => 'field_lvc_off_market', 'label' => 'Off market', 'name' => 'off_market', 'type' => 'true_false', 'ui' => 1, 'default_value' => 0, 'instructions' => 'Keeps the URL live for existing links but drops the property from the index and from listings.' ),
		array( 'key' => 'field_lvc_internal_notes', 'label' => 'Internal Notes', 'name' => 'internal_notes', 'type' => 'textarea', 'rows' => 3, 'instructions' => 'Never rendered on the front end.' ),

		/* ── FAQ (repeater) ───────────────────────────────────────────
		   Preferred over the flat faq_q1..a4 pairs above, which stay for
		   sheet-sync compatibility. Falls back to the universal set. */
		array(
			'key'          => 'field_lvc_faq_items',
			'label'        => 'FAQ Items',
			'name'         => 'faq_items',
			'type'         => 'repeater',
			'layout'       => 'row',
			'button_label' => 'Add FAQ',
			'instructions' => 'Needs 2+ complete rows before FAQPage schema is emitted.',
			'sub_fields'   => array(
				array( 'key' => 'field_lvc_faq_item_q', 'label' => 'Question', 'name' => 'question', 'type' => 'text', 'wrapper' => array( 'width' => '50' ) ),
				array( 'key' => 'field_lvc_faq_item_a', 'label' => 'Answer', 'name' => 'answer', 'type' => 'textarea', 'rows' => 3, 'wrapper' => array( 'width' => '50' ) ),
			),
		),

		/* ── Testimonials (repeater) ──────────────────────────────── */
		array(
			'key'          => 'field_lvc_testimonials',
			'label'        => 'Testimonials',
			'name'         => 'testimonials',
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => 'Add Testimonial',
			'instructions' => 'Real guest reviews only. An unverified or invented review is worse than an empty section at this price point.',
			'sub_fields'   => array(
				array( 'key' => 'field_lvc_t_quote', 'label' => 'Quote', 'name' => 'quote', 'type' => 'textarea', 'rows' => 3 ),
				array( 'key' => 'field_lvc_t_name', 'label' => 'Guest Name', 'name' => 'guest_name', 'type' => 'text', 'wrapper' => array( 'width' => '30' ) ),
				array( 'key' => 'field_lvc_t_location', 'label' => 'Guest Location', 'name' => 'guest_location', 'type' => 'text', 'wrapper' => array( 'width' => '30' ) ),
				array( 'key' => 'field_lvc_t_date', 'label' => 'Stay Date', 'name' => 'stay_date', 'type' => 'text', 'wrapper' => array( 'width' => '40' ) ),
				array( 'key' => 'field_lvc_t_rating', 'label' => 'Rating', 'name' => 'rating', 'type' => 'number', 'min' => 1, 'max' => 5, 'wrapper' => array( 'width' => '30' ) ),
				array( 'key' => 'field_lvc_t_verified', 'label' => 'Verified guest', 'name' => 'verified_guest', 'type' => 'true_false', 'ui' => 1, 'wrapper' => array( 'width' => '35' ) ),
				array( 'key' => 'field_lvc_t_source', 'label' => 'Source', 'name' => 'source_label', 'type' => 'text', 'wrapper' => array( 'width' => '35' ) ),
			),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lvc_property_core',
		'title'    => lvc_config( 'cpt_singular', 'Villa' ) . ' — Core Fields',
		'fields'   => $fields,
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => $cpt ) ) ),
		'position' => 'normal',
		'active'   => true,
	) );

	/* ── Universal content (tier 3 of lvc_content) ─────────────────────
	   One options page holding the site-wide defaults every property falls
	   back to. Editing a line here fixes that section on every property that
	   has not overridden it — which is the whole reason a sparse catalogue
	   still reads as finished. */
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( array(
			'page_title'  => 'Universal Content',
			'menu_title'  => 'Universal Content',
			'menu_slug'   => 'lvc-universal-content',
			'parent_slug' => 'edit.php?post_type=' . $cpt,
			'capability'  => 'edit_posts',
			'autoload'    => true,
		) );

		acf_add_local_field_group( array(
			'key'    => 'group_lvc_universal',
			'title'  => 'Universal Content — used when a ' . strtolower( (string) lvc_config( 'cpt_singular', 'villa' ) ) . ' leaves a field blank',
			'fields' => array(
				array( 'key' => 'field_lvc_u_tagline', 'label' => 'Tagline', 'name' => 'universal_tagline', 'type' => 'text', 'maxlength' => 120 ),
				array( 'key' => 'field_lvc_u_intro', 'label' => 'Property Intro', 'name' => 'universal_property_intro', 'type' => 'textarea', 'rows' => 5 ),
				array( 'key' => 'field_lvc_u_setting', 'label' => 'Setting & Positioning', 'name' => 'universal_setting', 'type' => 'textarea', 'rows' => 5 ),
				array( 'key' => 'field_lvc_u_editorial', 'label' => 'Editorial Line', 'name' => 'universal_editorial', 'type' => 'text' ),
				array( 'key' => 'field_lvc_u_included', 'label' => 'Included (one per line)', 'name' => 'universal_included', 'type' => 'textarea', 'rows' => 6 ),
				array( 'key' => 'field_lvc_u_on_request', 'label' => 'On Request (one per line)', 'name' => 'universal_on_request', 'type' => 'textarea', 'rows' => 6 ),
				array( 'key' => 'field_lvc_u_faq_tab', 'label' => 'Fallback FAQ', 'type' => 'tab' ),
				array( 'key' => 'field_lvc_u_q1', 'label' => 'Q1', 'name' => 'universal_faq_q1', 'type' => 'text' ),
				array( 'key' => 'field_lvc_u_a1', 'label' => 'A1', 'name' => 'universal_faq_a1', 'type' => 'textarea', 'rows' => 3 ),
				array( 'key' => 'field_lvc_u_q2', 'label' => 'Q2', 'name' => 'universal_faq_q2', 'type' => 'text' ),
				array( 'key' => 'field_lvc_u_a2', 'label' => 'A2', 'name' => 'universal_faq_a2', 'type' => 'textarea', 'rows' => 3 ),
				array( 'key' => 'field_lvc_u_q3', 'label' => 'Q3', 'name' => 'universal_faq_q3', 'type' => 'text' ),
				array( 'key' => 'field_lvc_u_a3', 'label' => 'A3', 'name' => 'universal_faq_a3', 'type' => 'textarea', 'rows' => 3 ),
				array( 'key' => 'field_lvc_u_q4', 'label' => 'Q4', 'name' => 'universal_faq_q4', 'type' => 'text' ),
				array( 'key' => 'field_lvc_u_a4', 'label' => 'A4', 'name' => 'universal_faq_a4', 'type' => 'textarea', 'rows' => 3 ),
				array( 'key' => 'field_lvc_u_q5', 'label' => 'Q5', 'name' => 'universal_faq_q5', 'type' => 'text' ),
				array( 'key' => 'field_lvc_u_a5', 'label' => 'A5', 'name' => 'universal_faq_a5', 'type' => 'textarea', 'rows' => 3 ),
			),
			'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'lvc-universal-content' ) ) ),
			'active'   => true,
		) );
	}
}
