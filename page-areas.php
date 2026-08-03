<?php
/**
 * St Barts Villa Rentals — "Areas" hub page.
 *
 * WordPress template-hierarchy convention: this file auto-applies to the
 * Page whose slug is exactly `areas` — no template-router change needed. A
 * WP Page with that slug must exist (title "Areas", content can be left
 * blank; this template ignores post_content and renders the grid below).
 *
 * Why this exists: the site had no "browse all areas" index anywhere — only
 * a per-term page for each of the `area` terms, reachable solely from the
 * header's "Browse" mega-menu dropdown. That left no page for a standalone
 * header nav link to point to, and no single hub earning/passing links to
 * every area page. This page is that hub, modeled on the same pattern
 * Punta Mita already uses at /communities/.
 *
 * Deliberately lists ALL `area` terms, not just the ones clearing
 * `min_index_count` (unlike footer.php's Areas column, which filters to
 * indexable terms only) — Luis's call: several areas are under the index
 * floor only because of inventory, more villas are being added, and the hub
 * page itself is useful navigation regardless of any one area's current
 * index status. Each card still shows its live villa count, so a
 * lightly-stocked area reads as exactly that, not as a broken link.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_areas = get_terms(
	array(
		'taxonomy'   => 'area',
		'hide_empty' => false, // show every area, even one currently below min_index_count.
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
$lvc_areas = is_wp_error( $lvc_areas ) ? array() : $lvc_areas;

$lvc_min_index = (int) lvc_config( 'min_index_count', 3 );
$lvc_region    = (string) lvc_config( 'region', 'St Barthélemy' );

get_header();
?>

<style>
/* Namespace: lvc-areas-hub-*. Reuses the site's existing design tokens
   (declared globally in header.php / term-archive.php) rather than
   redeclaring them, so a palette change elsewhere stays in sync here. */

.lvc-areas-hub {
	background: var( --lvc-bg, #12100f );
	color: var( --lvc-text, #f5f0ea );
	padding: clamp( 6rem, 12vw, 9rem ) var( --lvc-px, clamp( 1.25rem, 5vw, 4rem ) ) 5rem;
}
.lvc-areas-hub__eyebrow {
	font-family: var( --lvc-fb, sans-serif );
	font-size: 0.8rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var( --lvc-accent, #c2818c );
	margin: 0 0 0.75rem;
}
.lvc-areas-hub__h1 {
	font-family: var( --lvc-fd, Georgia, serif );
	font-weight: 400;
	font-size: clamp( 2rem, 4.5vw, 3.25rem );
	line-height: 1.15;
	margin: 0 0 1rem;
}
.lvc-areas-hub__intro {
	font-family: var( --lvc-fb, sans-serif );
	color: var( --lvc-soft, #c3b8b0 );
	max-width: 62ch;
	line-height: 1.65;
	margin: 0 0 3rem;
}
.lvc-areas-hub__grid {
	display: grid;
	grid-template-columns: repeat( auto-fill, minmax( 260px, 1fr ) );
	gap: 1.5rem;
}
.lvc-areas-hub__card {
	display: block;
	position: relative;
	border-radius: 4px;
	overflow: hidden;
	background: var( --lvc-bg2, #1a1715 );
	border: 1px solid var( --lvc-border, rgba(245,240,234,0.06) );
	text-decoration: none;
	color: inherit;
	transition: border-color 0.25s var( --lvc-ease, ease ), transform 0.25s var( --lvc-ease, ease );
}
.lvc-areas-hub__card:hover {
	border-color: var( --lvc-border-h, rgba(194,129,140,0.3) );
	transform: translateY( -2px );
}
/* Only rendered when the term actually has a hero image — an empty 4:3 box on
   a card with no photo reads as a broken image, not as a design. */
.lvc-areas-hub__card-media {
	aspect-ratio: 4 / 3;
	background-size: cover;
	background-position: center;
	background-color: var( --lvc-bg2, #1a1715 );
}
.lvc-areas-hub__card-body { padding: 1.1rem 1.25rem 1.3rem; }
.lvc-areas-hub__card-name {
	font-family: var( --lvc-fd, Georgia, serif );
	font-size: 1.25rem;
	margin: 0 0 0.35rem;
}
.lvc-areas-hub__card-tagline {
	font-family: var( --lvc-fb, sans-serif );
	font-size: 0.9rem;
	color: var( --lvc-soft, #c3b8b0 );
	margin: 0 0 0.6rem;
	line-height: 1.4;
}
.lvc-areas-hub__card-count {
	font-family: var( --lvc-fb, sans-serif );
	font-size: 0.78rem;
	letter-spacing: 0.04em;
	text-transform: uppercase;
	color: var( --lvc-muted, #9c918b );
}
</style>

<main class="lvc-areas-hub">
	<p class="lvc-areas-hub__eyebrow"><?php echo esc_html( $lvc_region ); ?></p>
	<h1 class="lvc-areas-hub__h1">Explore St Barts by Area</h1>
	<p class="lvc-areas-hub__intro">
		Every villa on this site sits in one of <?php echo (int) count( $lvc_areas ); ?> named
		areas across the island — from Gustavia's harbor hillsides to St Jean's beachfront and
		the quieter south-coast bays. Pick an area below for its villas, access notes, and
		what makes it different from its neighbors.
	</p>

	<?php if ( $lvc_areas ) : ?>
	<div class="lvc-areas-hub__grid">
		<?php
		foreach ( $lvc_areas as $lvc_area ) :
			$lvc_url = get_term_link( $lvc_area );
			if ( is_wp_error( $lvc_url ) ) {
				continue;
			}
			$lvc_key     = 'term_' . $lvc_area->term_id;
			$lvc_hero    = lvc_priority_image_url( (string) lvc_field( 'hero_image_url', $lvc_key ) );
			$lvc_tagline = (string) lvc_field( 'tagline', $lvc_key );
			$lvc_count   = (int) $lvc_area->count;
			?>
			<a class="lvc-areas-hub__card" href="<?php echo esc_url( $lvc_url ); ?>">
				<?php if ( $lvc_hero ) : ?>
				<div class="lvc-areas-hub__card-media" style="background-image:url('<?php echo esc_url( $lvc_hero ); ?>')"></div>
				<?php endif; ?>
				<div class="lvc-areas-hub__card-body">
					<p class="lvc-areas-hub__card-name"><?php echo esc_html( $lvc_area->name ); ?></p>
					<?php if ( $lvc_tagline ) : ?>
					<p class="lvc-areas-hub__card-tagline"><?php echo esc_html( $lvc_tagline ); ?></p>
					<?php endif; ?>
					<p class="lvc-areas-hub__card-count">
						<?php
						if ( $lvc_count > 0 ) {
							echo (int) $lvc_count . ' ' . esc_html( 1 === $lvc_count ? 'Villa' : 'Villas' );
						} else {
							echo 'Coming soon';
						}
						?>
					</p>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
