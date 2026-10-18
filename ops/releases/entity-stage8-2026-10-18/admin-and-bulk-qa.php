<?php
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'||get_option('home')!=='https://dev.ehpmi.org'){throw new RuntimeException('STOP-WRONG-ENV');}
global $wpdb;
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));if(!$admins){throw new RuntimeException('No existing administrator');}wp_set_current_user((int)$admins[0]);
$warnings=array();set_error_handler(static function($level,$message,$file,$line)use(&$warnings){if(strpos($file,'wp-optimize')!==false&&($level&E_WARNING)){$warnings[]=array('message'=>$message,'file'=>str_replace(ABSPATH,'',$file),'line'=>$line);return true;}return false;});
$r=array('mode'=>'capability-checked-internal-native-media-REST-save-reload','media'=>array(),'bulk_posts'=>array());
foreach(array(147,212,484,2371) as $id){
 if(!get_post($id)||get_post_type($id)!=='attachment'){continue;}
 $before=get_post($id,ARRAY_A);$alt=get_post_meta($id,'_wp_attachment_image_alt',true);
 $save=new WP_REST_Request('POST','/wp/v2/media/'.$id);$save->set_param('alt_text',$alt);$response=rest_do_request($save);
 if($response->is_error()||$response->get_status()!==200){throw new RuntimeException('Media REST save failed '.$id.' '.wp_json_encode($response->get_data()));}
 $reload=new WP_REST_Request('GET','/wp/v2/media/'.$id);$reload->set_param('context','edit');$loaded=rest_do_request($reload);$data=$loaded->get_data();
 if($loaded->is_error()||$data['alt_text']!==$alt){throw new RuntimeException('Media REST reload failed '.$id);}
 $prepared=wp_prepare_attachment_for_js($id);
 if($prepared['alt']!==$alt||empty($prepared['url'])){throw new RuntimeException('Native Media Library data failed '.$id);}
 $wpdb->update($wpdb->posts,array('post_modified'=>$before['post_modified'],'post_modified_gmt'=>$before['post_modified_gmt']),array('ID'=>$id));clean_post_cache($id);
 $r['media'][$id]=array('save'=>$response->get_status(),'reload'=>$loaded->get_status(),'alt'=>$alt,'native_library_data'=>true,'timestamps_restored'=>true);
}
foreach(array(1524,1676,1731,1733,1858) as $id){
 $before=get_post($id,ARRAY_A);$result=wp_update_post(array('ID'=>$id,'post_modified'=>$before['post_modified'],'post_modified_gmt'=>$before['post_modified_gmt']),true);
 if(is_wp_error($result)){throw new RuntimeException('Bulk update failed '.$id);}
 // WordPress intentionally refreshes these on every save, even for identical content.
 $wpdb->update($wpdb->posts,array('post_modified'=>$before['post_modified'],'post_modified_gmt'=>$before['post_modified_gmt']),array('ID'=>$id));clean_post_cache($id);
 $after=get_post($id,ARRAY_A);if($after!==$before){throw new RuntimeException('Idempotent bulk test changed post '.$id);}$r['bulk_posts'][]=$id;
}
restore_error_handler();$r['wp_optimize_warnings']=$warnings;$r['status']=$warnings?'FAIL':'PASS';echo wp_json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";if($warnings){WP_CLI::error('WP-Optimize warning repeated');}
