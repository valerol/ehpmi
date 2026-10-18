import fs from 'node:fs';
import path from 'node:path';
const base=path.dirname(new URL(import.meta.url).pathname);
const read=name=>JSON.parse(fs.readFileSync(path.join(base,name),'utf8'));
const before=read('ehpmi-stage7-dev-records-before.json');
const after=read('ehpmi-stage7-dev-final.json');
const original=read('ehpmi-stage7-dev-before.json');
const prodBefore=read('ehpmi-stage7-production-before.json');
const prodAfter=read('ehpmi-stage7-production-after.json');
const semantics=read('semantic-final.json');
const prior=read('semantic-before.json');
const browser=read('browser-final.json');
const interaction=read('interactions.json');
const equal=(a,b)=>JSON.stringify(a)===JSON.stringify(b);
const controlledKeys=['root','database','home','siteurl','records_hash','page_records_hash','widget_options_hash','core_file','files','migration_markers','newsletter_tables'];
const changedRecords=Object.keys(before.record_hashes).filter(id=>before.record_hashes[id]!==after.record_hashes[id]);
const newRecords=Object.keys(after.record_hashes).filter(id=>!before.record_hashes[id]);
const originalPageMap=new Map(original.pages.map(page=>[page.url,page]));
const listingPaths=['/projects/','/projects/current/','/projects/past/','/projects/potential/','/blog/','/blog/news/','/library/','/library/action-plans/','/library/health-and-pollution/','/library/lead-health-risk-assessment/'];
const listingParity=listingPaths.map(url=>{const a=prior.find(page=>new URL(page.url).pathname===url);const b=semantics.find(page=>new URL(page.url).pathname===url);return {url,identicalHeadingsAndCards:equal(a.headings,b.headings)};});
const audit=original.pages.map(page=>({id:page.ID,url:page.url,scope:page.slug==='projects'||page.slug==='blog'||page.parent===2378||page.parent===2379?'News/Project regression control':'Stage 7 audit',bodyBytes:Buffer.byteLength(page.content),blocks:[...page.content.matchAll(/<!-- wp:([a-z0-9/-]+)/g)].map(match=>match[1]),classic:!page.content.includes('<!-- wp:')&&!!page.content,inlineStyleAttributes:(page.content.match(/\bstyle=/g)||[]).length,tables:(page.content.match(/<table\b/gi)||[]).length,legacyAnchors:(page.content.match(/<a\b[^>]*\bname=/gi)||[]).length,iframes:(page.content.match(/<iframe\b/gi)||[]).length}));
const summary={status:'PASS_PENDING_MANUAL_ACCEPTANCE',baselineCommit:'55295474e7cf3f65a5f7919140559dd99f322311',pages:semantics.length,auditedOutsideNewsProject:audit.filter(page=>page.scope==='Stage 7 audit').length,semanticDefects:semantics.filter(page=>page.status!==200||page.h1!==1||page.main!==1||page.emptyHeadings||page.jumps.length||page.anchors.length||page.headings.find(h=>h.level===1)?.text!==originalPageMap.get(page.url).title),browserViews:browser.visual.length,overflowViews:browser.visual.filter(view=>view.documentWidth>view.width),postRequests:[...browser.postRequests,...interaction.postRequests],changedExistingRecords:changedRecords,addedRecords:newRecords,newsletterTablesUnchanged:equal(before.newsletter_tables,after.newsletter_tables),fixture2458Unchanged:equal(before.fixture_2458,after.fixture_2458),productionControls:controlledKeys.map(key=>({key,unchanged:equal(prodBefore[key],prodAfter[key])})),listingParity,interactionStatus:interaction.status};
if(summary.semanticDefects.length||summary.overflowViews.length||summary.postRequests.length||!equal(changedRecords,['1480','2389'])||!equal(newRecords,['2459','2460'])||!summary.newsletterTablesUnchanged||!summary.fixture2458Unchanged||summary.productionControls.some(control=>!control.unchanged)||listingParity.some(page=>!page.identicalHeadingsAndCards)||summary.interactionStatus!=='PASS')throw Error('Evidence invariant failed');
const incidentFolder=path.join(base,'widget-content-incident');
if(fs.existsSync(path.join(incidentFolder,'ehpmi-stage7-widgets-repaired-final.json'))){
 const incident=read('widget-content-incident/ehpmi-stage7-widgets-incident-current.json');
 const current=read('widget-content-incident/ehpmi-stage7-widgets-repaired-final.json');
 const currentProd=read('widget-content-incident/ehpmi-stage7-production-widget-repair-after.json');
 const repair=read('widget-content-incident/repair-output.json');
 const rest=read('widget-content-incident/editor-rest-roundtrip.json');
 const visual=read('widget-content-incident/widgets-repaired-browser.json');
 const proof={status:'COMPENSATED_PENDING_OPERATOR_VISUAL_CONFIRMATION',repairResult:repair.result,exactPreStageRestore:repair.exact_pre_stage_restore,restSaveReload:rest.result,restoredTextBrowser:visual.status,repairPostRecordsUnchanged:equal(incident.record_hashes,current.record_hashes),subscriberTablesUnchanged:equal(before.newsletter_tables,current.newsletter_tables),fixture2458Unchanged:equal(before.fixture_2458,current.fixture_2458),productionUnchanged:controlledKeys.every(key=>equal(prodBefore[key],currentProd[key]))};
 if(proof.repairResult!=='PASS'||proof.exactPreStageRestore!=='PASS'||proof.restSaveReload!=='PASS'||proof.restoredTextBrowser!=='PASS'||!proof.repairPostRecordsUnchanged||!proof.subscriberTablesUnchanged||!proof.fixture2458Unchanged||!proof.productionUnchanged)throw Error('Corrective evidence invariant failed');
 summary.status=proof.status;summary.widgetContentIncident=proof;summary.initialImplementationRevisionIds=summary.addedRecords;summary.addedRecords=Object.keys(current.record_hashes).filter(id=>!before.record_hashes[id]);summary.priorManualPhaseAdditionalRevisionIds=summary.addedRecords.filter(id=>!summary.initialImplementationRevisionIds.includes(id));
}
if(fs.existsSync(path.join(base,'acceptance-checks.json'))&&/^status: ACCEPTED$/m.test(fs.readFileSync(path.join(base,'manifest.yml'),'utf8'))){
 const accepted=read('acceptance-checks.json');
 const confirmation='Hero, Map и Footer восстановлены на desktop и 390. Newsletter отображается корректно.';
 const manual=fs.readFileSync(path.join(base,'manual-acceptance.md'),'utf8');
 if(accepted.status!=='PASS'||!manual.includes('Status: ACCEPTED.')||!manual.includes(confirmation)||!manual.includes('2026-10-18 (Asia/Makassar)'))throw Error('Acceptance attribution/checks mismatch');
 summary.status='ACCEPTED';summary.acceptance={date:'2026-10-18',timezone:'Asia/Makassar',source:'OPERATOR_DIRECT_CONFIRMATION',statement:confirmation,postConfirmationReadOnlyChecks:'PASS',additionalPostRepairBrowserAdminDetails:'NOT_INFERRED'};
 if(summary.widgetContentIncident)summary.widgetContentIncident.status='COMPENSATED_ACCEPTED_OPERATOR_VISUAL_CONFIRMATION';
}
fs.writeFileSync(path.join(base,'audit-inventory.json'),JSON.stringify(audit,null,2)+'\n');
fs.writeFileSync(path.join(base,'qa-summary.json'),JSON.stringify(summary,null,2)+'\n');
console.log(JSON.stringify(summary,null,2));
