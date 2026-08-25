<?php if (get_post_type() == 'page' and isset($args['children'])) : ?>
<section class="subpages">
    <div class="container">
        <ul class="page-nav">
            <?php foreach ($args['children'] as $child) : ?>
                <?php if ($child->ID != $post->ID) : ?>
                    <li>
                        <a href="<?= get_permalink($child->ID) ?>"><?= $child->post_title ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>    
</section>
<?php endif; ?>
