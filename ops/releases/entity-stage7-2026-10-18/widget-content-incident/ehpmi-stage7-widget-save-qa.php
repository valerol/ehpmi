<?php
/** Actual WordPress widget REST save/reload; no account/session is created. */
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'||get_option('home')!=='https://dev.ehpmi.org'){throw new RuntimeException('STOP-WRONG-ENV');}
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
if(!$admins){throw new RuntimeException('No existing administrator for capability-checked REST simulation');}
wp_set_current_user((int)$admins[0]);
if(!current_user_can('edit_theme_options')||!current_user_can('unfiltered_html')){throw new RuntimeException('Insufficient local REST test capabilities');}
$root='/home2/nykvymmy/backups/entity-stage7-2026-10-18';
$delta=json_decode(file_get_contents($root.'/content-before-v3.json'),true,512,JSON_THROW_ON_ERROR);
$sidebars=get_option('sidebars_widgets');$widgets=get_option('widget_block');$report=array('mode'=>'capability-checked-internal-REST-save-reload','widgets'=>array());
foreach(array(2=>'slider-text',3=>'map-text',6=>'footer-contacts') as $id=>$region){
    $content=$widgets[$id]['content'];
    if($content!==$delta['widgets'][$id]['after']){throw new RuntimeException('Unexpected widget before save: '.$id);}
    // Installed edit-widgets.js chooses parsedBlocks[0]. Prove that this root contains every child.
    $parsed=array_values(array_filter(parse_blocks($content),static fn($b)=>$b['blockName']!==null));
    if(count($parsed)!==1||$parsed[0]['blockName']!=='core/group'){throw new RuntimeException('Unsafe editor root: '.$id);}
    $editor_payload=serialize_block($parsed[0]);
    if($editor_payload!==$content){throw new RuntimeException('Editor first-root projection loses content: '.$id);}
    $save=new WP_REST_Request('POST','/wp/v2/widgets/block-'.$id);
    $save->set_param('sidebar',$region);$save->set_param('instance',array('raw'=>array('content'=>$editor_payload)));
    $saved=rest_do_request($save);
    if($saved->is_error()||$saved->get_status()!==200){throw new RuntimeException('REST save failed: '.$id.' '.wp_json_encode($saved->get_data()));}
    $reload=new WP_REST_Request('GET','/wp/v2/widgets/block-'.$id);$reload->set_param('context','edit');
    $response=rest_do_request($reload);$data=$response->get_data();
    $loaded=$data['instance']['raw']['content']??null;
    if($response->is_error()||$loaded!==$content||get_option('widget_block')[$id]['content']!==$content){throw new RuntimeException('REST reload loses widget content: '.$id);}
    $tails=array(2=>'Our team unites efforts of',3=>'EHPMI has active teams and operates',6=>'Anna Politkovskaya');
    if(strpos($loaded,$tails[$id])===false||strpos($loaded,'stage7-round-trip-test')!==false){throw new RuntimeException('Missing tail or retained test marker: '.$id);}
    ob_start();$GLOBALS['wp_widget_factory']->widgets['WP_Widget_Block']->form(array('content'=>$loaded));$admin_html=ob_get_clean();
    $report['widgets'][$id]=array('save_status'=>$saved->get_status(),'reload_status'=>$response->get_status(),'sha256'=>hash('sha256',$loaded),'root_count'=>count($parsed),'inner_blocks'=>array_column($parsed[0]['innerBlocks'],'blockName'),'tail_present'=>true,'admin_form_contains_tail'=>strpos(html_entity_decode($admin_html,ENT_QUOTES|ENT_HTML5),$tails[$id])!==false,'test_marker_removed'=>true);
}
if(get_option('sidebars_widgets')!==$sidebars){throw new RuntimeException('Sidebar mapping changed');}
if(get_option('widget_block')!==$widgets){throw new RuntimeException('Widget option changed across idempotent REST save');}
$report['sidebar_mapping_unchanged']=true;$report['widget_option_unchanged_after_save']=true;$report['result']='PASS';
file_put_contents($root.'/widget-content-incident/editor-rest-roundtrip.json',wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
