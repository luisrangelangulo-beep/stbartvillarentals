<?php
/**
 * St Barts Villa Rentals — Magazine Index (posts page).
 *
 * Cloned from tulumholidayvillas-theme archive-magazine.php (v2.2) per the
 * clone-don't-rebuild rule: same hero/tabs/featured/grid/pagination markup and
 * CSS, recolored to the St Barts salt-rose palette. Adapted to this site's data model:
 * the magazine here is the STANDARD POSTS PAGE (page_for_posts → /magazine/),
 * so the loop runs on the main query with standard pagination — no magazine
 * CPT, no category slug allowlist, no custom WP_Query. The "featured" slot is
 * the newest sticky post (WordPress-native analogue of THV's ACF flag), images
 * come from the post thumbnail with a graceful dark surface when absent, and
 * category tabs use standard get_category_link() URLs.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Data ─────────────────────────────────────────────────────────────────
$posts_page   = (int) get_option( 'page_for_posts' );
$magazine_url = $posts_page ? get_permalink( $posts_page ) : lvc_page_url( 'magazine' );

// Category tabs: every non-empty category except the default bucket.
// Standard get_category_link() is the single URL source for these.
$tab_terms = get_categories(
	array(
		'hide_empty' => true,
		'exclude'    => array( (int) get_option( 'default_category' ) ),
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
$tab_terms = is_wp_error( $tab_terms ) ? array() : $tab_terms;

// Featured article: newest sticky post, first page only (WP prepends stickies
// to the main query on page 1, so skipping it in the loop below is safe).
$featured = null;
if ( ! is_paged() ) {
	$sticky_ids = array_map( 'intval', (array) get_option( 'sticky_posts', array() ) );
	if ( $sticky_ids ) {
		$sticky_posts = get_posts(
			array(
				'post_type'           => 'post',
				'post__in'            => $sticky_ids,
				'posts_per_page'      => 1,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			)
		);
		$featured = $sticky_posts ? $sticky_posts[0] : null;
	}
}

get_header();
?>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
	MAGAZINE INDEX (cloned from THV archive-magazine v2.2, St Barts salt-rose palette)
	All var(--lvc-*) tokens carry explicit fallback values so the template is
	self-sufficient when the homepage token block isn't in scope.
	═══════════════════════════════════════════════════════════════════════════ */

