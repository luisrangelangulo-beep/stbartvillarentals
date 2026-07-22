<?php
/**
 * Luxury Villa Theme Core — JSON-LD schema + SEO hygiene.
 * ─────────────────────────────────────────────────────────────────────────
 * Emits typed schema (VacationRental, CollectionPage/ItemList, Article,
 * BreadcrumbList, FAQPage), suppresses Rank Math's own schema so they don't
 * collide (when theme_owns_schema), and noindexes thin/paged term archives.
 *
 * Templates call lvc_schema_property()/lvc_schema_collection()/lvc_schema_article().
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Echo a JSON-LD <script> block. */
if ( ! function_exists( 'lvc_jsonld' ) ) {
	function lvc_jsonld( array $data ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}
}

/** BreadcrumbList from a list of [name => url] pairs (last item = current, url optional). */
if ( ! function_exists( 'lvc_schema_breadcrumb' ) ) {
	function lvc_schema_breadcrumb( array $items ) {
		$list = array();
		$i    = 0;
		foreach ( $items as $name => $url ) {
			$i++;
			$entry = array( '@type' => 'ListItem', 'position' => $i, 'name' => $name );
			if ( $url ) {
				$entry['item'] = $url;
			}
			$list[] = $entry;
		}
		return array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list );
	}
}

/** Single property: VacationRental + Accommodation + breadcrumb. */
if ( ! function_exists( 'lvc_schema_property' ) ) {
	function lvc_schema_property( $post_id ) {
		$beds    = lvc_field( 'bed_count', $post_id );
		$guests  = lvc_field( 'guests_max', $post_id );
		$tier    = lvc_field( 'from_rate_tier', $post_id );
		$descr   = lvc_field( 'property_descr', $post_id );
		$aliases = lvc_field( 'villa_aliases', $post_id );
		$area    = get_the_terms( $post_id, 'area' );
		$dest    = get_the_terms( $post_id, 'destination' );
		$area_n  = ( $area && ! is_wp_error( $area ) ) ? $area[0]->name : '';
		$dest_n  = ( $dest && ! is_wp_error( $dest ) ) ? $dest[0]->name : '';

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => array( 'VacationRental', 'Accommodation' ),
			'name'     => get_the_title( $post_id ),
			'url'      => get_permalink( $post_id ),
		);
		if ( $descr ) {
			$schema['description'] = wp_trim_words( wp_strip_all_tags( $descr ), 50, '' );
		}
		if ( $aliases ) {
			$alt = array_values( array_filter( array_map( 'trim', explode( ',', $aliases ) ) ) );
			if ( $alt ) {
				$schema['alternateName'] = ( count( $alt ) === 1 ) ? $alt[0] : $alt;
			}
		}
		$img = lvc_property_image( $post_id, 'full' );
		if ( $img ) {
			$schema['image'] = $img;
		}
		if ( $beds ) {
			$schema['numberOfRooms'] = (int) $beds;
		}
		if ( $guests ) {
			$schema['occupancy'] = array( '@type' => 'QuantitativeValue', 'maxValue' => (int) $guests );
		}
		if ( $tier && function_exists( 'lvc_price_range' ) ) {
			$schema['priceRange'] = lvc_price_range( $tier );
		}
		// amenityFeature as typed objects (NOT bare strings).
		$amen = get_the_terms( $post_id, 'amenity' );
		if ( $amen && ! is_wp_error( $amen ) ) {
			$feat = array();
			foreach ( $amen as $a ) {
				$feat[] = array( '@type' => 'LocationFeatureSpecification', 'name' => $a->name, 'value' => true );
			}
			$schema['amenityFeature'] = $feat;
		}
		if ( $area_n || $dest_n ) {
			$addr = array( '@type' => 'PostalAddress' );
			if ( $area_n ) {
				$addr['addressLocality'] = $area_n;
			}
			if ( $dest_n ) {
				$addr['addressRegion'] = $dest_n;
			}
			$schema['address'] = $addr;
		}

		// Graft real reviews + aggregate rating onto the property node.
		if ( function_exists( 'lvc_schema_reviews' ) ) {
			$reviews = lvc_schema_reviews( $post_id );
			if ( $reviews ) {
				$schema = array_merge( $schema, $reviews );
			}
		}

		lvc_jsonld( $schema );

		$crumbs = array( lvc_brand() => home_url( '/' ), lvc_config( 'cpt_plural', 'Villas' ) => lvc_archive_url(), get_the_title( $post_id ) => '' );
		lvc_jsonld( lvc_schema_breadcrumb( $crumbs ) );

		// FAQ schema — same source the template renders from (repeater, else the
		// flat pairs, else universal), so what shows and what is marked up never
		// diverge. Only genuine on-page answers become schema.
		$faq = function_exists( 'lvc_property_faq' ) ? lvc_property_faq( $post_id ) : array();
		$qas = array();
		foreach ( $faq as $row ) {
			$qas[] = array(
				'@type'          => 'Question',
				'name'           => $row['question'],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $row['answer'] ) ),
			);
		}
		if ( count( $qas ) >= 2 ) {
			lvc_jsonld( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qas ) );
		}
	}
}

/**
 * Review + aggregateRating for a property's real testimonials.
 *
 * Emitted only for reviews that carry a rating — Google requires reviewRating
 * on each Review, and an invented aggregate is a policy risk. Skips entirely
 * when there are no rated reviews.
 */
