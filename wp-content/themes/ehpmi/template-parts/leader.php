<?php
$leader_id = isset( $args['leader_id'] ) ? (int) $args['leader_id'] : 0;
$leader    = $leader_id ? get_post( $leader_id ) : null;

if ( $leader instanceof WP_Post && 'staff_member' === $leader->post_type ) :
    $country_role = trim( (string) get_post_meta( $leader->ID, 'country_role', true ) );
    if ( '' === $country_role ) {
        // Documented Stage 2 fallback for unmigrated or newly incomplete records.
        $country_role = __( 'The Country Coordinator', 'ehpmi' );
    }
    $thumbnail_id = get_post_thumbnail_id( $leader->ID );
    $image_alt    = $thumbnail_id ? (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) : '';
?>
<article id="country-leader-<?php echo esc_attr( $leader->ID ); ?>" class="country-leader staff-profile staff-profile--leader">
    <header>
        <h2><?php echo esc_html( get_the_title( $leader->ID ) ); ?></h2>
        <p class="position"><?php echo esc_html( $country_role ); ?></p>
    </header>
    <div class="container">
        <div class="image">
            <?php
            echo get_the_post_thumbnail(
                $leader->ID,
                'post-thumbnail',
                array( 'alt' => '' !== trim( $image_alt ) ? $image_alt : get_the_title( $leader->ID ) )
            );
            ?>
        </div>
        <div class="text entry-content">
            <?php echo wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $leader->ID ) ) ); ?>
        </div>
    </div>
</article>
<?php endif; ?>
