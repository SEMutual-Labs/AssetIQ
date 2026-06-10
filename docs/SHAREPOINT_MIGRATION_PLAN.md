# AssetIQ → SharePoint Migration Plan

**Goal:** Rebuild AssetIQ as a SharePoint-native solution using Microsoft tools already included in our Microsoft 365 subscription, so we no longer depend on third-party hosting (Cloudways), a PHP application, or a MySQL database.

**Audience:** This document assumes zero prior experience with SharePoint, Power Apps, or Power Automate. It is written so it can be handed to anyone — a manager, a new hire, or a consultant — and they will understand what we are doing, why, and how.

---

## 1. Executive Summary

| Question | Answer |
|---|---|
| Can the current app be "published into" SharePoint? | No. SharePoint cannot run PHP or host MySQL. This is a **rebuild**, not a copy-paste. |
| What replaces it? | **SharePoint Lists** (the database) + a **Power Apps** canvas app (the screens) + **Power Automate** flows (the automation), embedded in our SharePoint site. |
| Does our data survive? | Yes. AssetIQ already exports everything to CSV, and SharePoint imports CSV directly. Asset history carries over too. |
| What does it cost? | $0 in new licenses if we stay on "standard connectors" (explained in §5). Roughly $5–$20/user/month **only** if we want premium features like automatic Intune sync. |
| How long? | Realistically **6–8 weeks part-time** for someone learning as they go; 2–3 weeks for someone experienced. |
| What do we lose? | The custom dark-themed UI, one-click AI price estimates (third-party AI service — likely dropped under the new policy anyway), and in-browser desktop QR scanning (mobile scanning still works, and existing printed labels stay usable). |
| What do we gain? | No servers, no PHP, no database to maintain, Microsoft sign-in for free, built-in version history on every record, automatic backups, and everything inside the tenant our security policies already govern. |

---

## 2. The Building Blocks, in Plain English

Before the plan makes sense, you need to know what four Microsoft tools are. Think of it like this:

### SharePoint Lists — "the filing cabinet"
A **List** is like an Excel spreadsheet that lives on our SharePoint site, except it behaves like a real database table: every row is a record, every column has a type (date, choice, currency, person), multiple people can use it at once, and **every change to every row is automatically versioned** (who changed it, when, and what the old value was). Our MySQL tables become Lists.

### Power Apps — "the screens"
**Power Apps** is Microsoft's tool for building applications without traditional programming. You drag buttons, galleries (scrollable lists), and forms onto a canvas, then wire them to your SharePoint Lists with short Excel-style formulas. The result runs in any browser and in the Power Apps mobile app on phones. Everything the AssetIQ web pages do today — dashboard, asset list, add/edit form, retired list, audit viewer — becomes screens in one Power App.

### Power Automate — "the robot assistant"
**Power Automate** runs automated workflows ("flows") triggered by events or schedules. Examples: *"When an asset record changes, write an entry to the audit log list"* or *"Every Monday at 8am, check unassigned laptop count and email IT if it's below the threshold."* This replaces the alerting and audit-writing code in the PHP backend.

### Microsoft Graph — "the connector to everything Microsoft"
**Graph** is the API that exposes Microsoft 365 data — including **Intune device records**. Our current Intune sync feature calls Graph from PHP; in the new world, a Power Automate flow calls it instead (this is the one feature that needs a premium license — see §5 — and there is a free manual workaround).

### How they fit together

```
Our SharePoint Site (e.g., "IT Asset Management")
│
├── SharePoint Lists  ──────────  the data (replaces MySQL)
│     ├── Assets
│     ├── Asset Audit Log
│     ├── Asset Links
│     └── App Settings
│
├── Power App ("AssetIQ")  ─────  the screens (replaces index.php)
│     embedded in a SharePoint page, also usable on phones
│
└── Power Automate flows  ──────  the automation (replaces api/*.php logic)
      ├── Audit logger
      ├── Low-stock alert (weekly)
      ├── End-of-life digest (monthly)
      └── Intune sync (optional / premium)
```

No piece of this runs outside our Microsoft 365 tenant. There is nothing to host, patch, or back up ourselves.

---

## 3. Feature-by-Feature Map: Today → SharePoint

Every current AssetIQ feature, and exactly where it lands:

