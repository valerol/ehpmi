<?php
/** Default read-only validation; pass apply only for an approved rollback. */
if (rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org' || DB_NAME!=='nykvymmy_ehpmidev' || get_option('home')!=='https://dev.ehpmi.org') { throw new RuntimeException('STOP-WRONG-ENV'); }
$backup='/home2/nykvymmy/backups/entity-stage7-2026-10-18';
$manifest=json_decode(file_get_contents($backup.'/runtime-files.json'),true,512,JSON_THROW_ON_ERROR);
$changes=json_decode(file_get_contents($backup.'/content-before-v3.json'),true,512,JSON_THROW_ON_ERROR);
$theme=get_template_directory();
foreach ($manifest as $file=>$hashes) {
    if (hash_file('sha256',$theme.'/'.$file)!==$hashes['after']) { throw new RuntimeException('Live file drift: '.$file); }
    if ($hashes['before']!==null && hash_file('sha256',$backup.'/files/'.$file)!==$hashes['before']) { throw new RuntimeException('Rollback copy mismatch: '.$file); }
}
foreach ($changes['pages'] as $id=>$change) { if (str_replace('allowfullscreen=""','allowfullscreen',get_post_field('post_content',$id))!==str_replace('allowfullscreen=""','allowfullscreen',$change['after'])) { throw new RuntimeException('Page drift: '.$id); } }
$widgets=get_option('widget_block');
foreach ($changes['widgets'] as $id=>$change) { if ($widgets[$id]['content']!==$change['after']) { throw new RuntimeException('Widget drift: '.$id); } }
$apply=($args[0]??'')==='apply';
if ($apply) {
    foreach ($changes['pages'] as $id=>$change) {
        $saved=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$change['before'])),true);
        if (is_wp_error($saved) || get_post_field('post_content',$id)!==$change['before']) { throw new RuntimeException('Page restoration failed: '.$id); }
    }
    foreach ($changes['widgets'] as $id=>$change) { $widgets[$id]['content']=$change['before']; }
    update_option('widget_block',$widgets);
    delete_option('ehpmi_stage7_applied');
    foreach (array_reverse($manifest,true) as $file=>$hashes) {
        $target=$theme.'/'.$file;
        if ($hashes['before']===null) {
            if (!unlink($target)) { throw new RuntimeException('Cannot remove Stage 7 owned file: '.$file); }
        } else {
            if (!copy($backup.'/files/'.$file,$target.'.stage7-rollback') || hash_file('sha256',$target.'.stage7-rollback')!==$hashes['before'] || !rename($target.'.stage7-rollback',$target)) { throw new RuntimeException('File restoration failed: '.$file); }
        }
    }
}
echo wp_json_encode(array('status'=>'PASS','mode'=>$apply?'apply':'validate-only','files'=>count($manifest),'pages'=>array_keys($changes['pages']),'widgets'=>array_keys($changes['widgets'])),JSON_PRETTY_PRINT),"\n";
