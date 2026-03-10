<?php
require_once __DIR__ . '/auth/auth.php';
auth_require();
$user = auth_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>AssetIQ — IT Asset Manager</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap');

:root {
  --bg:        #08090d;
  --surface:   #0e1018;
  --surface2:  #141720;
  --surface3:  #1a1f2e;
  --border:    #1e2538;
  --border2:   #252d3f;
  --accent:    #00e5ff;
  --accent2:   #00b8cc;
  --glow:      rgba(0,229,255,0.18);
  --glow-sm:   rgba(0,229,255,0.08);
  --green:     #00ff88;
  --orange:    #ff8c00;
  --purple:    #a78bfa;
  --red:       #ff3b5c;
  --amber:     #ffb400;
  --text:      #dde2ef;
  --text2:     #a8b2c8;
  --muted:     #4a5468;
  --fg2:       #8899b0;
  --nav-h:     60px;
  --bottom-h:  64px;
  /* Skeuomorphic light source: top-left */
  --shine:     rgba(255,255,255,0.035);
  --shadow-hi: rgba(0,0,0,0.6);
  --shadow-lo: rgba(0,0,0,0.25);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  font-family: 'Outfit', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  -webkit-text-size-adjust: 100%;
  overflow-x: hidden;
}

/* ── GLOBAL TEXTURE OVERLAY ── */
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background-image:
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Crect width='60' height='60' fill='none'/%3E%3Cpath d='M0 0h60M0 60h60M0 0v60M60 0v60' stroke='%23ffffff' stroke-width='0.3' stroke-opacity='0.018'/%3E%3C/svg%3E");
  background-size: 60px 60px;
}

/* ── TOP HEADER ── */
.top-bar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  height: var(--nav-h);
  background: linear-gradient(180deg, rgba(14,16,24,0.98) 0%, rgba(10,11,17,0.95) 100%);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 1px 0 rgba(255,255,255,0.03), 0 4px 24px rgba(0,0,0,0.5);
  display: flex; align-items: center;
  padding: 0 16px; gap: 12px;
}

.logo {
  display: flex; align-items: center; gap: 10px; flex: 1;
  cursor: pointer; text-decoration: none;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}
.logo-mark {
  width: 34px; height: 34px; flex-shrink: 0;
  background: linear-gradient(145deg, rgba(0,229,255,0.2) 0%, rgba(0,180,200,0.06) 100%);
  border: 1px solid rgba(0,229,255,0.35);
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  box-shadow:
    0 0 20px rgba(0,229,255,0.12),
    inset 0 1px 0 rgba(255,255,255,0.08),
    inset 0 -1px 0 rgba(0,0,0,0.3);
  transition: box-shadow 0.2s, border-color 0.2s;
}
.logo:hover .logo-mark {
  box-shadow: 0 0 32px rgba(0,229,255,0.25), inset 0 1px 0 rgba(255,255,255,0.1);
  border-color: rgba(0,229,255,0.6);
}
.logo-wordmark { display: flex; flex-direction: column; line-height: 1; }
.logo-wordmark-top {
  font-family: 'Outfit', sans-serif;
  font-size: 16px; font-weight: 800; letter-spacing: 0.5px;
  color: var(--text);
}
.logo-wordmark-top span { color: var(--accent); }
.logo-wordmark-sub {
  font-size: 9px; font-weight: 500; letter-spacing: 1.5px;
  color: var(--muted); text-transform: uppercase; margin-top: 2px;
}

.top-add-btn {
  background: linear-gradient(135deg, #00e5ff 0%, #00b8cc 100%);
  border: none; color: #000;
  font-family: 'Outfit', sans-serif; font-weight: 800;
  font-size: 14px; border-radius: 9px;
  padding: 9px 16px; cursor: pointer;
  display: flex; align-items: center; gap: 6px;
  white-space: nowrap; flex-shrink: 0;
  box-shadow: 0 0 18px rgba(0,229,255,0.25), 0 2px 8px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.3);
  -webkit-tap-highlight-color: transparent;
  transition: box-shadow 0.2s;
}
.top-add-btn:active {
  opacity: 0.85; transform: scale(0.97);
  box-shadow: 0 0 8px rgba(0,229,255,0.15), inset 0 2px 4px rgba(0,0,0,0.2);
}

/* ── BOTTOM NAV ── */
.bottom-nav {
  position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
  height: var(--bottom-h);
  background: linear-gradient(0deg, rgba(8,9,13,0.99) 0%, rgba(14,16,24,0.97) 100%);
  border-top: 1px solid var(--border);
  box-shadow: 0 -1px 0 rgba(255,255,255,0.03), 0 -8px 32px rgba(0,0,0,0.5);
  display: flex; align-items: stretch;
  padding-bottom: env(safe-area-inset-bottom);
}
.nav-btn {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 4px;
  background: none; border: none; color: var(--muted);
  font-family: 'Outfit', sans-serif; font-size: 10px; font-weight: 700;
  letter-spacing: 0.5px; text-transform: uppercase;
  cursor: pointer; transition: color .15s;
  -webkit-tap-highlight-color: transparent;
  padding: 8px 4px;
  position: relative;
}
.nav-btn.active { color: var(--accent); }
.nav-btn.active::before {
  content: '';
  position: absolute; top: 0; left: 25%; right: 25%; height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent), transparent);
  border-radius: 0 0 2px 2px;
  box-shadow: 0 0 8px var(--accent);
}
.nav-btn:active { opacity: 0.7; }

/* ── PAGE WRAPPER ── */
.page {
  display: none;
  padding: calc(var(--nav-h) + 16px) 16px calc(var(--bottom-h) + 20px);
  min-height: 100vh;
  position: relative; z-index: 1;
}
.page.active { display: block; }
.page-title {
  font-family: 'Outfit', sans-serif;
  font-size: 22px; font-weight: 800; margin-bottom: 2px;
  background: linear-gradient(135deg, var(--text) 0%, var(--text2) 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.page-sub { color: var(--muted); font-size: 13px; margin-bottom: 20px; line-height: 1.5; }

/* ── Page header separator ── */
.page-header {
  margin-bottom: 20px; padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
}
.page-header .page-title { margin-bottom: 3px; }
.page-header .page-sub   { margin-bottom: 0; }

.section-label {
  font-size: 10px; font-weight: 700; letter-spacing: 2px;
  text-transform: uppercase; color: var(--muted); margin-bottom: 10px;
  display: flex; align-items: center; gap: 8px;
}
.section-label::after {
  content: ''; flex: 1; height: 1px;
  background: linear-gradient(90deg, var(--border), transparent);
}

/* ── STATS GRID ── */
.stats-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 10px; margin-bottom: 24px;
}

/* Last stat card fills full width on mobile if odd */
.stats-grid > .stat-card:last-child:nth-child(odd) {
  grid-column: 1 / -1;
}

.stat-card {
  background: linear-gradient(145deg, var(--surface) 0%, rgba(14,16,24,0.8) 100%);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 14px 16px;
  position: relative; overflow: hidden;
  transition: border-color 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow:
    inset 0 1px 0 var(--shine),
    inset 0 -1px 0 rgba(0,0,0,0.3),
    0 4px 16px rgba(0,0,0,0.3);
}
.stat-card.clickable { cursor: pointer; }
.stat-card.clickable:hover {
  border-color: var(--c, var(--accent));
  transform: translateY(-2px);
  box-shadow:
    inset 0 1px 0 var(--shine),
    0 0 20px rgba(0,229,255,0.06),
    0 8px 24px rgba(0,0,0,0.4);
}
.stat-card.clickable:active { transform: translateY(0); }
.stat-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, transparent, var(--c, var(--accent)), transparent);
  box-shadow: 0 0 12px var(--c, var(--accent));
}
.stat-card::after {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(ellipse at top left, rgba(255,255,255,0.025) 0%, transparent 60%);
}
.stat-label {
  font-size: 9px; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
}
.stat-value {
  font-family: 'Outfit', sans-serif;
  font-size: 28px; font-weight: 800; line-height: 1;
  color: var(--c, var(--accent));
  text-shadow: 0 0 20px var(--c, rgba(0,229,255,0.4));
  font-variant-numeric: tabular-nums;
}
.stat-sub { font-size: 11px; color: var(--muted); margin-top: 4px; font-family: 'JetBrains Mono', monospace; }

/* ── ASSET CARDS ── */
.asset-list { display: flex; flex-direction: column; gap: 10px; }
.asset-card {
  background: linear-gradient(160deg, #0f1219 0%, #0c0e15 100%);
  border: 1px solid var(--border);
  border-radius: 14px; overflow: hidden;
  position: relative;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,0.04),
    inset 0 -1px 0 rgba(0,0,0,0.4),
    0 4px 20px rgba(0,0,0,0.35);
  transition: border-color .2s, transform .1s, box-shadow .2s;
}
.asset-card::after {
  content: ''; position: absolute; inset: 0; pointer-events: none;
  background: radial-gradient(ellipse at top left, rgba(255,255,255,0.02) 0%, transparent 50%);
}
.asset-card.selected {
  border-color: var(--accent);
  box-shadow: 0 0 0 1px var(--accent), 0 0 24px rgba(0,229,255,0.12), inset 0 1px 0 rgba(255,255,255,0.04);
}
.asset-card-checkbox {
  position: absolute; top: 14px; left: 14px; z-index: 2;
  width: 20px; height: 20px; border-radius: 6px;
  border: 1.5px solid var(--border2); background: var(--surface2);
  cursor: pointer; display: none; align-items: center; justify-content: center;
  transition: all .15s;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.4), 0 1px 0 rgba(255,255,255,0.04);
}
.batch-mode .asset-card-checkbox { display: flex; }
.batch-mode .asset-card-header { padding-left: 42px; }
.asset-card.selected .asset-card-checkbox {
  background: var(--accent); border-color: var(--accent);
  box-shadow: 0 0 10px rgba(0,229,255,0.4);
}
.asset-card-header {
  padding: 12px 16px 8px;
  display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;
}
.asset-card-name {
  font-family: 'Outfit', sans-serif;
  font-size: 14px; font-weight: 700; line-height: 1.3; flex: 1; min-width: 0;
}
.asset-card-id {
  font-family: 'JetBrains Mono', monospace; font-size: 11px;
  color: var(--accent); opacity: 0.7; margin-top: 3px;
  text-shadow: 0 0 8px rgba(0,229,255,0.3);
}
.asset-card-body {
  padding: 0 16px 12px;
  display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
}
.asset-card-field label {
  font-size: 9px; font-weight: 700; letter-spacing: 1px;
  text-transform: uppercase; color: var(--muted); display: block; margin-bottom: 2px;
}
.asset-card-field span { font-size: 12px; font-weight: 600; }
.asset-card-actions {
  border-top: 1px solid var(--border);
  background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.05) 100%);
  display: flex;
  position: relative;
}
.card-action-btn {
  flex: 1; background: none; border: none;
  font-family: 'Outfit', sans-serif; font-weight: 700;
  font-size: 12px; color: var(--muted); padding: 12px 8px;
  cursor: pointer; transition: all .2s;
  -webkit-tap-highlight-color: transparent;
  display: flex; align-items: center; justify-content: center; gap: 5px;
  min-height: 44px; position: relative;
}
.card-action-btn:not(:last-child) { border-right: 1px solid var(--border); }
.card-action-btn:hover { background: rgba(255,255,255,0.03); }
.card-action-btn:active { background: rgba(255,255,255,0.05); transform: scale(0.95); }
.card-action-btn.qr-btn   { color: var(--accent); }
.card-action-btn.qr-btn:hover { color: var(--accent); background: rgba(0,229,255,0.05); }
.card-action-btn.edit-btn { color: var(--purple); }
.card-action-btn.edit-btn:hover { background: rgba(167,139,250,0.05); }
.card-action-btn.del-btn  { color: var(--red); }
/* More menu dropdown */
.card-more-btn { color: var(--muted); }
.card-more-btn:hover { color: var(--text); background: rgba(255,255,255,0.04); }
.card-more-menu {
  position: absolute; bottom: calc(100% + 6px); right: 0;
  background: var(--surface2);
  border: 1px solid var(--border2);
  border-radius: 12px; min-width: 160px; z-index: 50;
  box-shadow: 0 8px 32px rgba(0,0,0,0.6), 0 2px 8px rgba(0,0,0,0.4);
  overflow: hidden; display: none;
  animation: menuPop 0.12s cubic-bezier(0.2,0,0,1.3);
}
.card-more-menu.open { display: block; }
@keyframes menuPop {
  from { opacity:0; transform: scale(0.92) translateY(4px); }
  to   { opacity:1; transform: scale(1)    translateY(0); }
}
.card-menu-item {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.1s; color: var(--text2);
  border-bottom: 1px solid var(--border);
  font-family: 'Outfit', sans-serif;
}
.card-menu-item:last-child { border-bottom: none; }
.card-menu-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
.card-menu-item.danger { color: var(--red); }
.card-menu-item.danger:hover { background: rgba(255,59,92,0.06); }
.card-menu-item.warn  { color: var(--amber); }
.card-menu-item.warn:hover  { background: rgba(255,180,0,0.06); }
.card-menu-item.eol-ack { color: var(--orange); }
.card-menu-item.eol-ack:hover { background: rgba(255,140,0,0.06); }
.card-menu-item.eol-clear { color: var(--muted); }
.card-menu-item.eol-clear:hover { background: rgba(255,255,255,0.04); }

/* ── BATCH SELECTION ── */
.batch-bar {
  position: fixed; bottom: 88px; left: 12px; right: 12px;
  background: linear-gradient(145deg, #0e1220, #0a0d18);
  border: 1px solid rgba(0,229,255,0.35);
  border-radius: 16px; padding: 12px 16px;
  display: none; align-items: center; gap: 10px;
  z-index: 200;
  box-shadow: 0 0 30px rgba(0,229,255,0.1), 0 8px 32px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.05);
  animation: slideUp .2s ease;
}
.batch-bar.visible { display: flex; }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.batch-count { font-size: 13px; font-weight: 700; color: var(--accent); white-space: nowrap; flex-shrink: 0; text-shadow: 0 0 12px rgba(0,229,255,0.4); }
.batch-actions { display: flex; gap: 8px; flex: 1; justify-content: flex-end; flex-wrap: wrap; }
.batch-btn {
  font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 12px;
  padding: 8px 14px; border-radius: 9px; cursor: pointer;
  display: flex; align-items: center; gap: 5px; transition: all .15s;
  white-space: nowrap;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.08), 0 2px 6px rgba(0,0,0,0.3);
}
.batch-btn-reassign { background: rgba(0,255,136,0.1); color: var(--green);  border: 1px solid rgba(0,255,136,0.3); }
.batch-btn-ai  { background: rgba(0,229,255,0.1); color: var(--accent); border: 1px solid rgba(0,229,255,0.3); }
.batch-btn-del { background: rgba(255,59,92,0.1); color: var(--red);    border: 1px solid rgba(255,59,92,0.3); }
.batch-btn-cancel { background: var(--surface2); color: var(--muted); border: 1px solid var(--border2); }
.batch-btn:active { opacity: 0.7; transform: scale(0.97); }
.batch-progress {
  position: fixed; top: calc(var(--nav-h) + 12px); left: 12px; right: 12px;
  background: linear-gradient(145deg, #0e1220, #0a0d18);
  border: 1px solid rgba(0,229,255,0.25);
  border-radius: 14px; padding: 14px 16px; z-index: 300;
  display: none; flex-direction: column; gap: 8px;
  box-shadow: 0 0 24px rgba(0,229,255,0.08), 0 8px 32px rgba(0,0,0,0.5);
}
.batch-progress.visible { display: flex; }
.batch-progress-bar-wrap {
  background: var(--surface3); border-radius: 99px; height: 5px; overflow: hidden;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.5);
}
.batch-progress-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--accent2), var(--accent));
  border-radius: 99px; transition: width .3s;
  box-shadow: 0 0 8px rgba(0,229,255,0.5);
}
.select-all-btn {
  font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 12px;
  padding: 12px 14px; border-radius: 9px; cursor: pointer;
  background: var(--surface2); color: var(--muted);
  border: 1px solid var(--border); display: flex; align-items: center; gap: 5px;
  min-height: 44px; flex-shrink: 0; transition: all .15s; white-space: nowrap;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 2px 6px rgba(0,0,0,0.3);
}
.select-all-btn.active {
  background: rgba(0,229,255,0.08); color: var(--accent);
  border-color: rgba(0,229,255,0.3);
  box-shadow: 0 0 12px rgba(0,229,255,0.08), inset 0 1px 0 rgba(255,255,255,0.04);
}

/* ── SEARCH / FILTER ── */
.filter-bar { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
.search-wrap { position: relative; }
.search-wrap svg {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  color: var(--muted); pointer-events: none;
}
.filter-row { display: flex; gap: 8px; }
.filter-row select { flex: 1; min-width: 0; }

/* ── FORM ELEMENTS ── */
input, select, textarea {
  font-family: 'Outfit', sans-serif;
  background: var(--surface2);
  border: 1px solid var(--border2);
  color: var(--text); border-radius: 10px; outline: none;
  font-size: 16px;
  padding: 12px 14px; width: 100%;
  transition: border-color .2s, box-shadow .2s;
  -webkit-appearance: none; appearance: none;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.35), inset 0 1px 0 rgba(0,0,0,0.2);
}
input:focus, select:focus, textarea:focus {
  border-color: var(--accent);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.3), 0 0 0 3px rgba(0,229,255,0.08);
}
.search-wrap input { padding-left: 40px; }
textarea { resize: vertical; min-height: 80px; }
select {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%234a5468' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}

/* ── BUTTONS ── */
.btn {
  font-family: 'Outfit', sans-serif; font-weight: 700; border: none;
  border-radius: 11px; cursor: pointer; font-size: 15px;
  transition: all .15s; display: inline-flex; align-items: center;
  justify-content: center; gap: 7px; white-space: nowrap;
  padding: 13px 20px;
  -webkit-tap-highlight-color: transparent;
  min-height: 48px;
  width: 100%;
}
.btn:active { opacity: 0.8; transform: scale(0.98); }
.btn-primary {
  background: linear-gradient(135deg, #00e5ff 0%, #00b0c8 100%);
  color: #000;
  box-shadow: 0 0 20px rgba(0,229,255,0.25), 0 4px 12px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.35);
}
.btn-primary:active {
  box-shadow: 0 0 8px rgba(0,229,255,0.15), inset 0 2px 4px rgba(0,0,0,0.2);
}
.btn-ghost {
  background: linear-gradient(145deg, var(--surface2), var(--surface));
  color: var(--text); border: 1px solid var(--border2);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 2px 8px rgba(0,0,0,0.3);
}
.btn-ghost:active { border-color: var(--accent); color: var(--accent); }

/* ── MODAL ── */
.overlay {
  display: none; position: fixed; inset: 0; z-index: 200;
  align-items: flex-end; justify-content: center;
}
.overlay.open {
  display: flex;
  animation: overlayIn 0.35s ease forwards;
}
@keyframes overlayIn {
  from { background: rgba(0,0,0,0); backdrop-filter: blur(0px); }
  to   { background: rgba(0,0,0,0.52); backdrop-filter: blur(22px) saturate(1.5); }
}
.modal {
  background: rgba(11,13,20,0.74);
  backdrop-filter: blur(40px) saturate(1.8);
  -webkit-backdrop-filter: blur(40px) saturate(1.8);
  border: 1px solid rgba(255,255,255,0.08);
  border-top: 1px solid rgba(255,255,255,0.15);
  border-radius: 24px 24px 0 0;
  width: 100%; max-width: 620px;
  max-height: 92vh; overflow-y: auto;
  box-shadow: 0 -1px 0 rgba(255,255,255,0.09), 0 -32px 80px rgba(0,0,0,0.55), inset 0 1px 0 rgba(255,255,255,0.07);
  animation: sheetUp .25s cubic-bezier(0.32,0.72,0,1);
  padding-bottom: env(safe-area-inset-bottom);
}
@keyframes sheetUp { from { transform: translateY(100%); opacity:0.3; } to { transform: translateY(0); opacity:1; } }
.modal-handle {
  width: 36px; height: 4px; border-radius: 2px;
  background: rgba(255,255,255,0.1); margin: 12px auto 4px;
}
.modal-header {
  padding: 4px 20px 16px;
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.modal-title { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; }
.close-btn {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.09); color: var(--muted);
  width: 34px; height: 34px; border-radius: 50%; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  -webkit-tap-highlight-color: transparent; flex-shrink: 0;
  transition: all .15s;
}
.close-btn:hover { background: rgba(255,255,255,0.1); color: var(--text); }
.close-btn:active { background: rgba(255,255,255,0.03); }
.modal-body { padding: 20px; }
.modal-footer {
  padding: 12px 20px 16px;
  border-top: 1px solid var(--border);
  display: flex; flex-direction: column; gap: 10px;
  background: linear-gradient(0deg, rgba(0,0,0,0.2) 0%, transparent 100%);
}

/* ── FORM LAYOUT ── */
.form-stack { display: flex; flex-direction: column; gap: 14px; }
.form-2col  { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label {
  font-size: 10px; font-weight: 700; letter-spacing: 1px;
  text-transform: uppercase; color: var(--muted);
}

/* ── BADGES ── */
.badge {
  display: inline-flex; align-items: center;
  padding: 3px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: 0.5px; white-space: nowrap;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.07);
}
.badge-laptop     { background: rgba(0,229,255,0.1);    color: var(--accent);  border: 1px solid rgba(0,229,255,0.2); }
.badge-desktop    { background: rgba(0,255,136,0.1);    color: var(--green);   border: 1px solid rgba(0,255,136,0.2); }
.badge-monitor    { background: rgba(167,139,250,0.1);  color: var(--purple);  border: 1px solid rgba(167,139,250,0.2); }
.badge-docking    { background: rgba(255,140,0,0.1);    color: var(--orange);  border: 1px solid rgba(255,140,0,0.2); }
.badge-peripheral { background: rgba(255,59,92,0.1);    color: var(--red);     border: 1px solid rgba(255,59,92,0.2); }
.badge-printer    { background: rgba(167,139,250,0.1);  color: var(--purple);  border: 1px solid rgba(167,139,250,0.2); }
.badge-camera     { background: rgba(0,255,136,0.1);    color: var(--green);   border: 1px solid rgba(0,255,136,0.2); }
.badge-assigned   { background: rgba(0,255,136,0.1);    color: var(--green);   border: 1px solid rgba(0,255,136,0.2); }
.badge-unassigned { background: rgba(74,84,104,0.15);   color: var(--muted);   border: 1px solid rgba(74,84,104,0.2); }
.badge-retired    { background: rgba(255,59,92,0.08);   color: var(--red);     border: 1px solid rgba(255,59,92,0.15); }

/* ── QR ── */
.qr-container { display: flex; flex-direction: column; align-items: center; gap: 18px; }
.qr-box {
  background: #fff; border-radius: 14px; padding: 16px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 30px rgba(0,229,255,0.1);
}
.qr-asset-id {
  font-family: 'JetBrains Mono', monospace; font-size: 24px; font-weight: 700;
  color: var(--accent); letter-spacing: 3px; text-align: center;
  text-shadow: 0 0 20px rgba(0,229,255,0.4);
}
.qr-asset-name { font-size: 14px; color: var(--muted); text-align: center; margin-top: 4px; }

/* ── SCAN ── */
.scan-wrap { max-width: 480px; margin: 0 auto; }
#reader {
  border: 1px solid var(--border2); border-radius: 14px; overflow: hidden;
  background: var(--surface2); margin-bottom: 14px;
  min-height: 280px; display: flex; align-items: center; justify-content: center;
  box-shadow: inset 0 2px 8px rgba(0,0,0,0.4);
}
.scan-result {
  background: linear-gradient(145deg, var(--surface), var(--surface2));
  border: 1px solid var(--border2);
  border-radius: 14px; padding: 18px; display: none; margin-top: 16px;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 4px 16px rgba(0,0,0,0.3);
}
.scan-result.visible { display: block; animation: sheetUp .2s ease; }
.scan-result-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 14px 0; }
.scan-field label {
  font-size: 9px; font-weight: 700; letter-spacing: 1px;
  text-transform: uppercase; color: var(--muted); display: block; margin-bottom: 2px;
}
.scan-field span { font-size: 13px; font-weight: 600; }

