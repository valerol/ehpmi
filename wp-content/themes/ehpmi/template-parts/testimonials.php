<?php
$testimonials = get_posts(['post_type' => 'testimonial', 'numberposts' => 50]);
?>
<?php if ($testimonials) : ?>
<section class="testimonials animation-element slide-left">
    <header>
        <h2>Testimonials</h2>
    </header>
    <div id="testimonials-carousel" class="testimonials-carousel container">
        <div class="owl-carousel">
            <?php foreach ($testimonials as $testimonial) : ?>
            <div class="item">
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
</section>
<?php endif; ?>
