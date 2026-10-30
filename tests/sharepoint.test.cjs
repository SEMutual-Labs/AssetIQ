const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');
const start = source.indexOf('const SHAREPOINT_CSRF');
const end = source.indexOf('async function loadSettings()', start);
const script = source.slice(start, end).replace(/<\?=[\s\S]*?\?>/, '"csrf-test"');

function setup(apiFetch, confirm = () => true) {
  const elements = new Map();
  const selectors = [{dataset:{index:'0'}, disabled:false, checked:true}];
  const document = {
    getElementById(id) {
      if (!elements.has(id)) elements.set(id, {disabled:false, hidden:true, innerHTML:'', textContent:'',
        removeAttribute(name) { delete this[name]; }, prepend(value) { this.summary = value.textContent; }});
      return elements.get(id);
    },
    querySelectorAll: selector => selector === '.sharepoint-bulk' ? []
      : selector.includes(':checked') ? selectors.filter(box => box.checked) : selectors,
    createElement: () => ({textContent:'', setAttribute() {}}),
  };
  const messages = [];
  const context = vm.createContext({document, apiFetch, confirm, authRedirectPending:false,
    toast:(...args) => messages.push(args), esc:value => String(value || '').replaceAll('<', '&lt;').replaceAll('>', '&gt;')});
  vm.runInContext(script, context);
  return {context, elements, messages, selectors};
}

const plan = () => ({previewId:'server-plan', rows:[{assetId:'SEM-NB01',status:'ready',operation:'update',
  changes:[{column:'Make/Model',before:'Old',after:'<img src=x>'}], warnings:['Status preserved (Lost)']}]});

test('Preview is read-only and displays escaped field changes and warnings', async () => {
  const calls = [];
  const app = setup(async (url, opts) => {
    const body = JSON.parse(opts.body);
    calls.push(body);
    return body.action === 'apply' ? {status:'updated'} : plan();
  });
  await app.context.previewSharePoint();
  assert.equal(calls.length, 1);
  assert.equal(calls[0].action, 'preview');
  assert.equal(calls[0].csrf, 'csrf-test');
  const html = app.elements.get('sharepoint-output').innerHTML;
  assert.match(html, /&lt;img src=x&gt;/);
  assert.match(html, /Status preserved \(Lost\)/);
  assert.equal(app.elements.get('sharepoint-apply').disabled, false);
});

test('Cancelled confirmation makes no writes', async () => {
  const calls = [];
  const app = setup(async (url, opts) => { calls.push(JSON.parse(opts.body)); return plan(); }, () => false);
  await app.context.previewSharePoint();
  await app.context.applySharePoint();
  assert.equal(calls.length, 1);
});

test('Apply sends only selected server-side plan references and requires another preview', async () => {
  const calls = [];
  const app = setup(async (url, opts) => {
    const body = JSON.parse(opts.body);
    calls.push(body);
    return body.action === 'apply' ? {status:'updated'} : plan();
  });
  await app.context.previewSharePoint();
  await app.context.applySharePoint();
  assert.deepEqual(calls[1], {action:'apply',csrf:'csrf-test',previewId:'server-plan',assetId:'SEM-NB01'});
  assert.equal(app.elements.get('sharepoint-result-0').textContent, 'Updated successfully.');
  assert.equal(app.elements.get('sharepoint-apply').disabled, true);
  await app.context.applySharePoint();
  assert.equal(calls.length, 2);
});

test('Failed apply stays visible and is never automatically retried', async () => {
  const app = setup(async (url, opts) => {
    if (JSON.parse(opts.body).action === 'apply') throw new Error('HTTP 412: preview again');
    return plan();
  });
  await app.context.previewSharePoint();
  await app.context.applySharePoint();
  assert.match(app.elements.get('sharepoint-result-0').textContent, /HTTP 412/);
  assert.match(app.elements.get('sharepoint-output').summary, /0 updated, 0 created, 1 failed/);
  assert.equal(app.elements.get('sharepoint-preview').disabled, false);
});

test('Preview failure is visible and disables writes', async () => {
  const app = setup(async () => { throw new Error('Configure SHAREPOINT_CLIENT_ID'); });
  await app.context.previewSharePoint();
  assert.match(app.elements.get('sharepoint-output').textContent, /Configure SHAREPOINT_CLIENT_ID/);
  assert.equal(app.elements.get('sharepoint-apply').disabled, true);
});

