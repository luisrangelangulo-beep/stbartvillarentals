<?php
/**
 * St Barts Villa Rentals — Footer.
 *
 * Cloned from tulumholidayvillas' footer.php per the clone-don't-rebuild rule:
 * same pre-footer CTA strip, background-image footer, column grid, trust list
 * and legal bar, recolored to the salt-rose palette. Class prefix lvc-
 * replaces thv-.
 *
 * Differences from the THV source, on purpose:
 * - Areas column = live `area` taxonomy terms that clear the index floor
 *   (min_index_count) — no hardcoded slugs, no Tulum content.
 * - Explore column = this site's config-driven nav pages (lvc_page_url).
 * - Contact = lvc_config support_email / phone / whatsapp_url only.
 * - No social profile icons row — this brand has no own profiles yet; the
 *   "social" shortcuts here are the config-driven WhatsApp + email links.
 * - No inline JSON-LD — inc/seo/schema.php owns all schema.
 * - Footer background image comes from the same single source as the
 *   homepage/archive heroes (ACF on the front page + live-image fallback),
 *   not THV's R2 stock photo.
 * - THV's header/drawer JS is ported below but re-targeted at the lvc data
 *   attributes (data-lvc-drawer-toggle / data-lvc-drawer / data-lvc-mega-*)
 *   since this repo ships no assets/theme.js.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Areas column: every area with enough villas to be indexable, by count.
$lvc_footer_areas = get_terms(
	array(
		'taxonomy'   => 'area',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
$lvc_footer_areas = is_wp_error( $lvc_footer_areas ) ? array() : $lvc_footer_areas;
$lvc_min_index    = (int) lvc_config( 'min_index_count', 3 );
$lvc_footer_areas = array_values( array_filter( $lvc_footer_areas, static function ( $t ) use ( $lvc_min_index ) {
	return (int) $t->count >= $lvc_min_index;
} ) );

// Footer background — same single source as the homepage hero.
//
// Deliberately NO fallback image. Anguilla hardcoded one of its own villa
// photographs here; carrying that across would publish another island's property
// as this site's footer, which is the exact trap the front-page port already
// avoided. Empty is guarded in the markup below and falls back to a dark
// surface — the honest state until a hero is set on the front page.
$lvc_footer_bg = lvc_priority_image_url( (string) lvc_field( 'home_hero_image_url', (int) get_option( 'page_on_front' ) ) );

$lvc_whatsapp = lvc_whatsapp_url();
$lvc_email    = (string) lvc_config( 'support_email', '' );
$lvc_phone    = (string) lvc_config( 'phone', '' );
$lvc_region   = (string) lvc_config( 'region', 'St Barthélemy' );

// Legal links: real pages when they exist, else the URLs the previous footer used.
$lvc_privacy_page = get_page_by_path( 'privacy-policy' );
$lvc_privacy_url  = $lvc_privacy_page ? get_permalink( $lvc_privacy_page ) : home_url( '/privacy-policy/' );
$lvc_terms_page   = get_page_by_path( 'terms' );
if ( ! $lvc_terms_page ) {
	$lvc_terms_page = get_page_by_path( 'terms-and-conditions' );
}
$lvc_terms_url = $lvc_terms_page ? get_permalink( $lvc_terms_page ) : home_url( '/terms-and-conditions/' );

// Front page + taxonomy landings carry their own inquiry/CTA sections (THV pattern).
// The villa archive and every taxonomy landing already close with their own
// final CTA, so the prefooter repeated the same offer directly above it.
$lvc_hide_prefooter = is_front_page()
	|| is_post_type_archive( lvc_config( 'cpt', 'villa' ) )
	|| is_tax( array( 'area', 'bedrooms', 'beach_access', 'collection' ) );
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	FOOTER STYLES (cloned from THV header+footer style blocks, merged; scoped
	to .lvc-prefooter / .lvc-footer so nothing leaks)
	═══════════════════════════════════════════════════════════════════════════ -->
<style>
.lvc-prefooter *, .lvc-prefooter *::before, .lvc-prefooter *::after,
.lvc-footer *, .lvc-footer *::before, .lvc-footer *::after { box-sizing: border-box; }

/* Pre-footer CTA strip ───────────────────────────────────────────────────── */
.lvc-prefooter {
	background: var(--lvc-bg2);
	border-top: 1px solid var(--lvc-border);
	padding: 4rem var(--lvc-px);
	text-align: center;
}

