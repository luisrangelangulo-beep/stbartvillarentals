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

    $did_seed = false;

    if ( ! get_field( 'about_bullets', $front_id ) ) {
        update_field( 'about_bullets', [
            [ 'item' => 'Christian mental health treatment' ],
            [ 'item' => 'Addiction support' ],
            [ 'item' => 'Sex addiction recovery' ],
            [ 'item' => 'Schizophrenia resources' ],
        ], $front_id );
        $did_seed = true;
    }

    if ( ! get_field( 'conditions', $front_id ) ) {
        update_field( 'conditions', [
            [ 'card_title' => 'Addiction Support', 'card_text' => 'Guidance for substance use, recovery options, and next steps.', 'card_link' => '' ],
            [ 'card_title' => 'Alcohol or Drug Use', 'card_text' => 'Support for individuals or families facing alcohol or substance-related concerns.', 'card_link' => '' ],
            [ 'card_title' => 'Depression & Anxiety', 'card_text' => 'Faith-informed guidance for emotional distress, anxiety, and mental health concerns.', 'card_link' => '' ],
            [ 'card_title' => 'Sex Addiction', 'card_text' => 'Help for compulsive sexual behavior, pornography addiction, and related struggles.', 'card_link' => '' ],
            [ 'card_title' => 'Schizophrenia & Psychosis', 'card_text' => 'Resources and support for serious mental health conditions and treatment decisions.', 'card_link' => '' ],
            [ 'card_title' => 'Help for a Loved One', 'card_text' => 'Support for spouses, parents, and families trying to help someone they care about.', 'card_link' => '' ],
        ], $front_id );
        $did_seed = true;
    }

    if ( ! get_field( 'how_steps', $front_id ) ) {
        update_field( 'how_steps', [
            [ 'step_icon' => '', 'step_title' => 'Call or Start Online', 'step_text' => 'Speak confidentially with Lighthouse about what you or your loved one is facing.' ],
            [ 'step_icon' => '', 'step_title' => "Share What's Going On", 'step_text' => 'A Care Guide asks about your situation, treatment needs, location, and insurance or financial considerations.' ],
            [ 'step_icon' => '', 'step_title' => 'Review Possible Options', 'step_text' => 'Lighthouse reviews available resources and treatment pathways that may fit your needs.' ],
            [ 'step_icon' => '', 'step_title' => 'Get Connected', 'step_text' => 'Your Care Guide helps you understand the next step and connect with the option you choose.' ],
        ], $front_id );
        $did_seed = true;
    }

    if ( ! get_field( 'trust_cards', $front_id ) ) {
        update_field( 'trust_cards', [
            [ 'trust_card_image' => '', 'trust_card_title' => 'Founded in 2003', 'trust_card_text' => 'Over twenty years helping callers find guidance, resources, and hope.' ],
            [ 'trust_card_image' => '', 'trust_card_title' => 'Faith-Informed Approach', 'trust_card_text' => 'Support that respects Christian values while helping families understand available options.' ],
            [ 'trust_card_image' => '', 'trust_card_title' => 'Nationwide Resources', 'trust_card_text' => 'Access to a broad network of treatment programs, outpatient services, and recovery resources.' ],
            [ 'trust_card_image' => '', 'trust_card_title' => 'Confidential Guidance', 'trust_card_text' => 'Every conversation begins with a private discussion focused on understanding your situation and next steps.' ],
        ], $front_id );
        $did_seed = true;
    }

    if ( ! get_field( 'founder_bullets', $front_id ) ) {
        update_field( 'founder_bullets', [
            [ 'item' => 'Founder of Lighthouse Network' ],
            [ 'item' => 'Christian mental health and addiction recovery advocate' ],
            [ 'item' => 'Editorial voice behind Lighthouse resources' ],
            [ 'item' => 'Helping individuals and families navigate treatment decisions' ],
        ], $front_id );
        $did_seed = true;
    }

    if ( ! get_field( 'resources', $front_id ) ) {
        update_field( 'resources', [
            [ 'resource_title' => 'Devotionals', 'resource_text' => 'Daily encouragement rooted in faith, hope, and personal growth.', 'resource_cta_label' => 'Read Devotionals', 'resource_cta_link' => '' ],
            [ 'resource_title' => 'Mental Health Resources', 'resource_text' => 'Guidance for anxiety, depression, emotional distress, and treatment decisions.', 'resource_cta_label' => 'Explore Mental Health', 'resource_cta_link' => '' ],
            [ 'resource_title' => 'Addiction & Recovery', 'resource_text' => 'Resources for substance use, recovery support, relapse prevention, and family guidance.', 'resource_cta_label' => 'Explore Recovery Resources', 'resource_cta_link' => '' ],
            [ 'resource_title' => 'Family Support', 'resource_text' => 'Help for parents, spouses, and loved ones trying to support someone in crisis.', 'resource_cta_label' => 'Find Family Resources', 'resource_cta_link' => '' ],
        ], $front_id );
        $did_seed = true;
    }

    if ( $did_seed || $force_seed ) {
        update_option( 'lh_home_seeded', 1 );
    }
}
