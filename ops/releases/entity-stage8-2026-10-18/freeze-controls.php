<?php
if(rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org'||DB_NAME!=='nykvymmy_ehpmidev'){throw new RuntimeException('STOP-WRONG-ENV');}
$source=json_decode(file_get_contents('/tmp/ehpmi-stage8-dev-before.json'),true,512,JSON_THROW_ON_ERROR);
$r=array_intersect_key($source,array_flip(array('root','database','home','siteurl','php','wp','records_hash','content_hash','widgets_hash','fixture','markers','newsletter','files','core','plugins')));
foreach($source['posts'] as $p){$p['body_sha256']=hash('sha256',$p['post_content']);unset($p['post_content']);$r['posts'][]=$p;}
foreach($source['attachments'] as $a){$a['metadata_sha256']=hash('sha256',wp_json_encode($a['metadata']));unset($a['metadata']);$r['attachments'][]=$a;}
$path='/home2/nykvymmy/backups/entity-stage8-2026-10-18/invariants-before.json';if(file_exists($path)){throw new RuntimeException('Frozen controls already exist');}
file_put_contents($path,wp_json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));echo "PASS: frozen sanitized pre-stage controls\n";
