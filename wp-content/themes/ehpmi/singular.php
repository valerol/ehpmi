<?php
/** Singular fallback; Page region ownership lives in inc/page-resolver.php. */
get_header();
$ehpmi_page = is_page() ? get_queried_object() : null;
$view = $ehpmi_page instanceof WP_Post ? ehpmi_resolve_page( $ehpmi_page ) : null;
?>
<main id="main-content" class="site-main">
<?php
if ( $view && $view['child_navigation'] ) {
    $children = get_children( array( 'post_parent' => $ehpmi_page->ID, 'post_type' => 'page', 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC' ) );
    if ( $children ) {
        get_template_part( 'template-parts/subpages', '', array( 'children' => $children ) );
    }
}
while ( have_posts() ) {
    the_post();
    if ( '' !== trim( (string) get_the_content() ) ) {
        get_template_part( 'template-parts/content', get_post_type() );
    } elseif ( $view && ! $view['listing_owns_title'] ) {
        // Empty managed Pages still have a document title without a visual redesign.
        echo '<h1 class="screen-reader-text">' . esc_html( get_the_title() ) . '</h1>';
    }
}
if ( $view ) {
    ehpmi_render_page_regions( $ehpmi_page, $view );
    if ( $view['sibling_navigation'] ) {
        $siblings = get_children( array( 'post_parent' => $view['parent_id'], 'post_type' => 'page', 'post_status' => 'publish', 'order' => 'ASC' ) );
        if ( count( $siblings ) > 1 ) {
            get_sidebar( '', array( 'siblings' => $siblings ) );
        }
    }
}
?>
</main>
<?php get_footer();
