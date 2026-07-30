<?php
/**
 * Canva Connect — OAuth 2.0 (Authorization Code + PKCE, SHA-256)
 *
 * One endpoint, two jobs:
 *   1. START     GET /core/canva-oauth.php?start=<CANVA_SETUP_KEY>
 *                Generates a PKCE verifier, stores it against a random state,
 *                and redirects to Canva's consent screen.
 *   2. CALLBACK  Canva redirects back here with ?code=...&state=...
 *                Exchanges the code for tokens and writes them to
 *                CANVA_TOKEN_FILE (outside web root, mode 600).
 *
 * This must be registered as the redirect URL in the Developer Portal:
 *   https://www.bombayengg.net/core/canva-oauth.php
 *
 * The start leg is gated by CANVA_SETUP_KEY so a passer-by cannot kick off an
 * authorisation flow against our integration. The callback leg validates the
 * state parameter, which is both CSRF protection and how we find the matching
 * PKCE verifier.
 *
 * Credentials never appear in this file — they live in canva-config.php, which
 * sits outside the web root and is not in git.
 */

require_once '/home/bombayengg/canva-config.php';
date_default_timezone_set('Asia/Kolkata');

header('Content-Type: text/html; charset=utf-8');

function page(string $title, string $body, bool $ok = true): void
{
    $c = $ok ? '#157bba' : '#a9761f';
    echo "<!doctype html><meta charset=utf-8><meta name=robots content=noindex>"
       . "<title>Canva — $title</title>"
       . "<style>body{font-family:system-ui,sans-serif;background:#f8fafb;color:#1a1a2e;"
       . "padding:48px 24px;line-height:1.6}.w{max-width:640px;margin:0 auto;background:#fff;"
       . "border:1px solid #e4ebf1;border-left:8px solid $c;padding:28px 32px}"
       . "h1{font-size:22px;margin-bottom:12px}code{background:#eaf4fb;padding:2px 7px;"
       . "font-size:13px;word-break:break-all}</style>"
       . "<div class=w><h1>$title</h1>$body</div>";
    exit;
}

function canvaLog(string $m): void
{
    $dir = '/home/bombayengg/public_html/logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents("$dir/canva-" . date('Y-m-d') . '.log',
        '[' . date('Y-m-d H:i:s') . "] $m\n", FILE_APPEND | LOCK_EX);
}

if (CANVA_CLIENT_ID === '' || CANVA_CLIENT_SECRET === '') {
    page('Not configured yet',
        '<p>Paste the Client ID and Client Secret from the Canva Developer Portal into '
      . '<code>/home/bombayengg/canva-config.php</code>, then start again.</p>', false);
}

// ─────────────────────────── 1. START ───────────────────────────
if (isset($_GET['start'])) {
    if (!hash_equals(CANVA_SETUP_KEY, (string)$_GET['start'])) {
        http_response_code(403);
        canvaLog('START rejected — bad setup key from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        page('Forbidden', '<p>Wrong setup key.</p>', false);
    }

    // PKCE: verifier is 43-128 chars; challenge is its base64url SHA-256
    $verifier  = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $state     = bin2hex(random_bytes(16));

    if (!is_dir(CANVA_STATE_DIR)) mkdir(CANVA_STATE_DIR, 0700, true);
    file_put_contents(CANVA_STATE_DIR . "/$state.json",
        json_encode(['verifier' => $verifier, 'created' => time()]));
    chmod(CANVA_STATE_DIR . "/$state.json", 0600);

    $url = 'https://www.canva.com/api/oauth/authorize?' . http_build_query([
        'client_id'             => CANVA_CLIENT_ID,
        'redirect_uri'          => CANVA_REDIRECT_URI,
        'response_type'         => 'code',
        'scope'                 => CANVA_SCOPES,
        'code_challenge'        => $challenge,
        'code_challenge_method' => 's256',
        'state'                 => $state,
    ]);
    canvaLog("START -> redirecting to Canva consent (state=$state)");
    header("Location: $url", true, 302);
    exit;
}

// ───────────────────────── 2. CALLBACK ──────────────────────────
if (isset($_GET['error'])) {
    canvaLog('CALLBACK error: ' . $_GET['error'] . ' ' . ($_GET['error_description'] ?? ''));
    page('Canva returned an error',
        '<p><code>' . htmlspecialchars($_GET['error']) . '</code></p><p>'
      . htmlspecialchars($_GET['error_description'] ?? '') . '</p>', false);
}

if (!isset($_GET['code'], $_GET['state'])) {
    page('Ready',
        '<p>This is the Canva OAuth endpoint. Register it as the redirect URL:</p>'
      . '<p><code>' . CANVA_REDIRECT_URI . '</code></p>'
      . '<p>Then start the flow with <code>?start=&lt;setup key&gt;</code>.</p>');
}

$stateFile = CANVA_STATE_DIR . '/' . preg_replace('/[^a-f0-9]/', '', $_GET['state']) . '.json';
if (!is_file($stateFile)) {
    canvaLog('CALLBACK rejected — unknown state');
    page('State not recognised',
        '<p>This authorisation did not start here, or it expired. Start again.</p>', false);
}
$st = json_decode(file_get_contents($stateFile), true);
unlink($stateFile);                              // single use
if (!$st || (time() - ($st['created'] ?? 0)) > 900) {
    page('Expired', '<p>That link was older than 15 minutes. Start again.</p>', false);
}

// Exchange the code. Canva wants the client credentials as HTTP Basic auth.
$ch = curl_init('https://api.canva.com/rest/v1/oauth/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_USERPWD        => CANVA_CLIENT_ID . ':' . CANVA_CLIENT_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS     => http_build_query([
        'grant_type'    => 'authorization_code',
        'code'          => $_GET['code'],
        'code_verifier' => $st['verifier'],
        'redirect_uri'  => CANVA_REDIRECT_URI,
    ]),
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tok = json_decode($raw, true);
if ($code !== 200 || empty($tok['access_token'])) {
    canvaLog("TOKEN exchange failed HTTP $code: " . substr($raw, 0, 300));
    page('Token exchange failed',
        '<p>HTTP ' . $code . '</p><p><code>' . htmlspecialchars(substr($raw, 0, 400)) . '</code></p>', false);
}

$tok['obtained_at'] = time();
file_put_contents(CANVA_TOKEN_FILE, json_encode($tok, JSON_PRETTY_PRINT));
chmod(CANVA_TOKEN_FILE, 0600);
canvaLog('TOKEN stored; expires_in=' . ($tok['expires_in'] ?? '?'));

page('Connected',
    '<p>Canva is connected. Tokens are stored outside the web root.</p>'
  . '<p>Access token expires in ' . (int)($tok['expires_in'] ?? 0) . ' seconds; the refresh '
  . 'token is saved and renews it automatically.</p>'
  . '<p>You can close this tab.</p>');
