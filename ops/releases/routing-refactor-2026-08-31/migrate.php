<?php
/**
 * Dev-only migration from structural Categories to Pages and private filters.
 *
 * Run from the WordPress document root:
 *   wp eval-file /absolute/path/to/migrate.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Run this file through WP-CLI.\n" );
    exit( 1 );
}

if ( 'https://dev.ehpmi.org' !== untrailingslashit( get_option( 'home' ) ) || 'nykvymmy_ehpmidev' !== DB_NAME ) {
    WP_CLI::error( 'Refusing to run outside the EHPMI dev site and dev database.' );
}

global $wpdb;

$migration_version = '2026-08-31-v1';
if ( $migration_version === get_option( 'ehpmi_routing_migration_version' ) ) {
    WP_CLI::success( 'Routing model is already migrated; no changes made.' );
    return;
}

$fail = static function ( $condition, $message ) {
    if ( ! $condition ) {
        throw new RuntimeException( $message );
    }
};

$post_ids = static function ( $post_type, $post_status = 'publish' ) use ( $wpdb ) {
    return array_map(
        'intval',
        $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s ORDER BY ID",
                $post_type,
                $post_status
            )
        )
    );
};

$category_object_ids = static function ( $slugs, $post_type = null ) use ( $wpdb ) {
    $slugs        = array_values( array_unique( array_map( 'sanitize_title', (array) $slugs ) ) );
    $placeholders = implode( ',', array_fill( 0, count( $slugs ), '%s' ) );
    $params       = $slugs;
    $sql          = "SELECT DISTINCT tr.object_id
                     FROM {$wpdb->term_relationships} tr
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                     INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                     INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
                     WHERE tt.taxonomy = 'category'
                       AND t.slug IN ({$placeholders})";

    if ( $post_type ) {
        $sql     .= ' AND p.post_type = %s';
        $params[] = $post_type;
    }

    $sql .= ' ORDER BY tr.object_id';

    return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( $sql, $params ) ) );
};

$category_slugs_for_object = static function ( $object_id ) use ( $wpdb ) {
    return $wpdb->get_col(
        $wpdb->prepare(
            "SELECT t.slug
             FROM {$wpdb->term_relationships} tr
             INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
             WHERE tr.object_id = %d AND tt.taxonomy = 'category'
             ORDER BY t.term_id",
            $object_id
        )
    );
};

$ensure_term = static function ( $name, $slug, $taxonomy ) {
    $existing = term_exists( $slug, $taxonomy );
    if ( $existing ) {
        return (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
    }

    $created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
    if ( is_wp_error( $created ) ) {
        throw new RuntimeException( 'Could not create ' . $taxonomy . ':' . $slug . ': ' . $created->get_error_message() );
    }

    return (int) $created['term_id'];
};

$ensure_page = static function ( $definition, $parent_id = 0 ) {
    $path     = $parent_id ? get_page_uri( $parent_id ) . '/' . $definition['slug'] : $definition['slug'];
    $existing = get_page_by_path( $path, OBJECT, 'page' );

    if ( $existing instanceof WP_Post ) {
        throw new RuntimeException( 'A Page already occupies the required path /' . $path . '/.' );
    }

    $page_id = wp_insert_post(
        array(
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_title'   => $definition['title'],
            'post_name'    => $definition['slug'],
            'post_parent'  => $parent_id,
            'post_content' => isset( $definition['content'] ) ? $definition['content'] : '',
            'post_excerpt' => isset( $definition['excerpt'] ) ? $definition['excerpt'] : '',
            'menu_order'   => isset( $definition['menu_order'] ) ? (int) $definition['menu_order'] : 0,
        ),
        true
    );

    if ( is_wp_error( $page_id ) ) {
        throw new RuntimeException( 'Could not create /' . $path . '/: ' . $page_id->get_error_message() );
    }

    if ( ! empty( $definition['thumbnail_id'] ) ) {
        set_post_thumbnail( $page_id, (int) $definition['thumbnail_id'] );
    }

    return (int) $page_id;
};

$add_redirect = static function ( &$redirects, $source, $destination ) {
    $source_path      = '/' . trim( (string) wp_parse_url( $source, PHP_URL_PATH ), '/' ) . '/';
    $destination_path = '/' . trim( (string) wp_parse_url( $destination, PHP_URL_PATH ), '/' ) . '/';

    if ( '/' !== $source_path && $source_path !== $destination_path ) {
        $redirects[ $source_path ] = $destination;
    }
};

try {
    $published_posts     = $post_ids( 'post' );
    $published_materials = $post_ids( 'material' );
    $published_pages     = $post_ids( 'page' );
    $project_ids         = $category_object_ids( array( 'projects', 'current', 'past', 'potential' ), 'post' );
    $news_ids            = $category_object_ids( array( 'news' ), 'post' );
    $intro_ids           = array( 2131, 2136, 2138, 2140, 2142 );

    sort( $intro_ids );
    $classified_posts = array_values( array_unique( array_merge( $project_ids, $news_ids, $intro_ids ) ) );
    sort( $classified_posts );

    $fail( 46 === count( $published_posts ), 'Expected 46 published Posts before migration.' );
    $fail( 19 === count( $published_materials ), 'Expected 19 published Materials before migration.' );
    $fail( 22 === count( $published_pages ), 'Expected 22 published Pages before migration.' );
    $fail( 28 === count( $project_ids ), 'Expected 28 project Posts.' );
    $fail( 13 === count( $news_ids ), 'Expected 13 News Posts.' );
    $fail( $published_posts === $classified_posts, 'Published Posts do not match the expected project/news/library partition.' );
    $fail( 172 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter" ), 'Newsletter subscriber count is not 172.' );
    $fail( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}newsletter_sent" ), 'Newsletter sent table is not empty.' );

    foreach ( $intro_ids as $intro_id ) {
        $intro = get_post( $intro_id );
        $fail( $intro instanceof WP_Post && 'post' === $intro->post_type && 'publish' === $intro->post_status, 'Library intro source ' . $intro_id . ' is unavailable.' );
    }
} catch ( Throwable $error ) {
    WP_CLI::error( 'Pre-migration validation failed: ' . $error->getMessage() );
}

$wpdb->query( 'START TRANSACTION' );

try {
    $project_terms = array(
        'current'   => $ensure_term( 'Current', 'current', 'project_status' ),
        'past'      => $ensure_term( 'Past', 'past', 'project_status' ),
        'potential' => $ensure_term( 'Potential', 'potential', 'project_status' ),
    );
    $material_terms = array(
        'health-and-pollution'                  => $ensure_term( 'Health and pollution', 'health-and-pollution', 'material_type' ),
        'action-plans'                          => $ensure_term( 'Action plans', 'action-plans', 'material_type' ),
        'lead-health-risk-assessment'           => $ensure_term( 'Lead health risk assessment', 'lead-health-risk-assessment', 'material_type' ),
        'publications-and-co-authored-publications' => $ensure_term( 'Publications and co-authored publications', 'publications-and-co-authored-publications', 'material_type' ),
        'videos'                                => $ensure_term( 'Videos', 'videos', 'material_type' ),
    );

    $intro_map = array(
        'action-plans'                          => 2131,
        'health-and-pollution'                  => 2136,
        'lead-health-risk-assessment'           => 2138,
        'publications-and-co-authored-publications' => 2140,
        'videos'                                => 2142,
    );

    $projects_page = $ensure_page( array( 'slug' => 'projects', 'title' => 'What we do' ) );
    $blog_page     = $ensure_page( array( 'slug' => 'blog', 'title' => 'Blog' ) );
    $library_page  = $ensure_page( array( 'slug' => 'library', 'title' => 'Library' ) );

    $pages = array(
        'projects' => $projects_page,
        'blog'     => $blog_page,
        'library'  => $library_page,
    );

    $project_page_definitions = array(
        'current'   => 'Current projects',
        'past'      => 'Projects implemented by EHPMI team',
        'potential' => 'Potential projects',
    );
    foreach ( $project_page_definitions as $slug => $title ) {
        $pages[ $slug ] = $ensure_page(
            array( 'slug' => $slug, 'title' => $title ),
            $projects_page
        );
    }

    $pages['news'] = $ensure_page( array( 'slug' => 'news', 'title' => 'News' ), $blog_page );

    foreach ( $intro_map as $slug => $source_id ) {
        $source = get_post( $source_id );
        $pages[ $slug ] = $ensure_page(
            array(
                'slug'         => $slug,
                'title'        => $source->post_title,
                'content'      => $source->post_content,
                'excerpt'      => $source->post_excerpt,
                'thumbnail_id' => get_post_thumbnail_id( $source ),
            ),
            $library_page
        );
    }

    $redirects = array();

    foreach ( $project_ids as $project_id ) {
        $project = get_post( $project_id );
        $slugs   = $category_slugs_for_object( $project_id );
        $status  = array_values( array_intersect( array( 'current', 'past', 'potential' ), $slugs ) );
        $fail( 1 === count( $status ), 'Project ' . $project_id . ' does not have exactly one legacy status.' );

        $updated = wp_update_post( array( 'ID' => $project_id, 'post_type' => 'project' ), true );
        $fail( ! is_wp_error( $updated ), 'Could not convert project ' . $project_id . '.' );
        $assigned = wp_set_object_terms( $project_id, $status[0], 'project_status', false );
        $fail( ! is_wp_error( $assigned ), 'Could not assign project status to ' . $project_id . '.' );

        $canonical = home_url( '/projects/' . $project->post_name . '/' );
        $add_redirect( $redirects, '/projects/' . $status[0] . '/' . $project->post_name . '/', $canonical );
        $add_redirect( $redirects, '/' . $status[0] . '/' . $project->post_name . '/', $canonical );
    }

    foreach ( $news_ids as $news_id ) {
        $news      = get_post( $news_id );
        $canonical = home_url( '/blog/' . $news->post_name . '/' );
        $add_redirect( $redirects, '/blog/news/' . $news->post_name . '/', $canonical );
        $add_redirect( $redirects, '/news/' . $news->post_name . '/', $canonical );
    }

    foreach ( $intro_map as $slug => $source_id ) {
        $source      = get_post( $source_id );
        $destination = get_permalink( $pages[ $slug ] );
        $add_redirect( $redirects, '/library/' . $slug . '/' . $source->post_name . '/', $destination );
        $add_redirect( $redirects, '/' . $slug . '/' . $source->post_name . '/', $destination );

        $updated = wp_update_post( array( 'ID' => $source_id, 'post_status' => 'draft' ), true );
        $fail( ! is_wp_error( $updated ), 'Could not retire library intro Post ' . $source_id . '.' );
    }

    foreach ( $published_materials as $material_id ) {
        $material = get_post( $material_id );
        $slugs    = $category_slugs_for_object( $material_id );
        $types    = array_values( array_intersect( array_keys( $material_terms ), $slugs ) );
        $fail( 1 === count( $types ), 'Material ' . $material_id . ' does not have exactly one legacy type.' );

        $assigned = wp_set_object_terms( $material_id, $types[0], 'material_type', false );
        $fail( ! is_wp_error( $assigned ), 'Could not assign material type to ' . $material_id . '.' );

        $file_id  = (int) get_post_meta( $material_id, 'file', true );
        $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
        $fail( is_string( $file_url ) && '' !== $file_url, 'Material ' . $material_id . ' has no usable file URL.' );
        $add_redirect( $redirects, '/material/' . $material->post_name . '/', $file_url );
    }

    $category_page_map = array(
        'projects'                                  => $pages['projects'],
        'current'                                   => $pages['current'],
        'past'                                      => $pages['past'],
        'potential'                                 => $pages['potential'],
        'blog'                                      => $pages['blog'],
        'news'                                      => $pages['news'],
        'library'                                   => $pages['library'],
        'health-and-pollution'                      => $pages['health-and-pollution'],
        'action-plans'                              => $pages['action-plans'],
        'lead-health-risk-assessment'               => $pages['lead-health-risk-assessment'],
        'publications-and-co-authored-publications' => $pages['publications-and-co-authored-publications'],
        'videos'                                    => $pages['videos'],
    );

    $category_paths = array(
        'projects'                                  => array( '/category/projects/', '/projects/' ),
        'current'                                   => array( '/category/projects/current/', '/category/current/', '/current/' ),
        'past'                                      => array( '/category/projects/past/', '/category/past/', '/past/' ),
        'potential'                                 => array( '/category/projects/potential/', '/category/potential/', '/potential/' ),
        'blog'                                      => array( '/category/blog/', '/blog/' ),
        'news'                                      => array( '/category/blog/news/', '/category/news/', '/news/' ),
        'library'                                   => array( '/category/library/', '/library/' ),
        'health-and-pollution'                      => array( '/category/library/health-and-pollution/', '/category/health-and-pollution/', '/health-and-pollution/' ),
        'action-plans'                              => array( '/category/library/action-plans/', '/category/action-plans/', '/action-plans/' ),
        'lead-health-risk-assessment'               => array( '/category/library/lead-health-risk-assessment/', '/category/lead-health-risk-assessment/', '/lead-health-risk-assessment/' ),
        'publications-and-co-authored-publications' => array( '/category/library/publications-and-co-authored-publications/', '/category/publications-and-co-authored-publications/', '/publications-and-co-authored-publications/' ),
        'videos'                                    => array( '/category/library/videos/', '/category/videos/', '/videos/' ),
    );

    foreach ( $category_paths as $slug => $paths ) {
        $destination = get_permalink( $category_page_map[ $slug ] );
        foreach ( $paths as $path ) {
            $add_redirect( $redirects, $path, $destination );
        }
    }

    update_option( 'permalink_structure', '/blog/%postname%/' );
    ksort( $redirects );
    update_option( 'ehpmi_legacy_redirects', $redirects, false );
    update_option( 'ehpmi_category_page_map', $category_page_map, false );

    $menu_items = $wpdb->get_results(
        "SELECT p.ID, object_id.meta_value AS object_id
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} item_type ON item_type.post_id = p.ID AND item_type.meta_key = '_menu_item_type' AND item_type.meta_value = 'taxonomy'
         INNER JOIN {$wpdb->postmeta} item_object ON item_object.post_id = p.ID AND item_object.meta_key = '_menu_item_object' AND item_object.meta_value = 'category'
         INNER JOIN {$wpdb->postmeta} object_id ON object_id.post_id = p.ID AND object_id.meta_key = '_menu_item_object_id'
         WHERE p.post_type = 'nav_menu_item'"
    );
    $menu_changed = 0;
    foreach ( $menu_items as $menu_item ) {
        $term = get_term( (int) $menu_item->object_id, 'category' );
        if ( ! $term instanceof WP_Term || ! isset( $category_page_map[ $term->slug ] ) ) {
            continue;
        }

        update_post_meta( $menu_item->ID, '_menu_item_type', 'post_type' );
        update_post_meta( $menu_item->ID, '_menu_item_object', 'page' );
        update_post_meta( $menu_item->ID, '_menu_item_object_id', (string) $category_page_map[ $term->slug ] );
        ++$menu_changed;
    }
    $fail( 12 === $menu_changed, 'Expected to convert 12 category menu items; converted ' . $menu_changed . '.' );

    update_option( 'ehpmi_routing_migration_version', $migration_version, false );
    $wpdb->query( 'COMMIT' );
} catch ( Throwable $error ) {
    $wpdb->query( 'ROLLBACK' );
    WP_CLI::error( 'Migration rolled back: ' . $error->getMessage() );
}

clean_post_cache( 2131 );
clean_post_cache( 2136 );
clean_post_cache( 2138 );
clean_post_cache( 2140 );
clean_post_cache( 2142 );
wp_cache_flush();
flush_rewrite_rules();

WP_CLI::success(
    'Migrated routing model: ' . wp_json_encode(
        array(
            'projects'  => count( $project_ids ),
            'news'      => count( $news_ids ),
            'materials' => count( $published_materials ),
            'pages'     => count( $pages ),
            'menu'      => $menu_changed,
            'redirects' => count( $redirects ),
        )
    )
);
