<?php
/**
 * St Barts Villa Rentals — Header.
 *
 * Cloned from tulumholidayvillas' header.php per the clone-don't-rebuild rule:
 * same fixed transparent→scrolled header, same markup and CSS, recolored to
 * the salt-rose palette. Class prefix lvc- replaces thv-.
 *
 * Differences from the THV source, on purpose:
 * - WordPress plumbing kept from the lvc core header: wp_head/body_class/
 *   wp_body_open, the 'primary' menu location (hardcoded config-driven list
 *   as fallback), and the lvc JS contract on the drawer + mega menu
 *   (data-lvc-drawer-toggle / data-lvc-drawer / data-lvc-mega-wrap /
 *   data-lvc-mega-toggle). This repo ships no assets/theme.js, so the
 *   binding JS is THV's inline script (in footer.php) re-targeted at those
 *   data attributes.
 * - Nav links derive from this site's config (lvc_archive_url + lvc_page_url),
 *   not THV's Tulum menu; the villas dropdown is lvc_mega_menu() (live
 *   taxonomy terms) styled as THV's dropdown panel — no hardcoded slugs.
 * - Text logo only (no logo asset exists yet); brand_logo_svg from
 *   config still wins if one ever lands.
 * - No inline JSON-LD — inc/seo/schema.php owns all schema.
 *
 * @package StBartsVillaRentals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'lvc-body' ); ?>>
<?php wp_body_open(); ?>

<?php
// Primary nav from config — this site's real pages, not THV's Tulum menu. Doubles
// as the wp_nav_menu fallback and feeds the mobile drawer.
$lvc_pages     = (array) lvc_config( 'pages', array() );
$lvc_nav_items = array(
	array(
		'label' => 'Villas',
		'url'   => lvc_archive_url(),
		'slug'  => (string) lvc_config( 'cpt_archive_slug', 'luxury-villas' ),
	),
	array(
		'label' => 'About',
		'url'   => lvc_page_url( 'about' ),
		'slug'  => isset( $lvc_pages['about'] ) ? (string) $lvc_pages['about'] : 'about',
	),
	array(
		'label' => 'How It Works',
		'url'   => lvc_page_url( 'how' ),
		'slug'  => isset( $lvc_pages['how'] ) ? (string) $lvc_pages['how'] : 'how-it-works',
	),
	array(
		'label' => 'For Owners',
		'url'   => lvc_page_url( 'owners' ),
		'slug'  => isset( $lvc_pages['owners'] ) ? (string) $lvc_pages['owners'] : 'list-your-villa',
	),
	array(
		'label' => 'Magazine',
		'url'   => lvc_page_url( 'magazine' ),
		'slug'  => isset( $lvc_pages['magazine'] ) ? (string) $lvc_pages['magazine'] : 'magazine',
	),
	array(
		'label' => 'Contact',
		'url'   => lvc_page_url( 'contact' ),
		'slug'  => isset( $lvc_pages['contact'] ) ? (string) $lvc_pages['contact'] : 'contact',
	),
);

// Active state — THV v2.1 exact-segment match (no strpos false positives).
$lvc_current_post  = get_post();
$lvc_current_slug  = ( $lvc_current_post instanceof WP_Post ) ? $lvc_current_post->post_name : '';
$lvc_request_path  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path only, escaped on output.
$lvc_path_segments = explode( '/', trim( $lvc_request_path, '/' ) );
$lvc_first_segment = isset( $lvc_path_segments[0] ) ? $lvc_path_segments[0] : '';
$lvc_villas_ctx    = is_post_type_archive( 'villa' ) || is_singular( 'villa' ) || is_tax( array( 'area', 'bedrooms', 'beach_access', 'collection' ) );

$lvc_whatsapp    = lvc_whatsapp_url();
$lvc_request_url = lvc_page_url( 'request' );
$lvc_has_mega    = function_exists( 'lvc_mega_menu' ) && lvc_config( 'nav_mega', array() );
?>

<!-- ═══════════════════════════════════════════════════════════════════════════
	HEADER STYLES (cloned from THV; scoped to .lvc-header / .lvc- prefixes so
	nothing leaks into template CSS — no global resets ported)
	═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── SHARED DESIGN TOKENS — header + footer, salt-rose palette ───────────── */
