const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');

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