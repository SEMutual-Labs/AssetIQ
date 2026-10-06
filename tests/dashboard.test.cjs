const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const test=require('node:test');
const source=fs.readFileSync(path.join(__dirname,'..','index.php'),'utf8');
const start=source.indexOf('function pastEolAssets(');
const end=source.indexOf('\nfunction assetCard(',start);
test('Past EOL count excludes upcoming, retired, archived and unset dates',()=>{
  const context=vm.createContext({});vm.runInContext(source.slice(start,end),context);
  const assets=[{id:'past',endOfLife:'2026-10-05'},{id:'today',endOfLife:'2026-10-06'},{id:'future',endOfLife:'2026-10-07'},{id:'retired',status:'retired',endOfLife:'2020-01-01'},{id:'archived',archived:true,endOfLife:'2020-01-01'},{id:'unset'}];
  assert.deepEqual(Array.from(context.pastEolAssets(assets,new Date(2026,9,6)),asset=>asset.id),['past']);
});
test('Desktop rows open assets once without intercepting actions or selection',async()=>{
  let mobile=false,opens=0,selections=0;
  const context=vm.createContext({window:{matchMedia:()=>({matches:mobile})},batchMode:false,editAsset:async()=>{opens++;},toggleCardSelect:()=>{selections++;}});
  vm.runInContext(source.slice(start,end),context);
  const event={type:'click',target:{closest:()=>null}};
  context.openAssetRow(event,'asset');assert.equal(opens,1);
  context.openAssetRow({...event,target:{closest:()=>({})}},'asset');assert.equal(opens,1);
  context.batchMode=true;context.openAssetRow(event,'asset',true);assert.equal(selections,1);assert.equal(opens,1);
  mobile=true;context.openAssetRow(event,'asset');assert.equal(opens,1);
});
test('Overdue stat follows Total Value and replaces the dashboard banner',()=>{
  assert.ok(source.indexOf("{label:'Past End of Life'")>source.indexOf("{label:'Total Value'"));
  assert.doesNotMatch(source,/eol-banner-dash/);
  assert.match(source,/id="recent-tbody"/);
});
test('Obsolete Intune and ADP UI is removed while General settings and startup remain',()=>{
  const uiIds=Array.from(source.matchAll(/\bid\s*=\s*["']([^"']+)["']/g),match=>match[1]);
  assert.deepEqual(uiIds.filter(id=>/intune/i.test(id)),[]);
  assert.doesNotMatch(source,/\b(?:exportADP|INTUNE_API|switchSettingsTab)\b/);
  assert.match(source,/id="settings-tab-general"/);
  assert.match(source,/loadDashboard\(\);\s*loadAssets\(\);/);
});