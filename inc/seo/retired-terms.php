<?php
/**
 * 301s for taxonomy terms that have been merged or retired.
 *
 * When a term is merged into another the term row is deleted, and WordPress
 * then serves its URL as a plain 404 — losing whatever equity and inbound links
 * the retired URL had. That is what happened to /area/st-jean-beach/: it was
 * merged into St Jean, was in the XML sitemap, and had its own meta description.
 *
 * A map lives here rather than in .htaccess because .htaccess is not in this
 * repo — a redirect added there is invisible to code review and vanishes on a
 * server rebuild. Keeping it in the theme means it deploys, versions and
 * reviews with everything else.
 *
 * Runs on `template_redirect` and only when the request is genuinely a 404, so
 * it can never shadow a live term that is later recreated under the same slug.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lvc_retired_term_map' ) ) {
	/**
	 * Retired path => destination path. Both relative to the site root, with
	 * leading and trailing slashes.
	 *
	 * @return array<string,string>
	 */
	function lvc_retired_term_map() {
		return apply_filters(
			'lvc_retired_terms',
			array(
				// Merged 2026-08-02: "St Jean Beach" (1 villa) folded into the
				// "St Jean" area term, which now holds 7.
				'/area/st-jean-beach/' => '/area/st-jean/',
			)
		);
	}
}

add_action( 'template_redirect', 'lvc_redirect_retired_terms', 1 );

if ( ! function_exists( 'lvc_redirect_retired_terms' ) ) {
	function lvc_redirect_retired_terms() {
		if ( ! is_404() || is_admin() ) {
			return;
		}

		$request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path only, compared against a fixed map.
		if ( '' === $request ) {
			return;
		}

		// Normalise to a single trailing slash so /x and /x/ both match.
		$request = '/' . trim( $request, '/' ) . '/';

		$map = lvc_retired_term_map();
		if ( ! isset( $map[ $request ] ) ) {
			return;
		}

		wp_safe_redirect( home_url( $map[ $request ] ), 301, 'St Barts retired term' );
		exit;
	}
}