| Current feature (PHP app) | SharePoint equivalent | Effort | Notes |
|---|---|---|---|
| Login via Cloudways OAuth | **Gone — free.** Users are already signed into Microsoft 365 | None | Big win. Access controlled by SharePoint site permissions. |
| Assets database (MySQL) | **Assets** list | Low | Schema in §6. |
| Dashboard stat cards (total, unassigned, retired, value) | Power Apps home screen with formula-driven tiles | Low | |
| Asset list with search / filter / sort, card + table views | Power Apps gallery with search box and filter dropdowns | Medium | One responsive layout instead of two views. |
| Add/Edit modal | Power Apps form screen | Medium | |
| Auto asset numbering (SEM-NB01, SEM-PC01…) | Formula in the app on save (find highest existing number for the prefix, add 1) | Low | Same logic as `nextId()` in `api/assets.php`. |
| Serial duplicate warning | Formula check on the form | Low | |
| Status Active/Retired + disposed date | Choice column + date column; form shows Disposed Date only when Retired (same behavior we just built) | Low | |
| EOL auto-calc (+6 years) and warnings | Formula on the form; conditional formatting in the gallery | Low | |
| Multi-select batch retire | Checkbox selection in the gallery + "Retire selected" button | Medium | |
| Audit trail (who/what/when, before→after values) | **Two layers:** built-in list version history (automatic, tamper-resistant) + an **Asset Audit Log** list written by a flow for a friendly, filterable, exportable view | Medium | Arguably *stronger* than today — version history cannot be edited even by the app. IP logging is replaced by Microsoft's own sign-in/audit logs (Purview), which are more authoritative. |
| Audit CSV export | Built-in "Export to Excel/CSV" on the list | None | |
| Asset links (linked assets) | **Asset Links** list using lookup columns | Low | |
| Custom fields per asset type | Just add columns to the Assets list; the form shows/hides them by type | Low | *Simpler* than today's dynamic system. Adding a field becomes "add a column," no code. |
| QR/barcode **scanning** to look up an asset | Power Apps **Barcode reader** control | Low | Works in the Power Apps **mobile app** (phone camera). Does **not** work from a desktop browser webcam — in practice scanning was a phone activity anyway. Existing printed labels keep working (see §8.3). |
| QR label **generation/printing** | Decision needed — see §8.3 | Medium | No fully native generator; honest options listed. |
| AI price estimation (Anthropic API) | **Recommend dropping** under the "no third-party" policy; Azure OpenAI via premium connector is the all-Microsoft alternative if the feature must survive | — | Decision point for management. |
| Low-stock alerts by type | Power Automate weekly flow → email or Teams message | Low | Thresholds stored in the **App Settings** list. |
| Reports (cost, depreciation, EOL forecast) | Power Apps report screen, or Power BI later if appetite grows | Medium | Start simple: totals and EOL timeline in the app; lists export to Excel for anything fancier. |
| Intune device import | Power Automate flow calling Graph (**premium**, ~$5–20/user/mo) **or** free manual route: Intune admin center → export devices CSV → paste into the list | Low–Med | Manual route costs nothing and takes ~5 minutes a month. |
| ADP export | Built-in list export to Excel/CSV, or a small flow that formats it | Low | |
| Users page (assets grouped by person) | Gallery grouped by Assigned To | Low | Upgrade: Assigned To becomes a real **Person** column tied to the company directory — no more typos in names. |

---

## 4. What We Gain / What We Lose (the honest version)

**Gains**
- **Zero infrastructure.** No Cloudways bill, no PHP upgrades, no MySQL backups, no SSL certificates, no `config.php` secrets on a server.
- **Security & compliance for free.** Access via Microsoft sign-in (with MFA, conditional access — whatever IT already enforces). All activity additionally lands in Microsoft 365's own audit system (Purview), which is stronger evidence than our self-written IP logging.
- **Version history on every record, automatically** — even if our custom audit flow ever failed, the underlying history is still there and cannot be tampered with from the app.
- **Person-type columns** — "Assigned To" links to real directory accounts; when someone leaves, their assets are one filter away.
- **Anyone can maintain it.** Adding a column or tweaking an alert doesn't require a developer or a deployment.

**Losses / compromises**
- The polished custom UI becomes a more utilitarian Power Apps UI. Functional, branded with our colors, but not pixel-identical.
- Desktop-browser camera scanning goes away (phone scanning remains, via the Power Apps mobile app).
- AI price estimates: dropped or rebuilt on Azure (management decision).
- QR label *generation* needs one of the workarounds in §8.3.
- Power Apps has a learning curve measured in days, not months — but it is a curve.

---

## 5. Licensing & Cost (read this before building)

This is the most common place SharePoint projects get surprised, so here it is up front:

