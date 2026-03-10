<?php
require_once __DIR__ . '/auth.php';

// Handle errors from Microsoft
if (!empty($_GET['error'])) {
    $msg = htmlspecialchars($_GET['error_description'] ?? $_GET['error']);
    die(renderError("Microsoft sign-in failed", $msg));
}

// Validate state using HMAC (no session dependency)
$state = $_GET['state'] ?? '';
if (!$state || !auth_verify_state($state)) {
    die(renderError("Security error", "State validation failed. This can happen if the login took more than 10 minutes. Please try again."));
}

// Exchange code for tokens
$code = $_GET['code'] ?? '';
if (!$code) {
    die(renderError("Sign-in failed", "No authorization code received from Microsoft."));
}

try {
    $tokens = auth_exchange_code($code);
    $msUser = auth_get_user_info($tokens['access_token']);

    $_SESSION['auth_user'] = [
        'id'        => $msUser['id'],
        'name'      => $msUser['displayName']      ?? 'Unknown',
        'email'     => $msUser['mail']              ?? $msUser['userPrincipalName'] ?? '',
        'upn'       => $msUser['userPrincipalName'] ?? '',
        'dept'      => $msUser['department']        ?? '',
        'title'     => $msUser['jobTitle']          ?? '',
        'logged_in' => time(),
    ];

    $redirect = $_SESSION['auth_redirect'] ?? '/';
    unset($_SESSION['auth_redirect']);
    if (!preg_match('/^\//', $redirect)) $redirect = '/';

    header("Location: $redirect");
    exit;

} catch (Exception $e) {
    die(renderError("Sign-in failed", htmlspecialchars($e->getMessage())));
}

function renderError(string $title, string $msg): string {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>AssetIQ — Error</title>
<style>
  body { background:#0a0c10; color:#e8eaf0; font-family:system-ui,sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; padding:24px; }
  .card { background:#111318; border:1px solid #1e2230; border-radius:16px; padding:40px; max-width:440px; text-align:center; }
  h2 { color:#ff3b5c; margin-bottom:12px; }
  p  { color:#5a6070; font-size:14px; margin-bottom:24px; line-height:1.6; }
  a  { color:#00e5ff; text-decoration:none; font-weight:600; }
</style>
</head>
<body>
<div class="card">
  <h2>$title</h2>
  <p>$msg</p>
  <a href="/auth/login.php">← Try again</a>
</div>
</body>
</html>
HTML;
}