if ( ! function_exists( 'lvc_schema_reviews' ) ) {
	function lvc_schema_reviews( $post_id ) {
		$rows = lvc_field( 'testimonials', $post_id, array() );
		if ( ! is_array( $rows ) || ! $rows ) {
			return array();
		}

		$reviews = array();
		$sum     = 0;
		foreach ( $rows as $t ) {
			$quote  = isset( $t['quote'] ) ? trim( (string) $t['quote'] ) : '';
			$rating = isset( $t['rating'] ) ? (float) $t['rating'] : 0;
			if ( '' === $quote || $rating <= 0 ) {
				continue;
			}
			$name      = isset( $t['guest_name'] ) ? trim( (string) $t['guest_name'] ) : '';
			$reviews[] = array(
				'@type'         => 'Review',
				'reviewBody'    => $quote,
				'reviewRating'  => array( '@type' => 'Rating', 'ratingValue' => $rating, 'bestRating' => 5 ),
				'author'        => array( '@type' => 'Person', 'name' => $name !== '' ? $name : 'Verified guest' ),
			);
			$sum += $rating;
		}

		if ( ! $reviews ) {
			return array();
		}

		$count = count( $reviews );
		return array(
			'review'          => $reviews,
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => round( $sum / $count, 1 ),
				'reviewCount' => $count,
				'bestRating'  => 5,
			),
		);
	}
}

/** Taxonomy / archive: CollectionPage + ItemList of the current query. */
if ( ! function_exists( 'lvc_schema_collection' ) ) {
	function lvc_schema_collection() {
		global $wp_query;
		$items = array();
		$pos   = 0;
		if ( ! empty( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $p ) {
				$pos++;
				$items[] = array( '@type' => 'ListItem', 'position' => $pos, 'url' => get_permalink( $p ) );
			}
		}
		$obj  = get_queried_object();
		$name = $obj instanceof WP_Term ? $obj->name : lvc_config( 'cpt_plural', 'Villas' );
		lvc_jsonld( array(
			'@context'        => 'https://schema.org',
			'@type'           => 'CollectionPage',
			'name'            => $name,
			'mainEntity'      => array( '@type' => 'ItemList', 'numberOfItems' => count( $items ), 'itemListElement' => $items ),
		) );
	}
}

/** Magazine article: Article + breadcrumb. */
if ( ! function_exists( 'lvc_schema_article' ) ) {
	function lvc_schema_article( $post_id ) {
		$schema = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => get_the_title( $post_id ),
			'datePublished' => get_the_date( 'c', $post_id ),
			'dateModified'  => get_the_modified_date( 'c', $post_id ),
			'author'        => array( '@type' => 'Organization', 'name' => lvc_brand() ),
			'publisher'     => array( '@type' => 'Organization', 'name' => lvc_brand() ),
			'mainEntityOfPage' => get_permalink( $post_id ),
		);
		$img = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( $img ) {
			$schema['image'] = $img;
		}
		lvc_jsonld( $schema );
	}
}

/* ── Rank Math de-duplication: let the theme own schema. ─────────────────── */
if ( lvc_config( 'theme_owns_schema', true ) ) {
	add_filter( 'rank_math/json_ld', function ( $data ) {
		if ( is_singular( lvc_config( 'cpt', 'villa' ) ) || is_tax( array_keys( (array) lvc_config( 'taxonomies', array() ) ) ) || is_post_type_archive( lvc_config( 'cpt', 'villa' ) ) ) {
			return array();
		}
		return $data;
	}, 99 );
}

/* ── Robots hygiene for term archives. ──────────────────────────────────────
 *
 * Resolves to noindex when ANY of these is true:
 *   1. The request is paginated or carries a query string. Faceted grids
 *      paginate with ?page/?filter params; every one of those is a near-copy of
 *      page 1 and must never be indexed.
 *   2. The term holds fewer than `min_index_count` properties. A near-empty
 *      archive is doorway content, and this floor is what makes indexing by
 *      default safe — a freshly created or emptied term cannot leak in.
 *   3. The term's `search_visible` switch is explicitly off. Unset counts as
 *      visible, so terms created before the field existed are not penalised.
 *
 * IMPORTANT — this hooks BOTH `wp_robots` and `rank_math/frontend/robots`.
 * Rank Math emits its own robots tag and does not read `wp_robots`, so a
 * wp_robots-only guard silently does nothing on a Rank Math site (which is all
 * of them). That exact gap hid a 127-property archive on Punta Mita for months.
 * If you add another robots rule, add it in lvc_term_should_noindex() so both
 * paths keep agreeing.
 */
if ( ! function_exists( 'lvc_term_should_noindex' ) ) {
	function lvc_term_should_noindex() {
		if ( is_paged() || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only robots decision
			return true;
		}
		if ( ! is_tax( array_keys( (array) lvc_config( 'taxonomies', array() ) ) ) ) {
			return false;
		}
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return false;
		}
		if ( (int) $term->count < (int) lvc_config( 'min_index_count', 3 ) ) {
			return true;
		}
		$visible = get_term_meta( $term->term_id, 'search_visible', true );
		return ( '0' === (string) $visible );
	}
}

if ( lvc_config( 'noindex_thin_terms', true ) ) {
	add_filter( 'wp_robots', function ( $robots ) {
		if ( lvc_term_should_noindex() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['nofollow'] );
		}
		return $robots;
	}, 99 );

	add_filter( 'rank_math/frontend/robots', function ( $robots ) {
		if ( lvc_term_should_noindex() ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}
		return $robots;
	}, 99 );
}
