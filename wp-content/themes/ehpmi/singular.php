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

$is_page = (get_post_type() == 'page');

if ($is_page) {
    
    // Children nav
    $child_args = array(
        'post_parent' => $post->ID,
        'post_type'   => 'page',
        'post_status' => 'publish',
        'orderby'     => 'menu_order',
        'order'       => 'ASC'
    );

    $children = get_children($child_args);

    if ($children) {
        get_template_part( 'template-parts/subpages', '', ['children' => $children] );
    }
}

if ( have_posts() ) {

    while ( have_posts() ) {
        the_post();
        if (get_the_content()) {
            get_template_part( 'template-parts/content', get_post_type() );
        }
    }
}

if ($is_page) {

    // Page types
    $parent_id = wp_get_post_parent_id($post->ID);
    
    // Staff page
    if ($post->post_name == 'staff') {
        get_template_part( 'template-parts/staff');
        
        $organization = get_post_meta($post->ID, 'organization');

        if (!empty($organization[0])) {
            get_template_part( 'template-parts/partners', '', ['partners' => $organization, 'inner' => true]);
        }
    }
    
    // Partners page
    if (in_array($post->ID, [29, 1453])) {
        get_template_part( 'template-parts/partners');
    }
    
    // Offices page map
    if ($post->ID == 17) {
        get_template_part( 'template-parts/map');
    }
    
    // Country offices staff and partners
    if ($parent_id == 17) {
        $leader_id = get_post_meta($post->ID, 'leader', true);
        $staff_members = get_post_meta($post->ID, 'team', true);
        $partners = get_post_meta($post->ID, 'partner', true);
        
        if (!empty($leader_id)) {
            get_template_part( 'template-parts/leader', '', ['leader_id' => $leader_id] );
        }
        
        if (!empty($staff_members[0])) {
            get_template_part( 'template-parts/staff', '', ['staff_members' => $staff_members, 'classes' => 'country-staff'] );
        }

        if (!empty($partners[0])) {
            get_template_part( 'template-parts/partners', '', ['partners' => $partners, 'inner' => true] );
        }
    }
    
    // Siblings nav (See also)
    if ($parent_id) {
        $siblings_args = array(
            'post_parent' => $parent_id,
            'post_type'   => 'page',
            'post_status' => 'publish',
            'order'       => 'ASC'
        );

        $siblings = get_children($siblings_args);

        if (count($siblings) > 1) {
            get_sidebar('', ['siblings' => $siblings]);
        }
    }

}

get_template_part( 'template-parts/footer-menus-widgets' );

get_footer();
