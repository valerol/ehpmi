<?php
$post_type = $post->ID == 1453 ? 'partner2' : 'partner';

$partners = isset($args['partners']) ? $args['partners'] : get_posts(['post_type' => $post_type, 'numberposts' => 50]);
if ($partners) {
?>
<?php if (!isset($args['inner']) && !in_array($post->ID, [29, 1453])) : ?>
<section class="partners animation-element slide-bottom">
    <header>
        <h2>Member Organizations</h2>
    </header>
    <div id="partner-carousel" class="partner-carousel container">
        <div class="owl-carousel">
            <?php foreach ($partners as $partner) : ?><?php if (get_the_post_thumbnail($partner->ID)) : ?>
            <div class="item"><?php if (get_post_meta($partner->ID, 'url', true)) : ?><a href="<?= get_post_meta($partner->ID, 'url', true) ?>"><?php endif; ?>
                <?= get_the_post_thumbnail($partner->ID) ?><?php if (get_post_meta($partner->ID, 'url', true)) : ?></a><?php endif; ?>
            </div><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section><?php else: ?>
<section class="partners"><?php if (isset($args['heading'])) : ?>
    <header>
        <h2><?= $args['heading'] ?></h2>
    </header><?php endif ?>
    <div class="container"><?php foreach ($partners as $partner) : ?>
        <article class="partner">
            <div class="brand"><?php if (get_the_post_thumbnail($partner)) : ?>
                <?= get_the_post_thumbnail($partner) ?><?php endif; ?><?php if (get_post_field('url', $partner)) : ?>
                <a href="<?= get_post_field('url', $partner); ?>"><?= get_post_field('url', $partner); ?></a><?php endif; ?><?php if (get_post_field('additonal_image', $partner)) : ?>
                <?= wp_get_attachment_image(get_post_field('additonal_image', $partner)); ?><?php endif; ?>
            </div>
            <div class="text">
                <h3><?= get_the_title($partner); ?></h3>    
                <?= get_post_field('post_content', $partner); ?>
            </div>
        </article><?php endforeach; ?>
    </div>
</section>
<?php endif ?><?php
}