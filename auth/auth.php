<?php
// ─────────────────────────────────────────────────────────────
//  AssetIQ — Auth Helper
// ─────────────────────────────────────────────────────────────

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', 86400);
    session_name('assetiq_sess');
    session_start();
}

function auth_user(): ?array {
    return $_SESSION['auth_user'] ?? null;
}

function auth_require(): void {
    if (!auth_user()) {
        $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: /auth/login.php');
        exit;
    }
}

function auth_require_json(): void {
    if (!auth_user()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    // Also sign out of Microsoft
    $logoutUrl = 'https://login.microsoftonline.com/' . INTUNE_TENANT_ID
        . '/oauth2/v2.0/logout?post_logout_redirect_uri=' . urlencode(APP_URL . '/auth/login.php');
    header('Location: ' . $logoutUrl);
    exit;
}

function auth_build_login_url(): string {
    // Use a signed state token instead of session-stored state
    // This avoids session persistence issues across redirect
    $state = auth_make_state();

    $params = http_build_query([
        'client_id'     => INTUNE_CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri'  => APP_URL . '/auth/callback.php',
        'response_mode' => 'query',
        'scope'         => 'openid profile email User.Read',
        'state'         => $state,
    ]);

    return "https://login.microsoftonline.com/" . INTUNE_TENANT_ID . "/oauth2/v2.0/authorize?$params";
}

function auth_make_state(): string {
    // State = timestamp:hmac — no session dependency
    $ts      = time();
    $secret  = defined('APP_SECRET') ? APP_SECRET : (INTUNE_CLIENT_SECRET . INTUNE_CLIENT_ID);
    $hmac    = substr(hash_hmac('sha256', $ts, $secret), 0, 16);
    return base64_encode("$ts:$hmac");
}

function auth_verify_state(string $state): bool {
    $decoded = base64_decode($state);
    if (!$decoded || !str_contains($decoded, ':')) return false;
    [$ts, $hmac] = explode(':', $decoded, 2);
    // Must be within 10 minutes
    if (abs(time() - (int)$ts) > 600) return false;
    $secret   = defined('APP_SECRET') ? APP_SECRET : (INTUNE_CLIENT_SECRET . INTUNE_CLIENT_ID);
    $expected = substr(hash_hmac('sha256', $ts, $secret), 0, 16);
    return hash_equals($expected, $hmac);
}

function auth_exchange_code(string $code): array {
    $ch = curl_init("https://login.microsoftonline.com/" . INTUNE_TENANT_ID . "/oauth2/v2.0/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => INTUNE_CLIENT_ID,
            'client_secret' => INTUNE_CLIENT_SECRET,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => APP_URL . '/auth/callback.php',
            'scope'         => 'openid profile email User.Read',
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true);
    if ($httpCode !== 200 || empty($data['access_token'])) {
        throw new Exception($data['error_description'] ?? 'Token exchange failed');
    }
    return $data;
}

function auth_get_user_info(string $accessToken): array {
    $ch = curl_init('https://graph.microsoft.com/v1.0/me?$select=id,displayName,mail,userPrincipalName,department,jobTitle');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $accessToken"],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($res, true);
    if ($httpCode !== 200 || empty($data['id'])) {
        throw new Exception('Could not fetch user profile from Microsoft');
    }
    return $data;
}