.lvc-prefooter__inner {
	max-width: 900px;
	margin: 0 auto;
}

.lvc-prefooter__eyebrow {
	font-family: var(--lvc-fb);
	font-size: 0.7rem;
	letter-spacing: 0.18em;
	text-transform: uppercase;
	color: var(--lvc-accent);
	margin: 0 0 1rem;
	font-weight: 500;
}

.lvc-prefooter__title {
	font-family: var(--lvc-fd);
	font-size: clamp(1.85rem, 3.5vw, 2.75rem);
	font-weight: 400;
	color: #fff;
	line-height: 1.15;
	letter-spacing: -0.005em;
	margin: 0 0 1rem;
}

.lvc-prefooter__sub {
	font-family: var(--lvc-fb);
	font-size: 1rem;
	color: var(--lvc-soft);
	line-height: 1.7;
	margin: 0 auto 2rem;
	max-width: 580px;
}

.lvc-prefooter__btns {
	display: flex;
	gap: 0.85rem;
	justify-content: center;
	flex-wrap: wrap;
}

.lvc-prefooter__btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.55rem;
	padding: 0.95rem 1.75rem;
	font-family: var(--lvc-fb);
	font-size: 0.72rem;
	font-weight: 500;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	text-decoration: none;
	border: 1px solid transparent;
	transition: all 0.25s ease;
	white-space: nowrap;
}

.lvc-prefooter__btn--primary {
	background: var(--lvc-accent);
	color: #12100f;
	border-color: var(--lvc-accent);
}

.lvc-prefooter__btn--primary:hover {
	background: var(--lvc-accent-h);
	border-color: var(--lvc-accent-h);
	color: #12100f;
	transform: translateY(-2px);
	box-shadow: 0 6px 24px rgba(194,129,140,0.3);
}

.lvc-prefooter__btn--wa {
	background: #25D366;
	color: #fff;
	border-color: #25D366;
}

.lvc-prefooter__btn--wa:hover {
	background: #1ebd5b;
	border-color: #1ebd5b;
	color: #fff;
	transform: translateY(-2px);
	box-shadow: 0 6px 24px rgba(37,211,102,0.3);
}

/* ── FOOTER ─────────────────────────────────────────────────────────────── */
.lvc-footer {
	position  : relative;
	overflow  : hidden;
	background: var(--lvc-bg);
	border-top: 1px solid var(--lvc-border);
}

/* Background image layer */
.lvc-footer__bg {
	position            : absolute;
	inset               : 0;
	background-image    : url('<?php echo esc_url( $lvc_footer_bg ); ?>');
	background-size     : cover;
	background-position : center center;
	opacity             : 0.35;
	z-index             : 0;
}

/* Night-sea gradient overlay for text contrast */
.lvc-footer__bg-overlay {
	position  : absolute;
	inset     : 0;
	background: linear-gradient(180deg, rgba(18,16,15,0.85) 0%, rgba(18,16,15,0.7) 50%, rgba(18,16,15,0.9) 100%);
	z-index   : 1;
}

/* Content sits above image layers */
.lvc-footer__inner {
	position : relative;
	z-index  : 2;
	max-width: 1600px;
	margin   : 0 auto;
	padding  : 5rem var(--lvc-px) 2.5rem;
}

.lvc-footer__top {
	display              : grid;
	grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
	gap                  : 2.5rem 2rem;
	padding-bottom       : 4rem;
	border-bottom        : 1px solid var(--lvc-border);
}

.lvc-footer__logo {
	font-family: var(--lvc-fd);
	font-size: 1.5rem;
	font-weight: 400;
	color: var(--lvc-text);
	text-decoration: none;
	display: inline-block;
	margin-bottom: 1.25rem;
}

.lvc-footer__logo span {
	font-style: italic;
	color: var(--lvc-accent);
}

.lvc-footer__tagline {
	font-family: var(--lvc-fb);
	font-size: 0.85rem;
	line-height: 1.75;
	color: var(--lvc-muted);
	max-width: 280px;
	margin: 0 0 1.5rem;
}

.lvc-footer__social {
	display: flex;
	gap: 1rem;
}

