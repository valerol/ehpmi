<?php
/** Read-only Stage 4 Material catalogue verifier. Run with WP-CLI from dev root. */
if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WP_CLI' ) ) {
    exit( 1 );
}
if (
    '/home2/nykvymmy/dev.ehpmi.org' !== realpath( ABSPATH ) ||
    'nykvymmy_ehpmidev' !== DB_NAME ||
    'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) ||
    'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'siteurl' ) )
) {
    WP_CLI::error( 'Wrong installation.' );
}

global $wpdb;
$failures = array();
$check = static function ( $condition, $label ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $label;
    }
};

$expected = array(
    'all' => array( null, array( 1852, 1739, 532, 529, 526, 523, 520, 516, 513, 510, 507, 504, 501, 498, 494, 491, 488, 485, 482 ), array( 12, 7 ) ),
    'health-and-pollution' => array( 'health-and-pollution', array( 523, 520, 516, 513, 510, 507, 504, 501, 498, 494, 491, 488, 485, 482 ), array( 12, 2 ) ),
    'action-plans' => array( 'action-plans', array( 1852, 529, 526 ), array( 3 ) ),
    'lead-health-risk-assessment' => array( 'lead-health-risk-assessment', array( 1739, 532 ), array( 2 ) ),
    'publications-and-co-authored-publications' => array( 'publications-and-co-authored-publications', array(), array( 0 ) ),
    'videos' => array( 'videos', array(), array( 0 ) ),
);
$listings = array();
foreach ( $expected as $label => $definition ) {
    $args = array(
        'post_type' => 'material',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => 1,
        'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
    );
    if ( null !== $definition[0] ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'material_type', 'field' => 'slug', 'terms' => $definition[0] ),
        );
    }
    $first = new WP_Query( $args );
    $ids = array();
    $sizes = array();
    for ( $page = 1; $page <= max( 1, (int) $first->max_num_pages ); ++$page ) {
        $args['paged'] = $page;
        $query = 1 === $page ? $first : new WP_Query( $args );
        $page_ids = array_map( 'intval', wp_list_pluck( $query->posts, 'ID' ) );
        $ids = array_merge( $ids, $page_ids );
        $sizes[] = count( $page_ids );
    }
    $check( $definition[1] === $ids, $label . ' ordered IDs' );
    $check( $definition[2] === $sizes, $label . ' page sizes' );
    $check( count( $ids ) === count( array_unique( $ids ) ), $label . ' duplicate IDs' );
    $check( count( $definition[1] ) === (int) $first->found_posts, $label . ' found_posts' );
    $listings[ $label ] = array( 'ids' => $ids, 'page_sizes' => $sizes );
}

$file_ids = array(
    482 => 483, 485 => 486, 488 => 489, 491 => 492, 494 => 495,
    498 => 1105, 501 => 1106, 504 => 505, 507 => 508, 510 => 511,
    513 => 514, 516 => 517, 520 => 521, 523 => 524, 526 => 527,
    529 => 530, 532 => 533, 1739 => 1740, 1852 => 1855,
);
$thumbnail_ids = array(
    482 => 484, 485 => 487, 488 => 490, 491 => 493, 494 => 1885,
    498 => 1535, 501 => 1535, 504 => 506, 507 => 509, 510 => 512,
    513 => 515, 516 => 518, 520 => 522, 523 => 525, 526 => 528,
    529 => 531, 532 => 1887, 1739 => 1743, 1852 => 1854,
);
$published_ids = array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'material' AND post_status = 'publish' ORDER BY ID ASC" ) );
$expected_ids = array_keys( $file_ids );
sort( $expected_ids );
$check( $expected_ids === $published_ids, 'exact published Material record IDs' );
$file_results = array();
foreach ( $file_ids as $material_id => $file_id ) {
    $material = get_post( $material_id );
    $types = wp_get_object_terms( $material_id, 'material_type', array( 'fields' => 'slugs' ) );
    $file = function_exists( 'ehpmi_core_material_file_details' ) ? ehpmi_core_material_file_details( $material_id ) : array();
    $check( $material instanceof WP_Post && 'publish' === $material->post_status, 'published Material ' . $material_id );
    $check( 0 === (int) $material->menu_order, 'menu_order zero ' . $material_id );
    $check( ! is_wp_error( $types ) && 1 === count( $types ), 'one material_type ' . $material_id );
    $check( $file_id === (int) get_post_meta( $material_id, 'file', true ), 'file relationship ' . $material_id );
    $check( $thumbnail_ids[ $material_id ] === (int) get_post_thumbnail_id( $material_id ), 'thumbnail relationship ' . $material_id );
    $check( ! empty( $file['available'] ) && $file_id === (int) $file['id'], 'file available ' . $material_id );
    $check( ! empty( $file['path'] ) && is_file( $file['path'] ), 'physical file ' . $material_id );
    $check( ! empty( $file['bytes'] ) && ! empty( $file['label'] ), 'derived file metadata ' . $material_id );
    $file_results[ $material_id ] = array(
        'file_id' => isset( $file['id'] ) ? (int) $file['id'] : 0,
        'thumbnail_id' => (int) get_post_thumbnail_id( $material_id ),
        'mime_type' => isset( $file['mime_type'] ) ? $file['mime_type'] : '',
        'bytes' => isset( $file['bytes'] ) ? (int) $file['bytes'] : 0,
        'label' => isset( $file['label'] ) ? $file['label'] : '',
    );
}
$check( 501 === (int) get_post( 1535 )->post_parent, 'shared thumbnail parent preserved' );
$check( 0 === (int) get_post( 1885 )->post_parent, 'thumbnail 1885 remains unparented' );
$check( 0 === (int) get_post( 1743 )->post_parent, 'thumbnail 1743 remains unparented' );

