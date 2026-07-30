<?php
/**
 * canva-import.php — push a rendered PDF into Canva as an editable design.
 *
 * Uses the Connect API Design Import endpoint. Because we print the artboard as
 * a PDF with live text (rather than screenshotting it), the design arrives in
 * Canva with editable text boxes, not a flattened picture.
 *
 * Requires a completed OAuth consent — see core/canva-oauth.php. Access tokens
 * are short-lived; this refreshes them automatically using the stored refresh
 * token, so it keeps working unattended.
 *
 * Usage:
 *   php scripts/canva-import.php --file=path/to.pdf --title="Hazardous Area Motors"
 *   php scripts/canva-import.php --file=... --title=... --wait
 *   php scripts/canva-import.php --status        # show token state, import nothing
 */

require_once '/home/bombayengg/canva-config.php';
date_default_timezone_set('Asia/Kolkata');

$opt = getopt('', ['file:', 'title:', 'wait', 'status']);

function fail(string $m): void { fwrite(STDERR, "  $m\n"); exit(1); }

function loadTokens(): array
{
    if (!is_file(CANVA_TOKEN_FILE)) {
        fail('Not connected yet. Open '
           . 'https://www.bombayengg.net/core/canva-oauth.php?start=' . CANVA_SETUP_KEY
           . ' in a browser and approve.');
    }
    return json_decode(file_get_contents(CANVA_TOKEN_FILE), true) ?: [];
}

function saveTokens(array $t): void
{
    $t['obtained_at'] = time();
    file_put_contents(CANVA_TOKEN_FILE, json_encode($t, JSON_PRETTY_PRINT));
    chmod(CANVA_TOKEN_FILE, 0600);
}

/** Returns a valid access token, refreshing if it is close to expiry. */
function accessToken(): string
{
    $t = loadTokens();
    $age  = time() - ($t['obtained_at'] ?? 0);
    $life = (int)($t['expires_in'] ?? 0);

    // refresh with 5 minutes to spare rather than waiting for a 401
    if ($life && $age < $life - 300) return $t['access_token'];
    if (empty($t['refresh_token']))  return $t['access_token'];

    $ch = curl_init('https://api.canva.com/rest/v1/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_USERPWD => CANVA_CLIENT_ID . ':' . CANVA_CLIENT_SECRET,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $t['refresh_token'],
        ]),
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $new = json_decode($raw, true);
    if ($code !== 200 || empty($new['access_token'])) {
        fail("Token refresh failed (HTTP $code). Re-authorise: "
           . 'https://www.bombayengg.net/core/canva-oauth.php?start=' . CANVA_SETUP_KEY);
    }
    // Canva may or may not rotate the refresh token — keep the old one if absent
    $new['refresh_token'] = $new['refresh_token'] ?? $t['refresh_token'];
    saveTokens($new);
    echo "  access token refreshed\n";
    return $new['access_token'];
}

function api(string $method, string $url, string $token, array $headers = [], $body = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => array_merge(["Authorization: Bearer $token"], $headers),
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true), $raw];
}

// ── status ──
if (isset($opt['status'])) {
    if (!is_file(CANVA_TOKEN_FILE)) {
        echo "  not connected — authorise at:\n"
           . "  https://www.bombayengg.net/core/canva-oauth.php?start=" . CANVA_SETUP_KEY . "\n";
        exit(0);
    }
    $t = loadTokens();
    $left = ($t['obtained_at'] + ($t['expires_in'] ?? 0)) - time();
    printf("  connected. access token %s (%d min left), refresh token %s\n",
        $left > 0 ? 'valid' : 'expired', max(0, (int)($left / 60)),
        empty($t['refresh_token']) ? 'MISSING' : 'present');
    printf("  scopes: %s\n", $t['scope'] ?? '?');
    exit(0);
}

// ── import ──
$file  = $opt['file']  ?? '';
$title = $opt['title'] ?? ($file ? pathinfo($file, PATHINFO_FILENAME) : '');
if ($file === '')      fail('usage: canva-import.php --file=FILE.pdf [--title="..."] [--wait]');
if (!is_file($file))   fail("file not found: $file");

$bytes = file_get_contents($file);
$mime  = mime_content_type($file) ?: 'application/pdf';
// Canva caps the title at 50 characters before encoding
$title = mb_substr($title, 0, 50);

echo "  file  " . basename($file) . '  ' . round(strlen($bytes) / 1024) . " KB  ($mime)\n";
echo "  title $title\n";

$token = accessToken();
$meta  = base64_encode(json_encode([
    'title_base64' => base64_encode($title),
    'mime_type'    => $mime,
]));

[$code, $j, $raw] = api('POST', 'https://api.canva.com/rest/v1/imports', $token, [
    'Content-Type: application/octet-stream',
    'Import-Metadata: ' . json_encode([
        'title_base64' => base64_encode($title),
        'mime_type'    => $mime,
    ]),
], $bytes);

if ($code !== 200 || empty($j['job'])) {
    fail("import failed HTTP $code: " . substr($raw, 0, 400));
}

$jobId = $j['job']['id'];
echo "  job   $jobId  (" . ($j['job']['status'] ?? '?') . ")\n";

if (!isset($opt['wait'])) {
    echo "  queued. re-run with --wait, or poll:\n"
       . "  GET https://api.canva.com/rest/v1/imports/$jobId\n";
    exit(0);
}

for ($i = 0; $i < 40; $i++) {
    sleep(3);
    [$c, $s] = api('GET', "https://api.canva.com/rest/v1/imports/$jobId", $token);
    $status = $s['job']['status'] ?? '?';
    if ($status === 'success') {
        foreach ($s['job']['result']['designs'] ?? [] as $d) {
            echo "  design  " . ($d['title'] ?? '?') . "\n";
            echo "  open    " . ($d['urls']['edit_url'] ?? $d['id'] ?? '?') . "\n";
        }
        echo "done.\n";
        exit(0);
    }
    if ($status === 'failed') {
        fail('import failed: ' . json_encode($s['job']['error'] ?? $s));
    }
}
fail("timed out waiting; check job $jobId manually");
