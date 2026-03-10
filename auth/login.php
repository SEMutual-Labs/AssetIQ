<?php
require_once __DIR__ . '/auth.php';

// Already logged in
if (auth_user()) {
    header('Location: /');
    exit;
}

$loginUrl = auth_build_login_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AssetIQ — Sign In</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:     #0a0c10;
    --card:   #111318;
    --border: #1e2230;
    --accent: #00e5ff;
    --text:   #e8eaf0;
    --muted:  #5a6070;
  }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', system-ui, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  /* Subtle grid background */
  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(rgba(0,229,255,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(0,229,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
  }

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 48px 40px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    position: relative;
    box-shadow: 0 0 80px rgba(0,229,255,0.04);
  }

  .logo {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, rgba(0,229,255,0.15), rgba(0,229,255,0.05));
    border: 1px solid rgba(0,229,255,0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
  }

  h1 {
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.5px;
    margin-bottom: 8px;
  }

  h1 span { color: var(--accent); }

  .subtitle {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 36px;
    line-height: 1.5;
  }

  .btn-ms {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    padding: 14px 20px;
    background: #2f2f2f;
    border: 1px solid #444;
    border-radius: 10px;
    color: #fff;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s, transform 0.1s;
  }

  .btn-ms:hover {
    background: #3a3a3a;
    border-color: #666;
    transform: translateY(-1px);
  }

  .btn-ms:active { transform: translateY(0); }

  .ms-logo {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
    width: 20px;
    height: 20px;
    flex-shrink: 0;
  }

  .ms-logo span {
    display: block;
    width: 9px;
    height: 9px;
  }

  .ms-logo span:nth-child(1) { background: #f25022; }
  .ms-logo span:nth-child(2) { background: #7fba00; }
  .ms-logo span:nth-child(3) { background: #00a4ef; }
  .ms-logo span:nth-child(4) { background: #ffb900; }

  .footer {
    margin-top: 28px;
    font-size: 12px;
    color: var(--muted);
  }

  @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00e5ff" stroke-width="1.8">
      <rect x="2" y="3" width="20" height="14" rx="2"/>
      <path d="M8 21h8M12 17v4"/>
      <path d="M7 8h.01M10 8h7M7 11h5"/>
    </svg>
  </div>

  <h1>Asset<span>IQ</span></h1>
  <p class="subtitle">Sign in with your organization account<br>to access IT asset management.</p>

  <a href="<?= htmlspecialchars($loginUrl) ?>" class="btn-ms">
    <div class="ms-logo">
      <span></span><span></span><span></span><span></span>
    </div>
    Sign in with Microsoft
  </a>

  <p class="footer">Access restricted to your organization.</p>
</div>
</body>
</html>
