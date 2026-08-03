<?php
/**
 * Magazine authoring fields and shared article presentation helpers.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attach the place taxonomies to `post` so a magazine article can be tagged
 * with an area (and, on a multi-destination portfolio site, a destination).
 *
 * Without this, `lvc_related_properties_for_post()` in inc/property/data.php
 * (reads the post's `destination`/`area` terms to find matching villas) has
 * no term to read, because those taxonomies are registered only against the
 * property CPT (inc/cpt/register-property.php) — there is no meta box on the
 * post editor to set one. That leaves every magazine article with zero
 * automatic related-villa links: the single most important internal-linking
 * element a magazine article has.
 *
 * Scoped to exactly the two taxonomies that function actually reads — not
 * every configured taxonomy — so this doesn't add unused meta boxes to the
 * post editor for `collection`/`bedrooms`/`beach_access`/`amenity`.
 *
 * Priority 20: taxonomies register on `init` at priority 5
 * (lvc_register_property_model), so this must run after that.
 */
add_action( 'init', static function () {
	foreach ( array( 'destination', 'area' ) as $lvc_tax ) {
		if ( taxonomy_exists( $lvc_tax ) ) {
			register_taxonomy_for_object_type( $lvc_tax, 'post' );
		}
	}
}, 20 );

/**
 * Keep `area`/`destination` term counts villa-only.
 *
 * WordPress' default count callback, _update_post_term_count() (see
 * wp-includes/taxonomy.php), sums EVERY post type currently registered
 * against a taxonomy into ONE $wp_term_taxonomy.count value per term — the
 * count is per taxonomy, not per object type. The moment the hook above
 * attaches `area`/`destination` to `post`, the next recount for any term
 * (triggered by saving ANY tagged villa or magazine article) would start
 * blending article counts into a number that 14 call sites across this
 * theme already read as "how many villas are in this area": the
 * min_index_count noindex threshold (inc/seo/schema.php,
 * template-parts/property-archive.php), the Areas hub card counts
 * (page-areas.php), the footer Areas column (footer.php), the homepage
 * bands and orientation-block links (front-page.php), the sibling-area
 * links (template-parts/term-archive.php) and the mega-menu counts
 * (inc/nav/mega-menu.php). Confirmed by reading _update_post_term_count()
 * directly rather than assuming.
 *
 * Rather than patch every one of those call sites — and hope nothing
 * outside this theme (Rank Math's sitemap, an Elementor widget) also
 * reads $term->count expecting villas-only — this overrides the two
 * taxonomies' update_count_callback so WordPress itself keeps recounting
 * villas only, no matter what other post types get attached to them
 * later. $term->count keeps meaning exactly what it meant before this
 * file existed; every existing caller needs zero changes.
 *
 * Priority 21: must run after both the object-type registration above
 * (20) and core's own taxonomy registration (5) — the taxonomy object
 * has to exist in $wp_taxonomies before its callback can be overridden.
 */
add_action( 'init', static function () {
	global $wp_taxonomies;
	foreach ( array( 'destination', 'area' ) as $lvc_tax ) {
		if ( isset( $wp_taxonomies[ $lvc_tax ] ) ) {
			$wp_taxonomies[ $lvc_tax ]->update_count_callback = 'lvc_update_term_count_villas_only';
		}
	}
}, 21 );