/* ── TOAST ── */
.toast {
  position: fixed; bottom: 88px; left: 16px; right: 16px;
  z-index: 999;
  background: linear-gradient(145deg, #0f1219, #0c0e16);
  border: 1px solid var(--border2);
  border-radius: 13px; padding: 14px 18px; font-size: 14px; font-weight: 600;
  box-shadow: 0 8px 32px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.04);
  display: none; align-items: center; gap: 10px;
}
.toast.show    { display: flex; animation: sheetUp .2s ease; }
.toast.success { border-color: rgba(0,255,136,0.3); border-left: 3px solid var(--green); color: var(--green); box-shadow: 0 0 20px rgba(0,255,136,0.06), 0 8px 32px rgba(0,0,0,0.6); }
.toast.error   { border-color: rgba(255,59,92,0.3);  border-left: 3px solid var(--red);   color: var(--red);   box-shadow: 0 0 20px rgba(255,59,92,0.06), 0 8px 32px rgba(0,0,0,0.6); }

/* ── EMPTY / LOADING ── */
.empty-state { text-align: center; padding: 48px 20px; color: var(--muted); }
.empty-state h3 { font-size: 17px; font-weight: 700; color: var(--text); margin: 12px 0 6px; }
.spinner {
  width: 24px; height: 24px; border: 2px solid var(--border2);
  border-top-color: var(--accent); border-radius: 50%;
  animation: spin .6s linear infinite; margin: 32px auto;
  box-shadow: 0 0 12px rgba(0,229,255,0.15);
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── MOBILE-ONLY ── */
@media (max-width: 767px) {
  .hamburger-btn { display: flex !important; }
  .fab-qr        { display: flex !important; }
  .fab-add       { display: flex !important; }
  .bottom-nav    { display: none !important; }
  :root { --bottom-h: 0px; }
  .page { padding: calc(var(--nav-h) + 16px) 16px 90px; }
}

/* ── DESKTOP ── */
@media (min-width: 768px) {
  :root { --bottom-h: 0px; }
  .bottom-nav { display: none; }
  .top-add-btn { display: none; }
  .desktop-nav { display: flex !important; gap: 4px; margin-left: auto; align-items: center; }
  .page { padding: 80px 32px 40px; }
  .page-title { font-size: 26px; }
  .stats-grid { grid-template-columns: repeat(5,1fr); gap: 14px; margin-bottom: 28px; }
  .stat-value { font-size: 30px; }
  .asset-list  { display: none !important; }
  .table-wrap  { display: block !important; }
  .filter-bar  { flex-direction: row; align-items: center; }
  .filter-row  { flex-direction: row; gap: 10px; }
  .filter-row select { flex: 0 0 auto; width: auto; }
  .modal { border-radius: 16px !important; max-height: 90vh; animation: fadeUp .2s ease; }
  @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
  .overlay { align-items: center; }
  .modal-handle { display: none; }
  .form-stack { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  .form-2col { display: contents; }
  .form-group.full { grid-column: span 2; }
  .modal-footer { flex-direction: row; justify-content: flex-end; gap: 10px; }
  .modal-footer .btn { width: auto; min-width: 120px; }
  .toast { left: auto; right: 24px; width: 320px; bottom: 24px; }
  .desktop-add-btn { display: inline-flex !important; }
}

/* ── DESKTOP TABLE ── */
.table-wrap {
  display: none;
  background: linear-gradient(160deg, #0f1219, #0c0e15);
  border: 1px solid var(--border);
  border-radius: 14px; overflow: auto; max-height: calc(100vh - 220px);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 4px 24px rgba(0,0,0,0.4);
}
table { width: 100%; border-collapse: collapse; }
thead {
  background: linear-gradient(180deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);
  border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 2;
}
th {
  padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700;
  letter-spacing: 1px; text-transform: uppercase; color: var(--muted);
  cursor: pointer; user-select: none; white-space: nowrap;
  transition: color .15s;
}
th:hover { color: var(--accent); }
td { padding: 12px 16px; font-size: 13px; border-bottom: 1px solid var(--border); vertical-align: middle; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(0,229,255,0.022) !important; transition: background 0.1s; }
tbody tr:nth-child(even) td { background: rgba(255,255,255,0.01); }
.font-mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--accent); opacity: 0.7; }
.tbl-actions { display: flex; gap: 6px; }
.tbl-btn {
  font-family: 'Outfit', sans-serif; font-weight: 700;
  border: 1px solid var(--border2); background: var(--surface2);
  color: var(--text2); border-radius: 7px; cursor: pointer;
  padding: 5px 11px; font-size: 12px; transition: all .15s;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
}
.tbl-btn:hover { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 8px rgba(0,229,255,0.1), inset 0 1px 0 rgba(255,255,255,0.04); }
.tbl-btn.danger:hover { border-color: var(--red); color: var(--red); box-shadow: 0 0 8px rgba(255,59,92,0.1); }

/* ── Desktop nav: icon-only, label animates in on hover/active ── */
.desktop-nav .nav-btn {
  flex-direction: column;
  gap: 2px;
  padding: 6px 10px;
  border-radius: 9px;
  min-width: 48px;
  font-size: 9px;
}
.desktop-nav .nav-btn .nav-label {
  font-size: 9px; font-weight: 700; letter-spacing: 0.5px;
  text-transform: uppercase; line-height: 1;
  max-height: 0; overflow: hidden; opacity: 0;
  transition: max-height 0.18s ease, opacity 0.18s ease, margin-top 0.18s ease;
  white-space: nowrap;
}
.desktop-nav .nav-btn:hover .nav-label,
.desktop-nav .nav-btn.active .nav-label {
  max-height: 14px; opacity: 1; margin-top: 1px;
}
.desktop-nav .nav-btn:hover { background: rgba(255,255,255,0.04); }

.desktop-nav { display: none; }

/* Desktop nav active state */
.desktop-nav .nav-btn.active {
  background: rgba(0,229,255,0.08);
  color: var(--accent);
}
.desktop-nav .nav-btn.active::after { display: none; }

.desktop-add-btn { display: none !important; }

/* ── EOL FLAGS ── */
.flag-critical {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(255,59,92,0.12); color: var(--red);
  border: 1px solid rgba(255,59,92,0.25);
  padding: 3px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: 0.5px; white-space: nowrap;
  box-shadow: 0 0 10px rgba(255,59,92,0.08);
}
.flag-warning {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(255,140,0,0.12); color: var(--orange);
  border: 1px solid rgba(255,140,0,0.25);
  padding: 3px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: 0.5px; white-space: nowrap;
  box-shadow: 0 0 10px rgba(255,140,0,0.08);
}
.flag-override {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(255,180,0,0.08); color: #c8931a;
  border: 1px solid rgba(255,180,0,0.18);
  padding: 3px 9px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: 0.5px; white-space: nowrap;
}
.eol-banner {
  background: rgba(255,59,92,0.06); border: 1px solid rgba(255,59,92,0.18);
  border-left: 3px solid var(--red);
  border-radius: 0 10px 10px 0; padding: 10px 14px; margin-bottom: 10px;
  font-size: 12px; font-weight: 600; color: var(--red);
  display: flex; align-items: center; gap: 8px;
  cursor: default;
}
.eol-banner.warn {
  background: rgba(255,140,0,0.06); border-color: rgba(255,140,0,0.18);
  border-left-color: var(--orange); color: var(--orange);
}

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }

/* ── USERS PAGE ── */
.user-card {
  background: linear-gradient(155deg, #0f1219, #0c0e15);
  border: 1px solid var(--border);
  border-radius: 14px; padding: 16px; margin-bottom: 10px;
  cursor: pointer;
  transition: border-color .2s, box-shadow .2s;
  -webkit-tap-highlight-color: transparent;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 4px 16px rgba(0,0,0,0.3);
}
.user-card:hover, .user-card:active {
  border-color: rgba(0,229,255,0.3);
  box-shadow: 0 0 20px rgba(0,229,255,0.05), inset 0 1px 0 rgba(255,255,255,0.03);
}
.user-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.user-avatar {
  width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--accent2) 0%, var(--accent) 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 15px; font-weight: 800; color: #000;
  box-shadow: 0 0 16px rgba(0,229,255,0.2);
}
.user-name { font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; }
.user-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.user-asset-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.user-asset-chip {
  background: var(--surface2); border: 1px solid var(--border2);
  border-radius: 8px; padding: 5px 10px;
  font-size: 11px; font-weight: 600;
  display: flex; align-items: center; gap: 5px;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
}
.user-eol-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

.user-detail-asset {
  background: var(--surface2); border: 1px solid var(--border2);
  border-radius: 11px; padding: 14px; margin-bottom: 8px;
  cursor: pointer; transition: border-color .15s;
  -webkit-tap-highlight-color: transparent;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
}
.user-detail-asset:hover { border-color: rgba(0,229,255,0.3); }
.user-detail-asset:last-child { margin-bottom: 0; }
.user-detail-asset-name { font-size: 14px; font-weight: 700; margin-bottom: 6px; display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
.user-detail-asset-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.user-detail-field label { font-size: 9px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); display: block; margin-bottom: 2px; }
.user-detail-field span  { font-size: 12px; font-weight: 600; }

.users-empty { text-align:center; padding:48px 20px; color:var(--muted); }
.users-empty h3 { font-size:17px; font-weight:700; color:var(--text); margin:12px 0 6px; }

.intune-setup-card {
  background: linear-gradient(145deg, #0f1219, #0c0e16);
  border: 1px solid var(--border);
  border-radius: 16px; padding: 28px 24px; max-width: 600px;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 8px 32px rgba(0,0,0,0.4);
}
.intune-setup-icon {
  width: 56px; height: 56px; border-radius: 14px;
  background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.2);
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16px; color: var(--accent);
  box-shadow: 0 0 20px rgba(0,229,255,0.08);
}
.intune-setup-card h3 { font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; margin-bottom: 8px; }
.intune-setup-card p  { font-size: 13px; color: var(--muted); margin-bottom: 20px; line-height: 1.6; }
.intune-setup-card code {
  font-family: 'JetBrains Mono', monospace; font-size: 11px;
  background: var(--surface2); border: 1px solid var(--border2);
  padding: 2px 6px; border-radius: 4px; color: var(--accent);
}
.setup-steps { display: flex; flex-direction: column; gap: 14px; }
.setup-step  { display: flex; gap: 12px; align-items: flex-start; font-size: 13px; line-height: 1.5; }
.step-num {
  width: 24px; height: 24px; border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  color: #000; font-size: 11px; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px;
  box-shadow: 0 0 12px rgba(0,229,255,0.25);
}
.intune-device-card {
  background: linear-gradient(145deg, #0f1219, #0c0e15);
  border: 1px solid var(--border);
  border-radius: 13px; padding: 14px 16px; margin-bottom: 8px;
  display: flex; align-items: flex-start; gap: 12px; cursor: pointer;
  transition: border-color .15s, box-shadow .15s;
  -webkit-tap-highlight-color: transparent;
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.03), 0 2px 8px rgba(0,0,0,0.3);
}
.intune-device-card:hover  { border-color: rgba(0,229,255,0.3); }
.intune-device-card.selected { border-color: var(--accent); background: rgba(0,229,255,0.04); box-shadow: 0 0 20px rgba(0,229,255,0.08); }
.intune-device-card.already-exists { opacity: 0.45; }
.intune-check {
  width: 20px; height: 20px; border-radius: 6px;
  border: 1.5px solid var(--border2); flex-shrink: 0; margin-top: 2px;
  display: flex; align-items: center; justify-content: center;
  transition: all .15s; background: var(--surface2);
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.4);
}
.intune-device-card.selected .intune-check {
  background: var(--accent); border-color: var(--accent);
  box-shadow: 0 0 10px rgba(0,229,255,0.3);
}
.intune-device-info { flex: 1; min-width: 0; }
.intune-device-name { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
.intune-device-meta { font-size: 11px; color: var(--muted); display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 6px; }
.intune-badges { display: flex; flex-wrap: wrap; gap: 5px; }
.compliance-compliant    { background:rgba(0,255,136,0.1);  color:var(--green);  border: 1px solid rgba(0,255,136,0.2); }
.compliance-noncompliant { background:rgba(255,59,92,0.1);  color:var(--red);    border: 1px solid rgba(255,59,92,0.2); }
.compliance-unknown      { background:rgba(74,84,104,0.15); color:var(--muted);  border: 1px solid rgba(74,84,104,0.2); }
.select-all-row { display:flex; align-items:center; gap:10px; padding:8px 0 12px; font-size:13px; font-weight:600; color:var(--muted); cursor:pointer; }

/* MOUSE SPOTLIGHT */
.asset-card,.stat-card,.user-card,.intune-device-card { --mx:50%;--my:50%; }
.card-spotlight {
  position:absolute;inset:0;z-index:0;pointer-events:none;border-radius:inherit;
  background:radial-gradient(200px circle at var(--mx) var(--my),rgba(0,229,255,0.075) 0%,transparent 65%);
  opacity:0;transition:opacity 0.35s ease;
}
.stat-card .card-spotlight {
  background: radial-gradient(
    140px circle at var(--mx) var(--my),
    color-mix(in srgb, var(--c, var(--accent)) 18%, transparent) 0%,
    transparent 65%
  );
}
.asset-card:hover .card-spotlight,.stat-card:hover .card-spotlight,
.user-card:hover .card-spotlight,.intune-device-card:hover .card-spotlight { opacity:1; }
.asset-card:hover { border-color:rgba(0,229,255,0.22) !important; }
.stat-card.clickable:hover { border-color: color-mix(in srgb, var(--c,var(--accent)) 45%, transparent) !important; }

/* Checkbox fix — restore native appearance */
input[type="checkbox"] {
  appearance: auto !important;
  -webkit-appearance: checkbox !important;
  width: auto; height: auto; padding: 0;
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  min-height: unset;
}

/* NAV TAB GLOW */
.nav-btn { position: relative; overflow: visible; }
.nav-btn.active::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 15%; right: 15%; height: 2px;
  background: var(--accent);
  border-radius: 2px 2px 0 0;
  box-shadow: 0 0 5px 1px rgba(0,229,255,0.3), 0 0 12px 2px rgba(0,229,255,0.12);
  transition: box-shadow 0.4s ease;
  pointer-events: none;
}
.nav-btn.nav-flash::after {
  animation: navFlash 0.55s ease forwards;
}
@keyframes navFlash {
  0%   { box-shadow: 0 0 14px 5px rgba(0,229,255,0.95), 0 0 36px 8px rgba(0,229,255,0.5), 0 0 70px 14px rgba(0,229,255,0.2); }
  100% { box-shadow: 0 0 5px 1px rgba(0,229,255,0.3),  0 0 12px 2px rgba(0,229,255,0.12); }
}
.bottom-nav .nav-btn.active::before {
  box-shadow: 0 0 6px 2px rgba(0,229,255,0.4), 0 0 16px 3px rgba(0,229,255,0.15);
  transition: box-shadow 0.4s ease;
}
.bottom-nav .nav-btn.nav-flash::before { animation: navFlash 0.55s ease forwards; }
.nav-btn:not(.active):hover::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 30%; right: 30%; height: 1px;
  background: rgba(0,229,255,0.25);
  box-shadow: 0 0 5px 1px rgba(0,229,255,0.12);
  pointer-events: none;
}

/* ── Activity Log ─────────────────────────── */
.log-entry {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 14px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  transition: background 0.15s;
}
.log-entry:hover { background: var(--surface2); }
.log-icon {
  width: 32px; height: 32px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 14px;
}
.log-icon.created  { background: rgba(0,255,136,0.1);  color: var(--green); }
.log-icon.updated  { background: rgba(0,229,255,0.1);  color: var(--accent); }
.log-icon.archived { background: rgba(255,180,0,0.1);  color: var(--amber); }
.log-icon.restored { background: rgba(167,139,250,0.1);color: var(--purple); }
.log-icon.deleted  { background: rgba(255,59,92,0.1);  color: var(--red); }
.log-body { flex: 1; min-width: 0; }
.log-title { font-size: 13px; font-weight: 600; color: var(--text); line-height: 1.4; }
.log-title a { color: var(--accent); text-decoration: none; }
.log-title a:hover { text-decoration: underline; }
.log-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.log-fields { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 6px; }
.log-field-tag {
  font-size: 11px; font-weight: 600; padding: 2px 7px;
  background: rgba(0,229,255,0.08); border: 1px solid rgba(0,229,255,0.2);
  color: var(--accent); border-radius: 4px; text-transform: capitalize;
}
/* ── Archive card actions ─────────────────── */
.archive-card-actions {
  display: flex; gap: 8px; margin-top: 12px; padding-top: 12px;
  border-top: 1px solid var(--border);
}
.archive-card-actions .btn {
  min-height: 36px; padding: 8px 16px; font-size: 13px; border-radius: 9px;
}