:root {
	--lvc-bg        : #12100f;           /* Night-sea background */
	--lvc-bg2       : #1a1715;           /* Elevated surface */
	--lvc-bg3       : #1c1917;           /* Hover states, subtle elevation */
	--lvc-primary   : #2a1c20;           /* Deep lagoon */
	--lvc-primary-h : #3a262b;           /* Primary hover */
	--lvc-accent    : #c2818c;           /* Seaglass */
	--lvc-accent-h  : #d9a0a9;           /* Accent hover */
	--lvc-text      : #f5f0ea;           /* Coral-sand off-white */
	--lvc-soft      : #c3b8b0;           /* Secondary text */
	--lvc-muted     : #9c918b;           /* Tertiary text (dimmed) */
	--lvc-border    : rgba(245,240,234,0.06);
	--lvc-border-h  : rgba(194,129,140,0.25);
	--lvc-fd        : 'Gilda Display', Georgia, serif;
	--lvc-fb        : 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif;
	--lvc-px        : clamp(1.25rem, 5vw, 4rem);
	--lvc-ease      : cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

body { margin: 0; overflow-x: hidden; }
body.lvc-menu-open { overflow: hidden; }

.lvc-header *, .lvc-header *::before, .lvc-header *::after,
.lvc-header__mobile-nav *, .lvc-header__mobile-nav *::before, .lvc-header__mobile-nav *::after { box-sizing: border-box; }

.lvc-header a:focus, .lvc-header button:focus,
.lvc-header__mobile-nav a:focus, .lvc-header__mobile-nav button:focus { outline: 1px solid rgba(194,129,140,0.3); outline-offset: 2px; }
.lvc-header a:focus:not(:focus-visible), .lvc-header button:focus:not(:focus-visible),
.lvc-header__mobile-nav a:focus:not(:focus-visible), .lvc-header__mobile-nav button:focus:not(:focus-visible) { outline: none; }

/* ── HEADER ─────────────────────────────────────────────────────────────── */
.lvc-header {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	z-index: 1000;
	background: transparent;
	transition: all 0.3s ease;
	padding: 1.25rem 0;
}

.lvc-header--scrolled {
	background: rgba(18,16,15,0.92);
	backdrop-filter: blur(16px) saturate(1.2);
	-webkit-backdrop-filter: blur(16px) saturate(1.2);
	box-shadow: 0 2px 24px rgba(0,0,0,0.5), inset 0 -1px 0 rgba(245,240,234,0.04);
	padding: 0.75rem 0;
}

.lvc-header--scrolled .lvc-header__logo,
.lvc-header--scrolled .lvc-header__nav-link {
	color: var(--lvc-text);
}

.lvc-header--scrolled .lvc-header__nav-link:hover {
	color: var(--lvc-accent);
}

.lvc-header__inner {
	width: 100%;
	max-width: 1780px;            /* wider than 1400 so it breathes */
	margin: 0 auto;
	padding: 0 clamp(16px, 2.5vw, 42px);
	display: grid;
	grid-template-columns: auto 1fr auto; /* logo | nav | actions */
	align-items: center;
	gap: clamp(14px, 2vw, 34px);
}

/* Logo */
.lvc-header__logo {
	font-family: var(--lvc-fd);
	font-size: 1.5rem;
	font-weight: 400;
	color: var(--lvc-text);
	text-decoration: none;
	letter-spacing: 0.02em;
	transition: letter-spacing 0.4s var(--lvc-ease), color 0.3s ease;
	white-space: nowrap;
	display: inline-flex;
	align-items: center;
	min-height: 44px;
}
.lvc-header__logo:hover {
	letter-spacing: 0.06em;
	color: var(--lvc-text);
}

.lvc-header__logo span {
	font-style: italic;
	color: var(--lvc-accent);
}

/* Navigation */
.lvc-header__nav {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: clamp(14px, 1.4vw, 22px);
	min-width: 0;                  /* allows shrinking */
}
.lvc-header__nav-list {
	display: flex;
	align-items: center;
	gap: clamp(14px, 1.4vw, 22px);
	list-style: none;
	margin: 0;
	padding: 0;
}
.lvc-header__nav-link,
.lvc-header__nav-list a {
	font-size: 0.7rem;
	font-weight: 300;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	color: rgba(255,255,255,0.85);
	text-decoration: none;
	position: relative;
	padding: 0.5rem 0;
	transition: color 0.25s ease;
	font-family: var(--lvc-fb);
	white-space: nowrap;
}

.lvc-header__nav-link::after,
.lvc-header__nav-list a::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 50%;
	width: 0;
	height: 1px;
	background: var(--lvc-accent);
	transition: width 0.3s var(--lvc-ease), left 0.3s var(--lvc-ease);
}

