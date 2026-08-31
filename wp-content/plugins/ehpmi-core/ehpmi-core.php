<?php
/**
 * Plugin Name: EHPMI Core
 * Description: Project-owned content types, classifications, canonical routes and breadcrumb roots.
 * Version: 1.0.0
 * Author: EHPMI
 * Text Domain: ehpmi-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'EHPMI_CORE_VERSION', '1.0.0' );

/**
 * Register content types independently from the active theme.
 */
function ehpmi_core_register_content_types() {
    register_post_type(
        'hero_slide',
        array(
            'labels' => array(
                'name'          => __( 'Hero slides', 'ehpmi-core' ),
                'singular_name' => __( 'Hero slide', 'ehpmi-core' ),
                'add_new_item'  => __( 'Add hero slide', 'ehpmi-core' ),
                'edit_item'     => __( 'Edit hero slide', 'ehpmi-core' ),
            ),
            'description'         => __( 'Homepage carousel images ordered by the Order field.', 'ehpmi-core' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-images-alt2',
            'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
            'rewrite'             => false,
            'query_var'           => false,
        )
    );

    register_post_type(
        'testimonial',
        array(
            'labels' => array(
                'name'          => __( 'Testimonials', 'ehpmi-core' ),
                'singular_name' => __( 'Testimonial', 'ehpmi-core' ),
            ),
            'supports'     => array( 'title', 'thumbnail', 'editor' ),
            'public'       => true,
            'show_in_rest' => true,
        )
    );

    register_post_type(
        'member',
        array(
            'labels' => array(
                'name'          => __( 'Members', 'ehpmi-core' ),
                'singular_name' => __( 'Member', 'ehpmi-core' ),
                'add_new_item'  => __( 'Add member', 'ehpmi-core' ),
                'edit_item'     => __( 'Edit member', 'ehpmi-core' ),
            ),
            'description'         => __( 'EHPMI member organizations.', 'ehpmi-core' ),
            'supports'            => array( 'title', 'thumbnail', 'editor', 'custom-fields' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_rest'        => false,
            'menu_icon'           => 'dashicons-groups',
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        )
    );

    register_post_type(
        'partner',
        array(
            'labels' => array(
                'name'          => __( 'Partners', 'ehpmi-core' ),
                'singular_name' => __( 'Partner', 'ehpmi-core' ),
                'add_new_item'  => __( 'Add partner', 'ehpmi-core' ),
                'edit_item'     => __( 'Edit partner', 'ehpmi-core' ),
            ),
            'description'         => __( 'External EHPMI partner organizations.', 'ehpmi-core' ),
            'supports'            => array( 'title', 'thumbnail', 'editor', 'custom-fields' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_rest'        => false,
            'menu_icon'           => 'dashicons-businessperson',
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        )
    );

    register_post_type(
        'project',
        array(
            'labels' => array(
                'name'          => __( 'Projects', 'ehpmi-core' ),
                'singular_name' => __( 'Project', 'ehpmi-core' ),
                'add_new_item'  => __( 'Add project', 'ehpmi-core' ),
                'edit_item'     => __( 'Edit project', 'ehpmi-core' ),
            ),
            'description'         => __( 'EHPMI projects.', 'ehpmi-core' ),
            'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
            'public'              => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'show_ui'             => true,
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-portfolio',
            'has_archive'         => false,
            'rewrite'             => array(
                'slug'       => 'projects',
                'with_front' => false,
            ),
        )
    );

    register_post_type(
        'material',
        array(
            'labels' => array(
                'name'          => __( 'Materials', 'ehpmi-core' ),
                'singular_name' => __( 'Material', 'ehpmi-core' ),
                'add_new_item'  => __( 'Add material', 'ehpmi-core' ),
                'edit_item'     => __( 'Edit material', 'ehpmi-core' ),
            ),
            'description'         => __( 'Downloadable EHPMI library resources.', 'ehpmi-core' ),
            'supports'            => array( 'title', 'thumbnail', 'custom-fields' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_rest'        => false,
            'show_in_nav_menus'   => false,
            'menu_icon'           => 'dashicons-media-document',
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
        )
    );

    register_post_type(
        'staff_member',
        array(
            'labels' => array(
                'name'          => __( 'Staff', 'ehpmi-core' ),
                'singular_name' => __( 'Staff member', 'ehpmi-core' ),
            ),
            'public'       => true,
            'has_archive'  => false,
            'rewrite'      => array(
                'slug'       => 'about/staff',
                'with_front' => false,
            ),
            'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
            'show_in_rest' => true,
        )
    );

    register_taxonomy(
        'project_status',
        array( 'project' ),
        array(
            'labels' => array(
                'name'          => __( 'Project statuses', 'ehpmi-core' ),
                'singular_name' => __( 'Project status', 'ehpmi-core' ),
            ),
            'hierarchical'      => false,
            'public'            => false,
            'publicly_queryable' => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => false,
            'query_var'         => false,
        )
    );

    register_taxonomy(
        'material_type',
        array( 'material' ),
        array(
            'labels' => array(
                'name'          => __( 'Material types', 'ehpmi-core' ),
                'singular_name' => __( 'Material type', 'ehpmi-core' ),
            ),
            'hierarchical'       => false,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_rest'       => false,
            'rewrite'            => false,
            'query_var'          => false,
        )
    );
}
add_action( 'init', 'ehpmi_core_register_content_types', 5 );

/**
 * Categories are retained as rollback data, but no longer drive public content.
 */
function ehpmi_core_detach_legacy_categories() {
    unregister_taxonomy_for_object_type( 'category', 'post' );
    unregister_taxonomy_for_object_type( 'category', 'material' );
}
add_action( 'init', 'ehpmi_core_detach_legacy_categories', 100 );

/**
 * Preserve the structural Page routes that share the Projects prefix.
 */
function ehpmi_core_page_rewrite_rules() {
    add_rewrite_rule(
        '^projects/(current|past|potential)/?$',
        'index.php?pagename=projects/$matches[1]',
        'top'
    );
}
add_action( 'init', 'ehpmi_core_page_rewrite_rules', 20 );

/**
 * Redirect every recorded legacy route to its single canonical destination.
 */
function ehpmi_core_redirect_legacy_routes() {
    if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    $method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
    if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
        return;
    }

    if ( is_category() ) {
        $category  = get_queried_object();
        $page_map  = get_option( 'ehpmi_category_page_map', array() );
        $target_id = $category instanceof WP_Term && isset( $page_map[ $category->slug ] )
            ? (int) $page_map[ $category->slug ]
            : 0;

        if ( $target_id ) {
            wp_safe_redirect( get_permalink( $target_id ), 301, 'EHPMI Core' );
            exit;
        }
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
    $path        = wp_parse_url( $request_uri, PHP_URL_PATH );
    $path        = '/' . trim( rawurldecode( (string) $path ), '/' ) . '/';
    $redirects   = get_option( 'ehpmi_legacy_redirects', array() );

    if ( ! is_array( $redirects ) || empty( $redirects[ $path ] ) ) {
        return;
    }

    $destination = $redirects[ $path ];
    if ( ! is_string( $destination ) || '' === $destination ) {
        return;
    }

    wp_safe_redirect( $destination, 301, 'EHPMI Core' );
    exit;
}
add_action( 'template_redirect', 'ehpmi_core_redirect_legacy_routes', 1 );

/**
 * Categories and private Materials must not generate duplicate sitemap URLs.
 */
function ehpmi_core_sitemap_taxonomies( $taxonomies ) {
    unset( $taxonomies['category'], $taxonomies['project_status'], $taxonomies['material_type'] );

    return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'ehpmi_core_sitemap_taxonomies' );

/**
 * Give Breadcrumb NavXT explicit, stable Page roots for public records.
 */
function ehpmi_core_breadcrumb_roots( $trail ) {
    if ( ! is_object( $trail ) || ! isset( $trail->opt ) || ! is_array( $trail->opt ) ) {
        return;
    }

    if ( is_singular( 'post' ) ) {
        $blog = get_page_by_path( 'blog' );
        if ( $blog instanceof WP_Post ) {
            $trail->opt['apost_post_root']                 = $blog->ID;
            $trail->opt['bpost_post_hierarchy_display']   = false;
            $trail->opt['Epost_post_hierarchy_type']      = 'BCN_POST_PARENT';
        }
    }

    if ( is_singular( 'project' ) ) {
        $projects = get_page_by_path( 'projects' );
        if ( $projects instanceof WP_Post ) {
            $trail->opt['apost_project_root']               = $projects->ID;
            $trail->opt['bpost_project_hierarchy_display'] = false;
            $trail->opt['Epost_project_hierarchy_type']    = 'BCN_POST_PARENT';
        }
    }
}
add_action( 'bcn_before_fill', 'ehpmi_core_breadcrumb_roots' );

/**
 * Flush routes only on lifecycle changes, never on each request.
 */
function ehpmi_core_activate() {
    ehpmi_core_register_content_types();
    ehpmi_core_page_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ehpmi_core_activate' );

function ehpmi_core_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ehpmi_core_deactivate' );
