<?php
/**
 * Ensure required core routes exist after a code-only deployment.
 *
 * Ported from anguilla-repo 2026-08-01. Two deliberate changes from that source:
 *
 * 1. Slugs and titles are brand-neutral or built from lvc_config(), not
 *    hardcoded to one island. Anguilla shipped 'anguilla-villa-request' and
 *    'anguilla-villas-for-events' as literals; copying those verbatim is how a
 *    clone ends up serving another brand's URLs.
 * 2. The legal boilerplate reads brand_name and support_email from config
 *    instead of a hardcoded mailbox, so it cannot silently publish a contact
 *    address belonging to a different site.
 *
 * The documents remain ordinary WordPress pages and can be edited normally.
 * Existing pages are NEVER overwritten — only a missing template assignment or
 * a draft status is corrected.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', static function () {
	if ( wp_installing() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	$brand   = (string) lvc_config( 'brand_name', get_bloginfo( 'name' ) );
	$region  = (string) lvc_config( 'region', '' );
	$email   = (string) lvc_config( 'support_email', get_option( 'admin_email' ) );
	$mailto  = '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	$request = 'st-barts-villa-request';
	$events  = 'st-barts-villas-for-events';

	$pages = array(
		// Front page and posts page. Neither needs a template — front-page.php
		// and home.php win the hierarchy on their own — but both must EXIST as
		// pages for the routing below to work, and the front page additionally
		// hosts the Homepage ACF group. With page_on_front unset, that group has
		// nowhere to live and the homepage hero can never be set.
		'home' => array(
			'title' => 'Home',
		),
		'magazine' => array(
			'title' => 'Magazine',
		),
		'about-us' => array(
			'title'    => 'About Us',
			'template' => 'page-templates/about.php',
		),
		'contact' => array(
			'title'    => 'Contact',
			'template' => 'page-templates/contact.php',
		),
		'how-it-works' => array(
			'title'    => 'How It Works',
			'template' => 'page-templates/how-it-works.php',
		),
		'list-your-villa' => array(
			'title'    => 'List Your Villa',
			'template' => 'page-templates/list-your-villa.php',
		),
		$request => array(
			'title'    => trim( 'Request a ' . $region . ' Villa' ),
			'template' => 'page-templates/villa-request.php',
		),
		'faq' => array(
			'title'    => 'Frequently Asked Questions',
			'template' => 'page-templates/faq.php',
		),
		$events => array(
			'title'    => trim( $region . ' Villas for Events' ),
			'template' => 'page-templates/events.php',
		),
		'privacy-policy' => array(
			'title'    => 'Privacy Policy',
			'template' => 'page-templates/legal.php',
			'content'  => '<p>We respect your privacy and collect only the information needed to answer inquiries, recommend villas, and provide booking and concierge support.</p><h2>Information we collect</h2><p>When you contact us, we may receive your name, email address, telephone number, travel dates, group size, preferences, budget, and any details you include in your message. The website may also collect limited technical and analytics information such as browser type, device type, pages visited, and referral source.</p><h2>How we use information</h2><p>We use this information to respond to requests, match guests with suitable villas, coordinate booking support, improve the website, prevent misuse, and understand marketing performance. We do not sell personal information.</p><h2>Service providers and retention</h2><p>Information may be processed by trusted hosting, email, analytics, and booking-support providers when necessary to operate the service. We retain inquiry information only as long as reasonably necessary for those purposes and applicable legal obligations.</p><h2>Your choices</h2><p>You may ask to access, correct, or delete personal information by contacting ' . $mailto . '. Browser settings and consent tools may be used to manage cookies where available.</p><h2>Updates</h2><p>We may update this policy as the website or legal requirements change. The date shown on this page reflects the latest revision.</p>',
		),
		'terms-and-conditions' => array(
			'title'    => 'Terms and Conditions',
			'template' => 'page-templates/legal.php',
			'content'  => '<p>These terms govern use of the ' . esc_html( $brand ) . ' website and its inquiry and villa-matching services.</p><h2>Website information</h2><p>Villa descriptions, amenities, availability, rates, taxes, fees, and policies may change. Website content is provided for general planning and does not itself create a reservation or guarantee availability.</p><h2>Bookings</h2><p>A booking becomes binding only when the applicable rental agreement has been accepted and the required payment has been received by the contracting party. The rental agreement, payment schedule, cancellation policy, security deposit, occupancy limits, and property-specific rules control if they differ from information on this website.</p><h2>Guest responsibilities</h2><p>Guests must provide accurate information, comply with occupancy and property rules, and use villas responsibly. Events, commercial activity, pets, and special requests require written approval where applicable.</p><h2>Third-party services</h2><p>Transportation, chefs, activities, and other concierge services may be supplied by independent providers under their own terms. We are not responsible for third-party acts, omissions, schedule changes, or conditions outside our reasonable control.</p><h2>Intellectual property</h2><p>Website text, branding, design, and imagery may not be copied or commercially reused without permission from the applicable rights holder.</p><h2>Contact</h2><p>Questions about these terms may be sent to ' . $mailto . '.</p>',
		),
	);

	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			// 'home' and 'magazine' carry no template on purpose — front-page.php
			// and home.php win the hierarchy without one.
			if ( isset( $page['template'] ) && get_page_template_slug( $existing->ID ) !== $page['template'] ) {
				update_post_meta( $existing->ID, '_wp_page_template', $page['template'] );
			}
			if ( in_array( $existing->post_status, array( 'draft', 'pending' ), true ) ) {
				wp_update_post( array( 'ID' => $existing->ID, 'post_status' => 'publish' ) );
			}
			if ( 'privacy-policy' === $slug && ! get_option( 'wp_page_for_privacy_policy' ) ) {
				update_option( 'wp_page_for_privacy_policy', (int) $existing->ID );
			}
			continue;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => isset( $page['content'] ) ? $page['content'] : '',
				'meta_input'   => isset( $page['template'] )
					? array( '_wp_page_template' => $page['template'] )
					: array(),
			),
			true
		);

		if ( ! is_wp_error( $page_id ) && 'privacy-policy' === $slug && ! get_option( 'wp_page_for_privacy_policy' ) ) {
			update_option( 'wp_page_for_privacy_policy', (int) $page_id );
		}
	}

	/* ── Front-page / posts-page routing ──────────────────────────────────
	 * Only runs while the site is still on the default "your latest posts"
	 * setting. Once a front page is chosen — here or by hand — this never
	 * touches it again, so an editor's choice is never overwritten.
	 *
	 * Both assignments matter beyond tidiness:
	 *  - page_on_front is where the Homepage ACF group lives. Unset, there is
	 *    nowhere to store the homepage hero and front-page.php renders without
	 *    one, which reads as a design bug rather than a missing setting.
	 *  - page_for_posts is what gives home.php (the magazine index) a URL at
	 *    all. Without it the template is dead code: front-page.php wins the
	 *    front page, and nothing else routes to home.php.
	 */
	if ( 'posts' === get_option( 'show_on_front' ) && ! (int) get_option( 'page_on_front' ) ) {
		$front = get_page_by_path( 'home', OBJECT, 'page' );
		$blog  = get_page_by_path( 'magazine', OBJECT, 'page' );

		if ( $front instanceof WP_Post && $blog instanceof WP_Post ) {
			update_option( 'page_on_front', (int) $front->ID );
			update_option( 'page_for_posts', (int) $blog->ID );
			update_option( 'show_on_front', 'page' );
		}
	}
}, 30 );