.lvc-footer__social-link {
	width: 44px;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1px solid rgba(245,240,234,0.1);
	border-radius: 50%;
	color: var(--lvc-muted);
	transition: all 0.3s var(--lvc-ease);
	background: transparent;
}

.lvc-footer__social-link:hover {
	border-color: var(--lvc-accent);
	color: var(--lvc-accent);
	transform: scale(1.1);
	box-shadow: 0 0 16px rgba(194,129,140,0.2);
	background: rgba(194,129,140,0.08);
}

.lvc-footer__heading {
	font-family: var(--lvc-fb);
	font-size: 0.65rem;
	font-weight: 500;
	letter-spacing: 0.14em;
	text-transform: uppercase;
	color: var(--lvc-muted);
	margin: 0 0 1.25rem;
}

.lvc-footer__links {
	display: flex;
	flex-direction: column;
	gap: 0.75rem;
}

.lvc-footer__link {
	font-size: 0.8rem;
	color: var(--lvc-soft);
	text-decoration: none;
	transition: color 0.3s var(--lvc-ease), transform 0.3s var(--lvc-ease);
	font-family: var(--lvc-fb);
	display: inline-block;
}

.lvc-footer__link:hover {
	color: var(--lvc-accent);
	transform: translateX(4px);
}

/* Trust signals strip in brand column ────────────────────────────────────── */
.lvc-footer__trust {
	margin: 1.5rem 0 0;
	list-style: none;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 0.6rem;
}

.lvc-footer__trust-item {
	display: flex;
	align-items: flex-start;
	gap: 0.6rem;
	font-family: var(--lvc-fb);
	font-size: 0.78rem;
	color: var(--lvc-soft);
	line-height: 1.5;
}

.lvc-footer__trust-icon {
	flex-shrink: 0;
	width: 16px;
	height: 16px;
	border-radius: 50%;
	background: rgba(194,129,140,0.15);
	color: var(--lvc-accent);
	font-size: 0.65rem;
	font-weight: 700;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	margin-top: 1px;
}

/* Contact column ─────────────────────────────────────────────────────────── */
.lvc-footer__contact-group {
	display: flex;
	flex-direction: column;
	gap: 1.25rem;
}

.lvc-footer__contact-block {
	display: flex;
	flex-direction: column;
	gap: 0.3rem;
}

.lvc-footer__contact-label {
	font-family: var(--lvc-fb);
	font-size: 0.62rem;
	letter-spacing: 0.16em;
	text-transform: uppercase;
	color: var(--lvc-muted);
	font-weight: 500;
}

.lvc-footer__contact-value {
	font-family: var(--lvc-fb);
	font-size: 0.85rem;
	color: var(--lvc-soft);
	display: flex;
	align-items: center;
	gap: 0.5rem;
	overflow-wrap: anywhere;
}

.lvc-footer__contact-value a {
	color: var(--lvc-soft);
	text-decoration: none;
	transition: color 0.2s ease;
}

.lvc-footer__contact-value a:hover {
	color: var(--lvc-accent);
}

.lvc-footer__wa-inline {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 22px;
	height: 22px;
	background: #25D366;
	border-radius: 50%;
	color: #fff !important;
	flex-shrink: 0;
	transition: transform 0.2s ease;
}

.lvc-footer__wa-inline:hover {
	transform: scale(1.1);
	color: #fff !important;
}

/* Legal bar ──────────────────────────────────────────────────────────────── */
.lvc-footer__bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding-top: 2rem;
	flex-wrap: wrap;
	gap: 1rem;
}

.lvc-footer__copyright {
	font-size: 0.7rem;
	color: var(--lvc-muted);
	font-family: var(--lvc-fb);
	margin: 0;
}

.lvc-footer__legal {
	display: flex;
	gap: 1.5rem;
}

.lvc-footer__legal-link {
	font-size: 0.7rem;
	color: var(--lvc-muted);
	text-decoration: none;
	transition: color 0.3s var(--lvc-ease);
	font-family: var(--lvc-fb);
}

.lvc-footer__legal-link:hover {
	color: var(--lvc-text);
}

/* ── RESPONSIVE FOOTER ──────────────────────────────────────────────────── */
@media (max-width: 1024px) {
	.lvc-footer__top {
		grid-template-columns: 1fr 1fr;
		gap: 3rem;
	}
}

