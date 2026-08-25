<?php
/**
 * The default template for displaying content
 *
 * Used for both singular and index.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */
 
$category = get_category_by_slug($post->post_name);
$is_staff_member = (get_post_type() === 'staff_member');
?>
<?php if (empty($category)) :?>
<article class="main-article<?= $is_staff_member ? ' staff' : '' ?>">
    <header>
        <div class="container">
            <h1><?php the_title(); ?></h1>
        </div>
    </header>
    <div class="container">
        <?php if (get_the_post_thumbnail() && $is_staff_member) : ?>
            <div class="image">
                <?= get_the_post_thumbnail() ?>
            </div>
        <?php endif; ?>
        <div class="text"><?php if ($is_staff_member) : ?>
            <p class="position"><?= get_the_excerpt(); ?></p><?php else: ?>
            <?= get_the_excerpt(); ?><?php endif ?>
            <?php the_content(); ?>
        </div>
    </div>
</article>
<?php endif ?>
