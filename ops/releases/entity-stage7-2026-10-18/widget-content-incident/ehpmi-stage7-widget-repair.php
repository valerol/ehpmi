<?php
/** Compensate the Stage 7 Widgets editor truncation and prove a safe root. */
if (rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org' || DB_NAME!=='nykvymmy_ehpmidev' || get_option('home')!=='https://dev.ehpmi.org') { throw new RuntimeException('STOP-WRONG-ENV'); }
global $wpdb;
$root='/home2/nykvymmy/backups/entity-stage7-2026-10-18';
$incident=$root.'/widget-content-incident';
$delta_path=$root.'/content-before-v2.json';
if(hash_file('sha256',$delta_path)!=='323a0e489199096feb039b9f154e7f9b15fff79c2575ccd32719d83a4ab009b0'){throw new RuntimeException('STOP-CHECKSUM original delta');}
if(hash_file('sha256',$incident.'/20261018T043956Z_nykvymmy_ehpmidev_pre-widget-content-repair.sql.gz')!=='8d8c399f3b7c5e262528fe56f35f486cc04bfc530de850bf3f1223a30fc465ad'){throw new RuntimeException('STOP-CHECKSUM incident backup');}
$delta=json_decode(file_get_contents($delta_path),true,512,JSON_THROW_ON_ERROR);
$snapshot=json_decode(file_get_contents('/tmp/ehpmi-stage7-widgets-incident-current.json'),true,512,JSON_THROW_ON_ERROR);
$widgets=get_option('widget_block');
$region_ids=array(2=>'slider-text',3=>'map-text',6=>'footer-contacts');
$raw=$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='widget_block'");
if(!is_serialized($raw)||maybe_unserialize($raw)!==$widgets){throw new RuntimeException('Invalid widget option serialization');}
foreach($region_ids as $id=>$region){
    if($widgets[$id]!==$snapshot['widgets'][$region]['block-'.$id]){throw new RuntimeException('Current widget drift: '.$id);}
    if(!in_array('block-'.$id,get_option('sidebars_widgets')[$region],true)){throw new RuntimeException('Wrong sidebar owner: '.$id);}
}
$before=array('widget_block'=>$widgets,'sidebars_widgets'=>get_option('sidebars_widgets'),'raw_option_sha256'=>hash('sha256',$raw));
$rollback=$incident.'/before-repair-widgets.json';
if(file_exists($rollback)){throw new RuntimeException('Repair already has rollback evidence; inspect before repeating');}
file_put_contents($rollback,wp_json_encode($before,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));chmod($rollback,0600);
$report=array('before_option_sha256'=>hash('sha256',$raw),'widgets'=>array());
// Restore exact verified pre-stage values before constructing the safe replacement.
foreach($region_ids as $id=>$region){$widgets[$id]['content']=$delta['widgets'][$id]['before'];}
update_option('widget_block',$widgets);
foreach($region_ids as $id=>$region){if(get_option('widget_block')[$id]['content']!==$delta['widgets'][$id]['before']){throw new RuntimeException('Exact baseline restore failed: '.$id);}}
$report['exact_pre_stage_restore']='PASS';
file_put_contents($incident.'/baseline-restored-receipt.json',wp_json_encode($report,JSON_PRETTY_PRINT));
foreach($region_ids as $id=>$region){
    $inner=$delta['widgets'][$id]['after'];
    $group='<!-- wp:group {"className":"ehpmi-widget-content"} -->'."\n".'<div class="wp-block-group ehpmi-widget-content">'.$inner.'</div>'."\n".'<!-- /wp:group -->';
    $blocks=array_values(array_filter(parse_blocks($group),static fn($block)=>$block['blockName']!==null));
    if(count($blocks)!==1||$blocks[0]['blockName']!=='core/group'||count(array_filter($blocks[0]['innerBlocks'],static fn($block)=>$block['blockName']!==null))!==2){throw new RuntimeException('Invalid single-root structure: '.$id);}
    if(serialize_block($blocks[0])!==$group){throw new RuntimeException('Group serialization mismatch: '.$id);}
    $widgets[$id]['content']=$group;
    $report['widgets'][$id]=array('incident_sha256'=>hash('sha256',$snapshot['widgets'][$region]['block-'.$id]['content']),'pre_stage_sha256'=>hash('sha256',$delta['widgets'][$id]['before']),'grouped_sha256'=>hash('sha256',$group),'root'=>'core/group','children'=>array_column(array_filter($blocks[0]['innerBlocks'],static fn($block)=>$block['blockName']!==null),'blockName'));
}
update_option('widget_block',$widgets);
foreach($region_ids as $id=>$region){if(get_option('widget_block')[$id]['content']!==$widgets[$id]['content']){throw new RuntimeException('Grouped persistence failed: '.$id);}}
$delta_grouped=$delta;
foreach($region_ids as $id=>$region){$delta_grouped['widgets'][$id]['after']=$widgets[$id]['content'];}
file_put_contents($root.'/content-before-v3.json',wp_json_encode($delta_grouped,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));chmod($root.'/content-before-v3.json',0600);
$report['grouped_delta_sha256']=hash_file('sha256',$root.'/content-before-v3.json');
$report['sidebars_unchanged']=get_option('sidebars_widgets')===$before['sidebars_widgets'];
$raw_after=$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='widget_block'");
$report['serialized_db_option_valid']=is_serialized($raw_after)&&maybe_unserialize($raw_after)===$widgets;
$report['after_option_sha256']=hash('sha256',$raw_after);
$report['result']='PASS';
file_put_contents($incident.'/repair-output.json',wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
