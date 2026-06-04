<?php
/**
 * Site footer template.
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
        <nav class="footer-nav" aria-label="Footer menu">
            <?php
            wp_nav_menu( [
                'theme_location' => 'footer',
                'container'      => false,
                'fallback_cb'    => false,
                'menu_class'     => 'menu-list',
            ] );
            ?>
        </nav>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
