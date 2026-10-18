// Corrected source for future runs; existing raw evidence was not regenerated.
import fs from 'node:fs';
const phase = process.argv[2] || 'after';
const dir = process.argv[3] || '/private/tmp/ehpmi-stage8-work';
const endpoint = process.argv[4] || 'http://127.0.0.1:9223';
const target = await (await fetch(`${endpoint}/json/new?about:blank`, {method:'PUT'})).json();
const socket = new WebSocket(target.webSocketDebuggerUrl);
await new Promise((resolve,reject) => { socket.addEventListener('open',resolve,{once:true}); socket.addEventListener('error',reject,{once:true}); });
let id = 0;
const pending = new Map();
socket.addEventListener('message',event => {
  const message = JSON.parse(event.data), request = pending.get(message.id);
  if (!request) return;
  clearTimeout(request.timer); pending.delete(message.id);
  message.error ? request.reject(Error(message.error.message)) : request.resolve(message.result);
});
const send = (method,params={}) => new Promise((resolve,reject) => {
  const key = ++id;
  const timer = setTimeout(() => { pending.delete(key); reject(Error(`Timeout: ${method}`)); },20000);
  pending.set(key,{resolve,reject,timer}); socket.send(JSON.stringify({id:key,method,params}));
});
const evaluate = async expression => {
  const response = await send('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true});
  if (response.exceptionDetails) throw Error(JSON.stringify(response.exceptionDetails));
  return response.result.value;
};
const pause = ms => new Promise(resolve => setTimeout(resolve,ms));
const report = [];
try {
  await send('Page.enable'); await send('Runtime.enable');
  for (const width of [1440,390]) {
    await send('Emulation.setDeviceMetricsOverride',{width,height:1000,deviceScaleFactor:1,mobile:width===390});
    await send('Page.navigate',{url:`https://dev.ehpmi.org/?stage8=regions-${phase}`});
    for (let attempt=0;attempt<80;attempt++) {
      await pause(200); if (await evaluate('document.readyState === "complete"')) break;
    }
    await evaluate('Promise.race([document.fonts.ready,new Promise(resolve=>setTimeout(resolve,5000))])');
    for (const [region,selector] of Object.entries({hero:'.hero',map:'.map',newsletter:'.newsletter',footer:'body > footer'})) {
      await evaluate(`document.querySelector(${JSON.stringify(selector)}).scrollIntoView({block:'center',behavior:'instant'})`);
      await pause(800);
      const data = await evaluate(`(()=>{const e=document.querySelector(${JSON.stringify(selector)});if(!e)throw Error('Missing region');return {region:${JSON.stringify(region)},width:innerWidth,text:e.innerText,icons:[...e.querySelectorAll('i,svg')].map(x=>({class:x.getAttribute('class'),before:getComputedStyle(x,'::before').content,font:getComputedStyle(x,'::before').fontFamily,width:x.getBoundingClientRect().width})),overflow:document.documentElement.scrollWidth>innerWidth}})()`);
      report.push(data);
      const shot = await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
      fs.writeFileSync(`${dir}/${region}-${width}-${phase}.png`,Buffer.from(shot.data,'base64'));
    }
  }
  fs.writeFileSync(`${dir}/regions-${phase}.json`,JSON.stringify(report,null,2)+'\n');
  console.log(JSON.stringify({status:'PASS',regions:report.length,phase}));
} finally { for (const request of pending.values()) clearTimeout(request.timer); socket.close(); }