/* ── Reports ─────────────────────────────── */
.report-section { margin-bottom: 28px; }
.report-section-title {
  font-size: 13px; font-weight: 700; color: var(--muted);
  margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.08em;
  display: flex; align-items: center; gap: 8px;
}
.report-section-title::after {
  content: ''; flex: 1; height: 1px;
  background: linear-gradient(90deg, var(--border), transparent);
}
.report-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border); }
.report-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.report-table th { background: rgba(255,255,255,0.025); color: var(--muted); font-weight: 700; padding: 10px 14px; text-align: left; white-space: nowrap; border-bottom: 1px solid var(--border); font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; }
.report-table td { padding: 10px 14px; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--text); vertical-align: middle; }
.report-table tbody tr:nth-child(even) td { background: rgba(255,255,255,0.012); }
.report-table tr:last-child td { border-bottom: none; }
.report-table tbody tr:hover td { background: rgba(0,229,255,0.025) !important; transition: background 0.1s; }
.report-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.report-summary-card {
  background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px;
  position: relative; overflow: hidden;
}
.report-summary-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg, transparent, var(--accent2, var(--accent)), transparent);
  opacity: 0.5;
}
.report-summary-card .rsv { font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 2px; }
.report-summary-card .rsl { font-size: 12px; color: var(--muted); font-weight: 500; }
.depr-bar-wrap { width: 80px; height: 6px; background: var(--surface3); border-radius: 3px; overflow: hidden; }
.depr-bar { height: 100%; border-radius: 3px; background: linear-gradient(90deg, var(--green), var(--accent)); transition: width 0.4s; }
.depr-bar.warn  { background: linear-gradient(90deg, var(--orange), #ffb400); }
.depr-bar.crit  { background: linear-gradient(90deg, var(--red), var(--orange)); }

/* ── Settings ────────────────────────────── */
.settings-section { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; }
.settings-section + .settings-section { margin-top: 16px; }

/* ── Collapsible settings sections ── */
.settings-section-toggle {
  display: flex; align-items: center; justify-content: space-between;
  cursor: pointer; user-select: none; padding-bottom: 0;
}
.settings-toggle-icon {
  width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;
  color: var(--muted); transition: transform 0.2s;
  flex-shrink: 0;
}
.settings-section.collapsed .settings-toggle-icon { transform: rotate(-90deg); }
.settings-section-body { overflow: hidden; transition: max-height 0.25s ease; }
.settings-section.collapsed .settings-section-body { max-height: 0 !important; }

.settings-section-title {
  font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 2px;
  display: flex; align-items: center; gap: 8px;
}
.settings-section-title::before {
  content: ''; display: block; width: 3px; height: 16px;
  background: linear-gradient(180deg, var(--accent), var(--accent2));
  border-radius: 2px; flex-shrink: 0;
}
.settings-section-sub { font-size: 13px; color: var(--muted); }
.threshold-field label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: capitalize; }
.threshold-field input { width: 100%; }

/* Toggle switch */
.toggle-wrap { display: inline-flex; align-items: center; cursor: pointer; }
.toggle-wrap input { display: none; }
.toggle-track { width: 40px; height: 22px; border-radius: 11px; background: var(--surface3); border: 1px solid var(--border2); transition: background 0.2s; position: relative; }
.toggle-wrap input:checked + .toggle-track { background: var(--accent2); border-color: var(--accent); }
.toggle-thumb { position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: var(--muted); transition: transform 0.2s, background 0.2s; }
.toggle-wrap input:checked + .toggle-track .toggle-thumb { transform: translateX(18px); background: #fff; }

/* ── Linked Assets ───────────────────────── */
.linked-section { margin-top: 10px; border-top: 1px solid var(--border); padding-top: 10px; }
.linked-header { display: flex; align-items: center; justify-content: space-between; cursor: pointer; padding: 2px 0; }
.linked-header-label { font-size: 11px; font-weight: 700; color: var(--muted); display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
.linked-count { font-size: 11px; background: rgba(0,229,255,0.1); color: var(--accent); border: 1px solid rgba(0,229,255,0.2); border-radius: 10px; padding: 1px 7px; font-weight: 700; }
.linked-list { margin-top: 8px; display: flex; flex-direction: column; gap: 5px; }
.linked-chip { display: flex; align-items: center; gap: 8px; padding: 6px 10px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; font-size: 12px; }
.linked-chip-name { flex: 1; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.linked-chip-type { color: var(--muted); flex-shrink: 0; }
.linked-chip-unlink { margin-left: auto; color: var(--muted); cursor: pointer; flex-shrink: 0; padding: 2px; }
.linked-chip-unlink:hover { color: var(--red); }
.linked-add-btn { font-size: 12px; color: var(--accent); background: none; border: 1px dashed rgba(0,229,255,0.3); border-radius: 8px; padding: 5px 10px; cursor: pointer; width: 100%; margin-top: 5px; font-family: inherit; font-weight: 600; transition: background 0.2s; }
.linked-add-btn:hover { background: rgba(0,229,255,0.06); }

/* Link picker modal */
.link-picker { position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: var(--surface2); border: 1px solid var(--border2); border-radius: 10px; z-index: 100; max-height: 200px; overflow-y: auto; box-shadow: 0 8px 32px rgba(0,0,0,0.5); }
.link-picker-item { padding: 9px 12px; cursor: pointer; font-size: 13px; border-bottom: 1px solid var(--border); }
.link-picker-item:last-child { border-bottom: none; }
.link-picker-item:hover { background: rgba(0,229,255,0.06); }
.link-picker-item .pid { color: var(--muted); font-size: 11px; }


/* ── Hamburger Drawer ─────────────────────── */
.drawer-overlay {
  display: none; position: fixed; inset: 0; z-index: 400;
  background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);
}
.drawer-overlay.open { display: block; }
.drawer {
  position: fixed; top: 0; left: 0; bottom: 0; z-index: 401;
  width: min(300px, 85vw);
  background: var(--surface);
  border-right: 1px solid var(--border2);
  display: flex; flex-direction: column;
  transform: translateX(-100%);
  transition: transform 0.28s cubic-bezier(0.4,0,0.2,1);
  box-shadow: 4px 0 40px rgba(0,0,0,0.5);
}
.drawer.open { transform: translateX(0); }

.drawer-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 20px 16px;
  border-bottom: 1px solid var(--border);
}
.drawer-logo { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 800; color: var(--text); }
.drawer-close {
  width: 32px; height: 32px; border-radius: 8px; display: flex;
  align-items: center; justify-content: center; cursor: pointer;
  color: var(--muted); background: var(--surface2); border: 1px solid var(--border);
  transition: color 0.2s;
}
.drawer-close:hover { color: var(--text); }
.drawer-nav { flex: 1; overflow-y: auto; padding: 12px 12px; display: flex; flex-direction: column; gap: 2px; }
.drawer-nav-item {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 14px; border-radius: 10px; cursor: pointer;
  color: var(--fg2); font-size: 14px; font-weight: 600;
  transition: background 0.15s, color 0.15s; position: relative;
  border: 1px solid transparent;
}

/* Drawer nav item active transition */
.drawer-nav-item {
  transition: background 0.15s, color 0.15s, box-shadow 0.15s;
}
.drawer-nav-item:hover { background: var(--surface2); color: var(--text); }
.drawer-nav-item.active {
  background: rgba(0,229,255,0.07);
  color: var(--accent);
  border-color: rgba(0,229,255,0.15);
  box-shadow: inset 3px 0 0 var(--accent);
}
.drawer-nav-item .nav-badge {
  margin-left: auto; font-size: 10px; font-weight: 700;
  background: var(--red); color: #fff; border-radius: 10px; padding: 1px 6px;
}
.drawer-nav-item svg { transition: color 0.15s; }
.drawer-nav-item.active svg { color: var(--accent); }
.drawer-nav-item:hover svg { color: var(--text); }
.drawer-section-label {
  font-size: 10px; font-weight: 700; color: var(--muted); text-transform: uppercase;
  letter-spacing: 0.08em; padding: 10px 14px 4px;
}
.drawer-footer {
  padding: 16px; border-top: 1px solid var(--border);
  display: flex; align-items: center; gap: 10px;
}
.drawer-avatar {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg,rgba(0,229,255,0.2),rgba(167,139,250,0.2));
  border: 1px solid rgba(0,229,255,0.25);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: var(--accent);
}
.drawer-user-info { flex: 1; min-width: 0; }
.drawer-user-name { font-size: 13px; font-weight: 600; color: var(--text); }
.drawer-user-sub  { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Floating QR Button ───────────────────── */
.fab-qr {
  position: fixed; bottom: 24px; left: 20px; z-index: 300;
  width: 50px; height: 50px; border-radius: 15px;
  background: var(--surface2);
  border: 1px solid rgba(0,229,255,0.2); cursor: pointer; color: var(--accent);
  box-shadow: 0 4px 16px rgba(0,0,0,0.4), 0 0 12px rgba(0,229,255,0.06), inset 0 1px 0 rgba(255,255,255,0.06);
  display: none; align-items: center; justify-content: center;
  transition: transform 0.15s, box-shadow 0.15s;
}
.fab-qr:active { transform: scale(0.93); }
.fab-add {
  position: fixed; bottom: 24px; right: 20px; z-index: 300;
  width: 50px; height: 50px; border-radius: 15px;
  background: linear-gradient(135deg, #00e5ff 0%, #00b0c8 100%);
  border: none; cursor: pointer; color: #000;
  box-shadow: 0 4px 20px rgba(0,229,255,0.35), 0 2px 8px rgba(0,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.3);
  display: none; align-items: center; justify-content: center;
  transition: transform 0.15s, box-shadow 0.15s;
}
.fab-add:active { transform: scale(0.93); }

/* ── Hamburger button in header ───────────── */
.hamburger-btn {
  display: none; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 9px; cursor: pointer;
  background: var(--surface2); border: 1px solid var(--border);
  color: var(--fg2); transition: color 0.15s, background 0.15s; flex-shrink: 0;
}
.hamburger-btn:hover { color: var(--text); background: var(--surface3); }


/* ── Date Range Filter ───────────────────── */
.date-range-strip {
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
  margin-bottom: 0;
}
.date-preset {
  padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700;
  border: 1px solid var(--border2); background: var(--surface2);
  color: var(--muted); cursor: pointer; font-family: 'Outfit', sans-serif;
  transition: all 0.15s;
}
.date-preset:hover { color: var(--text); border-color: rgba(0,229,255,0.3); }
.date-preset.active {
  background: rgba(0,229,255,0.1); border-color: rgba(0,229,255,0.35);
  color: var(--accent);
}
.date-range-custom {
  display: none; align-items: center; gap: 6px; flex-wrap: wrap;
}
.date-range-custom.open { display: flex; }

@media (max-width: 767px) {
  .date-range-label { display: none; }
  .date-range-strip { gap: 4px; margin-top: 8px; }
}
.date-range-custom input[type="date"] {
  padding: 5px 10px; font-size: 12px; width: auto; min-width: 130px;
}
.date-range-label {
  font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;
  letter-spacing: 0.05em; white-space: nowrap;
}

/* ── Custom Fields ───────────────────────── */
.custom-fields-row {
  display: flex; flex-wrap: wrap; gap: 6px;
  margin-top: 8px; padding-top: 8px;
  border-top: 1px solid var(--border);
}
.custom-field-chip {
  display: flex; align-items: center; gap: 5px;
  font-size: 11px; padding: 3px 9px; border-radius: 6px;
  background: rgba(167,139,250,0.08); border: 1px solid rgba(167,139,250,0.2);
  color: var(--purple);
}
.custom-field-chip-label { color: var(--muted); font-weight: 500; }
.custom-field-chip-value { font-weight: 700; }

/* ── Custom Field Defs in Settings ──────── */
.cf-def-row {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 0; border-bottom: 1px solid var(--border);
}
.cf-def-row:last-child { border-bottom: none; }
.cf-def-type { font-size: 11px; color: var(--muted); background: var(--surface3);
  border: 1px solid var(--border2); border-radius: 5px; padding: 2px 7px; font-weight: 600; }

</style>
</head>
<body>

<!-- TOP BAR -->
<header class="top-bar">
  <button class="hamburger-btn" onclick="openDrawer()" aria-label="Menu">
    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>
  <div class="logo" onclick="showPage('dashboard')">
    <div class="logo-mark">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Monitor screen -->
        <rect x="1" y="2" width="14" height="10" rx="1.5" stroke="#00e5ff" stroke-width="1.3"/>
        <!-- Monitor stand -->
        <path d="M5 12v2M9 12v2M5 14h4" stroke="#00e5ff" stroke-width="1.3" stroke-linecap="round"/>
        <!-- IQ badge -->
        <circle cx="15.5" cy="14.5" r="4" fill="#0a0c10" stroke="#00e5ff" stroke-width="1.2"/>
        <text x="15.5" y="17.2" text-anchor="middle" font-size="5.5" font-weight="800" font-family="DM Sans,system-ui,sans-serif" fill="#00e5ff">IQ</text>
        <!-- Pulse dot -->
        <circle cx="11.5" cy="7" r="1" fill="#00e5ff" opacity="0.9"/>
        <line x1="4" y1="7" x2="8.5" y2="7" stroke="#00e5ff" stroke-width="1" stroke-linecap="round" opacity="0.4"/>
        <line x1="12.5" y1="7" x2="13" y2="7" stroke="#00e5ff" stroke-width="1" stroke-linecap="round" opacity="0.4"/>
      </svg>
    </div>
    <div class="logo-wordmark">
      <div class="logo-wordmark-top">Asset<span>IQ</span></div>
      <div class="logo-wordmark-sub">IT Asset Manager</div>
    </div>
  </div>
  <button class="top-add-btn" onclick="openAddModal()">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
    Add
  </button>
  <nav class="desktop-nav">
    <button class="nav-btn active" id="dnav-dashboard" onclick="showPage('dashboard')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span class="nav-label">Dashboard</span>
    </button>
    <button class="nav-btn" id="dnav-assets" onclick="showPage('assets')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      <span class="nav-label">Assets</span>
    </button>
    <button class="nav-btn" id="dnav-archive" onclick="showPage('archive')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
      <span class="nav-label">Archive</span>
    </button>
    <button class="nav-btn" id="dnav-activity" onclick="showPage('activity')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      <span class="nav-label">Activity</span>
    </button>
    <button class="nav-btn" id="dnav-reports" onclick="showPage('reports')">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      <span class="nav-label">Reports</span>
    </button>
    <button class="nav-btn" id="dnav-settings" onclick="showPage('settings')" style="position:relative">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span class="nav-label">Settings</span>
      <span id="alert-badge" style="display:none;position:absolute;top:4px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red)"></span>
    </button>
    <button class="btn btn-primary desktop-add-btn" onclick="openAddModal()" style="padding:7px 16px;font-size:13px;min-height:auto;border-radius:8px;width:auto;margin-left:4px">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Add Asset
    </button>
    <div style="display:flex;align-items:center;gap:8px;margin-left:8px;padding-left:12px;border-left:1px solid var(--border)">
      <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,rgba(0,229,255,0.2),rgba(167,139,250,0.2));border:1px solid rgba(0,229,255,0.25);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--accent)">
        <?= strtoupper(substr($user['name'], 0, 1)) ?>
      </div>
      <div style="display:flex;flex-direction:column;line-height:1.2">
        <span style="font-size:12px;font-weight:600"><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></span>
        <span style="font-size:10px;color:var(--muted)"><?= htmlspecialchars($user['dept'] ?: $user['title'] ?: $user['email']) ?></span>
      </div>
      <a href="/auth/logout.php" title="Sign out" style="display:flex;align-items:center;color:var(--muted);padding:4px;border-radius:6px;transition:color 0.2s" onmouseover="this.style.color='var(--red)'" onmouseout="this.style.color='var(--muted)'">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </nav>
</header>

<!-- MOBILE DRAWER OVERLAY -->
<div class="drawer-overlay" id="drawer-overlay" onclick="closeDrawer()"></div>

<!-- MOBILE DRAWER -->
<div class="drawer" id="mobile-drawer">
  <div class="drawer-header">
    <div class="drawer-logo">
      <svg width="22" height="22" viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="rgba(0,229,255,0.12)"/><rect x="7" y="7" width="8" height="8" rx="2" fill="var(--accent)"/><rect x="17" y="7" width="8" height="8" rx="2" fill="rgba(0,229,255,0.5)"/><rect x="7" y="17" width="8" height="8" rx="2" fill="rgba(0,229,255,0.5)"/><rect x="17" y="17" width="8" height="8" rx="2" fill="rgba(0,229,255,0.3)"/></svg>
      AssetIQ
    </div>
    <div class="drawer-close" onclick="closeDrawer()">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </div>
  </div>

  <div class="drawer-nav">
    <div class="drawer-section-label">Main</div>
    <div class="drawer-nav-item active" id="nav-dashboard" onclick="showPage('dashboard');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </div>
    <div class="drawer-nav-item" id="nav-assets" onclick="showPage('assets');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Assets
    </div>
    <div class="drawer-nav-item" id="nav-archive" onclick="showPage('archive');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
      Archive
    </div>

    <div class="drawer-section-label" style="margin-top:8px">Insights</div>
    <div class="drawer-nav-item" id="nav-reports" onclick="showPage('reports');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Reports
    </div>
    <div class="drawer-nav-item" id="nav-activity" onclick="showPage('activity');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Activity
    </div>

    <div class="drawer-section-label" style="margin-top:8px">Admin</div>
    <div class="drawer-nav-item" id="nav-users" onclick="showPage('users');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Users
    </div>
    <div class="drawer-nav-item" id="nav-intune" onclick="showPage('intune');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
      Intune
    </div>
    <div class="drawer-nav-item" id="nav-settings" onclick="showPage('settings');closeDrawer()" style="position:relative">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Settings
      <span id="alert-badge-mob" style="display:none" class="nav-badge">!</span>
    </div>

    <div class="drawer-section-label" style="margin-top:8px">Tools</div>
    <div class="drawer-nav-item" onclick="openAddModal();closeDrawer()" style="color:var(--accent);border:1px solid rgba(0,229,255,0.18);background:rgba(0,229,255,0.06)">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      Add Asset
    </div>
    <div class="drawer-nav-item" id="nav-scan" onclick="showPage('scan');closeDrawer()">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
      Scan QR
    </div>
  </div>

  <div class="drawer-footer">
    <div class="drawer-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
    <div class="drawer-user-info">
      <div class="drawer-user-name"><?= htmlspecialchars(explode(' ', $user['name'])[0]) ?></div>
      <div class="drawer-user-sub"><?= htmlspecialchars($user['dept'] ?: $user['title'] ?: $user['email']) ?></div>
    </div>
    <a href="/auth/logout.php" title="Sign out" style="color:var(--muted);padding:6px;border-radius:6px;transition:color 0.2s" onmouseover="this.style.color='var(--red)'" onmouseout="this.style.color='var(--muted)'">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</div>

<!-- FLOATING BUTTONS (mobile only) -->
<button class="fab-qr" id="fab-qr" onclick="showPage('scan')" aria-label="Scan QR">
  <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
</button>
<button class="fab-add" id="fab-add" onclick="openAddModal()" aria-label="Add Asset">
  <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
</button>

<!-- DASHBOARD -->
<div class="page active" id="page-dashboard">
  <!-- Low stock alert banner -->
  <div id="alert-banner" style="display:none;margin-bottom:16px;padding:12px 16px;background:rgba(255,59,92,0.08);border:1px solid rgba(255,59,92,0.25);border-radius:12px;display:none">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <svg width="16" height="16" fill="none" stroke="var(--red)" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <span style="font-weight:700;color:var(--red);font-size:13px">Low Stock Alert</span>
      <span id="alert-banner-text" style="font-size:13px;color:var(--fg2)"></span>
      <button onclick="showPage('settings')" style="margin-left:auto;font-size:12px;padding:4px 10px;background:rgba(255,59,92,0.12);border:1px solid rgba(255,59,92,0.3);color:var(--red);border-radius:6px;cursor:pointer;font-family:'Outfit',sans-serif;font-weight:600">Manage Thresholds</button>
    </div>
  </div>
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
    <div>
      <div class="page-title" style="margin-bottom:2px">Dashboard</div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span class="page-sub" style="margin-bottom:0">IT Asset Overview</span>
        <button id="archived-pill" onclick="showPage('archive')" style="font-size:11px;font-weight:700;color:var(--amber);background:rgba(255,180,0,0.08);border:1px solid rgba(255,180,0,0.2);border-radius:20px;padding:2px 10px;cursor:pointer;font-family:'Outfit',sans-serif;transition:background 0.15s">0 archived</button>
      </div>
    </div>
    <!-- Date range filter — inline with header -->
    <div class="date-range-strip" id="date-range-strip" style="margin-bottom:0">
      <span class="date-range-label">Purchased:</span>
      <button class="date-preset active" id="preset-all"    onclick="setDatePreset('all')">All</button>
      <button class="date-preset"        id="preset-30d"    onclick="setDatePreset('30d')">30d</button>
      <button class="date-preset"        id="preset-90d"    onclick="setDatePreset('90d')">90d</button>
      <button class="date-preset"        id="preset-1yr"    onclick="setDatePreset('1yr')">1yr</button>
      <button class="date-preset"        id="preset-custom" onclick="setDatePreset('custom')">Custom…</button>
      <div class="date-range-custom" id="date-range-custom">
        <input type="date" id="range-from" onchange="applyCustomRange()">
        <span style="color:var(--muted);font-size:12px">→</span>
        <input type="date" id="range-to"   onchange="applyCustomRange()">
      </div>
    </div>
  </div>
  <div class="stats-grid" id="stats-grid">
    <div class="stat-card" style="grid-column:span 2"><div class="spinner"></div></div>
  </div>
  <div id="eol-banner-dash"></div>
  <div class="section-label">Recent Assets</div>
  <div class="asset-list" id="recent-list"><div class="spinner"></div></div>
  <div class="table-wrap">
    <table><thead><tr><th>Asset ID</th><th>Name</th><th>Type</th><th>Assigned To</th><th>Status</th></tr></thead>
    <tbody id="recent-tbody"><tr><td colspan="5"><div class="spinner"></div></td></tr></tbody></table>
  </div>
</div>

<!-- ASSETS -->
<div class="page" id="page-assets">
  <div class="page-title">Assets</div>
  <div class="page-sub">All IT equipment</div>
  <div class="filter-bar">
    <div class="search-wrap">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="search-input" placeholder="Search name, serial, user…">
    </div>
    <div class="filter-row">
      <select id="filter-type">
        <option value="">All Types</option>
        <option>Laptop</option><option>Desktop</option>
        <option>Monitor</option><option>Peripheral</option>
        <option>Docking Station</option>
        <option>Printer</option><option>Camera</option>
      </select>
      <select id="filter-dept">
        <option value="">All Departments</option>
        <option>IT</option><option>Finance</option>
        <option>Claims</option><option>Management</option><option>Marketing</option>
        <option>Underwriting</option><option>Agent</option>
        <option>No Longer At SEM</option>
      </select>
      <select id="filter-status">
        <option value="">All Status</option>
        <option value="assigned">Assigned</option>
        <option value="unassigned">Unassigned</option>
        <option value="retired">Retired</option>
        <option value="eol">End of Life</option>
      </select>
      <button class="btn btn-ghost" onclick="exportCSV()" style="width:auto;min-width:auto;padding:12px 14px;min-height:44px;flex-shrink:0">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        CSV
      </button>
      <button class="btn btn-ghost" onclick="exportADP(this)" style="width:auto;min-width:auto;padding:12px 14px;min-height:44px;flex-shrink:0;border-color:rgba(0,229,255,0.3);color:var(--accent)">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        ADP Export
      </button>
      <button class="select-all-btn" id="batch-toggle-btn" onclick="toggleBatchMode()">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Select
      </button>
    </div>
  </div>
  <div class="asset-list" id="assets-list"><div class="spinner"></div></div>

  <!-- Batch action bar -->
  <div class="batch-bar" id="batch-bar">
    <div class="batch-count" id="batch-count">0 selected</div>
    <div class="batch-actions">
      <button class="batch-btn batch-btn-reassign" onclick="openBatchReassign()">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Reassign
      </button>
      <button class="batch-btn batch-btn-ai" onclick="batchEstimate()">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        AI Estimate
      </button>
      <button class="batch-btn batch-btn-del" onclick="batchDelete()">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
        Delete
      </button>
      <button class="batch-btn batch-btn-cancel" onclick="cancelBatchMode()">Cancel</button>
    </div>
  </div>

  <!-- Batch progress overlay -->
  <div class="batch-progress" id="batch-progress">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <span id="batch-progress-label" style="font-size:13px;font-weight:700;color:var(--accent)">Estimating prices…</span>
      <span id="batch-progress-count" style="font-size:12px;color:var(--muted)">0 / 0</span>
    </div>
    <div class="batch-progress-bar-wrap">
      <div class="batch-progress-bar" id="batch-progress-bar" style="width:0%"></div>
    </div>
    <div id="batch-progress-detail" style="font-size:11px;color:var(--muted)">Starting…</div>
  </div>
  <div id="asset-empty" class="empty-state" style="display:none">
    <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.3"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
    <h3>No assets found</h3>
    <p style="font-size:13px">Adjust filters or tap Add in the top-right.</p>
  </div>
  <div class="table-wrap" id="assets-table-wrap">
    <table>
      <thead><tr>
        <th onclick="sortBy('id')">ID ↕</th>
        <th onclick="sortBy('name')">Name ↕</th>
        <th onclick="sortBy('type')">Type ↕</th>
        <th>Serial</th>
        <th onclick="sortBy('assigned_to')">Assigned To ↕</th>
        <th onclick="sortBy('purchase_date')">Purchase Date ↕</th>
        <th onclick="sortBy('end_of_life')">End of Life ↕</th>
        <th onclick="sortBy('cost')">Cost ↕</th>
        <th>Actions</th>
      </tr></thead>
      <tbody id="assets-tbody"><tr><td colspan="8"><div class="spinner"></div></td></tr></tbody>
    </table>
  </div>
</div>

<!-- SCAN -->
<div class="page" id="page-scan">
  <div class="scan-wrap">
    <div class="page-title">Scan QR Code</div>
    <div class="page-sub">Point camera at any asset label to look it up instantly.</div>
    <div id="reader"><div style="color:var(--muted);font-size:13px;text-align:center;padding:20px">Camera will appear here</div></div>
    <button class="btn btn-primary" id="scan-btn" onclick="toggleScanner()">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/></svg>
      Start Camera
    </button>
    <div class="scan-result" id="scan-result"></div>
  </div>
</div>

<!-- ADD/EDIT MODAL -->
<div class="overlay" id="asset-modal">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-header">
      <div class="modal-title" id="modal-title">Add Asset</div>
      <button class="close-btn" onclick="closeModal('asset-modal')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-stack">
        <div class="form-group full">
          <label class="form-label">Asset Name / Model *</label>
          <input type="text" id="f-name" placeholder="e.g. Dell XPS 15 9530" autocomplete="off">
        </div>
        <div class="form-2col">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select id="f-type" onchange="loadCustomFieldsForModal(this.value, editingId)">
              <option>Laptop</option><option>Desktop</option>
              <option>Monitor</option><option>Peripheral</option>
              <option>Docking Station</option>
              <option>Printer</option><option>Camera</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Serial Number</label>
            <input type="text" id="f-serial" placeholder="SN-XXXX" autocomplete="off">
          </div>
          <div class="form-group">
            <label class="form-label">Assigned To</label>
            <input type="text" id="f-assigned" placeholder="Name or leave blank">
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select id="f-dept">
              <option value="">— Unassigned —</option>
              <option>IT</option><option>Finance</option>
              <option>Claims</option><option>Management</option><option>Marketing</option>
              <option>Underwriting</option><option>Agent</option>
              <option>No Longer At SEM</option>
            </select>
            <label class="form-label">Status</label>
            <select id="f-status">
              <option value="active">Active</option>
              <option value="retired">Retired</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Purchase Date</label>
            <input type="date" id="f-date" oninput="autoSetEOL()">
          </div>
          <div class="form-group">
            <label class="form-label">End of Life Date <span style="color:var(--muted);font-weight:400;font-size:9px">(auto: +6 yrs)</span></label>
            <input type="date" id="f-eol">
          </div>
          <div class="form-group">
            <label class="form-label">Cost ($)</label>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="number" id="f-cost" placeholder="0.00" step="0.01" min="0" style="flex:1">
              <button type="button" id="ai-price-btn" onclick="getAIPrice()" title="Get AI price estimate" style="flex-shrink:0;height:40px;padding:0 12px;background:linear-gradient(135deg,rgba(0,229,255,0.12),rgba(167,139,250,0.12));border:1px solid rgba(0,229,255,0.25);border-radius:8px;color:var(--accent);font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px;transition:all 0.2s">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                AI Estimate
              </button>
            </div>
            <div id="ai-price-result" style="display:none;margin-top:8px;padding:10px 12px;background:rgba(0,229,255,0.06);border:1px solid rgba(0,229,255,0.15);border-radius:8px;font-size:12px;line-height:1.5"></div>
          </div>
        </div>
        <div class="form-group full">
          <label class="form-label">Notes</label>
          <textarea id="f-notes" placeholder="Any additional notes…"></textarea>
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:4px 0">
            <input type="checkbox" id="f-eol-override" style="width:18px;height:18px;flex-shrink:0;accent-color:var(--accent);appearance:auto;-webkit-appearance:checkbox;padding:0;min-height:unset;background:transparent;box-shadow:none;border:none;cursor:pointer;">
            <span style="font-size:13px;font-weight:600">Acknowledge EOL warning <span style="font-weight:400;color:var(--muted)">(silences overdue flag)</span></span>
          </label>
        </div>
        <!-- Custom date fields (injected dynamically by loadCustomFieldsForModal) -->
        <div id="custom-fields-modal-section" style="display:none">
          <div style="height:1px;background:var(--border);margin:4px 0 12px"></div>
          <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px">Custom Fields</div>
          <div id="custom-fields-modal-inputs" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px"></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" id="save-btn" onclick="saveAsset()">Save Asset</button>
      <button class="btn btn-ghost" onclick="closeModal('asset-modal')">Cancel</button>
    </div>
  </div>
</div>

<!-- BULK REASSIGN MODAL -->
<div class="overlay" id="reassign-modal">
  <div class="modal" style="max-width:440px">
    <div class="modal-handle"></div>
    <div class="modal-header">
      <div>
        <div class="modal-title">Bulk Reassign</div>
        <div style="font-size:12px;color:var(--muted);margin-top:2px" id="reassign-modal-sub">0 assets selected</div>
      </div>
      <button class="close-btn" onclick="closeModal('reassign-modal')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:18px;line-height:1.6">Update the assigned user and/or department for all selected assets. Leave a field blank to keep existing values.</p>
      <div class="form-stack" style="display:flex;flex-direction:column;gap:14px">
        <div class="form-group">
          <label class="form-label">Assigned To <span style="color:var(--muted);font-weight:400">(leave blank to keep existing)</span></label>
          <input type="text" id="reassign-user" placeholder="e.g. John Smith or leave blank">
        </div>
        <div class="form-group">
          <label class="form-label">Department <span style="color:var(--muted);font-weight:400">(leave blank to keep existing)</span></label>
          <select id="reassign-dept">
            <option value="">— Keep existing —</option>
            <option value="__clear__">— Clear department —</option>
            <option>IT</option><option>Finance</option>
            <option>Claims</option><option>Management</option><option>Marketing</option>
            <option>Underwriting</option><option>Agent</option>
            <option>No Longer At SEM</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status <span style="color:var(--muted);font-weight:400">(leave blank to keep existing)</span></label>
          <select id="reassign-status">
            <option value="">— Keep existing —</option>
            <option value="active">Active</option>
            <option value="retired">Retired</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer" style="flex-direction:row;justify-content:flex-end;gap:10px">
      <button class="btn btn-primary" id="reassign-save-btn" onclick="batchReassign()" style="width:auto;min-width:140px">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Apply to Selected
      </button>
      <button class="btn btn-ghost" onclick="closeModal('reassign-modal')" style="width:auto;min-width:90px">Cancel</button>
    </div>
  </div>
</div>

<!-- QR MODAL -->
<div class="overlay" id="qr-modal">
  <div class="modal" style="max-width:420px">
    <div class="modal-handle"></div>
    <div class="modal-header">
      <div class="modal-title">Asset QR Code</div>
      <button class="close-btn" onclick="closeModal('qr-modal')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="qr-container">
        <div class="qr-box" id="qr-canvas-wrap"></div>
        <div id="qr-info"></div>
        <button class="btn btn-primary" onclick="downloadQR()">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Download PNG
        </button>
        <button class="btn btn-ghost" onclick="printQR()">Print Label</button>
      </div>
    </div>
  </div>
</div>

<!-- USERS PAGE -->
<div class="page" id="page-users">
  <div class="page-title">Users</div>
  <div class="page-sub">Assets grouped by assigned user or department</div>

  <div class="filter-bar">
    <div class="search-wrap" style="flex:1">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="users-search" placeholder="Search…" oninput="filterUsers()">
    </div>
    <div class="filter-row" style="gap:8px">
      <button class="btn btn-primary" id="users-view-user" onclick="setUsersView('user')" style="width:auto;padding:10px 14px;font-size:12px;min-height:40px">By User</button>
      <button class="btn btn-ghost"   id="users-view-dept" onclick="setUsersView('dept')" style="width:auto;padding:10px 14px;font-size:12px;min-height:40px">By Department</button>
    </div>
  </div>

  <div id="users-list"><div class="spinner"></div></div>
</div>

<!-- USER DETAIL MODAL -->
<div class="overlay" id="user-modal">
  <div class="modal" style="max-width:580px">
    <div class="modal-handle"></div>
    <div class="modal-header">
      <div>
        <div class="modal-title" id="user-modal-name"></div>
        <div style="font-size:12px;color:var(--muted);margin-top:2px" id="user-modal-count"></div>
      </div>
      <button class="close-btn" onclick="closeModal('user-modal')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body" id="user-modal-body"></div>
  </div>
</div>

<!-- INTUNE SYNC PAGE -->
<div class="page" id="page-intune">
  <div class="page-title">Intune Sync</div>
  <div class="page-sub">Import devices directly from Microsoft Intune</div>

  <!-- Unconfigured state -->
  <div id="intune-unconfigured" style="display:none">
    <div class="intune-setup-card">
      <div class="intune-setup-icon">
        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
      </div>
      <h3>Connect to Microsoft Intune</h3>
      <p>Add your Azure App Registration credentials to <code>config.php</code> to enable Intune sync.</p>
      <div class="setup-steps">
        <div class="setup-step"><span class="step-num">1</span><div><strong>Register an app</strong> in Azure Portal → Entra ID → App registrations → New registration</div></div>
        <div class="setup-step"><span class="step-num">2</span><div><strong>Add API permission:</strong> Microsoft Graph → Application → <code>DeviceManagementManagedDevices.Read.All</code> → Grant admin consent</div></div>
        <div class="setup-step"><span class="step-num">3</span><div><strong>Create a client secret</strong> under Certificates &amp; secrets and copy the value</div></div>
        <div class="setup-step"><span class="step-num">4</span><div><strong>Edit config.php</strong> and fill in <code>INTUNE_TENANT_ID</code>, <code>INTUNE_CLIENT_ID</code>, <code>INTUNE_CLIENT_SECRET</code></div></div>
      </div>
    </div>
  </div>

  <!-- Configured state -->
  <div id="intune-configured">
    <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center">
      <button class="btn btn-primary" id="intune-fetch-btn" onclick="intuneFetch()" style="width:auto;min-width:160px">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        Fetch from Intune
      </button>
      <button class="btn btn-ghost" onclick="intuneTestConnection()" style="width:auto">Test Connection</button>
      <div id="intune-status" style="font-size:13px;color:var(--muted)"></div>
    </div>

    <!-- Filter bar -->
    <div id="intune-filter-bar" style="display:none;margin-bottom:14px">
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <div class="search-wrap" style="flex:1;min-width:200px">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" id="intune-search" placeholder="Search devices…" oninput="renderIntuneDevices()">
        </div>
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;white-space:nowrap">
          <input type="checkbox" id="intune-hide-existing" onchange="renderIntuneDevices()" style="width:auto;padding:0;background:none;border:none"> Hide already imported
        </label>
        <button class="btn btn-primary" onclick="intuneImportSelected()" style="width:auto;min-width:140px" id="intune-import-btn">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
          Import Selected
        </button>
      </div>
      <div style="margin-top:10px;font-size:12px;color:var(--muted)" id="intune-selection-info"></div>
    </div>

    <!-- Device list -->
    <div id="intune-device-list"></div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
const API          = 'api/assets.php';
const SETTINGS_API = 'api/settings.php';
const LINKS_API    = 'api/links.php';
const FIELDS_API   = 'api/fields.php';
const T={Laptop:'badge-laptop',Desktop:'badge-desktop',Monitor:'badge-monitor',Peripheral:'badge-peripheral','Docking Station':'badge-docking',Printer:'badge-printer',Camera:'badge-camera'};
let editingId=null, sortKey='id', sortAsc=true;
let scannerActive=false, html5QrCode=null, searchTimer=null;

async function apiFetch(url, opts={}) {
  try {
    const res = await fetch(url, {headers:{'Content-Type':'application/json'}, ...opts});
    if (!res.ok) {
      let msg = 'API error ' + res.status;
      try { const d = await res.json(); msg = d.error || msg; } catch(_) {}
      throw new Error(msg);
    }
    return await res.json();
  } catch(e) {
    if (e.name !== 'AbortError') toast(e.message, 'error');
    throw e;
  }
}

function showPage(name) {
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  ['nav-','dnav-'].forEach(pre => {
    document.querySelectorAll('[id^="'+pre+'"]').forEach(b=>{
      b.classList.remove('active','nav-flash');
    });
    const el = document.getElementById(pre+name);
    if(el) {
      el.classList.add('active');
      if(el.tagName === 'BUTTON') {
        requestAnimationFrame(() => {
          el.classList.add('nav-flash');
          el.addEventListener('animationend', () => el.classList.remove('nav-flash'), {once:true});
        });
      }
    }
  });
  const page = document.getElementById('page-'+name);
  if(page) page.classList.add('active');
  if(name==='dashboard') { loadDashboard(); checkAlerts(); }
  if(name==='assets')    loadAssets();
  if(name==='intune') { showPage('settings'); setTimeout(()=>switchSettingsTab('intune'),50); return; }
  if(name==='users')     loadUsers();
  if(name==='archive')   loadArchive();
  if(name==='activity')  loadActivity();
  if(name==='reports')   loadReports();
  if(name==='settings')  loadSettings();
  if(name!=='scan' && scannerActive) stopScanner();
}

async function getAIPrice() {
  const btn       = document.getElementById('ai-price-btn');
  const resultEl  = document.getElementById('ai-price-result');

  const name         = document.getElementById('f-name').value.trim();
  const type         = document.getElementById('f-type').value;
  const serial       = document.getElementById('f-serial').value.trim();
  const purchaseDate = document.getElementById('f-date').value;  // fixed: was f-purchase
  const notes        = document.getElementById('f-notes').value.trim();

  if (!name && !serial) {
    resultEl.style.display = 'block';
    resultEl.innerHTML = '<span style="color:var(--orange)">⚠ Please enter a name or serial number first.</span>';
    return;
  }

  const assetAge = purchaseDate
    ? (() => { const yrs = ((Date.now() - new Date(purchaseDate)) / 31557600000).toFixed(1); return `purchased ${purchaseDate} (${yrs} years ago)`; })()
    : 'purchase date unknown';

  // Show loading state
  btn.disabled = true;
  btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Estimating…`;
  resultEl.style.display = 'block';
  resultEl.innerHTML = `
    <div style="display:flex;align-items:center;gap:10px;color:var(--muted)">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite;flex-shrink:0"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
      <div>
        <div style="color:var(--text);font-weight:600;font-size:12px">Analysing asset…</div>
        <div style="font-size:11px;margin-top:2px">Looking up <strong>${esc(name||serial)}</strong> · ${esc(type)} · ${assetAge}</div>
      </div>
    </div>`;

  try {
    const res = await fetch('api/ai_price.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, type, serial, purchaseDate, notes })
    });

    const est = await res.json();
    if (!res.ok) throw new Error(est.error || 'Estimate failed');

    const confidenceColor = {high:'var(--green)', medium:'var(--orange)', low:'var(--red)'}[est.confidence] || 'var(--muted)';
    const confidenceLabel = {high:'High confidence', medium:'Medium confidence', low:'Low confidence'}[est.confidence] || '';

    resultEl.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <div style="font-size:18px;font-weight:700;color:var(--text)">
          $${Number(est.low).toLocaleString()} – $${Number(est.high).toLocaleString()}
          <span style="font-size:11px;font-weight:400;color:var(--muted);margin-left:4px">${est.currency}</span>
        </div>
        <span style="font-size:10px;font-weight:600;color:${confidenceColor};background:${confidenceColor}18;padding:2px 8px;border-radius:20px">${confidenceLabel}</span>
      </div>
      <div style="color:var(--muted);font-size:12px;margin-bottom:8px">${est.reasoning}</div>
      ${est.caveat ? `<div style="color:var(--orange);font-size:11px;margin-bottom:8px">⚠ ${est.caveat}</div>` : ''}
      <button onclick="document.getElementById('f-cost').value='${est.midpoint}';document.getElementById('ai-price-result').style.display='none'" style="font-size:11px;padding:5px 10px;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.2);border-radius:6px;color:var(--accent);cursor:pointer;font-weight:600">
        Use midpoint ($${Number(est.midpoint).toLocaleString()})
      </button>`;

  } catch (err) {
    resultEl.innerHTML = `<span style="color:var(--red)">⚠ ${err.message||'Could not get estimate — check your API key in config.php.'}</span>`;
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg> AI Estimate`;
  }
}

function goToAssets({type='', status='', dept=''}={}) {
  const ft = document.getElementById('filter-type');
  const fs = document.getElementById('filter-status');
  const fd = document.getElementById('filter-dept');
  const si = document.getElementById('search-input');
  if (ft) ft.value = type;
  if (fs) fs.value = status;
  if (fd) fd.value = dept;
  if (si) si.value = '';
  // For EOL, sort by end_of_life ascending so most urgent appear first
  if (status === 'eol') { sortKey = 'end_of_life'; sortAsc = true; }
  showPage('assets');
}

async function loadDashboard() {
  // Build stats URL with active date range
  const statsUrl = buildStatsUrl();
  let stats, recent;
  try {
    [stats,recent] = await Promise.all([
      apiFetch(statsUrl),
      apiFetch(API+'?sort=created_at&dir=desc')
    ]);
  } catch(e) {
    document.getElementById('recent-list').innerHTML='<div class="empty-state"><h3>Failed to load</h3><p>'+e.message+'</p></div>';
    return;
  }
  const cards=[
    {label:'Total Assets', value:stats.total,       sub:stats.assigned+' assigned',  c:'var(--accent)', action:()=>goToAssets({})},
    {label:'Unassigned',   value:stats.unassigned,  sub:'Available now',             c:'var(--orange)', action:()=>goToAssets({status:'unassigned'})},
    {label:'Computers',    value:(stats.byType['Laptop']||0)+(stats.byType['Desktop']||0), sub:'Laptops & desktops', c:'var(--purple)', action:()=>goToAssets({type:'Laptop'})},
    {label:'Retired',      value:stats.retired||0,  sub:'Decommissioned',            c:'var(--red)',    action:()=>goToAssets({status:'retired'})},
    {label:'Total Value',  value:'$'+Number(stats.totalCost).toLocaleString('en-US',{maximumFractionDigits:0}), sub:'Inventory cost basis', c:'var(--green)'},
  ];
  document.getElementById('stats-grid').innerHTML=cards.map((card,i)=>
    `<div class="stat-card${card.action?' clickable':''}" style="--c:${card.c}" ${card.action?`onclick="statCardActions[${i}]()"`:''}>
      <div class="card-spotlight"></div>
      <div class="stat-label">${card.label}</div>
      <div class="stat-value">${card.value}</div>
      <div class="stat-sub">${card.sub}</div>
    </div>`).join('');
  window.statCardActions = cards.map(card=>card.action||null);
  // Update archived link pill
  const arEl = document.getElementById('archived-pill');
  if(arEl) arEl.textContent = (stats.archived||0) + ' archived';

  // EOL banner
  const expiringSoon = recent.filter(a => !a.eolOverride && eolStatus(a.endOfLife) !== null);
  const critical     = expiringSoon.filter(a => !a.eolOverride && eolStatus(a.endOfLife) === 'critical');
  const banner = document.getElementById('eol-banner-dash');
  if (critical.length) {
    banner.innerHTML = `<div class="eol-banner" onclick="goToAssets({status:'eol'})" style="cursor:pointer"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="0.5" fill="currentColor"/></svg> ${critical.length} asset${critical.length>1?'s':''} past end of life — replacement overdue <span style="font-weight:700;opacity:0.7;margin-left:4px">View →</span></div>`;
  } else if (expiringSoon.length) {
    banner.innerHTML = `<div class="eol-banner warn" onclick="goToAssets({status:'eol'})" style="cursor:pointer"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><circle cx="12" cy="17" r="0.5" fill="currentColor"/></svg> ${expiringSoon.length} asset${expiringSoon.length>1?'s':''} approaching end of life <span style="font-weight:700;opacity:0.7;margin-left:4px">View →</span></div>`;
  } else {
    banner.innerHTML = '';
  }

  await prefetchCustomFields(recent);
  const slice=recent.slice(0,6);
  document.getElementById('recent-list').innerHTML=slice.length?slice.map(a=>assetCard(a)).join(''):'<div class="empty-state"><h3>No assets yet</h3><p>Tap Add to get started.</p></div>';
  document.getElementById('recent-tbody').innerHTML=slice.length?slice.map(a=>`<tr>
    <td class="font-mono">${esc(a.id)}</td><td style="font-weight:600">${esc(a.name)}</td>
    <td>${typeBadge(a.type)}</td>
    <td>${a.assignedTo?esc(a.assignedTo):'<span style="color:var(--muted)">—</span>'}</td>
    <td>${eolFlag(a.endOfLife)||statusBadge(a.assignedTo, a.status)}</td></tr>`).join(''):`<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:40px">No assets yet.</td></tr>`;
}

async function loadAssets() {
  const q=document.getElementById('search-input')?.value||'';
  const ft=document.getElementById('filter-type')?.value||'';
  const fs=document.getElementById('filter-status')?.value||'';
  const fd=document.getElementById('filter-dept')?.value||'';
  const p=new URLSearchParams();
  if(q)p.set('q',q); if(ft)p.set('type',ft); if(fs)p.set('status',fs); if(fd)p.set('dept',fd);
  p.set('sort',sortKey); p.set('dir',sortAsc?'asc':'desc');
  document.getElementById('assets-list').innerHTML='<div class="spinner"></div>';
  document.getElementById('assets-tbody').innerHTML=`<tr><td colspan="8"><div class="spinner"></div></td></tr>`;
  document.getElementById('asset-empty').style.display='none';
  let assets;
  try {
    assets = await apiFetch(API+'?'+p.toString());
  } catch(e) {
    document.getElementById('assets-list').innerHTML='<div class="empty-state"><h3>Failed to load</h3><p>'+e.message+'</p></div>';
    document.getElementById('assets-tbody').innerHTML='<tr><td colspan="8" style="text-align:center;color:var(--red);padding:40px">'+e.message+'</td></tr>';
    return;
  }
  if(!assets.length){
    document.getElementById('assets-list').innerHTML='';
    document.getElementById('assets-tbody').innerHTML='';
    document.getElementById('asset-empty').style.display='block';
    return;
  }
  document.getElementById('asset-empty').style.display='none';
  cachedAssets = assets;
  document.getElementById('assets-list').innerHTML=assets.map(a=>assetCard(a)).join('');
  document.getElementById('assets-tbody').innerHTML=assets.map(a=>`
    <tr style="${a.status==='retired'?'opacity:0.55':eolStatus(a.endOfLife)==='critical'?'background:rgba(255,59,92,0.04)':eolStatus(a.endOfLife)==='warning'?'background:rgba(255,140,0,0.03)':''}">
      <td class="font-mono">${esc(a.id)}</td>
      <td><div style="font-weight:600">${esc(a.name)}</div>${a.dept?`<div style="font-size:11px;color:var(--muted)">${esc(a.dept)}</div>`:''}</td>
      <td>${typeBadge(a.type)}</td>
      <td class="font-mono">${esc(a.serial)||'<span style="color:var(--muted)">—</span>'}</td>
      <td>${a.assignedTo||'<span style="color:var(--muted)">Unassigned</span>'}</td>
      <td class="font-mono">${a.purchaseDate||'—'}</td>
      <td class="font-mono" style="${eolStatus(a.endOfLife)==='critical'?'color:var(--red)':eolStatus(a.endOfLife)==='warning'?'color:var(--orange)':''}">${a.endOfLife||'—'} ${eolFlag(a.endOfLife)||''}</td>
      <td class="font-mono">${a.cost?'$'+Number(a.cost).toLocaleString():'—'}</td>
      <td><div class="tbl-actions">
        <button class="tbl-btn" onclick="showQR('${esc(a.id)}','${esc(a.name)}','${esc(a.serial||'')}')">QR</button>
        <button class="tbl-btn" onclick="editAsset('${esc(a.id)}')">Edit</button>
        <button class="tbl-btn danger" onclick="deleteAsset('${esc(a.id)}')">Del</button>
      </div></td>
    </tr>`).join('');
}


function eolMenuHtml(id, eolOverride, isEolActive) {
  if (!eolOverride && !isEolActive) return '';
  const cls  = eolOverride ? 'eol-clear' : 'eol-ack';
  const icon = eolOverride
    ? '<path d="M18 6 6 18M6 6l12 12"/>'
    : '<polyline points="20 6 9 17 4 12"/>';
  const label = eolOverride ? 'Remove EOL Override' : 'Acknowledge EOL';
  const safeId = id.replace(/'/g, "\'");
  return `<div class="card-menu-item ${cls}" onclick="toggleEolOverride('${safeId}');closeCardMenu('${safeId}')">` +
    `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">${icon}</svg>${label}</div>`;
}

function assetCard(a) {
  const flag    = eolFlag(a.endOfLife, a.eolOverride);
  const retired = a.status === 'retired';
  const isEolActive = !a.eolOverride && eolStatus(a.endOfLife);
  const cardStyle = retired ? 'opacity:0.55;' : isEolActive === 'critical' ? 'border-color:rgba(255,59,92,0.35)' : isEolActive === 'warning' ? 'border-color:rgba(255,140,0,0.35)' : '';
  return `<div class="asset-card" id="card-${esc(a.id)}" onmousemove="cardMove(event,this)" onmouseleave="cardLeave(this)" style="${cardStyle}">
    <div class="card-spotlight"></div>
    <div class="asset-card-checkbox" onclick="toggleCardSelect('${esc(a.id)}',event)">
      <svg width="11" height="11" fill="none" stroke="#000" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div class="asset-card-header" onclick="batchMode?toggleCardSelect('${esc(a.id)}',event):null" style="cursor:inherit">
      <div style="min-width:0;flex:1">
        <div class="asset-card-name">${esc(a.name)}</div>
        <div class="asset-card-id">${esc(a.id)}${a.serial?' · '+esc(a.serial):''}</div>
      </div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
        ${typeBadge(a.type)}
        ${retired?'<span class="badge badge-retired">Retired</span>':(flag||'')}
      </div>
    </div>
    <div class="asset-card-body">
      <div class="asset-card-field"><label>Assigned To</label><span>${a.assignedTo?esc(a.assignedTo):'<span style="color:var(--muted)">Unassigned</span>'}</span></div>
      <div class="asset-card-field"><label>Department</label><span>${a.dept?esc(a.dept):'<span style="color:var(--muted)">—</span>'}</span></div>
      <div class="asset-card-field"><label>Purchase Date</label><span>${a.purchaseDate||'<span style="color:var(--muted)">—</span>'}</span></div>
      <div class="asset-card-field"><label>End of Life</label><span style="${!a.eolOverride&&eolStatus(a.endOfLife)==='critical'?'color:var(--red)':!a.eolOverride&&eolStatus(a.endOfLife)==='warning'?'color:var(--orange)':''}">${a.endOfLife||'<span style="color:var(--muted)">—</span>'}</span></div>
      ${renderCustomFieldChips(a.id, a.type)}
    </div>
    <div class="asset-card-actions">
      <button class="card-action-btn qr-btn" onclick="showQR('${esc(a.id)}','${esc(a.name)}','${esc(a.serial||'')}')">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="5" height="5" rx="0.5"/><rect x="16" y="3" width="5" height="5" rx="0.5"/><rect x="3" y="16" width="5" height="5" rx="0.5"/><path d="M16 16h5M16 21h5M21 16v5M16 11h5v2"/></svg>
        QR
      </button>
      <button class="card-action-btn edit-btn" onclick="editAsset('${esc(a.id)}')">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
        Edit
      </button>
      <button class="card-action-btn card-more-btn" onclick="toggleCardMenu('${esc(a.id)}',event)" style="flex:0 0 44px;border-left:1px solid var(--border)">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.5" fill="currentColor"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/><circle cx="12" cy="19" r="1.5" fill="currentColor"/></svg>
      </button>
      <div class="card-more-menu" id="card-menu-${esc(a.id)}">
        ${eolMenuHtml(a.id, a.eolOverride, isEolActive)}
        <div class="card-menu-item" onclick="showAssetLog('${esc(a.id)}');closeCardMenu('${esc(a.id)}')">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          View History
        </div>
        <div class="card-menu-item warn" onclick="archiveAsset('${esc(a.id)}');closeCardMenu('${esc(a.id)}')">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
          Archive Asset
        </div>
      </div>
    </div>
    <div id="linked-section-${esc(a.id)}" class="linked-section" style="margin-top:8px;border-top:1px solid var(--border);padding-top:8px">
      <div class="linked-header" onclick="toggleLinkedExpand('${esc(a.id)}')">
        <span class="linked-header-label">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
          Linked Assets
          ${(linksCache[a.id]||[]).length ? `<span class="linked-count">${(linksCache[a.id]||[]).length}</span>` : ''}
        </span>
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" id="linked-chevron-${esc(a.id)}" style="color:var(--muted);transition:transform 0.2s"><polyline points="6 9 12 15 18 9"/></svg>
      </div>
      <div id="linked-body-${esc(a.id)}" style="display:none">
        <div class="linked-list" id="linked-list-${esc(a.id)}">
          ${(linksCache[a.id]||[]).map(l => linkedChipHtml(a.id, l)).join('')}
        </div>
        <div style="position:relative">
          <button class="linked-add-btn" onclick="openLinkPicker('${esc(a.id)}')">+ Link another asset</button>
          <div id="link-picker-${esc(a.id)}" class="link-picker" style="display:none"></div>
        </div>
      </div>
    </div>
  </div>`;
}

// ── Batch Selection ───────────────────────────────────────────
let batchMode    = false;
let selectedIds  = new Set();
let cachedAssets = []; // keep reference to loaded assets for batch ops

function toggleBatchMode() {
  batchMode = !batchMode;
  selectedIds.clear();
  const list = document.getElementById('assets-list');
  const btn  = document.getElementById('batch-toggle-btn');
  if (batchMode) {
    list.classList.add('batch-mode');
    btn.classList.add('active');
    btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Select All`;
    btn.onclick = selectAll;
  } else {
    cancelBatchMode();
  }
  updateBatchBar();
}

function selectAll() {
  cachedAssets.forEach(a => selectedIds.add(a.id));
  document.querySelectorAll('.asset-card').forEach(card => card.classList.add('selected'));
  updateBatchBar();
}

function cancelBatchMode() {
  batchMode = false;
  selectedIds.clear();
  document.getElementById('assets-list').classList.remove('batch-mode');
  document.querySelectorAll('.asset-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('batch-bar').classList.remove('visible');
  const btn = document.getElementById('batch-toggle-btn');
  btn.classList.remove('active');
  btn.innerHTML = `<svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Select`;
  btn.onclick = toggleBatchMode;
}

function toggleCardSelect(id, e) {
  if (!batchMode) return;
  e && e.stopPropagation();
  const card = document.getElementById('card-' + id);
  if (!card) return;
  if (selectedIds.has(id)) {
    selectedIds.delete(id);
    card.classList.remove('selected');
  } else {
    selectedIds.add(id);
    card.classList.add('selected');
  }
  updateBatchBar();
}

function updateBatchBar() {
  const bar   = document.getElementById('batch-bar');
  const count = document.getElementById('batch-count');
  const n     = selectedIds.size;
  count.textContent = n === 1 ? '1 selected' : `${n} selected`;
  bar.classList.toggle('visible', batchMode && n > 0);
}

async function batchDelete() {
  const ids = [...selectedIds];
  if (!ids.length) return;
  if (!confirm(`Delete ${ids.length} asset${ids.length>1?'s':''}? This cannot be undone.`)) return;
  let done = 0;
  for (const id of ids) {
    await apiFetch(`${API}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    done++;
  }
  toast(`Deleted ${done} asset${done>1?'s':''}`, 'success');
  cancelBatchMode();
  loadAssets();
}

function openBatchReassign() {
  const n = selectedIds.size;
  if (!n) return;
  document.getElementById('reassign-modal-sub').textContent = `${n} asset${n>1?'s':''} selected`;
  document.getElementById('reassign-user').value   = '';
  document.getElementById('reassign-dept').value   = '';
  document.getElementById('reassign-status').value = '';
  document.getElementById('reassign-modal').classList.add('open');
}

async function batchReassign() {
  const ids      = [...selectedIds];
  const newUser  = document.getElementById('reassign-user').value.trim();
  const newDept  = document.getElementById('reassign-dept').value;
  const newStatus= document.getElementById('reassign-status').value;

  if (!newUser && !newDept && !newStatus) {
    toast('Enter at least one field to update.', 'error');
    return;
  }

  const btn = document.getElementById('reassign-save-btn');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  const assets = cachedAssets.filter(a => ids.includes(a.id));
  let done = 0, failed = 0;

  for (const a of assets) {
    try {
      await apiFetch(API, {
        method: 'PUT',
        body: JSON.stringify({
          id:           a.id,
          name:         a.name,
          type:         a.type,
          serial:       a.serial,
          assigned_to:  newUser  !== ''           ? newUser                     : a.assignedTo,
          department:   newDept  === '__clear__'  ? ''      : newDept  !== '' ? newDept  : a.dept,
          status:       newStatus !== ''           ? newStatus                  : a.status,
          purchase_date: a.purchaseDate,
          end_of_life:   a.endOfLife,
          cost:          a.cost,
          notes:         a.notes,
          eol_override:  a.eolOverride ? 1 : 0,
        })
      });
      done++;
    } catch { failed++; }
  }

  btn.disabled = false;
  btn.innerHTML = `<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Apply to Selected`;
  closeModal('reassign-modal');
  cancelBatchMode();
  loadAssets();
  loadDashboard();
  if (document.getElementById('page-users').classList.contains('active')) loadUsers();
  toast(`Reassigned ${done} asset${done!==1?'s':''}${failed?` (${failed} failed)`:''}`, done ? 'success' : 'error');
}

async function batchEstimate() {
  const ids    = [...selectedIds];
  if (!ids.length) return;
  const assets = cachedAssets.filter(a => ids.includes(a.id));

  const prog      = document.getElementById('batch-progress');
  const progBar   = document.getElementById('batch-progress-bar');
  const progLabel = document.getElementById('batch-progress-label');
  const progCount = document.getElementById('batch-progress-count');
  const progDetail= document.getElementById('batch-progress-detail');

  prog.classList.add('visible');
  document.getElementById('batch-bar').classList.remove('visible');

  let done = 0, updated = 0, failed = 0;

  for (const a of assets) {
    progLabel.textContent  = 'Estimating prices…';
    progCount.textContent  = `${done} / ${assets.length}`;
    progDetail.textContent = `${a.name} (${a.id})`;
    progBar.style.width    = `${Math.round((done / assets.length) * 100)}%`;

    try {
      const res = await fetch('api/ai_price.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: a.name, type: a.type, serial: a.serial, purchaseDate: a.purchaseDate, notes: a.notes })
      });
      const est = await res.json();
      if (res.ok && est.midpoint) {
        // Save midpoint as cost
        await apiFetch(API, {
          method: 'PUT',
          body: JSON.stringify({
            id: a.id, name: a.name, type: a.type, serial: a.serial,
            assigned_to: a.assignedTo, department: a.dept, status: a.status,
            purchase_date: a.purchaseDate, end_of_life: a.endOfLife,
            cost: est.midpoint, notes: a.notes
          })
        });
        updated++;
      } else { failed++; }
    } catch { failed++; }
    done++;
  }

  progBar.style.width    = '100%';
  progLabel.textContent  = 'Done!';
  progCount.textContent  = `${done} / ${assets.length}`;
  progDetail.textContent = `${updated} updated, ${failed} failed`;

  setTimeout(() => {
    prog.classList.remove('visible');
    cancelBatchMode();
    loadAssets();
    toast(`Updated ${updated} asset price${updated!==1?'s':''} with AI estimates`, 'success');
  }, 1500);
}

async function toggleEolOverride(id) {
  const asset = cachedAssets.find(a => a.id === id);
  if (!asset) return;
  const newOverride = !asset.eolOverride;

  try {
    await apiFetch(API, {
      method: 'PUT',
      body: JSON.stringify({
        id: asset.id, name: asset.name, type: asset.type, serial: asset.serial,
        assigned_to: asset.assignedTo, department: asset.dept, status: asset.status,
        purchase_date: asset.purchaseDate, end_of_life: asset.endOfLife,
        cost: asset.cost, notes: asset.notes, eol_override: newOverride ? 1 : 0
      })
    });
    // Update local cache after confirmed save
    asset.eolOverride = newOverride;
    toast(newOverride ? 'EOL warning acknowledged' : 'EOL override removed', 'success');
    await loadAssets();
    loadDashboard();
  } catch(e) {
    toast('Failed to update EOL override', 'error');
  }
}


// ── Archive ───────────────────────────────────────────────────────────────────
let archiveTimer = null;

async function loadArchive() {
  const q = document.getElementById('archive-search')?.value || '';
  const list = document.getElementById('archive-list');
  const empty = document.getElementById('archive-empty');
  if (!list) return;
  try {
    const params = new URLSearchParams({ archived: 1 });
    if (q) params.set('q', q);
    const assets = await apiFetch(API + '?' + params);
    if (!assets.length) {
      list.innerHTML = ''; empty.style.display = '';
    } else {
      empty.style.display = 'none';
      list.innerHTML = assets.map(archiveCard).join('');
    }
  } catch(e) {}
}

function archiveCard(a) {
  const since = a.archivedAt ? timeAgo(a.archivedAt) : 'Unknown';
  return `<div class="asset-card" id="arc-card-${esc(a.id)}" style="opacity:0.85">
    <div class="card-spotlight"></div>
    <div class="card-header">
      <div>
        <div class="asset-id">${esc(a.id)}</div>
        <div class="asset-name">${esc(a.name)}</div>
      </div>
      <span class="badge badge-type">${esc(a.type)}</span>
    </div>
    <div class="card-meta">
      ${a.assignedTo ? `<span>👤 ${esc(a.assignedTo)}</span>` : '<span style="color:var(--muted)">Unassigned</span>'}
      ${a.dept ? `<span>🏢 ${esc(a.dept)}</span>` : ''}
    </div>
    <div style="font-size:12px;color:var(--muted);margin-top:6px">Archived ${since}</div>
    <div class="archive-card-actions">
      <button class="btn btn-primary" style="flex:1;font-size:12px;padding:8px 12px;min-height:auto" onclick="restoreAsset('${esc(a.id)}')">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.07"/></svg>
        Restore
      </button>
      <button class="btn" style="font-size:12px;padding:8px 12px;min-height:auto;background:rgba(255,59,92,0.08);border-color:rgba(255,59,92,0.25);color:var(--red)" onclick="hardDeleteAsset('${esc(a.id)}','${esc(a.name)}')">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        Delete
      </button>
    </div>
  </div>`;
}

async function archiveAsset(id) {
  if (!confirm('Archive this asset? It will be hidden from the main list but can be restored.')) return;
  try {
    await apiFetch(API + '?archive=1', { method: 'PUT', body: JSON.stringify({ id }) });
    toast('Asset archived', 'success');
    // Remove from cachedAssets + re-render
    const idx = cachedAssets.findIndex(a => a.id === id);
    if (idx !== -1) cachedAssets.splice(idx, 1);
    document.getElementById('card-' + id)?.remove();
    loadDashboard();
  } catch(e) {}
}

async function restoreAsset(id) {
  try {
    await apiFetch(API + '?restore=1', { method: 'PUT', body: JSON.stringify({ id }) });
    toast('Asset restored to active list', 'success');
    document.getElementById('arc-card-' + id)?.remove();
    loadDashboard();
    // Check if archive list is now empty
    const remaining = document.querySelectorAll('[id^="arc-card-"]');
    if (!remaining.length) {
      document.getElementById('archive-empty').style.display = '';
    }
  } catch(e) {}
}

async function hardDeleteAsset(id, name) {
  if (!confirm(`Permanently delete "${name}"? This cannot be undone.`)) return;
  try {
    await apiFetch(API + '?id=' + encodeURIComponent(id), { method: 'DELETE' });
    toast('Asset permanently deleted', 'success');
    document.getElementById('arc-card-' + id)?.remove();
    loadDashboard();
    const remaining = document.querySelectorAll('[id^="arc-card-"]');
    if (!remaining.length) {
      document.getElementById('archive-empty').style.display = '';
    }
  } catch(e) {}
}

// ── Activity Log ──────────────────────────────────────────────────────────────
let activityOffset = 0;
const ACTIVITY_LIMIT = 30;

const LOG_ICONS = {
  created:  `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>`,
  updated:  `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
  archived: `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>`,
  restored: `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.07"/></svg>`,
  deleted:  `<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>`,
};

const LOG_LABELS = {
  created: 'Created',  updated: 'Updated',
  archived: 'Archived', restored: 'Restored', deleted: 'Deleted',
};

const FIELD_LABELS = {
  name:'Name', type:'Type', serial:'Serial', assigned_to:'Assigned To',
  department:'Department', status:'Status', purchase_date:'Purchase Date',
  end_of_life:'End of Life', cost:'Cost', notes:'Notes', eol_override:'EOL Override',
};

function logEntryHtml(log) {
  const action = log.action || 'updated';
  const icon = LOG_ICONS[action] || LOG_ICONS.updated;
  const label = LOG_LABELS[action] || action;
  const when    = timeAgo(log.createdAt);
  const absDate = log.createdAt
    ? new Date(log.createdAt.replace(' ','T')).toLocaleString(undefined,{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'})
    : '';
  const who = log.performedBy || 'Unknown';
  const fields = (log.changedFields || []).map(f =>
    `<span class="log-field-tag">${FIELD_LABELS[f] || f}</span>`
  ).join('');
  const nameLink = action !== 'deleted'
    ? `<a href="#" onclick="event.preventDefault();showPage('assets');setTimeout(()=>openEditModal('${esc(log.assetId)}'),300)">${esc(log.assetName)}</a>`
    : `<span>${esc(log.assetName)}</span>`;
  return `<div class="log-entry">
    <div class="log-icon ${action}">${icon}</div>
    <div class="log-body">
      <div class="log-title">${label} — ${nameLink} <span style="color:var(--muted);font-weight:400">(${esc(log.assetId)})</span></div>
      <div class="log-meta" title="${absDate}">${esc(who)} · ${when}</div>
      ${fields ? `<div class="log-fields">${fields}</div>` : ''}
    </div>
  </div>`;
}

async function loadActivity(reset = true) {
  if (reset) activityOffset = 0;
  const list = document.getElementById('activity-list');
  const empty = document.getElementById('activity-empty');
  const loadMoreBtn = document.getElementById('activity-load-more');
  if (!list) return;
  try {
    const params = new URLSearchParams({ logs: 1, limit: ACTIVITY_LIMIT, offset: activityOffset });
    const data = await apiFetch(API + '?' + params);
    const logs = data.logs || [];
    if (reset) list.innerHTML = '';
    if (!logs.length && reset) {
      empty.style.display = ''; loadMoreBtn.style.display = 'none';
    } else {
      empty.style.display = 'none';
      list.insertAdjacentHTML('beforeend', logs.map(logEntryHtml).join(''));
      activityOffset += logs.length;
      loadMoreBtn.style.display = (logs.length === ACTIVITY_LIMIT) ? '' : 'none';
    }
  } catch(e) {}
}

async function loadMoreActivity() { await loadActivity(false); }

// Per-asset activity drawer
async function showAssetLog(assetId) {
  try {
    const data = await apiFetch(API + '?logs=1&id=' + encodeURIComponent(assetId) + '&limit=20');
    const logs = data.logs || [];
    if (!logs.length) { toast('No history for this asset yet', 'success'); return; }
    // Re-use the detail overlay
    let drawer = document.getElementById('asset-log-drawer');
    if (!drawer) {
      drawer = document.createElement('div');
      drawer.id = 'asset-log-drawer';
      drawer.style.cssText = 'position:fixed;inset:0;z-index:300;display:flex;align-items:flex-end;justify-content:center';
      drawer.innerHTML = `
        <div onclick="this.parentElement.remove()" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px)"></div>
        <div style="position:relative;width:100%;max-width:600px;max-height:75vh;overflow-y:auto;background:var(--surface);border:1px solid var(--border2);border-bottom:none;border-radius:18px 18px 0 0;padding:20px">
          <div style="width:36px;height:4px;border-radius:2px;background:rgba(255,255,255,0.15);margin:0 auto 16px"></div>
          <h3 style="font-size:16px;font-weight:700;margin-bottom:12px">Asset History</h3>
          <div id="asset-log-entries" style="display:flex;flex-direction:column;gap:6px"></div>
        </div>`;
      document.body.appendChild(drawer);
    }
    document.getElementById('asset-log-entries').innerHTML = logs.map(logEntryHtml).join('');
  } catch(e) {}
}

// Helper: human-readable relative time
function timeAgo(dateStr) {
  if (!dateStr) return 'Unknown';
  const date = new Date(dateStr.replace(' ','T'));
  const diff = Math.floor((Date.now() - date.getTime()) / 1000);
  if (diff < 60)    return 'Just now';
  if (diff < 3600)  return Math.floor(diff/60) + 'm ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  if (diff < 604800)return Math.floor(diff/86400) + 'd ago';
  // For older entries, show actual date
  return date.toLocaleDateString(undefined, {month:'short', day:'numeric', year: diff > 31536000 ? 'numeric' : undefined});
}



// ══════════════════════════════════════════════════════════════════════════════
// ALERTS
// ══════════════════════════════════════════════════════════════════════════════
async function checkAlerts() {
  try {
    const data = await apiFetch(SETTINGS_API + '?alerts=1');
    const alerts = data.alerts || [];
    const banner = document.getElementById('alert-banner');
    const txt    = document.getElementById('alert-banner-text');
    const badge  = document.getElementById('alert-badge');
    const badgem = document.getElementById('alert-badge-mob');

    if (alerts.length && data.enabled) {
      const msg = alerts.map(a =>
        `${a.type}: ${a.have} unassigned (need ≥ ${a.threshold})`
      ).join(' · ');
      if (txt)    txt.textContent = msg;
      if (banner) banner.style.display = '';
      if (badge)  badge.style.display = '';
      if (badgem) badgem.style.display = '';
    } else {
      if (banner) banner.style.display = 'none';
      if (badge)  badge.style.display = 'none';
      if (badgem) badgem.style.display = 'none';
    }
  } catch(e) {}
}

// ══════════════════════════════════════════════════════════════════════════════
// SETTINGS PAGE
// ══════════════════════════════════════════════════════════════════════════════
const THRESHOLD_TYPES = ['Laptop','Desktop','Monitor','Docking Station','Printer','Camera','Other'];
const THRESHOLD_KEYS  = {
  'Laptop':'threshold_laptop','Desktop':'threshold_desktop','Monitor':'threshold_monitor',
  'Docking Station':'threshold_docking_station','Printer':'threshold_printer',
  'Camera':'threshold_camera','Other':'threshold_other'
};

// ── Settings section collapse ──────────────────────────────────────────────
function initSettingsCollapse() {
  document.querySelectorAll('.settings-section').forEach(section => {
    const titleEl = section.querySelector('.settings-section-title');
    const bodyEl  = section.querySelector('.settings-section-body');
    if (!titleEl || !bodyEl) return;
    // Measure and store natural height
    bodyEl.style.maxHeight = bodyEl.scrollHeight + 'px';
    // Make title a toggle
    titleEl.classList.add('settings-section-toggle');
    const icon = document.createElement('span');
    icon.className = 'settings-toggle-icon';
    icon.innerHTML = '<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>';
    titleEl.appendChild(icon);
    titleEl.addEventListener('click', () => {
      const isCollapsed = section.classList.toggle('collapsed');
      if (!isCollapsed) bodyEl.style.maxHeight = bodyEl.scrollHeight + 'px';
    });
  });
}

// Auto-wrap settings section content in .settings-section-body
function wrapSettingsBodies() {
  document.querySelectorAll('.settings-section').forEach(section => {
    const title = section.querySelector('.settings-section-title');
    const sub   = section.querySelector('.settings-section-sub');
    if (!title) return;
    // Find all children after the title/sub
    const children = Array.from(section.children);
    const startIdx = children.indexOf(sub || title) + 1;
    if (startIdx <= 0 || startIdx >= children.length) return;
    const body = document.createElement('div');
    body.className = 'settings-section-body';
    children.slice(startIdx).forEach(child => body.appendChild(child));
    section.appendChild(body);
  });
}

async function loadSettings() {
  try {
    const s = await apiFetch(SETTINGS_API);
    // Alerts toggle
    const tog = document.getElementById('setting-alerts-enabled');
    if (tog) tog.checked = (s['alerts_enabled'] !== '0');
    // Depreciation years
    const dy = document.getElementById('setting-depr-years');
    if (dy) dy.value = s['depreciation_years'] || '5';
    // Threshold fields
    const wrap = document.getElementById('threshold-fields');
    if (wrap) {
      wrap.innerHTML = THRESHOLD_TYPES.map(type => {
        const key = THRESHOLD_KEYS[type];
        const val = s[key] ?? '1';
        return `<div class="threshold-field">
          <label>${type}</label>
          <input type="number" min="0" max="50" value="${val}" id="thr-${key}" data-key="${key}">
        </div>`;
      }).join('');
    }
  renderCfDefsList();
  } catch(e) {}
}

async function saveAlertToggle(enabled) {
  try {
    await apiFetch(SETTINGS_API, { method:'POST', body: JSON.stringify({ alerts_enabled: enabled ? '1' : '0' }) });
    toast(enabled ? 'Alerts enabled' : 'Alerts disabled', 'success');
    checkAlerts();
  } catch(e) {}
}

async function saveThresholds() {
  const payload = {};
  document.querySelectorAll('#threshold-fields input').forEach(inp => {
    payload[inp.dataset.key] = inp.value;
  });
  try {
    await apiFetch(SETTINGS_API, { method:'POST', body: JSON.stringify(payload) });
    toast('Thresholds saved', 'success');
    checkAlerts();
  } catch(e) {}
}

async function saveDeprYears() {
  const val = document.getElementById('setting-depr-years')?.value || '5';
  try {
    await apiFetch(SETTINGS_API, { method:'POST', body: JSON.stringify({ depreciation_years: val }) });
    toast('Depreciation period saved', 'success');
  } catch(e) {}
}

// ══════════════════════════════════════════════════════════════════════════════
// REPORTS PAGE
// ══════════════════════════════════════════════════════════════════════════════
async function loadReports() {
  try {
    const [assets, settings] = await Promise.all([
      apiFetch(API + '?show_retired=1'),
      apiFetch(SETTINGS_API),
    ]);
    const deprYears = parseFloat(settings['depreciation_years'] || '5');
    const now = Date.now();

    // Annotate each asset with depreciation
    const annotated = assets.map(a => {
      const cost = parseFloat(a.cost) || 0;
      const ageYears = a.purchaseDate
        ? (now - new Date(a.purchaseDate).getTime()) / (1000 * 60 * 60 * 24 * 365.25)
        : null;
      const currentValue = ageYears !== null
        ? Math.max(0, cost * (1 - ageYears / deprYears))
        : null;
      const deprPct = cost > 0 && currentValue !== null
        ? Math.max(0, Math.min(100, 100 - (currentValue / cost * 100)))
        : null;
      const eolDate  = a.endOfLife ? new Date(a.endOfLife) : null;
      const msToEol  = eolDate ? eolDate.getTime() - now : null;
      const eolStatus = a.eolOverride ? 'acknowledged'
        : !eolDate ? 'unknown'
        : msToEol < 0 ? 'expired'
        : msToEol < 1000*60*60*24*180 ? 'critical'
        : msToEol < 1000*60*60*24*365 ? 'warning'
        : 'ok';
      return { ...a, cost, ageYears, currentValue, deprPct, eolStatus, eolDate };
    });

    renderSummaryCards(annotated);
    renderByDept(annotated);
    renderByType(annotated);
    renderDepreciation(annotated);
    renderEol(annotated);
  } catch(e) { console.error(e); }
}

function fmt$(n) {
  if (n == null) return '—';
  return '$' + Number(n).toLocaleString('en-US',{maximumFractionDigits:0});
}
function fmtPct(n) { return n == null ? '—' : Math.round(n) + '%'; }

function renderSummaryCards(assets) {
  const active = assets.filter(a => !a.archived && a.status !== 'retired');
  const totalCost = active.reduce((s,a)=>s+a.cost,0);
  const totalCurrent = active.reduce((s,a)=>s+(a.currentValue??a.cost),0);
  const eolSoon = active.filter(a=>a.eolStatus==='warning'||a.eolStatus==='critical').length;
  const fullyDepr = active.filter(a=>a.currentValue===0&&a.cost>0).length;
  const cards = [
    {label:'Total Book Value',  val: fmt$(totalCost),    sub:'Original cost'},
    {label:'Current Value',     val: fmt$(totalCurrent), sub:'After depreciation'},
    {label:'Total Depreciation',val: fmt$(totalCost-totalCurrent), sub:'Accumulated'},
    {label:'EOL Soon',          val: eolSoon,            sub:'Within 12 months'},
    {label:'Fully Depreciated', val: fullyDepr,          sub:'Book value $0'},
  ];
  document.getElementById('report-summary-cards').innerHTML = cards.map(c=>`
    <div class="report-summary-card">
      <div class="rsv">${c.val}</div>
      <div class="rsl">${c.label}</div>
      <div class="rsl" style="color:var(--muted2,var(--muted));opacity:0.7">${c.sub}</div>
    </div>`).join('');
}

function renderByDept(assets) {
  const active = assets.filter(a=>!a.archived&&a.status!=='retired');
  const map = {};
  active.forEach(a => {
    const d = a.dept || '(None)';
    if (!map[d]) map[d] = {count:0,cost:0,current:0};
    map[d].count++; map[d].cost+=a.cost; map[d].current+=(a.currentValue??a.cost);
  });
  const rows = Object.entries(map).sort((x,y)=>y[1].cost-x[1].cost);
  if (!rows.length) { document.getElementById('report-by-dept').innerHTML='<p style="padding:14px;color:var(--muted);font-size:13px">No data</p>'; return; }
  document.getElementById('report-by-dept').innerHTML = `<table class="report-table">
    <thead><tr><th>Department</th><th class="num">Assets</th><th class="num">Book Value</th><th class="num">Current Value</th><th class="num">Depreciated</th></tr></thead>
    <tbody>${rows.map(([dept,d])=>`<tr>
      <td>${esc(dept)}</td>
      <td class="num">${d.count}</td>
      <td class="num">${fmt$(d.cost)}</td>
      <td class="num">${fmt$(d.current)}</td>
      <td class="num">${fmt$(d.cost-d.current)}</td>
    </tr>`).join('')}</tbody>
  </table>`;
}

function renderByType(assets) {
  const active = assets.filter(a=>!a.archived&&a.status!=='retired');
  const map = {};
  active.forEach(a => {
    const t = a.type || 'Other';
    if (!map[t]) map[t] = {count:0,assigned:0,cost:0};
    map[t].count++; if(a.assignedTo) map[t].assigned++;
    map[t].cost+=a.cost;
  });
  const rows = Object.entries(map).sort((x,y)=>y[1].count-x[1].count);
  document.getElementById('report-by-type').innerHTML = `<table class="report-table">
    <thead><tr><th>Type</th><th class="num">Total</th><th class="num">Assigned</th><th class="num">Unassigned</th><th class="num">Book Value</th></tr></thead>
    <tbody>${rows.map(([type,d])=>`<tr>
      <td>${esc(type)}</td>
      <td class="num">${d.count}</td>
      <td class="num">${d.assigned}</td>
      <td class="num">${d.count-d.assigned}</td>
      <td class="num">${fmt$(d.cost)}</td>
    </tr>`).join('')}</tbody>
  </table>`;
}

function renderDepreciation(assets) {
  const withCost = assets.filter(a=>!a.archived&&a.status!=='retired'&&a.cost>0&&a.purchaseDate)
    .sort((a,b)=>(a.currentValue??0)-(b.currentValue??0));
  if (!withCost.length) { document.getElementById('report-depreciation').innerHTML='<p style="padding:14px;color:var(--muted);font-size:13px">No assets with cost + purchase date set</p>'; return; }
  document.getElementById('report-depreciation').innerHTML = `<table class="report-table">
    <thead><tr><th>Asset</th><th>Type</th><th class="num">Original</th><th class="num">Current Value</th><th class="num">Depreciated</th><th>Remaining Life</th></tr></thead>
    <tbody>${withCost.slice(0,50).map(a=>{
      const pct = a.deprPct ?? 0;
      const remaining = a.ageYears != null ? Math.max(0, 5 - a.ageYears) : null;
      const remStr = remaining != null ? (remaining < 0.1 ? 'Fully depreciated' : remaining.toFixed(1) + ' yrs') : '—';

      return `<tr>
        <td><div style="font-weight:600">${esc(a.name)}</div><div style="font-size:11px;color:var(--muted)">${esc(a.id)}</div></td>
        <td>${esc(a.type)}</td>
        <td class="num">${fmt$(a.cost)}</td>
        <td class="num">${fmt$(a.currentValue)}</td>
        <td class="num">
          <div style="display:flex;align-items:center;gap:8px;justify-content:flex-end">
            ${fmtPct(pct)}
            <div class="depr-bar-wrap"><div class="depr-bar${pct<=25?' crit':pct<=50?' warn':''}" style="width:${pct}%"></div></div>
          </div>
        </td>
        <td style="font-size:12px;color:${remaining!=null&&remaining<0.5?'var(--red)':remaining!=null&&remaining<1?'var(--orange)':'var(--text)'}">${remStr}</td>
      </tr>`;
    }).join('')}</tbody>
  </table>${withCost.length>50?`<p style="padding:10px 14px;font-size:12px;color:var(--muted)">Showing top 50 of ${withCost.length}</p>`:''}`;
}

function renderEol(assets) {
  const active = assets.filter(a=>!a.archived&&a.status!=='retired');
  const eolBuckets = {expired:[],critical:[],warning:[],ok:[],unknown:[]};
  active.forEach(a => eolBuckets[a.eolStatus]?.push(a));
  const order = ['expired','critical','warning','ok','unknown'];
  const labels = {expired:'Expired',critical:'Critical (<6 mo)',warning:'Warning (<12 mo)',ok:'OK',unknown:'No EOL Set'};
  const colors = {expired:'var(--red)',critical:'var(--red)',warning:'var(--orange)',ok:'var(--green)',unknown:'var(--muted)'};
  let html = `<table class="report-table"><thead><tr><th>Asset</th><th>Type</th><th>Assigned To</th><th>EOL Date</th><th>Status</th></tr></thead><tbody>`;
  let any = false;
  order.forEach(bucket => {
    eolBuckets[bucket].sort((a,b)=>{
      if(!a.eolDate&&!b.eolDate) return 0;
      if(!a.eolDate) return 1; if(!b.eolDate) return -1;
      return a.eolDate-b.eolDate;
    }).forEach(a => {
      any = true;
      const dateStr = a.endOfLife ? new Date(a.endOfLife).toLocaleDateString() : '—';
      html += `<tr>
        <td><div style="font-weight:600">${esc(a.name)}</div><div style="font-size:11px;color:var(--muted)">${esc(a.id)}</div></td>
        <td>${esc(a.type)}</td>
        <td>${esc(a.assignedTo||'—')}</td>
        <td>${dateStr}</td>
        <td><span style="font-size:12px;font-weight:700;color:${colors[bucket]}">${labels[bucket]}</span></td>
      </tr>`;
    });
  });
  html += '</tbody></table>';
  document.getElementById('report-eol').innerHTML = any ? html : '<p style="padding:14px;color:var(--muted);font-size:13px">No EOL data</p>';
}

// CSV Export
async function exportCSV() {
  try {
    const [assets, settings] = await Promise.all([
      apiFetch(API + '?show_retired=1'),
      apiFetch(SETTINGS_API),
    ]);
    const deprYears = parseFloat(settings['depreciation_years'] || '5');
    const now = Date.now();
    const annotated = assets.map(a => {
      const cost = parseFloat(a.cost) || 0;
      const ageYears = a.purchaseDate ? (now - new Date(a.purchaseDate).getTime()) / (1000*60*60*24*365.25) : null;
      const currentValue = ageYears !== null ? Math.max(0, cost * (1 - ageYears / deprYears)) : null;
      const eolDate = a.endOfLife ? new Date(a.endOfLife) : null;
      const msToEol = eolDate ? eolDate.getTime() - now : null;
      const eolStatus = a.eolOverride ? 'Acknowledged'
        : !eolDate ? 'Unknown'
        : msToEol < 0 ? 'Expired'
        : msToEol < 1000*60*60*24*180 ? 'Critical'
        : msToEol < 1000*60*60*24*365 ? 'Warning' : 'OK';
      return { ...a, cost, currentValue, eolStatus };
    });

    const headers = ['ID','Name','Type','Serial','Assigned To','Department','Status',
      'Purchase Date','End of Life','EOL Status','Cost','Current Value','Depreciation %',
      'Notes','Created At'];
    const rows = annotated.map(a => [
      a.id, a.name, a.type, a.serial, a.assignedTo, a.dept, a.status,
      a.purchaseDate||'', a.endOfLife||'', a.eolStatus,
      a.cost||'', a.currentValue != null ? Math.round(a.currentValue) : '',
      a.currentValue != null && a.cost > 0 ? Math.round((1-(a.currentValue/a.cost))*100)+'%' : '',
      a.notes, a.createdAt,
    ]);

    const csvContent = [headers, ...rows].map(row =>
      row.map(v => '"' + String(v||'').replace(/"/g,'""') + '"').join(',')
    ).join('\n');

    const blob = new Blob([csvContent], {type:'text/csv;charset=utf-8;'});
    const url  = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `assetiq-export-${new Date().toISOString().slice(0,10)}.csv`;
    a.click(); URL.revokeObjectURL(url);
    toast('CSV exported', 'success');
  } catch(e) { toast('Export failed','error'); }
}

// ══════════════════════════════════════════════════════════════════════════════
// LINKED ASSETS
// ══════════════════════════════════════════════════════════════════════════════
// Cache links per asset to avoid refetching
const linksCache = {};

async function loadAssetLinks(assetId) {
  try {
    const links = await apiFetch(LINKS_API + '?id=' + encodeURIComponent(assetId));
    linksCache[assetId] = links;
    return links;
  } catch(e) { return []; }
}

function renderLinkedSection(assetId, links, expanded = false) {
  const count = links.length;
  return `<div class="linked-section" id="linked-section-${esc(assetId)}">
    <div class="linked-header" onclick="toggleLinkedExpand('${esc(assetId)}')">
      <span class="linked-header-label">
        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Linked Assets
        ${count ? `<span class="linked-count">${count}</span>` : ''}
      </span>
      <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" id="linked-chevron-${esc(assetId)}" style="color:var(--muted);transition:transform 0.2s${expanded?';transform:rotate(180deg)':''}"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
    <div id="linked-body-${esc(assetId)}" style="display:${expanded?'block':'none'}">
      <div class="linked-list" id="linked-list-${esc(assetId)}">
        ${links.map(l => linkedChipHtml(assetId, l)).join('')}
      </div>
      <div style="position:relative">
        <button class="linked-add-btn" onclick="openLinkPicker('${esc(assetId)}')">
          + Link another asset
        </button>
        <div id="link-picker-${esc(assetId)}" class="link-picker" style="display:none"></div>
      </div>
    </div>
  </div>`;
}

function linkedChipHtml(assetId, link) {
  const status = link.linked_archived ? '⚠ Archived' : (link.linked_assigned_to ? `👤 ${esc(link.linked_assigned_to)}` : 'Unassigned');
  return `<div class="linked-chip" id="link-chip-${link.id}">
    <div>
      <div class="linked-chip-name">${esc(link.linked_name)}</div>
      <div class="linked-chip-type">${esc(link.linked_id)} · ${esc(link.linked_type)} · ${status}</div>
    </div>
    <span class="linked-chip-unlink" onclick="unlinkAsset(${link.id},'${esc(assetId)}')" title="Remove link">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </span>
  </div>`;
}

function toggleLinkedExpand(assetId) {
  const body    = document.getElementById(`linked-body-${assetId}`);
  const chevron = document.getElementById(`linked-chevron-${assetId}`);
  if (!body) return;
  const open = body.style.display === 'none';
  body.style.display = open ? 'block' : 'none';
  if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
  // Load links on first expand
  if (open && !linksCache[assetId]) {
    loadAssetLinks(assetId).then(links => {
      const list = document.getElementById(`linked-list-${assetId}`);
      if (list) list.innerHTML = links.map(l => linkedChipHtml(assetId, l)).join('');
    });
  }
}

let linkPickerTimer = null;
function openLinkPicker(assetId) {
  const picker = document.getElementById(`link-picker-${assetId}`);
  if (!picker) return;
  picker.style.display = '';
  picker.innerHTML = `<div style="padding:8px 12px"><input type="text" placeholder="Search assets…" id="link-search-${esc(assetId)}" oninput="debounceLinkSearch('${esc(assetId)}')" style="width:100%"></div>`;
  document.getElementById(`link-search-${assetId}`)?.focus();
  // Close on outside click
  setTimeout(() => document.addEventListener('click', function handler(e) {
    if (!picker.contains(e.target)) { picker.style.display='none'; document.removeEventListener('click', handler); }
  }), 50);
}

function debounceLinkSearch(assetId) {
  clearTimeout(linkPickerTimer);
  linkPickerTimer = setTimeout(() => runLinkSearch(assetId), 250);
}

async function runLinkSearch(assetId) {
  const input   = document.getElementById(`link-search-${assetId}`);
  const picker  = document.getElementById(`link-picker-${assetId}`);
  if (!input || !picker) return;
  const q = input.value.trim();
  if (!q) return;
  try {
    const results = await apiFetch(API + '?q=' + encodeURIComponent(q) + '&show_retired=1');
    const existing = (linksCache[assetId] || []).map(l => l.linked_id);
    const filtered = results.filter(r => r.id !== assetId && !existing.includes(r.id)).slice(0, 8);
    const items = filtered.length
      ? filtered.map(r => `<div class="link-picker-item" onclick="createLink('${esc(assetId)}','${esc(r.id)}')">
          <div style="font-weight:600">${esc(r.name)}</div>
          <div class="pid">${esc(r.id)} · ${esc(r.type)} ${r.assignedTo ? '· 👤 '+esc(r.assignedTo) : ''}</div>
        </div>`).join('')
      : `<div style="padding:10px 12px;color:var(--muted);font-size:13px">No results</div>`;
    picker.innerHTML = `<div style="padding:8px 12px"><input type="text" placeholder="Search assets…" id="link-search-${esc(assetId)}" oninput="debounceLinkSearch('${esc(assetId)}')" value="${esc(q)}" style="width:100%"></div>` + items;
    document.getElementById(`link-search-${assetId}`)?.focus();
  } catch(e) {}
}

async function createLink(assetId, targetId) {
  try {
    await apiFetch(LINKS_API, { method:'POST', body: JSON.stringify({ asset_id_a: assetId, asset_id_b: targetId }) });
    // Refresh links for this asset
    const links = await loadAssetLinks(assetId);
    const list  = document.getElementById(`linked-list-${assetId}`);
    if (list) list.innerHTML = links.map(l => linkedChipHtml(assetId, l)).join('');
    // Update count badge
    const section = document.getElementById(`linked-section-${assetId}`);
    if (section) {
      const countEl = section.querySelector('.linked-count');
      if (countEl) countEl.textContent = links.length;
      else if (links.length) {
        const label = section.querySelector('.linked-header-label');
        label?.insertAdjacentHTML('beforeend', `<span class="linked-count">${links.length}</span>`);
      }
    }
    // Close picker
    const picker = document.getElementById(`link-picker-${assetId}`);
    if (picker) picker.style.display = 'none';
    toast('Assets linked', 'success');
  } catch(e) {
    if (e.message?.includes('409') || e.message?.includes('exists')) toast('Already linked','error');
  }
}

async function unlinkAsset(linkId, assetId) {
  try {
    await apiFetch(LINKS_API + '?id=' + linkId, { method:'DELETE' });
    document.getElementById(`link-chip-${linkId}`)?.remove();
    // Update cache
    if (linksCache[assetId]) {
      linksCache[assetId] = linksCache[assetId].filter(l => l.id !== linkId);
      const countEl = document.querySelector(`#linked-section-${assetId} .linked-count`);
      if (countEl) {
        const newCount = linksCache[assetId].length;
        if (newCount) countEl.textContent = newCount;
        else countEl.remove();
      }
    }
    toast('Link removed', 'success');
  } catch(e) {}
}

// Inject linked section into a rendered asset card
async function injectLinkedSection(assetId) {
  const section = document.getElementById(`linked-section-${assetId}`);
  if (section) return; // already there
  const card = document.getElementById(`card-${assetId}`);
  if (!card) return;
  const links = await loadAssetLinks(assetId);
  card.insertAdjacentHTML('beforeend', renderLinkedSection(assetId, links, false));
}


// ── Mobile Drawer ─────────────────────────────────────────────────────────────

// ── Card overflow menu ────────────────────────────────────────────────────────
function toggleCardMenu(id, e) {
  e.stopPropagation();
  const menu = document.getElementById('card-menu-' + id);
  if (!menu) return;
  const wasOpen = menu.classList.contains('open');
  // close all first
  document.querySelectorAll('.card-more-menu.open').forEach(m => m.classList.remove('open'));
  if (!wasOpen) menu.classList.add('open');
}
function closeCardMenu(id) {
  const el = document.getElementById('card-menu-' + id);
  if (el) el.classList.remove('open');
}
document.addEventListener('click', () => {
  document.querySelectorAll('.card-more-menu.open').forEach(m => m.classList.remove('open'));
});

function openDrawer() {
  document.getElementById('mobile-drawer')?.classList.add('open');
  document.getElementById('drawer-overlay')?.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('mobile-drawer')?.classList.remove('open');
  document.getElementById('drawer-overlay')?.classList.remove('open');
  document.body.style.overflow = '';
}
(function(){
  let sx=0;
  document.addEventListener('touchstart',e=>{sx=e.touches[0].clientX;},{passive:true});
  document.addEventListener('touchend',e=>{
    if(e.changedTouches[0].clientX-sx<-60&&document.getElementById('mobile-drawer')?.classList.contains('open'))closeDrawer();
  },{passive:true});
})();

// ── Settings Tabs ─────────────────────────────────────────────────────────────
function switchSettingsTab(tab) {
  document.getElementById('settings-tab-general').style.display = tab==='general' ? '' : 'none';
  document.getElementById('settings-tab-intune').style.display  = tab==='intune'  ? '' : 'none';
  document.querySelectorAll('.settings-tab').forEach(b => {
    const active = b.id === 'stab-'+tab;
    b.style.background = active ? 'rgba(0,229,255,0.1)' : 'transparent';
    b.style.color      = active ? 'var(--accent)' : 'var(--muted)';
  });
  if(tab==='intune') {
    const src   = document.getElementById('page-intune');
    const mount = document.getElementById('settings-intune-mount');
    if(src && mount && !mount.contains(src)) {
      mount.innerHTML = '';
      mount.appendChild(src);
      src.style.padding = '0';
      src.style.display = 'block';
    }
    initIntunePage();
  }
}


// ══════════════════════════════════════════════════════════════════════════════
// DATE RANGE FILTER
// ══════════════════════════════════════════════════════════════════════════════
let activeDatePreset   = 'all';
let customRangeFrom    = '';
let customRangeTo      = '';

function buildStatsUrl() {
  const p = new URLSearchParams({ stats: 1 });
  const now  = new Date();
  if (activeDatePreset === '30d') {
    const d = new Date(now); d.setDate(d.getDate() - 30);
    p.set('purchased_after', d.toISOString().slice(0,10));
  } else if (activeDatePreset === '90d') {
    const d = new Date(now); d.setDate(d.getDate() - 90);
    p.set('purchased_after', d.toISOString().slice(0,10));
  } else if (activeDatePreset === '1yr') {
    const d = new Date(now); d.setFullYear(d.getFullYear() - 1);
    p.set('purchased_after', d.toISOString().slice(0,10));
  } else if (activeDatePreset === 'custom') {
    if (customRangeFrom) p.set('purchased_after',  customRangeFrom);
    if (customRangeTo)   p.set('purchased_before', customRangeTo);
  }
  return API + '?' + p.toString();
}

function setDatePreset(preset) {
  activeDatePreset = preset;
  // Update button styles
  ['all','30d','90d','1yr','custom'].forEach(p => {
    const btn = document.getElementById('preset-' + p);
    if (btn) btn.classList.toggle('active', p === preset);
  });
  // Show/hide custom inputs
  const custom = document.getElementById('date-range-custom');
  if (custom) custom.classList.toggle('open', preset === 'custom');
  // Reload dashboard unless waiting for custom date input
  if (preset !== 'custom') loadDashboard();
}

function applyCustomRange() {
  customRangeFrom = document.getElementById('range-from')?.value || '';
  customRangeTo   = document.getElementById('range-to')?.value   || '';
  if (customRangeFrom || customRangeTo) loadDashboard();
}

// ══════════════════════════════════════════════════════════════════════════════
// CUSTOM FIELDS
// ══════════════════════════════════════════════════════════════════════════════
let cfDefsCache   = null;  // all field defs: [{id,asset_type,label,field_key,...}]
let cfValuesCache = {};    // {assetId: {field_key: value}}

async function loadCfDefs(force = false) {
  if (cfDefsCache && !force) return cfDefsCache;
  try {
    cfDefsCache = await apiFetch(FIELDS_API);
  } catch(e) { cfDefsCache = []; }
  return cfDefsCache;
}

async function loadCfValues(assetId) {
  if (cfValuesCache[assetId]) return cfValuesCache[assetId];
  try {
    cfValuesCache[assetId] = await apiFetch(FIELDS_API + '?values=1&id=' + encodeURIComponent(assetId));
  } catch(e) { cfValuesCache[assetId] = {}; }
  return cfValuesCache[assetId];
}

// Render inline chips on the card — synchronous using cache
function renderCustomFieldChips(assetId, assetType) {
  const defs   = (cfDefsCache || []).filter(d => d.asset_type === assetType);
  const values = cfValuesCache[assetId] || {};
  const chips  = defs
    .filter(d => values[d.field_key])
    .map(d => {
      const val = values[d.field_key];
      const display = d.field_type === 'date' && val
        ? new Date(val).toLocaleDateString() : val;
      return `<span class="custom-field-chip">
        <span class="custom-field-chip-label">${esc(d.label)}:</span>
        <span class="custom-field-chip-value">${esc(display)}</span>
      </span>`;
    });
  if (!chips.length) return '';
  return `<div class="custom-fields-row">${chips.join('')}</div>`;
}

// Pre-fetch custom field defs + values for a batch of assets so cards render correctly
async function prefetchCustomFields(assets) {
  await loadCfDefs();
  const relevant = assets.filter(a =>
    (cfDefsCache || []).some(d => d.asset_type === a.type)
  );
  await Promise.all(relevant.map(a => loadCfValues(a.id)));
}

// Load custom fields into edit/add modal for current asset type
async function loadCustomFieldsForModal(assetType, assetId = null) {
  const section = document.getElementById('custom-fields-modal-section');
  const wrap    = document.getElementById('custom-fields-modal-inputs');
  if (!section || !wrap) return;
  const defs = (await loadCfDefs()).filter(d => d.asset_type === assetType);
  if (!defs.length) { section.style.display = 'none'; return; }
  section.style.display = '';
  const values = assetId ? await loadCfValues(assetId) : {};
  wrap.innerHTML = defs.map(d => `
    <div>
      <label style="display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:5px">${esc(d.label)}</label>
      <input type="date" id="cf-input-${esc(d.field_key)}" data-key="${esc(d.field_key)}"
             value="${esc(values[d.field_key] || '')}" style="width:100%">
    </div>
  `).join('');
}

// Collect custom field values from modal inputs
function collectCustomFieldValues() {
  const out = {};
  document.querySelectorAll('#custom-fields-modal-inputs input[data-key]').forEach(inp => {
    if (inp.value) out[inp.dataset.key] = inp.value;
  });
  return out;
}

// Save custom field values after asset save
async function saveCustomFieldValues(assetId) {
  const values = collectCustomFieldValues();
  if (!Object.keys(values).length) return;
  try {
    await apiFetch(FIELDS_API + '?values=1', {
      method: 'POST',
      body: JSON.stringify({ asset_id: assetId, values })
    });
    // Update local cache
    cfValuesCache[assetId] = { ...(cfValuesCache[assetId] || {}), ...values };
  } catch(e) {}
}

// ── Settings: manage field defs ───────────────────────────────────────────────
async function renderCfDefsList() {
  const defs = await loadCfDefs(true);
  const wrap = document.getElementById('cf-defs-list');
  if (!wrap) return;
  if (!defs.length) {
    wrap.innerHTML = '<p style="color:var(--muted);font-size:13px;padding:8px 0">No custom fields defined yet.</p>';
    return;
  }
  // Group by type
  const byType = {};
  defs.forEach(d => { (byType[d.asset_type] = byType[d.asset_type]||[]).push(d); });
  wrap.innerHTML = Object.entries(byType).map(([type, fields]) => `
    <div style="margin-bottom:14px">
      <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:6px">${esc(type)}</div>
      ${fields.map(d => `
        <div class="cf-def-row">
          <span class="cf-def-type">date</span>
          <span style="font-size:13px;font-weight:600;flex:1">${esc(d.label)}</span>
          <span style="font-size:11px;color:var(--muted);margin-right:8px">${esc(d.field_key)}</span>
          <button onclick="deleteCustomField(${d.id})" style="background:rgba(255,59,92,0.08);border:1px solid rgba(255,59,92,0.2);color:var(--red);border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700;cursor:pointer;font-family:'Outfit',sans-serif">Remove</button>
        </div>`).join('')}
    </div>`).join('');
}

async function addCustomField() {
  const type  = document.getElementById('cf-new-type')?.value;
  const label = document.getElementById('cf-new-label')?.value?.trim();
  if (!type)  { toast('Select an asset type','error'); return; }
  if (!label) { toast('Enter a field label','error'); return; }
  try {
    await apiFetch(FIELDS_API, { method:'POST', body: JSON.stringify({ asset_type: type, label }) });
    document.getElementById('cf-new-label').value = '';
    document.getElementById('cf-new-type').value  = '';
    toast(`Custom field added to ${type}`, 'success');
    await renderCfDefsList();
  // Init collapsible sections
  setTimeout(() => { wrapSettingsBodies(); initSettingsCollapse(); }, 50);
    cfDefsCache = null; // bust cache
  } catch(e) {
    if (e.message?.includes('409')) toast('Field already exists for this type', 'error');
  }
}

async function deleteCustomField(id) {
  if (!confirm('Remove this custom field? All stored values will be deleted.')) return;
  try {
    await apiFetch(FIELDS_API + '?id=' + id, { method:'DELETE' });
    toast('Custom field removed', 'success');
    cfDefsCache = null; cfValuesCache = {};
    await renderCfDefsList();
  // Init collapsible sections
  setTimeout(() => { wrapSettingsBodies(); initSettingsCollapse(); }, 50);
  } catch(e) {}
}

function sortBy(k){if(sortKey===k)sortAsc=!sortAsc;else{sortKey=k;sortAsc=true;}loadAssets();}

document.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('search-input')?.addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(loadAssets,300);});
  document.getElementById('filter-type')?.addEventListener('change',loadAssets);
  document.getElementById('filter-dept')?.addEventListener('change',loadAssets);
  document.getElementById('filter-status')?.addEventListener('change',loadAssets);
});

function openAddModal(){
  editingId=null;
  document.getElementById('modal-title').textContent='Add Asset';
  ['f-name','f-serial','f-assigned','f-notes'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('f-type').value='Laptop';
  document.getElementById('f-dept').value='';
  document.getElementById('f-status').value='active';
  document.getElementById('f-cost').value='';
  document.getElementById('ai-price-result').style.display='none';
  document.getElementById('f-type').value='Laptop';
  document.getElementById('f-date').value='';
  document.getElementById('f-eol').value='';
  document.getElementById('f-cost').value='';
  document.getElementById('f-eol-override').checked = false;
  document.getElementById('save-btn').textContent='Save Asset';
  document.getElementById('asset-modal').classList.add('open');
  setTimeout(()=>document.getElementById('f-name').focus(),300);
}

async function editAsset(id){
  const a=await apiFetch(API+'?id='+encodeURIComponent(id));
  editingId=id;
  document.getElementById('modal-title').textContent='Edit Asset';
  document.getElementById('f-name').value=a.name||'';
  document.getElementById('f-type').value=a.type||'Laptop';
  document.getElementById('f-serial').value=a.serial||'';
  document.getElementById('f-assigned').value=a.assignedTo||'';
  document.getElementById('f-dept').value=a.dept||'';
  document.getElementById('f-status').value=a.status||'active';
  document.getElementById('f-date').value=a.purchaseDate||'';
  document.getElementById('f-eol').value=a.endOfLife||'';
  document.getElementById('f-cost').value=a.cost||'';
  document.getElementById('f-notes').value=a.notes||'';
  document.getElementById('f-eol-override').checked=!!a.eolOverride;
  loadCustomFieldsForModal(a.type, a.id);
  document.getElementById('save-btn').textContent='Update Asset';
  document.getElementById('asset-modal').classList.add('open');
}

async function saveAsset(){
  const name=document.getElementById('f-name').value.trim();
  if(!name){toast('Please enter an asset name.','error');return;}
  const btn=document.getElementById('save-btn');
  btn.disabled=true; btn.textContent='Saving…';
  const payload={id:editingId,name,
    type:document.getElementById('f-type').value,
    serial:document.getElementById('f-serial').value.trim(),
    assigned_to:document.getElementById('f-assigned').value.trim(),
    department:document.getElementById('f-dept').value.trim(),
    status:document.getElementById('f-status').value,
    purchase_date:document.getElementById('f-date').value||null,
    end_of_life:document.getElementById('f-eol').value||null,
    cost:document.getElementById('f-cost').value||null,
    notes:document.getElementById('f-notes').value.trim(),
    eol_override:document.getElementById('f-eol-override').checked};
  try{
    let savedId = editingId;
    if(editingId){await apiFetch(API,{method:'PUT',body:JSON.stringify(payload)});toast('Asset updated!','success');}
    else{const created=await apiFetch(API,{method:'POST',body:JSON.stringify(payload)});savedId=created.id;toast('Asset added!','success');}
    await saveCustomFieldValues(savedId);
    closeModal('asset-modal'); loadAssets(); loadDashboard();
    // Refresh users if on that page
    if (document.getElementById('page-users').classList.contains('active')) loadUsers();
  }finally{btn.disabled=false; btn.textContent=editingId?'Update Asset':'Save Asset';}
}

async function deleteAsset(id){
  // Kept for legacy batch; direct delete now goes through archive flow
  if(!confirm('Delete this asset? This cannot be undone.'))return;
  await apiFetch(API+'?id='+encodeURIComponent(id),{method:'DELETE'});
  toast('Asset deleted.','success'); loadAssets(); loadDashboard();
  if (document.getElementById('page-users').classList.contains('active')) loadUsers();
}

function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open');}));

function showQR(id,name,serial){
  document.getElementById('qr-canvas-wrap').innerHTML='<div id="qr-gen"></div>';
  new QRCode(document.getElementById('qr-gen'),{
    text:JSON.stringify({id,name,serial}),width:200,height:200,
    colorDark:'#000000',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.H});
  document.getElementById('qr-info').innerHTML=`
    <div class="qr-asset-id">${esc(id)}</div>
    <div class="qr-asset-name">${esc(name)}</div>
    ${serial?`<div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--muted);text-align:center;margin-top:4px">${esc(serial)}</div>`:''}`;
  document.getElementById('qr-modal').classList.add('open');
}

function downloadQR(){
  const canvas=document.querySelector('#qr-gen canvas');if(!canvas)return;
  const a=document.createElement('a');
  a.href=canvas.toDataURL('image/png');
  a.download=(document.querySelector('.qr-asset-id')?.textContent||'asset')+'-qr.png';
  a.click();
}

function printQR(){
  const canvas=document.querySelector('#qr-gen canvas');
  const info=document.getElementById('qr-info').innerHTML;
  if(!canvas)return;
  const w=window.open('','_blank');
  w.document.write(`<html><head><title>Asset Label</title>
    <style>body{font-family:sans-serif;display:flex;flex-direction:column;align-items:center;padding:24px;background:#fff}
    img{border:2px solid #eee;border-radius:8px;padding:8px;margin-bottom:12px}
    .qr-asset-id{font-family:monospace;font-size:20px;font-weight:700;text-align:center;letter-spacing:2px;color:#000}
    .qr-asset-name{font-size:13px;color:#555;text-align:center}</style></head>
    <body><img src="${canvas.toDataURL()}">${info.replace(/var\(--[^)]+\)/g,'#333')}</body></html>`);
  w.document.close();w.focus();setTimeout(()=>{w.print();w.close();},500);
}

function loadHtml5QrCode(cb){
  if(window.Html5Qrcode){cb();return;}
  const s=document.createElement('script');
  s.src='https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js';
  s.onload=cb;document.head.appendChild(s);
}
function toggleScanner(){scannerActive?stopScanner():startScanner();}
function startScanner(){
  loadHtml5QrCode(()=>{
    document.getElementById('reader').innerHTML='';
    html5QrCode=new Html5Qrcode('reader');
    html5QrCode.start({facingMode:'environment'},{fps:10,qrbox:{width:240,height:240}},onScanSuccess,()=>{})
    .then(()=>{
      scannerActive=true;
      document.getElementById('scan-btn').innerHTML='<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Stop Camera';
    }).catch(err=>{
      document.getElementById('reader').innerHTML=`<div style="color:var(--red);padding:20px;font-size:13px;text-align:center">Camera error: ${err}<br><br>Must be on HTTPS and allow camera access.</div>`;
    });
  });
}
function stopScanner(){
  html5QrCode?.stop().catch(()=>{});html5QrCode=null;scannerActive=false;
  document.getElementById('scan-btn').innerHTML='<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/></svg> Start Camera';
  document.getElementById('reader').innerHTML='<div style="color:var(--muted);font-size:13px;text-align:center;padding:20px">Camera will appear here</div>';
}
async function onScanSuccess(decoded){
  stopScanner();
  let id=decoded;try{id=JSON.parse(decoded).id;}catch{}
  const el=document.getElementById('scan-result');
  try{
    const a=await apiFetch(API+'?id='+encodeURIComponent(id));
    el.className='scan-result visible';
    el.innerHTML=`
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
        <div><div style="font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--green);margin-bottom:4px;font-weight:700">✓ Asset Found</div>
        <div style="font-size:17px;font-weight:800">${esc(a.name)}</div></div>
        ${typeBadge(a.type)}
      </div>
      <div class="scan-result-grid">
        ${sf('Asset ID',a.id,true)}${sf('Serial',a.serial||'—',true)}
        ${sf('Assigned To',a.assignedTo||'Unassigned')}${sf('Department',a.dept||'—')}
        ${sf('Purchase Date',a.purchaseDate||'—')}${sf('Cost',a.cost?'$'+Number(a.cost).toLocaleString():'—')}
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary" style="font-size:13px;min-height:44px" onclick="editAsset('${esc(a.id)}');showPage('assets')">Edit Asset</button>
        <button class="btn btn-ghost" style="font-size:13px;min-height:44px" onclick="showQR('${esc(a.id)}','${esc(a.name)}','${esc(a.serial||'')}')">Show QR</button>
      </div>`;
  }catch{
    el.className='scan-result visible';
    el.innerHTML=`<div style="color:var(--red);font-weight:600">Asset not found: ${esc(decoded)}</div>`;
  }
}
function sf(label,val,mono=false){
  return `<div class="scan-field"><label>${label}</label><span style="${mono?'font-family:JetBrains Mono,monospace;font-size:12px':''}">${esc(String(val))}</span></div>`;
}

async function exportCSV(){
  const assets=await apiFetch(API);
  const h=['Asset ID','Name','Type','Serial','Assigned To','Department','Purchase Date','Cost','Notes'];
  const rows=assets.map(a=>[a.id,a.name,a.type,a.serial,a.assignedTo,a.dept,a.purchaseDate,a.cost,a.notes].map(v=>`"${(v||'').toString().replace(/"/g,'""')}"`));
  const csv=[h.join(','),...rows.map(r=>r.join(','))].join('\n');
  const url=URL.createObjectURL(new Blob([csv],{type:'text/csv'}));
  const el=document.createElement('a');el.href=url;el.download='assets-export.csv';el.click();URL.revokeObjectURL(url);
}

async function exportADP(btn) {
  const orig = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Generating…`;
  try {
    const res = await fetch('api/adp_export.php');
    if (!res.ok) {
      const err = await res.json();
      throw new Error(err.error || 'Export failed');
    }
    const blob = await res.blob();
    const url  = URL.createObjectURL(blob);
    const el   = document.createElement('a');
    const date = new Date().toISOString().split('T')[0];
    el.href = url;
    el.download = `AssetIQ_ADP_Export_${date}.csv`;
    el.click();
    URL.revokeObjectURL(url);
    toast('ADP export downloaded!', 'success');
  } catch(e) {
    toast(e.message || 'ADP export failed', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = orig;
  }
}

let toastTimer;
function toast(msg,type='success'){
  const el=document.getElementById('toast');
  el.textContent=msg;el.className=`toast show ${type}`;
  clearTimeout(toastTimer);toastTimer=setTimeout(()=>el.classList.remove('show'),3000);
}

// Auto-set end of life = purchase date + 6 years
function autoSetEOL(){
  const d = document.getElementById('f-date').value;
  if (!d) return;
  const eol = new Date(d);
  eol.setFullYear(eol.getFullYear() + 6);
  document.getElementById('f-eol').value = eol.toISOString().split('T')[0];
}

// Returns 'critical' (past or within 3mo), 'warning' (within 12mo), or null
function eolStatus(eolDate, override=false) {
  if (override) return 'override';
  if (!eolDate) return null;
  const today = new Date(); today.setHours(0,0,0,0);
  const eol   = new Date(eolDate);
  const msLeft = eol - today;
  const daysLeft = msLeft / 86400000;
  if (daysLeft <= 90)  return 'critical';
  if (daysLeft <= 365) return 'warning';
  return null;
}

// Returns a flag badge HTML string or null
function eolFlag(eolDate, override=false) {
  if (override) return `<span class="flag-override">✓ EOL Acknowledged</span>`;
  const s = eolStatus(eolDate);
  if (!s) return null;
  if (s === 'critical') {
    const d = new Date(eolDate);
    const past = d < new Date();
    return `<span class="flag-critical">🔴 ${past ? 'EOL Overdue' : 'Replace Soon'}</span>`;
  }
  return `<span class="flag-warning">🟡 Due This Year</span>`;
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function typeBadge(t){return `<span class="badge ${T[t]||'badge-laptop'}">${esc(t)}</span>`;}
function statusBadge(assignedTo, status) {
  if (status === 'retired') return '<span class="badge badge-retired">Retired</span>';
  return assignedTo ? '<span class="badge badge-assigned">Assigned</span>' : '<span class="badge badge-unassigned">Unassigned</span>';
}

// ── USERS ─────────────────────────────────────────────────────
let allUsersData = [];
let allDeptData  = [];
let usersView    = 'user';

const DEPT_COLORS = {IT:'#00e5ff',Finance:'#00ff88',Claims:'#ff8c00',Management:'#a78bfa',Marketing:'#ff3b5c',Underwriting:'#38bdf8',Agent:'#fb923c','No Longer At SEM':'#6b7280',Unassigned:'#5a6070'};
const BADGE_MAP   = {Laptop:'badge-laptop',Desktop:'badge-desktop',Monitor:'badge-monitor',Peripheral:'badge-peripheral','Docking Station':'badge-docking',Printer:'badge-printer',Camera:'badge-camera'};

function setUsersView(v) {
  usersView = v;
  document.getElementById('users-view-user').className = v==='user'?'btn btn-primary':'btn btn-ghost';
  document.getElementById('users-view-dept').className = v==='dept'?'btn btn-primary':'btn btn-ghost';
  document.getElementById('users-view-user').style.cssText='width:auto;padding:10px 14px;font-size:12px;min-height:40px';
  document.getElementById('users-view-dept').style.cssText='width:auto;padding:10px 14px;font-size:12px;min-height:40px';
  document.getElementById('users-search').placeholder = v==='user'?'Search users…':'Search departments…';
  filterUsers();
}

async function loadUsers() {
  document.getElementById('users-list').innerHTML = '<div class="spinner"></div>';
  document.getElementById('users-search').value   = '';
  const assets = await apiFetch(API);

  // Group by user
  const userMap = {};
  assets.forEach(a => {
    if (!a.assignedTo?.trim()) return;
    const n = a.assignedTo.trim();
    if (!userMap[n]) userMap[n] = [];
    userMap[n].push(a);
  });
  allUsersData = Object.entries(userMap).map(([name,assets])=>({name,assets})).sort((a,b)=>a.name.localeCompare(b.name));

  // Group by department
  const DEPTS = ['IT','Finance','Claims','Management','Marketing','Underwriting','Agent','No Longer At SEM','Unassigned'];
  const deptMap = {};
  DEPTS.forEach(d => deptMap[d] = []);
  assets.forEach(a => {
    const d = a.dept?.trim();
    if (d && deptMap[d]!==undefined) deptMap[d].push(a);
    else deptMap['Unassigned'].push(a);
  });
  allDeptData = DEPTS.map(name=>({name,assets:deptMap[name]})).filter(d=>d.assets.length>0);

  filterUsers();
}

function filterUsers() {
  const q = (document.getElementById('users-search')?.value||'').toLowerCase();
  if (usersView==='user') {
    renderUserCards(q ? allUsersData.filter(u=>u.name.toLowerCase().includes(q)) : allUsersData);
  } else {
    renderDeptCards(q ? allDeptData.filter(d=>d.name.toLowerCase().includes(q)) : allDeptData);
  }
}

function buildTypeChips(assets) {
  const counts = {};
  assets.forEach(a => { counts[a.type]=(counts[a.type]||0)+1; });
  return Object.entries(counts).map(([type,count])=>`<div class="user-asset-chip">
    <span class="badge ${BADGE_MAP[type]||'badge-laptop'}" style="padding:1px 6px;font-size:10px">${esc(type)}</span>
    <span style="color:var(--text)">${count}</span>
  </div>`).join('');
}

function buildAssetDetailRow(a) {
  const flag=eolFlag(a.endOfLife), status=eolStatus(a.endOfLife);
  return `<div class="user-detail-asset" onclick="closeModal('user-modal');editAsset('${esc(a.id)}');showPage('assets')">
    <div class="user-detail-asset-name">
      <span style="flex:1;min-width:0">${esc(a.name)}</span>
      <div style="display:flex;gap:5px;flex-shrink:0;align-items:center">${typeBadge(a.type)}${flag||''}</div>
    </div>
    <div class="user-detail-asset-fields">
      <div class="user-detail-field"><label>Asset ID</label><span style="font-family:'JetBrains Mono',monospace;font-size:11px">${esc(a.id)}</span></div>
      <div class="user-detail-field"><label>Serial No.</label><span style="font-family:'JetBrains Mono',monospace;font-size:11px">${esc(a.serial)||'—'}</span></div>
      <div class="user-detail-field"><label>Assigned To</label><span>${esc(a.assignedTo)||'—'}</span></div>
      <div class="user-detail-field"><label>Department</label><span>${esc(a.dept)||'—'}</span></div>
      <div class="user-detail-field"><label>Purchase Date</label><span>${a.purchaseDate||'—'}</span></div>
      <div class="user-detail-field"><label>End of Life</label><span style="${status==='critical'?'color:var(--red)':status==='warning'?'color:var(--orange)':''}">${a.endOfLife||'—'}</span></div>
    </div>
    <div style="margin-top:8px;font-size:11px;color:var(--muted);display:flex;align-items:center;gap:4px">
      <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>
      Tap to edit
    </div>
  </div>`;
}

function renderUserCards(users) {
  const el = document.getElementById('users-list');
  if (!users.length) { el.innerHTML=`<div class="users-empty"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:12px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg><h3>No users found</h3><p style="font-size:13px">Users appear here once assets are assigned to them.</p></div>`; return; }
  el.innerHTML = users.map(u => {
    const initials=u.name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
    const hasFlags=u.assets.some(a=>eolStatus(a.endOfLife)!==null);
    return `<div class="user-card" onclick="openUserModal('${esc(u.name)}')">
      <div class="user-card-header">
        <div class="user-avatar">${esc(initials)}</div>
        <div style="flex:1;min-width:0">
          <div class="user-name">${esc(u.name)}</div>
          <div class="user-meta">${u.assets.length} asset${u.assets.length!==1?'s':''}${hasFlags?' · <span style="color:var(--orange)">⚠ EOL alert</span>':''}</div>
        </div>
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--muted);flex-shrink:0" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
      </div>
      <div class="user-asset-chips">${buildTypeChips(u.assets)}</div>
    </div>`;
  }).join('');
}

function renderDeptCards(depts) {
  const el = document.getElementById('users-list');
  if (!depts.length) { el.innerHTML=`<div class="users-empty"><svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="opacity:.3;margin-bottom:12px"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg><h3>No departments found</h3><p style="font-size:13px">Assign departments to assets to see them here.</p></div>`; return; }
  el.innerHTML = depts.map(d => {
    const color=DEPT_COLORS[d.name]||'#5a6070';
    const hasFlags=d.assets.some(a=>eolStatus(a.endOfLife)!==null);
    return `<div class="user-card" onclick="openDeptModal('${esc(d.name)}')">
      <div class="user-card-header">
        <div class="user-avatar" style="background:linear-gradient(135deg,${color}22,${color}44);border:1px solid ${color}33;color:${color}">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
        </div>
        <div style="flex:1;min-width:0">
          <div class="user-name">${esc(d.name)}</div>
          <div class="user-meta">${d.assets.length} asset${d.assets.length!==1?'s':''}${hasFlags?' · <span style="color:var(--orange)">⚠ EOL alert</span>':''}</div>
        </div>
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--muted);flex-shrink:0" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
      </div>
      <div class="user-asset-chips">${buildTypeChips(d.assets)}</div>
    </div>`;
  }).join('');
}

function openUserModal(name) {
  const user=allUsersData.find(u=>u.name===name); if(!user) return;
  const initials=name.split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
  document.getElementById('user-modal-name').innerHTML=`<div style="display:flex;align-items:center;gap:10px"><div class="user-avatar" style="width:34px;height:34px;font-size:13px">${esc(initials)}</div>${esc(name)}</div>`;
  document.getElementById('user-modal-count').textContent=`${user.assets.length} assigned asset${user.assets.length!==1?'s':''}`;
  document.getElementById('user-modal-body').innerHTML=user.assets.map(a=>buildAssetDetailRow(a)).join('');
  document.getElementById('user-modal').classList.add('open');
}

function openDeptModal(name) {
  const dept=allDeptData.find(d=>d.name===name); if(!dept) return;
  const color=DEPT_COLORS[name]||'#5a6070';
  document.getElementById('user-modal-name').innerHTML=`<div style="display:flex;align-items:center;gap:10px"><div class="user-avatar" style="width:34px;height:34px;background:linear-gradient(135deg,${color}22,${color}44);border:1px solid ${color}33;color:${color}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>${esc(name)}</div>`;
  document.getElementById('user-modal-count').textContent=`${dept.assets.length} asset${dept.assets.length!==1?'s':''}`;
  document.getElementById('user-modal-body').innerHTML=dept.assets.map(a=>buildAssetDetailRow(a)).join('');
  document.getElementById('user-modal').classList.add('open');
}

