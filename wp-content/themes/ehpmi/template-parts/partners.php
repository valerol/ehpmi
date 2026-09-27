<?php
$requested_post_type = isset( $args['post_type'] ) ? $args['post_type'] : 'member';
$post_type = in_array( $requested_post_type, array( 'member', 'partner' ), true )
    ? $requested_post_type
    : 'member';

$organizations = isset( $args['organizations'] )
    ? $args['organizations']
    : get_posts(
        array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 50,
        )
    );

if ( ! $organizations ) {
    return;
}

$is_directory = ! empty( $args['inner'] ) || is_page( array( 'members', 'partners' ) );
$organization_type = 'organization--' . $post_type;

// ACF stores an attachment ID; use its verified alt metadata rather than a post title.
$organization_image = static function ( $attachment_id, $size = 'post-thumbnail' ) {
    $attachment_id = absint( $attachment_id );
    if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
        return '';
    }

    $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
    return wp_get_attachment_image( $attachment_id, $size, false, array( 'alt' => is_string( $alt ) ? $alt : '' ) );
};
?>
<?php if ( ! $is_directory ) : ?>
<section class="organizations <?php echo esc_attr( $organization_type ); ?> animation-element slide-bottom">
    <header>
        <h2>Member Organizations</h2>
    </header>
    <div id="organization-carousel" class="organization-carousel container ehpmi-carousel" role="region"
         aria-label="Member organizations" aria-roledescription="carousel" data-carousel-loop="true"
         data-carousel-gap="10" data-carousel-medium-breakpoint="600" data-carousel-medium-items="3"
         data-carousel-large-breakpoint="1000" data-carousel-large-items="5">
        <div class="ehpmi-carousel__viewport" tabindex="0">
            <div class="ehpmi-carousel__track">
            <?php foreach ( $organizations as $organization ) : ?>
                <?php
                $organization_id = $organization instanceof WP_Post ? $organization->ID : absint( $organization );
                $site_url        = get_post_meta( $organization_id, 'url', true );
                $logo            = $organization_image( get_post_thumbnail_id( $organization_id ) );
                ?>
                <?php if ( $organization_id && $logo ) : ?>
                <div class="item ehpmi-carousel__item">
                    <?php if ( $site_url ) : ?><a href="<?php echo esc_url( $site_url ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Visit %s website', get_the_title( $organization_id ) ) ); ?>"><?php endif; ?>
                    <?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image escapes attributes. ?>
                    <?php if ( $site_url ) : ?></a><?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            </div>
        </div>
        <div class="ehpmi-carousel__nav" aria-label="Member organization controls">
            <button class="ehpmi-carousel__control ehpmi-carousel__control--prev" type="button" aria-label="Previous member organization"></button>
            <button class="ehpmi-carousel__control ehpmi-carousel__control--next" type="button" aria-label="Next member organization"></button>
        </div>
        <p class="ehpmi-carousel__status screen-reader-text" aria-live="polite"></p>
    </div>
</section>
<?php else : ?>
<section class="organizations <?php echo esc_attr( $organization_type ); ?>">
    <?php if ( isset( $args['heading'] ) ) : ?>
    <header>
        <h2><?php echo esc_html( $args['heading'] ); ?></h2>
    </header>
    <?php endif; ?>
    <div class="container">
        <?php foreach ( $organizations as $organization ) : ?>
            <?php
            $organization_id = $organization instanceof WP_Post ? $organization->ID : absint( $organization );
            if ( ! $organization_id ) {
                continue;
            }

            $site_url         = get_post_meta( $organization_id, 'url', true );
            $additional_image = get_post_meta( $organization_id, 'additional_image', true );
            $logo            = $organization_image( get_post_thumbnail_id( $organization_id ) );
            $content          = get_post_field( 'post_content', $organization_id );
            ?>
        <article class="organization-card organization-card--<?php echo esc_attr( $post_type ); ?>">
            <div class="brand">
                <?php if ( $logo ) : ?>
                    <?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated attachment markup. ?>
                <?php endif; ?>
                <?php if ( $site_url ) : ?>
                    <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a>
                <?php endif; ?>
                <?php if ( $additional_image ) : ?>
                    <?php echo $organization_image( $additional_image ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated attachment markup. ?>
                <?php endif; ?>
            </div>
            <div class="text">
                <h3><?php echo esc_html( get_the_title( $organization_id ) ); ?></h3>
                <?php echo wp_kses_post( $content ); ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
