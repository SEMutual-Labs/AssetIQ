# AssetIQ Code Review

Reviewed: 2026-10-06.

Scope: all 14 existing PHP files, the deployment README, and local regression
tests. This is a source review, not a production penetration test. The private
config file, deployed database, browser policies, and sign-in configuration are
not available in this workspace.

## Completed Changes

- Removed EOL acknowledgement from the asset form, card menu, request payloads,
  asset API response/write fields, dashboard suppression, reports, and exports.
  Date-based warnings remain. Historical audit labels remain readable. Existing
  database override columns are not dropped, but their values are ignored.
- Stopped allocating IDs that are still occupied by retired assets.
- Scoped custom-field value deletion to the definition's asset type. Deleting a
  Laptop field no longer deletes a Monitor field with the same generated key.
- Replaced the stored API key in settings GET responses with a configured flag.
  Settings key writes and server-side use remain supported.
- Validated AI estimate shape, numeric prices, range ordering, confidence, and
  text fields. Escaped model text before rendering and constrained the price
  inserted into an inline handler to a number.
- Escaped assigned-to table cells and threshold input values.
- Encoded dynamic QR/user/department handler arguments for both JavaScript and
  HTML contexts, so quotes in names or serials do not break handlers.
- Made settings collapse initialization idempotent.
- Removed the unreachable batch archive function. Historical archive labels and
  legacy database fields remain; see the migration finding below.
- Fixed history asset links to call the existing asset editor.
- Separated asset CSV and report CSV handlers. Previously the later duplicate
  function silently replaced the detailed report exporter.
- Protected frontend CSV cells against spreadsheet formula execution and kept
  numeric zero values. Audit CSV needs separate attention below.
- Made the report depreciation table use the configured period instead of a
  hard-coded five years.
- Corrected deployment instructions to use index.php, keep credentials outside
  the web root, include auth files, and document engine/config requirements.

## Remaining Findings

### High: Login State Is Not Bound to the Browser Session

Source: [auth/auth.php](../auth/auth.php), `auth_make_state` and
`auth_verify_state`; [auth/callback.php](../auth/callback.php).

State contains a timestamp and shared-secret HMAC, with no session-specific
nonce or one-time consumption. A state obtained in one browser can validate in
another within the time window. Signature validity does not establish that this
browser initiated the login; this weakens protection against login CSRF.

Recommendation: bind random, expiring, single-use state to the initiating
session. Add PKCE and test success, mismatched session, replay, expiry, and
concurrent login tabs. Verify proxy/session-cookie behavior on staging first.

### High: No Application-Level Role or User Allowlist

Source: [auth/auth.php](../auth/auth.php), `auth_require_json`, and every write API.

The app only checks whether a signed-in profile exists. It does not distinguish
inventory readers from people allowed to delete assets, change settings/keys,
define fields, or invoke paid AI calls. Tenant sign-in alone is not an app role.
Whether unintended users can sign in also depends on external enterprise-app
assignment rules, which were not available for review.

Recommendation: confirm who should have access, enforce an allowlist or app
roles server-side, and separate read/write/admin permissions. Verify assignment
requirements in the deployed identity application. Do not rely on hidden UI.

### High: Schema Changes and Archive Conversion Run on Every DB Include

Source: [db.php](../db.php), `installSchema`; most API files include it before
checking authentication.

Every inclusion issues CREATE/ALTER statements and archive-conversion UPDATEs.
This can take metadata locks, requires elevated DB privileges for ordinary
requests, and may fail on engines without `ADD COLUMN IF NOT EXISTS` support.
Archive endpoints still exist in [api/assets.php](../api/assets.php), but the next
DB inclusion converts archived records into retired assets and clears archive
metadata. That makes the legacy archive/restore contract inconsistent.

Recommendation: move setup into a protected explicit installer/versioned
migrations, authenticate before DB work, and retire archive API routes only
after checking for external callers. Back up and explicitly migrate existing
archive data once. Do not drop columns automatically during page requests.

### High: Multi-Table Writes Are Not Atomic and Deletes Leave References

Source: [api/assets.php](../api/assets.php), create/update/delete routes;
[api/fields.php](../api/fields.php), delete route.

An ID rename updates assets, audit references, custom values, and both link
columns independently. A failure halfway leaves mismatched references. Renames
also do not re-normalize link-pair ordering. Asset deletion records an audit
event and removes the asset, but does not remove its custom values or links.
The links JOIN hides orphaned rows rather than raising an error; a later reuse
of an ID can unexpectedly associate old values/links with a new asset.

Recommendation: use transactions for dependent writes, remove dependent values
and links on deletion, and normalize link pairs after renames. Preserve audit
history, including deleted-asset events; do not cascade-delete audit records.
Test rollback on an injected failure and delete/recreate cases on staging.

### Medium: ID Allocation Still Has a Cross-Session Race

Source: `nextId` in [api/assets.php](../api/assets.php) and
[api/intune.php](../api/intune.php).

Both read IDs and then insert without a shared allocator lock or collision
retry. Different signed-in sessions can obtain the same next ID. Fixing retired
ID reuse does not fix concurrent creates. Intune and manual creates need the
same allocation protocol.

Recommendation: shared transactional counters or a scoped allocator lock with
duplicate-key retry, plus a concurrent-create integration test. Preserve all
existing identifiers and explicitly chosen custom IDs.

### Medium: Intune Re-import and Audit Gaps