// ── INTUNE ────────────────────────────────────────────────────
const INTUNE_API = 'api/intune.php';
let intuneDevices = [];
let intuneSelected = new Set();

async function initIntunePage() {
  try {
    const res  = await fetch(INTUNE_API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'test'})});
    const data = await res.json();
    const show = !data.unconfigured;
    document.getElementById('intune-unconfigured').style.display = show ? 'none'  : 'block';
    document.getElementById('intune-configured').style.display   = show ? 'block' : 'none';
  } catch {
    document.getElementById('intune-unconfigured').style.display = 'none';
    document.getElementById('intune-configured').style.display   = 'block';
  }
}

async function intuneTestConnection() {
  const status = document.getElementById('intune-status');
  status.textContent = 'Testing…'; status.style.color = 'var(--muted)';
  try {
    const res  = await fetch(INTUNE_API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'test'})});
    const data = await res.json();
    if (data.success) { status.textContent = '✓ Connected to Intune'; status.style.color = 'var(--green)'; }
    else              { status.textContent = '✗ ' + (data.error||'Failed'); status.style.color = 'var(--red)'; }
  } catch(e) { status.textContent = '✗ ' + e.message; status.style.color = 'var(--red)'; }
}

async function intuneFetch() {
  const btn    = document.getElementById('intune-fetch-btn');
  const status = document.getElementById('intune-status');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:14px;height:14px;border-width:2px;margin:0"></div> Fetching…';
  status.textContent = ''; intuneDevices = []; intuneSelected = new Set();
  document.getElementById('intune-filter-bar').style.display = 'none';
  document.getElementById('intune-device-list').innerHTML = '<div class="spinner"></div>';
  try {
    const res  = await fetch(INTUNE_API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'fetch'})});
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    intuneDevices = data.devices || [];
    status.textContent = `Found ${data.total} devices — ${data.new} not yet imported`;
    status.style.color = 'var(--muted)';
    document.getElementById('intune-filter-bar').style.display = 'block';
    intuneDevices.forEach((_,i) => { if (!intuneDevices[i].alreadyExists) intuneSelected.add(i); });
    renderIntuneDevices();
  } catch(e) {
    document.getElementById('intune-device-list').innerHTML = `<div class="eol-banner">✗ ${esc(e.message)}</div>`;
    status.textContent = '';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg> Fetch from Intune';
  }
}

