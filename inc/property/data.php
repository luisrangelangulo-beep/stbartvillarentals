<?php
/**
 * Luxury Villa Theme Core — property data helpers.
 * Related-property resolution with an area → destination fallback ladder,
 * and a rate-tier → Schema.org priceRange map.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pick N IDs from a candidate pool, rotating the window by a seed.
 *
 * Why not orderby=rand: random returns a different set on every request, so
 * Googlebot sees inconsistent internal links between crawls and the set is not
 * cache-stable. Why not orderby=date: every property in an area then links to
 * the same newest few, so the newest imports collect all the internal links and
 * older ones receive none — measured across the portfolio, that left 38 of 67
 * properties (Republic), 71 of 105 (Tulum) and 108 of 150 (Residencias Reef)
 * with no related-property link at all, while single properties collected 15,
 * 20 and 108 respectively.
 *
 * Rotating by post ID is deterministic — the same property always shows the
 * same set, so pages stay cache-stable and crawls are consistent — while each
 * property starts at a different offset, spreading link equity across the
 * whole collection.
 *
 * @param int[] $pool  Candidate post IDs.
 * @param int   $seed  Usually the current post ID.
 * @param int   $limit How many to return.
 * @return int[]
 */
if ( ! function_exists( 'lvc_rotate_pick' ) ) {
	function lvc_rotate_pick( array $pool, $seed, $limit ) {
		$pool  = array_values( array_unique( array_map( 'intval', $pool ) ) );
		$total = count( $pool );
		$limit = max( 1, (int) $limit );
		if ( $total <= $limit ) {
			return $pool;
		}
		sort( $pool, SORT_NUMERIC );
		$offset = abs( (int) $seed ) % $total;
		$picked = array();
		for ( $i = 0; $i < $limit; $i++ ) {
			$picked[] = $pool[ ( $offset + $i ) % $total ];
		}
		return $picked;
	}
}

/**
 * Similar properties for a given property: same area first, then same
 * destination, excluding the current one. Returns an array of post IDs.
 */
if ( ! function_exists( 'lvc_related_properties' ) ) {
	function lvc_related_properties( $post_id, $limit = 3 ) {
		$cpt = lvc_config( 'cpt', 'villa' );

		foreach ( array( 'area', 'destination' ) as $tax ) {
			$terms = get_the_terms( $post_id, $tax );
			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}
			$ids = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			$q   = new WP_Query( array(
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post__not_in'   => array( (int) $post_id ),
				'orderby'        => 'ID',
				'fields'         => 'ids',
				'tax_query'      => array( array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $ids ) ),
			) );
			if ( $q->have_posts() ) {
				return lvc_rotate_pick( $q->posts, $post_id, $limit );
			}
		}
		return array();
	}
}

/**
 * Properties related to a magazine post by shared area/destination terms.
 */
if ( ! function_exists( 'lvc_related_properties_for_post' ) ) {
	function lvc_related_properties_for_post( $post_id, $limit = 2 ) {
		$cpt = lvc_config( 'cpt', 'villa' );

		foreach ( array( 'destination', 'area' ) as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$ids = wp_get_post_terms( $post_id, $tax, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $ids ) || ! $ids ) {
				continue;
			}
			$q = new WP_Query( array(
				'post_type'      => $cpt,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'fields'         => 'ids',
				'tax_query'      => array( array( 'taxonomy' => $tax, 'field' => 'term_id', 'terms' => $ids ) ),
			) );
			if ( $q->have_posts() ) {
				return lvc_rotate_pick( $q->posts, $post_id, $limit );
			}
		}
		return array();
	}
}

/**
 * Map a rate-tier slug to a Schema.org priceRange shorthand.
 */
if ( ! function_exists( 'lvc_price_range' ) ) {
	function lvc_price_range( $tier ) {
		$map = array(
			'under-5k' => '$',
			'5k-10k'   => '$$',
			'10k-20k'  => '$$$',
			'20k-plus' => '$$$$',
		);
		return $map[ (string) $tier ] ?? '$$';
	}
}


