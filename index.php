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