@media (max-width: 640px) {
	.lvc-footer__top {
		grid-template-columns: 1fr;
		gap: 2.5rem;
	}

	.lvc-footer__bottom {
		flex-direction: column;
		text-align: center;
	}

	.lvc-prefooter { padding: 3rem 1.25rem; }
	.lvc-prefooter__btns { flex-direction: column; }
	.lvc-prefooter__btn { width: 100%; }
}
</style>


<!-- ═══════════════════════════════════════════════════════════════════════════
	PRE-FOOTER CTA STRIP — final conversion moment before footer
	═══════════════════════════════════════════════════════════════════════════ -->
<?php if ( ! $lvc_hide_prefooter ) : ?>
<section class="lvc-prefooter" aria-label="Get in touch">
	<div class="lvc-prefooter__inner">
		<p class="lvc-prefooter__eyebrow">Ready to plan your stay?</p>
		<h2 class="lvc-prefooter__title">Let's find the right villa for your trip</h2>
		<p class="lvc-prefooter__sub">
			A team that knows every villa and beach in <?php echo esc_html( lvc_config( 'region' ) ); ?> personally. Personal villa matching, direct booking, no platform fees, fast response.
		</p>
		<div class="lvc-prefooter__btns">
			<a href="<?php echo esc_url( lvc_archive_url() ); ?>" class="lvc-prefooter__btn lvc-prefooter__btn--primary">
				Browse Villas <span aria-hidden="true">&#x2192;</span>
			</a>
			<?php if ( $lvc_whatsapp ) : ?>
			<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-prefooter__btn lvc-prefooter__btn--wa">
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
				WhatsApp Us
			</a>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════════════════
	FOOTER
	═══════════════════════════════════════════════════════════════════════════ -->
