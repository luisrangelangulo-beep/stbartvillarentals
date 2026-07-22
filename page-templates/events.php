<?php
/**
 * Template Name: Events
 * Luxury Villa Theme Core — weddings, celebrations and milestone events.
 *
 * Deliberately NOT a "browse event-approved properties" grid. Rental villas are
 * privately owned and events are approved by the owner, case by case, based on
 * the specifics of the event. Publishing an "events allowed" list would promise
 * something no owner has agreed to, and generates enquiries you then decline.
 *
 * This page sells the process instead: it states how approval actually works,
 * publishes the questions owners ask, and captures those answers so the first
 * message to an owner is complete. Copy is brand-agnostic — destination names
 * come from theme-config.php. Customise per site.
 *
 * Uses the shared inquiry form with event_fields enabled; the extra fields are
 * registered in inc/inquiry/event-fields.php.
 *
 * @package LuxuryVillaThemeCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_cpt    = lvc_config( 'cpt', 'villa' );
$lvc_noun   = lvc_config( 'cpt_singular', 'villa' );
$lvc_nouns  = lvc_config( 'cpt_plural', 'villas' );
$lvc_region = lvc_config( 'region', lvc_brand() );

/*
 * Capability figures, read live from the CPT — never hardcoded, so they cannot
 * drift as inventory changes. These describe what the collection can physically
 * hold; they are NOT a claim that any property will host an event.
 */
