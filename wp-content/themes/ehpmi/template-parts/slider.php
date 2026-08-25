<?php
$slides = get_posts(
    array(
        'post_type'      => 'hero_slide',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ),
        ),
        'orderby'        => array(
            'menu_order' => 'ASC',
            'date'       => 'ASC',
        ),
    )
);

if ( ! $slides ) {
    $slides = array(
        array(
            'src' => get_theme_file_uri( '/assets/images/hero/slide1.jpeg' ),
            'alt' => __( 'Hero slide 1', 'ehpmi' ),
        ),
        array(
            'src' => get_theme_file_uri( '/assets/images/hero/slide2.jpeg' ),
            'alt' => __( 'Hero slide 2', 'ehpmi' ),
        ),
        array(
            'src' => get_theme_file_uri( '/assets/images/hero/slide3.jpeg' ),
            'alt' => __( 'Hero slide 3', 'ehpmi' ),
        ),
    );
}
?>
<section class="hero">
    <div class="container">
        <?php dynamic_sidebar('slider-text'); ?>

        <div id="carouselExampleSlidesOnly" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ( $slides as $index => $slide ) : ?>
                    <div class="carousel-item<?php echo 0 === $index ? ' active' : ''; ?>">
                        <?php if ( $slide instanceof WP_Post ) : ?>
                            <?php
                            echo get_the_post_thumbnail(
                                $slide,
                                'full',
                                array(
                                    'alt'     => get_the_title( $slide ),
                                    'loading' => 0 === $index ? 'eager' : 'lazy',
                                )
                            );
                            ?>
                        <?php else : ?>
                            <img src="<?php echo esc_url( $slide['src'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