<footer class="lvc-footer">
	<div class="lvc-footer__bg" aria-hidden="true"></div>
	<div class="lvc-footer__bg-overlay" aria-hidden="true"></div>
	<div class="lvc-footer__inner">

		<div class="lvc-footer__top">

			<!-- Brand Column -->
			<div class="lvc-footer__brand">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lvc-footer__logo" aria-label="<?php echo esc_attr( lvc_brand() ); ?>">
					St Barts <span>Villa</span> Rentals
				</a>
				<?php if ( lvc_config( 'brand_tagline' ) ) : ?>
				<p class="lvc-footer__tagline"><?php echo esc_html( lvc_config( 'brand_tagline' ) ); ?></p>
				<?php endif; ?>

				<?php if ( $lvc_whatsapp || $lvc_email ) : ?>
				<div class="lvc-footer__social" aria-label="Direct contact shortcuts">
					<?php if ( $lvc_whatsapp ) : ?>
					<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-footer__social-link" aria-label="Chat on WhatsApp">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
					</a>
					<?php endif; ?>
					<?php if ( $lvc_email ) : ?>
					<a href="mailto:<?php echo esc_attr( $lvc_email ); ?>" class="lvc-footer__social-link" aria-label="Send us an email">
						<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect><path d="M3 7l9 6 9-6"></path></svg>
					</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<ul class="lvc-footer__trust">
					<li class="lvc-footer__trust-item">
						<span class="lvc-footer__trust-icon" aria-hidden="true">&#10003;</span>
						<span>Book direct, no platform fees</span>
					</li>
					<li class="lvc-footer__trust-item">
						<span class="lvc-footer__trust-icon" aria-hidden="true">&#10003;</span>
						<span>Personal villa matching</span>
					</li>
					<li class="lvc-footer__trust-item">
						<span class="lvc-footer__trust-icon" aria-hidden="true">&#10003;</span>
						<span>Concierge support to arrival</span>
					</li>
				</ul>
			</div>

			<?php if ( $lvc_footer_areas ) : ?>
			<!-- Areas Column — live taxonomy terms, never hardcoded slugs -->
			<div class="lvc-footer__column">
				<h4 class="lvc-footer__heading">Areas</h4>
				<div class="lvc-footer__links">
					<?php
					foreach ( $lvc_footer_areas as $lvc_area_term ) :
						$lvc_area_url = get_term_link( $lvc_area_term );
						if ( is_wp_error( $lvc_area_url ) ) {
							continue;
						}
						?>
					<a href="<?php echo esc_url( $lvc_area_url ); ?>" class="lvc-footer__link"><?php echo esc_html( $lvc_area_term->name ); ?> Villas</a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Explore Column — config-driven nav pages -->
			<div class="lvc-footer__column">
				<h4 class="lvc-footer__heading">Explore</h4>
				<div class="lvc-footer__links">
					<a href="<?php echo esc_url( lvc_archive_url() ); ?>" class="lvc-footer__link">All <?php echo esc_html( lvc_config( 'cpt_plural', 'Villas' ) ); ?></a>
					<a href="<?php echo esc_url( lvc_page_url( 'about' ) ); ?>" class="lvc-footer__link">About</a>
					<a href="<?php echo esc_url( lvc_page_url( 'how' ) ); ?>" class="lvc-footer__link">How It Works</a>
					<a href="<?php echo esc_url( lvc_page_url( 'owners' ) ); ?>" class="lvc-footer__link">For Owners</a>
					<a href="<?php echo esc_url( lvc_page_url( 'faq' ) ); ?>" class="lvc-footer__link">FAQs</a>
					<a href="<?php echo esc_url( lvc_page_url( 'events' ) ); ?>" class="lvc-footer__link">Villas for Events</a>
					<a href="<?php echo esc_url( lvc_page_url( 'magazine' ) ); ?>" class="lvc-footer__link">Magazine</a>
					<a href="<?php echo esc_url( lvc_page_url( 'contact' ) ); ?>" class="lvc-footer__link">Contact</a>
					<a href="<?php echo esc_url( lvc_page_url( 'request' ) ); ?>" class="lvc-footer__link">Request Availability</a>
				</div>
			</div>

			<!-- Get in Touch Column -->
			<div class="lvc-footer__column">
				<h4 class="lvc-footer__heading">Get in Touch</h4>
				<div class="lvc-footer__contact-group">

					<?php if ( $lvc_email ) : ?>
					<div class="lvc-footer__contact-block">
						<span class="lvc-footer__contact-label">Email</span>
						<span class="lvc-footer__contact-value">
							<a href="mailto:<?php echo esc_attr( $lvc_email ); ?>"><?php echo esc_html( $lvc_email ); ?></a>
						</span>
					</div>
					<?php endif; ?>

					<?php if ( $lvc_whatsapp ) : ?>
					<div class="lvc-footer__contact-block">
						<span class="lvc-footer__contact-label">Sales / Inquiries</span>
						<span class="lvc-footer__contact-value">
							<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp a specialist</a>
							<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-footer__wa-inline" aria-label="Message us on WhatsApp">
								<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
							</a>
						</span>
					</div>
					<?php endif; ?>

					<?php if ( $lvc_phone ) : ?>
					<div class="lvc-footer__contact-block">
						<span class="lvc-footer__contact-label">Phone</span>
						<span class="lvc-footer__contact-value">
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $lvc_phone ) ); ?>"><?php echo esc_html( $lvc_phone ); ?></a>
						</span>
					</div>
					<?php endif; ?>

					<div class="lvc-footer__contact-block">
						<span class="lvc-footer__contact-label">Based in</span>
						<span class="lvc-footer__contact-value"><?php echo esc_html( $lvc_region ); ?>, Caribbean</span>
					</div>

				</div>
			</div>

		</div>

		<div class="lvc-footer__bottom">
			<p class="lvc-footer__copyright">
				&copy; <?php echo esc_html( date( 'Y' ) ); ?> <?php echo esc_html( lvc_brand() ); ?>. All rights reserved.
			</p>
			<div class="lvc-footer__legal">
				<a href="<?php echo esc_url( $lvc_privacy_url ); ?>" class="lvc-footer__legal-link">Privacy Policy</a>
				<a href="<?php echo esc_url( $lvc_terms_url ); ?>" class="lvc-footer__legal-link">Terms &amp; Conditions</a>
			</div>
		</div>

	</div>
</footer>


<!-- ═══════════════════════════════════════════════════════════════════════════
	HEADER + MOBILE MENU + MEGA PANEL JAVASCRIPT
	(Ported from THV; end-of-body for performance. Bound to the lvc data
	attributes so the markup keeps the theme-core JS contract.)
	═══════════════════════════════════════════════════════════════════════════ -->
