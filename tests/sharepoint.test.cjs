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
  const selectors = [{dataset:{index:'0'}, disabled:false}];
  const document = {
    getElementById(id) {
      if (!elements.has(id)) elements.set(id, {disabled:false, innerHTML:'', textContent:'', prepend(value) { this.summary = value.textContent; }});
      return elements.get(id);
    },
    querySelectorAll: () => selectors,
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
  app.selectors.push({dataset:{index:'1'},disabled:false});
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
  app.selectors.push({dataset:{index:'0'}});
  await app.context.applySharePoint();
  assert.match(app.elements.get('sharepoint-result-0').textContent, /Unexpected sync response/);
  assert.match(app.elements.get('sharepoint-output').summary, /0 updated, 0 created, 1 failed/);
});
