<?php
/**
 * Hello Elementor Child Theme functions
 *
 * STBART bootstrap:
 * - Enqueue Hello Elementor parent stylesheet
 * - Enqueue child stylesheet + site stylesheet
 * - Register basic theme supports and menus
 * - Expose build id marker for deploy verification
 * - Optional manual rewrite flush trigger: /?rmof_flush=1 (admin only)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'STBART_THEME_VERSION', '1.0.0' );
define( 'STBART_CHILD_DIR', get_stylesheet_directory() );
define( 'STBART_CHILD_URL', get_stylesheet_directory_uri() );

add_action( 'after_setup_theme', 'stbart_theme_setup' );
function stbart_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', [
        'height'      => 120,
        'width'       => 420,
        'flex-height' => true,
        'flex-width'  => true,
    ] );

    register_nav_menus( [
        'primary' => 'Primary Menu',
        'footer'  => 'Footer Menu',
    ] );
}

add_action( 'wp_enqueue_scripts', 'stbart_enqueue_parent_style', 20 );
function stbart_enqueue_parent_style() {
    wp_enqueue_style(
        'hello-elementor-parent',
        get_template_directory_uri() . '/style.css',
        [],
        STBART_THEME_VERSION
    );

    wp_enqueue_style(
        'hello-elementor-child',
        get_stylesheet_uri(),
        [ 'hello-elementor-parent' ],
        STBART_THEME_VERSION
    );

    wp_enqueue_style(
        'stbart-site',
        STBART_CHILD_URL . '/assets/css/site.css',
        [ 'hello-elementor-parent', 'hello-elementor-child' ],
        STBART_THEME_VERSION
    );

    if ( is_front_page() ) {
        wp_enqueue_style(
            'stbart-home',
            STBART_CHILD_URL . '/assets/css/home.css',
            [ 'stbart-site' ],
            STBART_THEME_VERSION
        );
    }
}

function stbart_get_build_id() {
    static $build_id = null;

    if ( null !== $build_id ) {
        return $build_id;
    }

    $file = trailingslashit( STBART_CHILD_DIR ) . 'build-id.txt';
    if ( file_exists( $file ) && is_readable( $file ) ) {
        $raw = trim( (string) file_get_contents( $file ) );
        if ( '' !== $raw ) {
            $build_id = $raw;
            return $build_id;
        }
    }

    $build_id = 'STBART_THEME_VERSION=' . STBART_THEME_VERSION;
    return $build_id;
}

add_action( 'wp_head', 'stbart_output_build_meta', 2 );
function stbart_output_build_meta() {
    if ( is_admin() ) {
        return;
    }

    echo '<meta name="stbart-build-id" content="' . esc_attr( stbart_get_build_id() ) . '">' . "\n";
}

add_action( 'wp_footer', 'stbart_output_build_comment', 99 );
function stbart_output_build_comment() {
    if ( is_admin() ) {
        return;
    }

    echo "\n<!-- stbart-build-id: " . esc_html( stbart_get_build_id() ) . " -->\n";
}

add_action( 'init', 'stbart_optional_manual_flush' );
function stbart_optional_manual_flush() {
    if ( is_admin() ) {
        return;
    }

    if ( ! isset( $_GET['rmof_flush'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    flush_rewrite_rules( true );
}
