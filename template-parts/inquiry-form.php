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
$lvc_submit   = isset( $args['submit_label'] ) ? (string) $args['submit_label'] : 'Send Enquiry';
// Events pages opt in to the extra qualification fields owners ask for before
// approving a celebration. Registered for sanitising in inc/inquiry/event-fields.php.
$lvc_event    = ! empty( $args['event_fields'] );
?>
<form class="lvc-form" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-lvc-inquiry>
	<input type="hidden" name="action" value="<?php echo esc_attr( $lvc_action ); ?>">
	<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( $lvc_action ) ); ?>">
	<input type="hidden" name="inquiry_type" value="<?php echo esc_attr( $lvc_type ); ?>">
	<input type="hidden" name="property_name" value="<?php echo esc_attr( $lvc_prop ); ?>">
	<input type="hidden" name="source_url" value="<?php echo esc_url( get_permalink() ?: home_url( '/' ) ); ?>">
	<input type="hidden" name="lvc_ts" value="<?php echo esc_attr( time() ); ?>">
	<?php // Honeypot — visually hidden; bots fill it, humans don't. ?>
	<input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" class="lvc-form__hp">

	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="lvc-name">Full Name</label>
			<input id="lvc-name" type="text" name="name" required>
		</div>
		<div class="lvc-form__group">
			<label for="lvc-email">Email</label>
			<input id="lvc-email" type="email" name="email" required>
		</div>
	</div>
	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="lvc-checkin">Check-in</label>
			<input id="lvc-checkin" type="date" name="checkin">
		</div>
		<div class="lvc-form__group">
			<label for="lvc-checkout">Check-out</label>
			<input id="lvc-checkout" type="date" name="checkout">
		</div>
	</div>
	<div class="lvc-form__row">
		<div class="lvc-form__group">
			<label for="lvc-guests">Guests</label>
			<input id="lvc-guests" type="number" name="guests" min="1" max="100">
		</div>
		<div class="lvc-form__group">
			<label for="lvc-phone">Phone / WhatsApp</label>
			<input id="lvc-phone" type="text" name="phone">
		</div>
	</div>
	<?php if ( $lvc_event ) : ?>
		<div class="lvc-form__row">
			<div class="lvc-form__group">
				<label for="lvc-event-type">Type of Event</label>
				<select id="lvc-event-type" name="event_type" required>
					<option value="">Select</option>
					<option>Wedding ceremony</option>
					<option>Wedding &mdash; welcome dinner or brunch</option>
					<option>Bachelorette party</option>
					<option>Bachelor party</option>
					<option>Birthday / milestone</option>
					<option>Anniversary</option>
					<option>Corporate / retreat</option>
					<option>Other celebration</option>
				</select>
			</div>
			<div class="lvc-form__group">
				<label for="lvc-event-guests">Total Attending the Event</label>
				<input id="lvc-event-guests" type="text" name="event_guests" placeholder="e.g. 60 &mdash; including guests not staying">
			</div>
		</div>
		<div class="lvc-form__row">
			<div class="lvc-form__group">
				<label for="lvc-event-ages">Age Range of the Group</label>
				<input id="lvc-event-ages" type="text" name="event_ages" placeholder="e.g. 28-35, or mixed with children">
			</div>
			<div class="lvc-form__group">
				<label for="lvc-event-music">Music</label>
				<select id="lvc-event-music" name="event_music">
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
				<label for="lvc-event-vendors">Outside Vendors</label>
				<select id="lvc-event-vendors" name="event_vendors">
					<option value="">Select</option>
					<option>No outside vendors</option>
					<option>Caterer only</option>
					<option>Planner and vendors</option>
					<option>Not sure yet</option>
				</select>
			</div>
			<div class="lvc-form__group">
				<label for="lvc-event-end">Expected End Time</label>
				<input id="lvc-event-end" type="text" name="event_end_time" placeholder="e.g. 11pm">
			</div>
		</div>
	<?php endif; ?>

	<div class="lvc-form__group">
		<label for="lvc-message"><?php echo $lvc_event ? 'Anything else we should know?' : 'Message'; ?></label>
		<textarea id="lvc-message" name="message"<?php echo $lvc_event ? '' : ' required'; ?>></textarea>
	</div>

	<p class="lvc-form__status" data-inquiry-status aria-live="polite"></p>
	<button type="submit" class="lvc-btn lvc-form__submit"><?php echo esc_html( $lvc_submit ); ?></button>
	<p class="lvc-form__micro">We typically respond <?php echo esc_html( lvc_config( 'response_time', 'soon' ) ); ?>.</p>
</form>
