<?php
/**
 * Conservative response security headers for public front-end requests.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'send_headers', static function () {
	if ( headers_sent() || is_admin() ) {
		return;
	}

	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
}, 20 );
