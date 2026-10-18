<?php
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'||get_option('home')!=='https://dev.ehpmi.org'){throw new RuntimeException('STOP-WRONG-ENV');}
global $wpdb;require_once ABSPATH.'wp-admin/includes/plugin.php';
$base='/home2/nykvymmy/backups/entity-stage8-2026-10-18';$fail=array();$ok=static function($yes,$message)use(&$fail){if(!$yes)$fail[]=$message;};
$plan=json_decode(file_get_contents($base.'/alt-plan.json'),true,512,JSON_THROW_ON_ERROR);$body=json_decode(file_get_contents($base.'/body-plan.json'),true,512,JSON_THROW_ON_ERROR);
$before=json_decode(file_get_contents($base.'/invariants-before.json'),true,512,JSON_THROW_ON_ERROR);
$r=array('alt_updates'=>count($plan),'body_posts'=>array_keys($body));
foreach($plan as $c){$ok(get_post_meta($c['ID'],'_wp_attachment_image_alt',true)===$c['after'],'Alt '.$c['ID']);}
foreach($body as $id=>$c){$ok(get_post_field('post_content',$id)===$c['after'],'Body '.$id);$ok(serialize_blocks(parse_blocks($c['after']))===$c['after'],'Block serialization '.$id);}
foreach($before['posts'] as $p){$id=(int)$p['ID'];$current=get_post($id);$expected=isset($body[$id])?hash('sha256',$body[$id]['after']):$p['body_sha256'];$ok(hash('sha256',$current->post_content)===$expected,'Outside body drift '.$id);$ok($current->post_type===$p['post_type']&&$current->post_status===$p['post_status']&&$current->post_title===$p['post_title'],'Record identity '.$id);}
$ok(!is_plugin_active('real-media-library-lite/index.php'),'RML inactive');$ok(!file_exists(WP_PLUGIN_DIR.'/real-media-library-lite'),'RML plugin directory absent after explicitly authorized deletion');
$r['rml']=array('folders'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}realmedialibrary"),'relations'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}realmedialibrary_posts"));$ok($r['rml']===array('folders'=>31,'relations'=>401),'RML table counts');
$export=json_decode(file_get_contents($base.'/rml-export.json'),true,512,JSON_THROW_ON_ERROR);
$ok($wpdb->get_results("SELECT * FROM {$wpdb->prefix}realmedialibrary",ARRAY_A)===$export['folders'],'RML folder bytes');$ok($wpdb->get_results("SELECT * FROM {$wpdb->prefix}realmedialibrary_posts",ARRAY_A)===$export['relations'],'RML relation bytes');
$r['attachment_count']=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment'");$ok($r['attachment_count']===467,'Attachment count');
foreach($before['attachments'] as $a){$ok(get_post_meta($a['ID'],'_wp_attached_file',true)===$a['file'],'Attachment path '.$a['ID']);$ok(hash('sha256',wp_json_encode(wp_get_attachment_metadata($a['ID'])))===$a['metadata_sha256'],'Attachment derivatives '.$a['ID']);$ok(file_exists(get_attached_file($a['ID'])),'Attachment exists '.$a['ID']);}
$ok(hash('sha256',wp_json_encode($wpdb->get_results("SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget_%' OR option_name='sidebars_widgets' ORDER BY option_name",ARRAY_A)))===$before['widgets_hash'],'Widget options unchanged');
foreach($before['newsletter'] as $t=>$v){$ok((int)$wpdb->get_var("SELECT COUNT(*) FROM `$t`")===$v['count']&&hash('sha256',wp_json_encode($wpdb->get_results("SELECT * FROM `$t`",ARRAY_A)))===$v['hash'],'Newsletter invariant '.$t);}
$f=get_post(2458);$ok($f->post_status==='trash'&&$f->menu_order===15,'Fixture 2458');$ok(count(get_option('ehpmi_legacy_redirects',array()))===141,'141 redirects');
$ok(hash_file('sha256',WP_PLUGIN_DIR.'/ehpmi-core/ehpmi-core.php')===$before['core'],'Core unchanged');$ok(hash_file('sha256',get_template_directory().'/css/style.css')===$before['files']['css/style.css'],'Accepted CSS unchanged');
$ok(hash_file('sha256',WP_PLUGIN_DIR.'/wp-optimize/cache/class-wpo-cache-rules.php')==='fc52f550d41c8506a55fe8d7bd65b37bfbbc2c59e8fb7165d223ad3ea4eda682','WP-Optimize patch hash');
$r['status']=$fail?'FAIL':'PASS';$r['failures']=$fail;echo wp_json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";if($fail){WP_CLI::error('Stage 8 verification failed');}
