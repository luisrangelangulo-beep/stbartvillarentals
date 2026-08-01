<?php
/**
 * Luxury Villa Theme Core â€” shared template helpers.
 * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 * Small, brand-agnostic helpers used across templates. All read from
 * theme-config.php so nothing brand-specific is hardcoded in templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** URL of the property archive (e.g. /luxury-villas/). */
if ( ! function_exists( 'lvc_archive_url' ) ) {
	function lvc_archive_url() {
		$url = get_post_type_archive_link( lvc_config( 'cpt', 'villa' ) );
		return $url ?: home_url( '/' . trim( (string) lvc_config( 'cpt_archive_slug', 'luxury-villas' ), '/' ) . '/' );
	}
}

/**
 * Validated trip criteria from the public villa finder.
 *
 * Dates are deliberately search context, not an availability claim: this
 * theme has no authoritative per-villa calendar feed. They travel with the
 * guest into the property inquiry so the concierge can confirm live dates.
 */
if ( ! function_exists( 'lvc_trip_context' ) ) {
	function lvc_trip_context() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only public search parameters.
		$arrival   = isset( $_GET['arrival'] ) ? sanitize_text_field( wp_unslash( $_GET['arrival'] ) ) : '';
		$departure = isset( $_GET['departure'] ) ? sanitize_text_field( wp_unslash( $_GET['departure'] ) ) : '';
		$guests    = isset( $_GET['guests'] ) ? min( 30, max( 0, absint( $_GET['guests'] ) ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$parse_date = static function ( $value ) {
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				return false;
			}
			$date   = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );
			$errors = DateTimeImmutable::getLastErrors();
			return $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ? $date : false;
		};

		$arrival_date   = $parse_date( $arrival );
		$departure_date = $parse_date( $departure );
		$today          = new DateTimeImmutable( 'today', wp_timezone() );
		$dates_valid    = $arrival_date && $departure_date && $arrival_date >= $today && $departure_date > $arrival_date;

		return array(
			'arrival'     => $dates_valid ? $arrival : '',
			'departure'   => $dates_valid ? $departure : '',
			'guests'      => $guests,
			'dates_valid' => (bool) $dates_valid,
		);
	}
}

/** Add validated dates and party size to the next step in the villa journey. */
if ( ! function_exists( 'lvc_trip_url' ) ) {
	function lvc_trip_url( $url ) {
		$trip = lvc_trip_context();
		$args = array();
		if ( $trip['dates_valid'] ) {
			$args['arrival']   = $trip['arrival'];
			$args['departure'] = $trip['departure'];
		}
		if ( $trip['guests'] ) {
			$args['guests'] = $trip['guests'];
		}
		return $args ? add_query_arg( $args, $url ) : $url;
	}
}

/** URL of a configured page by key (contact, request, about, how, owners, magazine). */
if ( ! function_exists( 'lvc_page_url' ) ) {
	function lvc_page_url( $key ) {
		$pages = (array) lvc_config( 'pages', array() );
		$slug  = isset( $pages[ $key ] ) ? $pages[ $key ] : $key;
		return home_url( '/' . trim( (string) $slug, '/' ) . '/' );
	}
}

/** Filterable WhatsApp URL (empty if not configured). */
if ( ! function_exists( 'lvc_whatsapp_url' ) ) {
	function lvc_whatsapp_url() {
		return apply_filters( 'lvc_whatsapp_url', (string) lvc_config( 'whatsapp_url', '' ) );
	}
}

/**
 * Best-available image URL for a property.
 *
 * Order: the curated ACF image for this context â†’ the other ACF image â†’
 * FIFU meta â†’ featured image. Pass $context = 'hero' on a single-property
 * template so it prefers `hero_image`; everywhere else (cards, related tiles,
 * schema `image`) defaults to `feature_image`.
 *
 * The curated ACF image always wins â€” nothing may override an editor's pick.
 *
 * There is deliberately NO gallery fallback. The first URL in a gallery field
 * is an arbitrary frame â€” often a bathroom or interior â€” so falling back to it
 * puts an unvetted photo on cards and schema. A property with no curated image
 * returns '' and callers render their imageless variant.
 */
if ( ! function_exists( 'lvc_property_image' ) ) {
	function lvc_property_image( $post_id, $size = 'large', $context = 'card' ) {
		$primary   = 'hero' === $context ? 'hero_image' : 'feature_image';
		$secondary = 'hero' === $context ? 'feature_image' : 'hero_image';

		$img = lvc_field( $primary, $post_id );
		if ( ! $img ) {
			$img = lvc_field( $secondary, $post_id );
		}
		// No FIFU step: Featured Image From URL is not installed on any site in
		// the portfolio, and leaving the lookup here made a removed plugin's
		// leftover meta outrank the WordPress featured image.
		if ( ! $img ) {
			$img = get_the_post_thumbnail_url( $post_id, $size );
		}

		return $img ? esc_url( $img ) : '';
	}
}

/**
 * Parse a gallery meta field into a clean list of image URLs.
 *
 * Split on commas AND newlines: galleries arrive comma-separated about as often
 * as line-separated, and a newline-only split silently resolves a 30-image
 * gallery to one URL (see docs/LESSONS_LEARNED.md Â§3). Values are trimmed â€”
 * comma-separated exports usually carry a leading space â€” and anything that is
 * not an http(s) URL is dropped rather than rendered as a broken <img>.
 *
 * @param string $field   Meta/ACF field name, e.g. 'gallery_squares'.
 * @param int    $post_id Property ID.
 * @return string[]
 */
if ( ! function_exists( 'lvc_gallery_urls' ) ) {
	function lvc_gallery_urls( $field, $post_id ) {
		$raw = (string) lvc_field( $field, $post_id );
		if ( '' === $raw ) {
			return array();
		}
		$parts = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $raw ) ) );

		return array_values( array_filter( $parts, static function ( $u ) {
			return (bool) preg_match( '#^https?://#i', $u );
		} ) );
	}
}

/**
 * ACF field with a graceful fallback chain when the plugin or value is absent.
 * Safe to call even if ACF is not active.
 */
if ( ! function_exists( 'lvc_field' ) ) {
	function lvc_field( $name, $post_id = null, $default = '' ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}
		$value = get_field( $name, $post_id );
		return ( null === $value || '' === $value || array() === $value ) ? $default : $value;
	}
}

/** The active brand name (for headings, schema, email subjects). */
if ( ! function_exists( 'lvc_brand' ) ) {
	function lvc_brand() {
		return (string) lvc_config( 'brand_name', get_bloginfo( 'name' ) );
	}
}

