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

const plan = () => ({previewId:'server-plan', rows:[{assetId:'SEM-NB01',status:'ready',
  changes:[{column:'Make/Model',before:'Old',after:'<img src=x>'}], warnings:['Status preserved (Lost)']}]});

test('Preview is read-only and displays escaped field changes and warnings', async () => {
  const calls = [];
  const app = setup(async (url, opts) => { calls.push(JSON.parse(opts.body)); return plan(); });
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
  const app = setup(async (url, opts) => { calls.push(JSON.parse(opts.body)); return plan(); });
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
  assert.match(app.elements.get('sharepoint-output').summary, /0 updated, 1 failed/);
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