.lvc-header__nav-link:hover,
.lvc-header__nav-link--active,
.lvc-header__nav-list a:hover,
.lvc-header__nav-list .current-menu-item > a {
	color: var(--lvc-accent);
}

.lvc-header__nav-link:hover::after,
.lvc-header__nav-link--active::after,
.lvc-header__nav-list a:hover::after,
.lvc-header__nav-list .current-menu-item > a::after {
	width: 100%;
	left: 0;
}

/* CTA Button */
.lvc-header__cta {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.75rem 1.5rem;
	background: var(--lvc-accent);
	color: #12100f;
	font-size: 0.65rem;
	font-weight: 500;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	text-decoration: none;
	border: 1px solid var(--lvc-accent);
	transition: all 0.3s var(--lvc-ease);
	font-family: var(--lvc-fb);
	white-space: nowrap;
}

.lvc-header__cta:hover {
	background: var(--lvc-accent-h);
	border-color: var(--lvc-accent-h);
	color: #12100f;
	transform: translateY(-2px);
	box-shadow: 0 6px 24px rgba(194,129,140,0.35);
}

.lvc-header__cta .arrow {
	transition: transform 0.3s var(--lvc-ease);
}

.lvc-header__cta:hover .arrow {
	transform: translateX(4px);
}

/* CTA Group — wraps both buttons in header */
.lvc-header__cta-group {
	display: flex;
	align-items: center;
	gap: 0.6rem;
}

/* WhatsApp / Talk to Specialist Button (WhatsApp's own green — keep) */
.lvc-header__wa {
	display: inline-flex;
	align-items: center;
	gap: 0.45rem;
	padding: 0.75rem 1.25rem;
	background: #25D366;
	color: #fff;
	font-size: 0.65rem;
	font-weight: 300;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	text-decoration: none;
	border: 1px solid #25D366;
	transition: all 0.25s ease;
	font-family: var(--lvc-fb);
	white-space: nowrap;
}

.lvc-header__wa:hover {
	background: #1ebd5b;
	border-color: #1ebd5b;
	color: #fff;
	box-shadow: 0 4px 20px rgba(37,211,102,0.25);
	transform: translateY(-1px);
}

.lvc-header__wa-icon {
	flex-shrink: 0;
	display: inline-block;
	line-height: 0;
}

/* Mobile Menu Toggle */
.lvc-header__menu-toggle {
	display: none;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 5px;
	width: 44px;
	height: 44px;
	padding: 0.5rem;
	background: none;
	border: none;
	cursor: pointer;
}

.lvc-header__menu-toggle span {
	display: block;
	width: 24px;
	height: 2px;
	background: #fff;
	transition: all 0.3s ease;
}

.lvc-header__menu-toggle[aria-expanded="true"] span:nth-child(1) {
	transform: translateY(7px) rotate(45deg);
}

.lvc-header__menu-toggle[aria-expanded="true"] span:nth-child(2) {
	opacity: 0;
}

.lvc-header__menu-toggle[aria-expanded="true"] span:nth-child(3) {
	transform: translateY(-7px) rotate(-45deg);
}

.lvc-header--scrolled .lvc-header__menu-toggle span {
	background: var(--lvc-text);
}

/* ════ Taxonomy mega dropdown (desktop) — lvc_mega_menu('panel') styled as
	THV's "All Villas" dropdown. The is-open class is toggled by the inline JS
	in footer.php via data-lvc-mega-toggle; hover/focus-within keep it usable
	with no JS at all. ════ */
