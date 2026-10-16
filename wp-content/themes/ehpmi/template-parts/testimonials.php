<?php
/**
 * Dormant Testimonials homepage section.
 *
 * Stage 5 deliberately leaves this disabled. Activation requires a separate
 * reviewed release that returns true from the filter below after its checklist
 * has passed.
 */
if ( ! apply_filters( 'ehpmi_testimonials_active', false ) ) {
    return;
}

$testimonials = get_posts(
    array(
        'post_type'      => 'testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => array(
            'menu_order' => 'ASC',
            'ID'         => 'ASC',
        ),
        'no_found_rows'  => true,
    )
);

if ( ! $testimonials ) {
    return;
}
?>
<section class="testimonials animation-element slide-left">
    <header>
        <h2>Testimonials</h2>
    </header>
    <div id="testimonials-carousel" class="testimonials-carousel container ehpmi-carousel" role="region"
         aria-label="Testimonials" aria-roledescription="carousel" data-carousel-loop="true"
         data-carousel-gap="50" data-carousel-medium-breakpoint="721" data-carousel-medium-items="2"
         data-carousel-large-breakpoint="1025" data-carousel-large-items="3">
        <div class="ehpmi-carousel__viewport" tabindex="0">
            <div class="ehpmi-carousel__track">
            <?php foreach ( $testimonials as $testimonial ) :
                $thumbnail_id = get_post_thumbnail_id( $testimonial->ID );
                $author_name  = trim( (string) get_post_meta( $testimonial->ID, 'testimonial_author_name', true ) );
                $author_role  = trim( (string) get_post_meta( $testimonial->ID, 'testimonial_author_role', true ) );
                $organization = trim( (string) get_post_meta( $testimonial->ID, 'testimonial_author_organization', true ) );
                $quote        = apply_filters( 'the_content', $testimonial->post_content );
                ?>
            <div class="item ehpmi-carousel__item">
                <article id="testimonial-<?php echo esc_attr( $testimonial->ID ); ?>" class="testimonial">
                    <?php if ( $thumbnail_id ) :
                        $thumbnail_alt = trim( (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) );
                        echo wp_get_attachment_image(
                            $thumbnail_id,
                            'thumbnail',
                            false,
                            array( 'alt' => $thumbnail_alt )
                        );
                    endif; ?>
                    <div class="text">
                        <blockquote class="quote">
                            <?php echo wp_kses_post( $quote ); ?>
                            <footer>
                                <cite>
                                    <span class="name"><?php echo esc_html( $author_name ); ?></span>
                                    <?php if ( '' !== $author_role ) : ?>
                                        <span class="position"><?php echo esc_html( $author_role ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( '' !== $organization ) : ?>
                                        <span class="organization"><?php echo esc_html( $organization ); ?></span>
                                    <?php endif; ?>
                                </cite>
                            </footer>
                        </blockquote>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <div class="ehpmi-carousel__nav" aria-label="Testimonial controls">
            <button class="ehpmi-carousel__control ehpmi-carousel__control--prev" type="button" aria-label="Previous testimonial"></button>
            <button class="ehpmi-carousel__control ehpmi-carousel__control--next" type="button" aria-label="Next testimonial"></button>
        </div>
        <p class="ehpmi-carousel__status screen-reader-text" aria-live="polite"></p>
    </div>
</section>
