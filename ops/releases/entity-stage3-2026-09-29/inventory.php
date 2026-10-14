<?php
/** Read-only Stage 3 inventory. Run with WP-CLI before and after deployment. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
    exit( 1 );
}
if (
    '/home2/nykvymmy/dev.ehpmi.org' !== realpath( ABSPATH ) ||
    'nykvymmy_ehpmidev' !== DB_NAME ||
    'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) )
) {
    WP_CLI::error( 'Wrong installation.' );
}

$projects = get_posts(
    array(
        'post_type'      => 'project',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    )
);
$records = array();
foreach ( $projects as $project ) {
    $statuses = wp_get_object_terms( $project->ID, 'project_status', array( 'fields' => 'slugs' ) );
    sort( $statuses );
    $meta = get_post_meta( $project->ID );
    ksort( $meta );
    $records[] = array(
        'id'             => (int) $project->ID,
        'status'         => $project->post_status,
        'slug'           => $project->post_name,
        'parent'         => (int) $project->post_parent,
        'menu_order'     => (int) $project->menu_order,
        'published_gmt'  => $project->post_date_gmt,
        'modified_gmt'   => $project->post_modified_gmt,
        'title_sha256'   => hash( 'sha256', $project->post_title ),
        'excerpt_sha256' => hash( 'sha256', $project->post_excerpt ),
        'content_sha256' => hash( 'sha256', $project->post_content ),
        'meta_sha256'    => hash( 'sha256', serialize( $meta ) ),
        'thumbnail_id'   => (int) get_post_thumbnail_id( $project->ID ),
        'project_status' => $statuses,
        'url'            => get_permalink( $project ),
    );
}

$pages = array();
foreach ( array( 'projects', 'projects/current', 'projects/past', 'projects/potential' ) as $path ) {
    $page = get_page_by_path( $path, OBJECT, 'page' );
    $pages[ $path ] = $page instanceof WP_Post
        ? array(
            'id'           => (int) $page->ID,
            'status'       => $page->post_status,
            'content_hash' => hash( 'sha256', $page->post_content ),
            'url'          => get_permalink( $page ),
        )
        : null;
}

$redirects = get_option( 'ehpmi_legacy_redirects', array() );
if ( is_array( $redirects ) ) {
    ksort( $redirects );
}
$report = array(
    'database'                => DB_NAME,
    'home'                    => untrailingslashit( get_option( 'home' ) ),
    'project_records'         => $records,
    'project_pages'           => $pages,
    'legacy_redirect_count'   => is_array( $redirects ) ? count( $redirects ) : -1,
    'legacy_redirect_sha256'  => is_array( $redirects ) ? hash( 'sha256', serialize( $redirects ) ) : '',
    'permalink_structure'     => get_option( 'permalink_structure' ),
);
WP_CLI::line( 'STAGE3_INVENTORY_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
