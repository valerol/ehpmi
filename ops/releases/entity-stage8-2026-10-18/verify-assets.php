<?php
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'){throw new RuntimeException('STOP-WRONG-ENV');}
$manifest=json_decode(file_get_contents('/tmp/ehpmi-stage8-vendor-assets.json'),true,512,JSON_THROW_ON_ERROR);$fail=array();foreach($manifest['files'] as $f){$path=get_template_directory().'/assets/vendor/'.$f['path'];if(!is_file($path)||filesize($path)!==$f['bytes']||hash_file('sha256',$path)!==$f['sha256'])$fail[]=$f['path'];}
$native=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','numberposts'=>-1,'fields'=>'ids'));foreach($native as $id){$a=wp_prepare_attachment_for_js($id);if(empty($a['url'])||$a['id']!==$id)$fail[]='native_media_'.$id;}
if(function_exists('wp_rml'))$fail[]='RML runtime still loaded';
echo wp_json_encode(array('status'=>$fail?'FAIL':'PASS','vendor_files'=>count($manifest['files']),'native_media_records'=>count($native),'rml_function_absent'=>!function_exists('wp_rml'),'failures'=>$fail),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";if($fail)WP_CLI::error('Vendor/native media verification failed');
