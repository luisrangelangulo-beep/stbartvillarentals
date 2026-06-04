<?php
/**
 * front-page.php - Lighthouse Network homepage.
 *
 * Wired to the Home - Lighthouse ACF field group.
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lh_field' ) ) {
    function lh_field( $field_name, $post_id = false ) {
        if ( ! function_exists( 'get_field' ) ) {
            return null;
        }

        return get_field( $field_name, $post_id );
    }
}

if ( ! function_exists( 'lh_has_rows' ) ) {
    function lh_has_rows( $field_name ) {
        return function_exists( 'have_rows' ) && have_rows( $field_name );
    }
}

if ( ! function_exists( 'lh_sub_field' ) ) {
    function lh_sub_field( $field_name ) {
        if ( ! function_exists( 'get_sub_field' ) ) {
            return null;
        }

        return get_sub_field( $field_name );
    }
}

if ( ! function_exists( 'lh_image_url' ) ) {
    function lh_image_url( $image ) {
        if ( is_array( $image ) && ! empty( $image['url'] ) ) {
            return $image['url'];
        }

        if ( is_string( $image ) ) {
            return $image;
        }

        return '';
    }
}

if ( ! function_exists( 'lh_image_alt' ) ) {
    function lh_image_alt( $image, $fallback = '' ) {
        if ( is_array( $image ) && ! empty( $image['alt'] ) ) {
            return $image['alt'];
        }

        return $fallback;
    }
}

if ( ! function_exists( 'lh_phone' ) ) {
    function lh_phone() {
        $phone = lh_field( 'global_phone', 'option' );

        if ( ! $phone ) {
            $phone = lh_field( 'global_phone' );
        }

        return $phone ? $phone : '(844) 543-3242';
    }
}

if ( ! function_exists( 'lh_phone_tel' ) ) {
    function lh_phone_tel() {
        return 'tel:' . preg_replace( '/[^0-9+]/', '', lh_phone() );
    }
}

get_header();
?>

<main id="lh-home" class="lh-home">

    <?php if ( lh_field( 'hero_title' ) ) : ?>
        <?php $hero_bg = lh_field( 'hero_bg_image' ); ?>
        <section class="lh-hero"<?php if ( lh_image_url( $hero_bg ) ) : ?> style="background-image:url('<?php echo esc_url( lh_image_url( $hero_bg ) ); ?>');"<?php endif; ?>>
            <div class="lh-hero__overlay"></div>
            <div class="lh-container lh-hero__inner">
                <?php if ( lh_field( 'hero_eyebrow' ) ) : ?>
                    <p class="lh-eyebrow"><?php echo esc_html( lh_field( 'hero_eyebrow' ) ); ?></p>
                <?php endif; ?>

                <h1 class="lh-hero__title"><?php echo esc_html( lh_field( 'hero_title' ) ); ?></h1>

                <?php if ( lh_field( 'hero_subtext' ) ) : ?>
                    <p class="lh-hero__subtext"><?php echo esc_html( lh_field( 'hero_subtext' ) ); ?></p>
                <?php endif; ?>

                <div class="lh-hero__ctas">
                    <?php if ( lh_field( 'hero_cta_1_label' ) ) : ?>
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'hero_cta_1_link' ) ? lh_field( 'hero_cta_1_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_field( 'hero_cta_1_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( lh_field( 'hero_cta_2_label' ) ) : ?>
                        <a class="lh-btn lh-btn--ghost" href="<?php echo esc_url( lh_field( 'hero_cta_2_link' ) ); ?>">
                            <?php echo esc_html( lh_field( 'hero_cta_2_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_field( 'about_heading' ) ) : ?>
        <section class="lh-about">
            <div class="lh-container lh-about__grid">
                <div class="lh-about__main">
                    <?php if ( lh_field( 'about_eyebrow' ) ) : ?>
                        <p class="lh-eyebrow"><?php echo esc_html( lh_field( 'about_eyebrow' ) ); ?></p>
                    <?php endif; ?>
                    <h2 class="lh-about__heading"><?php echo esc_html( lh_field( 'about_heading' ) ); ?></h2>
                    <?php if ( lh_field( 'about_body' ) ) : ?>
                        <div class="lh-about__body"><?php echo wp_kses_post( lh_field( 'about_body' ) ); ?></div>
                    <?php endif; ?>

                    <?php if ( lh_has_rows( 'about_bullets' ) ) : ?>
                        <ul class="lh-about__bullets">
                            <?php while ( have_rows( 'about_bullets' ) ) : the_row(); ?>
                                <li><?php echo esc_html( lh_sub_field( 'item' ) ); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php if ( lh_field( 'about_side_heading' ) ) : ?>
                    <aside class="lh-about__side">
                        <h3><?php echo esc_html( lh_field( 'about_side_heading' ) ); ?></h3>
                        <?php if ( lh_field( 'about_side_text' ) ) : ?>
                            <p><?php echo esc_html( lh_field( 'about_side_text' ) ); ?></p>
                        <?php endif; ?>
                        <?php if ( lh_field( 'about_side_cta_label' ) ) : ?>
                            <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'about_side_cta_link' ) ? lh_field( 'about_side_cta_link' ) : lh_phone_tel() ); ?>">
                                <?php echo esc_html( lh_field( 'about_side_cta_label' ) ); ?>
                            </a>
                        <?php endif; ?>
                    </aside>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_has_rows( 'conditions' ) ) : ?>
        <?php $conditions_bg = lh_field( 'conditions_bg_image' ); ?>
        <section class="lh-conditions"<?php if ( lh_image_url( $conditions_bg ) ) : ?> style="background-image:url('<?php echo esc_url( lh_image_url( $conditions_bg ) ); ?>');"<?php endif; ?>>
            <div class="lh-conditions__overlay"></div>
            <div class="lh-container">
                <div class="lh-conditions__grid">
                    <?php while ( have_rows( 'conditions' ) ) : the_row(); ?>
                        <?php
                        $condition_title = lh_sub_field( 'card_title' );
                        $condition_text  = lh_sub_field( 'card_text' );
                        $condition_link  = lh_sub_field( 'card_link' );

                        if ( ! $condition_title ) {
                            continue;
                        }
                        ?>
                        <?php if ( $condition_link ) : ?>
                            <a class="lh-cond-card" href="<?php echo esc_url( $condition_link ); ?>">
                        <?php else : ?>
                            <div class="lh-cond-card">
                        <?php endif; ?>
                            <h3 class="lh-cond-card__title"><?php echo esc_html( $condition_title ); ?></h3>
                            <?php if ( $condition_text ) : ?>
                                <p class="lh-cond-card__text"><?php echo esc_html( $condition_text ); ?></p>
                            <?php endif; ?>
                        <?php if ( $condition_link ) : ?>
                            </a>
                        <?php else : ?>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>

                <?php if ( lh_field( 'conditions_cta_label' ) ) : ?>
                    <div class="lh-conditions__cta">
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'conditions_cta_link' ) ? lh_field( 'conditions_cta_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_field( 'conditions_cta_label' ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_field( 'how_heading' ) ) : ?>
        <section class="lh-how">
            <div class="lh-container">
                <header class="lh-how__head">
                    <h2><?php echo esc_html( lh_field( 'how_heading' ) ); ?></h2>
                    <?php if ( lh_field( 'how_subtext' ) ) : ?>
                        <p><?php echo esc_html( lh_field( 'how_subtext' ) ); ?></p>
                    <?php endif; ?>
                </header>

                <?php if ( lh_has_rows( 'how_steps' ) ) : ?>
                    <div class="lh-how__grid">
                        <?php while ( have_rows( 'how_steps' ) ) : the_row(); ?>
                            <?php $step_icon = lh_sub_field( 'step_icon' ); ?>
                            <div class="lh-step">
                                <?php if ( $step_icon ) : ?>
                                    <div class="lh-step__icon">
                                        <?php if ( is_array( $step_icon ) && ! empty( $step_icon['url'] ) ) : ?>
                                            <img src="<?php echo esc_url( $step_icon['url'] ); ?>" alt="" />
                                        <?php else : ?>
                                            <i class="<?php echo esc_attr( $step_icon ); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <h3 class="lh-step__title"><?php echo esc_html( lh_sub_field( 'step_title' ) ); ?></h3>
                                <?php if ( lh_sub_field( 'step_text' ) ) : ?>
                                    <p class="lh-step__text"><?php echo esc_html( lh_sub_field( 'step_text' ) ); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <?php if ( lh_field( 'how_cta_label' ) ) : ?>
                    <div class="lh-how__cta">
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'how_cta_link' ) ? lh_field( 'how_cta_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_field( 'how_cta_label' ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_field( 'story_heading' ) ) : ?>
        <section class="lh-story">
            <div class="lh-container lh-story__grid">
                <?php if ( lh_field( 'story_video_url' ) ) : ?>
                    <div class="lh-story__video">
                        <?php echo wp_oembed_get( esc_url( lh_field( 'story_video_url' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>

                <div class="lh-story__content">
                    <?php if ( lh_field( 'story_eyebrow' ) ) : ?>
                        <p class="lh-eyebrow"><?php echo esc_html( lh_field( 'story_eyebrow' ) ); ?></p>
                    <?php endif; ?>
                    <h2><?php echo esc_html( lh_field( 'story_heading' ) ); ?></h2>
                    <?php if ( lh_field( 'story_body' ) ) : ?>
                        <p><?php echo esc_html( lh_field( 'story_body' ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( lh_field( 'story_person' ) ) : ?>
                        <p class="lh-story__person"><strong><?php echo esc_html( lh_field( 'story_person' ) ); ?></strong></p>
                    <?php endif; ?>
                    <?php if ( lh_field( 'story_person_text' ) ) : ?>
                        <p><?php echo esc_html( lh_field( 'story_person_text' ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( lh_field( 'story_cta_label' ) ) : ?>
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'story_cta_link' ) ? lh_field( 'story_cta_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_field( 'story_cta_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_field( 'trust_heading' ) ) : ?>
        <section class="lh-trust">
            <div class="lh-container">
                <header class="lh-trust__head">
                    <h2><?php echo esc_html( lh_field( 'trust_heading' ) ); ?></h2>
                    <?php if ( lh_field( 'trust_subtext' ) ) : ?>
                        <p><?php echo esc_html( lh_field( 'trust_subtext' ) ); ?></p>
                    <?php endif; ?>
                </header>

                <?php if ( lh_has_rows( 'trust_cards' ) ) : ?>
                    <div class="lh-trust__grid">
                        <?php while ( have_rows( 'trust_cards' ) ) : the_row(); ?>
                            <?php $trust_image = lh_sub_field( 'trust_card_image' ); ?>
                            <div class="lh-trust-card"<?php if ( lh_image_url( $trust_image ) ) : ?> style="background-image:url('<?php echo esc_url( lh_image_url( $trust_image ) ); ?>');"<?php endif; ?>>
                                <div class="lh-trust-card__overlay"></div>
                                <div class="lh-trust-card__body">
                                    <h3><?php echo esc_html( lh_sub_field( 'trust_card_title' ) ); ?></h3>
                                    <?php if ( lh_sub_field( 'trust_card_text' ) ) : ?>
                                        <p><?php echo esc_html( lh_sub_field( 'trust_card_text' ) ); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_field( 'founder_name' ) ) : ?>
        <section class="lh-founder">
            <div class="lh-container lh-founder__grid">
                <?php $founder_photo = lh_field( 'founder_photo' ); ?>
                <?php if ( lh_image_url( $founder_photo ) ) : ?>
                    <div class="lh-founder__photo">
                        <img src="<?php echo esc_url( lh_image_url( $founder_photo ) ); ?>" alt="<?php echo esc_attr( lh_image_alt( $founder_photo, lh_field( 'founder_name' ) ) ); ?>" />
                    </div>
                <?php endif; ?>

                <div class="lh-founder__content">
                    <?php if ( lh_field( 'founder_eyebrow' ) ) : ?>
                        <p class="lh-eyebrow"><?php echo esc_html( lh_field( 'founder_eyebrow' ) ); ?></p>
                    <?php endif; ?>
                    <h2><?php echo esc_html( lh_field( 'founder_name' ) ); ?></h2>
                    <?php if ( lh_field( 'founder_bio' ) ) : ?>
                        <div class="lh-founder__bio"><?php echo wp_kses_post( lh_field( 'founder_bio' ) ); ?></div>
                    <?php endif; ?>

                    <?php if ( lh_has_rows( 'founder_bullets' ) ) : ?>
                        <ul class="lh-founder__bullets">
                            <?php while ( have_rows( 'founder_bullets' ) ) : the_row(); ?>
                                <li><?php echo esc_html( lh_sub_field( 'item' ) ); ?></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ( lh_field( 'founder_cta_label' ) ) : ?>
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'founder_cta_link' ) ); ?>">
                            <?php echo esc_html( lh_field( 'founder_cta_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_has_rows( 'resources' ) ) : ?>
        <section class="lh-resources">
            <div class="lh-container">
                <div class="lh-resources__grid">
                    <?php while ( have_rows( 'resources' ) ) : the_row(); ?>
                        <div class="lh-res-card">
                            <h3><?php echo esc_html( lh_sub_field( 'resource_title' ) ); ?></h3>
                            <?php if ( lh_sub_field( 'resource_text' ) ) : ?>
                                <p><?php echo esc_html( lh_sub_field( 'resource_text' ) ); ?></p>
                            <?php endif; ?>
                            <?php if ( lh_sub_field( 'resource_cta_label' ) ) : ?>
                                <a class="lh-btn lh-btn--ghost" href="<?php echo esc_url( lh_sub_field( 'resource_cta_link' ) ); ?>">
                                    <?php echo esc_html( lh_sub_field( 'resource_cta_label' ) ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <div class="lh-resources__cta">
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_phone_tel() ); ?>">
                        <?php echo esc_html( 'Call ' . lh_phone() ); ?>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ( lh_field( 'final_heading' ) ) : ?>
        <section class="lh-final">
            <div class="lh-container lh-final__inner">
                <h2><?php echo esc_html( lh_field( 'final_heading' ) ); ?></h2>
                <?php if ( lh_field( 'final_text' ) ) : ?>
                    <p><?php echo esc_html( lh_field( 'final_text' ) ); ?></p>
                <?php endif; ?>

                <div class="lh-final__ctas">
                    <?php if ( lh_field( 'final_cta_1_label' ) ) : ?>
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_field( 'final_cta_1_link' ) ? lh_field( 'final_cta_1_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_field( 'final_cta_1_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ( lh_field( 'final_cta_2_label' ) ) : ?>
                        <a class="lh-btn lh-btn--ghost" href="<?php echo esc_url( lh_field( 'final_cta_2_link' ) ); ?>">
                            <?php echo esc_html( lh_field( 'final_cta_2_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ( lh_field( 'final_emergency_note' ) ) : ?>
                    <p class="lh-final__emergency"><?php echo esc_html( lh_field( 'final_emergency_note' ) ); ?></p>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

</main>

<?php get_footer(); ?>
