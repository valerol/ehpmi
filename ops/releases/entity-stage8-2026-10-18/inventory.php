<?php
global $wpdb;
if (!in_array(DB_NAME, array('nykvymmy_ehpmidev','nykvymmy_ehpmi'), true)) { throw new RuntimeException('Unexpected database'); }
$r=array('root'=>rtrim(ABSPATH,'/'),'database'=>DB_NAME,'home'=>get_option('home'),'siteurl'=>get_option('siteurl'),'php'=>PHP_VERSION,'wp'=>get_bloginfo('version'));
$r['records_hash']=hash('sha256',wp_json_encode($wpdb->get_results("SELECT * FROM {$wpdb->posts} ORDER BY ID",ARRAY_A)));
$r['content_hash']=hash('sha256',wp_json_encode($wpdb->get_results("SELECT ID,post_type,post_status,post_parent,menu_order,post_content,post_excerpt FROM {$wpdb->posts} ORDER BY ID",ARRAY_A)));
$r['widgets_hash']=hash('sha256',wp_json_encode($wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget_%' OR option_name='sidebars_widgets' ORDER BY option_name",ARRAY_A)));
$r['fixture']=$wpdb->get_row("SELECT ID,post_status,menu_order FROM {$wpdb->posts} WHERE ID=2458",ARRAY_A);
$r['markers']=$wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'ehpmi%stage%' ORDER BY option_name");
foreach($wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}newsletter%'") as $t){$r['newsletter'][$t]=array('count'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM `$t`"),'hash'=>hash('sha256',wp_json_encode($wpdb->get_results("SELECT * FROM `$t`",ARRAY_A))));}
foreach(array('functions.php','css/style.less','css/style.css','css/structural-regions.css','onload.js') as $f){$r['files'][$f]=hash_file('sha256',get_template_directory().'/'.$f);}
$r['core']=hash_file('sha256',WP_PLUGIN_DIR.'/ehpmi-core/ehpmi-core.php');
require_once ABSPATH.'wp-admin/includes/plugin.php';
foreach(get_plugins() as $path=>$p){$r['plugins'][$path]=array('version'=>$p['Version'],'active'=>is_plugin_active($path));}
if(DB_NAME==='nykvymmy_ehpmidev'){
 foreach($wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}realmedialibrary%'") as $t){$r['rml'][$t]=$wpdb->get_results("SELECT * FROM `$t`",ARRAY_A);}
 $r['attachments']=array();
 foreach(get_posts(array('post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC')) as $a){$r['attachments'][]=array('ID'=>$a->ID,'title'=>$a->post_title,'parent'=>(int)$a->post_parent,'mime'=>$a->post_mime_type,'file'=>get_post_meta($a->ID,'_wp_attached_file',true),'url'=>wp_get_attachment_url($a->ID),'alt'=>get_post_meta($a->ID,'_wp_attachment_image_alt',true),'exists'=>file_exists(get_attached_file($a->ID)),'metadata'=>wp_get_attachment_metadata($a->ID));}
 $r['posts']=$wpdb->get_results("SELECT ID,post_type,post_status,post_title,post_content FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','attachment') ORDER BY ID",ARRAY_A);
 $r['meta']=$wpdb->get_results("SELECT post_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE meta_key NOT IN ('_wp_attachment_metadata','_edit_lock','_edit_last') ORDER BY meta_id",ARRAY_A);
 // Exclude security/user/password/session options; evidence contains only media/content references.
 $r['media_options']=$wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name IN ('theme_mods_ehpmi','site_icon') OR option_name LIKE 'widget_%' ORDER BY option_name",ARRAY_A);
}
echo wp_json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
