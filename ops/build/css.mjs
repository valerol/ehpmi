import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import crypto from 'node:crypto';
import less from 'less';
const root=fileURLToPath(new URL('../../',import.meta.url));
const source=path.join(root,'wp-content/themes/ehpmi/css/style.less');
const target=source.replace(/\.less$/,'.css');
const result=await less.render(await fs.readFile(source,'utf8'),{filename:source});
if(process.argv.includes('--check')){
  if(result.css!==await fs.readFile(target,'utf8'))throw Error('Compiled CSS differs. Run npm run build:css and review the diff.');
}else await fs.writeFile(target,result.css);
console.log(JSON.stringify({status:'PASS',compiler:'less@4.2.2',mode:process.argv.includes('--check')?'check':'build',sha256:crypto.createHash('sha256').update(result.css).digest('hex')}));