if ( ! function_exists( 'lvc_update_term_count_villas_only' ) ) {
	/**
	 * Drop-in replacement for WordPress' _update_post_term_count(),
	 * hardcoded to the property CPT so magazine articles never move an
	 * area/destination term's count. See the registration hook above for
	 * why this exists. Mirrors core's own query shape (including the
	 * `update_post_term_count_statuses` filter) so behavior stays
	 * identical to core apart from which post types are counted.
	 */
	function lvc_update_term_count_villas_only( $terms, $taxonomy ) {
		global $wpdb;

		$lvc_cpt = (string) lvc_config( 'cpt', 'villa' );

		/** This filter is documented in wp-includes/taxonomy.php */
		$post_statuses = apply_filters( 'update_post_term_count_statuses', array( 'publish' ), $taxonomy );
		$post_statuses = esc_sql( $post_statuses );

		foreach ( (array) $terms as $lvc_tt_id ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->term_relationships}, {$wpdb->posts}
					 WHERE {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id
					 AND post_status IN ('" . implode( "', '", $post_statuses ) . "')
					 AND post_type = %s
					 AND term_taxonomy_id = %d",
					$lvc_cpt,
					$lvc_tt_id
				)
			);

			/** This filter is documented in wp-includes/taxonomy.php */
			$count = apply_filters( 'edit_term_count', $count, $lvc_tt_id, $taxonomy );

			$wpdb->update( $wpdb->term_taxonomy, array( 'count' => $count ), array( 'term_taxonomy_id' => $lvc_tt_id ) );

			/** This filter is documented in wp-includes/taxonomy.php */
			do_action( 'edited_term_count', $lvc_tt_id, $taxonomy );
		}

		clean_term_cache( array_map( 'intval', (array) $terms ), '', false );
	}
}

/**
 * Keep the front-end `/area/...` (and `/destination/...`) archives
 * villa-only.
 *
 * WP_Query::get_posts(), when a taxonomy archive has no explicit
 * post_type, does — quoting wp-includes/class-wp-query.php directly —
 * "a fully inclusive search for currently registered post types of
 * queried taxonomies": it collects every post type whose object
 * taxonomies intersect the queried one. Once `area`/`destination` are
 * attached to `post` above, that default would pull magazine articles
 * into the SAME grid template (template-parts/term-archive.php, routed
 * via inc/template-router.php) that renders villa cards — a template
 * that assumes every result has villa fields, plus the ItemList schema
 * in inc/seo/schema.php. Tagging an article with an area is for the
 * related-villas widget (lvc_related_properties_for_post() in
 * inc/property/data.php) only, not for making it appear on the area's
 * own archive page — so force the main query back to villa-only,
 * matching current behavior exactly.
 *
 * Priority 20 to run alongside the other query-shaping hooks in
 * inc/template-router.php.
 */
add_action( 'pre_get_posts', static function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_tax( array( 'area', 'destination' ) ) && empty( $q->get( 'post_type' ) ) ) {
		$q->set( 'post_type', lvc_config( 'cpt', 'villa' ) );
	}
}, 20 );

add_action( 'acf/init', static function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_lvc_magazine_editorial',
			'title'    => 'Magazine — Editorial Details',
			'fields'   => array(
				array(
					'key'          => 'field_lvc_blog_media_image_url',
					'label'        => 'Article image URL',
					'name'         => 'blog_media_image_url',
					'type'         => 'url',
					'instructions' => 'Optional unique landscape image. The featured image is used when this is empty.',
				),
				array(
					'key'          => 'field_lvc_read_time',
					'label'        => 'Read time',
					'name'         => 'read_time',
					'type'         => 'text',
					'placeholder'  => '6 min read',
					'instructions' => 'Optional. A reading time is calculated automatically when empty.',
				),
				array(
					'key'           => 'field_lvc_author_name',
					'label'         => 'Display author',
					'name'          => 'author_name',
					'type'          => 'text',
					'default_value' => lvc_config( 'brand_name', get_bloginfo( 'name' ) ) . ' Team',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'position' => 'side',
			'active'   => true,
		)
	);
} );

if ( ! function_exists( 'lvc_blog_image_url' ) ) {
	function lvc_blog_image_url( $post_id, $size = 'large' ) {
		$image_url = trim( (string) lvc_field( 'blog_media_image_url', $post_id ) );
		if ( '' !== $image_url ) {
			return lvc_priority_image_url( $image_url );
		}

		$image_url = get_the_post_thumbnail_url( $post_id, $size );
		return $image_url ? lvc_priority_image_url( (string) $image_url ) : '';
	}
}

if ( ! function_exists( 'lvc_article_read_time' ) ) {
	function lvc_article_read_time( $post_id ) {
		$manual = trim( (string) lvc_field( 'read_time', $post_id ) );
		if ( '' !== $manual ) {
			return $manual;
		}

		$content = (string) get_post_field( 'post_content', $post_id );
		$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		$minutes = max( 1, (int) ceil( $words / 220 ) );
		return sprintf( '%d min read', $minutes );
	}
}