.lvc-header__mega-wrap { position: relative; display: flex; align-items: center; }
.lvc-header__mega-toggle {
	background: none;
	border: none;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 0.35rem;
}
.lvc-header__mega-caret { font-size: 0.55rem; line-height: 1; opacity: 0.7; }
.lvc-header__mega-wrap .lvc-mega {
	position: absolute;
	top: 100%;
	left: 50%;
	transform: translateX(-50%) translateY(8px);
	display: flex;
	flex-wrap: wrap;
	gap: 1.5rem 2.5rem;
	background: var(--lvc-bg2);
	border: 1px solid var(--lvc-border);
	border-radius: 6px;
	padding: 1.5rem 1.75rem;
	min-width: 460px;
	max-width: min(92vw, 720px);
	opacity: 0;
	visibility: hidden;
	transition: opacity .2s ease, transform .2s ease;
	z-index: 200;
}
.lvc-header__mega-wrap.is-open .lvc-mega,
.lvc-header__mega-wrap:hover .lvc-mega,
.lvc-header__mega-wrap:focus-within .lvc-mega {
	opacity: 1;
	visibility: visible;
	transform: translateX(-50%) translateY(0);
}
.lvc-header__mega-wrap .lvc-mega__col { display: flex; flex-direction: column; min-width: 150px; }
.lvc-header__mega-wrap .lvc-mega__label { font-size: .68rem; letter-spacing: .15em; text-transform: uppercase; color: var(--lvc-accent); margin: 0 0 .6rem; font-family: var(--lvc-fb); font-weight: 500; }
.lvc-header__mega-wrap .lvc-mega__list { display: flex; flex-direction: column; gap: .6rem; }
.lvc-header__mega-wrap .lvc-mega__item { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; color: var(--lvc-text); text-decoration: none; font-size: .92rem; font-family: var(--lvc-fb); transition: color .2s ease; }
.lvc-header__mega-wrap .lvc-mega__item:hover { color: var(--lvc-accent); }
.lvc-header__mega-wrap .lvc-mega__count { font-size: .68rem; color: var(--lvc-muted); }
.lvc-header__mega-wrap .lvc-mega__col--compact .lvc-mega__list { flex-direction: row; flex-wrap: wrap; gap: .5rem; }
.lvc-header__mega-wrap .lvc-mega__chip { display: inline-flex; align-items: center; padding: .35rem .6rem; border: 1px solid var(--lvc-border-h); border-radius: 4px; color: var(--lvc-text); text-decoration: none; font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; font-family: var(--lvc-fb); transition: all .2s ease; }
.lvc-header__mega-wrap .lvc-mega__chip:hover { border-color: var(--lvc-accent); color: var(--lvc-accent); }
.lvc-header__mega-wrap .lvc-mega__all { flex-basis: 100%; margin-top: .25rem; padding-top: .85rem; border-top: 1px solid var(--lvc-border); color: var(--lvc-accent); text-decoration: none; font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; font-family: var(--lvc-fb); font-weight: 500; }
.lvc-header__mega-wrap .lvc-mega__all:hover { color: var(--lvc-accent-h); }

/* Mobile Navigation */
.lvc-header__mobile-nav {
	display: none;
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(18,16,15,0.96);
	backdrop-filter: blur(20px) saturate(1.3);
	-webkit-backdrop-filter: blur(20px) saturate(1.3);
	z-index: 999;
	padding: 6rem 2rem 2rem;
	flex-direction: column;
	gap: 1.5rem;
	transform: translateX(100%);
	visibility: hidden;
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
	transition: transform 0.4s var(--lvc-ease), visibility 0.4s var(--lvc-ease);
}

.lvc-header__mobile-nav--open {
	transform: translateX(0);
	visibility: visible;
}

.lvc-header__mobile-nav-link,
.lvc-header__mobile-nav-list a {
	font-family: var(--lvc-fd);
	font-size: 1.75rem;
	font-weight: 400;
	color: var(--lvc-text);
	text-decoration: none;
	padding: 0.75rem 0;
	border-bottom: 1px solid var(--lvc-border);
	transition: color 0.3s var(--lvc-ease), transform 0.3s var(--lvc-ease), opacity 0.3s ease;
	display: block;
}
.lvc-header__mobile-nav-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0; }

.lvc-header__mobile-nav-link {
	opacity: 0;
	transform: translateX(20px);
}

.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link {
	opacity: 1;
	transform: translateX(0);
}
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(1) { transition-delay: 0.05s; }
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(2) { transition-delay: 0.1s; }
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(3) { transition-delay: 0.15s; }
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(4) { transition-delay: 0.2s; }
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(5) { transition-delay: 0.25s; }
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(6) { transition-delay: 0.3s; }
.lvc-header__mobile-nav--open .lvc-header__mobile-nav-link:nth-child(7) { transition-delay: 0.35s; }

.lvc-header__mobile-nav-link:hover,
.lvc-header__mobile-nav-link--active,
.lvc-header__mobile-nav-list a:hover {
	color: var(--lvc-accent);
	transform: translateX(6px);
}

