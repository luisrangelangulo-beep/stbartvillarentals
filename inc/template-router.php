<?php
/**
 * Luxury Villa Theme Core — template router.
 * ─────────────────────────────────────────────────────────────────────────
 * Maps the configured property CPT + its taxonomies to the GENERIC template
 * parts, so the same files work no matter the CPT slug (villa/chalet/condo).
 * No need to rename single-{cpt}.php / archive-{cpt}.php per brand.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'single_template', 'lvc_route_single' );
function lvc_route_single( $template ) {
	if ( is_singular( lvc_config( 'cpt', 'villa' ) ) ) {
		$part = LVC_DIR . '/template-parts/property-single.php';
		if ( file_exists( $part ) ) {
			return $part;
		}
	}
	return $template;
}

add_filter( 'archive_template', 'lvc_route_archive' );
function lvc_route_archive( $template ) {
	if ( is_post_type_archive( lvc_config( 'cpt', 'villa' ) ) ) {
		$part = LVC_DIR . '/template-parts/property-archive.php';
		if ( file_exists( $part ) ) {
			return $part;
		}
	}
	return $template;
}

add_filter( 'taxonomy_template', 'lvc_route_taxonomy' );
function lvc_route_taxonomy( $template ) {
	$obj = get_queried_object();
	if ( $obj instanceof WP_Term && array_key_exists( $obj->taxonomy, (array) lvc_config( 'taxonomies', array() ) ) ) {
		$part = LVC_DIR . '/template-parts/term-archive.php';
		if ( file_exists( $part ) ) {
			return $part;
		}
	}
	return $template;
}

/**
 * Apply sanitized GET filters on the property archive (filter bar support).
 * Only touches the main query on the front-end CPT archive.
 */
add_action( 'pre_get_posts', 'lvc_archive_filters' );
function lvc_archive_filters( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( ! $q->is_post_type_archive( lvc_config( 'cpt', 'villa' ) ) ) {
		return;
	}

	$tax_query = array();
	foreach ( array_keys( (array) lvc_config( 'taxonomies', array() ) ) as $tax ) {
		if ( ! empty( $_GET[ $tax ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tax_query[] = array(
				'taxonomy' => $tax,
				'field'    => 'slug',
				'terms'    => sanitize_title( wp_unslash( $_GET[ $tax ] ) ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}
	}
	if ( count( $tax_query ) > 1 ) {
		$tax_query['relation'] = 'AND';
	}
	if ( $tax_query ) {
		$q->set( 'tax_query', $tax_query );
	}
}

/**
 * Twelve villas per archive page.
 *
 * WordPress' default of 10 leaves the four-across grid with a two-card last row
 * and a visible gap. 12 divides evenly by 4, 3 and 2, so every breakpoint fills
 * its rows.
 */
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$lvc_cpt = lvc_config( 'cpt', 'villa' );
	if ( $query->is_post_type_archive( $lvc_cpt ) || $query->is_tax( array( 'area', 'bedrooms', 'beach_access', 'collection' ) ) ) {
		$query->set( 'posts_per_page', 12 );
	}
}, 20 );
// phpcs:ignore -- marker: lvc_archive_posts_per_page
