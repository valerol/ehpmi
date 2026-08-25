<?php
$materials = $args['materials'] ? $args['materials'] : get_posts(['post_type' => 'materials', 'numberposts' => 50]);
if ($materials) {
?>
<section class="materials"><?php if (isset($args['category_name'])) : ?><?php if (isset($args['breadcrumbs'])) : ?><div id="breadcrumbs" class="container"><?= $args['breadcrumbs'] ?></div><?php endif; ?>
    <header>
        <h2><?= $args['category_name'] ?></h2>
    </header><?php endif ?>
    <div class="container"><?php foreach ($materials as $material) : ?>
        <article class="material"><?php if (get_the_post_thumbnail($material)) : ?>
            <?= get_the_post_thumbnail($material, [200, 150]) ?><?php endif; ?>
            <?php 
            $file_arr = get_post_meta($material->ID, 'file');
            $file_url = wp_get_attachment_url($file_arr[0]);
            ?>
            <div class="text">
                <a class="file" href="<?= $file_url ?>"><?= get_the_title($material); ?></a>
                <?= get_post_field('post_content', $material); ?>
            </div>
        </article><?php endforeach; ?>
    </div>
</section>
<?php } ?>