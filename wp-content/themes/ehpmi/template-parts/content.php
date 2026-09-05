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
$is_news_post     = is_singular( 'post' );
?>
<article <?php post_class( 'main-article' . ( $is_staff_member ? ' staff' : '' ) ); ?> id="post-<?php the_ID(); ?>">
    <header class="entry-header">
        <div class="container">
            <h1><?php the_title(); ?></h1>
            <?php if ( $is_news_post ) : ?>
                <p class="entry-meta">
                    <time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></time>
                </p>
            <?php endif; ?>
        </div>
    </header>
    <div class="container">
        <?php if (get_the_post_thumbnail() && $is_staff_member) : ?>
            <div class="image">
                <?php echo get_the_post_thumbnail(); ?>
            </div>
        <?php endif; ?>
        <div class="text entry-content"><?php if ($is_staff_member) : ?>
            <p class="position"><?php echo esc_html( get_the_excerpt() ); ?></p><?php elseif ( $is_news_post && has_excerpt() ) : ?>
            <div class="entry-summary"><?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?></div><?php endif; ?>
            <?php the_content(); ?>
        </div>
    </div>
</article>
