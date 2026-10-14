<?php
/** Read-only Stage 3 verifier. Run with WP-CLI from the dev document root. */
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

$failures = array();
$check = static function ( $condition, $label ) use ( &$failures ) {
    if ( ! $condition ) {
        $failures[] = $label;
    }
};
$expected = array(
    'all'       => array( 1988, 1953, 1899, 1888, 1873, 1770, 1690, 1692, 1694, 1545, 1420, 717, 558, 1073, 1113, 1061, 1039, 892, 857, 832, 821, 811, 803, 761, 461, 448, 457, 455 ),
    'current'   => array( 1888, 1873, 1770, 1690, 1692, 1694 ),
    'past'      => array( 1988, 1953, 1899, 1545, 1420, 717, 558, 1073, 1113, 1061, 1039, 857, 761 ),
    'potential' => array( 892, 832, 821, 811, 803, 461, 448, 457, 455 ),
);

$results = array();
foreach ( $expected as $status => $expected_ids ) {
    $query_args = array(
        'post_type'      => 'project',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'paged'          => 1,
    );
    if ( 'all' !== $status ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'project_status',
                'field'    => 'slug',
                'terms'    => $status,
            ),
        );
    }

    $first_page = new WP_Query( $query_args );
    $ids        = array();
    $page_sizes = array();
    for ( $page_number = 1; $page_number <= max( 1, (int) $first_page->max_num_pages ); ++$page_number ) {
        $query_args['paged'] = $page_number;
        $page_query          = 1 === $page_number ? $first_page : new WP_Query( $query_args );
        $page_ids            = array_map( 'intval', wp_list_pluck( $page_query->posts, 'ID' ) );
        $page_sizes[]        = count( $page_ids );
        $ids                 = array_merge( $ids, $page_ids );
        $check( count( $page_ids ) <= 12, $status . ' page ' . $page_number . ' exceeds 12 cards' );
    }

    $check( $ids === $expected_ids, $status . ' ordered IDs' );
    $check( count( $ids ) === count( array_unique( $ids ) ), $status . ' duplicate IDs' );
    $check( (int) $first_page->found_posts === count( $expected_ids ), $status . ' found_posts' );
    $check( (int) $first_page->max_num_pages === (int) ceil( count( $expected_ids ) / 12 ), $status . ' max_num_pages' );
    $results[ $status ] = array(
        'ids'        => $ids,
        'page_sizes' => $page_sizes,
        'pages'      => (int) $first_page->max_num_pages,
    );
}

$partition = array_merge( $expected['current'], $expected['past'], $expected['potential'] );
sort( $partition );
$all_sorted = $expected['all'];
sort( $all_sorted );
$check( $partition === $all_sorted, 'status listings partition all published Projects' );

foreach ( array( 'projects', 'projects/current', 'projects/past', 'projects/potential' ) as $path ) {
    $page = get_page_by_path( $path, OBJECT, 'page' );
    $check( $page instanceof WP_Post && 'publish' === $page->post_status, 'published Page /' . $path . '/' );
    $check( $page instanceof WP_Post && trailingslashit( get_permalink( $page ) ) === 'https://dev.ehpmi.org/' . trailingslashit( $path ), 'canonical Page URL /' . $path . '/' );
}

$original_paged = get_query_var( 'paged' );
set_query_var( 'paged', 2 );
foreach ( array( 'projects', 'projects/current', 'projects/past', 'projects/potential' ) as $path ) {
    $page = get_page_by_path( $path, OBJECT, 'page' );
    $check(
        $page instanceof WP_Post &&
        wp_get_canonical_url( $page ) === 'https://dev.ehpmi.org/' . trailingslashit( $path ) . 'page/2/',
        'paginated canonical URL /' . $path . '/page/2/'
    );
}
set_query_var( 'paged', $original_paged );

$redirects = get_option( 'ehpmi_legacy_redirects', array() );
$rewrite_rules = get_option( 'rewrite_rules', array() );
$check( is_array( $redirects ) && 141 === count( $redirects ), 'exactly 141 legacy redirects' );
$check(
    is_array( $rewrite_rules ) &&
    isset( $rewrite_rules['^projects/page/([0-9]+)/?$'] ) &&
    'index.php?pagename=projects&paged=$matches[1]' === $rewrite_rules['^projects/page/([0-9]+)/?$'],
    'root Project pagination rewrite'
);
$check(
    is_array( $rewrite_rules ) &&
    isset( $rewrite_rules['^projects/(current|past|potential)/page/([0-9]+)/?$'] ) &&
    'index.php?pagename=projects/$matches[1]&paged=$matches[2]' === $rewrite_rules['^projects/(current|past|potential)/page/([0-9]+)/?$'],
    'filtered Project pagination rewrite'
);
$check( '/blog/%postname%/' === get_option( 'permalink_structure' ), 'permalink structure' );
$check( taxonomy_exists( 'project_status' ) && ! is_taxonomy_viewable( 'project_status' ), 'private project_status taxonomy' );
$check( '' === (string) $GLOBALS['wpdb']->last_error, 'database read error' );

$report = array(
    'result'   => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'listings' => $results,
    'redirects' => is_array( $redirects ) ? count( $redirects ) : -1,
    'project_pagination_rewrites' => array(
        'root'     => is_array( $rewrite_rules ) && isset( $rewrite_rules['^projects/page/([0-9]+)/?$'] ),
        'filtered' => is_array( $rewrite_rules ) && isset( $rewrite_rules['^projects/(current|past|potential)/page/([0-9]+)/?$'] ),
    ),
);
WP_CLI::line( 'STAGE3_VERIFY_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
    WP_CLI::error( 'Stage 3 verifier failed.' );
}
