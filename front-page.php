<?php
/**
 * Homepage — hero → collections → areas → featured → how-we-help → CTA.
 * Sections render from taxonomy terms + featured properties; copy from config
 * with ACF homepage overrides where present. Structure + logic only, no styling.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$lvc_home   = (int) get_option( 'page_on_front' );
$lvc_cpt    = lvc_config( 'cpt', 'villa' );
$lvc_plural = lvc_config( 'cpt_plural', 'Villas' );

$lvc_hero_title = lvc_field( 'home_hero_title', $lvc_home, lvc_brand() );
$lvc_hero_sub   = lvc_field( 'home_hero_subtitle', $lvc_home, lvc_config( 'brand_tagline', '' ) );
$lvc_hero_img   = lvc_field( 'home_hero_image_url', $lvc_home );
?>
<main class="lvc-home">

	<section class="lvc-hero" <?php echo $lvc_hero_img ? 'style="--lvc-hero-img:url(\'' . esc_url( $lvc_hero_img ) . '\')"' : ''; ?>>
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow"><?php echo esc_html( lvc_config( 'brand_tagline', '' ) ); ?></p>
			<h1 class="lvc-hero__title"><?php echo esc_html( $lvc_hero_title ); ?></h1>
			<?php if ( $lvc_hero_sub ) : ?><p class="lvc-hero__sub"><?php echo esc_html( $lvc_hero_sub ); ?></p><?php endif; ?>
			<div class="lvc-hero__cta">
				<a class="lvc-btn" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse <?php echo esc_html( $lvc_plural ); ?></a>
				<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request a Match</a>
			</div>
		</div>
	</section>

	<?php
	// Collections band.
	$lvc_collections = get_terms( array( 'taxonomy' => 'collection', 'hide_empty' => true, 'number' => 6, 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( ! is_wp_error( $lvc_collections ) && $lvc_collections ) : ?>
		<section class="lvc-section">
			<div class="lvc-sec-header">
				<p class="lvc-eyebrow">By Collection</p>
				<h2 class="lvc-sec-title">Browse by Collection</h2>
			</div>
			<div class="lvc-grid lvc-grid--3">
				<?php foreach ( $lvc_collections as $t ) : $u = get_term_link( $t ); if ( is_wp_error( $u ) ) { continue; } ?>
					<a class="lvc-tile" href="<?php echo esc_url( $u ); ?>">
						<span class="lvc-tile__name"><?php echo esc_html( $t->name ); ?></span>
						<span class="lvc-tile__cta">Explore &rarr;</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Areas / communities band.
	$lvc_areas = get_terms( array( 'taxonomy' => 'area', 'hide_empty' => true, 'number' => 6, 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( ! is_wp_error( $lvc_areas ) && $lvc_areas ) : ?>
		<section class="lvc-section lvc-section--alt">
			<div class="lvc-sec-header">
				<p class="lvc-eyebrow">By Area</p>
				<h2 class="lvc-sec-title">Find the Right Area</h2>
			</div>
			<div class="lvc-grid lvc-grid--3">
				<?php foreach ( $lvc_areas as $t ) :
					$u = get_term_link( $t );
					if ( is_wp_error( $u ) ) { continue; }
					// Unprefixed field names against a term_{id} key — see
					// inc/property/term-fields.php. The term hero is the single
					// source for both this card and the archive's own hero.
					$img = lvc_field( 'hero_image_url', 'term_' . $t->term_id );
					$tag = lvc_field( 'tagline', 'term_' . $t->term_id );
					?>
					<a class="lvc-tile" href="<?php echo esc_url( $u ); ?>" <?php echo $img ? 'style="--lvc-tile-img:url(\'' . esc_url( $img ) . '\')"' : ''; ?>>
						<span class="lvc-tile__name"><?php echo esc_html( $t->name ); ?></span>
						<?php if ( $tag ) : ?><span class="lvc-tile__desc"><?php echo esc_html( $tag ); ?></span><?php endif; ?>
						<span class="lvc-tile__cta">Explore <?php echo esc_html( $lvc_plural ); ?> &rarr;</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Featured properties.
	$lvc_featured = new WP_Query( array(
		'post_type'      => $lvc_cpt,
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'meta_key'       => 'featured',
		'meta_value'     => '1',
		'orderby'        => 'rand',
	) );
	if ( ! $lvc_featured->have_posts() ) {
		wp_reset_postdata();
		$lvc_featured = new WP_Query( array( 'post_type' => $lvc_cpt, 'posts_per_page' => 6, 'post_status' => 'publish', 'orderby' => 'date' ) );
	}
	if ( $lvc_featured->have_posts() ) : ?>
		<section class="lvc-section">
			<div class="lvc-sec-header">
				<p class="lvc-eyebrow">Featured</p>
				<h2 class="lvc-sec-title">Handpicked <?php echo esc_html( $lvc_plural ); ?></h2>
			</div>
			<div class="lvc-grid lvc-grid--3">
				<?php while ( $lvc_featured->have_posts() ) : $lvc_featured->the_post(); ?>
					<?php get_template_part( 'template-parts/card-property', null, array( 'id' => get_the_ID() ) ); ?>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
			<p class="lvc-section__more"><a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">View All <?php echo esc_html( $lvc_plural ); ?> &rarr;</a></p>
		</section>
	<?php endif; ?>

	<section class="lvc-section lvc-section--alt lvc-cta">
		<div class="lvc-cta__inner">
			<h2 class="lvc-sec-title">Let us match you to the right <?php echo esc_html( lvc_config( 'cpt_singular', 'villa' ) ); ?></h2>
			<p>Share your dates and what you're looking for &mdash; a local specialist will shortlist the right options, booked direct.</p>
			<div class="lvc-hero__cta">
				<a class="lvc-btn" href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>">Request a Match</a>
				<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse All</a>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
