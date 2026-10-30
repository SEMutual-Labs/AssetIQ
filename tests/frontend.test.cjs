const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');

function assetSelectionContext() {
  const elements = new Map();
  const element = id => {
    if (!elements.has(id)) {
      const classes = new Set();
      elements.set(id, { value: '', style: {}, innerHTML: '', classList: {
        add: name => classes.add(name), remove: name => classes.delete(name),
        contains: name => classes.has(name),
        toggle: (name, checked) => checked ? classes.add(name) : classes.delete(name),
      } });
    }
    return elements.get(id);
  };
  const context = vm.createContext({
    document: { getElementById: element, querySelectorAll: () => [...elements.values()] },
    URLSearchParams, API: '/assets', sortKey: 'id', sortAsc: true,
    apiFetch: async () => [],
  });
  const start = source.indexOf('let batchMode    =');
  const end = source.indexOf('\nasync function batchDelete(', start);
  vm.runInContext(source.slice(start, end), context);
  vm.runInContext("cachedAssets = ['D', 'B', 'A', 'C'].map(id => ({id}));", context);
  return { context, element, selected: () => Array.from(vm.runInContext('[...selectedIds]', context)) };
}

test('Assets Shift-click selects and deselects inclusive ranges in displayed order in both views', () => {
  const { context, element, selected } = assetSelectionContext();
  const click = (id, shiftKey = false) => context.toggleCardSelect(id, { shiftKey, stopPropagation() {} });
  click('B');
  click('C', true);
  assert.deepEqual(selected(), ['B', 'A', 'C']);
  click('B', true);
  assert.deepEqual(selected(), []);
  click('C');
  click('D', true);
  assert.deepEqual(new Set(selected()), new Set(['D', 'B', 'A', 'C']));
  for (const id of selected()) {
    assert.ok(element('card-' + id).classList.contains('selected'));
    assert.ok(element('trow-' + id).classList.contains('selected'));
  }
  assert.equal(element('batch-count').textContent, '4 selected');
  context.cancelBatchMode();
  assert.deepEqual(selected(), []);
  assert.equal(vm.runInContext('assetSelectionAnchor', context), null);
  context.toggleBatchMode();
  click('B');
  context.selectAll();
  assert.equal(selected().length, 4);
  assert.equal(vm.runInContext('assetSelectionAnchor', context), null);
});

test('Assets empty and failed reloads discard stale selection, cached order and range anchor', async () => {
  const { context, selected } = assetSelectionContext();
  vm.runInContext('async ' + functionSource('loadAssets', 'pastEolAssets'), context);
  for (const fail of [false, true]) {
    vm.runInContext("cachedAssets = [{id:'B'}];", context);
    context.toggleCardSelect('B', { stopPropagation() {} });
    context.apiFetch = async () => { if (fail) throw new Error('offline'); return []; };
    await context.loadAssets();
    assert.deepEqual(selected(), []);
    assert.equal(vm.runInContext('cachedAssets.length', context), 0);
    assert.equal(vm.runInContext('assetSelectionAnchor', context), null);
  }
});

test('Assets filtered reload preserves only visible selection and uses the new sort order for ranges', async () => {
  const { context, element, selected } = assetSelectionContext();
  Object.assign(context, {
    esc: String, jsArg: JSON.stringify, assetCard: () => '',
    typeBadge: () => '', eolStatus: () => '', eolFlag: () => '',
  });
  vm.runInContext('async ' + functionSource('loadAssets', 'pastEolAssets'), context);
  context.toggleCardSelect('D');
  context.toggleCardSelect('B');
  context.apiFetch = async () => ['B', 'C', 'A'].map(id => ({ id }));
  await context.loadAssets();
  assert.deepEqual(selected(), ['B']);
  assert.equal(vm.runInContext('assetSelectionAnchor', context), null);
  assert.ok(element('card-B').classList.contains('selected'));
  assert.ok(element('trow-B').classList.contains('selected'));
  context.toggleCardSelect('C', { shiftKey: true, stopPropagation() {} });
  assert.deepEqual(selected(), ['B', 'C']);
  context.toggleCardSelect('A', { shiftKey: true, stopPropagation() {} });
  assert.deepEqual(selected(), ['B', 'C', 'A']);
});