$paths = array(
    'library',
    'library/health-and-pollution',
    'library/action-plans',
    'library/lead-health-risk-assessment',
    'library/publications-and-co-authored-publications',
    'library/videos',
);
foreach ( $paths as $path ) {
    $page = get_page_by_path( $path, OBJECT, 'page' );
    $check( $page instanceof WP_Post && 'publish' === $page->post_status, 'published Page /' . $path . '/' );
}
$old_paged = get_query_var( 'paged' );
set_query_var( 'paged', 2 );
foreach ( array( 'library', 'library/health-and-pollution' ) as $path ) {
    $page = get_page_by_path( $path, OBJECT, 'page' );
    $check(
        $page instanceof WP_Post &&
        'https://dev.ehpmi.org/' . trailingslashit( $path ) . 'page/2/' === wp_get_canonical_url( $page ),
        'paginated canonical /' . $path . '/page/2/'
    );
}
set_query_var( 'paged', $old_paged );

$post_type = get_post_type_object( 'material' );
$taxonomy = get_taxonomy( 'material_type' );
$check( $post_type && ! $post_type->publicly_queryable && $post_type->exclude_from_search && ! $post_type->show_in_rest && false === $post_type->has_archive, 'private Material post type' );
$check( $taxonomy && ! $taxonomy->publicly_queryable && ! $taxonomy->show_in_rest, 'private material_type taxonomy' );
$field = function_exists( 'acf_get_field' ) ? acf_get_field( 'field_6371e86e1de40' ) : null;
$check( is_array( $field ) && 1 === (int) $field['required'], 'ACF Material File required' );

$rules = get_option( 'rewrite_rules', array() );
$root_rule = '^library/page/([0-9]+)/?$';
$type_rule = '^library/(health-and-pollution|action-plans|lead-health-risk-assessment|publications-and-co-authored-publications|videos)/page/([0-9]+)/?$';
$check( isset( $rules[ $root_rule ] ) && 'index.php?pagename=library&paged=$matches[1]' === $rules[ $root_rule ], 'root Library pagination rewrite' );
$check( isset( $rules[ $type_rule ] ) && 'index.php?pagename=library/$matches[1]&paged=$matches[2]' === $rules[ $type_rule ], 'typed Library pagination rewrite' );

$redirects = get_option( 'ehpmi_legacy_redirects', array() );
foreach ( $file_ids as $material_id => $file_id ) {
    $path = '/material/' . get_post_field( 'post_name', $material_id ) . '/';
    $check( isset( $redirects[ $path ] ) && wp_get_attachment_url( $file_id ) === $redirects[ $path ], 'legacy Material redirect ' . $material_id );
}
$check( '/blog/%postname%/' === get_option( 'permalink_structure' ), 'permalink structure' );
$check( '' === (string) $wpdb->last_error, 'database read error' );

$probe_filter = static function ( $value, $object_id, $meta_key, $single ) {
    if ( 482 === (int) $object_id && "file" === $meta_key ) {
        return $single ? 0 : array();
    }
    return $value;
};
add_filter( "get_post_metadata", $probe_filter, 10, 4 );
ob_start();
get_template_part(
    "template-parts/library",
    "",
    array( "materials" => array( get_post( 482 ) ), "show_heading" => false, "heading" => "Unavailable probe" )
);
$unavailable_markup = ob_get_clean();
remove_filter( "get_post_metadata", $probe_filter, 10 );
$check( false !== strpos( $unavailable_markup, "material--unavailable" ), "unavailable card class" );
$check( false !== strpos( $unavailable_markup, "File temporarily unavailable" ), "unavailable card text" );
$check( false !== strpos( $unavailable_markup, "<h2 class=\"file\">" ), "unavailable semantic heading" );
$check( false === strpos( $unavailable_markup, "<a class=\"material__primary\"" ), "unavailable card has no primary link" );

$report = array(
    'result' => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'listings' => $listings,
    'materials' => count( $file_ids ),
    'files' => $file_results,
    'material_redirects' => count( $file_ids ),
);
WP_CLI::line( 'STAGE4_VERIFY_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
    WP_CLI::error( 'Stage 4 verifier failed.' );
}