Source: [api/intune.php](../api/intune.php), import route;
[import.php](../import.php).

Intune IDs are returned to the client but never persisted; duplicate detection
uses only serials. A device with a blank or changed serial can be imported again.
Intune enrollment dates are stored as purchase dates, affecting EOL and value
calculations. Neither importer creates the same audit events as manual creation.

Recommendation: persist a unique Intune device identifier, separate enrollment
from purchase dates, audit imports, and test repeated/partial imports.

### Medium: One-Time Import Is a Public GET-Triggered Write Utility

Source: [import.php](../import.php).

`?run=1` writes data using a navigable GET request. SameSite=Lax is not a defense
against cross-site top-level GET navigation. It also directly renders import
values/error text without consistent escaping and does not validate dates like
the asset API. The referenced import_data.json would contain inventory data
inside the web root if deployed as instructed in the utility's comment.

Recommendation: remove the utility from production after use, or replace it
with an authenticated administrative POST/CLI import and keep input outside
the web root. Add explicit CSRF protection to browser-driven mutations. Do not
assume every current JSON endpoint has a demonstrated cross-site exploit:
SameSite cookies and preflight restrictions are relevant mitigations.

### Medium: Input Validation and Error Responses Are Inconsistent

Sources: [api/assets.php](../api/assets.php), [api/fields.php](../api/fields.php),
[api/links.php](../api/links.php), [api/settings.php](../api/settings.php).

Malformed/non-object JSON, arrays where strings are expected, and overlong
values can cause TypeError/DB exceptions instead of controlled JSON errors.
Partial asset PUTs overwrite omitted fields with defaults. Settings accept
unvalidated depreciation periods, including zero; links do not verify both
asset IDs exist; custom values do not validate the asset/definition relationship.
Negative audit limit/offset values can produce invalid SQL.

Recommendation: validate body shape, ranges, lengths, asset existence, and
field ownership; define PUT versus PATCH semantics; return uniform JSON errors.
Validate the complete mutation before performing any writes.

### Medium: Audit CSV Still Allows Spreadsheet Formulas

Source: [api/assets.php](../api/assets.php), audit CSV branch.

CSV quoting does not prevent formula interpretation by spreadsheets. The audit
export sends user-controlled asset names, actors, and changed text directly to
fputcsv. Frontend exports were fixed; ADP already has its own protection.

Recommendation: share a server-side spreadsheet-safe CSV cell helper across
audit and ADP exports, and test formula prefixes, leading controls, quotes, and
ordinary numeric/text fields in the receiving spreadsheet workflow.

### Medium: EOL Date Boundaries Differ Between Views

Source: [index.php](../index.php), `eolStatus`, reports/CSV annotations, and
`autoSetEOL`; [api/assets.php](../api/assets.php), EOL filter.

Cards classify critical as within 90 days; reports use 180 days, while the
dashboard text describes critical assets as already overdue. Date-only values
are parsed through UTC Date strings and compared with local midnight; near
boundaries and leap-day purchase dates this can be inconsistent.

Recommendation: define overdue/critical/warning separately, choose a single
business threshold policy, and test the same dates across cards, reports, SQL
filters, and CSV in positive and negative timezones.

### Low: Scale, Session Lifetime, and Deployment Assumptions

Sources: [index.php](../index.php), [auth/auth.php](../auth/auth.php),
[api/assets.php](../api/assets.php).

- Full inventory downloads and per-asset custom-field requests are reasonable
  at the reported 210 assets but need pagination/batched values for larger use.
- PHP session locks stay held during slow integration requests, serializing
  requests from the same browser session. Consider releasing after auth reads.
- gc_maxlifetime is not a guaranteed application idle/absolute expiry. logged_in
  is saved but never checked. Define and enforce session expiry explicitly.
- Absolute redirects assume a domain-root install; documentation now states it.
- Forwarded audit IP headers are trustworthy only if the deployment sanitizes
  them and accepts them solely from known proxies. Verify the hosting chain.
- Stored API keys are no longer returned, but remain plaintext in DB backups
  and editable by any authenticated user. Prefer private server configuration
  and tightly restricted admin access; rotate if exposure is suspected.

## Verification and Limits

Local checks: Node tests for API error handling, EOL removal, inline-script
parsing, AI rendering, settings initialization, argument encoding, CSV behavior,
and export handler wiring. PHP tests execute isolated production functions for
ID allocation, secret masking, acknowledgement input, and AI shape validation.
All PHP files are syntax-checked.

These checks use mocks rather than production services. The type-scoped custom
field DELETE statement and remaining transaction/migration recommendations need
database integration testing against the deployed MySQL/MariaDB version.

Recommended staging checks before deployment:

1. Add/edit/save an asset and verify EOL warnings without acknowledgement UI.
2. Open QR/user cards containing quotes in names and serials.
3. Download both asset and report CSV; verify the correct columns and zeros.
4. Verify GET settings contains a configured flag and no stored key.
5. Create identical custom field labels for Laptop and Monitor; delete one and
   confirm values for the other remain.
6. Retire the highest-numbered asset and create a new one; verify a new ID.
7. Try valid/malformed AI estimates and repeatedly open custom-field settings.

The earlier ERR_BLOCKED_BY_CLIENT screenshot identifies client-side request
blocking; it is not evidence of a database or tenant permission failure. These
cleanup changes do not disable or bypass browser/endpoint security filtering.