/**
 * Return the public pricing summary for a villa.
 *
 * Mirrors Punta Mita's fallback order so existing imports using the legacy
 * base_rate_from key continue to work. Empty pricing is honest: it renders
 * "Rates on request" instead of inventing a nightly amount.
 *
 * @param int|null $post_id Villa post ID.
 * @return array{amount:int,label:string,minimum_stay:int,note:string}
 */
if ( ! function_exists( 'lvc_property_rate' ) ) {
	function lvc_property_rate( $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		$raw     = function_exists( 'get_field' ) ? get_field( 'nightly_rate_from', $post_id ) : '';

		if ( '' === (string) $raw || null === $raw ) {
			$raw = get_post_meta( $post_id, 'base_rate_from', true );
		}
		if ( '' === (string) $raw || null === $raw ) {
			$raw = get_post_meta( $post_id, 'nightly_rate_from', true );
		}

		$amount       = (int) round( (float) preg_replace( '/[^0-9.]/', '', (string) $raw ) );
		$minimum_raw  = function_exists( 'get_field' ) ? get_field( 'minimum_stay', $post_id ) : '';
		$minimum_stay = $minimum_raw ? max( 1, (int) $minimum_raw ) : 0;
		$label        = $amount > 0
			? 'From approx. $' . number_format_i18n( $amount ) . '/night'
			: 'Rates on request';

		if ( $amount <= 0 ) {
			$note = 'Contact us for current rates and availability.';
		} elseif ( $minimum_stay ) {
			$note = 'Minimum stay ' . $minimum_stay . ' nights. Rates vary by season and availability.';
		} else {
			$note = 'Rates vary by season and availability. Current pricing is confirmed before booking.';
		}

		return array(
			'amount'       => $amount,
			'label'        => $label,
			'minimum_stay' => $minimum_stay,
			'note'         => $note,
		);
	}
}

/* ── Off-market properties ───────────────────────────────────────────────
 *
 * A property that is no longer bookable keeps its URL working — existing
 * links, bookmarks and inbound SEO all still resolve — but drops out of
 * listings and out of the search index. Ported from Punta Mita.
 *
 * Registering the `off_market` field without these two hooks would make it a
 * toggle that looks like it works and does nothing, which is the exact defect
 * documented in docs/LESSONS_LEARNED.md §11.
 */

/** Hide off-market properties from archives, taxonomies and the related rail. */
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$cpt = lvc_config( 'cpt', 'villa' );
	$is_property_list = $query->is_post_type_archive( $cpt )
		|| $query->is_tax( array_keys( (array) lvc_config( 'taxonomies', array() ) ) );

	if ( ! $is_property_list ) {
		return;
	}

	$meta = (array) $query->get( 'meta_query' );
	// NOT EXISTS is required alongside != '1': properties saved before the
	// field existed have no row at all, and a bare comparison drops them.
	$meta[] = array(
		'relation' => 'OR',
		array( 'key' => 'off_market', 'compare' => 'NOT EXISTS' ),
		array( 'key' => 'off_market', 'value' => '1', 'compare' => '!=' ),
	);
	$query->set( 'meta_query', $meta );
} );

/** Noindex an off-market property while leaving its URL live. */
if ( ! function_exists( 'lvc_is_off_market' ) ) {
	function lvc_is_off_market( $post_id = null ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		return '1' === (string) get_post_meta( $post_id, 'off_market', true );
	}
}

add_filter( 'wp_robots', function ( $robots ) {
	if ( is_singular( lvc_config( 'cpt', 'villa' ) ) && lvc_is_off_market() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'], $robots['nofollow'] );
	}
	return $robots;
}, 99 );

// Rank Math emits its own robots tag and ignores wp_robots — both filters are
// required. See docs/LESSONS_LEARNED.md §10.
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( is_singular( lvc_config( 'cpt', 'villa' ) ) && lvc_is_off_market() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}, 99 );

