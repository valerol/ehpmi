<?php
$leader = $args['leader_id'] ? get_post($args['leader_id']) : '';

if (!empty($leader)) : 
?>
<article class="country-leader">
    <header>
        <h2><?= get_the_title($leader->ID); ?></h2>
        <p class="position">The Country Coordinator</p>
    </header>
    <div class="container">
        <div class="image">
            <?= get_the_post_thumbnail($leader->ID) ?>
        </div>
        <div class="text">
            <?= get_post_field('post_content', $leader->ID); ?>
        </div>
    </div>
</article>
<?php endif; ?>