.lvc-header__mobile-close {
	position: absolute;
	top: 1.5rem;
	right: 1.5rem;
	width: 32px;
	height: 32px;
	background: none;
	border: none;
	cursor: pointer;
}

.lvc-header__mobile-close::before,
.lvc-header__mobile-close::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	width: 24px;
	height: 2px;
	background: var(--lvc-text);
}

.lvc-header__mobile-close::before {
	transform: translate(-50%, -50%) rotate(45deg);
}

.lvc-header__mobile-close::after {
	transform: translate(-50%, -50%) rotate(-45deg);
}

.lvc-header__mobile-cta {
	margin-top: auto;
	padding: 1rem 2rem;
	background: var(--lvc-accent);
	color: #12100f;
	text-align: center;
	font-size: 0.75rem;
	font-weight: 300;
	letter-spacing: 0.1em;
	text-transform: uppercase;
	text-decoration: none;
	font-family: var(--lvc-fb);
	transition: background 0.2s ease;
}

.lvc-header__mobile-cta:hover {
	background: var(--lvc-accent-h);
	color: #12100f;
}

.lvc-header__mobile-cta--wa {
	margin-top: 0.75rem;
	background: #25D366;
	color: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 0.55rem;
}

.lvc-header__mobile-cta--wa:hover {
	background: #1ebd5b;
	color: #fff;
}

/* ════ Mobile taxonomy list — lvc_mega_menu('drawer') styled as THV's
	mobile subnav (touch — no hover). ════ */
.lvc-header__mobile-nav .lvc-mega {
	display: flex;
	flex-direction: column;
	gap: .5rem;
	margin: .25rem 0 .75rem 1rem;
	padding-left: 1rem;
	border-left: 1px solid var(--lvc-border);
}
.lvc-header__mobile-nav .lvc-mega__col { display: flex; flex-direction: column; gap: .5rem; }
.lvc-header__mobile-nav .lvc-mega__label { font-size: .62rem; letter-spacing: .15em; text-transform: uppercase; color: var(--lvc-accent); margin: .6rem 0 0; font-family: var(--lvc-fb); font-weight: 500; }
.lvc-header__mobile-nav .lvc-mega__list { display: flex; flex-direction: column; gap: .5rem; }
.lvc-header__mobile-nav .lvc-mega__item { color: var(--lvc-soft); text-decoration: none; font-size: .95rem; font-family: var(--lvc-fb); display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; }
.lvc-header__mobile-nav .lvc-mega__item:hover { color: var(--lvc-accent); }
.lvc-header__mobile-nav .lvc-mega__count { font-size: .68rem; color: var(--lvc-muted); }
.lvc-header__mobile-nav .lvc-mega__col--compact .lvc-mega__list { flex-direction: row; flex-wrap: wrap; gap: .5rem; }
.lvc-header__mobile-nav .lvc-mega__chip { display: inline-flex; align-items: center; padding: .35rem .6rem; border: 1px solid var(--lvc-border-h); border-radius: 4px; color: var(--lvc-soft); text-decoration: none; font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; font-family: var(--lvc-fb); }
.lvc-header__mobile-nav .lvc-mega__chip:hover { border-color: var(--lvc-accent); color: var(--lvc-accent); }
.lvc-header__mobile-nav .lvc-mega__all { color: var(--lvc-accent); text-decoration: none; font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; font-family: var(--lvc-fb); font-weight: 500; margin-top: .35rem; }

/* ── RESPONSIVE HEADER ──────────────────────────────────────────────────── */
@media (max-width: 1024px) {
	.lvc-header__nav {
		display: none;
	}

	.lvc-header__menu-toggle {
		display: flex;
	}

	.lvc-header__mobile-nav {
		display: flex;
	}

	.lvc-header__cta-group {
		display: none;
	}
}

@media (max-width: 768px) {
	.lvc-header {
		padding: 1rem 0;
	}

	.lvc-header__logo {
		font-size: 1.25rem;
	}
}
</style>


<!-- ═══════════════════════════════════════════════════════════════════════════
	HEADER
	═══════════════════════════════════════════════════════════════════════════ -->
