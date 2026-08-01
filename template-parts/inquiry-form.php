<?php
/**
 * Reusable inquiry form. Wired to inc/inquiry/ajax-handler.php.
 * Optional $args: 'property_name', 'inquiry_type' ('guest'|'owner'), 'submit_label'.
 * Brand-agnostic markup + .lvc-form classes. JS to submit lives in assets/brand
 * or a site script; this part just renders the spec-correct fields.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lvc_action   = (string) lvc_config( 'inquiry_action', 'lvc_inquiry' );
$lvc_type     = isset( $args['inquiry_type'] ) ? sanitize_key( $args['inquiry_type'] ) : 'guest';
$lvc_prop     = isset( $args['property_name'] ) ? (string) $args['property_name'] : '';
// Stable villa ID for analytics + the lead record: display names get renamed,
// post IDs don't.
$lvc_prop_id  = isset( $args['property_id'] ) ? absint( $args['property_id'] ) : ( is_singular( lvc_config( 'cpt', 'villa' ) ) ? (int) get_the_ID() : 0 );
$lvc_submit   = isset( $args['submit_label'] ) ? (string) $args['submit_label'] : 'Send Enquiry';
// Events pages opt in to the extra qualification fields owners ask for before
// approving a celebration. Registered for sanitising via the
// lvc_inquiry_extra_fields filter in inc/inquiry/event-fields.php.
$lvc_event    = ! empty( $args['event_fields'] );
// Owner inquiries don't book a stay: the server already exempts them from
// date/guest validation, but the browser's required attributes blocked the
// submit first (audit LCLV-018).
$lvc_req      = ( 'owner' === $lvc_type ) ? '' : ' required';
// Unique per-instance id prefix: two forms on one page produced duplicate
// ids and mis-targeted labels (audit LCLV-020).
$lvc_fid      = wp_unique_id( 'lvcf' );

// Validated finder context carries through so the guest never re-types it.
$lvc_trip       = function_exists( 'lvc_trip_context' ) ? lvc_trip_context() : array();
$lvc_pre_in     = isset( $lvc_trip['arrival'] ) ? $lvc_trip['arrival'] : '';
$lvc_pre_out    = isset( $lvc_trip['departure'] ) ? $lvc_trip['departure'] : '';
$lvc_pre_guests = isset( $lvc_trip['guests'] ) ? (int) $lvc_trip['guests'] : 0;
$lvc_today       = current_time( 'Y-m-d' );
$lvc_privacy_url = function_exists( 'get_privacy_policy_url' ) ? (string) get_privacy_policy_url() : '';
if ( '' === $lvc_privacy_url ) {
	$lvc_privacy_url = home_url( '/privacy-policy/' );
}
?>
<form class="lvc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-lvc-inquiry>
	<input type="hidden" name="action" value="<?php echo esc_attr( $lvc_action ); ?>">
	<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( $lvc_action ) ); ?>">
	<input type="hidden" name="inquiry_type" value="<?php echo esc_attr( $lvc_type ); ?>">
	<input type="hidden" name="property_name" value="<?php echo esc_attr( $lvc_prop ); ?>">
	<?php if ( $lvc_prop_id ) : ?><input type="hidden" name="property_id" value="<?php echo esc_attr( $lvc_prop_id ); ?>"><?php endif; ?>
	<input type="hidden" name="source_url" value="<?php echo esc_url( get_permalink() ?: home_url( '/' ) ); ?>">
	<input type="hidden" name="lvc_ts" value="<?php echo esc_attr( time() ); ?>">
	<?php // Honeypot â€” visually hidden; bots fill it, humans don't. ?>
	<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="lvc-form__hp">

	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="<?php echo esc_attr( $lvc_fid ); ?>-name">Full Name</label>
			<input id="<?php echo esc_attr( $lvc_fid ); ?>-name" type="text" name="name" required>
		</div>
		<div class="lvc-form__group">
			<label for="<?php echo esc_attr( $lvc_fid ); ?>-email">Email</label>
			<input id="<?php echo esc_attr( $lvc_fid ); ?>-email" type="email" name="email" required>
		</div>
	</div>
	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="<?php echo esc_attr( $lvc_fid ); ?>-checkin">Check-in</label>
			<input id="<?php echo esc_attr( $lvc_fid ); ?>-checkin" type="date" name="checkin" min="<?php echo esc_attr( $lvc_today ); ?>" value="<?php echo esc_attr( $lvc_pre_in ); ?>"<?php echo $lvc_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		</div>
		<div class="lvc-form__group">
			<label for="<?php echo esc_attr( $lvc_fid ); ?>-checkout">Check-out</label>
			<input id="<?php echo esc_attr( $lvc_fid ); ?>-checkout" type="date" name="checkout" min="<?php echo esc_attr( $lvc_pre_in ?: $lvc_today ); ?>" value="<?php echo esc_attr( $lvc_pre_out ); ?>"<?php echo $lvc_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		</div>
	</div>
	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="<?php echo esc_attr( $lvc_fid ); ?>-guests">Guests</label>
			<input id="<?php echo esc_attr( $lvc_fid ); ?>-guests" type="number" name="guests" min="1" max="100" value="<?php echo $lvc_pre_guests ? esc_attr( $lvc_pre_guests ) : ''; ?>"<?php echo $lvc_req; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		</div>
		<div class="lvc-form__group">
			<label for="<?php echo esc_attr( $lvc_fid ); ?>-phone">Phone / WhatsApp</label>
			<input id="<?php echo esc_attr( $lvc_fid ); ?>-phone" type="text" name="phone">
		</div>
	</div>
	<div class="lvc-form__group">
		<label for="<?php echo esc_attr( $lvc_fid ); ?>-budget">Approximate Budget (Optional)</label>
		<input id="<?php echo esc_attr( $lvc_fid ); ?>-budget" type="text" name="budget" placeholder="USD per night or total budget range">
	</div>
	<?php if ( $lvc_event ) : ?>
		<div class="lvc-form__row">
			<div class="lvc-form__group">
				<label for="<?php echo esc_attr( $lvc_fid ); ?>-event-type">Type of Event</label>
				<select id="<?php echo esc_attr( $lvc_fid ); ?>-event-type" name="event_type" required>
					<option value="">Select</option>
					<option>Wedding ceremony</option>
					<option>Wedding â€” welcome dinner or brunch</option>
					<option>Bachelorette party</option>
					<option>Bachelor party</option>
					<option>Birthday / milestone</option>
					<option>Anniversary</option>
					<option>Corporate / retreat</option>
					<option>Other celebration</option>
				</select>
			</div>
			<div class="lvc-form__group">
				<label for="<?php echo esc_attr( $lvc_fid ); ?>-event-guests">Total Attending the Event</label>
				<input id="<?php echo esc_attr( $lvc_fid ); ?>-event-guests" type="text" name="event_guests" placeholder="e.g. 60 â€” including guests not staying">
			</div>
		</div>
		<div class="lvc-form__row">
			<div class="lvc-form__group">
				<label for="<?php echo esc_attr( $lvc_fid ); ?>-event-ages">Age Range of the Group</label>
				<input id="<?php echo esc_attr( $lvc_fid ); ?>-event-ages" type="text" name="event_ages" placeholder="e.g. 28-35, or mixed with children">
			</div>
			<div class="lvc-form__group">
				<label for="<?php echo esc_attr( $lvc_fid ); ?>-event-music">Music</label>
				<select id="<?php echo esc_attr( $lvc_fid ); ?>-event-music" name="event_music">
					<option value="">Select</option>
					<option>Background music only</option>
					<option>DJ / amplified</option>
					<option>Live band</option>
					<option>Not sure yet</option>
				</select>
			</div>
		</div>
		<div class="lvc-form__row">
			<div class="lvc-form__group">
				<label for="<?php echo esc_attr( $lvc_fid ); ?>-event-vendors">Outside Vendors</label>
				<select id="<?php echo esc_attr( $lvc_fid ); ?>-event-vendors" name="event_vendors">
					<option value="">Select</option>
					<option>No outside vendors</option>
					<option>Caterer only</option>
					<option>Planner and vendors</option>
					<option>Not sure yet</option>
				</select>
			</div>
			<div class="lvc-form__group">
				<label for="<?php echo esc_attr( $lvc_fid ); ?>-event-end">Expected End Time</label>
				<input id="<?php echo esc_attr( $lvc_fid ); ?>-event-end" type="text" name="event_end_time" placeholder="e.g. 11pm">
			</div>
		</div>
	<?php endif; ?>

	<div class="lvc-form__group">
		<label for="<?php echo esc_attr( $lvc_fid ); ?>-message"><?php echo $lvc_event ? 'Anything else we should know?' : 'Message (Optional)'; ?></label>
		<textarea id="<?php echo esc_attr( $lvc_fid ); ?>-message" name="message"<?php echo $lvc_event ? ' placeholder="Ceremony on site or elsewhere, planner already engaged, villas you already like, accessibility needs."' : ''; ?>></textarea>
	</div>

	<p class="lvc-form__status" data-inquiry-status aria-live="polite"></p>
	<?php lvc_turnstile_field(); ?>
	<button type="submit" class="lvc-btn lvc-form__submit"><?php echo esc_html( $lvc_submit ); ?></button>
	<p class="lvc-form__micro lvc-form__privacy">We use your details only to respond to this request and help plan your stay. See our <a href="<?php echo esc_url( $lvc_privacy_url ); ?>">Privacy Policy</a>.</p>
	<p class="lvc-form__micro">We typically respond <?php echo esc_html( lvc_config( 'response_time', 'soon' ) ); ?>.</p>
</form>
<script>
(function () {
	var arrival = document.getElementById('<?php echo esc_js( $lvc_fid ); ?>-checkin');
	var departure = document.getElementById('<?php echo esc_js( $lvc_fid ); ?>-checkout');
	if (!arrival || !departure) return;
	var syncDates = function () {
		departure.min = arrival.value || arrival.min;
		if (departure.value && departure.value <= arrival.value) departure.value = '';
	};
	arrival.addEventListener('change', syncDates);
	syncDates();
})();
</script>


