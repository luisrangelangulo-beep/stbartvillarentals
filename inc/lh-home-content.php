<?php
/**
 * Lighthouse homepage default content.
 *
 * Single source of truth for the homepage copy. Used by:
 *  - inc/lh-home-seed.php  -> to populate ACF repeaters in the database, and
 *  - front-page.php        -> as render-time fallbacks when ACF returns nothing.
 *
 * Keeping the copy here means the homepage always renders real content, even
 * before ACF is installed/configured or the seeder has run.
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lh_home_default_content' ) ) {
    /**
     * Default homepage field values.
     *
     * Scalar fields map to their default string; repeater fields map to an
     * array of rows (each row an associative array of sub-field => value).
     *
     * @return array
     */
    function lh_home_default_content() {
        return [
            // Hero.
            'hero_eyebrow'         => '',
            'hero_title'           => 'Christian Mental Health & Addiction Help Available 24/7',
            'hero_subtext'         => 'Speak with Lighthouse Network for faith-informed guidance, treatment options, and support for addiction, mental health, or a loved one in crisis.',
            'hero_cta_1_label'     => 'Call (844) 543-3242',
            'hero_cta_1_link'      => 'tel:8445433242',
            'hero_cta_2_label'     => 'Start Online Assessment',
            'hero_cta_2_link'      => '',

            // About / Intro.
            'about_eyebrow'        => 'About Lighthouse Network',
            'about_heading'        => 'Faith-informed guidance for addiction, mental health, and life recovery',
            'about_body'           => 'Lighthouse Network is a Christian nonprofit founded in 2003, providing 24/7 guidance and online life-growth resources for individuals and families facing addiction, mental health concerns, or difficult treatment decisions.',
            'about_bullets'        => [
                [ 'item' => 'Christian mental health treatment' ],
                [ 'item' => 'Addiction support' ],
                [ 'item' => 'Sex addiction recovery' ],
                [ 'item' => 'Schizophrenia resources' ],
            ],
            'about_side_heading'   => 'Need help deciding what to do next?',
            'about_side_text'      => 'Talk with Lighthouse Network about treatment options, next steps, and support for yourself or a loved one.',
            'about_side_cta_label' => 'Call (844) 543-3242',
            'about_side_cta_link'  => 'tel:8445433242',

            // Conditions.
            'conditions'           => [
                [ 'card_title' => 'Addiction Support', 'card_text' => 'Guidance for substance use, recovery options, and next steps.', 'card_link' => '' ],
                [ 'card_title' => 'Alcohol or Drug Use', 'card_text' => 'Support for individuals or families facing alcohol or substance-related concerns.', 'card_link' => '' ],
                [ 'card_title' => 'Depression & Anxiety', 'card_text' => 'Faith-informed guidance for emotional distress, anxiety, and mental health concerns.', 'card_link' => '' ],
                [ 'card_title' => 'Sex Addiction', 'card_text' => 'Help for compulsive sexual behavior, pornography addiction, and related struggles.', 'card_link' => '' ],
                [ 'card_title' => 'Schizophrenia & Psychosis', 'card_text' => 'Resources and support for serious mental health conditions and treatment decisions.', 'card_link' => '' ],
                [ 'card_title' => 'Help for a Loved One', 'card_text' => 'Support for spouses, parents, and families trying to help someone they care about.', 'card_link' => '' ],
            ],
            'conditions_cta_label' => 'Call (844) 543-3242',
            'conditions_cta_link'  => 'tel:8445433242',

            // How It Works.
            'how_heading'          => 'How Lighthouse Helps You Take the Next Step',
            'how_subtext'          => 'A Care Guide helps you understand what\'s happening, what options may fit, and how to move forward with support.',
            'how_steps'            => [
                [ 'step_icon' => '', 'step_title' => 'Call or Start Online', 'step_text' => 'Speak confidentially with Lighthouse about what you or your loved one is facing.' ],
                [ 'step_icon' => '', 'step_title' => 'Share What\'s Going On', 'step_text' => 'A Care Guide asks about your situation, treatment needs, location, and insurance or financial considerations.' ],
                [ 'step_icon' => '', 'step_title' => 'Review Possible Options', 'step_text' => 'Lighthouse reviews available resources and treatment pathways that may fit your needs.' ],
                [ 'step_icon' => '', 'step_title' => 'Get Connected', 'step_text' => 'Your Care Guide helps you understand the next step and connect with the option you choose.' ],
            ],
            'how_cta_label'        => 'Call (844) 543-3242',
            'how_cta_link'         => 'tel:8445433242',

            // Story / Video.
            'story_eyebrow'        => 'Recovery Stories',
            'story_heading'        => 'Stories of Hope, Healing, and Recovery',
            'story_body'           => 'Real stories can remind us that healing is possible, even when the next step feels overwhelming. Lighthouse shares stories of individuals and families who found support, treatment, and a path forward.',
            'story_person'         => 'Brandi\'s Story',
            'story_person_text'    => 'See how one mother found her way back from despair toward recovery, support, and renewed hope.',
            'story_video_url'      => '',
            'story_cta_label'      => 'Call (844) 543-3242',
            'story_cta_link'       => 'tel:8445433242',

            // Why Families / Trust.
            'trust_heading'        => 'Why Families Have Turned to Lighthouse Network Since 2003',
            'trust_subtext'        => 'For more than two decades, Lighthouse Network has helped individuals and families navigate addiction, mental health challenges, treatment decisions, and faith-informed recovery resources.',
            'trust_cards'          => [
                [ 'trust_card_image' => '', 'trust_card_title' => 'Founded in 2003', 'trust_card_text' => 'Over twenty years helping callers find guidance, resources, and hope.' ],
                [ 'trust_card_image' => '', 'trust_card_title' => 'Faith-Informed Approach', 'trust_card_text' => 'Support that respects Christian values while helping families understand available options.' ],
                [ 'trust_card_image' => '', 'trust_card_title' => 'Nationwide Resources', 'trust_card_text' => 'Access to a broad network of treatment programs, outpatient services, and recovery resources.' ],
                [ 'trust_card_image' => '', 'trust_card_title' => 'Confidential Guidance', 'trust_card_text' => 'Every conversation begins with a private discussion focused on understanding your situation and next steps.' ],
            ],

            // Founder.
            'founder_eyebrow'      => 'Founder & Editorial Director',
            'founder_name'         => 'Meet Dr. David Hoskins',
            'founder_bio'          => 'Dr. David Hoskins founded Lighthouse Network to help individuals and families find hope, healing, and practical guidance through addiction, mental health challenges, and difficult treatment decisions. His work brings together faith-informed support, clinical awareness, and a commitment to helping people take the next step toward recovery.',
            'founder_bullets'      => [
                [ 'item' => 'Founder of Lighthouse Network' ],
                [ 'item' => 'Christian mental health and addiction recovery advocate' ],
                [ 'item' => 'Editorial voice behind Lighthouse resources' ],
                [ 'item' => 'Helping individuals and families navigate treatment decisions' ],
            ],
            'founder_cta_label'    => 'Learn More About Dr. Hoskins',
            'founder_cta_link'     => '',

            // Resource Cards.
            'resources'            => [
                [ 'resource_title' => 'Devotionals', 'resource_text' => 'Daily encouragement rooted in faith, hope, and personal growth.', 'resource_cta_label' => 'Read Devotionals', 'resource_cta_link' => '' ],
                [ 'resource_title' => 'Mental Health Resources', 'resource_text' => 'Guidance for anxiety, depression, emotional distress, and treatment decisions.', 'resource_cta_label' => 'Explore Mental Health', 'resource_cta_link' => '' ],
                [ 'resource_title' => 'Addiction & Recovery', 'resource_text' => 'Resources for substance use, recovery support, relapse prevention, and family guidance.', 'resource_cta_label' => 'Explore Recovery Resources', 'resource_cta_link' => '' ],
                [ 'resource_title' => 'Family Support', 'resource_text' => 'Help for parents, spouses, and loved ones trying to support someone in crisis.', 'resource_cta_label' => 'Find Family Resources', 'resource_cta_link' => '' ],
            ],

            // Final CTA.
            'final_heading'        => 'You Don\'t Have to Figure This Out Alone',
            'final_text'           => 'Whether you\'re seeking help for yourself or someone you love, Lighthouse Network can help you talk through the next step with faith-informed guidance and practical support. Many insurance providers cover our residential options.',
            'final_cta_1_label'    => 'Call (844) 543-3242',
            'final_cta_1_link'     => 'tel:8445433242',
            'final_cta_2_label'    => 'Start Online Assessment',
            'final_cta_2_link'     => '',
            'final_emergency_note' => 'If this is a medical emergency or someone is in immediate danger, call 911 or your local emergency number.',
        ];
    }
}
