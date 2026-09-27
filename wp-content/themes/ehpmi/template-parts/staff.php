<?php

global $post;

$staff_page = isset( $post->post_name ) && 'staff' === $post->post_name;

if ( $staff_page ) {
    $members = get_posts(
        array(
            'post_type'      => 'staff_member',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => array(
                'menu_order' => 'ASC',
                'ID'         => 'ASC',
            ),
        )
    );
} else {
    $staff_ids = array_values( array_filter( array_map( 'intval', $args['staff_members'] ?? array() ) ) );
    $members   = get_posts(
        array(
            'post_type'      => 'staff_member',
            'post_status'    => 'publish',
            'post__in'       => $staff_ids,
            'posts_per_page' => -1,
            'orderby'        => 'post__in',
            'order'          => 'ASC',
        )
    );
}

?>

<section class="staff <?php echo esc_attr( $staff_page ? 'inner' : 'animation-element slide-left' ); ?><?php echo isset( $args['classes'] ) ? ' ' . esc_attr( $args['classes'] ) : ''; ?>">
    <?php if ( ! $staff_page ) : ?>
        <h2><?php esc_html_e( 'EHPMI Member Organization', 'ehpmi' ); ?></h2>
    <?php endif; ?>

    <div class="container">
        <?php if ( ! empty( $members ) ) : ?>
            <?php foreach ( $members as $post ) : setup_postdata( $post ); ?>
                <?php
                $staff_id = get_the_ID();
                $position = (string) get_post_meta( $staff_id, 'position', true );
                ?>
                <a id="staff-card-<?php echo esc_attr( $staff_id ); ?>" class="staff-block staff-card" href="<?php the_permalink(); ?>">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="image">
                            <?php
                            $thumbnail_id = get_post_thumbnail_id( $staff_id );
                            $image_alt     = (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
                            echo wp_get_attachment_image(
                                $thumbnail_id,
                                'post-thumbnail',
                                false,
                                array( 'alt' => '' !== trim( $image_alt ) ? $image_alt : get_the_title( $staff_id ) )
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                    <div class="staff-text">
                        <h3 class="title"><?php the_title(); ?></h3>
                        <?php if ( '' !== trim( $position ) ) : ?>
                            <p class="position"><?php echo esc_html( $position ); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; wp_reset_postdata(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No staff members found.', 'ehpmi' ); ?></p>
        <?php endif; ?>
    </div>
</section>
