<?php
/** Stage 8 dev-only media migration: dry-run by default, explicit apply/rollback. */
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'||get_option('home')!=='https://dev.ehpmi.org'){throw new RuntimeException('STOP-WRONG-ENV');}
global $wpdb;
$base='/home2/nykvymmy/backups/entity-stage8-2026-10-18';
$plan=json_decode(file_get_contents($base.'/alt-plan.json'),true,512,JSON_THROW_ON_ERROR);
$mode=$args[0]??'dry-run';
$plugin='real-media-library-lite/index.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
$marker='ehpmi_stage8_media_applied';
$report=array('mode'=>$mode,'expected_alt_updates'=>63,'alt_updates'=>0,'body_posts'=>array(),'plugin_files_deleted'=>0,'uploads_moved'=>0);
if(count($plan)!==63){throw new RuntimeException('Unexpected plan size');}
$body_plan=array();
foreach($plan as $change){
 $id=$change['ID'];$current=get_post_meta($id,'_wp_attachment_image_alt',true);
 if($mode==='rollback'){$expected=$change['after'];}else{$expected=$change['before'];}
 if($current!==$expected&&!(get_option($marker)&&$current===$change['after'])){throw new RuntimeException('Alt source drift: '.$id);}
}
foreach(get_posts(array('post_type'=>array('page','project','post'),'post_status'=>'publish','numberposts'=>-1)) as $post){
 $processor=new WP_HTML_Tag_Processor($post->post_content);$count=0;
 while($processor->next_tag('IMG')){
  if(trim((string)$processor->get_attribute('alt'))!=='')continue;
  $class=(string)$processor->get_attribute('class');$src=(string)$processor->get_attribute('src');
  foreach($plan as $change){
   if(empty($change['sync_body_alt']))continue;
   $matches=(bool)preg_match('/(?:^|\s)wp-image-'.$change['ID'].'(?:\s|$)/',$class);
   foreach($change['source_files'] as $file){if(strpos(rawurldecode($src),'/uploads/'.$file)!==false){$matches=true;break;}}
   if(!$matches)continue;$processor->set_attribute('alt',$change['after']);$count++;break;
  }
 }
 $after=$processor->get_updated_html();
 if($after!==$post->post_content){$body_plan[$post->ID]=array('before'=>$post->post_content,'after'=>$after,'count'=>$count,'modified'=>$post->post_modified,'modified_gmt'=>$post->post_modified_gmt);}
}
if($mode==='dry-run'){
 if(get_option($marker)){throw new RuntimeException('Already migrated');}
 if(!is_plugin_active($plugin)){throw new RuntimeException('RML source state drift');}
 if((int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}realmedialibrary")!==31||(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}realmedialibrary_posts")!==401){throw new RuntimeException('RML counts drift');}
 $body_ids=array_keys($body_plan);sort($body_ids);
 if($body_ids!==array(21,461,1690,1692)||array_sum(array_column($body_plan,'count'))!==5){throw new RuntimeException('Unexpected body delta scope');}
 $report['planned_body_posts']=array_map(static fn($x)=>array('images'=>$x['count'],'before_sha256'=>hash('sha256',$x['before']),'after_sha256'=>hash('sha256',$x['after'])),$body_plan);
 file_put_contents($base.'/body-plan.json',wp_json_encode($body_plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";return;
}
if($mode==='apply'){
 if(get_option($marker)){throw new RuntimeException('Already migrated; verify instead of reapplying');}
 if(!is_plugin_active($plugin)){throw new RuntimeException('Unexpected RML activation state');}
 $expected=json_decode(file_get_contents($base.'/body-plan.json'),true,512,JSON_THROW_ON_ERROR);
 if($expected!==$body_plan){throw new RuntimeException('Body source drift');}
 if(hash_file('sha256',WP_PLUGIN_DIR.'/wp-optimize/cache/class-wpo-cache-rules.php')!=='fc52f550d41c8506a55fe8d7bd65b37bfbbc2c59e8fb7165d223ad3ea4eda682'){throw new RuntimeException('WP-Optimize date patch required before content update');}
 foreach($plan as $change){update_post_meta($change['ID'],'_wp_attachment_image_alt',$change['after']);$report['alt_updates']++;}
 foreach($body_plan as $id=>$change){
  $result=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$change['after'],'post_modified'=>$change['modified'],'post_modified_gmt'=>$change['modified_gmt'])),true);
  if(is_wp_error($result)||get_post_field('post_content',$id)!==$change['after']){throw new RuntimeException('Body update failure: '.$id);}
  $report['body_posts'][$id]=$change['count'];
 }
 deactivate_plugins($plugin);
 update_option($marker,array('alt_plan_sha256'=>hash_file('sha256',$base.'/alt-plan.json'),'body_ids'=>array_keys($body_plan),'rml_files_retained'=>true),false);
}elseif($mode==='rollback'||$mode==='validate-rollback'){
 if($mode==='rollback'&&!is_file(WP_PLUGIN_DIR.'/real-media-library-lite/index.php')){throw new RuntimeException('Restore the verified RML plugin archive before data rollback; no data rollback has run');}
 $body_plan=json_decode(file_get_contents($base.'/body-plan.json'),true,512,JSON_THROW_ON_ERROR);
 foreach($body_plan as $id=>$change){if(get_post_field('post_content',$id)!==$change['after']){throw new RuntimeException('Rollback content drift: '.$id);}}
 if($mode==='rollback'){
  foreach($plan as $change){if($change['before_exists']){update_post_meta($change['ID'],'_wp_attachment_image_alt',$change['before']);}else{delete_post_meta($change['ID'],'_wp_attachment_image_alt');}}
  foreach($body_plan as $id=>$change){wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$change['before'],'post_modified'=>$change['modified'],'post_modified_gmt'=>$change['modified_gmt'])),true);}
  $result=activate_plugin($plugin);if(is_wp_error($result)){throw new RuntimeException($result->get_error_message());}delete_option($marker);
 }
}else{throw new RuntimeException('Invalid mode');}
$report['status']='PASS';echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
