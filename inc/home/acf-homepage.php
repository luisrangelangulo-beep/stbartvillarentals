<?php
/**
 * Luxury Villa Theme Core — homepage ACF fields.
 * ─────────────────────────────────────────────────────────────────────────
 * front-page.php reads home_hero_title / home_hero_subtitle / home_hero_image_url
 * off the static front page. Without this group those fields are unregistered,
 * so the hero has no editable image and falls back to the brand name/tagline.
 *
 * Located on the static front page (page_type == front_page), so it appears on
 * whichever page the site sets as its homepage. No-op without ACF.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'    => 'group_lvc_homepage',
		'title'  => 'Homepage',
		'fields' => array(
			array( 'key' => 'field_lvc_home_hero_title', 'label' => 'Hero Title', 'name' => 'home_hero_title', 'type' => 'text', 'instructions' => 'Big H1 on the homepage hero. Falls back to the brand name.' ),
			array( 'key' => 'field_lvc_home_hero_subtitle', 'label' => 'Hero Subtitle', 'name' => 'home_hero_subtitle', 'type' => 'text', 'instructions' => 'One line under the H1. Falls back to the brand tagline.' ),
			array( 'key' => 'field_lvc_home_hero_image', 'label' => 'Hero Image URL', 'name' => 'home_hero_image_url', 'type' => 'url', 'instructions' => 'Full-bleed hero background (Cloudflare R2). Leave blank for the flat dark hero.' ),
		),
		'location' => array(
			array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ) ),
		),
		'position' => 'acf_after_title',
		'active'   => true,
	) );
} );
