<?php
$published_slides = get_posts(
    array(
        'post_type'      => 'hero_slide',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => array(
            'menu_order' => 'ASC',
            'ID'         => 'ASC',
        ),
    )
);

$slides = array();

foreach ( $published_slides as $published_slide ) {
    $attachment_id = get_post_thumbnail_id( $published_slide );

    if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
        continue;
    }

    $image_src  = wp_get_attachment_image_src( $attachment_id, 'full' );
    $image_file = get_attached_file( $attachment_id );

    if ( ! $image_src || empty( $image_src[0] ) || ! $image_file || ! is_file( $image_file ) ) {
        continue;
    }

    $slides[] = array(
        'attachment_id' => $attachment_id,
        'src'           => $image_src[0],
        'srcset'        => wp_get_attachment_image_srcset( $attachment_id, 'full' ),
        'sizes'         => wp_get_attachment_image_sizes( $attachment_id, 'full' ),
        'width'         => (int) $image_src[1],
        'height'        => (int) $image_src[2],
        'alt'           => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
    );
}

if ( ! $slides ) {
    $slides = array(
        array(
            'src'    => get_theme_file_uri( '/assets/images/hero/slide1.jpeg' ),
            'width'  => 800,
            'height' => 600,
            'alt'    => __( 'Child wearing a mask near an industrial facility', 'ehpmi' ),
        ),
        array(
            'src'    => get_theme_file_uri( '/assets/images/hero/slide2.jpeg' ),
            'width'  => 800,
            'height' => 600,
            'alt'    => __( 'Child wearing a mask and holding a teddy bear', 'ehpmi' ),
        ),
        array(
            'src'    => get_theme_file_uri( '/assets/images/hero/slide3.jpeg' ),
            'width'  => 800,
            'height' => 600,
            'alt'    => __( 'Child coughing', 'ehpmi' ),
        ),
    );
}

$hero_placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
?>
<section class="hero">
    <div class="container">
        <?php dynamic_sidebar('slider-text'); ?>

        <div id="carouselExampleSlidesOnly" class="carousel slide" data-ehpmi-desktop-carousel="true">
            <div class="carousel-inner">
                <?php foreach ( $slides as $index => $slide ) : ?>
                    <?php
                    $image_attributes = array(
                        'alt'     => $slide['alt'],
                        'loading' => 0 === $index ? 'eager' : 'lazy',
                    );

                    if ( 0 === $index ) {
                        $image_attributes['fetchpriority'] = 'high';
                    }
                    ?>
                    <div class="carousel-item<?php echo 0 === $index ? ' active' : ''; ?>">
                        <picture>
                            <source media="(min-width: 1025px)" srcset="<?php echo esc_attr( ! empty( $slide['srcset'] ) ? $slide['srcset'] : $slide['src'] ); ?>"<?php echo ! empty( $slide['sizes'] ) ? ' sizes="' . esc_attr( $slide['sizes'] ) . '"' : ''; ?>>
                            <img src="<?php echo esc_attr( $hero_placeholder ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" width="<?php echo esc_attr( $slide['width'] ); ?>" height="<?php echo esc_attr( $slide['height'] ); ?>" loading="<?php echo esc_attr( $image_attributes['loading'] ); ?>"<?php echo 0 === $index ? ' fetchpriority="high"' : ''; ?>>
                        </picture>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
