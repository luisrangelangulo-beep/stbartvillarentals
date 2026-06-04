<?php
/**
 * Site header template.
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
            <?php
            if ( has_custom_logo() ) {
                the_custom_logo();
            } else {
                echo esc_html( get_bloginfo( 'name' ) );
            }
            ?>
        </a>
        <nav class="main-nav" aria-label="Primary menu">
            <?php
            wp_nav_menu( [
                'theme_location' => 'primary',
                'container'      => false,
                'fallback_cb'    => false,
                'menu_class'     => 'menu-list',
            ] );
            ?>
        </nav>
    </div>
</header>
<main class="site-main">