- **Included with almost every Microsoft 365 business/enterprise plan** (Business Standard/Premium, E1/E3/E5): SharePoint Lists, Power Apps using **standard connectors** (SharePoint, Outlook, Teams), and Power Automate standard flows. **Everything in this plan except one item fits in this free tier.**
- **Premium connectors** (the HTTP connector needed to call Microsoft Graph for **automatic Intune sync**, or Azure OpenAI for AI estimates) require a paid Power Platform license: about **$5/user/month** (per-app) or **$20/user/month** (full), or a single per-flow license (~$100/month) that covers everyone. Only the person/account *owning* the flow strictly needs it for a scheduled flow.
- **Free workaround for Intune:** the Intune admin center exports all managed devices to CSV in two clicks. A monthly 5-minute manual import keeps us at $0.

**Action for Phase 0:** confirm with whoever manages our Microsoft 365 tenant (a) which plan we're on, and (b) whether Power Apps is enabled for users. That's it.

---

## 6. The Data: SharePoint List Designs

These translate the current MySQL tables (`db.php`) one-for-one. Hand this section to whoever creates the lists.

### List 1: **Assets** (replaces the `assets` table)

| Column name | Type | Settings / notes |
|---|---|---|
| Title | Single line of text | Rename to **Asset Name**. Required. (e.g., "Dell XPS 15 9530") |
| AssetID | Single line of text | **Enforce unique values: Yes.** Our SEM-NB01 style numbers. |
| AssetType | Choice | Laptop, Desktop, Monitor, Peripheral, Docking Station, Printer, Camera |
| SerialNumber | Single line of text | Don't enforce unique (blanks are common); the app warns on duplicates like today. |
| AssignedTo | **Person** | Tied to the directory. (During migration, free-text names get matched to accounts; unmatched ones go in the Notes or a fallback text column.) |
| Department | Choice | Copy current department values. |
| Status | Choice | Active, Retired. Default: Active. |
| PurchaseDate | Date | |
| EndOfLife | Date | |
| DisposedDate | Date | Only filled when Status = Retired (the app enforces this, same as now). |
| Cost | Currency | |
| Notes | Multiple lines of text | Plain text mode. |
| EOLOverride | Yes/No | Default No ("Acknowledge EOL warning" checkbox today). |
| *(custom fields)* | Date (or as needed) | One column per current custom field, e.g. WarrantyExpiry. App shows them per type. |

> Created / Created By / Modified / Modified By columns exist automatically — they replace `created_at`/`updated_at`.
>
> **Turn on:** List settings → Versioning settings → "Create a version each time you edit an item." This single checkbox is the foundation of the audit story.

### List 2: **Asset Audit Log** (replaces `asset_logs`)

| Column name | Type | Notes |
|---|---|---|
| Title | Single line of text | Human-readable summary, e.g. "Retired — Dell XPS 15 (SEM-NB07)" |
| AssetID | Single line of text | |
| AssetName | Single line of text | Kept even if the asset is later deleted. |
| Action | Choice | Created, Updated, Retired, Deleted, Imported, Link Added, Link Removed, Settings Changed |
| ChangedFields | Multiple lines of text | "Status: active → retired; DisposedDate: — → 2026-06-10" |
| PerformedBy | Person | |

> **Permissions:** set this list so regular users have **read-only** access; only the flow's account writes to it. That makes the trail tamper-resistant. (How-to in Phase 5.)
> The old PHP log's IP-address column is superseded by Microsoft's sign-in logs.

### List 3: **Asset Links** (replaces `asset_links`)

| Column name | Type |
|---|---|
| AssetA | Lookup → Assets (shows AssetID) |
| AssetB | Lookup → Assets (shows AssetID) |
| Note | Single line of text |

### List 4: **App Settings** (replaces `settings`)

| Column name | Type | Example rows |
|---|---|---|
| Title | Single line of text | threshold_laptop, threshold_monitor, alerts_enabled, depreciation_years |
| Value | Single line of text | "2", "1", "yes", "5" |

> The `anthropic_api_key` setting does **not** migrate — secrets never go in a list.

---

## 7. The Build Plan, Phase by Phase

Each phase says *what*, *how*, *who*, and *how long*. Phases are sequential; nothing here requires touching the old app until the very end, so **AssetIQ keeps running untouched throughout**.

### Phase 0 — Decisions & prep (1 week, mostly waiting on answers)
1. **Confirm licensing** (§5) with the M365 admin.
2. **Management decisions** (use the checklist in §9): AI estimates — drop, rebuild on Azure, or defer? Intune sync — automatic (premium $) or manual CSV (free)? QR labels — which option from §8.3?
3. **Pick the team:** one builder (the person learning Power Apps), one IT/M365 admin for site creation and permissions, one or two pilot users from the team that uses AssetIQ daily.
4. **Builder training** (~1 day): Microsoft Learn's free modules — "Get started with Power Apps canvas apps" and "Introduction to SharePoint lists." That genuinely is enough to start.