function renderIntuneDevices() {
  const q         = (document.getElementById('intune-search')?.value||'').toLowerCase();
  const hideExist = document.getElementById('intune-hide-existing')?.checked;
  const list      = document.getElementById('intune-device-list');
  const selInfo   = document.getElementById('intune-selection-info');
  if (!intuneDevices.length) { list.innerHTML = ''; return; }

  const filtered = intuneDevices.filter((d,i) => {
    if (hideExist && d.alreadyExists) return false;
    if (q && !([d.deviceName,d.model,d.serial,d.assignedTo,d.os].join(' ').toLowerCase().includes(q))) return false;
    return true;
  });

  const newCount   = [...intuneSelected].filter(i => !intuneDevices[i]?.alreadyExists).length;
  selInfo.textContent = `${intuneSelected.size} selected · ${newCount} new to import`;

  const filteredIdx = filtered.map(d => intuneDevices.indexOf(d));
  const allSelected = filteredIdx.length > 0 && filteredIdx.every(i => intuneSelected.has(i));

  list.innerHTML = `
    <div class="select-all-row" onclick="intuneToggleAll()">
      <div class="intune-check" style="${allSelected?'background:var(--accent);border-color:var(--accent)':''}">
        ${allSelected?'<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>':''}
      </div>
      <span>${allSelected?'Deselect all':'Select all'} (${filtered.length} shown)</span>
    </div>
    ${filtered.map(d => {
      const idx = intuneDevices.indexOf(d);
      const sel = intuneSelected.has(idx);
      const comp = (d.compliance||'unknown').toLowerCase();
      return `<div class="intune-device-card ${sel?'selected':''} ${d.alreadyExists?'already-exists':''}" onclick="intuneToggle(${idx})">
        <div class="intune-check">${sel?'<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>':''}</div>
        <div class="intune-device-info">
          <div class="intune-device-name">${esc(d.deviceName)}</div>
          <div class="intune-device-meta">
            ${d.model?`<span>📦 ${esc(d.model)}</span>`:''}
            ${d.serial?`<span>🔢 ${esc(d.serial)}</span>`:''}
            ${d.assignedTo?`<span>👤 ${esc(d.assignedTo)}</span>`:''}
            ${d.enrolledDate?`<span>📅 ${d.enrolledDate}</span>`:''}
          </div>
          <div class="intune-badges">
            ${typeBadge(d.type)}
            <span class="badge compliance-${comp}">${comp}</span>
            ${d.alreadyExists?'<span class="badge" style="background:rgba(90,96,112,0.15);color:var(--muted)">Already imported</span>':''}
            ${d.os?`<span class="badge" style="background:var(--surface2);color:var(--muted)">${esc(d.os.trim())}</span>`:''}
          </div>
        </div>
      </div>`;
    }).join('')}`;
}

