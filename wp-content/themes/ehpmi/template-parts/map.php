<?php
$countries_args = array(
    'post_parent' => 17,
    'post_type'   => 'page',
    'post_status' => 'publish'
);

$countries = get_children($countries_args);
?>
<section class="map<?= $post->ID == 17 ? ' inner' : ' animation-element slide-bottom' ?>">
    <div class="container">
        <div class="image">
            <img class="map-image" src="<?= get_template_directory_uri() ?>/images/map9-01.webp" alt="map"><?php 
            if (!empty($countries)) {
                foreach ($countries as $country) {
                    $country_point = get_post_meta($country->ID, 'point', true);
                    if (!empty($country_point)) { ?>
            <details class="point point-<?= $country_point ?>"><summary class="marker"></summary>
                <p class="address"><a href="<?= get_post_permalink($country->ID) ?>"><?= get_the_title($country->ID) ?></a></p>
            </details><?php
                    }
                }
            } ?>
        </div>
        <?php $post->ID != 17 ? dynamic_sidebar('map-text') : ''; ?>
    </div>
</section>