<header class="lvc-header" id="lvc-header" data-lvc-header>
	<div class="lvc-header__inner">

		<!-- Logo — styled text brand (no logo asset); config SVG wins if set -->
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lvc-header__logo" aria-label="<?php echo esc_attr( lvc_brand() ); ?>">
			<?php
			$lvc_logo_svg = (string) lvc_config( 'brand_logo_svg', '' );
			if ( $lvc_logo_svg ) {
				echo $lvc_logo_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG from config.
			} else {
				echo 'St Barts <span>Villa</span> Rentals';
			}
			?>
		</a>

		<!-- Desktop Navigation -->
		<nav class="lvc-header__nav" aria-label="Main navigation">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'lvc-header__nav-list',
					'fallback_cb'    => false,
					'depth'          => 1,
				) );
			} else {
				foreach ( $lvc_nav_items as $lvc_item ) {
					$lvc_is_active = ( $lvc_first_segment === $lvc_item['slug'] ) || ( $lvc_current_slug === $lvc_item['slug'] ) || ( 'Villas' === $lvc_item['label'] && $lvc_villas_ctx );
					echo '<a href="' . esc_url( $lvc_item['url'] ) . '" class="lvc-header__nav-link' . ( $lvc_is_active ? ' lvc-header__nav-link--active' : '' ) . '">' . esc_html( $lvc_item['label'] ) . '</a>';
				}
			}

			// Taxonomy browse panel — button-toggled (keyboard/touch reachable),
			// data attributes are the lvc JS contract bound in footer.php.
			if ( $lvc_has_mega ) :
				?>
				<div class="lvc-header__mega-wrap" data-lvc-mega-wrap>
					<button class="lvc-header__nav-link lvc-header__mega-toggle" type="button" aria-expanded="false" aria-haspopup="true" data-lvc-mega-toggle>
						<?php echo esc_html( lvc_config( 'nav_mega_label', 'Browse' ) ); ?>
						<span class="lvc-header__mega-caret" aria-hidden="true">&#9662;</span>
					</button>
					<?php lvc_mega_menu( 'panel' ); ?>
				</div>
				<?php
			endif;
			?>
		</nav>

		<!-- CTA Group: Request Availability (accent) + Talk to Specialist (WhatsApp green) -->
		<div class="lvc-header__cta-group">
			<a href="<?php echo esc_url( $lvc_request_url ); ?>" class="lvc-header__cta">
				Request Availability <span class="arrow">&#x2192;</span>
			</a>
			<?php if ( $lvc_whatsapp ) : ?>
			<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-header__wa" aria-label="Talk to a Specialist via WhatsApp">
				<svg class="lvc-header__wa-icon" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
				Talk to a Specialist
			</a>
			<?php endif; ?>
		</div>

		<!-- Mobile Menu Toggle (data-lvc-drawer-toggle = lvc JS contract) -->
		<button class="lvc-header__menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="lvc-mobile-nav" data-lvc-drawer-toggle>
			<span></span>
			<span></span>
			<span></span>
		</button>

	</div>
</header>

<!-- Mobile Navigation (data-lvc-drawer = lvc JS contract) -->
<div class="lvc-header__mobile-nav" id="lvc-mobile-nav" aria-hidden="true" data-lvc-drawer>
	<button class="lvc-header__mobile-close" type="button" aria-label="Close menu" data-lvc-drawer-close></button>

	<?php
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'lvc-header__mobile-nav-list',
			'fallback_cb'    => false,
			'depth'          => 1,
		) );
	} else {
		foreach ( $lvc_nav_items as $lvc_item ) {
			$lvc_is_active = ( $lvc_first_segment === $lvc_item['slug'] ) || ( $lvc_current_slug === $lvc_item['slug'] ) || ( 'Villas' === $lvc_item['label'] && $lvc_villas_ctx );
			echo '<a href="' . esc_url( $lvc_item['url'] ) . '" class="lvc-header__mobile-nav-link' . ( $lvc_is_active ? ' lvc-header__mobile-nav-link--active' : '' ) . '">' . esc_html( $lvc_item['label'] ) . '</a>';
		}
	}

	// Same taxonomy links on mobile — orphaned archives are orphaned on every
	// breakpoint, so the drawer carries the panel rather than dropping it.
	if ( function_exists( 'lvc_mega_menu' ) ) {
		lvc_mega_menu( 'drawer' );
	}
	?>

	<a href="<?php echo esc_url( $lvc_request_url ); ?>" class="lvc-header__mobile-cta">
		Request Availability
	</a>
	<?php if ( $lvc_whatsapp ) : ?>
	<a href="<?php echo esc_url( $lvc_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="lvc-header__mobile-cta lvc-header__mobile-cta--wa">
		<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
		Talk to a Specialist
	</a>
	<?php endif; ?>
</div>
