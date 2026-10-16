<?php
/** Read-only Stage 5 testimonial verifier. Run with WP-CLI from the dev root. */
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
    127 => array( 'draft', 'Alena Temnikova', 'alena-temnikova', '63cbfad155a5ccbf28e67dbf3376b7c1ea39d2d5b6cc3f17ff22fcb9c3727a37', 155 ),
    133 => array( 'draft', 'Photo 7', 'denis-richie-2', '2b46665d80f129b90a382f87b54d3789395b350dbc5d610cda2f49a4e3c34684', 162 ),
    134 => array( 'draft', 'Photo 5', 'denis-richie-3', '2b46665d80f129b90a382f87b54d3789395b350dbc5d610cda2f49a4e3c34684', 154 ),
    135 => array( 'draft', 'Photo 8', 'denis-richie-4', '2b46665d80f129b90a382f87b54d3789395b350dbc5d610cda2f49a4e3c34684', 156 ),
    136 => array( 'draft', 'Photo 10', 'denis-richie-5', '2b46665d80f129b90a382f87b54d3789395b350dbc5d610cda2f49a4e3c34684', 158 ),
    160 => array( 'draft', 'Photo 11', 'photo-11', '2b46665d80f129b90a382f87b54d3789395b350dbc5d610cda2f49a4e3c34684', 157 ),
    161 => array( 'draft', 'Photo 9', 'photo-9', '2b46665d80f129b90a382f87b54d3789395b350dbc5d610cda2f49a4e3c34684', 159 ),
);
$ids = array_map(
    'intval',
    $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'testimonial' ORDER BY ID ASC" )
);
$check( array_keys( $expected ) === $ids, 'exact Testimonial IDs' );

$rows = array();
foreach ( $expected as $id => $frozen ) {
    $post = get_post( $id );
    $thumbnail_id = get_post_thumbnail_id( $id );
    $check( $post instanceof WP_Post, 'Testimonial exists ' . $id );
    if ( ! $post instanceof WP_Post ) {
        continue;
    }
    $check( $frozen[0] === $post->post_status, 'draft status ' . $id );
    $check( $frozen[1] === $post->post_title, 'title ' . $id );
    $check( $frozen[2] === $post->post_name, 'slug ' . $id );
    $check( $frozen[3] === hash( 'sha256', $post->post_content ), 'quotation hash ' . $id );
    $check( $frozen[4] === (int) $thumbnail_id, 'thumbnail relationship ' . $id );
    $check( 0 === (int) $post->menu_order, 'menu_order preserved ' . $id );
    $check( 'image/png' === get_post_mime_type( $thumbnail_id ), 'thumbnail MIME ' . $id );
    $check( '' === (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ), 'thumbnail alt preserved ' . $id );
    foreach ( array( 'testimonial_author_name', 'testimonial_author_role', 'testimonial_author_organization' ) as $meta_key ) {
        $check( '' === (string) get_post_meta( $id, $meta_key, true ), $meta_key . ' remains empty ' . $id );
    }
    $rows[ $id ] = array(
        'status' => $post->post_status,
        'menu_order' => (int) $post->menu_order,
        'thumbnail_id' => (int) $thumbnail_id,
        'content_sha256' => hash( 'sha256', $post->post_content ),
    );
}

$published = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'testimonial' AND post_status = 'publish'" );
$check( 0 === $published, 'zero published Testimonials' );

$post_type = get_post_type_object( 'testimonial' );
$check( $post_type instanceof WP_Post_Type, 'Testimonial post type registered' );
$check( $post_type && ! $post_type->public, 'Testimonial not public' );
$check( $post_type && ! $post_type->publicly_queryable, 'Testimonial not publicly queryable' );
$check( $post_type && $post_type->exclude_from_search, 'Testimonial excluded from search' );
$check( $post_type && ! $post_type->show_in_rest, 'Testimonial excluded from REST' );
$check( $post_type && false === $post_type->has_archive, 'Testimonial archive disabled' );
$check( $post_type && false === $post_type->rewrite, 'Testimonial rewrites disabled' );
$check( $post_type && false === $post_type->query_var, 'Testimonial query var disabled' );
$check( post_type_supports( 'testimonial', 'editor' ), 'quotation editor enabled' );
$check( post_type_supports( 'testimonial', 'thumbnail' ), 'thumbnail enabled' );
$check( post_type_supports( 'testimonial', 'page-attributes' ), 'Order control enabled' );

