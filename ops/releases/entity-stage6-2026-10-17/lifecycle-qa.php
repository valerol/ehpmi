<?php
$failures = array();
$test_id = 0;
$result = array();

try {
    $test_id = wp_insert_post(array(
        'post_type' => 'hero_slide',
        'post_status' => 'draft',
        'post_title' => 'Stage 6 temporary lifecycle check',
        'menu_order' => 25,
    ), true);
    if (is_wp_error($test_id)) {
        throw new RuntimeException($test_id->get_error_message());
    }
    set_post_thumbnail($test_id, 2369);
    clean_post_cache($test_id);
    $created = get_post($test_id);
    if (!$created || $created->post_status !== 'draft' || (int) $created->menu_order !== 25 || (int) get_post_thumbnail_id($test_id) !== 2369) {
        $failures[] = 'add/save/reload';
    }

    $updated = wp_update_post(array(
        'ID' => $test_id,
        'post_title' => 'Stage 6 temporary lifecycle check edited',
        'post_status' => 'publish',
        'menu_order' => 15,
    ), true);
    if (is_wp_error($updated)) {
        throw new RuntimeException($updated->get_error_message());
    }
    clean_post_cache($test_id);
    $edited = get_post($test_id);
    if (!$edited || $edited->post_status !== 'publish' || (int) $edited->menu_order !== 15 || $edited->post_title !== 'Stage 6 temporary lifecycle check edited') {
        $failures[] = 'edit/reorder/publish/save/reload';
    }

    $ordered = get_posts(array(
        'post_type' => 'hero_slide',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'),
        'fields' => 'ids',
    ));
    if ($ordered !== array(2370, $test_id, 2372, 2374)) {
        $failures[] = 'published deterministic reorder';
    }

    $unpublished = wp_update_post(array('ID' => $test_id, 'post_status' => 'draft'), true);
    if (is_wp_error($unpublished)) {
        throw new RuntimeException($unpublished->get_error_message());
    }
    clean_post_cache($test_id);
    $reloaded = get_post($test_id);
    if (!$reloaded || $reloaded->post_status !== 'draft') {
        $failures[] = 'unpublish/save/reload';
    }
} catch (Throwable $error) {
    $failures[] = 'exception: ' . $error->getMessage();
} finally {
    if ($test_id) {
        wp_delete_post($test_id, true);
        clean_post_cache($test_id);
        if (get_post($test_id)) {
            $failures[] = 'temporary record cleanup';
        }
    }
}

$result = array(
    'status' => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'temporary_id' => (int) $test_id,
    'cleanup' => $test_id && !get_post($test_id) ? 'PASS' : 'FAIL',
    'scope' => 'automated WordPress lifecycle; authenticated Admin UI remains a separate manual acceptance step',
);
echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
exit($failures ? 1 : 0);
