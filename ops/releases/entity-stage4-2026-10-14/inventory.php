<?php
/**
 * Read-only Stage 4 material catalogue audit.
 *
 * Run through WP-CLI from the dev document root. This script performs no writes.
 */
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

global $wpdb;

$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );

$attachment_info = static function ( $attachment_id ) use ( $home_host ) {
    $attachment_id = (int) $attachment_id;
    if ( $attachment_id < 1 ) {
        return null;
    }

    $post          = get_post( $attachment_id );
    $url           = wp_get_attachment_url( $attachment_id );
    $attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
    $absolute_path = get_attached_file( $attachment_id, true );
    $url_host      = is_string( $url ) ? strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) : '';

    return array(
        'id'            => $attachment_id,
        'post_exists'   => $post instanceof WP_Post,
        'post_type'     => $post instanceof WP_Post ? $post->post_type : null,
        'status'        => $post instanceof WP_Post ? $post->post_status : null,
        'title'         => $post instanceof WP_Post ? $post->post_title : null,
        'slug'          => $post instanceof WP_Post ? $post->post_name : null,
        'parent'        => $post instanceof WP_Post ? (int) $post->post_parent : null,
        'mime_type'     => $post instanceof WP_Post ? $post->post_mime_type : null,
        'guid'          => $post instanceof WP_Post ? $post->guid : null,
        'attached_file' => is_string( $attached_file ) ? $attached_file : null,
        'absolute_path' => is_string( $absolute_path ) ? $absolute_path : null,
        'file_exists'   => is_string( $absolute_path ) && is_file( $absolute_path ),
        'filesize'      => is_string( $absolute_path ) && is_file( $absolute_path ) ? (int) filesize( $absolute_path ) : null,
        'url'           => is_string( $url ) ? $url : null,
        'url_host'      => $url_host,
        'external_url'  => '' !== $url_host && $home_host !== $url_host,
    );
};

$resolve_file = static function ( $raw_value ) {
    if ( is_numeric( $raw_value ) ) {
        return (int) $raw_value;
    }

    if ( is_array( $raw_value ) ) {
        if ( isset( $raw_value['ID'] ) && is_numeric( $raw_value['ID'] ) ) {
            return (int) $raw_value['ID'];
        }
        if ( isset( $raw_value['id'] ) && is_numeric( $raw_value['id'] ) ) {
            return (int) $raw_value['id'];
        }
        if ( isset( $raw_value['url'] ) && is_string( $raw_value['url'] ) ) {
            return (int) attachment_url_to_postid( $raw_value['url'] );
        }
    }

    if ( is_string( $raw_value ) && preg_match( '#^https?://#i', $raw_value ) ) {
        return (int) attachment_url_to_postid( $raw_value );
    }

    return 0;
};

$material_posts = $wpdb->get_results(
    "SELECT ID, post_title, post_name, post_status, post_date, post_date_gmt,
            post_modified, post_modified_gmt, menu_order, post_parent
     FROM {$wpdb->posts}
     WHERE post_type = 'material'
     ORDER BY ID ASC"
);

$materials = array();
$status_counts = array();
$meta_key_counts = array();
foreach ( $material_posts as $material ) {
    $material_id = (int) $material->ID;
    $meta_rows   = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_id, meta_key, meta_value
             FROM {$wpdb->postmeta}
             WHERE post_id = %d
             ORDER BY meta_id ASC",
            $material_id
        ),
        ARRAY_A
    );

    $meta = array();
    foreach ( $meta_rows as $row ) {
        $key = (string) $row['meta_key'];
        if ( ! isset( $meta_key_counts[ $key ] ) ) {
            $meta_key_counts[ $key ] = 0;
        }
        ++$meta_key_counts[ $key ];
        $meta[] = array(
            'meta_id' => (int) $row['meta_id'],
            'key'     => $key,
            'value'   => maybe_unserialize( $row['meta_value'] ),
        );
    }

    $file_values = get_post_meta( $material_id, 'file', false );
    $file_value  = get_post_meta( $material_id, 'file', true );
    $file_id     = $resolve_file( $file_value );
    $file_url    = $file_id ? wp_get_attachment_url( $file_id ) : ( is_string( $file_value ) && preg_match( '#^https?://#i', $file_value ) ? $file_value : '' );
    $file_host   = is_string( $file_url ) ? strtolower( (string) wp_parse_url( $file_url, PHP_URL_HOST ) ) : '';
    $thumb_id    = (int) get_post_thumbnail_id( $material_id );

    $types      = wp_get_object_terms( $material_id, 'material_type', array( 'fields' => 'all' ) );
    $categories = wp_get_object_terms( $material_id, 'category', array( 'fields' => 'all' ) );

    $type_rows = array();
    if ( ! is_wp_error( $types ) ) {
        foreach ( $types as $term ) {
            $type_rows[] = array(
                'term_id' => (int) $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
            );
        }
    }

    $category_rows = array();
    if ( ! is_wp_error( $categories ) ) {
        foreach ( $categories as $term ) {
            $category_rows[] = array(
                'term_id' => (int) $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
            );
        }
    }

    if ( ! isset( $status_counts[ $material->post_status ] ) ) {
        $status_counts[ $material->post_status ] = 0;
    }
    ++$status_counts[ $material->post_status ];

    $materials[] = array(
        'id'                  => $material_id,
        'title'               => $material->post_title,
        'slug'                => $material->post_name,
        'status'              => $material->post_status,
        'date'                => $material->post_date,
        'date_gmt'            => $material->post_date_gmt,
        'modified'            => $material->post_modified,
        'modified_gmt'        => $material->post_modified_gmt,
        'menu_order'          => (int) $material->menu_order,
        'parent'              => (int) $material->post_parent,
        'material_types'      => $type_rows,
        'legacy_categories'   => $category_rows,
        'meta'                => $meta,
        'file_values'         => $file_values,
        'file_value'          => $file_value,
        'resolved_file_id'    => $file_id,
        'resolved_file_url'   => is_string( $file_url ) ? $file_url : null,
        'file_url_host'       => $file_host,
        'external_file_url'   => '' !== $file_host && $home_host !== $file_host,
        'file_attachment'     => $attachment_info( $file_id ),
        'thumbnail_id'        => $thumb_id,
        'thumbnail_attachment'=> $attachment_info( $thumb_id ),
        'legacy_single_path'  => '/material/' . $material->post_name . '/',
    );
}
ksort( $status_counts );
ksort( $meta_key_counts );

