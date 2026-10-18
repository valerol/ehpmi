<?php
$staged_core = '/home2/nykvymmy/backups/entity-stage6-2026-10-17/stage/ehpmi-core.php';
if (!function_exists('ehpmi_core_order_hero_slide_admin_query')) {
    require_once $staged_core;
}

$failures = array();
$original_wp_the_query = isset($GLOBALS['wp_the_query']) ? $GLOBALS['wp_the_query'] : null;
set_current_screen('edit-hero_slide');

$default_query = new WP_Query();
$default_query->set('post_type', 'hero_slide');
$default_query->set('post_status', array('publish', 'draft', 'pending', 'private', 'future'));
$default_query->set('posts_per_page', -1);
$GLOBALS['wp_the_query'] = $default_query;
ehpmi_core_order_hero_slide_admin_query($default_query);
$default_orderby = $default_query->get('orderby');
$default_posts = $default_query->get_posts();
$default_rows = array_map(static function ($post) {
    return array(
        'id' => (int) $post->ID,
        'status' => $post->post_status,
        'menu_order' => (int) $post->menu_order,
        'title' => $post->post_title,
    );
}, $default_posts);

$expected_orderby = array('menu_order' => 'ASC', 'ID' => 'ASC');
if ($default_orderby !== $expected_orderby) {
    $failures[] = 'default orderby mismatch';
}
if (array_column($default_rows, 'menu_order') !== array(10, 20, 30)) {
    $failures[] = 'default row order mismatch';
}
if (array_column($default_rows, 'id') !== array(2370, 2372, 2374)) {
    $failures[] = 'default row ID order mismatch';
}

$title_query = new WP_Query();
$title_query->set('post_type', 'hero_slide');
$title_query->set('orderby', 'title');
$title_query->set('order', 'DESC');
$GLOBALS['wp_the_query'] = $title_query;
ehpmi_core_order_hero_slide_admin_query($title_query);
if ('title' !== $title_query->get('orderby') || 'DESC' !== $title_query->get('order')) {
    $failures[] = 'explicit title sort changed';
}

$order_query = new WP_Query();
$order_query->set('post_type', 'hero_slide');
$order_query->set('orderby', 'menu_order');
$order_query->set('order', 'DESC');
$GLOBALS['wp_the_query'] = $order_query;
ehpmi_core_order_hero_slide_admin_query($order_query);
if ($order_query->get('orderby') !== array('menu_order' => 'DESC', 'ID' => 'ASC')) {
    $failures[] = 'explicit Order sort mismatch';
}

$post_query = new WP_Query();
$post_query->set('post_type', 'post');
$GLOBALS['wp_the_query'] = $post_query;
ehpmi_core_order_hero_slide_admin_query($post_query);
if ($post_query->get('orderby')) {
    $failures[] = 'other post type query changed';
}

$columns = apply_filters('manage_hero_slide_posts_columns', array(
    'cb' => 'Checkbox',
    'title' => 'Title',
    'date' => 'Date',
));
if (array_keys($columns) !== array('cb', 'title', 'ehpmi_hero_order', 'date') || 'Order' !== $columns['ehpmi_hero_order']) {
    $failures[] = 'Order column mismatch';
}

$sortable = apply_filters('manage_edit-hero_slide_sortable_columns', array('title' => 'title', 'date' => 'date'));
if (($sortable['ehpmi_hero_order'] ?? '') !== 'menu_order') {
    $failures[] = 'Order column is not sortable';
}

ob_start();
do_action('manage_hero_slide_posts_custom_column', 'ehpmi_hero_order', 2458);
$temporary_order_output = trim(ob_get_clean());
if ('15' !== $temporary_order_output) {
    $failures[] = 'Order column output mismatch';
}

$other_columns = apply_filters('manage_post_posts_columns', array('title' => 'Title', 'date' => 'Date'));
if ($other_columns !== array('title' => 'Title', 'date' => 'Date')) {
    $failures[] = 'other post type columns changed';
}

$temporary = get_post(2458);
$temporary_state = $temporary ? array(
    'id' => (int) $temporary->ID,
    'status' => $temporary->post_status,
    'menu_order' => (int) $temporary->menu_order,
    'title' => $temporary->post_title,
) : null;
if ($temporary_state !== array(
    'id' => 2458,
    'status' => 'trash',
    'menu_order' => 15,
    'title' => 'Title: Stage 6 validation test reordered',
)) {
    $failures[] = 'operator fixture changed since mobile-gate preflight';
}

$GLOBALS['wp_the_query'] = $original_wp_the_query;

echo wp_json_encode(array(
    'status' => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'default_orderby' => $default_orderby,
    'default_rows' => $default_rows,
    'columns' => $columns,
    'sortable_columns' => $sortable,
    'temporary_order_output' => $temporary_order_output,
    'explicit_title_sort' => array('orderby' => $title_query->get('orderby'), 'order' => $title_query->get('order')),
    'explicit_order_sort' => $order_query->get('orderby'),
    'other_post_type_orderby' => $post_query->get('orderby'),
    'other_post_type_columns' => $other_columns,
    'temporary_state_after_test' => $temporary_state,
    'fixture_note' => 'ID 2458 was already Trash/order 15 at the resumed mobile-gate preflight and was not modified by this verifier.',
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
exit($failures ? 1 : 0);
