<?php
/** Guarded dev-only deploy; originals are retained before any replacement. */
if (rtrim(ABSPATH,'/')!=='/home2/nykvymmy/dev.ehpmi.org' || DB_NAME!=='nykvymmy_ehpmidev' || get_option('home')!=='https://dev.ehpmi.org') { throw new RuntimeException('STOP-WRONG-ENV'); }
$backup='/home2/nykvymmy/backups/entity-stage7-2026-10-18';
if (hash_file('sha256',$backup.'/20261018T032908Z_nykvymmy_ehpmidev_pre-stage7.sql.gz')!=='fbfd37912c33e23047b0e9973a0ad4713d27cc90665c8c892d4f75af0a2f40de') { throw new RuntimeException('STOP-CHECKSUM database backup'); }
$staged=$backup.'/staged';
$baseline=json_decode(file_get_contents('/tmp/ehpmi-stage7-dev-before.json'),true,512,JSON_THROW_ON_ERROR);
$relative=array('inc/page-resolver.php','css/structural-regions.css','functions.php','template-parts/map.php','template-parts/contact-forms.php','singular.php');
$theme=get_template_directory();
$manifest=array();
foreach ($relative as $file) {
    $source=$staged.'/wp-content/themes/ehpmi/'.$file;
    if (!is_file($source)) { throw new RuntimeException('Missing staged file: '.$file); }
    if (isset($baseline['files'][$file])) {
        if (hash_file('sha256',$theme.'/'.$file)!==$baseline['files'][$file]) { throw new RuntimeException('STOP-SOURCE-DRIFT '.$file); }
    } elseif (file_exists($theme.'/'.$file)) { throw new RuntimeException('Unexpected existing target: '.$file); }
    $manifest[$file]=array('before'=>$baseline['files'][$file]??null,'after'=>hash_file('sha256',$source));
}
foreach ($relative as $file) {
    if (!isset($baseline['files'][$file])) { continue; }
    $destination=$backup.'/files/'.$file;
    if (!is_dir(dirname($destination))) { mkdir(dirname($destination),0700,true); }
    if (file_exists($destination)) { throw new RuntimeException('Rollback target already exists: '.$file); }
    if (!copy($theme.'/'.$file,$destination) || hash_file('sha256',$destination)!==$baseline['files'][$file]) { throw new RuntimeException('Rollback copy failed: '.$file); }
}
file_put_contents($backup.'/runtime-files.json',wp_json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
foreach ($relative as $file) {
    $target=$theme.'/'.$file;
    if (!is_dir(dirname($target))) { mkdir(dirname($target),0755,true); }
    if (!copy($staged.'/wp-content/themes/ehpmi/'.$file,$target.'.stage7-new')) { throw new RuntimeException('Staging copy failed: '.$file); }
    chmod($target.'.stage7-new',0644);
    if (hash_file('sha256',$target.'.stage7-new')!==$manifest[$file]['after'] || !rename($target.'.stage7-new',$target)) { throw new RuntimeException('Replacement failed: '.$file); }
}
echo wp_json_encode(array('status'=>'PASS','files'=>$manifest),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
