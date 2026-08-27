<?php
$testimonials = get_posts(['post_type' => 'testimonial', 'numberposts' => 50]);
?>
<?php if ($testimonials) : ?>
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
            <?php foreach ($testimonials as $testimonial) : ?>
            <div class="item ehpmi-carousel__item">
                <div class="testimonial">
                    <?php if (get_the_post_thumbnail($testimonial->ID)) : ?>
                        <?= get_the_post_thumbnail($testimonial->ID) ?>
                    <?php endif; ?>
                    <div class="text">
                        <div class="quote"><?= $testimonial->post_content ?></div>
                        <div class="name"><?= $testimonial->post_title ?></div>
                    </div>
                </div>
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
<?php endif; ?>