### Phase 1 — Create the site and lists (2–3 days)
1. M365 admin creates a SharePoint **Team site** named e.g. *IT Asset Management*. (SharePoint home → "+ Create site" → Team site.)
2. Create the four lists from §6. (Site → New → List → add columns one at a time. Tedious but simple — ~an hour.)
3. Turn on versioning on the Assets list (one checkbox, see §6).
4. Add 3–4 fake assets by hand and confirm columns behave (choices, dates, person picker).

### Phase 2 — Migrate the data (1–2 days)
1. In current AssetIQ: **Reports → Export CSV** (all assets) and **Audit Trail → Export CSV**.
2. Clean the asset CSV in Excel: one column per list column, match the §6 names, fix obvious typos in names/departments. This is the moment to standardize "Assigned To" values to real employee names.
3. Import: on the Assets list, use **Edit in grid view** and paste from Excel (most reliable for a few hundred rows), or SharePoint's "Create a list from Excel" into a temp list first.
4. Import the audit CSV into the Asset Audit Log list the same way — history continuity from day one.
5. Spot-check 10 random assets against the old app.

### Phase 3 — Build the Power App (2–3 weeks, the main effort)
Build one screen at a time, in this order (each is a self-contained win):

1. **Asset list screen** — gallery bound to the Assets list; search box; Type/Status/Department dropdown filters; tap to open detail. *(This alone replaces 70% of daily usage.)*
2. **Asset form screen** — new/edit form. Add the logic: auto-generate AssetID from type prefix; auto-fill EOL = purchase date + 6 years; show DisposedDate only when Status = Retired (auto-fill today); warn on duplicate serial.
3. **Dashboard screen** — count tiles (Total/Unassigned/Retired/Total value) with formulas like `CountRows(Filter(Assets, Status.Value = "Active"))`; tiles navigate to the pre-filtered list.
4. **Retired view** — the list screen filtered to Retired, **with the Disposed Date column visible** (matching what we just shipped in the web app).
5. **Batch retire** — checkboxes in the gallery + "Retire selected" button that loops over selections with `Patch()`.
6. **Scan screen** — Barcode reader control; on scan, parse the payload and jump to the matching asset (§8.3 explains label compatibility).
7. **Audit log screen** — read-only gallery on the Asset Audit Log list with action/date/person filters.
8. **Links & settings screens** — linked-assets section on the detail screen; a simple admin screen editing the App Settings list.

Keep formulas **delegable** (Power Apps warns you with a blue underline) — at our asset count (<2,000) even non-delegable formulas work, but good habits cost nothing.

