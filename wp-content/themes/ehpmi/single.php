<?php
/**
 * Single news and public custom-content template.
 */

get_header();
?>
<main id="main-content" class="site-main">
    <?php
    while ( have_posts() ) {
        the_post();
        get_template_part( 'template-parts/content', get_post_type() );
    }
    ?>
</main>
<?php
get_footer();
