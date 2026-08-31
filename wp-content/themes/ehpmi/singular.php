<?php
/**
 * The template for displaying single posts and pages.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */

get_header();

$is_page               = is_page();
$structural_page_slugs = array( 'projects', 'blog', 'library' );
$has_page_content      = $is_page && '' !== trim( (string) $post->post_content );

if ( $is_page && ! in_array( $post->post_name, $structural_page_slugs, true ) ) {
    // Preserve the existing child navigation for the original Page sections.
    $child_args = array(
        'post_parent' => $post->ID,
        'post_type'   => 'page',
        'post_status' => 'publish',
        'orderby'     => 'menu_order',
        'order'       => 'ASC',
    );

    $children = get_children( $child_args );

    if ( $children ) {
        get_template_part( 'template-parts/subpages', '', array( 'children' => $children ) );
    }
}

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        if ( '' !== trim( (string) get_the_content() ) ) {
            get_template_part( 'template-parts/content', get_post_type() );
        }
    }
}

if ( $is_page ) {
    $parent_id   = wp_get_post_parent_id( $post->ID );
    $parent_slug = $parent_id ? get_post_field( 'post_name', $parent_id ) : '';

    // Staff page.
    if ( 'staff' === $post->post_name ) {
        get_template_part( 'template-parts/staff' );
    }

    // Organization directories use separate, admin-only content types.
    if ( 'members' === $post->post_name ) {
        get_template_part( 'template-parts/partners', '', array( 'post_type' => 'member' ) );
    } elseif ( 'partners' === $post->post_name ) {
        get_template_part( 'template-parts/partners', '', array( 'post_type' => 'partner' ) );
    }

    // Offices page map.
    if ( 'offices' === $post->post_name ) {
        get_template_part( 'template-parts/map' );
    }

    // Country offices staff and member organizations.
    if ( 'offices' === $parent_slug ) {
        $leader_id    = get_post_meta( $post->ID, 'leader', true );
        $staff_members = get_post_meta( $post->ID, 'team', true );
        $members       = get_post_meta( $post->ID, 'member', true );

        if ( ! empty( $leader_id ) ) {
            get_template_part( 'template-parts/leader', '', array( 'leader_id' => $leader_id ) );
        }

        if ( ! empty( $staff_members[0] ) ) {
            get_template_part(
                'template-parts/staff',
                '',
                array(
                    'staff_members' => $staff_members,
                    'classes'       => 'country-staff',
                )
            );
        }

        if ( ! empty( $members[0] ) ) {
            get_template_part(
                'template-parts/partners',
                '',
                array(
                    'organizations' => $members,
                    'post_type'     => 'member',
                    'inner'         => true,
                )
            );
        }
    }

    // Project landing Pages query the dedicated Project content type.
    if ( 'projects' === $post->post_name || 'projects' === $parent_slug ) {
        $project_args = array(
            'inner'       => true,
            'heading'     => get_the_title( $post ),
            'numberposts' => -1,
        );

        if ( 'projects' === $parent_slug ) {
            $project_args['project_status'] = $post->post_name;
        }

        if ( $has_page_content ) {
            $project_args['show_heading'] = false;
        }

        get_template_part( 'template-parts/projects', '', $project_args );
    }

    // Blog is an overview; News is the complete editorial index.
    if ( 'blog' === $post->post_name ) {
        get_template_part(
            'template-parts/news',
            '',
            array(
                'inner'       => true,
                'heading'     => get_the_title( $post ),
                'numberposts' => 3,
            )
        );
    } elseif ( 'blog' === $parent_slug && 'news' === $post->post_name ) {
        get_template_part(
            'template-parts/news',
            '',
            array(
                'inner'       => true,
                'heading'     => get_the_title( $post ),
                'numberposts' => -1,
            )
        );
    }

    // Library Pages own the public hierarchy; the taxonomy is an internal filter.
    if ( 'library' === $post->post_name || 'library' === $parent_slug ) {
        $material_query = array(
            'post_type'      => 'material',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( 'library' === $parent_slug ) {
            $material_query['tax_query'] = array(
                array(
                    'taxonomy' => 'material_type',
                    'field'    => 'slug',
                    'terms'    => $post->post_name,
                ),
            );
        }

        $library_args = array( 'materials' => get_posts( $material_query ) );
        if ( ! $has_page_content ) {
            $library_args['heading'] = get_the_title( $post );
        }

        get_template_part( 'template-parts/library', '', $library_args );
    }

    // Preserve the original See also navigation, excluding new directory Pages.
    if ( $parent_id && ! in_array( $parent_slug, $structural_page_slugs, true ) ) {
        $siblings_args = array(
            'post_parent' => $parent_id,
            'post_type'   => 'page',
            'post_status' => 'publish',
            'order'       => 'ASC',
        );

        $siblings = get_children( $siblings_args );

        if ( count( $siblings ) > 1 ) {
            get_sidebar( '', array( 'siblings' => $siblings ) );
        }
    }
}

get_footer();
