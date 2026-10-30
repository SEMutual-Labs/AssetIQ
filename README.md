# AssetIQ — IT Asset Manager
### Cloudways Deployment Guide

---

## Files in this package

```
assetiq/
├── index.php           ← Authenticated main app
├── db.php              ← DB connection + auto schema installer
├── api/
│   └── *.php           ← Assets, fields, links, settings, exports, integrations
├── auth/
│   └── *.php           ← Microsoft sign-in and session helpers
└── README.md           ← This file
```

---

## Step-by-Step Cloudways Setup

### 1. Create a MySQL Database
1. Log into your Cloudways dashboard
2. Go to your **Application → Database**
3. Note down:
   - Database name
   - Database username
   - Database password
   - Host (usually `localhost`)

### 2. Configure the Private Settings File
Create `private_html/config.php` as a sibling of `public_html`, outside the public
web root. Both the database and authentication helpers expect this exact layout.
Never commit this file or place credentials in `public_html`.

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_username');
define('DB_PASS', 'your_database_password');
```

Authentication also requires `APP_URL`, `INTUNE_TENANT_ID`, `INTUNE_CLIENT_ID`,
and `INTUNE_CLIENT_SECRET`. Set `APP_URL` to the canonical HTTPS origin without
a trailing slash, and configure the sign-in callback as
`APP_URL + /auth/callback.php`. Use a separate random `APP_SECRET` for app state
signing. The optional `ANTHROPIC_API_KEY` config value takes precedence over a
key saved in Settings.

Required PHP extensions include PDO MySQL and cURL. The current migration SQL
uses `ADD COLUMN IF NOT EXISTS`; verify support on your database engine/version
before deployment, rather than assuming compatibility with every MySQL server.

### 3. Upload Files
Upload the entire `assetiq/` folder to your Cloudways app's
**public_html** directory using:
- Cloudways **File Manager**, or
- **SFTP** (Cloudways → Application → Access Details → SFTP)

Your files should be at:
```
application/
├── private_html/
│   └── config.php
└── public_html/
  ├── index.php
  ├── db.php
  ├── api/
  └── auth/