.lvc-archive {
	padding-top: 120px;
	background: var(--lvc-bg, #12100f);
	min-height: 100vh;
	color: rgba(255,255,255,0.78);
}

.lvc-archive a { text-decoration: none; }

/* ─── Hero ──────────────────────────────────────────────────────────── */
.lvc-archive__hero {
	padding: 4rem var(--lvc-px, 2rem) 3rem;
	max-width: 1600px;
	margin: 0 auto;
	text-align: center;
}

.lvc-archive__eyebrow {
	display: inline-flex;
	align-items: center;
	gap: 10px;
	font-size: 0.68rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color: var(--lvc-accent, #c2818c);
	margin: 0 0 1rem;
	font-weight: 500;
}

.lvc-archive__title {
	font-family: var(--lvc-fd, 'Gilda Display', Georgia, serif);
	font-size: clamp(2.5rem, 5vw, 4rem);
	font-weight: 400;
	color: #fff !important;
	margin: 0 0 1rem;
	line-height: 1.1;
}

.lvc-archive__title em {
	font-style: italic;
	color: var(--lvc-accent, #c2818c);
}

.lvc-archive__intro {
	font-size: 1.05rem;
	color: rgba(255,255,255,0.7);
	line-height: 1.75;
	max-width: 700px;
	margin: 0 auto 2rem;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

/* ─── Category Tabs ─────────────────────────────────────────────────── */
.lvc-archive__tabs {
	display: flex;
	justify-content: center;
	gap: 0.5rem;
	flex-wrap: wrap;
	margin-bottom: 3rem;
	padding: 0 var(--lvc-px, 2rem);
}

.lvc-archive__tab {
	padding: 0.6rem 1.25rem;
	font-size: 0.72rem;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	color: rgba(255,255,255,0.55) !important;
	border: 1px solid rgba(255,255,255,0.12);
	transition: all 0.2s ease;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
	font-weight: 500;
	background: transparent;
	text-decoration: none;
}

.lvc-archive__tab:hover {
	color: var(--lvc-accent, #c2818c) !important;
	border-color: var(--lvc-accent, #c2818c);
	background: rgba(194,129,140,0.05);
}

.lvc-archive__tab--active {
	color: var(--lvc-accent, #c2818c) !important;
	border-color: var(--lvc-accent, #c2818c);
	background: rgba(194,129,140,0.05);
}

/* ─── Featured Article ──────────────────────────────────────────────── */
.lvc-archive__featured {
	max-width: 1600px;
	margin: 0 auto 4rem;
	padding: 0 var(--lvc-px, 2rem);
}

.lvc-archive__featured-card {
	position: relative;
	display: block;
	height: 500px;
	overflow: hidden;
	border-radius: 4px;
	background-color: var(--lvc-bg3, #1c1917);
	text-decoration: none;
}

.lvc-archive__featured-bg {
	position: absolute;
	inset: 0;
	background-size: cover;
	background-position: center;
	transition: transform 0.6s var(--lvc-ease, cubic-bezier(.2,.8,.2,1));
}

.lvc-archive__featured-card:hover .lvc-archive__featured-bg {
	transform: scale(1.03);
}

.lvc-archive__featured-overlay {
	position: absolute;
	inset: 0;
	background: linear-gradient(to top,
		rgba(18,16,15,0.95) 0%,
		rgba(18,16,15,0.5) 50%,
		rgba(18,16,15,0.2) 100%);
}

.lvc-archive__featured-content {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 3rem;
	z-index: 2;
}

.lvc-archive__featured-meta {
	display: flex;
	align-items: center;
	gap: 0.5rem;
	font-size: 0.72rem;
	color: var(--lvc-accent, #c2818c) !important;
	margin: 0 0 1rem;
	font-weight: 500;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

.lvc-archive__featured-title {
	font-family: var(--lvc-fd, 'Gilda Display', Georgia, serif);
	font-size: clamp(1.75rem, 3vw, 2.5rem);
	font-weight: 400;
	color: #fff !important;
	margin: 0 0 0.75rem;
	line-height: 1.15;
	max-width: 600px;
}

.lvc-archive__featured-excerpt {
	font-size: 0.95rem;
	color: rgba(255,255,255,0.75);
	line-height: 1.7;
	max-width: 500px;
	margin: 0 0 1.25rem;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

.lvc-archive__featured-cta {
	font-size: 0.72rem;
	font-weight: 600;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-accent, #c2818c) !important;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

/* ─── Article Grid ──────────────────────────────────────────────────── */
.lvc-archive__grid {
	max-width: 1600px;
	margin: 0 auto;
	padding: 0 var(--lvc-px, 2rem);
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 1.5rem;
}

.lvc-archive__card {
	position: relative;
	height: 360px;
	overflow: hidden;
	display: block;
	border-radius: 4px;
	border: 1px solid var(--lvc-border, rgba(245,240,234,0.06));
	background: var(--lvc-bg2, #1a1715);
	text-decoration: none;
}

.lvc-archive__card-img {
	position: absolute;
	inset: 0;
	background-size: cover;
	background-position: center;
	transition: transform 0.5s var(--lvc-ease, cubic-bezier(.2,.8,.2,1));
}

.lvc-archive__card:hover .lvc-archive__card-img {
	transform: scale(1.05);
}

.lvc-archive__card-overlay {
	position: absolute;
	inset: 0;
	background: linear-gradient(to top,
		rgba(18,16,15,0.92) 0%,
		rgba(18,16,15,0.4) 60%,
		transparent 100%);
}

.lvc-archive__card-body {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 1.75rem;
	z-index: 2;
}

.lvc-archive__card-cat {
	font-size: 0.62rem;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-accent, #c2818c) !important;
	font-weight: 500;
	margin: 0 0 0.5rem;
	display: block;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

.lvc-archive__card-title {
	font-family: var(--lvc-fd, 'Gilda Display', Georgia, serif);
	font-size: 1.25rem;
	font-weight: 400;
	color: #fff !important;
	margin: 0 0 0.5rem;
	line-height: 1.2;
}

.lvc-archive__card-readtime {
	font-size: 0.72rem;
	color: rgba(255,255,255,0.5);
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

/* ─── Pagination ────────────────────────────────────────────────────── */
.lvc-archive__pagination {
	max-width: 1600px;
	margin: 4rem auto;
	padding: 0 var(--lvc-px, 2rem);
	display: flex;
	justify-content: center;
	gap: 0.5rem;
	flex-wrap: wrap;
}

.lvc-archive__pagination a,
.lvc-archive__pagination span.current,
.lvc-archive__pagination span.dots {
	width: 40px;
	height: 40px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 0.85rem;
	color: rgba(255,255,255,0.55);
	border: 1px solid var(--lvc-border, rgba(255,255,255,0.12));
	transition: all 0.2s ease;
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
	text-decoration: none;
}

.lvc-archive__pagination a:hover {
	color: var(--lvc-accent, #c2818c);
	border-color: var(--lvc-accent, #c2818c);
	background: rgba(194,129,140,0.05);
}

.lvc-archive__pagination span.current {
	color: var(--lvc-accent, #c2818c);
	border-color: var(--lvc-accent, #c2818c);
	background: rgba(194,129,140,0.05);
}

/* ─── Empty state ───────────────────────────────────────────────────── */
.lvc-archive__empty {
	text-align: center;
	padding: 4rem var(--lvc-px, 2rem);
	color: rgba(255,255,255,0.5);
	font-family: var(--lvc-fb, 'Albert Sans', system-ui, sans-serif);
}

/* ─── Responsive ────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
	.lvc-archive__grid {
		grid-template-columns: repeat(2, 1fr);
	}
	.lvc-archive__featured-card { height: 400px; }
}

@media (max-width: 640px) {
	.lvc-archive__grid {
		grid-template-columns: 1fr;
	}
	.lvc-archive__featured-content { padding: 2rem; }
	.lvc-archive__featured-card { height: 360px; }
	.lvc-archive__tabs { gap: 0.35rem; }
	.lvc-archive__tab {
		padding: 0.5rem 1rem;
		font-size: 0.65rem;
	}
	.lvc-archive__hero { padding: 3rem 1.25rem 2rem; }
}
</style>

<div class="lvc-archive">

	<!-- Hero -->
	<div class="lvc-archive__hero">
		<p class="lvc-archive__eyebrow"><?php echo esc_html( lvc_config( 'region' ) ); ?> Travel Magazine</p>
		<h1 class="lvc-archive__title">
			Stories, Guides &amp; <em>Island Secrets</em>
		</h1>
		<p class="lvc-archive__intro">
			Curated beach guides, villa lifestyle insights, and insider knowledge from the team that matches travelers with <?php echo esc_html( lvc_config( 'region' ) ); ?>'s best villas. Every article is written to help you plan a better trip &mdash; and find the right villa.
		</p>
	</div>

	<!-- Category Tabs -->
	<?php if ( ! empty( $tab_terms ) ) : ?>
	<div class="lvc-archive__tabs">
		<a href="<?php echo esc_url( $magazine_url ); ?>"
			class="lvc-archive__tab lvc-archive__tab--active">
			All
		</a>
		<?php
		foreach ( $tab_terms as $tab_term ) :
			$tab_url = get_category_link( $tab_term );
			if ( is_wp_error( $tab_url ) ) {
				continue;
			}
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>" class="lvc-archive__tab">
				<?php echo esc_html( $tab_term->name ); ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php
	if ( $featured ) :
		$f_id   = $featured->ID;
		$f_cat  = get_the_category( $f_id );
		$f_hero = function_exists( 'lvc_blog_image_url' ) ? lvc_blog_image_url( $f_id, 'large' ) : get_the_post_thumbnail_url( $f_id, 'large' );
		$f_read = function_exists( 'lvc_article_read_time' ) ? lvc_article_read_time( $f_id ) : (string) lvc_field( 'read_time', $f_id );
		?>
	<!-- Featured Article -->
	<div class="lvc-archive__featured">
		<a href="<?php echo esc_url( get_permalink( $f_id ) ); ?>" class="lvc-archive__featured-card">
			<?php if ( $f_hero ) : ?>
				<div class="lvc-archive__featured-bg"
					style="background-image: url('<?php echo esc_url( $f_hero ); ?>')"></div>
			<?php endif; ?>
			<div class="lvc-archive__featured-overlay"></div>
			<div class="lvc-archive__featured-content">
				<div class="lvc-archive__featured-meta">
					<span><?php echo $f_cat ? esc_html( $f_cat[0]->name ) : 'Guide'; ?></span>
					<?php if ( $f_read ) : ?>
						<span aria-hidden="true">&middot;</span>
						<span><?php echo esc_html( $f_read ); ?></span>
					<?php endif; ?>
				</div>
				<h2 class="lvc-archive__featured-title"><?php echo esc_html( get_the_title( $f_id ) ); ?></h2>
				<?php
				$f_excerpt = wp_trim_words( get_the_excerpt( $f_id ), 20 );
				if ( $f_excerpt ) :
					?>
					<p class="lvc-archive__featured-excerpt"><?php echo esc_html( $f_excerpt ); ?></p>
				<?php endif; ?>
				<span class="lvc-archive__featured-cta">Read Article &rarr;</span>
			</div>
		</a>
	</div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<!-- Article Grid -->
		<div class="lvc-archive__grid">
			<?php
			while ( have_posts() ) :
				the_post();
				// Skip the featured (sticky) article so it doesn't appear twice.
				if ( $featured && get_the_ID() === $featured->ID ) {
					continue;
				}

				$post_cats = get_the_category();
				$card_hero = function_exists( 'lvc_blog_image_url' ) ? lvc_blog_image_url( get_the_ID(), 'medium_large' ) : get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
				$card_read = function_exists( 'lvc_article_read_time' ) ? lvc_article_read_time( get_the_ID() ) : (string) lvc_field( 'read_time', get_the_ID() );
				?>
			<a href="<?php the_permalink(); ?>" class="lvc-archive__card">
				<?php if ( $card_hero ) : ?>
					<div class="lvc-archive__card-img"
						style="background-image: url('<?php echo esc_url( $card_hero ); ?>')"></div>
				<?php endif; ?>
				<div class="lvc-archive__card-overlay"></div>
				<div class="lvc-archive__card-body">
					<span class="lvc-archive__card-cat">
						<?php echo $post_cats ? esc_html( $post_cats[0]->name ) : 'Guide'; ?>
					</span>
					<h3 class="lvc-archive__card-title"><?php the_title(); ?></h3>
					<?php if ( $card_read ) : ?>
						<span class="lvc-archive__card-readtime"><?php echo esc_html( $card_read ); ?></span>
					<?php endif; ?>
				</div>
			</a>
				<?php
			endwhile;
			?>
		</div>

		<!-- Pagination (main query) -->
		<?php
		global $wp_query;
		if ( $wp_query->max_num_pages > 1 ) :
			$big              = 999999999;
			$pagination_links = paginate_links(
				array(
					'base'      => str_replace( (string) $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '?paged=%#%',
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'total'     => (int) $wp_query->max_num_pages,
					'prev_text' => '&larr;',
					'next_text' => '&rarr;',
					'type'      => 'array',
				)
			);
			if ( $pagination_links ) :
				?>
				<div class="lvc-archive__pagination">
					<?php
					foreach ( $pagination_links as $page_link ) {
						echo wp_kses_post( $page_link );
					}
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

	<?php else : ?>
		<div class="lvc-archive__empty">
			<p>No articles yet. Check back soon.</p>
		</div>
	<?php endif; ?>

</div>

<?php get_footer(); ?>
