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
$is_staff_member = (get_post_type() === 'staff_member');
?>
<article class="main-article<?php echo $is_staff_member ? ' staff' : ''; ?>">
    <header>
        <div class="container">
            <h1><?php the_title(); ?></h1>
        </div>
    </header>
    <div class="container">
        <?php if (get_the_post_thumbnail() && $is_staff_member) : ?>
            <div class="image">
                <?php echo get_the_post_thumbnail(); ?>
            </div>
        <?php endif; ?>
        <div class="text"><?php if ($is_staff_member) : ?>
            <p class="position"><?php echo esc_html( get_the_excerpt() ); ?></p><?php else: ?>
            <?php echo wp_kses_post( get_the_excerpt() ); ?><?php endif ?>
            <?php the_content(); ?>
        </div>
    </div>
</article>
