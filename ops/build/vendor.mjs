import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import crypto from 'node:crypto';
const root=fileURLToPath(new URL('../../',import.meta.url));
const manifest=JSON.parse(await fs.readFile(new URL('./vendor-assets.json',import.meta.url)));
const vendor=path.join(root,'wp-content/themes/ehpmi/assets/vendor');
const check=process.argv.includes('--check');
for(const file of manifest.files){
  const target=path.join(vendor,file.path);
  if(!check){
    let data;
    if(file.source.startsWith('npm:')){
      const m=file.source.match(/^npm:(@[^/]+\/[^@]+|[^@]+)@([^/]+)\/(.+)$/);
      data=await fs.readFile(path.join(root,'node_modules',m[1],m[3]));
    }else if(file.source.startsWith('https:')){
      const response=await fetch(file.source);if(!response.ok)throw Error('Download failed: '+file.source);data=Buffer.from(await response.arrayBuffer());
    }else{
      // The checked-in Google CSS response freezes unicode ranges and local URLs.
      data=await fs.readFile(target);
    }
    if(crypto.createHash('sha256').update(data).digest('hex')!==file.sha256)throw Error('Source hash mismatch: '+file.path);
    await fs.mkdir(path.dirname(target),{recursive:true});await fs.writeFile(target,data);
  }
  const data=await fs.readFile(target);
  if(data.length!==file.bytes||crypto.createHash('sha256').update(data).digest('hex')!==file.sha256)throw Error('Asset mismatch: '+file.path);
}
console.log(JSON.stringify({status:'PASS',assets:manifest.files.length,mode:check?'check':'build'}));