$term_rows = get_terms(
    array(
        'taxonomy'   => 'material_type',
        'hide_empty' => false,
        'orderby'    => 'term_id',
        'order'      => 'ASC',
    )
);
$terms = array();
if ( ! is_wp_error( $term_rows ) ) {
    foreach ( $term_rows as $term ) {
        $all_object_ids = get_objects_in_term( $term->term_id, 'material_type' );
        $terms[] = array(
            'term_id'        => (int) $term->term_id,
            'name'           => $term->name,
            'slug'           => $term->slug,
            'description'    => $term->description,
            'published_count'=> (int) $term->count,
            'all_object_ids' => is_wp_error( $all_object_ids ) ? array() : array_map( 'intval', $all_object_ids ),
        );
    }
}

$library_page = get_page_by_path( 'library', OBJECT, 'page' );
$pages = array();
if ( $library_page instanceof WP_Post ) {
    $page_posts = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => 'any',
            'post_parent'    => $library_page->ID,
            'posts_per_page' => -1,
            'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
            'order'          => 'ASC',
        )
    );
    array_unshift( $page_posts, $library_page );
    foreach ( $page_posts as $page ) {
        $pages[] = array(
            'id'           => (int) $page->ID,
            'title'        => $page->post_title,
            'slug'         => $page->post_name,
            'status'       => $page->post_status,
            'parent'       => (int) $page->post_parent,
            'menu_order'   => (int) $page->menu_order,
            'date'         => $page->post_date,
            'modified'     => $page->post_modified,
            'content_sha256'=> hash( 'sha256', $page->post_content ),
            'url'          => get_permalink( $page ),
        );
    }
}

$query_orders = array();
$query_definitions = array( 'all' => null );
foreach ( $terms as $term ) {
    $query_definitions[ $term['slug'] ] = $term['slug'];
}
foreach ( $query_definitions as $label => $term_slug ) {
    $args = array(
        'post_type'      => 'material',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    );
    if ( null !== $term_slug ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'material_type',
                'field'    => 'slug',
                'terms'    => $term_slug,
            ),
        );
    }
    $query_orders[ $label ] = array_map( 'intval', get_posts( $args ) );
}

$post_type = get_post_type_object( 'material' );
$taxonomy  = get_taxonomy( 'material_type' );
$redirects = get_option( 'ehpmi_legacy_redirects', array() );
$material_redirects = array();
if ( is_array( $redirects ) ) {
    foreach ( $materials as $material ) {
        $path = $material['legacy_single_path'];
        $material_redirects[ $path ] = isset( $redirects[ $path ] ) ? $redirects[ $path ] : null;
    }
}

$report = array(
    'environment' => array(
        'document_root' => realpath( ABSPATH ),
        'database'      => DB_NAME,
        'home'          => untrailingslashit( get_option( 'home' ) ),
        'siteurl'       => untrailingslashit( get_option( 'siteurl' ) ),
    ),
    'post_type' => array(
        'name'                => $post_type ? $post_type->name : null,
        'public'              => $post_type ? (bool) $post_type->public : null,
        'publicly_queryable'  => $post_type ? (bool) $post_type->publicly_queryable : null,
        'exclude_from_search' => $post_type ? (bool) $post_type->exclude_from_search : null,
        'show_ui'             => $post_type ? (bool) $post_type->show_ui : null,
        'show_in_rest'        => $post_type ? (bool) $post_type->show_in_rest : null,
        'has_archive'         => $post_type ? (bool) $post_type->has_archive : null,
        'rewrite'             => $post_type ? $post_type->rewrite : null,
        'query_var'           => $post_type ? $post_type->query_var : null,
        'supports'            => get_all_post_type_supports( 'material' ),
    ),
    'taxonomy' => array(
        'name'               => $taxonomy ? $taxonomy->name : null,
        'object_type'        => $taxonomy ? $taxonomy->object_type : array(),
        'public'             => $taxonomy ? (bool) $taxonomy->public : null,
        'publicly_queryable' => $taxonomy ? (bool) $taxonomy->publicly_queryable : null,
        'show_ui'            => $taxonomy ? (bool) $taxonomy->show_ui : null,
        'show_admin_column'  => $taxonomy ? (bool) $taxonomy->show_admin_column : null,
        'show_in_rest'       => $taxonomy ? (bool) $taxonomy->show_in_rest : null,
        'rewrite'            => $taxonomy ? $taxonomy->rewrite : null,
        'query_var'          => $taxonomy ? $taxonomy->query_var : null,
        'terms'              => $terms,
    ),
    'status_counts'      => $status_counts,
    'meta_key_row_counts'=> $meta_key_counts,
    'materials'          => $materials,
    'library_pages'      => $pages,
    'query_orders'       => $query_orders,
    'material_redirects' => $material_redirects,
);
WP_CLI::line( 'STAGE4_AUDIT_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