function functionSource(name, nextName) {
  const start = source.indexOf('function ' + name + '(');
  const end = source.indexOf('\nfunction ' + nextName + '(', start);
  assert.ok(start >= 0 && end > start);
  return source.slice(start, end);
}

test('AI estimate text is escaped and numeric handler cannot contain executable text', async () => {
  const elements = new Map();
  for (const name of ['ai-price-btn', 'ai-price-result', 'f-name', 'f-type', 'f-serial', 'f-date', 'f-notes']) {
    elements.set(name, { value: name === 'f-name' ? 'Laptop' : '', style: {}, innerHTML: '' });
  }
  const context = vm.createContext({
    document: { getElementById: name => elements.get(name) },
    fetch: async () => ({ ok: true, json: async () => ({
      low: 1, high: 10, midpoint: "0';alert(1)", confidence: 'high',
      currency: '<img src=x>', reasoning: '<script>alert(1)</script>', caveat: '<b>test</b>',
    }) }),
  });
  const escLine = source.match(/function esc\(s\)\{[^\n]+/)[0];
  vm.runInContext(escLine + '\nasync ' + functionSource('getAIPrice', 'goToAssets'), context);
  await context.getAIPrice();
  const html = elements.get('ai-price-result').innerHTML;
  assert.doesNotMatch(html, /<script>|<img|<b>|0';alert/);
  assert.match(html, /&lt;script&gt;/);
});

test('Settings collapse setup is idempotent', () => {
  const classes = new Set();
  let listeners = 0;
  let icons = 0;
  const title = {
    classList: { contains: name => classes.has(name), add: name => classes.add(name) },
    appendChild: () => icons++, addEventListener: () => listeners++,
  };
  const body = { style: {}, scrollHeight: 40 };
  const section = { querySelector: selector => selector === '.settings-section-title' ? title : body };
  const context = vm.createContext({ document: {
    querySelectorAll: () => [section], createElement: () => ({}),
  } });
  vm.runInContext(functionSource('initSettingsCollapse', 'wrapSettingsBodies'), context);
  context.initSettingsCollapse();
  context.initSettingsCollapse();
  assert.equal(listeners, 1);
  assert.equal(icons, 1);
});

test('Obsolete batch archive action is removed', () => {
  assert.doesNotMatch(source, /function batchArchive\(/);
});

test('Quoted handler arguments survive HTML decoding without executing content', () => {
  const start = source.indexOf('function esc(s)');
  const end = source.indexOf('\nfunction csvCell(', start);
  const context = vm.createContext({});
  vm.runInContext(source.slice(start, end), context);
  for (const value of ["O'Connor", 'Monitor "27"', 'x\\y', "');throw new Error('injected');//", '<img src=x>&']) {
    const decoded = context.jsArg(value).replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
    assert.equal(vm.runInNewContext(decoded), value);
  }
});

test('CSV output protects formula cells and preserves numeric zero', () => {
  const start = source.indexOf('function csvCell(');
  const end = source.indexOf('\n}', start) + 2;
  const context = vm.createContext({});
  vm.runInContext(source.slice(start, end), context);
  assert.equal(context.csvCell('=1+1'), '"\'=1+1"');
  assert.equal(context.csvCell('Name "quoted"'), '"Name ""quoted"""');
  assert.equal(context.csvCell(0), '"0"');
  assert.equal(context.csvCell(null), '""');
});

test('Asset and report exports have distinct handlers and history calls existing editor', () => {
  assert.equal([...source.matchAll(/async function exportCSV\(/g)].length, 1);
  assert.equal([...source.matchAll(/async function exportAssetCSV\(/g)].length, 1);
  assert.match(source, /onclick="exportAssetCSV\(\)"/);
  assert.doesNotMatch(source, /openEditModal/);
});