```

Deploy at the domain root. Subdirectory deployments are not currently supported
by the absolute auth redirects and private-config paths. Do not upload the
`tests` or `docs` folders, or the one-time `import.php` utility, unless needed.

### 4. Set File Permissions (via SSH or File Manager)
```
chmod 644 db.php index.php api/*.php auth/*.php
chmod 755 api/ auth/
```

Restrict `private_html/config.php` to the deployment owner and PHP service account
using your host's permissions model. It must remain unreadable over HTTP.

### 5. Visit Your App
Navigate to your domain — the database table will be created
automatically on first load (no manual SQL needed).

---

## Keeping It Private (Internal Use)

Since you're on an internal network, the simplest approach is
to restrict access by IP at the server level.

### Option A — Cloudways Varnish/IP Whitelist
In Cloudways dashboard: **Application → Security → IP Whitelist**
Add your office/VPN IP range.

### Option B — Apache .htaccess (add to public_html/)
Create a `.htaccess` file in the same folder as `index.php`:

```apache
Order Deny,Allow
Deny from all
Allow from 192.168.1.0/24    # your office LAN
Allow from YOUR.VPN.IP.HERE
```

### Option C — Basic HTTP Auth (quick password gate)
```apache
AuthType Basic
AuthName "AssetIQ"
AuthUserFile /path/to/.htpasswd
Require valid-user
```
Generate a .htpasswd file at: https://www.web2generators.com/apache-tools/htpasswd-generator

---

## Mobile QR Scanning
- Open the app URL in any mobile browser (Chrome, Safari)
- Tap **Scan QR** in the nav
- Tap **Start Camera** and allow camera access
- Point at any printed QR label — it will instantly pull up the asset

## QR Labels
- On any asset row, click **QR** to generate its code
- Click **Download PNG** to save, or **Print Label** to print directly
- Print and stick to the physical device

---

## SharePoint Asset Register Write-back

Settings includes **Preview SharePoint Changes** and **Apply Selected Changes**
for the existing Asset Register list at
`https://semutual.sharepoint.com/sites/AssetManagement/Lists/Asset%20Register/AllItems.aspx`.
This is manual, one-way synchronization that updates matching items and can create
missing items after explicit selection and confirmation. It does not replace MySQL,
import SharePoint changes, or delete SharePoint items.

### Server configuration and permissions

In the private settings file outside the public web root, configure:

```php
define('SHAREPOINT_TENANT_ID', 'your-tenant-id');
define('SHAREPOINT_CLIENT_ID', 'your-application-id');
define('SHAREPOINT_CLIENT_SECRET', 'your-application-secret');
// Optional: the list GUID avoids discovering the list by display name.
define('SHAREPOINT_LIST_ID', 'your-list-guid');
```

Use an Entra application with Microsoft Graph **application** permissions.
Prefer `Sites.Selected`, administrator consent, and an explicit `write` grant
on the AssetManagement site. Consent alone does not grant access. An
administrator must provision the resource grant separately; AssetIQ does not
give itself permissions. A separate SharePoint application is recommended to
keep access independent of Intune. Reusing an existing app requires explicitly
configuring these constants and granting SharePoint permissions; Intune
permissions and a user's Microsoft sign-in are not sufficient.

Keep credentials server-side, rotate expiring secrets, and never paste them into
chat, JavaScript, or the public web root. This Cloudways-hosted PHP integration
uses client credentials, consistent with the existing hosting; it does not
assume an Azure managed identity is available. All signed-in AssetIQ users can
use this feature, consistent with existing inventory-edit access; there is no
new admin-only role.

### Translation rules

| AssetIQ | SharePoint | Behavior |
|---|---|---|
| Asset ID | Asset ID | Match existing items case-insensitively with surrounding whitespace ignored; set on new items only |
| Type | Asset Type | Match the live list's allowed choices |
| Serial | Serial Number | Text |
| Status | Status | `active` with an owner → `Active`; without an owner → `Unassigned`; `retired` → `Retired` |
| Name | Make/Model | Text; verify this mapping in the preview |
| Assigned To | Current Owner | Text, not a Person lookup |
| Department | Department | Match the live list's allowed choices |
| Purchase Date | Purchase Date | ISO date |
| Cost | Purchase Price | Numeric; no currency conversion |
| Notes | Notes | Plain text |
| Successful sync time | Last Updated | UTC timestamp, only when applying actual changes |

Internal column names and choice values are discovered through Graph; they are
not inferred from spaces in display names. Missing, ambiguous, read-only,
rich-text, or unsupported mapped columns fail explicitly. Invalid choices,
overlong text, or invalid dates/prices block the entire affected row.

If a display name differs from what Graph returns, an explicit internal-name
mapping can be configured in the private settings file. For this Asset Register,
the Asset ID column-edit URL contains `Field=Title`, confirming that its internal
name is `Title`. Configure:

```php
define('SHAREPOINT_COLUMN_MAP', [
    'id' => 'Title',
]);
```

This requires the updated SharePoint API files that support `SHAREPOINT_COLUMN_MAP`.
It does not rename SharePoint columns. Other mappings still use discovered display
names unless explicitly overridden. Verify the configured list GUID is
`f7197dfe-ed51-4844-8af0-c1f891e42b9b` if `SHAREPOINT_LIST_ID` is set; this identifies
the list from the supplied column-edit URL. A renamed Title column alone does not
explain a display-name mismatch, so also confirm that the configured list is correct.
Overrides use AssetIQ field keys (such as `id`, `serial`, or `assigned_to`) and exact
SharePoint internal names, never guessed encoded labels. Invalid, missing, duplicate,
or incompatible mapping targets fail explicitly.

SharePoint statuses such as **In storage**, **Lost**, and **repair** are preserved,
even when AssetIQ says active or retired. Empty AssetIQ values preserve existing
SharePoint values and appear as warnings; this version does not clear fields.
Calculated columns, Location, Previous Owner, Condition Notes, Color, Order
Number, Invoice Number, Days Owned, and other unmapped columns are not written.
SharePoint still manages Modified and Modified By and recalculates its formulas.
Previous Owner is not automatically maintained.

**Active**, **Unassigned**, and **Retired** can transition between each other.
Blank or whitespace-only Assigned To values count as unassigned. This changes
Status but does not clear an existing Current Owner: the empty-field preservation
rule still applies, and the preview warns about the retained owner.

Choice labels are matched case-insensitively and written using the exact
SharePoint label. Nonmatching values require an explicit translation; they are
never guessed. If needed, add confirmed aliases in the private settings file:

```php
define('SHAREPOINT_VALUE_MAPS', [
    'type' => [
        // 'AssetIQ type label' => 'Exact allowed SharePoint type label',
    ],
    'department' => [
        // 'AssetIQ department label' => 'Exact allowed SharePoint department label',
    ],
]);
```

`No Longer At SEM` matches the same Department choice in SharePoint without an
alias; it is not automatically converted to `Not Assigned`.
Empty departments are preserved rather than assigned a default.

### Selecting assets

In the main Assets list, click a selection checkbox, then Shift-click another
checkbox to select the inclusive range in the current filtered/sorted order.
In selection mode, table rows and card headers also support Shift-click.
Shift-clicking a selected asset deselects the range instead. Card and table
selections stay in sync. Reloading, sorting, or filtering resets the range anchor
and removes selections no longer in the displayed results; Select All and Cancel
also reset the anchor. Normal row clicks outside selection mode still open editing.

### Preview, apply, and conflicts

1. Open Settings and generate a preview. This reads the list but makes no
   SharePoint writes.
2. Expand rows to inspect before/after values and warnings. Missing Asset IDs
   are labelled **NEW ITEM** with values to create; duplicate matches are blocked.
3. Deselect any ready updates you do not want. New items are **unchecked by
   default** and must be explicitly selected. Apply and confirm the separate
   update/create counts.
   The preview is stored server-side in the signed-in session for 15 minutes.
4. Each selected item is written separately, so partial success is possible.
   Per-item results and separate updated/created counts remain visible.
   Successful writes are recorded as **Synced to SharePoint** in AssetIQ's
   Activity Log, including the operation and SharePoint item ID.

Use **Select all ready (including new items)** or **Clear selection** for bulk
selection. Click a checkbox and then Shift-click another checkbox to apply the
second checkbox's checked/unchecked state to the inclusive range in preview order.
Blocked and unchanged rows are skipped; a range can include new items. New items
still start unchecked on each fresh preview. The selected update/create counts
are shown before confirmation, and selection is locked during sync.

While loading a preview, an indeterminate indicator is shown. During apply, a
progress bar shows completed requests out of selected items, percentage, current
Asset ID, and updated/created/failed counts. Failed requests count as processed,
not successful. Progress advances after each response; it does not estimate
completion time or progress inside a single Microsoft request. If sign-in expires,
the sync stops and reports unattempted items without showing a false 100%.

### Creating missing items

The existing site `write` grant also allows creation; no new application permission
or private configuration setting is needed. The current column map, including
`id => Title`, and configured choice translations apply to both operations.

Creation uses the live list's required-column metadata. For this Asset Register,
Asset Type, Serial Number, Status, Purchase Date, Purchase Price, and Last Updated
are required. The sync supplies Last Updated, and Asset ID is always required by
the integration even if SharePoint marks it optional. Missing user-supplied values
block the new item and are listed together so they can be corrected in AssetIQ.
Zero is a valid price; blank/null is not. No invented serials, dates, prices,
default choices, or placeholder values are supplied.

An unmapped required writable column also blocks creation and is identified as
having no AssetIQ mapping; it requires a supported mapping/schema decision rather
than guessing a value. Server-managed and calculated columns are left to SharePoint.
Empty optional values are omitted with warnings, so SharePoint's configured defaults
may apply. Existing-item updates still preserve empty AssetIQ values as before.

Before creating, the server revalidates the current schema/translation and rechecks
all list pages for the Asset ID. If an item has appeared since preview, the operation
is rejected; it does not silently switch to updating it. A MySQL advisory lock
serializes creates for the same normalized Asset ID across AssetIQ sessions using
the same database. Lock failure blocks creation explicitly.

These safeguards are not an atomic SharePoint upsert. For a strict uniqueness
guarantee across external editors, other applications, or separate AssetIQ databases,
have a SharePoint administrator enable **Enforce unique values** on the Asset ID
column after checking existing duplicates. Without that constraint, an external
writer could still create the same ID between the final check and POST. AssetIQ
never automatically retries a create with an unknown outcome; preview again to
check whether it succeeded before attempting another write.

For updates, the server checks the local asset snapshot and uses SharePoint's `If-Match`
ETag to reject records changed since preview. Creates also check the local snapshot.
A request cannot provide arbitrary
field values or item IDs: only the stored preview can be applied. A new preview
replaces the old one; reloads, session expiry, or another tab generating a preview
may invalidate it.

Writes are not automatically retried, including throttling and timeouts, because
an interrupted request may already have reached SharePoint. Wait when throttled,
then generate a fresh preview to see what remains. An attempted row cannot be
replayed from the same preview: consumption is saved before remote writes.
An audit failure after a successful remote write
is explicitly reported as such, not as a safely retryable operation.

PHP cURL and outbound HTTPS to Microsoft login and Graph are required. No extra
Composer/npm dependencies or database migrations are needed. Collection reads
are paginated, with an explicit failure after 200 pages rather than a partial
preview.

References:
[Update list item fields and conditional writes](https://learn.microsoft.com/en-us/graph/api/listitem-update?view=graph-rest-1.0),
[Create a list item](https://learn.microsoft.com/en-us/graph/api/listitem-create?view=graph-rest-1.0),
[Selected permissions and resource grants](https://learn.microsoft.com/en-us/graph/permissions-selected-overview),
[Column definitions](https://learn.microsoft.com/en-us/graph/api/resources/columndefinition?view=graph-rest-1.0).

Focused local checks:

```powershell
php tests\sharepoint.test.php
node --test tests\sharepoint.test.cjs tests\api-fetch.test.cjs
```

These checks use fixtures and do not write to the real SharePoint list. A live
preview and an administrator-approved test update are still needed after server
configuration.

## Troubleshooting

| Problem | Fix |
|---|---|
| Blank page / DB error | Check config.php credentials |
| API returns 500 | Check PHP error logs in Cloudways → Application → Logs |
| Camera won't start on mobile | Site must be served over **HTTPS** (Cloudways SSL is free — enable it) |
| QR scan doesn't find asset | Make sure you're scanning a QR generated by THIS installation |

### One User Gets "Failed to Fetch"

This message means the browser could not provide a response to JavaScript. It is
different from an HTTP 401 (missing session) or HTTP 500 (server/database error).
It does not, by itself, identify an account permission problem.

1. On the affected computer, open Developer Tools (F12), select **Network**, and
    reload the page. Inspect the failed `api/assets.php` request and any red errors
    in **Console**. Record the request URL, status or browser error, and time.
    Do not share cookies, authorization headers, or tokens.
2. While signed in, open `/api/assets.php?stats=1` directly on the same app origin.
    JSON stats show that endpoint is reachable through navigation; they do not
    prove JavaScript requests are allowed. `Unauthorized` means the session is
    missing. An HTTP 500 requires checking the application/PHP logs. A browser
    connection or certificate error points to the connection or HTTPS setup.
3. Try the same account in an InPrivate/Incognito window. If that works, investigate
    extensions and site data in the normal profile. Private mode may still allow
    explicitly enabled extensions. Sign out and clear only this site's data if
    needed, then sign back in.
4. Compare the failing browser error with these next checks:
    - `ERR_BLOCKED_BY_CLIENT`: check extensions or managed browser filtering.
    - Connection reset, DNS, or certificate errors: check VPN/proxy/security
      filtering and the site's HTTPS certificate with IT. Do not bypass TLS checks.
    - CORS or CSP errors: inspect redirects and the final request origin. API calls
      are same-origin and the app permits connections only to its own origin.
      Check proxy/login redirects before changing CORS or security policy.
    - HTTP 401: sign in again; the main page's shared API helper now redirects to
      sign-in when it receives this response.
    - HTTP 500: correlate the request time with Cloudways application/PHP logs.

The shared API helper reports network failures as "Unable to reach the AssetIQ
API" and logs the original browser exception in Console. It does not automatically
retry requests, so interrupted writes are not silently submitted again.

---

## Tech Stack
- **Frontend**: Vanilla HTML/CSS/JS, no framework
- **Backend**: PHP 8+ with PDO
- **Database**: MySQL (auto-creates `assets` table on first run)
- **QR Generation**: qrcodejs (CDN)
- **QR Scanning**: html5-qrcode (CDN, loaded on demand)

## Review and Local Checks

### Dashboard and Navigation

Desktop inventory and Recent Assets rows open the asset editor when clicked, or
when focused and activated with Enter/Space. QR/Edit/Delete buttons retain their
own actions; batch selection remains separate. The dashboard's Past End of Life
box sits beside Total Value and opens the overdue list. It counts active,
non-archived assets with EOL dates before today, not upcoming replacements.

Intune navigation/settings and ADP export controls have been removed from the
interface. Their backend endpoints remain for compatibility with possible
external callers; Microsoft sign-in configuration is unchanged. Normal asset
and report CSV exports remain available.

### Faster Asset Entry

The add-asset model picker searches the full inventory, including retired assets,
rather than only the current filtered list. Enter a model-number fragment, name,
asset ID, serial, or a model code recorded in notes. Punctuation and case are
ignored when matching. This is an inventory lookup, not an external product
catalog: unknown models can still be entered manually.

Select a suggestion or use **Copy Existing** to reuse name, type, cost, and notes.
The existing device's serial, ID, assignee, department, purchase/EOL dates, status,
links, and custom dates are not copied. Set device-specific details for the new
asset as needed.

For a new Laptop, enable **Multiple Laptops** and enter one serial per line, up
to 100. All laptops use the shared form values, including cost per laptop and
any custom dates entered for this batch. IDs are generated separately. Repeated
serials in the input and serials already recorded in inventory are rejected.
Assets, audit events, and custom dates are saved in one database transaction:
an insertion failure rolls back the whole batch. This does not replace the
cross-session allocation/concurrency follow-up noted in the code review.

Deploy the updated page and asset API together. No new database columns are
required for these entry features.

### Serial Barcode Capture

Serial and asset QR cameras are available only in the mobile layout (viewport
width below 768px). Desktop camera controls are hidden and start calls are
blocked. Switching to desktop width stops active cameras. This follows the UI
breakpoint, not device detection; a narrow desktop window uses the mobile UI.
Manual serial entry remains available in both layouts.

The serial scanner uses html5-qrcode's cropped decoding region, outlined by a
stationary target. The dashed center line is only an alignment guide, not an
individual sampling point. Default Linear Barcode mode excludes square QR/Data
Matrix codes; select 2D Code when the printed serial uses those formats.

Three matching reads produce a paused candidate for review. The serial input is
not changed until **Use Serial** is selected. **Scan Again** discards the candidate
and resumes the camera. This reduces accidental reads but cannot determine
whether the captured barcode represents a serial, SKU, or other identifier.
Compare it with the human-readable serial printed on the machine.

The help control supports hover tooltips and tap/keyboard expansion. Camera
permission, missing/in-use cameras, unsupported constraints, and library-load
failures have separate feedback. A no-read hint lists focus/glare/margin checks;
the decoder does not diagnose blur or glare. Continuous focus is requested only
when supported, and a zoom slider appears only when reported by the camera.

Local tests cover crop dimensions, matching reads, confirmation, and camera
cleanup while startup is pending. Desktop/mobile browser checks use a simulated
camera. Real-world accuracy still requires HTTPS-device testing with the dense
labels, including neighboring barcodes, glossy surfaces, rotated codes, camera
permission denial, and stop/reopen behavior. Zoom/focus support varies by device.

### Tests

See [docs/CODE_REVIEW.md](docs/CODE_REVIEW.md) for completed cleanup and prioritized
remaining findings, including authentication and database migration concerns.

```powershell
node --test tests/*.test.cjs
php tests/backend.test.php
php tests/laptop-batch.test.php
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

These checks do not require production secrets or a database. They do not replace
authenticated staging tests against the actual database engine.

For a local mock-data UI preview, run `node tests/preview.cjs --fixtures` and open
the loopback URL printed by the command. This strips PHP and uses only in-memory
fake inventory; it does not test real authentication or SQL and must never be
deployed as an application server. Without `--fixtures`, APIs deliberately return
503. Stop the process when finished.
