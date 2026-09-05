<?php get_header(); ?>

<main id="main-content" class="main container">
<?php if (have_posts()) : ?>
    <header>
        <h1>Search Results</h1>
    </header>

    <?php while (have_posts()) : the_post(); ?>

    <article <?php post_class() ?> id="post-<?php the_ID(); ?>">

        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

        <div class="entry">
            <?php the_excerpt(); ?>
        </div>

    </article>

    <?php endwhile; ?>

<?php else : ?>

    <h2>No posts found.</h2>

<?php endif; ?>
</main>

<?php get_footer();
