<?php
/**
 * Fallback template for routes that do not have a more specific PHP template.
 */

get_header();
?>

<main id="main-content" class="main container">
    <?php if ( is_404() ) : ?>
        <header>
            <h1><?php esc_html_e( 'Page not found', 'ehpmi' ); ?></h1>
        </header>
        <p><?php esc_html_e( 'The requested page could not be found.', 'ehpmi' ); ?></p>
    <?php elseif ( have_posts() ) : ?>
        <header>
            <h1>
                <?php
                if ( is_archive() ) {
                    the_archive_title();
                } else {
                    esc_html_e( 'Latest posts', 'ehpmi' );
                }
                ?>
            </h1>
        </header>

        <?php while ( have_posts() ) : ?>
            <?php the_post(); ?>
            <article <?php post_class( 'main-article' ); ?> id="post-<?php the_ID(); ?>">
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php the_excerpt(); ?>
            </article>
        <?php endwhile; ?>

        <?php the_posts_navigation(); ?>
    <?php else : ?>
        <header>
            <h1><?php esc_html_e( 'Nothing found', 'ehpmi' ); ?></h1>
        </header>
    <?php endif; ?>
</main>

<?php
get_footer();