function intuneToggle(idx) {
  if (intuneSelected.has(idx)) intuneSelected.delete(idx); else intuneSelected.add(idx);
  renderIntuneDevices();
}

function intuneToggleAll() {
  const q         = (document.getElementById('intune-search')?.value||'').toLowerCase();
  const hideExist = document.getElementById('intune-hide-existing')?.checked;
  const filtered  = intuneDevices.filter(d => {
    if (hideExist && d.alreadyExists) return false;
    if (q && !([d.deviceName,d.model,d.serial,d.assignedTo].join(' ').toLowerCase().includes(q))) return false;
    return true;
  });
  const idxs       = filtered.map(d => intuneDevices.indexOf(d));
  const allSelected = idxs.every(i => intuneSelected.has(i));
  if (allSelected) idxs.forEach(i => intuneSelected.delete(i));
  else             idxs.forEach(i => intuneSelected.add(i));
  renderIntuneDevices();
}

async function intuneImportSelected() {
  if (intuneSelected.size === 0) { toast('No devices selected.','error'); return; }
  const toImport = [...intuneSelected].map(i => intuneDevices[i]).filter(d => !d.alreadyExists);
  if (toImport.length === 0) { toast('All selected are already imported.','error'); return; }
  if (!confirm(`Import ${toImport.length} device${toImport.length>1?'s':''} into AssetIQ?`)) return;
  const btn = document.getElementById('intune-import-btn');
  btn.disabled = true; btn.textContent = 'Importing…';
  try {
    const res  = await fetch(INTUNE_API, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'import',devices:toImport})});
    const data = await res.json();
    if (data.error) throw new Error(data.error);
    toast(`✓ Imported ${data.imported} device${data.imported>1?'s':''}${data.skipped?' ('+data.skipped+' skipped)':''}`, 'success');
    await intuneFetch(); loadDashboard();
  } catch(e) { toast(e.message,'error'); }
  finally {
    btn.disabled = false;
    btn.innerHTML = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg> Import Selected';
  }
}