$lvc_ids   = get_posts(
	array(
		'post_type'      => $lvc_cpt,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$lvc_big   = 0;
$lvc_large = 0;
$lvc_max   = 0;
foreach ( $lvc_ids as $lvc_pid ) {
	$beds   = (int) lvc_field( 'bed_count', $lvc_pid );
	$guests = (int) lvc_field( 'guests_max', $lvc_pid );
	if ( $beds >= 6 ) {
		$lvc_big++;
	}
	if ( $guests >= 16 ) {
		$lvc_large++;
	}
	if ( $guests > $lvc_max ) {
		$lvc_max = $guests;
	}
}

$lvc_owner_questions = array(
	array( 'What kind of event is it?', 'A seated dinner for the people already staying reads very differently to an owner than a ceremony with outside guests arriving.' ),
	array( 'How many attend, and how many sleep over?', 'These are two different numbers and owners care about both. Twelve guests staying with sixty attending is a different request to twelve of each.' ),
	array( 'What is the age range of the group?', 'Owners ask this on nearly every enquiry. It is usually the single biggest factor in the answer.' ),
	array( 'Will there be amplified music?', 'Background music, a DJ and a live band are three different conversations. Many communities have noise limits and curfews.' ),
	array( 'Are outside vendors coming in?', 'Caterers, planners, florists and rentals mean access, deliveries and setup time the owner needs to approve.' ),
	array( 'What time does it end?', 'An end time is often what turns a maybe into a yes.' ),
);

$lvc_event_faqs = array(
	array(
		'q' => 'Can I host a wedding at one of your ' . $lvc_nouns . '?',
		'a' => 'Sometimes. Every property is privately owned and there is no blanket policy. Events are approved by the owner, case by case, based on the specifics of your event. Once we know the details we take the request to the owners whose homes fit and come back with who has said yes.',
	),
	array(
		'q' => 'Why can you not just show me a list that allows events?',
		'a' => 'Because that list would not be accurate. The same property can approve one celebration and decline another depending on the guest count, the group, the music and the end time. We would rather ask on your behalf than publish a promise the owner has not made.',
	),
	array(
		'q' => 'How many guests can a property hold?',
		'a' => sprintf(
			'For overnight stays, %d %s in our collection have six or more bedrooms and %d sleep sixteen or more, with the largest sleeping %d. Event attendance is a separate question and is always subject to the owner and the community.',
			$lvc_big,
			$lvc_nouns,
			$lvc_large,
			$lvc_max
		),
	),
	array(
		'q' => 'Is there an extra fee for an event?',
		'a' => 'Often yes. Owners may ask for an event fee, a larger security deposit, additional staffing or event insurance. This varies by property and by event, and we confirm it in writing before anything is committed.',
	),
	array(
		'q' => 'How far ahead should we ask?',
		'a' => 'As early as you can. Owner approval takes time, and peak dates are typically committed far in advance.',
	),
	array(
		'q' => 'Do you work with event planners?',
		'a' => 'Yes. We work alongside planners already operating in ' . $lvc_region . ' and are glad to coordinate directly with yours. If you do not have one, we can point you toward planners who know the area.',
	),
);

$lvc_faq_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map(
		static function ( $item ) {
			return array(
				'@type'          => 'Question',
				'name'           => $item['q'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $item['a'],
				),
			);
		},
		$lvc_event_faqs
	),
);

get_header();
?>
<script type="application/ld+json"><?php echo wp_json_encode( $lvc_faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

<main class="lvc-page lvc-events">

	<section class="lvc-hero lvc-hero--page">
		<div class="lvc-hero__inner">
			<p class="lvc-eyebrow">Weddings &amp; Celebrations</p>
			<h1 class="lvc-hero__title"><?php echo esc_html( ucfirst( $lvc_nouns ) ); ?> in <?php echo esc_html( $lvc_region ); ?> for <em>the occasion</em></h1>
			<p class="lvc-hero__sub">Weddings, bachelorette weekends and milestone birthdays. Every property here is privately owned, so events are approved by the owner, case by case. Tell us what you are planning and we will find out who says yes.</p>
			<div class="lvc-hero__cta">
				<a class="lvc-btn" href="#lvc-event-request">Start the Conversation</a>
				<a class="lvc-btn lvc-btn--ghost" href="<?php echo esc_url( lvc_archive_url() ); ?>">Browse <?php echo esc_html( ucfirst( $lvc_nouns ) ); ?></a>
			</div>
		</div>
	</section>

	<section class="lvc-section">
		<div class="lvc-sec-header">
			<p class="lvc-eyebrow">Straight Answer</p>
			<h2 class="lvc-sec-title">Nobody can promise you a property will <em>host your event</em></h2>
		</div>
		<div class="lvc-prose">
			<p>These are individually owned homes. There is no blanket rule that says events are allowed, and any company that publishes a list of &ldquo;event <?php echo esc_html( $lvc_nouns ); ?>&rdquo; is making a promise the owner has not made.</p>
			<p>What actually happens is this: an owner hears the details of your event and decides. The same property can say yes to a family birthday dinner and no to a party with sixty guests and a DJ. That is not evasiveness, it is how privately owned homes work.</p>
			<p>So we do it the other way around. You tell us what you are planning, in enough detail that an owner can actually answer, and we take it to the owners whose homes fit. Then we come back with who said yes and on what terms.</p>
		</div>
		<div class="lvc-grid lvc-grid--3">
			<article class="lvc-card"><h3><?php echo esc_html( (string) $lvc_big ); ?></h3><p><?php echo esc_html( ucfirst( $lvc_nouns ) ); ?> with six or more bedrooms</p></article>
			<article class="lvc-card"><h3><?php echo esc_html( (string) $lvc_large ); ?></h3><p>Sleeping sixteen or more</p></article>
			<article class="lvc-card"><h3><?php echo esc_html( (string) $lvc_max ); ?></h3><p>Largest sleeps</p></article>
		</div>
		<p class="lvc-form__micro">Overnight capacity across the collection. Event attendance is a separate question, decided per property.</p>
	</section>

	<section class="lvc-section lvc-section--alt">
		<div class="lvc-sec-header">
			<p class="lvc-eyebrow">Before You Ask</p>
			<h2 class="lvc-sec-title">What every owner <em>wants to know</em></h2>
			<p>These are the questions that come back on nearly every event enquiry. Answer them up front and we can give the owner a complete picture the first time, which is usually the difference between a fast yes and a slow maybe.</p>
		</div>
		<div class="lvc-grid lvc-grid--3">
			<?php foreach ( $lvc_owner_questions as $lvc_q ) : ?>
				<article class="lvc-card">
					<h3><?php echo esc_html( $lvc_q[0] ); ?></h3>
					<p><?php echo esc_html( $lvc_q[1] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="lvc-section" id="lvc-event-request">
		<div class="lvc-sec-header">
			<p class="lvc-eyebrow">Start Here</p>
			<h2 class="lvc-sec-title">Tell us about <em>your event</em></h2>
			<p>The more of this you can answer, the faster we get you a real answer from the owners. Owners may ask for an event fee, a larger deposit or event insurance — we confirm all of it in writing before anything is committed.</p>
		</div>
		<div class="lvc-contact__form">
			<?php
			get_template_part(
				'template-parts/inquiry-form',
				null,
				array(
					'property_name' => 'Events Page',
					'submit_label'  => 'Send Event Enquiry',
					'event_fields'  => true,
				)
			);
			?>
		</div>
	</section>

	<section class="lvc-section lvc-section--alt">
		<div class="lvc-sec-header">
			<p class="lvc-eyebrow">Common Questions</p>
			<h2 class="lvc-sec-title">Event <em>FAQs</em></h2>
		</div>
		<div class="lvc-faq">
			<?php foreach ( $lvc_event_faqs as $lvc_f ) : ?>
				<details class="lvc-faq__item">
					<summary class="lvc-faq__q"><?php echo esc_html( $lvc_f['q'] ); ?></summary>
					<p class="lvc-faq__a"><?php echo esc_html( $lvc_f['a'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</section>

</main>
<?php
get_footer();
