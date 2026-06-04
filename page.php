<?php
/**
 * Default page template.
 *
 * @package hello-elementor-child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<section class="section page-content">
    <div class="container prose">
        <?php
        while ( have_posts() ) {
            the_post();
            the_title( '<h1>', '</h1>' );
            the_content();
        }
        ?>
    </div>
</section>
<?php
get_footer();
