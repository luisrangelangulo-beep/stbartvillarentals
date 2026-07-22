<?php
/**
 * Single property — mirrors the Punta Mita villa page: hero, trust strip, stats,
 * highlight chips, dual gallery, editorial "about" with living blocks, amenities,
 * what's-included, testimonials, FAQ, inquiry, related, plus a sticky mobile CTA.
 *
 * Editorial copy resolves through lvc_content() (property → place term →
 * universal option → shipped default), so a sparsely-filled property still
 * renders a complete page instead of empty sections. Every section is guarded so
 * it self-hides when it genuinely has nothing to show. Structure + logic only;
 * all styling via assets/brand.css against the class hooks in TOKEN_CONTRACT.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$lvc_id     = get_the_ID();
	$lvc_title  = get_the_title();
	$lvc_h1     = lvc_field( 'h1_title', $lvc_id, $lvc_title );
	$lvc_term   = lvc_primary_place_term( $lvc_id );
	$lvc_place  = $lvc_term instanceof WP_Term ? $lvc_term->name : '';

	// Curated images only — never derived from gallery position (LESSONS §2).
	$lvc_hero   = lvc_property_image( $lvc_id, 'full', 'hero' );

	// Facts.
	$lvc_beds   = lvc_field( 'bed_count', $lvc_id );
	$lvc_baths  = lvc_field( 'bath_count', $lvc_id );
	$lvc_guests = lvc_field( 'guests_max', $lvc_id );

	// Editorial — four-tier fallback so nothing renders blank.
	$lvc_tagline = lvc_content( 'tagline', 'tagline', 'universal_tagline', $lvc_id, $lvc_term );
	$lvc_intro   = lvc_content( 'intro_paragraph', 'intro', 'universal_property_intro', $lvc_id, $lvc_term );
	$lvc_setting = lvc_content( 'setting_positioning', null, 'universal_setting', $lvc_id, $lvc_term );
	$lvc_editor  = lvc_content( 'editorial_text', null, 'universal_editorial', $lvc_id, $lvc_term );
	$lvc_desc    = lvc_field( 'property_descr', $lvc_id, get_the_content() );
	$lvc_indoor  = lvc_field( 'indoor_living', $lvc_id );
	$lvc_outdoor = lvc_field( 'outdoor_living', $lvc_id );
	$lvc_bedrm   = lvc_field( 'bedroom_desc', $lvc_id );
	$lvc_cater   = lvc_field( 'catering_detail', $lvc_id );

	// Highlight chips.
	$lvc_view   = lvc_field( 'view_type', $lvc_id );
	$lvc_access = lvc_field( 'access_type', $lvc_id );

	if ( function_exists( 'lvc_schema_property' ) ) {
		lvc_schema_property( $lvc_id );
	}
	?>
	<main class="lvc-single">

		<?php /* ── HERO ─────────────────────────────────────────────── */ ?>
		<section class="lvc-single__hero<?php echo $lvc_hero ? ' lvc-single__hero--img' : ''; ?>">
			<?php if ( $lvc_hero ) : ?>
				<img class="lvc-single__img" src="<?php echo esc_url( $lvc_hero ); ?>" alt="<?php echo esc_attr( $lvc_title ); ?>" fetchpriority="high">
				<span class="lvc-single__scrim" aria-hidden="true"></span>
			<?php endif; ?>
			<div class="lvc-single__heading lvc-section">
				<?php if ( $lvc_place ) : ?><p class="lvc-eyebrow"><?php echo esc_html( $lvc_place ); ?></p><?php endif; ?>
				<h1 class="lvc-single__title"><?php echo esc_html( $lvc_h1 ); ?></h1>
				<?php if ( $lvc_tagline ) : ?><p class="lvc-single__tagline"><?php echo esc_html( wp_strip_all_tags( $lvc_tagline ) ); ?></p><?php endif; ?>
				<div class="lvc-single__hero-cta">
					<a class="lvc-btn" href="#inquiry">Request Availability</a>
					<?php if ( lvc_whatsapp_url() ) : ?>
						<a class="lvc-btn--ghost" href="<?php echo esc_url( lvc_whatsapp_url() ); ?>" target="_blank" rel="noopener">Speak With a Specialist</a>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<?php /* ── TRUST STRIP ──────────────────────────────────────── */ ?>
		<section class="lvc-single__trust" aria-label="Why book direct">
			<span>Direct Booking</span>
			<span>Concierge Planning</span>
			<span>Verified Property</span>
			<span>Fast Response</span>
		</section>

		<?php /* ── STATS ────────────────────────────────────────────── */ ?>
		<?php if ( $lvc_beds || $lvc_baths || $lvc_guests ) : ?>
			<section class="lvc-single__stats" aria-label="At a glance">
				<?php if ( $lvc_beds ) : ?><div class="lvc-single__stat"><b><?php echo esc_html( $lvc_beds ); ?></b><span>Bedrooms</span></div><?php endif; ?>
				<?php if ( $lvc_baths ) : ?><div class="lvc-single__stat"><b><?php echo esc_html( $lvc_baths ); ?></b><span>Bathrooms</span></div><?php endif; ?>
				<?php if ( $lvc_guests ) : ?><div class="lvc-single__stat"><b><?php echo esc_html( $lvc_guests ); ?></b><span>Guests</span></div><?php endif; ?>
			</section>
		<?php endif; ?>

		<?php /* ── HIGHLIGHT CHIPS ──────────────────────────────────── */ ?>
		<?php
		$lvc_chips = array_values( array_filter( array( $lvc_access, $lvc_view ) ) );
		$lvc_amen  = get_the_terms( $lvc_id, 'amenity' );
		if ( $lvc_amen && ! is_wp_error( $lvc_amen ) ) {
			foreach ( array_slice( $lvc_amen, 0, 3 ) as $a ) {
				$lvc_chips[] = $a->name;
			}
		}
		if ( $lvc_chips ) : ?>
			<section class="lvc-single__highlights" aria-label="Highlights">
				<?php foreach ( $lvc_chips as $chip ) : ?><span class="lvc-chip"><?php echo esc_html( $chip ); ?></span><?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php
		/* ── GALLERIES — squares grid + full slider ───────────────── */
		$lvc_squares = lvc_gallery_urls( 'gallery_squares', $lvc_id );
		$lvc_slides  = lvc_gallery_urls( 'gallery_slider', $lvc_id );
		if ( ! $lvc_slides ) {
			$lvc_slides = $lvc_squares;
		}
		if ( $lvc_squares ) : ?>
			<section class="lvc-single__gallery lvc-gallery" aria-label="Photo gallery">
				<?php foreach ( $lvc_squares as $g ) : ?>
					<figure class="lvc-gallery__item"><img src="<?php echo esc_url( $g ); ?>" alt="<?php echo esc_attr( $lvc_title ); ?>" loading="lazy" decoding="async"></figure>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ( count( $lvc_slides ) > 1 ) : ?>
			<section class="lvc-single__slider lvc-slider" aria-label="All photos" data-lvc-slider>
				<div class="lvc-slider__track" data-lvc-slider-track tabindex="0" role="group" aria-label="Photo carousel, scrollable">
					<?php foreach ( $lvc_slides as $s ) : ?>
						<figure class="lvc-slider__slide"><img src="<?php echo esc_url( $s ); ?>" alt="<?php echo esc_attr( $lvc_title ); ?>" loading="lazy" decoding="async"></figure>
					<?php endforeach; ?>
				</div>
				<button class="lvc-slider__nav lvc-slider__nav--prev" type="button" data-lvc-slider-prev aria-label="Previous photo">&#8249;</button>
				<button class="lvc-slider__nav lvc-slider__nav--next" type="button" data-lvc-slider-next aria-label="Next photo">&#8250;</button>
				<p class="lvc-slider__count"><span data-lvc-slider-current>1</span> / <?php echo (int) count( $lvc_slides ); ?></p>
			</section>
		<?php endif; ?>

		<div class="lvc-single__body lvc-section">
			<article class="lvc-single__main">

				<?php /* ── ABOUT ────────────────────────────────────── */ ?>
				<section class="lvc-single__about" id="about">
					<span class="lvc-eyebrow">About This <?php echo esc_html( lvc_config( 'cpt_singular', 'Villa' ) ); ?></span>
					<h2 class="lvc-sec-title"><?php echo esc_html( $lvc_h1 ); ?><?php echo $lvc_place ? ' <em>in ' . esc_html( $lvc_place ) . '</em>' : ''; ?></h2>
					<?php if ( $lvc_intro ) : ?><div class="lvc-single__intro"><?php echo wp_kses_post( wpautop( $lvc_intro ) ); ?></div><?php endif; ?>
					<?php if ( $lvc_desc && $lvc_desc !== $lvc_intro ) : ?><div class="lvc-prose"><?php echo wp_kses_post( wpautop( $lvc_desc ) ); ?></div><?php endif; ?>

					<?php
					$lvc_living = array(
						'Indoor Living'  => $lvc_indoor,
						'Outdoor & Pool' => $lvc_outdoor,
						'The Bedrooms'   => $lvc_bedrm,
						'Setting'        => $lvc_setting,
					);
					$lvc_living = array_filter( $lvc_living );
					if ( $lvc_living ) : ?>
						<div class="lvc-desc-grid">
							<?php foreach ( $lvc_living as $lvc_heading => $lvc_text ) : ?>
								<div class="lvc-desc-block">
									<h3><?php echo esc_html( $lvc_heading ); ?></h3>
									<?php echo wp_kses_post( wpautop( $lvc_text ) ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</section>

				<?php
				/* ── WHAT'S INCLUDED ──────────────────────────────── */
				$lvc_included = lvc_list_lines( lvc_content( 'included_items', null, 'universal_included', $lvc_id, $lvc_term ) );
				$lvc_request  = lvc_list_lines( lvc_content( 'on_request_items', null, 'universal_on_request', $lvc_id, $lvc_term ) );
				if ( $lvc_included || $lvc_request ) : ?>
					<section class="lvc-single__included">
						<h2 class="lvc-sec-title">What's Included</h2>
						<div class="lvc-included__cols">
							<?php if ( $lvc_included ) : ?>
								<div class="lvc-included__col">
									<h3>Included in your stay</h3>
									<ul class="lvc-list"><?php foreach ( $lvc_included as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul>
								</div>
							<?php endif; ?>
							<?php if ( $lvc_request ) : ?>
								<div class="lvc-included__col">
									<h3>Available on request</h3>
									<ul class="lvc-list lvc-list--request"><?php foreach ( $lvc_request as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul>
								</div>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php /* ── AMENITIES ────────────────────────────────── */ ?>
				<?php if ( $lvc_amen && ! is_wp_error( $lvc_amen ) ) : ?>
					<section class="lvc-single__amenities">
						<h2 class="lvc-sec-title">Amenities</h2>
						<ul class="lvc-amenities">
							<?php foreach ( $lvc_amen as $a ) : ?><li><?php echo esc_html( $a->name ); ?></li><?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $lvc_cater ) : ?>
					<section class="lvc-single__service">
						<h2 class="lvc-sec-title">Service &amp; Catering</h2>
						<div class="lvc-prose"><?php echo wp_kses_post( wpautop( $lvc_cater ) ); ?></div>
					</section>
				<?php endif; ?>

				<?php
				/* ── TESTIMONIALS — real, verified reviews only ─────── */
				$lvc_testimonials = lvc_field( 'testimonials', $lvc_id, array() );
				if ( is_array( $lvc_testimonials ) && $lvc_testimonials ) : ?>
					<section class="lvc-single__testimonials" aria-label="Guest reviews">
						<h2 class="lvc-sec-title">What Guests Say</h2>
						<div class="lvc-testimonials">
							<?php foreach ( $lvc_testimonials as $t ) :
								$quote = isset( $t['quote'] ) ? trim( (string) $t['quote'] ) : '';
								if ( '' === $quote ) {
									continue;
								}
								$name = isset( $t['guest_name'] ) ? trim( (string) $t['guest_name'] ) : '';
								$loc  = isset( $t['guest_location'] ) ? trim( (string) $t['guest_location'] ) : '';
								$date = isset( $t['stay_date'] ) ? trim( (string) $t['stay_date'] ) : '';
								?>
								<figure class="lvc-testimonial">
									<blockquote><?php echo esc_html( $quote ); ?></blockquote>
									<figcaption>
										<?php echo esc_html( $name ); ?><?php echo $loc ? ' &middot; ' . esc_html( $loc ) : ''; ?><?php echo $date ? ' &middot; ' . esc_html( $date ) : ''; ?>
									</figcaption>
								</figure>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php
				/* ── FAQ — property repeater, else universal ────────── */
				$lvc_faq = lvc_property_faq( $lvc_id );
				if ( $lvc_faq ) : ?>
					<section class="lvc-faq" aria-label="Frequently asked questions">
						<h2 class="lvc-sec-title">Good to Know</h2>
						<?php foreach ( $lvc_faq as $qa ) : ?>
							<details class="lvc-faq__item">
								<summary class="lvc-faq__q"><?php echo esc_html( $qa['question'] ); ?></summary>
								<div class="lvc-faq__a"><?php echo wp_kses_post( wpautop( $qa['answer'] ) ); ?></div>
							</details>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>
			</article>

			<aside class="lvc-single__sidebar">
				<div class="lvc-single__inquiry" id="inquiry">
					<h2 class="lvc-sec-title">Enquire</h2>
					<?php if ( $lvc_editor ) : ?><p class="lvc-single__inquiry-line"><?php echo esc_html( wp_strip_all_tags( $lvc_editor ) ); ?></p><?php endif; ?>
					<?php
					get_template_part( 'template-parts/inquiry-form', null, array(
						'property_name' => $lvc_title,
						'submit_label'  => 'Request Availability',
					) );
					?>
				</div>
			</aside>
		</div>

		<?php
		/* ── RELATED ──────────────────────────────────────────────── */
		$lvc_related = function_exists( 'lvc_related_properties' ) ? lvc_related_properties( $lvc_id, 3 ) : array();
		if ( $lvc_related ) : ?>
			<section class="lvc-section lvc-related">
				<h2 class="lvc-sec-title">Similar <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></h2>
				<div class="lvc-grid lvc-grid--3">
					<?php foreach ( $lvc_related as $rid ) {
						get_template_part( 'template-parts/card-property', null, array( 'id' => $rid ) );
					} ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="lvc-single__mobilebar">
			<span class="lvc-single__mobilebar-name"><?php echo esc_html( $lvc_title ); ?></span>
			<a class="lvc-btn" href="#inquiry">Request Availability</a>
		</div>
	</main>
	<?php
endwhile;

get_footer();
