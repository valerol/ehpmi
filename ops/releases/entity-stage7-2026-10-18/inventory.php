<?php
global $wpdb;
$is_dev = DB_NAME === 'nykvymmy_ehpmidev';
$root = rtrim(ABSPATH, '/');
$result = array('root' => $root, 'database' => DB_NAME, 'home' => get_option('home'), 'siteurl' => get_option('siteurl'), 'wp_version' => get_bloginfo('version'));
$files = array('singular.php','functions.php','css/style.less','css/style.css','template-parts/map.php','template-parts/contact-forms.php','template-parts/newsletter.php','template-parts/slider.php','onload.js','footer.php');
foreach ($files as $file) { $result['files'][$file] = hash_file('sha256', get_template_directory().'/'.$file); }
$result['core_file'] = is_file(WP_PLUGIN_DIR.'/ehpmi-core/ehpmi-core.php') ? hash_file('sha256',WP_PLUGIN_DIR.'/ehpmi-core/ehpmi-core.php') : null;
$result['counts'] = $wpdb->get_results("SELECT post_type,post_status,COUNT(*) count FROM {$wpdb->posts} GROUP BY post_type,post_status ORDER BY post_type,post_status", ARRAY_A);
$result['records_hash'] = hash('sha256',wp_json_encode($wpdb->get_results("SELECT ID,post_type,post_status,post_parent,menu_order,post_name,post_title,post_content,post_excerpt FROM {$wpdb->posts} ORDER BY ID",ARRAY_A)));
foreach ($wpdb->get_results("SELECT ID,post_type,post_status,post_parent,menu_order,post_name,post_title,post_content,post_excerpt FROM {$wpdb->posts} ORDER BY ID",ARRAY_A) as $row) { $result['record_hashes'][$row['ID']]=hash('sha256',wp_json_encode($row)); }
$result['page_records_hash'] = hash('sha256',wp_json_encode($wpdb->get_results("SELECT ID,post_status,post_parent,menu_order,post_name,post_title,post_content FROM {$wpdb->posts} WHERE post_type='page' ORDER BY ID",ARRAY_A)));
$result['widget_options_hash'] = hash('sha256',wp_json_encode($wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget_%' OR option_name='sidebars_widgets' ORDER BY option_name",ARRAY_A)));
$result['migration_markers'] = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'ehpmi%stage7%' ORDER BY option_name");
$result['fixture_2458'] = $wpdb->get_row("SELECT ID,post_status,menu_order,post_title FROM {$wpdb->posts} WHERE ID=2458",ARRAY_A);
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}newsletter%'");
foreach ($tables as $table) { $result['newsletter_tables'][$table] = array('count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `$table`"),'hash'=>hash('sha256',wp_json_encode($wpdb->get_results("SELECT * FROM `$table`",ARRAY_A)))); }
if ($is_dev) {
    $result['pages'] = array();
    foreach (get_posts(array('post_type'=>'page','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC')) as $page) {
        $meta = get_post_meta($page->ID);
        $result['pages'][] = array('ID'=>$page->ID,'title'=>$page->post_title,'slug'=>$page->post_name,'parent'=>$page->post_parent,'order'=>$page->menu_order,'url'=>get_permalink($page),'content'=>$page->post_content,'meta'=>array_intersect_key($meta,array_flip(array('point','leader','team','member','_wp_page_template'))));
    }
    $sidebars = wp_get_sidebars_widgets();
    foreach (array('slider-text','map-text','contact-form-1','contact-form-2','newsletter','footer-text','footer-menu-1','footer-menu-2','footer-contacts','footer-social','footer-copyright') as $region) {
        foreach (($sidebars[$region] ?? array()) as $id) {
            if (preg_match('/^(.+)-(\d+)$/',$id,$m)) { $options=get_option('widget_'.$m[1]); $result['widgets'][$region][$id]=$options[(int)$m[2]] ?? null; }
        }
    }
    foreach (get_posts(array('post_type'=>'wpcf7_contact_form','post_status'=>'any','numberposts'=>-1)) as $form) { $result['contact_forms'][]=array('ID'=>$form->ID,'title'=>$form->post_title,'form'=>get_post_meta($form->ID,'_form',true)); }
    $result['redirects_hash'] = hash('sha256',wp_json_encode(get_option('ehpmi_legacy_redirects')));
    $result['plugins'] = array();
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    foreach (get_plugins() as $path=>$plugin) { $result['plugins'][$path]=array('version'=>$plugin['Version'],'active'=>is_plugin_active($path)); }
}
echo wp_json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
