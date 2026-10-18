<?php
/** Page ownership; routing and public URLs remain owned by WordPress. */
function ehpmi_resolve_page( $page ) {
    $parent_id = wp_get_post_parent_id( $page->ID );
    $parent_slug = $parent_id ? get_post_field( 'post_name', $parent_id ) : '';
    $structural = array( 'projects', 'blog', 'library' );
    $role = 'content';
    $roles = array( 'staff' => 'staff', 'members' => 'member', 'partners' => 'partner', 'offices' => 'map', 'projects' => 'projects', 'blog' => 'blog', 'library' => 'library' );
    if ( isset( $roles[ $page->post_name ] ) ) {
        $role = $roles[ $page->post_name ];
    } elseif ( 'offices' === $parent_slug ) {
        $role = 'country';
    } elseif ( 'projects' === $parent_slug ) {
        $role = 'projects';
    } elseif ( 'blog' === $parent_slug && 'news' === $page->post_name ) {
        $role = 'news';
    } elseif ( 'library' === $parent_slug ) {
        $role = 'library';
    }
    return array(
        'role' => $role,
        'parent_id' => $parent_id,
        'parent_slug' => $parent_slug,
        'child_navigation' => ! in_array( $page->post_name, $structural, true ),
        'sibling_navigation' => $parent_id && ! in_array( $parent_slug, $structural, true ),
        'has_content' => '' !== trim( (string) $page->post_content ),
        'listing_owns_title' => in_array( $role, array( 'projects', 'blog', 'news', 'library' ), true ),
    );
}

/** Render the measured Page regions using one resolved owner. */
function ehpmi_render_page_regions( $page, $view ) {
    switch ( $view['role'] ) {
        case 'staff':
            echo '<h2 class="screen-reader-text">' . esc_html__( 'Staff directory', 'ehpmi' ) . '</h2>';
            get_template_part( 'template-parts/staff' );
            break;
        case 'member':
        case 'partner':
            echo '<h2 class="screen-reader-text">' . esc_html__( 'Organizations', 'ehpmi' ) . '</h2>';
            get_template_part( 'template-parts/partners', '', array( 'post_type' => $view['role'] ) );
            break;
        case 'map':
            get_template_part( 'template-parts/map' );
            break;
        case 'country':
            $leader = get_post_meta( $page->ID, 'leader', true );
            $team = get_post_meta( $page->ID, 'team', true );
            $members = get_post_meta( $page->ID, 'member', true );
            if ( ! empty( $leader ) ) {
                get_template_part( 'template-parts/leader', '', array( 'leader_id' => $leader ) );
            }
            if ( ! empty( $team[0] ) ) {
                get_template_part( 'template-parts/staff', '', array( 'staff_members' => $team, 'classes' => 'country-staff' ) );
            }
            if ( ! empty( $members[0] ) ) {
                get_template_part( 'template-parts/partners', '', array( 'organizations' => $members, 'post_type' => 'member', 'inner' => true ) );
            }
            break;
        case 'projects':
            $args = array( 'inner' => true, 'heading' => get_the_title( $page ), 'numberposts' => 12, 'paginate' => true, 'show_heading' => ! $view['has_content'] );
            if ( 'projects' === $view['parent_slug'] ) {
                $args['project_status'] = $page->post_name;
            }
            get_template_part( 'template-parts/projects', '', $args );
            break;
        case 'blog':
        case 'news':
            get_template_part( 'template-parts/news', '', array( 'inner' => true, 'heading' => get_the_title( $page ), 'numberposts' => 'news' === $view['role'] ? 12 : 3, 'paginate' => 'news' === $view['role'], 'show_heading' => ! $view['has_content'] ) );
            break;
        case 'library':
            $args = array( 'heading' => get_the_title( $page ), 'numberposts' => 12, 'paginate' => true, 'show_heading' => ! $view['has_content'] );
            if ( 'library' === $view['parent_slug'] ) {
                $args['material_type'] = $page->post_name;
            }
            get_template_part( 'template-parts/library', '', $args );
            break;
    }
}