### Phase 4 — Automation flows (1 week)
1. **Audit logger:** trigger "When an item is created or modified" (Assets list) → "Get changes for an item" (this action returns exactly which columns changed since the previous version — that's our before/after) → compose a summary → "Create item" in Asset Audit Log.
2. **Low-stock alert:** scheduled weekly → count unassigned active assets per type → compare to thresholds in App Settings → post to a Teams channel or email IT.
3. **EOL digest:** scheduled monthly → assets with EndOfLife within 12 months and EOLOverride = No → email a table.
4. **Intune sync** *(only if premium was approved)*: scheduled daily → HTTP call to Graph `managedDevices` → create assets for unknown serials, tagging the audit entry "Imported." *(Free alternative: monthly manual CSV from the Intune admin center, pasted via grid view.)*
5. **Flow ownership:** create flows under a **service account** (e.g., svc-assetiq@…), not a personal account — flows die when their owner's account is disabled. Cheap insurance.

### Phase 5 — Embed, permissions, polish (2–3 days)
1. Publish the app and **embed it in the SharePoint site's home page** (edit page → add the **Power Apps web part** → paste the App ID). Staff visit the site, the app is just *there*. It also appears automatically in their Power Apps mobile app for scanning.
2. **Permissions:** share the app with the team; site members get **Edit** on Assets/Asset Links, **Read** on Asset Audit Log (list → settings → "Stop inheriting permissions" → set members to Read; the service account keeps Edit). App Settings: Edit for IT only.
3. Hide the raw lists from casual navigation so people use the app (they remain available to admins).

### Phase 6 — Pilot & parallel run (1–2 weeks)
1. Pilot users do their **real work in the new app** while the old app stays available read-only as reference.
2. Keep a shared "friction log" — every annoyance gets written down and fixed during this phase.
3. Verify the audit flow: make a change, confirm the log entry and version history both captured it.
4. Test scanning real printed labels with two different phones.

### Phase 7 — Cutover & decommission (1 day + 30-day safety net)
1. Announce a freeze on the old app; export a final CSV delta of anything changed since Phase 2 and paste it in.
2. Switch the team to the SharePoint page. Old app becomes read-only.
3. After 30 days of quiet: take a final full backup of the MySQL database (one `mysqldump`, archived to SharePoint), then **cancel the Cloudways hosting**. That cancellation is the moment the "minimize third-party apps" goal is actually achieved.

---

## 8. The Ins and Outs — Gotchas Worth Knowing in Advance

### 8.1 Audit: how "before → after" works without our PHP code
SharePoint versioning stores a full snapshot of every record on every edit, automatically. The audit *flow* (Phase 4) uses the "Get changes for an item" action to diff the latest two versions and writes a readable entry. If the flow is ever off, **the version history still has everything** — the friendly log can even be backfilled. This is a stronger guarantee than the current system, where a bug in `writeLog()` would mean the change was simply never recorded.

### 8.2 Deleting vs. retiring
Same philosophy we just implemented in the web app: the Power App's UI offers **Retire**, not Delete. True deletion stays possible only for list admins directly on the list — and even then SharePoint's Recycle Bin retains deleted items for 93 days. Accidental permanent loss becomes nearly impossible.

### 8.3 QR codes — the full story
- **Scanning (reading):** the Power Apps Barcode reader control reads QR codes and barcodes with the phone camera, in the Power Apps mobile app. Desktop-browser webcam scanning is not supported — confirm with the team that scanning is phone-based today (it effectively is).
- **Existing printed labels keep working.** Current labels encode JSON like `{"id":"SEM-NB01",...}`. The scan screen parses that payload and looks up the ID — no relabeling required.
- **Generating new labels** — pick one in Phase 0:
  1. **Word/Excel mail-merge with a QR field** (Word has a built-in QR barcode field). Fully offline, zero cost, prints sheets of labels from a list export. **Recommended.**
  2. A Power Automate flow that renders label documents from a template.
  3. An online QR generator — works, but it's a third-party web service; encode only the asset ID, never names, if used at all.

### 8.4 Person column vs. free text
Today "Assigned To" is typed text ("Jon Smith", "jon smith", "J. Smith" can be three different people). The Person column fixes that permanently, but the migration spreadsheet needs one pass where someone maps each name to a real account. Budget an hour and do it — it's the single best data-quality upgrade in this whole project.

### 8.5 Things that look scary but aren't
- **"5,000 item list view threshold"** warnings online: relevant for huge lists; irrelevant below a few thousand assets, and indexed columns (AssetID, Status) handle growth far beyond that.
- **Power Apps formula language:** it's Excel formulas with different function names. Anyone comfortable with Excel can do this.
- **"Citizen developer" horror stories:** mitigated by exactly what this plan does — service account ownership, documented lists, permissions set deliberately, and this document living on the site itself.

---

## 9. Decision Checklist for Management

| # | Decision | Options | Recommendation |
|---|---|---|---|
| 1 | Platform approach | Power Apps + Lists (low-code) vs. SPFx custom code | **Power Apps** — matches the "minimize third-party/custom infrastructure" directive and is maintainable by non-developers |
| 2 | AI price estimation | Drop / rebuild on Azure OpenAI (premium $) / keep manual entry | **Drop for v1**; revisit if missed |
| 3 | Intune sync | Automatic via Graph (premium license, ~$5–20/user/mo or ~$100/mo per-flow) vs. manual monthly CSV (free) | **Manual CSV for v1** — upgrade later if the 5 min/month becomes annoying |
| 4 | QR label printing | Word mail-merge / Automate template / external generator | **Word mail-merge** (offline, free) |
| 5 | Old data retention | Archive final MySQL dump to SharePoint, then cancel Cloudways after 30-day parallel run | Yes |
| 6 | Builder + timeline | Name the builder; ~6–8 weeks part-time | — |

---

## 10. Learning Resources for the Builder (all free, all Microsoft)

- **Microsoft Learn: "Create a canvas app in Power Apps"** — the core skill, ~3 hours.
- **Microsoft Learn: "Introduction to SharePoint lists"** — ~1 hour.
- **Microsoft Learn: "Automate a business process using Power Automate"** — ~2 hours.
- Power Apps community forums and the in-product templates ("Asset Checkout" template is worth opening just to steal ideas from).

---

*Prepared June 2026, based on the AssetIQ codebase as of PR #10 (disposed dates, batch retire, full audit coverage). The feature map in §3 reflects that version, so nothing shipped recently gets lost in the move.*
