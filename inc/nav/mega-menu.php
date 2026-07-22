<?php
/**
 * Luxury Villa Theme Core — taxonomy mega menu.
 * ─────────────────────────────────────────────────────────────────────────
 * Builds the header's browse panel from the `nav_mega` config: one column per
 * taxonomy, each listing that taxonomy's terms.
 *
 * Why this exists rather than a hand-built WP menu: taxonomy archives are the
 * pages a destination site actually ranks with, and a hand-built menu never
 * keeps up with them. On Punta Mita the /bedrooms/ archives were fully built,
 * sitemapped, and then orphaned — zero inbound links anywhere on the site — so
 * they could not rank no matter how good the pages were. Driving the panel off
 * the taxonomy means a new term is linked from every page the moment it exists.
 *
 * Structure only — no styling. See docs/TOKEN_CONTRACT.md for the class hooks.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Terms for one mega-menu column, after limit/range filtering.
 *
 * @param string $taxonomy Taxonomy name.
 * @param array  $spec     Column spec from the `nav_mega` config.
 * @return WP_Term[]
 */
if ( ! function_exists( 'lvc_mega_terms' ) ) {
	function lvc_mega_terms( $taxonomy, array $spec ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		// Numeric facets (bedrooms) come in as "3-bedrooms". Filter to the
		// configured band and sort numerically — the names sort as strings, so
		// "10 Bedrooms" would otherwise lead. Terms outside the band are usually
		// the thin ones we deliberately noindex; linking them spends crawl on
		// pages we have told Google to skip.
		if ( ! empty( $spec['range'] ) && is_array( $spec['range'] ) ) {
			list( $min, $max ) = array_map( 'intval', $spec['range'] );
			$terms = array_values( array_filter( $terms, static function ( $t ) use ( $min, $max ) {
				$n = (int) $t->slug;
				return $n >= $min && $n <= $max;
			} ) );
			usort( $terms, static function ( $a, $b ) {
				return (int) $a->slug <=> (int) $b->slug;
			} );
		}

		$limit = isset( $spec['limit'] ) ? (int) $spec['limit'] : 0;
		if ( $limit > 0 ) {
			$terms = array_slice( $terms, 0, $limit );
		}

		return $terms;
	}
}

/**
 * Render the mega panel. Outputs nothing when nav_mega is empty or every
 * configured taxonomy is absent, so a brand can switch it off from config alone.
 *
 * @param string $variant 'panel' for the desktop header, 'drawer' for mobile.
 */
if ( ! function_exists( 'lvc_mega_menu' ) ) {
	function lvc_mega_menu( $variant = 'panel' ) {
		$config = (array) lvc_config( 'nav_mega', array() );
		if ( ! $config ) {
			return;
		}

		$columns = array();
		foreach ( $config as $taxonomy => $spec ) {
			$spec  = (array) $spec;
			$terms = lvc_mega_terms( $taxonomy, $spec );
			if ( $terms ) {
				$columns[] = array( 'spec' => $spec, 'terms' => $terms, 'taxonomy' => $taxonomy );
			}
		}
		if ( ! $columns ) {
			return;
		}

		$base = 'lvc-mega' . ( 'drawer' === $variant ? ' lvc-mega--drawer' : '' );
		echo '<div class="' . esc_attr( $base ) . '" data-lvc-mega>';

		foreach ( $columns as $col ) {
			$spec    = $col['spec'];
			$compact = ! empty( $spec['compact'] );
			$counts  = ! empty( $spec['counts'] );
			$label   = isset( $spec['label'] ) ? (string) $spec['label'] : '';

			echo '<div class="lvc-mega__col lvc-mega__col--' . esc_attr( $col['taxonomy'] ) . ( $compact ? ' lvc-mega__col--compact' : '' ) . '">';
			if ( $label ) {
				echo '<p class="lvc-mega__label">' . esc_html( $label ) . '</p>';
			}
			echo '<div class="lvc-mega__list">';

			foreach ( $col['terms'] as $term ) {
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				// Numeric facets read better as "6 BR" than "6 Bedrooms" in a chip.
				$name = $compact ? ( (int) $term->slug . ' BR' ) : $term->name;

				echo '<a class="' . ( $compact ? 'lvc-mega__chip' : 'lvc-mega__item' ) . '" href="' . esc_url( $url ) . '">';
				echo '<span class="lvc-mega__name">' . esc_html( $name ) . '</span>';
				if ( $counts ) {
					// Printed live from the term, never written into copy — see
					// docs/LESSONS_LEARNED.md on counts that go stale.
					echo '<span class="lvc-mega__count">' . (int) $term->count . '</span>';
				}
				echo '</a>';
			}

			echo '</div></div>';
		}

		echo '<a class="lvc-mega__all" href="' . esc_url( lvc_archive_url() ) . '">All ' . esc_html( lvc_config( 'cpt_plural', 'Villas' ) ) . ' &rarr;</a>';
		echo '</div>';
	}
}