loadDashboard();
loadAssets();

// Mouse spotlight & tilt
function cardMove(e,card){
  var r=card.getBoundingClientRect(),x=e.clientX-r.left,y=e.clientY-r.top;
  card.style.setProperty('--mx',(x/r.width*100).toFixed(1)+'%');
  card.style.setProperty('--my',(y/r.height*100).toFixed(1)+'%');
  var cx=x-r.width/2,cy=y-r.height/2;
  var tx=(-cy/r.height*7).toFixed(2),ty=(cx/r.width*7).toFixed(2);
  card.style.transform='perspective(700px) rotateX('+tx+'deg) rotateY('+ty+'deg) translateZ(3px)';
}
function cardLeave(card){
  card.style.transform='';
  card.style.setProperty('--mx','50%');
  card.style.setProperty('--my','50%');
}
document.addEventListener('mousemove',function(e){
  var card=e.target.closest&&e.target.closest('.stat-card,.user-card,.intune-device-card');
  if(!card)return;
  var r=card.getBoundingClientRect(),x=e.clientX-r.left,y=e.clientY-r.top;
  card.style.setProperty('--mx',(x/r.width*100).toFixed(1)+'%');
  card.style.setProperty('--my',(y/r.height*100).toFixed(1)+'%');
});
var _sObs=new MutationObserver(function(muts){
  muts.forEach(function(m){m.addedNodes.forEach(function(n){
    if(n.nodeType!==1)return;
    var sel='.stat-card,.user-card,.intune-device-card';
    var cards=n.matches&&n.matches(sel)?[n]:Array.from(n.querySelectorAll?n.querySelectorAll(sel):[]);
    cards.forEach(function(card){
      if(!card.querySelector('.card-spotlight')){
        var sp=document.createElement('div');sp.className='card-spotlight';
        card.insertBefore(sp,card.firstChild);
      }
    });
  });});
});
_sObs.observe(document.body,{childList:true,subtree:true});

