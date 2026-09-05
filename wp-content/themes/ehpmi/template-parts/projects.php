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
<section class="news projects <?php echo $is_inner ? 'inner' : 'animation-element slide-left'; ?>"<?php echo $show_heading ? '' : ' aria-label="' . esc_attr( $heading ) . '"'; ?>>
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
        <article <?php post_class( 'news-block', $project->ID ); ?> id="project-card-<?php echo esc_attr( $project->ID ); ?>">
            <?php if (get_the_post_thumbnail($project->ID)) : ?>
            <div class="image">
                <?= get_the_post_thumbnail($project->ID, 'thumbnail') ?>
            </div>
            <?php endif; ?>
            <div class="news-text">
                <?php if ( $is_inner ) : ?>
                    <h2 class="title"><a href="<?php echo esc_url( get_permalink( $project ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h2>
                <?php else : ?>
                    <h3 class="title"><a href="<?php echo esc_url( get_permalink( $project ) ); ?>"><?php echo esc_html( $project->post_title ); ?></a></h3>
                <?php endif; ?>
                <div class="description"><?php echo $project->post_excerpt
                    ? wp_kses_post( wpautop( $project->post_excerpt ) )
                    : '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( strip_shortcodes( $project->post_content ) ), 55 ) ) . '</p>'; ?></div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
