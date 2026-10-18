<?php
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'||get_option('home')!=='https://dev.ehpmi.org'){throw new RuntimeException('STOP-WRONG-ENV');}
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));if(!$admins)throw new RuntimeException('No existing administrator');wp_set_current_user((int)$admins[0]);
$report=array('mode'=>'READ_ONLY_CAPABILITY_CHECKED_MEDIA_REST_GET','media'=>array());
foreach(array(147,212,484,2371) as $id){$request=new WP_REST_Request('GET','/wp/v2/media/'.$id);$request->set_param('context','edit');$response=rest_do_request($request);$data=$response->get_data();if($response->is_error()||$response->get_status()!==200||empty($data['alt_text'])||empty($data['source_url']))throw new RuntimeException('Media read failed '.$id);$report['media'][$id]=array('status'=>200,'alt'=>$data['alt_text'],'source_url'=>$data['source_url']);}
$report['status']='PASS';$report['writes']=0;echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
