<?php
$heading      = isset( $args['heading'] ) ? $args['heading'] : __( 'Library', 'ehpmi' );
$show_heading = ! isset( $args['show_heading'] ) || (bool) $args['show_heading'];
$paginate     = ! empty( $args['paginate'] );
$paged        = $paginate
    ? max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) )
    : 1;
$materials_query = null;

if ( isset( $args['materials'] ) && is_array( $args['materials'] ) ) {
    $materials = $args['materials'];
} else {
    $query_args = array(
        'post_type'      => 'material',
        'post_status'    => 'publish',
        'posts_per_page' => isset( $args['numberposts'] ) ? (int) $args['numberposts'] : 50,
        'paged'          => $paged,
        'orderby'        => array(
            'date' => 'DESC',
            'ID'   => 'DESC',
        ),
    );

    if ( ! empty( $args['material_type'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'material_type',
                'field'    => 'slug',
                'terms'    => sanitize_key( $args['material_type'] ),
            ),
        );
    }

    $materials_query = new WP_Query( $query_args );
    $materials       = $materials_query->posts;
}

if ( $materials ) :
?>
<section class="materials<?php echo $paginate ? ' materials--paginated' : ''; ?>"<?php echo $show_heading ? '' : ' aria-label="' . esc_attr( $heading ) . '"'; ?>>
    <?php if ( $show_heading ) : ?>
    <header>
        <h1><?php echo esc_html( $heading ); ?></h1>
    </header>
    <?php endif; ?>
    <div class="container">
        <?php foreach ( $materials as $material ) : ?>
        <?php
        $file = function_exists( 'ehpmi_core_material_file_details' )
            ? ehpmi_core_material_file_details( $material->ID )
            : array(
                'available' => false,
                'url'       => '',
                'label'     => '',
            );
        $thumbnail = get_the_post_thumbnail(
            $material->ID,
            array( 200, 150 ),
            array( 'alt' => '' )
        );
        ?>
        <article class="material<?php echo $file['available'] ? '' : ' material--unavailable'; ?>" id="material-card-<?php echo esc_attr( $material->ID ); ?>">
            <?php if ( $file['available'] ) : ?>
            <a class="material__primary" href="<?php echo esc_url( $file['url'] ); ?>">
                <?php if ( $thumbnail ) : ?>
                <div class="material__image" aria-hidden="true"><?php echo wp_kses_post( $thumbnail ); ?></div>
                <?php endif; ?>
                <div class="text">
                    <h2 class="file"><?php echo esc_html( get_the_title( $material ) ); ?></h2>
                    <span class="material__meta"><?php echo esc_html( $file['label'] ); ?></span>
                </div>
            </a>
            <?php else : ?>
            <div class="material__primary">
                <?php if ( $thumbnail ) : ?>
                <div class="material__image" aria-hidden="true"><?php echo wp_kses_post( $thumbnail ); ?></div>
                <?php endif; ?>
                <div class="text">
                    <h2 class="file"><?php echo esc_html( get_the_title( $material ) ); ?></h2>
                    <span class="material__meta"><?php esc_html_e( 'File temporarily unavailable', 'ehpmi' ); ?></span>
                </div>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
        <?php if ( $paginate && $materials_query instanceof WP_Query && $materials_query->max_num_pages > 1 ) : ?>
        <nav class="news-pagination material-pagination" aria-label="<?php esc_attr_e( 'Library pages', 'ehpmi' ); ?>">
            <?php
            echo wp_kses_post(
                paginate_links(
                    array(
                        'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                        'current'   => $paged,
                        'total'     => (int) $materials_query->max_num_pages,
                        'mid_size'  => 1,
                        'end_size'  => 1,
                        'prev_text' => __( 'Previous', 'ehpmi' ),
                        'next_text' => __( 'Next', 'ehpmi' ),
                        'type'      => 'list',
                    )
                )
            );
            ?>
        </nav>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
