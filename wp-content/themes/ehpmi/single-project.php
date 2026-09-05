<?php
/**
 * Single Project template.
 */

get_header();
?>
<main id="main-content" class="site-main">
    <?php
    while ( have_posts() ) {
        the_post();
        get_template_part( 'template-parts/content', 'project' );
    }
    ?>
</main>
<?php
get_footer();
