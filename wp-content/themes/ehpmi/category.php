<?php
/**
 * Legacy category fallback.
 *
 * EHPMI Core redirects the former structural categories to canonical Pages.
 * This template remains as a safe fallback for any future non-structural term.
 */

get_header();
?>
<main class="main container">
    <header>
        <h1><?php single_cat_title(); ?></h1>
        <?php the_archive_description( '<div class="taxonomy-description">', '</div>' ); ?>
    </header>

    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : ?>
            <?php the_post(); ?>
            <article <?php post_class( 'main-article' ); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php the_excerpt(); ?>
            </article>
        <?php endwhile; ?>
        <?php the_posts_navigation(); ?>
    <?php endif; ?>
</main>
<?php
get_footer();
