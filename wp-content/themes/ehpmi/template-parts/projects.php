<?php
$projects = isset($args['projects']) ? $args['projects'] : get_posts(array('numberposts' => 3, 'category_name' => 'projects'));
?>
<section class="news <?= isset($args['category_name']) ? 
    'inner' :
    'animation-element slide-left' ?>">
    <?php if (isset($args['breadcrumbs'])) : ?><div id="breadcrumbs" class="container"><?= $args['breadcrumbs'] ?></div><?php endif; ?>
    <header>
        <?= isset($args['category_name']) ? "<h1>" . $args['category_name'] . "</h1>" : "<h2>Our Projects</h2>" ?>
    </header>
    <div class="container">
        <?php foreach ($projects as $project) : ?>
        <div class="news-block">
            <?php if (get_the_post_thumbnail($project->ID)) : ?>
            <div class="image">
                <?= get_the_post_thumbnail($project->ID, [530,530]) ?>
            </div>
            <?php endif; ?>
            <div class="news-text">
                <h3 class="title"><a href="<?= esc_url(get_post_permalink($project->ID, true)) ?>"><?= esc_html($project->post_title) ?></a></h3>
                <p class="description"><?= $project->post_excerpt ? $project->post_excerpt :
                        get_the_content(null, true, $project->ID) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
