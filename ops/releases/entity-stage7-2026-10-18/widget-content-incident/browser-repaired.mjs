import fs from 'node:fs';
const phase=process.argv[2] || 'before';
const folder='/private/tmp/ehpmi-stage7-work';
fs.mkdirSync(folder,{recursive:true});
const pages=phase.startsWith('newsletter-')||phase==='interactions'||phase==='widgets-repaired'?[]:JSON.parse(fs.readFileSync('/private/tmp/ehpmi-stage7-dev-before.json')).pages;
const target=await (await fetch('http://127.0.0.1:9223/json/new?about:blank',{method:'PUT'})).json();
const socket=new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve,reject)=>{socket.addEventListener('open',resolve,{once:true});socket.addEventListener('error',reject,{once:true});});
let id=0;const pending=new Map();const requests=[];
socket.addEventListener('message',event=>{const m=JSON.parse(event.data);if(m.id&&pending.has(m.id)){const p=pending.get(m.id);pending.delete(m.id);m.error?p.reject(Error(m.error.message)):p.resolve(m.result);}if(m.method==='Network.requestWillBeSent')requests.push({method:m.params.request.method,url:m.params.request.url});});
const send=(method,params={})=>new Promise((resolve,reject)=>{const key=++id;pending.set(key,{resolve,reject});socket.send(JSON.stringify({id:key,method,params}));});
const ev=async expression=>{const r=await send('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});if(r.exceptionDetails)throw Error(JSON.stringify(r.exceptionDetails));return r.result.value;};
const pause=ms=>new Promise(r=>setTimeout(r,ms));
await send('Page.enable');await send('Runtime.enable');await send('Network.enable');
async function navigate(url,width){await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width===390});await send('Page.navigate',{url:url+`?stage7=${phase}-${Date.now()}`});for(let i=0;i<60;i++){await pause(250);if(await ev('document.readyState!=="loading"'))break;}await pause(600);}
await navigate('https://dev.ehpmi.org/',1440);
const semantic=await ev(`(async()=>{const urls=${JSON.stringify(pages.map(p=>p.url))};const results=[];for(const url of urls){const r=await fetch(url+'?stage7-semantic=${phase}');const html=await r.text();const d=new DOMParser().parseFromString(html,'text/html');const main=d.querySelector('main');const hs=[...d.querySelectorAll('main h1,main h2,main h3,main h4,main h5,main h6')].map(h=>({level:+h.tagName[1],text:h.textContent.trim()}));const jumps=hs.filter((h,i)=>i&&h.level>hs[i-1].level+1);results.push({url,status:r.status,h1:d.querySelectorAll('h1').length,main:d.querySelectorAll('main').length,headings:hs,emptyHeadings:hs.filter(h=>!h.text).length,jumps,anchors:[...d.querySelectorAll('a[name]')].map(a=>a.name),inlineStyles:main?.querySelectorAll('[style]').length||0,tables:[...d.querySelectorAll('main table')].map(t=>({caption:!!t.caption,thead:!!t.tHead,headers:t.querySelectorAll('th[scope]').length})),iframes:[...d.querySelectorAll('main iframe')].map(f=>({src:f.src,title:f.title,loading:f.loading,referrerpolicy:f.referrerPolicy})),customHtml:(html.match(/wp:html/g)||[]).length});}return results;})()`);
fs.writeFileSync(`${folder}/semantic-${phase}.json`,JSON.stringify(semantic,null,2)+'\n');
console.log(JSON.stringify({phase,pages:semantic.length,semanticDefects:semantic.filter(p=>p.h1!==1||p.emptyHeadings||p.jumps.length||p.anchors.length).map(p=>({url:p.url,h1:p.h1,jumps:p.jumps,anchors:p.anchors}))}));
if(phase==='semantic-repaired'){const failures=semantic.filter(p=>p.status!==200||p.h1!==1||p.main!==1||p.emptyHeadings||p.jumps.length||p.anchors.length);console.log(JSON.stringify({status:failures.length?'FAIL':'PASS',pages:semantic.length,failures}));socket.close();process.exit(failures.length?1:0);}
if(phase==='widgets-repaired'){
const baseline=JSON.parse(fs.readFileSync('/private/tmp/ehpmi-stage7-dev-before.json'));
const expected={hero:baseline.widgets['slider-text']['block-2'].content,map:baseline.widgets['map-text']['block-3'].content,footer:baseline.widgets['footer-contacts']['block-6'].content};
const views=[];
for(const width of [1440,390]){
await navigate('https://dev.ehpmi.org/',width);
for(const [name,selector] of [['hero','.hero'],['map','.map'],['footer','.footer']]){
await ev(`document.querySelector(${JSON.stringify(selector)}).scrollIntoView({block:'center',behavior:'instant'});window.dispatchEvent(new Event('scroll'))`);await pause(1200);
const data=await ev(`(()=>{const n=document.querySelector(${JSON.stringify(selector)});const content=n.querySelector('.ehpmi-widget-content');const raw=new DOMParser().parseFromString(${JSON.stringify(expected[name])},'text/html');const expected=raw.querySelector('p')?.textContent.replace(/\\s+/g,' ').trim();const actual=content?.querySelector('p')?.textContent.replace(/\\s+/g,' ').trim();const p=content?.querySelector('p');const r=p?.getBoundingClientRect();return {region:${JSON.stringify(name)},width:innerWidth,expectedText:expected,actualText:actual,bodyTextMatches:expected===actual,visible:!!p&&getComputedStyle(p).display!=='none'&&getComputedStyle(p).visibility!=='hidden'&&r.width>0&&r.height>0,groupDisplay:content?getComputedStyle(content).display:null,allRegionText:n.innerText,overflow:document.documentElement.scrollWidth>innerWidth,testMarker:!!n.querySelector('.stage7-round-trip-test')};})()`);
views.push(data);const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(`${folder}/${name}-${width}-widgets-repaired.png`,Buffer.from(shot.data,'base64'));
}
}
const failures=views.filter(v=>!v.bodyTextMatches||!v.visible||v.groupDisplay!=='contents'||v.overflow||v.testMarker).map(v=>`${v.region}-${v.width}`);const postRequests=requests.filter(r=>r.method==='POST');if(postRequests.length)failures.push('unexpected browser POST');const report={status:failures.length?'FAIL':'PASS',failures,views,postRequests};fs.writeFileSync(`${folder}/widgets-repaired-browser.json`,JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify(report,null,2));socket.close();process.exit(failures.length?1:0);
}
if(phase==='interactions'){
 await send('Page.bringToFront');
 for(let i=0;i<40;i++){if(await ev("Boolean(window.wpcf7?.validate&&document.querySelector('.wpcf7-form')?.wpcf7?.schema)"))break;await pause(250);}
 await ev("document.querySelector('.map summary').focus()");
 const mapFocus=await ev("({tag:document.activeElement.tagName,name:document.activeElement.textContent.trim(),open:document.querySelector('.map details').open})");
 await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Enter',code:'Enter',text:'\r',windowsVirtualKeyCode:13});
 await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13});
 await pause(150);
 const map=await ev("({openedByKeyboard:document.querySelector('.map details').open,names:[...document.querySelectorAll('.map summary')].map(s=>s.textContent.trim()),link:document.querySelector('.map details a').href})");map.focusBefore=mapFocus;
 await ev("wpcf7.validate(document.querySelector('.wpcf7-form'))");await pause(500);
 const cf7=await ev("(()=>{const f=document.querySelector('.wpcf7-form');return {status:f.dataset.status,statusRegion:!!f.parentElement.querySelector('[role=status][aria-live=polite]'),errors:[...f.querySelectorAll('[aria-invalid=true]')].map(i=>({name:i.name,labels:[...i.labels].map(l=>l.textContent.trim()),describedBy:i.getAttribute('aria-describedby'),error:document.getElementById(i.getAttribute('aria-describedby'))?.textContent||null})),tips:f.querySelectorAll('.wpcf7-not-valid-tip').length};})()");
 await ev("document.querySelector('.wpcf7-form [name=your-name]').focus()");const keyboard=[];
 for(let i=0;i<5;i++){keyboard.push(await ev("({name:document.activeElement.name||'',type:document.activeElement.type||''})"));await send('Input.dispatchKeyEvent',{type:'keyDown',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});await send('Input.dispatchKeyEvent',{type:'keyUp',key:'Tab',code:'Tab',windowsVirtualKeyCode:9});}
 const newsletter=[];
 for(const width of [1440,390]){await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width===390});await pause(300);newsletter.push(await ev("(()=>{const n=document.querySelector('.newsletter'),f=n.querySelector('form'),l=n.querySelector('label'),nr=n.getBoundingClientRect(),fr=f.getBoundingClientRect();return {width:innerWidth,contained:fr.top>=nr.top&&fr.bottom<=nr.bottom&&fr.left>=nr.left&&fr.right<=nr.right,labelVisible:getComputedStyle(l).display!=='none'&&l.getBoundingClientRect().height>0,overflow:document.documentElement.scrollWidth>innerWidth,heading:n.querySelector('h2')?.textContent};})()"));}
 const postRequests=requests.filter(r=>r.method==='POST');
 const failures=[];if(!map.openedByKeyboard||map.names.length!==9||map.names.some(n=>!n))failures.push('map keyboard/name contract');if(cf7.tips!==3||cf7.errors.length!==3||cf7.errors.some(e=>!e.error||!e.labels.length)||!cf7.statusRegion)failures.push('CF7 local validation/announcements');if(keyboard.map(k=>k.name).slice(0,4).join(',')!=='your-name,your-email,your-subject,your-message'||keyboard[4].type!=='submit')failures.push('CF7 keyboard order');if(newsletter.some(n=>!n.contained||!n.labelVisible||n.overflow))failures.push('Newsletter geometry/label');if(postRequests.length)failures.push('Unexpected POST');
 const report={status:failures.length?'FAIL':'PASS',failures,map,cf7,keyboard,newsletter,postRequests};fs.writeFileSync(`${folder}/interactions.json`,JSON.stringify(report,null,2)+'\n');console.log(JSON.stringify(report,null,2));socket.close();process.exit(failures.length?1:0);
}
const visual=[];
for(const width of [1440,390]){
for(const page of [{url:'https://dev.ehpmi.org/',slug:'home'},...pages]){
await navigate(page.url,width);
const data=await ev(`(()=>{const n=document.querySelector('.newsletter');const bounds=e=>{const r=e.getBoundingClientRect();const s=getComputedStyle(e);return {text:e.textContent.trim().slice(0,120),x:r.x,y:r.y,w:r.width,h:r.height,display:s.display,visibility:s.visibility,float:s.float,maxWidth:s.maxWidth}};return {url:location.pathname,width:innerWidth,documentWidth:document.documentElement.scrollWidth,mainH1:document.querySelector('main')?.querySelectorAll('h1').length,newsletter:n?[...n.querySelectorAll('h2,h3,.text,.tnp-widget,input,label')].map(bounds):null,mapSummaries:[...document.querySelectorAll('.map summary')].map(s=>({text:s.textContent.trim(),ariaLabel:s.getAttribute('aria-label')})),forms:[...document.querySelectorAll('main form')].map(f=>({action:f.action,labels:[...f.querySelectorAll('input:not([type=hidden]):not([type=submit]),textarea')].filter(e=>getComputedStyle(e).display!=='none'&&e.getBoundingClientRect().height>0).map(e=>({name:e.name,labels:[...e.labels||[]].map(l=>l.textContent.trim()),tabIndex:e.tabIndex,required:e.getAttribute('aria-required')||e.required})),status:!!f.parentElement.querySelector('[role=status][aria-live]')}))};})()`);
visual.push(data);
if(['home','offices','staff','partners','about','video','vietnam'].includes(page.slug)){
 if(page.slug==='home') {await ev("document.querySelector('.newsletter').scrollIntoView({block:'center',behavior:'instant'}); window.dispatchEvent(new Event('scroll'))");await pause(1500);}
 const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});fs.writeFileSync(`${folder}/${page.slug}-${width}-${phase}.png`,Buffer.from(shot.data,'base64'));
}
console.log(JSON.stringify({phase,width,path:data.url,overflow:data.documentWidth>width}));
}
}
fs.writeFileSync(`${folder}/browser-${phase}.json`,JSON.stringify({visual,postRequests:requests.filter(r=>r.method==='POST')},null,2)+'\n');
socket.close();
