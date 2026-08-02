<?php
/**
 * Luxury Villa Theme Core — layered content resolution.
 * ─────────────────────────────────────────────────────────────────────────
 * Ported from Punta Mita, where it is the reason a thinly-written property
 * still renders a complete page instead of a half-empty one.
 *
 * Four tiers, first non-empty wins:
 *
 *   1. The property's own field          — the specific, best answer
 *   2. Its place term's field            — destination/area-level copy
 *   3. A site-wide "universal" option    — editable in Properties → Universal Content
 *   4. A hard default from lvc_universal_defaults()  — so nothing ever renders blank
 *
 * The point is editorial, not technical: a new property with three fields
 * filled in still reads as finished, and filling in the fourth field silently
 * upgrades it. Never bypass this by reading get_field() directly for any of the
 * fields listed in lvc_universal_defaults() — that reintroduces the blank
 * sections this exists to prevent.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve a piece of property copy through the four tiers.
 *
 * @param string      $property_field Field on the property, e.g. 'tagline'.
 * @param string|null $term_field     Field on the place term, e.g. 'intro'. Null to skip tier 2.
 * @param string|null $universal_key  Universal option key, e.g. 'universal_tagline'. Null to skip tier 3.
 * @param int|null    $post_id        Property ID; defaults to the current post.
 * @param WP_Term|null $term          Place term for tier 2; defaults to the property's primary term.
 * @return mixed Field value, or '' when every tier is empty.
 */
if ( ! function_exists( 'lvc_content' ) ) {
	function lvc_content( $property_field, $term_field = null, $universal_key = null, $post_id = null, $term = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

		// 1 — the property itself.
		if ( $property_field && $post_id ) {
			$value = lvc_field( $property_field, $post_id );
			if ( ! empty( $value ) ) {
				return $value;
			}
		}

		// 2 — the place it belongs to. Resolve the term lazily so callers that
		// never reach this tier do not pay for the lookup.
		if ( $term_field ) {
			if ( null === $term ) {
				$term = lvc_primary_place_term( $post_id );
			}
			if ( $term instanceof WP_Term ) {
				$value = lvc_field( $term_field, 'term_' . $term->term_id );
				if ( ! empty( $value ) ) {
					return $value;
				}
			}
		}

		// 3 — the site-wide universal, editable in wp-admin.
		if ( $universal_key ) {
			if ( function_exists( 'get_field' ) ) {
				$value = get_field( $universal_key, 'option' );
				if ( ! empty( $value ) ) {
					return $value;
				}
			}

			// 4 — the shipped default, so a fresh site is never blank.
			$defaults = lvc_universal_defaults();
			if ( isset( $defaults[ $universal_key ] ) && '' !== $defaults[ $universal_key ] ) {
				return $defaults[ $universal_key ];
			}
		}

		return '';
	}
}

/**
 * The property's primary place term — area first, then destination.
 *
 * Area is checked first because on a single-destination site (Anguilla, St
 * Barts) the area IS the geography and the destination taxonomy may not exist.
 */
if ( ! function_exists( 'lvc_primary_place_term' ) ) {
	function lvc_primary_place_term( $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();

		foreach ( array( 'area', 'destination' ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return $terms[0];
			}
		}

		return null;
	}
}

/**
 * Shipped defaults for the universal tier.
 *
 * Deliberately generic and brand-neutral: they must read acceptably on any
 * destination, because tier 4 is what a brand-new site renders before anyone
 * has written a word. Override per brand with the `lvc_universal_defaults`
 * filter rather than editing this array.
 */
if ( ! function_exists( 'lvc_universal_defaults' ) ) {
	function lvc_universal_defaults() {
		$plural   = strtolower( (string) lvc_config( 'cpt_plural', 'villas' ) );
		$singular = strtolower( (string) lvc_config( 'cpt_singular', 'villa' ) );
		$region   = (string) lvc_config( 'region', '' );
		$where    = $region ? ' in ' . $region : '';

		return apply_filters( 'lvc_universal_defaults', array(
			'universal_tagline'        => 'A private ' . $singular . ' stay' . $where . ' with tailored planning support.',
			'universal_property_intro' => 'This ' . $singular . $where . ' offers a private base for families and groups who want space, comfort and a more personal stay. Inclusions, staff, and access details vary by property and are confirmed before booking.',
			'universal_setting'        => 'Settings vary across our ' . $plural . ' — beachfront, elevated, and walkable to the water. The property page states which applies, and our team will confirm the detail that matters to your group.',
			'universal_editorial'      => 'Booked direct, with a specialist who knows the property.',
			'universal_included'       => '',
			'universal_on_request'     => '',
		) );
	}
}

/**
 * FAQ items for a property: its own repeater, else the universal set.
 *
 * Returns normalised [ question, answer ] rows with incomplete pairs dropped,
 * so callers can trust every row and apply the 2+ rule before emitting FAQPage
 * schema.
 *
 * @return array<int, array{question:string, answer:string}>
 */
if ( ! function_exists( 'lvc_property_faq' ) ) {
	function lvc_property_faq( $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$rows    = array();

		$own = lvc_field( 'faq_items', $post_id, array() );
		if ( is_array( $own ) ) {
			foreach ( $own as $row ) {
				$q = isset( $row['question'] ) ? trim( (string) $row['question'] ) : '';
				$a = isset( $row['answer'] ) ? trim( (string) $row['answer'] ) : '';
				if ( '' !== $q && '' !== $a ) {
					$rows[] = array( 'question' => $q, 'answer' => $a );
				}
			}
		}
		if ( $rows ) {
			return $rows;
		}

		// Flat pairs — what the sheet sync and the villa importer actually write.
		// fields.php defines faq_q1..faq_a4 on the villa and calls the repeater
		// above "preferred", but without this branch an imported villa fell
		// straight through to the universal set: on St Barts all 75 villas held
		// four complete pairs and not one ever rendered, in the FAQ section or in
		// FAQPage schema.
		for ( $i = 1; $i <= 4; $i++ ) {
			$q = trim( (string) lvc_field( 'faq_q' . $i, $post_id ) );
			$a = trim( (string) lvc_field( 'faq_a' . $i, $post_id ) );
			if ( '' !== $q && '' !== $a ) {
				$rows[] = array( 'question' => $q, 'answer' => $a );
			}
		}
		if ( $rows ) {
			return $rows;
		}

		// Universal fallback — flat q/a pairs, editable on the options page.
		if ( function_exists( 'get_field' ) ) {
			for ( $i = 1; $i <= 5; $i++ ) {
				$q = trim( (string) get_field( 'universal_faq_q' . $i, 'option' ) );
				$a = trim( (string) get_field( 'universal_faq_a' . $i, 'option' ) );
				if ( '' !== $q && '' !== $a ) {
					$rows[] = array( 'question' => $q, 'answer' => $a );
				}
			}
		}

		return $rows;
	}
}

/**
 * Split a textarea list field (one item per line) into trimmed values.
 *
 * Newline-only on purpose: these are prose lists where a comma is legitimate
 * content ("chef, on request"). Do NOT reuse lvc_gallery_urls() here — see
 * docs/LESSONS_LEARNED.md §3.
 */
if ( ! function_exists( 'lvc_list_lines' ) ) {
	function lvc_list_lines( $raw ) {
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $raw ) ) ) );
	}
}
