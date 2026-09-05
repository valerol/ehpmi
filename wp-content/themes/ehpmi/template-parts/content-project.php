<?php
/**
 * Semantic single-project content.
 */

$project_id = get_the_ID();
$intro      = (string) get_post_meta( $project_id, 'project_intro', true );
$fact_map   = array(
    'project_dates'        => __( 'Project dates', 'ehpmi' ),
    'people_at_risk'       => __( 'People at risk', 'ehpmi' ),
    'pollution_source'     => __( 'Pollution source', 'ehpmi' ),
    'project_implementers' => __( 'Implementers', 'ehpmi' ),
    'project_budget'       => __( 'Budget', 'ehpmi' ),
    'project_funding'      => __( 'Funding', 'ehpmi' ),
);
$facts      = array();

foreach ( $fact_map as $meta_key => $label ) {
    $value = trim( (string) get_post_meta( $project_id, $meta_key, true ) );
    if ( '' !== $value ) {
        $facts[ $label ] = $value;
    }
}

$has_structured_summary = '' !== trim( wp_strip_all_tags( $intro ) ) || ! empty( $facts );
?>
<article <?php post_class( 'main-article project-article' ); ?> id="post-<?php the_ID(); ?>">
    <header class="entry-header">
        <div class="container">
            <h1><?php the_title(); ?></h1>
        </div>
    </header>
    <div class="container">
        <div class="text entry-content">
            <?php if ( $has_structured_summary ) : ?>
                <div class="entry-summary project-summary">
                    <?php if ( '' !== trim( wp_strip_all_tags( $intro ) ) ) : ?>
                        <div class="project-summary__intro"><?php echo wp_kses_post( wpautop( $intro ) ); ?></div>
                    <?php endif; ?>
                    <?php if ( $facts ) : ?>
                        <dl class="project-facts">
                            <?php foreach ( $facts as $label => $value ) : ?>
                                <div class="project-facts__item">
                                    <dt><?php echo esc_html( $label ); ?></dt>
                                    <dd><?php echo esc_html( $value ); ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </div>
            <?php elseif ( has_excerpt() ) : ?>
                <div class="entry-summary project-summary project-summary--legacy"><?php echo wp_kses_post( wpautop( get_the_excerpt() ) ); ?></div>
            <?php endif; ?>
            <?php the_content(); ?>
        </div>
    </div>
</article>
