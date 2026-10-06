const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');
function loadFunctions(startName, endName, globals = {}) {
  const start = source.indexOf('function ' + startName + '(');
  const end = source.indexOf('\nfunction ' + endName + '(', start);
  assert.ok(start >= 0 && end > start);
  const context = vm.createContext(globals);
  vm.runInContext(source.slice(start, end), context);
  return context;
}

test('Model search matches numeric fragments, punctuation variants, IDs, serials and notes', () => {
  const context = loadFunctions('findModelMatches', 'onNameInput');
  const assets = [
    { name: 'Dell Latitude 5440', type: 'Laptop', id: 'SEM-NB01', serial: 'ABC-123', notes: 'Model P137G' },
    { name: 'Dell Latitude 5440', type: 'Laptop', id: 'SEM-NB02' },
    { name: 'Dell Latitude 5440', type: 'Desktop', id: 'SEM-PC01' },
  ];
  assert.equal(context.findModelMatches(assets, '5440').length, 2);
  assert.equal(context.findModelMatches(assets, 'latitude-5440').length, 2);
  assert.equal(context.findModelMatches(assets, 'p137g').length, 1);
  assert.equal(context.findModelMatches(assets, 'abc123').length, 1);
  assert.equal(context.findModelMatches(assets, 'nb01').length, 1);
  assert.equal(context.findModelMatches(assets, 'missing').length, 0);
  assert.equal(context.findModelMatches(assets, '').length, 2);
});

test('Copying a model preserves shared details and never copies device identity or ownership', () => {
  const context = loadFunctions('findModelMatches', 'onNameInput');
  const template = context.modelTemplate({
    name: 'Latitude 5440', type: 'Laptop', cost: 0, notes: '16 GB RAM',
    id: 'SEM-NB01', serial: 'OLD123', assignedTo: 'Someone', dept: 'IT',
    purchaseDate: '2020-01-01', endOfLife: '2026-01-01', status: 'retired',
  });
  assert.deepEqual(JSON.parse(JSON.stringify(template)), { name: 'Latitude 5440', type: 'Laptop', cost: 0, notes: '16 GB RAM' });
});

test('Batch serial parsing trims lines, ignores empty lines and rejects duplicates and oversized batches', () => {
  const context = loadFunctions('parseLaptopSerials', 'isLaptopBatch');
  assert.deepEqual(Array.from(context.parseLaptopSerials(' SN-001\r\n\nSN-002 \n')), ['SN-001', 'SN-002']);
  assert.throws(() => context.parseLaptopSerials('abc\nABC'), /Duplicate/);
  assert.throws(() => context.parseLaptopSerials(' \n'), /at least one/);
  assert.throws(() => context.parseLaptopSerials(Array.from({length:101}, (_, index) => 'SN' + index).join('\n')), /100/);
  assert.throws(() => context.parseLaptopSerials('x'.repeat(256)), /255/);
});