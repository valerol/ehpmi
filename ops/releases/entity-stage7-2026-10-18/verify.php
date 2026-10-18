<?php
/** Read-only Stage 7 data/template verifier; public baseline contains no secrets. */
if (rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org' || DB_NAME!=='nykvymmy_ehpmidev' || get_option('home')!=='https://dev.ehpmi.org') { throw new RuntimeException('STOP-WRONG-ENV'); }
$baseline=json_decode(file_get_contents($args[0]??''),true,512,JSON_THROW_ON_ERROR);
$delta=json_decode(file_get_contents('/home2/nykvymmy/backups/entity-stage7-2026-10-18/content-before-v3.json'),true,512,JSON_THROW_ON_ERROR);
$failures=array();$check=static function($ok,$message)use(&$failures){if(!$ok){$failures[]=$message;}};
$roles=array(13=>'content',17=>'map',27=>'staff',29=>'member',1453=>'partner',2378=>'projects',2381=>'projects',2382=>'projects',2383=>'projects',2379=>'blog',2384=>'news',2380=>'library',2385=>'library',2386=>'library',2387=>'library',2388=>'library',2389=>'library');
$report=array('pages'=>array(),'blocks'=>array());
foreach ($baseline['pages'] as $original) {
    $page=get_post($original['ID']);$id=$page->ID;
    $check($page->post_status==='publish' && $page->post_title===$original['title'] && $page->post_name===$original['slug'] && $page->post_parent===$original['parent'] && $page->menu_order===$original['order'],'Page identity '.$id);
    $check(get_permalink($page)===$original['url'],'Page URL '.$id);
    $expected=$delta['pages'][$id]['after']??$original['content'];
    $boolean_normalize=static fn($html)=>str_replace('allowfullscreen=""','allowfullscreen',$html);
    $check($page->post_content===$expected||$boolean_normalize($page->post_content)===$boolean_normalize($expected),'Page body '.$id);
    if($page->post_content!==$expected&&$boolean_normalize($page->post_content)===$boolean_normalize($expected)){$report['admin_boolean_attribute_normalization'][]=$id;}
    foreach ($original['meta'] as $key=>$values) {$check(get_post_meta($id)[$key]===$values,'Page relation '.$id.' '.$key);}
    $view=ehpmi_resolve_page($page);$role=$roles[$id]??($original['parent']===17?'country':'content');
    $check($view['role']===$role,'Page owner '.$id);
    $report['pages'][$id]=array('role'=>$view['role'],'url'=>get_permalink($page));
}
$check(count($baseline['pages'])===34 && (int)wp_count_posts('page')->publish===34,'Published Page count');
$widgets=get_option('widget_block');
foreach ($delta['widgets'] as $id=>$change) {
    $content=$widgets[$id]['content'];$check($content===$change['after'],'Widget body '.$id);
    $check(serialize_blocks(parse_blocks($content))===$content,'Block serialization round-trip '.$id);
    $roots=array_values(array_filter(parse_blocks($content),static fn($block)=>$block['blockName']!==null));
    $check(count($roots)===1&&$roots[0]['blockName']==='core/group','Editor-safe single root '.$id);
    $check(count(array_filter($roots[0]['innerBlocks'],static fn($block)=>$block['blockName']!==null))===2,'Group retains both children '.$id);
    $report['blocks'][$id]=array_column(parse_blocks($content),'blockName');
}
foreach ($baseline['widgets'] as $region=>$region_widgets) {
    foreach ($region_widgets as $id=>$value) {
        if (preg_match('/^(.+)-(\d+)$/',$id,$match)) {
            $current=get_option('widget_'.$match[1])[(int)$match[2]];
            if($match[1]==='block'&&isset($delta['widgets'][(int)$match[2]])){$value['content']=$delta['widgets'][(int)$match[2]]['after'];}
            $check($current===$value,'Widget identity/settings '.$region.' '.$id);
        }
    }
}
$fixture=get_post(2458);$check($fixture->post_status==='trash'&&$fixture->menu_order===15,'Fixture 2458 preserved');
$check(count(get_option('ehpmi_legacy_redirects',array()))===141,'141 legacy redirects retained');
$check(hash('sha256',wp_json_encode(get_option('ehpmi_legacy_redirects')))===$baseline['redirects_hash'],'Redirect map unchanged');
$check(hash_file('sha256',WP_PLUGIN_DIR.'/ehpmi-core/ehpmi-core.php')===$baseline['core_file'],'Site plugin unchanged');
$report['result']=$failures?'FAIL':'PASS';$report['failures']=$failures;
echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
if($failures){WP_CLI::error('Stage 7 verifier failed');}
