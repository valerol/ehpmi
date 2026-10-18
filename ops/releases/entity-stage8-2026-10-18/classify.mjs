import fs from 'node:fs';
const dir='/private/tmp/ehpmi-stage8-work';const r=JSON.parse(fs.readFileSync(dir+'/ehpmi-stage8-dev-before.json'));const posts=new Map(r.posts.map(p=>[+p.ID,p]));
const numeric=(value,id)=>new RegExp(`(^|[^0-9])${id}([^0-9]|$)`).test(value);
const evidence=r.attachments.map(a=>{const refs=[];const folder=a.file.substring(0,a.file.lastIndexOf('/')+1);const variants=[a.file,...Object.values(a.metadata?.sizes||{}).map(s=>folder+s.file),...(a.metadata?.original_image?[folder+a.metadata.original_image]:[])];const names=[a.url,...variants,decodeURI(a.url)];const stems=names.map(s=>s.replace(/\.[^.]+$/,''));
for(const p of r.posts){if(p.post_content&&(names.some(s=>p.post_content.includes(s))||new RegExp(`(?:wp-image-|\"id\"\\s*:\\s*)${a.ID}\\b`).test(p.post_content)))refs.push({kind:'content',owner:+p.ID,type:p.post_type,status:p.post_status});}
for(const m of r.meta){if(+m.post_id===a.ID)continue;const p=posts.get(+m.post_id);if(!p)continue;const imageKeys=['_thumbnail_id','additional_image','additonal_image','file'];const match=(imageKeys.includes(m.meta_key)&&numeric(m.meta_value,a.ID))||names.some(s=>m.meta_value.includes(s));if(match)refs.push({kind:'meta',key:m.meta_key,owner:+m.post_id,type:p.post_type,status:p.post_status});}
for(const o of r.media_options){if((['site_icon','theme_mods_ehpmi'].includes(o.option_name)&&numeric(o.option_value,a.ID))||names.some(s=>o.option_value.includes(s)))refs.push({kind:'option',key:o.option_name});}
const live=refs.filter(x=>x.status==='publish'||x.kind==='option');const historical=refs.filter(x=>x.status!=='publish'&&x.kind!=='option');
const classification=live.length?'meaningful':historical.length?'administrative':'orphaned-candidate';
return {ID:a.ID,title:a.title,mime:a.mime,file:a.file,exists:a.exists,alt:a.alt,parent:a.parent,classification,references:refs};});
fs.writeFileSync(dir+'/media-classification-initial.json',JSON.stringify(evidence,null,2)+'\n');
console.log(JSON.stringify({counts:Object.fromEntries([...new Set(evidence.map(a=>a.classification))].map(k=>[k,evidence.filter(a=>a.classification===k).length])),emptyImageAlt:evidence.filter(a=>a.mime.startsWith('image/')&&!a.alt).map(a=>({ID:a.ID,title:a.title,file:a.file,class:a.classification,refs:a.references.map(x=>x.kind+':'+x.type+':'+x.owner)}))},null,2));
fs.writeFileSync(dir+'/rml-export.json',JSON.stringify({database:r.database,folders:r.rml.wp_realmedialibrary,relations:r.rml.wp_realmedialibrary_posts,folder_meta:r.rml.wp_realmedialibrary_meta,temporary:r.rml.wp_realmedialibrary_tmp},null,2)+'\n');
