<?php
/**
 * canva-organise.php — file imported designs into a Canva folder.
 *
 * The Design Import API drops everything into the account root, so a batch of
 * ten artboards lands loose among whatever else is in Projects. This creates a
 * folder and moves matching designs into it.
 *
 * Needs folder:read and folder:write in addition to the import scopes. If the
 * stored token predates those, re-authorise at
 *   https://www.bombayengg.net/core/canva-oauth.php?start=<CANVA_SETUP_KEY>
 *
 * Usage:
 *   php scripts/canva-organise.php --folder="BES Social" --match="Hazardous,Fire Pump"
 *   php scripts/canva-organise.php --folder="BES Social" --all-bes
 *   php scripts/canva-organise.php --list
 *
 * Matching is by title prefix and is case-sensitive, so a stray design with a
 * similar name is not swept up by accident. Dry run unless --apply is given.
 */

require_once '/home/bombayengg/canva-config.php';
date_default_timezone_set('Asia/Kolkata');

$opt = getopt('', ['folder:', 'match:', 'all-bes', 'list', 'apply']);

/** Titles of the posts this pipeline produces. */
const BES_TITLES = [
    'Hazardous Area Motors',
    'UL Listed Fire Pump Motors',
    'How to Read a Motor Nameplate',
    'Sewage & Drainage Pumps',
    'Pressure Booster Pumps',
];

function fail(string $m): void { fwrite(STDERR, "  $m\n"); exit(1); }

function token(): string
{
    if (!is_file(CANVA_TOKEN_FILE)) fail('Not connected. Authorise first.');
    $t = json_decode(file_get_contents(CANVA_TOKEN_FILE), true);
    $age = time() - ($t['obtained_at'] ?? 0);
    if (($t['expires_in'] ?? 0) && $age < $t['expires_in'] - 300) return $t['access_token'];

    if (empty($t['refresh_token'])) return $t['access_token'];
    $ch = curl_init('https://api.canva.com/rest/v1/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_USERPWD => CANVA_CLIENT_ID . ':' . CANVA_CLIENT_SECRET,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']]),
    ]);
    $new = json_decode(curl_exec($ch), true); curl_close($ch);
    if (empty($new['access_token'])) fail('Token refresh failed — re-authorise.');
    $new['refresh_token'] = $new['refresh_token'] ?? $t['refresh_token'];
    $new['obtained_at'] = time();
    file_put_contents(CANVA_TOKEN_FILE, json_encode($new, JSON_PRETTY_PRINT));
    chmod(CANVA_TOKEN_FILE, 0600);
    echo "  access token refreshed\n";
    return $new['access_token'];
}

function api(string $method, string $path, string $tok, ?array $body = null): array
{
    $ch = curl_init('https://api.canva.com/rest/v1/' . ltrim($path, '/'));
    $h = ["Authorization: Bearer $tok"];
    if ($body !== null) { $h[] = 'Content-Type: application/json'; }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $h,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true), $raw];
}

/** Every design in the account, following continuation tokens. */
function allDesigns(string $tok): array
{
    $out = []; $cont = null; $page = 0;
    do {
        [$c, $j] = api('GET', 'designs?limit=100' . ($cont ? '&continuation=' . urlencode($cont) : ''), $tok);
        if ($c !== 200) fail("listing designs failed: HTTP $c");
        foreach ($j['items'] ?? [] as $d) $out[] = $d;
        $cont = $j['continuation'] ?? null;
    } while ($cont && ++$page < 20);
    return $out;
}

$tok = token();

// ── list mode ──
if (isset($opt['list'])) {
    [$c, $j] = api('GET', 'folders/root/items?limit=100', $tok);
    if ($c === 403) fail('Token lacks folder:read. Re-authorise to pick up the new scopes.');
    foreach ($j['items'] ?? [] as $it) {
        $t = $it['type'] ?? '?';
        $n = $it['folder']['name'] ?? $it['design']['title'] ?? '?';
        $i = $it['folder']['id'] ?? $it['design']['id'] ?? '';
        printf("  %-8s %-40s %s\n", $t, mb_substr($n, 0, 38), $i);
    }
    exit(0);
}

$folderName = $opt['folder'] ?? 'BES Social Posts';
$apply      = isset($opt['apply']);

$prefixes = isset($opt['all-bes'])
    ? BES_TITLES
    : array_filter(array_map('trim', explode(',', $opt['match'] ?? '')));
if (!$prefixes) fail('give --all-bes or --match="Prefix One,Prefix Two"');

$designs = allDesigns($tok);
$hits = [];
foreach ($designs as $d) {
    foreach ($prefixes as $p) {
        if (strpos($d['title'] ?? '', $p) === 0) { $hits[$d['id']] = $d['title']; break; }
    }
}

printf("  folder : %s\n  matched: %d of %d designs\n\n", $folderName, count($hits), count($designs));
foreach ($hits as $id => $t) printf("    %-12s %s\n", $id, $t);
if (!$hits) exit(0);

if (!$apply) { echo "\n  dry run — re-run with --apply\n"; exit(0); }

// ── find or create the folder (idempotent: re-running will not duplicate it) ──
$folderId = null;
[$c, $j] = api('GET', 'folders/root/items?limit=100', $tok);
if ($c === 403) fail('Token lacks folder:read. Re-authorise to pick up the new scopes.');
foreach ($j['items'] ?? [] as $it) {
    if (($it['type'] ?? '') === 'folder' && ($it['folder']['name'] ?? '') === $folderName) {
        $folderId = $it['folder']['id'];
        echo "\n  using existing folder $folderId\n";
        break;
    }
}
if (!$folderId) {
    [$c, $j, $raw] = api('POST', 'folders', $tok, ['name' => $folderName, 'parent_folder_id' => 'root']);
    if ($c !== 200 || empty($j['folder']['id'])) fail("create folder failed HTTP $c: " . substr($raw, 0, 200));
    $folderId = $j['folder']['id'];
    echo "\n  created folder $folderId\n";
}

// ── move each design in ──
$ok = $err = 0;
foreach ($hits as $id => $title) {
    [$c, $j, $raw] = api('POST', 'folders/move', $tok, [
        'to_folder_id' => $folderId,
        'item_id'      => $id,
    ]);
    if ($c === 200 || $c === 204) { $ok++; printf("    moved  %s\n", $title); }
    else { $err++; printf("    FAILED %s — HTTP %d %s\n", $title, $c, substr($raw, 0, 120)); }
    usleep(400000);
}
printf("\n  %d moved, %d failed\n", $ok, $err);