test('Preview preserves zero in currency before/after values', async () => {
  const data = plan();
  data.rows[0].changes = [{column:'Purchase Price',before:15,after:0}];
  const app = setup(async () => data);
  await app.context.previewSharePoint();
  assert.match(app.elements.get('sharepoint-output').innerHTML, /15 &rarr; 0/);
});

test('New items are labelled and unchecked; blocked rows cannot be selected', async () => {
  const data = plan();
  data.rows.push({assetId:'SEM-NB02',status:'ready',operation:'create',changes:[],warnings:[]});
  data.rows.push({assetId:'SEM-NB03',status:'blocked',operation:'create',changes:[],warnings:[],error:'Missing required values: Serial Number, Purchase Date'});
  const app = setup(async () => data);
  await app.context.previewSharePoint();
  const html = app.elements.get('sharepoint-output').innerHTML;
  assert.match(html, /1 ready to update, 1 ready to create, 0 unchanged, 1 blocked/);
  assert.match(html, /data-index="0" checked/);
  assert.match(html, /data-index="1"\s+aria-label/);
  assert.doesNotMatch(html, /data-index="2"/);
  assert.match(html, /NEW ITEM/);
  assert.match(html, /Missing required values: Serial Number, Purchase Date/);
});

test('Create-only apply explicitly confirms creation and displays created result', async () => {
  const data = plan();
  data.rows[0].operation = 'create';
  const calls = [];
  let prompt = '';
  const app = setup(async (url, opts) => {
    const body = JSON.parse(opts.body);
    calls.push(body);
    return body.action === 'preview' ? data : {status:'created',itemId:'43'};
  }, message => { prompt = message; return true; });
  await app.context.previewSharePoint();
  await app.context.applySharePoint();
  assert.match(prompt, /Update 0 existing SharePoint item\(s\) and create 1 new item\(s\)/);
  assert.equal(app.elements.get('sharepoint-result-0').textContent, 'Created successfully.');
  assert.match(app.elements.get('sharepoint-output').summary, /0 updated, 1 created, 0 failed/);
  assert.deepEqual(calls[1], {action:'apply',csrf:'csrf-test',previewId:'server-plan',assetId:'SEM-NB01'});
  assert.equal(app.elements.get('sharepoint-apply').disabled, true);
});

test('Mixed selection reports updates and creates separately', async () => {
  const data = plan();
  data.rows.push({...data.rows[0],assetId:'SEM-NB02',operation:'create'});
  let prompt = '';
  const app = setup(async (url, opts) => {
    const body = JSON.parse(opts.body);
    return body.action === 'preview' ? data : {status:body.assetId === 'SEM-NB02' ? 'created' : 'updated'};
  }, message => { prompt = message; return true; });
  app.selectors.push({dataset:{index:'1'},disabled:false,checked:true});
  await app.context.previewSharePoint();
  await app.context.applySharePoint();
  assert.match(prompt, /Update 1 existing SharePoint item\(s\) and create 1 new item\(s\)/);
  assert.match(app.elements.get('sharepoint-output').summary, /1 updated, 1 created, 0 failed/);
});

test('No selection and unexpected responses never report a successful create', async () => {
  const data = plan();
  data.rows[0].operation = 'create';
  let applies = 0;
  const app = setup(async (url, opts) => {
    if (JSON.parse(opts.body).action === 'apply') { applies++; return {status:'updated'}; }
    return data;
  });
  await app.context.previewSharePoint();
  app.selectors.splice(0);
  await app.context.applySharePoint();
  assert.equal(applies, 0);
  app.selectors.push({dataset:{index:'0'},checked:true});
  await app.context.applySharePoint();
  assert.match(app.elements.get('sharepoint-result-0').textContent, /Unexpected sync response/);
  assert.match(app.elements.get('sharepoint-output').summary, /0 updated, 0 created, 1 failed/);
});

test('Shift-click selects and excludes forward and backward ranges while skipping unavailable rows', async () => {
  const data = plan();
  data.rows = Array.from({length:6}, (_, i) => ({...data.rows[0],assetId:`ASSET-${i}`,operation:i === 4 ? 'create' : 'update'}));
  const app = setup(async () => data);
  app.selectors.splice(0, 1, ...[0,2,4,5].map(index => ({dataset:{index:String(index)},checked:false,disabled:false})));
  await app.context.previewSharePoint();
  let stopped = 0;
  const click = (box, checked, shiftKey = false) => {
    box.checked = checked;
    app.context.selectSharePointRange({shiftKey,stopPropagation() { stopped++; }}, box);
  };
  click(app.selectors[0], true);
  click(app.selectors[2], true, true);
  assert.deepEqual(app.selectors.map(box => box.checked), [true,true,true,false]);
  assert.match(app.elements.get('sharepoint-selection-count').textContent, /3 of 4 ready selected \(2 updates, 1 new items\)/);
  click(app.selectors[2], false);
  click(app.selectors[0], false, true);
  assert.deepEqual(app.selectors.map(box => box.checked), [false,false,false,false]);
  assert.equal(app.elements.get('sharepoint-apply').disabled, true);
  assert.equal(stopped, 4);
});