<script>
(function() {
	'use strict';

	// ─── Scroll-state header transition ─────────────────────────────────
	var header = document.querySelector('[data-lvc-header]');
	if (header) {
		var scrollThreshold = 40;
		var ticking = false;

		var updateHeaderState = function() {
			var scrolled = (window.pageYOffset || document.documentElement.scrollTop) > scrollThreshold;
			header.classList.toggle('lvc-header--scrolled', scrolled);
			ticking = false;
		};

		var onScroll = function() {
			if (!ticking) {
				window.requestAnimationFrame(updateHeaderState);
				ticking = true;
			}
		};

		window.addEventListener('scroll', onScroll, { passive: true });
		updateHeaderState(); // run once on load
	}

	// ─── Mobile drawer (lvc contract: data-lvc-drawer-toggle / data-lvc-drawer) ──
	var toggleBtn = document.querySelector('[data-lvc-drawer-toggle]');
	var mobileNav = document.querySelector('[data-lvc-drawer]');
	var closeBtn  = mobileNav ? mobileNav.querySelector('[data-lvc-drawer-close]') : null;
	var lastFocusedElement = null;

	var getFocusableElements = function(container) {
		if (!container) return [];
		return container.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
	};

	if (toggleBtn && mobileNav) {
		var openMenu = function() {
			lastFocusedElement = document.activeElement;
			mobileNav.classList.add('lvc-header__mobile-nav--open');
			mobileNav.setAttribute('aria-hidden', 'false');
			toggleBtn.setAttribute('aria-expanded', 'true');
			document.body.classList.add('lvc-menu-open');

			var focusables = getFocusableElements(mobileNav);
			if (focusables.length) {
				focusables[0].focus();
			}
		};

		var closeMenu = function() {
			mobileNav.classList.remove('lvc-header__mobile-nav--open');
			mobileNav.setAttribute('aria-hidden', 'true');
			toggleBtn.setAttribute('aria-expanded', 'false');
			document.body.classList.remove('lvc-menu-open');

			if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
				lastFocusedElement.focus();
			}
		};

		toggleBtn.addEventListener('click', function() {
			if (mobileNav.classList.contains('lvc-header__mobile-nav--open')) {
				closeMenu();
			} else {
				openMenu();
			}
		});
		if (closeBtn) closeBtn.addEventListener('click', closeMenu);

		// Close when a nav link is clicked
		var mobileLinks = mobileNav.querySelectorAll('a');
		mobileLinks.forEach(function(link) {
			link.addEventListener('click', closeMenu);
		});

		// Close on Escape key + focus trap on Tab
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && mobileNav.classList.contains('lvc-header__mobile-nav--open')) {
				closeMenu();
				return;
			}

			if (e.key === 'Tab' && mobileNav.classList.contains('lvc-header__mobile-nav--open')) {
				var focusables = getFocusableElements(mobileNav);
				if (!focusables.length) return;

				var first = focusables[0];
				var last = focusables[focusables.length - 1];

				if (e.shiftKey && document.activeElement === first) {
					e.preventDefault();
					last.focus();
				} else if (!e.shiftKey && document.activeElement === last) {
					e.preventDefault();
					first.focus();
				}
			}
		});

		mobileNav.addEventListener('click', function(e) {
			if (e.target === mobileNav) {
				closeMenu();
			}
		});
	}

	// ─── Mega panel toggle (lvc contract: data-lvc-mega-wrap / -toggle) ─────
	// CSS hover/focus-within also opens the panel; this drives aria-expanded
	// and gives touch users an explicit open/close.
	var megaWraps = document.querySelectorAll('[data-lvc-mega-wrap]');
	megaWraps.forEach(function(wrap) {
		var megaToggle = wrap.querySelector('[data-lvc-mega-toggle]');
		if (!megaToggle) return;

		var setMega = function(open) {
			wrap.classList.toggle('is-open', open);
			megaToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		};

		megaToggle.addEventListener('click', function(e) {
			e.preventDefault();
			setMega(!wrap.classList.contains('is-open'));
		});

		document.addEventListener('click', function(e) {
			if (wrap.classList.contains('is-open') && !wrap.contains(e.target)) {
				setMega(false);
			}
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && wrap.classList.contains('is-open')) {
				setMega(false);
				megaToggle.focus();
			}
		});
	});
})();
</script>


<?php // WhatsApp click tracking lives in assets/theme.js (lvcTrack, single measurement path) — do not duplicate it here. ?>
<?php wp_footer(); ?>
</body>
</html>