$fields = array(
    'field_ehpmi_testimonial_author_name' => 'testimonial_author_name',
    'field_ehpmi_testimonial_author_role' => 'testimonial_author_role',
    'field_ehpmi_testimonial_author_organization' => 'testimonial_author_organization',
);
foreach ( $fields as $field_key => $field_name ) {
    $field = function_exists( 'acf_get_field' ) ? acf_get_field( $field_key ) : null;
    $check( is_array( $field ), 'ACF field loaded ' . $field_name );
    $check( is_array( $field ) && $field_name === $field['name'], 'ACF field name ' . $field_name );
    $check( is_array( $field ) && 'text' === $field['type'], 'ACF field type ' . $field_name );
}

$render_template = static function () {
    ob_start();
    get_template_part( 'template-parts/testimonials' );
    return ob_get_clean();
};
$default_markup = $render_template();
$check( '' === trim( $default_markup ), 'dormant template renders nothing by default' );

$activate = static function () { return true; };
add_filter( 'ehpmi_testimonials_active', $activate );
$zero_markup = $render_template();
$check( '' === trim( $zero_markup ), 'activated probe with zero published renders nothing' );
$check( false === strpos( $zero_markup, 'testimonials-carousel' ), 'zero published has no controls' );

$probe = get_post( 127 );
$captured_orderby = null;
$query_probe = static function ( $posts, $query ) use ( $probe, &$captured_orderby ) {
    if ( 'testimonial' !== $query->get( 'post_type' ) ) {
        return $posts;
    }
    $captured_orderby = $query->get( 'orderby' );
    $query->found_posts = 1;
    $query->post_count = 1;
    return array( $probe );
};
$content_probe = static function ( $content ) {
    return $content . '<strong class="stage5-filtered-marker">Filtered marker</strong>';
};
add_filter( 'posts_pre_query', $query_probe, 10, 2 );
add_filter( 'the_content', $content_probe, 99 );
$probe_markup = $render_template();
remove_filter( 'the_content', $content_probe, 99 );
remove_filter( 'posts_pre_query', $query_probe, 10 );
remove_filter( 'ehpmi_testimonials_active', $activate );

$check( array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) === $captured_orderby, 'deterministic menu_order ID query' );
$check( false !== strpos( $probe_markup, '<article id="testimonial-127" class="testimonial">' ), 'stable semantic article' );
$check( false !== strpos( $probe_markup, '<blockquote class="quote">' ), 'semantic blockquote' );
$check( false !== strpos( $probe_markup, '<cite>' ), 'semantic cite' );
$check( false !== strpos( $probe_markup, 'stage5-filtered-marker' ), 'quotation passed through the_content' );
$check( false !== strpos( $probe_markup, 'alt=""' ), 'attachment alt metadata rendered verbatim' );

$template_path = get_stylesheet_directory() . '/template-parts/testimonials.php';
$template_source = file_get_contents( $template_path );
$check( false === strpos( $template_source, '<?= $testimonial->post_content' ), 'no raw short-echo content' );
$check( ! preg_match( '/echo\\s+\\$testimonial->post_content/', $template_source ), 'no raw echo content' );
$check( '' === (string) $wpdb->last_error, 'database read error' );

$report = array(
    'result' => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'testimonials' => $rows,
    'published' => $published,
    'migration_writes' => 0,
    'default_markup_bytes' => strlen( $default_markup ),
    'activated_zero_markup_bytes' => strlen( $zero_markup ),
    'semantic_probe_bytes' => strlen( $probe_markup ),
);
WP_CLI::line( 'STAGE5_VERIFY_JSON=' . wp_json_encode( $report, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
if ( $failures ) {
    WP_CLI::error( 'Stage 5 verifier failed.' );
}
