<?php get_header(); ?>

<section class="main container">
<?php if (have_posts()) : ?>
    <header>
        <h1>Search Results</h1>
    </header>

    <?php while (have_posts()) : the_post(); ?>

    <div <?php post_class() ?> id="post-<?php the_ID(); ?>">

        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>

        <div class="entry">
            <?php the_excerpt(); ?>
        </div>

    </div>

    <?php endwhile; ?>

<?php else : ?>

    <h3>No posts found.</h3>

<?php endif; ?>
</section>

<?php get_footer();
