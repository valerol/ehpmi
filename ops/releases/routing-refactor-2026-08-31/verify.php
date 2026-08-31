<?php
/**
 * Read-only verification for the EHPMI routing-model migration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to verify outside the EHPMI dev site and dev database.' );
}

global $wpdb;

$failures = array();
$check = static function ( $condition, $message ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $message;
    }
};
$count_posts = static function ( $type, $status ) use ( $wpdb ) {
    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
            $type,
            $status
        )
    );
};

$check( '2026-08-31-v1' === get_option( 'ehpmi_routing_migration_version' ), 'Migration version marker is missing.' );
$check( '/blog/%postname%/' === get_option( 'permalink_structure' ), 'Post permalink structure is not /blog/%postname%/.' );
$check( 28 === $count_posts( 'project', 'publish' ), 'Expected 28 published Projects.' );
$check( 13 === $count_posts( 'post', 'publish' ), 'Expected 13 published News Posts.' );
$check( 5 === $count_posts( 'post', 'draft' ), 'Expected 5 retired library intro Posts.' );
$check( 19 === $count_posts( 'material', 'publish' ), 'Expected 19 published Materials.' );
$check( 34 === $count_posts( 'page', 'publish' ), 'Expected 34 published Pages.' );

$plugin = 'ehpmi-core/ehpmi-core.php';
$active_plugins = (array) get_option( 'active_plugins', array() );
$check( in_array( $plugin, $active_plugins, true ), 'EHPMI Core is not active.' );
$check( ! in_array( 'remove-category-url/remove-category-url.php', $active_plugins, true ), 'Remove Category URL is still active.' );

$allow_html_active = false;
foreach ( $active_plugins as $active_plugin ) {
    if ( false !== strpos( $active_plugin, 'allow-html' ) && false !== strpos( $active_plugin, 'category' ) ) {
        $allow_html_active = true;
        break;
    }
}
$check( ! $allow_html_active, 'Allow HTML in Category Descriptions is still active.' );

$project_type  = get_post_type_object( 'project' );
$material_type = get_post_type_object( 'material' );
$check( $project_type && $project_type->public && $project_type->publicly_queryable && $project_type->show_ui, 'Project content type is not public and editable.' );
$check( $material_type && ! $material_type->public && ! $material_type->publicly_queryable && $material_type->show_ui, 'Material content type public/admin flags are incorrect.' );
$check( $material_type && ! $material_type->show_in_rest, 'Materials remain exposed through the public REST API.' );
$check( $material_type && ! post_type_supports( 'material', 'editor' ), 'Material editor support remains enabled.' );
$check( ! is_object_in_taxonomy( 'post', 'category' ), 'Categories remain attached to Posts.' );
$check( ! is_object_in_taxonomy( 'material', 'category' ), 'Categories remain attached to Materials.' );

$expected_pages = array(
    'projects',
    'projects/current',
    'projects/past',
    'projects/potential',
    'blog',
    'blog/news',
    'library',
    'library/health-and-pollution',
    'library/action-plans',
    'library/lead-health-risk-assessment',
    'library/publications-and-co-authored-publications',
    'library/videos',
);
foreach ( $expected_pages as $path ) {
    $page = get_page_by_path( $path, OBJECT, 'page' );
    $check( $page instanceof WP_Post && 'publish' === $page->post_status, 'Required Page /' . $path . '/ is unavailable.' );
}

$intro_map = array(
    'library/action-plans'                          => 2131,
    'library/health-and-pollution'                  => 2136,
    'library/lead-health-risk-assessment'           => 2138,
    'library/publications-and-co-authored-publications' => 2140,
    'library/videos'                                => 2142,
);
foreach ( $intro_map as $path => $source_id ) {
    $page   = get_page_by_path( $path, OBJECT, 'page' );
    $source = get_post( $source_id );
    $check( $page && $source && $page->post_content === $source->post_content, 'Copied content differs for /' . $path . '/.' );
    $check( $source && 'draft' === $source->post_status, 'Library intro source ' . $source_id . ' was not retired.' );
}

$taxonomy_counts = static function ( $taxonomy ) use ( $wpdb ) {
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.slug, COUNT(tr.object_id) AS total
             FROM {$wpdb->term_taxonomy} tt
             INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
             LEFT JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE tt.taxonomy = %s
             GROUP BY t.slug",
            $taxonomy
        ),
        OBJECT_K
    );
    $counts = array();
    foreach ( $rows as $slug => $row ) {
        $counts[ $slug ] = (int) $row->total;
    }
    return $counts;
};

$project_counts = $taxonomy_counts( 'project_status' );
$material_counts = $taxonomy_counts( 'material_type' );
$check( array( 'current' => 6, 'past' => 13, 'potential' => 9 ) === $project_counts, 'Project status counts are incorrect: ' . wp_json_encode( $project_counts ) );
$expected_material_counts = array(
    'action-plans'                              => 3,
    'health-and-pollution'                      => 14,
    'lead-health-risk-assessment'               => 2,
    'publications-and-co-authored-publications' => 0,
    'videos'                                    => 0,
);
ksort( $material_counts );
ksort( $expected_material_counts );
$check(
    $expected_material_counts === $material_counts,
    'Material type counts are incorrect: ' . wp_json_encode( $material_counts )
);

$menu_category_items = (int) $wpdb->get_var(
    "SELECT COUNT(*)
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_menu_item_type' AND pm.meta_value = 'taxonomy'
     INNER JOIN {$wpdb->postmeta} po ON po.post_id = p.ID AND po.meta_key = '_menu_item_object' AND po.meta_value = 'category'
     WHERE p.post_type = 'nav_menu_item'"
);
$menu_page_items = (int) $wpdb->get_var(
    "SELECT COUNT(*)
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_menu_item_type' AND pm.meta_value = 'post_type'
     INNER JOIN {$wpdb->postmeta} po ON po.post_id = p.ID AND po.meta_key = '_menu_item_object' AND po.meta_value = 'page'
     WHERE p.post_type = 'nav_menu_item'"
);
$check( 0 === $menu_category_items, 'Category items remain in navigation menus.' );
$check( $menu_page_items >= 12, 'Expected at least 12 Page navigation items.' );

$redirects = get_option( 'ehpmi_legacy_redirects', array() );
$page_map  = get_option( 'ehpmi_category_page_map', array() );
$check( is_array( $redirects ) && count( $redirects ) >= 100, 'Legacy redirect map is unexpectedly small.' );
$check( is_array( $page_map ) && 12 === count( $page_map ), 'Category-to-Page map must contain 12 routes.' );
$check( isset( $redirects['/category/projects/current/'] ), 'Current-project category redirect is missing.' );
$sample_material = get_posts(
    array(
        'post_type'      => 'material',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    )
);
$sample_material_path = $sample_material ? '/material/' . $sample_material[0]->post_name . '/' : '';
$check( $sample_material_path && isset( $redirects[ $sample_material_path ] ), 'Material redirect sample is missing.' );

$check( 172 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter" ), 'Newsletter subscriber count changed.' );
$check( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter_sent" ), 'Newsletter sent table changed.' );
$check( 7 === $count_posts( 'member', 'publish' ), 'Member records changed.' );
$check( 1 === $count_posts( 'partner', 'publish' ), 'Partner records changed.' );

if ( $failures ) {
    WP_CLI::error( implode( "\n", $failures ) );
}

WP_CLI::success(
    'Verified routing model: ' . wp_json_encode(
        array(
            'projects'         => 28,
            'news'             => 13,
            'materials'        => 19,
            'pages'            => 34,
            'redirects'        => count( $redirects ),
            'newsletter'       => 172,
            'project_statuses' => $project_counts,
            'material_types'   => $material_counts,
        )
    )
);