</script>

<!-- ARCHIVE PAGE -->
<div class="page" id="page-archive">
  <h1 class="page-title">Archive</h1>
  <p class="page-sub" style="color:var(--muted);margin-top:-8px;margin-bottom:16px;font-size:14px">Archived assets are hidden from the main list. Restore or permanently delete them here.</p>
  <div class="filter-bar" style="margin-bottom:16px">
    <div class="search-wrap" style="flex:1">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      <input type="text" id="archive-search" placeholder="Search archived assets…" oninput="clearTimeout(archiveTimer);archiveTimer=setTimeout(loadArchive,300)">
    </div>
  </div>
  <div id="archive-list" class="asset-list"></div>
  <div id="archive-empty" class="empty-state" style="display:none;text-align:center;padding:60px 20px">
    <svg width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;color:var(--muted)"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
    <h3 style="color:var(--muted);font-weight:600;margin-bottom:4px">No archived assets</h3>
    <p style="color:var(--muted);font-size:13px">Assets you archive will appear here</p>
  </div>
</div>

<!-- ACTIVITY PAGE -->
<div class="page" id="page-activity">
  <h1 class="page-title">Activity Log</h1>
  <p class="page-sub" style="color:var(--muted);margin-top:-8px;margin-bottom:20px;font-size:14px">Full audit trail — every create, edit, archive, restore, and delete.</p>
  <div id="activity-list" style="display:flex;flex-direction:column;gap:6px"></div>
  <div id="activity-empty" class="empty-state" style="display:none;text-align:center;padding:60px 20px">
    <svg width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;color:var(--muted)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
    <h3 style="color:var(--muted);font-weight:600;margin-bottom:4px">No activity yet</h3>
    <p style="color:var(--muted);font-size:13px">Changes to assets will be tracked here</p>
  </div>
  <button id="activity-load-more" onclick="loadMoreActivity()" style="display:none;width:100%;margin-top:16px;padding:12px;background:var(--surface2);border:1px solid var(--border2);color:var(--fg2);border-radius:10px;cursor:pointer;font-family:'Outfit',sans-serif;font-weight:600;font-size:13px;transition:background 0.2s">
    Load more
  </button>
</div>


<!-- REPORTS PAGE -->
<div class="page" id="page-reports">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div>
      <h1 class="page-title" style="margin-bottom:4px">Reports</h1>
      <p style="color:var(--muted);font-size:14px">Cost analysis, depreciation &amp; EOL forecasting</p>
    </div>
    <button class="btn btn-primary" onclick="exportCSV()" style="gap:8px">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </button>
  </div>

  <!-- Summary cards -->
  <div id="report-summary-cards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px"></div>

  <!-- By Department -->
  <div class="report-section">
    <h2 class="report-section-title">Value by Department</h2>
    <div id="report-by-dept" class="report-table-wrap"></div>
  </div>

  <!-- By Type -->
  <div class="report-section">
    <h2 class="report-section-title">Inventory by Type</h2>
    <div id="report-by-type" class="report-table-wrap"></div>
  </div>

  <!-- Depreciation -->
  <div class="report-section">
    <h2 class="report-section-title">Depreciation (Straight-line, 5 yr)</h2>
    <div id="report-depreciation" class="report-table-wrap"></div>
  </div>

  <!-- EOL Status -->
  <div class="report-section">
    <h2 class="report-section-title">EOL &amp; Warranty Status</h2>
    <div id="report-eol" class="report-table-wrap"></div>
  </div>
</div>

<!-- SETTINGS PAGE -->
<div class="page" id="page-settings">
  <h1 class="page-title">Settings</h1>
  <p style="color:var(--muted);font-size:14px;margin-top:-8px;margin-bottom:20px">Configure alerts and system preferences</p>
  <div style="display:flex;gap:4px;margin-bottom:24px;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:4px;width:fit-content">
    <button id="stab-general" class="settings-tab" onclick="switchSettingsTab('general')" style="padding:7px 16px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:'Outfit',sans-serif;background:rgba(0,229,255,0.1);color:var(--accent);transition:all 0.15s">General</button>
    <button id="stab-intune"  class="settings-tab" onclick="switchSettingsTab('intune')"  style="padding:7px 16px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:'Outfit',sans-serif;background:transparent;color:var(--muted);transition:all 0.15s">Intune</button>
  </div>
  <div id="settings-tab-general">
  <div class="settings-section">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div>
        <h2 class="settings-section-title">Low Stock Alerts</h2>
        <p class="settings-section-sub">Alert when unassigned count drops below threshold</p>
      </div>
      <label class="toggle-wrap">
        <input type="checkbox" id="setting-alerts-enabled" onchange="saveAlertToggle(this.checked)">
        <span class="toggle-track"><span class="toggle-thumb"></span></span>
      </label>
    </div>
    <div id="threshold-fields" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
      <!-- filled by JS -->
    </div>
    <button class="btn btn-primary" style="margin-top:16px" onclick="saveThresholds()">Save Thresholds</button>
  </div>

  <div class="settings-section" id="cf-settings-section">
    <h2 class="settings-section-title">Depreciation</h2>
    <p class="settings-section-sub">Straight-line depreciation lifetime in years</p>
    <div style="display:flex;align-items:center;gap:12px;margin-top:12px">
      <input type="number" id="setting-depr-years" min="1" max="20" value="5" style="width:80px">
      <button class="btn btn-primary" onclick="saveDeprYears()">Save</button>
    </div>
  </div>

  <!-- Custom Fields -->
  <div class="settings-section" id="cf-settings-section">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <div>
        <h2 class="settings-section-title">Custom Date Fields</h2>
        <p class="settings-section-sub">Add extra date fields to specific asset types (e.g. Warranty Expiry, Last Service Date)</p>
      </div>
    </div>
    <!-- Add field form -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
      <select id="cf-new-type" style="flex:1;min-width:120px">
        <option value="">Asset type…</option>
        <option>Laptop</option><option>Desktop</option><option>Monitor</option>
        <option>Docking Station</option><option>Printer</option><option>Camera</option><option>Other</option>
      </select>
      <input type="text" id="cf-new-label" placeholder="Field label (e.g. Warranty Expiry)" style="flex:2;min-width:160px">
      <button class="btn btn-primary" onclick="addCustomField()" style="white-space:nowrap">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        Add Field
      </button>
    </div>
    <!-- Existing fields list -->
    <div id="cf-defs-list">
      <div style="color:var(--muted);font-size:13px;padding:8px 0">Loading…</div>
    </div>
  </div>
  </div><!-- /settings-tab-general -->

  <!-- Intune tab -->
  <div id="settings-tab-intune" style="display:none">
    <div id="settings-intune-mount"></div>
  </div>
</div>

</body>
</html>
