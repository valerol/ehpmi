<?php
$failures = array();

$expectations = array(
    2370 => array(10, 'Child wearing a mask near an industrial facility', 2369, 'Child wearing a mask near an industrial facility', '6ae313df6e94420cf9aaeba494eb5ae1890e065d3f90969f82a19fbd28646155'),
    2372 => array(20, 'Child wearing a mask and holding a teddy bear', 2371, 'Child wearing a mask and holding a teddy bear', 'c34cf654207dd3412a91c27376af9f75297a1665deb007d03440c48fbd2086bf'),
    2374 => array(30, 'Child coughing', 2373, 'Child coughing', '2f3cc0a836f9642e9454b46e6c1220739dbe0fc8579e4189a8a39c9c563946a3'),
);

$slides = get_posts(array(
    'post_type' => 'hero_slide',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => array('menu_order' => 'ASC', 'ID' => 'ASC'),
));

if (array_map('intval', wp_list_pluck($slides, 'ID')) !== array_keys($expectations)) {
    $failures[] = 'hero ID/order inventory changed';
}

$inventory = array();
foreach ($slides as $slide) {
    $id = (int) $slide->ID;
    if (!isset($expectations[$id])) {
        $failures[] = 'unexpected hero slide ' . $id;
        continue;
    }
    list($order, $title, $attachment_id, $alt, $sha) = $expectations[$id];
    $actual_attachment_id = (int) get_post_thumbnail_id($slide);
    $file = $actual_attachment_id ? get_attached_file($actual_attachment_id) : '';
    $row = array(
        'id' => $id,
        'status' => $slide->post_status,
        'menu_order' => (int) $slide->menu_order,
        'title' => $slide->post_title,
        'attachment_id' => $actual_attachment_id,
        'alt' => $actual_attachment_id ? (string) get_post_meta($actual_attachment_id, '_wp_attachment_image_alt', true) : null,
        'file_exists' => $file ? is_file($file) : false,
        'file_sha256' => $file && is_file($file) ? hash_file('sha256', $file) : null,
    );
    $inventory[] = $row;
    if ($row['status'] !== 'publish' || $row['menu_order'] !== $order || $row['title'] !== $title || $row['attachment_id'] !== $attachment_id || $row['alt'] !== $alt || !$row['file_exists'] || $row['file_sha256'] !== $sha) {
        $failures[] = 'hero invariant mismatch for ' . $id;
    }
}

$template = get_theme_file_path('/template-parts/slider.php');
$source = file_get_contents($template);
$onload_source = file_get_contents(get_theme_file_path('/onload.js'));
$source_checks = array(
    'no_title_alt' => strpos($source, "'alt'     => get_the_title") === false,
    'no_generic_fallback' => !preg_match('/Hero slide [123]/', $source),
    'attachment_alt' => strpos($source, "'_wp_attachment_image_alt'") !== false,
    'menu_order_id' => strpos($source, "'menu_order' => 'ASC'") !== false && strpos($source, "'ID'         => 'ASC'") !== false,
    'file_guard' => strpos($source, "! is_file( \$image_file )") !== false,
    'desktop_picture_source' => strpos($source, 'media="(min-width: 1025px)"') !== false,
    'data_uri_placeholder' => strpos($source, 'data:image/gif;base64,') !== false,
    'desktop_carousel_marker' => strpos($source, 'data-ehpmi-desktop-carousel="true"') !== false,
    'no_automatic_bootstrap_ride' => strpos($source, 'data-bs-ride=') === false,
    'general_init_excludes_hero' => strpos($onload_source, '.carousel:not([data-ehpmi-desktop-carousel])') !== false,
    'responsive_carousel_lifecycle' => strpos($onload_source, "desktopMedia.addEventListener('change', updateHeroCarousels)") !== false && strpos($onload_source, 'instance.cycle()') !== false && strpos($onload_source, 'instance.dispose()') !== false,
);
foreach ($source_checks as $name => $pass) {
    if (!$pass) {
        $failures[] = 'source check failed: ' . $name;
    }
}

$render = static function () use ($template) {
    ob_start();
    include $template;
    return ob_get_clean();
};
$extract_images = static function ($html) {
    preg_match_all('/<img\b[^>]*>/i', $html, $matches);
    $rows = array();
    foreach ($matches[0] as $tag) {
        preg_match('/\balt="([^"]*)"/i', $tag, $alt);
        preg_match('/\bloading="([^"]*)"/i', $tag, $loading);
        preg_match('/\bfetchpriority="([^"]*)"/i', $tag, $priority);
        $rows[] = array(
            'alt' => html_entity_decode($alt[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'loading' => $loading[1] ?? '',
            'fetchpriority' => $priority[1] ?? '',
        );
    }
    return $rows;
};

$managed_images = $extract_images($render());
$expected_alts = array_column($expectations, 3);
if (array_column($managed_images, 'alt') !== $expected_alts) {
    $failures[] = 'managed render alt/order mismatch';
}
if (count($managed_images) !== 3 || ($managed_images[0]['loading'] ?? '') !== 'eager' || ($managed_images[0]['fetchpriority'] ?? '') !== 'high' || ($managed_images[1]['loading'] ?? '') !== 'lazy' || ($managed_images[2]['loading'] ?? '') !== 'lazy') {
    $failures[] = 'managed loading contract mismatch';
}

$managed_render = $render();
if (substr_count($managed_render, '<picture>') !== 3 || substr_count($managed_render, 'media="(min-width: 1025px)"') !== 3 || substr_count($managed_render, 'src="data:image/gif;base64,') !== 3) {
    $failures[] = 'responsive picture contract mismatch';
}

$empty_query = static function ($posts, $query) { return array(); };
add_filter('posts_pre_query', $empty_query, 10, 2);
$fallback_images = $extract_images($render());
remove_filter('posts_pre_query', $empty_query, 10);
if (array_column($fallback_images, 'alt') !== $expected_alts || count($fallback_images) !== 3) {
    $failures[] = 'fallback render mismatch';
}

$sidebars = wp_get_sidebars_widgets();
$slider_widgets = isset($sidebars['slider-text']) ? array_values($sidebars['slider-text']) : array();
if ($slider_widgets !== array('block-2')) {
    $failures[] = 'global slider text ownership changed';
}
$block_widgets = get_option('widget_block', array());
$block_instance = isset($block_widgets[2]) ? $block_widgets[2] : array();
$widget_hash = hash('sha256', maybe_serialize($block_instance));
if ($widget_hash !== '264d3a5dea705381b45bb43be47b86e580407e5865c8cc11d23caee00b449e3e') {
    $failures[] = 'global slider text content changed';
}

$result = array(
    'status' => $failures ? 'FAIL' : 'PASS',
    'failures' => $failures,
    'identity' => array('abspath' => ABSPATH, 'database' => DB_NAME, 'home' => home_url('/')),
    'slides' => $inventory,
    'source_checks' => $source_checks,
    'managed_images' => $managed_images,
    'fallback_images' => $fallback_images,
    'slider_text_widgets' => $slider_widgets,
    'slider_text_hash' => $widget_hash,
    'template_sha256' => hash_file('sha256', $template),
);

echo wp_json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
exit($failures ? 1 : 0);
