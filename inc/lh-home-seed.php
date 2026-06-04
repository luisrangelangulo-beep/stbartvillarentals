<?php
/**
 * Lighthouse homepage one-time content seeder.
 *
 * Populates repeater rows only if empty and runs once.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'acf/init', 'lh_seed_home_repeaters' );
function lh_seed_home_repeaters() {
    if ( ! is_admin() || ! function_exists( 'update_field' ) ) {
        return;
    }

    $force_seed = isset( $_GET['lh_seed'] ) && '1' === (string) $_GET['lh_seed']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ( get_option( 'lh_home_seeded' ) && ! $force_seed ) {
        return;
    }

    $front_id = (int) get_option( 'page_on_front' );
    if ( ! $front_id ) {
        return;
    }

    // Ensure the ACF field group is already available before attempting writes.
    if ( ! function_exists( 'get_field_object' ) || ! get_field_object( 'hero_title', $front_id ) ) {
        return;
    }

    $defaults = function_exists( 'lh_home_default_content' ) ? lh_home_default_content() : [];

    // Repeater fields seeded into the database. Copy lives in inc/lh-home-content.php
    // so the seeder and the front-page render fallbacks never drift apart.
    $repeaters = [ 'about_bullets', 'conditions', 'how_steps', 'trust_cards', 'founder_bullets', 'resources' ];

    $did_seed = false;

    foreach ( $repeaters as $repeater ) {
        if ( empty( $defaults[ $repeater ] ) ) {
            continue;
        }

        if ( $force_seed || ! get_field( $repeater, $front_id ) ) {
            update_field( $repeater, $defaults[ $repeater ], $front_id );
            $did_seed = true;
        }
    }

    if ( $did_seed || $force_seed ) {
        update_option( 'lh_home_seeded', 1 );
    }
}
