<?php
/**
 * front-page.php - Lighthouse Network homepage.
 *
 * Wired to the Home - Lighthouse ACF field group, with built-in content
 * fallbacks (see inc/lh-home-content.php) so the page always renders real
 * content even when ACF is missing, inactive, or unseeded.
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

if ( ! function_exists( 'lh_get' ) ) {
    /**
     * Return an ACF field value, falling back to the shared default content
     * when ACF is unavailable or the stored value is empty.
     *
     * @param string $field_name Field name.
     * @return mixed
     */
    function lh_get( $field_name ) {
        $value = lh_field( $field_name );

        if ( ! empty( $value ) ) {
            return $value;
        }

        $defaults = function_exists( 'lh_home_default_content' ) ? lh_home_default_content() : [];

        return isset( $defaults[ $field_name ] ) ? $defaults[ $field_name ] : '';
    }
}

if ( ! function_exists( 'lh_rows' ) ) {
    /**
     * Return repeater rows as plain arrays, falling back to the shared default
     * content when ACF has no usable rows.
     *
     * Rows whose sub-fields are all empty are discarded - this is what prevents
     * empty repeater scaffolding (e.g. blank cards) from rendering when ACF
     * field definitions are not resolving the stored sub-field values.
     *
     * @param string $field_name Repeater field name.
     * @param array  $sub_fields Sub-field names to read from each row.
     * @return array[] List of associative arrays keyed by sub-field name.
     */
    function lh_rows( $field_name, $sub_fields ) {
        $rows = [];

        if ( function_exists( 'have_rows' ) && have_rows( $field_name ) ) {
            while ( have_rows( $field_name ) ) {
                the_row();

                $row         = [];
                $has_content = false;

                foreach ( $sub_fields as $sub_field ) {
                    $value             = function_exists( 'get_sub_field' ) ? get_sub_field( $sub_field ) : null;
                    $row[ $sub_field ] = $value;

                    if ( ! empty( $value ) ) {
                        $has_content = true;
                    }
                }

                if ( $has_content ) {
                    $rows[] = $row;
                }
            }
        }

        if ( empty( $rows ) ) {
            $defaults = function_exists( 'lh_home_default_content' ) ? lh_home_default_content() : [];
            $rows     = isset( $defaults[ $field_name ] ) ? $defaults[ $field_name ] : [];
        }

        return $rows;
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

    <?php $hero_bg = lh_field( 'hero_bg_image' ); ?>
    <section class="lh-hero"<?php if ( lh_image_url( $hero_bg ) ) : ?> style="background-image:url('<?php echo esc_url( lh_image_url( $hero_bg ) ); ?>');"<?php endif; ?>>
        <div class="lh-hero__overlay"></div>
        <div class="lh-container lh-hero__inner">
            <?php if ( lh_get( 'hero_eyebrow' ) ) : ?>
                <p class="lh-eyebrow"><?php echo esc_html( lh_get( 'hero_eyebrow' ) ); ?></p>
            <?php endif; ?>

            <h1 class="lh-hero__title"><?php echo esc_html( lh_get( 'hero_title' ) ); ?></h1>

            <?php if ( lh_get( 'hero_subtext' ) ) : ?>
                <p class="lh-hero__subtext"><?php echo esc_html( lh_get( 'hero_subtext' ) ); ?></p>
            <?php endif; ?>

            <div class="lh-hero__ctas">
                <?php if ( lh_get( 'hero_cta_1_label' ) ) : ?>
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'hero_cta_1_link' ) ? lh_get( 'hero_cta_1_link' ) : lh_phone_tel() ); ?>">
                        <?php echo esc_html( lh_get( 'hero_cta_1_label' ) ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( lh_get( 'hero_cta_2_label' ) ) : ?>
                    <a class="lh-btn lh-btn--ghost" href="<?php echo esc_url( lh_get( 'hero_cta_2_link' ) ); ?>">
                        <?php echo esc_html( lh_get( 'hero_cta_2_label' ) ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="lh-about">
        <div class="lh-container lh-about__grid">
            <div class="lh-about__main">
                <?php if ( lh_get( 'about_eyebrow' ) ) : ?>
                    <p class="lh-eyebrow"><?php echo esc_html( lh_get( 'about_eyebrow' ) ); ?></p>
                <?php endif; ?>
                <h2 class="lh-about__heading"><?php echo esc_html( lh_get( 'about_heading' ) ); ?></h2>
                <?php if ( lh_get( 'about_body' ) ) : ?>
                    <div class="lh-about__body"><?php echo wp_kses_post( lh_get( 'about_body' ) ); ?></div>
                <?php endif; ?>

                <?php $about_bullets = lh_rows( 'about_bullets', [ 'item' ] ); ?>
                <?php if ( $about_bullets ) : ?>
                    <ul class="lh-about__bullets">
                        <?php foreach ( $about_bullets as $bullet ) : ?>
                            <li><?php echo esc_html( $bullet['item'] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ( lh_get( 'about_side_heading' ) ) : ?>
                <aside class="lh-about__side">
                    <h3><?php echo esc_html( lh_get( 'about_side_heading' ) ); ?></h3>
                    <?php if ( lh_get( 'about_side_text' ) ) : ?>
                        <p><?php echo esc_html( lh_get( 'about_side_text' ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( lh_get( 'about_side_cta_label' ) ) : ?>
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'about_side_cta_link' ) ? lh_get( 'about_side_cta_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_get( 'about_side_cta_label' ) ); ?>
                        </a>
                    <?php endif; ?>
                </aside>
            <?php endif; ?>
        </div>
    </section>

    <?php $conditions = lh_rows( 'conditions', [ 'card_title', 'card_text', 'card_link' ] ); ?>
    <?php if ( $conditions ) : ?>
        <?php $conditions_bg = lh_field( 'conditions_bg_image' ); ?>
        <section class="lh-conditions"<?php if ( lh_image_url( $conditions_bg ) ) : ?> style="background-image:url('<?php echo esc_url( lh_image_url( $conditions_bg ) ); ?>');"<?php endif; ?>>
            <div class="lh-conditions__overlay"></div>
            <div class="lh-container">
                <div class="lh-conditions__grid">
                    <?php foreach ( $conditions as $condition ) : ?>
                        <?php
                        $condition_title = $condition['card_title'];
                        $condition_text  = $condition['card_text'];
                        $condition_link  = $condition['card_link'];

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
                    <?php endforeach; ?>
                </div>

                <?php if ( lh_get( 'conditions_cta_label' ) ) : ?>
                    <div class="lh-conditions__cta">
                        <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'conditions_cta_link' ) ? lh_get( 'conditions_cta_link' ) : lh_phone_tel() ); ?>">
                            <?php echo esc_html( lh_get( 'conditions_cta_label' ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="lh-how">
        <div class="lh-container">
            <header class="lh-how__head">
                <h2><?php echo esc_html( lh_get( 'how_heading' ) ); ?></h2>
                <?php if ( lh_get( 'how_subtext' ) ) : ?>
                    <p><?php echo esc_html( lh_get( 'how_subtext' ) ); ?></p>
                <?php endif; ?>
            </header>

            <?php $how_steps = lh_rows( 'how_steps', [ 'step_icon', 'step_title', 'step_text' ] ); ?>
            <?php if ( $how_steps ) : ?>
                <div class="lh-how__grid">
                    <?php foreach ( $how_steps as $step ) : ?>
                        <?php $step_icon = $step['step_icon']; ?>
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
                            <h3 class="lh-step__title"><?php echo esc_html( $step['step_title'] ); ?></h3>
                            <?php if ( $step['step_text'] ) : ?>
                                <p class="lh-step__text"><?php echo esc_html( $step['step_text'] ); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ( lh_get( 'how_cta_label' ) ) : ?>
                <div class="lh-how__cta">
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'how_cta_link' ) ? lh_get( 'how_cta_link' ) : lh_phone_tel() ); ?>">
                        <?php echo esc_html( lh_get( 'how_cta_label' ) ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="lh-story">
        <div class="lh-container lh-story__grid">
            <?php if ( lh_get( 'story_video_url' ) ) : ?>
                <div class="lh-story__video">
                    <?php echo wp_oembed_get( esc_url( lh_get( 'story_video_url' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>

            <div class="lh-story__content">
                <?php if ( lh_get( 'story_eyebrow' ) ) : ?>
                    <p class="lh-eyebrow"><?php echo esc_html( lh_get( 'story_eyebrow' ) ); ?></p>
                <?php endif; ?>
                <h2><?php echo esc_html( lh_get( 'story_heading' ) ); ?></h2>
                <?php if ( lh_get( 'story_body' ) ) : ?>
                    <p><?php echo esc_html( lh_get( 'story_body' ) ); ?></p>
                <?php endif; ?>
                <?php if ( lh_get( 'story_person' ) ) : ?>
                    <p class="lh-story__person"><strong><?php echo esc_html( lh_get( 'story_person' ) ); ?></strong></p>
                <?php endif; ?>
                <?php if ( lh_get( 'story_person_text' ) ) : ?>
                    <p><?php echo esc_html( lh_get( 'story_person_text' ) ); ?></p>
                <?php endif; ?>
                <?php if ( lh_get( 'story_cta_label' ) ) : ?>
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'story_cta_link' ) ? lh_get( 'story_cta_link' ) : lh_phone_tel() ); ?>">
                        <?php echo esc_html( lh_get( 'story_cta_label' ) ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="lh-trust">
        <div class="lh-container">
            <header class="lh-trust__head">
                <h2><?php echo esc_html( lh_get( 'trust_heading' ) ); ?></h2>
                <?php if ( lh_get( 'trust_subtext' ) ) : ?>
                    <p><?php echo esc_html( lh_get( 'trust_subtext' ) ); ?></p>
                <?php endif; ?>
            </header>

            <?php $trust_cards = lh_rows( 'trust_cards', [ 'trust_card_image', 'trust_card_title', 'trust_card_text' ] ); ?>
            <?php if ( $trust_cards ) : ?>
                <div class="lh-trust__grid">
                    <?php foreach ( $trust_cards as $trust_card ) : ?>
                        <?php $trust_image = $trust_card['trust_card_image']; ?>
                        <div class="lh-trust-card"<?php if ( lh_image_url( $trust_image ) ) : ?> style="background-image:url('<?php echo esc_url( lh_image_url( $trust_image ) ); ?>');"<?php endif; ?>>
                            <div class="lh-trust-card__overlay"></div>
                            <div class="lh-trust-card__body">
                                <h3><?php echo esc_html( $trust_card['trust_card_title'] ); ?></h3>
                                <?php if ( $trust_card['trust_card_text'] ) : ?>
                                    <p><?php echo esc_html( $trust_card['trust_card_text'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="lh-founder">
        <div class="lh-container lh-founder__grid">
            <?php $founder_photo = lh_field( 'founder_photo' ); ?>
            <?php if ( lh_image_url( $founder_photo ) ) : ?>
                <div class="lh-founder__photo">
                    <img src="<?php echo esc_url( lh_image_url( $founder_photo ) ); ?>" alt="<?php echo esc_attr( lh_image_alt( $founder_photo, lh_get( 'founder_name' ) ) ); ?>" />
                </div>
            <?php endif; ?>

            <div class="lh-founder__content">
                <?php if ( lh_get( 'founder_eyebrow' ) ) : ?>
                    <p class="lh-eyebrow"><?php echo esc_html( lh_get( 'founder_eyebrow' ) ); ?></p>
                <?php endif; ?>
                <h2><?php echo esc_html( lh_get( 'founder_name' ) ); ?></h2>
                <?php if ( lh_get( 'founder_bio' ) ) : ?>
                    <div class="lh-founder__bio"><?php echo wp_kses_post( lh_get( 'founder_bio' ) ); ?></div>
                <?php endif; ?>

                <?php $founder_bullets = lh_rows( 'founder_bullets', [ 'item' ] ); ?>
                <?php if ( $founder_bullets ) : ?>
                    <ul class="lh-founder__bullets">
                        <?php foreach ( $founder_bullets as $bullet ) : ?>
                            <li><?php echo esc_html( $bullet['item'] ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ( lh_get( 'founder_cta_label' ) ) : ?>
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'founder_cta_link' ) ? lh_get( 'founder_cta_link' ) : lh_phone_tel() ); ?>">
                        <?php echo esc_html( lh_get( 'founder_cta_label' ) ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php $resources = lh_rows( 'resources', [ 'resource_title', 'resource_text', 'resource_cta_label', 'resource_cta_link' ] ); ?>
    <?php if ( $resources ) : ?>
        <section class="lh-resources">
            <div class="lh-container">
                <div class="lh-resources__grid">
                    <?php foreach ( $resources as $resource ) : ?>
                        <div class="lh-res-card">
                            <h3><?php echo esc_html( $resource['resource_title'] ); ?></h3>
                            <?php if ( $resource['resource_text'] ) : ?>
                                <p><?php echo esc_html( $resource['resource_text'] ); ?></p>
                            <?php endif; ?>
                            <?php if ( $resource['resource_cta_label'] ) : ?>
                                <a class="lh-btn lh-btn--ghost" href="<?php echo esc_url( $resource['resource_cta_link'] ); ?>">
                                    <?php echo esc_html( $resource['resource_cta_label'] ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="lh-resources__cta">
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_phone_tel() ); ?>">
                        <?php echo esc_html( 'Call ' . lh_phone() ); ?>
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="lh-final">
        <div class="lh-container lh-final__inner">
            <h2><?php echo esc_html( lh_get( 'final_heading' ) ); ?></h2>
            <?php if ( lh_get( 'final_text' ) ) : ?>
                <p><?php echo esc_html( lh_get( 'final_text' ) ); ?></p>
            <?php endif; ?>

            <div class="lh-final__ctas">
                <?php if ( lh_get( 'final_cta_1_label' ) ) : ?>
                    <a class="lh-btn lh-btn--primary" href="<?php echo esc_url( lh_get( 'final_cta_1_link' ) ? lh_get( 'final_cta_1_link' ) : lh_phone_tel() ); ?>">
                        <?php echo esc_html( lh_get( 'final_cta_1_label' ) ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( lh_get( 'final_cta_2_label' ) ) : ?>
                    <a class="lh-btn lh-btn--ghost" href="<?php echo esc_url( lh_get( 'final_cta_2_link' ) ); ?>">
                        <?php echo esc_html( lh_get( 'final_cta_2_label' ) ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ( lh_get( 'final_emergency_note' ) ) : ?>
                <p class="lh-final__emergency"><?php echo esc_html( lh_get( 'final_emergency_note' ) ); ?></p>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php get_footer(); ?>
