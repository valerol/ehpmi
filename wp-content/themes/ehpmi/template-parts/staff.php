<?php

$staff_page = $post->post_name === 'staff';

if ($staff_page) {
    
    $members = get_posts([
        'post_type'      => 'staff_member',
        'posts_per_page' => -1,
        'orderby'        => 'date',   // order by post date
        'order'          => 'DESC',   // newest first
    ]);
    
} else {
    
    $members = get_posts([
        'post_type'      => 'staff_member',
        'post__in'       => $args['staff_members'],
        'posts_per_page' => -1,
    ]);
}

global $post;

?>

<section class="staff <?= $staff_page ? 'inner' : 'animation-element slide-left' ?><?= isset($args['classes']) ? ' ' . $args['classes'] : '' ?>">
    <?php if (! $staff_page) : ?>
        <h2><?= 'EHPMI Member Organization' ?></h2>
    <?php endif ?>
    
    <div class="container">
        <?php if ( ! empty( $members ) ) : ?>
            <?php foreach ( $members as $post ) : setup_postdata( $post ); ?>
                <a class="staff-block" href="<?php the_permalink(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="image">
                            <?php the_post_thumbnail(); ?>
                        </div>
                    <?php endif; ?>
                    <div class="staff-text">
                        <h3 class="title"><?php the_title(); ?></h3>
                        <?php if ( get_the_excerpt() ) : ?>
                            <p class="position"><?php the_excerpt(); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; wp_reset_postdata(); ?>
        <?php else : ?>
            <p>No staff members found.</p>
        <?php endif; ?>
    </div>
</section>