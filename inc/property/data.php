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
