const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'index.php'), 'utf8');

test('All inline page scripts parse', () => {
  for (const match of source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)) {
    new vm.Script(match[1]);
  }
});

test('Acknowledgement controls and suppression are removed', () => {
  assert.doesNotMatch(source, /f-eol-override|toggleEolOverride|eolMenuHtml|a\.eolOverride|flag-override|Acknowledge EOL/);
  const api = fs.readFileSync(path.join(root, 'api', 'assets.php'), 'utf8');
  assert.doesNotMatch(api, /eol_override|eolOverride/);
});

test('EOL warnings depend on dates even with a legacy override argument', () => {
  const start = source.indexOf('function eolStatus(');
  const end = source.indexOf('\n}', source.indexOf('function eolFlag(', start)) + 2;
  const context = vm.createContext({});
  vm.runInContext(source.slice(start, end), context);
  assert.equal(context.eolStatus(null), null);
  assert.equal(context.eolStatus('2000-01-01', true), 'critical');
  assert.match(context.eolFlag('2000-01-01', true), /EOL Overdue/);
  assert.equal(context.eolFlag('2099-01-01'), null);
});