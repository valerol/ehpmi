<?php
$is_inner = ! empty( $args['inner'] );
$heading  = isset( $args['heading'] ) ? $args['heading'] : __( 'Our Projects', 'ehpmi' );
$show_heading = ! isset( $args['show_heading'] ) || (bool) $args['show_heading'];

if ( isset( $args['projects'] ) && is_array( $args['projects'] ) ) {
    $projects = $args['projects'];
} else {
    $query_args = array(
        'post_type'      => 'project',
        'post_status'    => 'publish',
        'posts_per_page' => isset( $args['numberposts'] ) ? (int) $args['numberposts'] : 3,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( ! empty( $args['project_status'] ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'project_status',
                'field'    => 'slug',
                'terms'    => sanitize_key( $args['project_status'] ),
            ),
        );
    }

    $projects = get_posts( $query_args );
}
?>
<section class="news <?php echo $is_inner ? 'inner' : 'animation-element slide-left'; ?>">
    <?php if ( $show_heading ) : ?>
    <header>
        <?php if ( $is_inner ) : ?>
            <h1><?php echo esc_html( $heading ); ?></h1>
        <?php else : ?>
            <h2><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>
    </header>
    <?php endif; ?>
    <div class="container">
        <?php foreach ($projects as $project) : ?>
        <div class="news-block">
            <?php if (get_the_post_thumbnail($project->ID)) : ?>
            <div class="image">
                <?= get_the_post_thumbnail($project->ID, 'thumbnail') ?>
            </div>
            <?php endif; ?>
            <div class="news-text">
                <h3 class="title"><a href="<?php echo esc_url( get_permalink( $project ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h3>
                <div class="description"><?php echo $project->post_excerpt
                    ? wp_kses_post( wpautop( $project->post_excerpt ) )
                    : wp_kses_post( apply_filters( 'the_content', $project->post_content ) ); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
