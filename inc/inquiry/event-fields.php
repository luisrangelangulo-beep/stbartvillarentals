<?php
/**
 * Event enquiry fields.
 *
 * Rental villas are privately owned and events are approved by the owner, case
 * by case — there is no blanket policy to publish. These are the details owners
 * consistently ask for before giving an answer, so the events page collects
 * them up front and the first message to an owner is complete rather than the
 * start of three rounds of back-and-forth.
 *
 * Registered through the existing lvc_inquiry_extra_fields filter, so the
 * handler sanitises them, includes them in the notification email and persists
 * them with the inquiry record. No handler changes are required, and every
 * other form is unaffected because the fields are simply absent there.
 *
 * @package LuxuryVillaThemeCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'lvc_inquiry_extra_fields',
	static function ( $keys ) {
		return array_merge(
			(array) $keys,
			array(
				'event_type',
				'event_guests',
				'event_ages',
				'event_music',
				'event_vendors',
				'event_end_time',
			)
		);
	}
);