test('Bulk selection includes new items explicitly, clears selection and resets range anchor', async () => {
  const data = plan();
  data.rows.push({...data.rows[0],assetId:'NEW',operation:'create'});
  const app = setup(async () => data);
  app.selectors.push({dataset:{index:'1'},checked:false,disabled:false});
  await app.context.previewSharePoint();
  app.context.selectSharePointAll(true);
  assert.deepEqual(app.selectors.map(box => box.checked), [true,true]);
  assert.match(app.elements.get('sharepoint-selection-count').textContent, /1 updates, 1 new items/);
  app.context.selectSharePointAll(false);
  assert.deepEqual(app.selectors.map(box => box.checked), [false,false]);
  assert.equal(app.elements.get('sharepoint-apply').disabled, true);
  app.selectors[1].checked = true;
  app.context.selectSharePointRange({shiftKey:true,stopPropagation() {}}, app.selectors[1]);
  assert.equal(app.selectors[0].checked, false);
});

test('Progress reports zero before response, each completed request, failures, and final completion', async () => {
  const data = plan();
  data.rows.push({...data.rows[0],assetId:'NEW',operation:'create'});
  const pending = [];
  const app = setup(async (url, opts) => JSON.parse(opts.body).action === 'preview' ? data
    : new Promise((resolve, reject) => pending.push({resolve,reject})));
  app.selectors.push({dataset:{index:'1'},checked:true,disabled:false});
  await app.context.previewSharePoint();
  const applying = app.context.applySharePoint();
  assert.equal(app.elements.get('sharepoint-progress').hidden, false);
  assert.equal(app.elements.get('sharepoint-progress-bar').value, 0);
  assert.equal(app.elements.get('sharepoint-progress-bar').max, 2);
  assert.match(app.elements.get('sharepoint-progress-label').textContent, /Updating SEM-NB01/);
  app.context.selectSharePointAll(false);
  assert.equal(app.selectors[0].checked, true);
  pending[0].resolve({status:'updated'});
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(app.elements.get('sharepoint-progress-bar').value, 1);
  assert.match(app.elements.get('sharepoint-progress-detail').textContent, /1 \/ 2 processed \(50%\)/);
  assert.match(app.elements.get('sharepoint-progress-label').textContent, /Creating NEW/);
  pending[1].reject(new Error('Create failed'));
  await applying;
  assert.equal(app.elements.get('sharepoint-progress-bar').value, 2);
  assert.match(app.elements.get('sharepoint-progress-detail').textContent, /100%.*1 updated, 0 created, 1 failed/);
  assert.equal(app.elements.get('sharepoint-progress-label').textContent, 'Sync finished with errors');
});

test('Expired sessions stop below 100 percent and report unattempted items', async () => {
  const data = plan();
  data.rows.push({...data.rows[0],assetId:'NEXT'});
  const app = setup(async (url, opts) => {
    if (JSON.parse(opts.body).action === 'preview') return data;
    app.context.authRedirectPending = true;
    throw new Error('Session expired');
  });
  app.selectors.push({dataset:{index:'1'},checked:true,disabled:false});
  await app.context.previewSharePoint();
  await app.context.applySharePoint();
  assert.equal(app.elements.get('sharepoint-progress-bar').value, 1);
  assert.match(app.elements.get('sharepoint-progress-detail').textContent, /50%/);
  assert.match(app.elements.get('sharepoint-output').summary, /1 not attempted/);
  assert.equal(app.elements.get('sharepoint-progress-label').textContent, 'Sync stopped - sign-in required');
});

test('Preview shows indeterminate progress and hides it on failure', async () => {
  let reject;
  const app = setup(() => new Promise((resolve, fail) => { reject = fail; }));
  const loading = app.context.previewSharePoint();
  assert.equal(app.elements.get('sharepoint-progress').hidden, false);
  assert.equal(app.elements.get('sharepoint-progress-bar').value, undefined);
  reject(new Error('Read failed'));
  await loading;
  assert.equal(app.elements.get('sharepoint-progress').hidden, true);
  assert.equal(app.elements.get('sharepoint-output').textContent, 'Read failed');
});
