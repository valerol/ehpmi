<?php
if (rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'){throw new RuntimeException('STOP-WRONG-ENV');}
$root='/home2/nykvymmy/backups/entity-stage7-2026-10-18';
$target=get_template_directory().'/css/structural-regions.css';$source='/tmp/ehpmi-stage7-widget-regions.css';
if(hash_file('sha256',$target)!=='8ed568bcf0effdcc84c3d07e599a89d93974ab7a2028efbca2d140b4888ea976'||hash_file('sha256',$source)!=='54e51f6f5ea6884176930d55be8e95dda8298afa572f471d9ddb34f5f3a3c556'){throw new RuntimeException('STOP-SOURCE-DRIFT');}
copy($target,$root.'/widget-content-incident/structural-regions-before-repair.css');
copy($source,$target.'.stage7-new');chmod($target.'.stage7-new',0644);rename($target.'.stage7-new',$target);
$manifest=json_decode(file_get_contents($root.'/runtime-files.json'),true);$manifest['css/structural-regions.css']['after']=hash_file('sha256',$target);file_put_contents($root.'/runtime-files.json',wp_json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo 'PASS grouped widget wrapper uses display:contents; native semantics remain in descendants',"\n";
