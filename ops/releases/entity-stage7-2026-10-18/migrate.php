<?php
/** Guarded, reversible Stage 7 transform. Default: dry-run. */
if (rtrim(ABSPATH,'/') !== '/home2/nykvymmy/dev.ehpmi.org' || DB_NAME !== 'nykvymmy_ehpmidev' || get_option('home') !== 'https://dev.ehpmi.org') { throw new RuntimeException('STOP-WRONG-ENV'); }
$baseline_path = $args[0] ?? '';
$apply = ($args[1] ?? '') === 'apply';
$baseline = json_decode(file_get_contents($baseline_path),true,512,JSON_THROW_ON_ERROR);
$pages = array_column($baseline['pages'],null,'ID');
$changes = array('pages'=>array(),'widgets'=>array());
$iframe_count = 0;
foreach (array(1480,2389) as $id) {
    $page = $pages[$id]; $index=0;
    $after = preg_replace_callback('/<iframe\b[^>]*>.*?<\/iframe>/is',static function($match) use (&$index,&$iframe_count,$page) {
        $index++; $iframe_count++;
        $processor = new WP_HTML_Tag_Processor($match[0]);
        if (!$processor->next_tag('iframe')) { throw new RuntimeException('Could not parse complete iframe'); }
        $processor->set_attribute('loading','lazy');
        $processor->set_attribute('referrerpolicy','strict-origin-when-cross-origin');
        $processor->set_attribute('title',$page['title'].' — video '.$index);
        return $processor->get_updated_html();
    },$page['content']);
    if ($after===$page['content']) { throw new RuntimeException('Iframe transform changed no bytes: '.$id); }
    $changes['pages'][$id] = array('before'=>$page['content'],'after'=>$after);
}
foreach (array('slider-text'=>2,'map-text'=>3) as $region=>$id) {
    $before = $baseline['widgets'][$region]['block-'.$id]['content'];
    $after = preg_replace_callback('/<(h[12]|p)>(.*?)<\/\1>/s',static function($m) {
        if ($m[1]==='p') { return '<!-- wp:paragraph -->'."\n".$m[0]."\n".'<!-- /wp:paragraph -->'; }
        $level=(int)substr($m[1],1);
        return '<!-- wp:heading {"level":'.$level.'} -->'."\n".'<'.$m[1].' class="wp-block-heading">'.$m[2].'</'.$m[1].'>'."\n".'<!-- /wp:heading -->';
    },$before);
    $changes['widgets'][$id]=array('before'=>$before,'after'=>$after);
}
$footer=$baseline['widgets']['footer-contacts']['block-6']['content'];
if (substr($footer,0,16)!=='<h3>Contact</h3>') { throw new RuntimeException('Unexpected footer heading'); }
$tail=substr($footer,16);
$after='<!-- wp:heading {"className":"h3"} -->'."\n".'<h2 class="wp-block-heading h3">Contact</h2>'."\n".'<!-- /wp:heading -->'."\n\n".'<!-- wp:html -->'."\n".str_replace('</br>','<br>',$tail)."\n".'<!-- /wp:html -->';
$changes['widgets'][6]=array('before'=>$footer,'after'=>$after);
// Widgets editor consumes only parsedBlocks[0]; each widget must have ONE root.
foreach ($changes['widgets'] as &$widget_change) {
    $widget_change['after']='<!-- wp:group {"className":"ehpmi-widget-content"} -->'."\n".'<div class="wp-block-group ehpmi-widget-content">'.$widget_change['after'].'</div>'."\n".'<!-- /wp:group -->';
}
unset($widget_change);
// Validate every precondition before the first database write.
$widgets=get_option('widget_block');
foreach ($changes['pages'] as $id=>$change) {
    if (get_post_field('post_content',$id)!==$change['before'] && str_replace('allowfullscreen=""','allowfullscreen',get_post_field('post_content',$id))!==str_replace('allowfullscreen=""','allowfullscreen',$change['after'])) { throw new RuntimeException('Page drift: '.$id); }
}
foreach ($changes['widgets'] as $id=>$change) {
    if (($widgets[$id]['content']??null)!==$change['before'] && ($widgets[$id]['content']??null)!==$change['after']) { throw new RuntimeException('Widget drift: '.$id); }
}
if (count($changes['pages'])!==2 || count($changes['widgets'])!==3 || $iframe_count!==5) { throw new RuntimeException('Unexpected counters'); }
$report=array('mode'=>$apply?'apply':'dry-run','expected'=>array('pages'=>2,'iframes'=>5,'block_widgets'=>3),'pages'=>array_keys($changes['pages']),'widgets'=>array_keys($changes['widgets']));
if ($apply) {
    $rollback='/home2/nykvymmy/backups/entity-stage7-2026-10-18/content-before-v3.json';
    if (!is_file($rollback)) { file_put_contents($rollback,wp_json_encode($changes,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); chmod($rollback,0600); }
    if (json_decode(file_get_contents($rollback),true)!==$changes) { throw new RuntimeException('Rollback evidence differs'); }
    $report['changed_this_run']=array('pages'=>0,'widget_option'=>0);
    foreach ($changes['pages'] as $id=>$change) {
        if (str_replace('allowfullscreen=""','allowfullscreen',get_post_field('post_content',$id))===str_replace('allowfullscreen=""','allowfullscreen',$change['after'])) { continue; }
        $result=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$change['after'])),true);
        if (is_wp_error($result) || get_post_field('post_content',$id)!==$change['after']) { throw new RuntimeException('Page apply failed: '.$id); }
        $report['changed_this_run']['pages']++;
    }
    foreach ($changes['widgets'] as $id=>$change) { $widgets[$id]['content']=$change['after']; }
    $report['changed_this_run']['widget_option']=update_option('widget_block',$widgets)?1:0;
    foreach ($changes['widgets'] as $id=>$change) { if (get_option('widget_block')[$id]['content']!==$change['after']) { throw new RuntimeException('Widget apply failed: '.$id); } }
    update_option('ehpmi_stage7_applied',array('pages'=>array_keys($changes['pages']),'widgets'=>array_keys($changes['widgets'])));
    $report['rollback_sha256']=hash_file('sha256',$rollback);
}
echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
