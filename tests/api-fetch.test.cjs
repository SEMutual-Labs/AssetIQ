const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');
const start = source.indexOf('let authRedirectPending=');
const end = source.indexOf('\nfunction showPage(', start);
assert.ok(start >= 0 && end > start, 'Request helper must exist');

function setup(fetch) {
  const redirects = [];
  const messages = [];
  const context = vm.createContext({
    fetch,
    console: { error: () => {} },
    window: { location: { replace: url => redirects.push(url) } },
    toast: (message, type) => messages.push({ message, type }),
  });
  vm.runInContext(source.slice(start, end), context);
  return { apiFetch: context.apiFetch, redirects, messages };
}

test('Concurrent unauthorized requests redirect once without error toasts', async () => {
  const helper = setup(async () => ({ status: 401, ok: false }));
  const results = await Promise.allSettled([
    helper.apiFetch('api/assets.php'),
    helper.apiFetch('api/assets.php?stats=1'),
  ]);
  assert.deepEqual(helper.redirects, ['/auth/login.php']);
  assert.deepEqual(helper.messages, []);
  for (const result of results) {
    assert.equal(result.status, 'rejected');
    assert.match(result.reason.message, /session has expired/);
  }
});

test('Successful requests return data and preserve request options', async () => {
  const data = [{ id: 'SEM-NB01' }];
  const helper = setup(async (url, options) => {
    assert.equal(url, 'api/assets.php');
    assert.equal(options.method, 'PUT');
    assert.equal(options.headers['Content-Type'], 'application/json');
    return { status: 200, ok: true, json: async () => data };
  });
  assert.equal(await helper.apiFetch('api/assets.php', { method: 'PUT' }), data);
  assert.deepEqual(helper.redirects, []);
});

test('Server errors still display the API error without redirecting', async () => {
  const helper = setup(async () => ({
    status: 500, ok: false, json: async () => ({ error: 'Database connection failed' }),
  }));
  await assert.rejects(helper.apiFetch('api/assets.php'), /Database connection failed/);
  assert.deepEqual(helper.messages, [{ message: 'Database connection failed', type: 'error' }]);
  assert.deepEqual(helper.redirects, []);
});

test('Non-JSON errors retain the HTTP status', async () => {
  const helper = setup(async () => ({
    status: 502, ok: false, json: async () => { throw new SyntaxError('Invalid JSON'); },
  }));
  await assert.rejects(helper.apiFetch('api/assets.php'), /API error 502/);
  assert.deepEqual(helper.redirects, []);
});

test('Network failures remain visible and aborted requests remain silent', async () => {
  const originalError = new TypeError('Failed to fetch');
  const network = setup(async () => { throw originalError; });
  await assert.rejects(network.apiFetch('api/assets.php'), error => {
    assert.match(error.message, /Unable to reach the AssetIQ API/);
    assert.equal(error.cause, originalError);
    return true;
  });
  assert.equal(network.messages.length, 1);
  assert.deepEqual(network.redirects, []);
  const aborted = setup(async () => {
    throw Object.assign(new Error('Cancelled'), { name: 'AbortError' });
  });
  await assert.rejects(aborted.apiFetch('api/assets.php'), /Cancelled/);
  assert.deepEqual(aborted.messages, []);
  assert.deepEqual(aborted.redirects, []);
});