<?php
$offices_page = get_page_by_path( 'offices' );
$offices_id   = $offices_page instanceof WP_Post ? $offices_page->ID : 0;
$is_offices   = is_page( 'offices' );

$countries_args = array(
    'post_parent' => $offices_id,
    'post_type'   => 'page',
    'post_status' => 'publish'
);

$countries = $offices_id ? get_children( $countries_args ) : array();
?>
<section class="map<?php echo $is_offices ? ' inner' : ' animation-element slide-bottom'; ?>">
    <div class="container">
        <div class="image">
            <img class="map-image" src="<?php echo esc_url( get_theme_file_uri( '/images/map9-01.webp' ) ); ?>" alt="map"><?php
            if (!empty($countries)) {
                foreach ($countries as $country) {
                    $country_point = get_post_meta($country->ID, 'point', true);
                    if (!empty($country_point)) { ?>
            <details class="point point-<?php echo absint( $country_point ); ?>"><summary class="marker"></summary>
                <p class="address"><a href="<?php echo esc_url( get_permalink( $country->ID ) ); ?>"><?php echo esc_html( get_the_title( $country->ID ) ); ?></a></p>
            </details><?php
                    }
                }
            } ?>
        </div>
        <?php if ( ! $is_offices ) : ?>
            <?php dynamic_sidebar( 'map-text' ); ?>
        <?php endif; ?>
    </div>
</section>
