<?php if (get_post_type() == 'page' and isset($args['siblings'])) : ?>
<aside>
    <h2>See also</h2>
    <ul class="page-nav content">
    <?php foreach ($args['siblings'] as $sibling) : ?>
    <?php if ($sibling->ID != $post->ID) : ?>
        <li>
            <a href="<?= get_permalink($sibling->ID) ?>"><?= $sibling->post_title ?></a>
        </li>
    <?php endif; ?>
    <?php endforeach; ?>
    </ul>
</aside>
<?php endif; ?>
