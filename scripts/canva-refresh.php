<?php
/**
 * canva-refresh.php — replace the Canva designs with freshly rendered ones.
 *
 * The Connect API has no way to update an existing design in place, and no
 * delete endpoint either (DELETE /designs/{id} is 404, and "trash" is not a
 * valid move target). So a re-import would leave two designs with the same
 * name and no clue which is current.
 *
 * Instead: move the existing batch into an "Archive" sub-folder, import the
 * new PDFs, and file those in the main folder. Nothing is destroyed — the old
 * versions stay in Archive until you delete them by hand in Canva.
 *
 * Usage:
 *   php scripts/canva-refresh.php            # dry run
 *   php scripts/canva-refresh.php --apply
 */

require_once '/home/bombayengg/canva-config.php';
date_default_timezone_set('Asia/Kolkata');

const POSTS_DIR = '/home/bombayengg/public_html/uploads/promo/posts';
const MAIN_FOLDER = 'BES Social Posts';

$SET = [
    ['hazardous-area',     'Hazardous Area Motors'],
    ['fire-pump-motors',   'UL Listed Fire Pump Motors'],
    ['read-the-nameplate', 'How to Read a Motor Nameplate'],
    ['sewage-drainage',    'Sewage & Drainage Pumps'],
    ['booster-pumps',      'Pressure Booster Pumps'],
];

$APPLY = in_array('--apply', $argv, true);

function fail(string $m): void { fwrite(STDERR, "  $m\n"); exit(1); }

function token(): string
{
    static $tok = null;
    if ($tok) return $tok;
    if (!is_file(CANVA_TOKEN_FILE)) fail('not connected');
    $t = json_decode(file_get_contents(CANVA_TOKEN_FILE), true);
    if (($t['expires_in'] ?? 0) && time() - $t['obtained_at'] < $t['expires_in'] - 300) {
        return $tok = $t['access_token'];
    }
    $ch = curl_init('https://api.canva.com/rest/v1/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_USERPWD => CANVA_CLIENT_ID . ':' . CANVA_CLIENT_SECRET,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']]),
    ]);
    $n = json_decode(curl_exec($ch), true); curl_close($ch);
    if (empty($n['access_token'])) fail('token refresh failed — re-authorise');
    $n['refresh_token'] = $n['refresh_token'] ?? $t['refresh_token'];
    $n['obtained_at'] = time();
    file_put_contents(CANVA_TOKEN_FILE, json_encode($n, JSON_PRETTY_PRINT));
    chmod(CANVA_TOKEN_FILE, 0600);
    echo "  token refreshed\n";
    return $tok = $n['access_token'];
}

function api(string $method, string $path, ?array $body = null, ?string $rawBody = null, array $extra = []): array
{
    $h = ['Authorization: Bearer ' . token()];
    if ($body !== null)    $h[] = 'Content-Type: application/json';
    if ($rawBody !== null) $h[] = 'Content-Type: application/octet-stream';
    $h = array_merge($h, $extra);
    $ch = curl_init('https://api.canva.com/rest/v1/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120, CURLOPT_HTTPHEADER => $h,
    ]);
    if ($body !== null)    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    if ($rawBody !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true), $raw];
}

/** Find a folder by name under a parent, or create it. */
function folderId(string $name, string $parent = 'root'): string
{
    [$c, $j] = api('GET', "folders/$parent/items?limit=100");
    if ($c !== 200) fail("listing $parent failed: HTTP $c");
    foreach ($j['items'] ?? [] as $it) {
        if (($it['type'] ?? '') === 'folder' && ($it['folder']['name'] ?? '') === $name) {
            return $it['folder']['id'];
        }
    }
    [$c, $j, $raw] = api('POST', 'folders', ['name' => $name, 'parent_folder_id' => $parent]);
    if ($c !== 200 || empty($j['folder']['id'])) fail("create folder '$name' failed: " . substr($raw, 0, 200));
    echo "  created folder '$name' ({$j['folder']['id']})\n";
    return $j['folder']['id'];
}

$titles = [];
foreach ($SET as [$slug, $name]) { $titles[$name] = 1; $titles["$name — Story"] = 1; }

// ── what is currently in the main folder ──
$main = folderId(MAIN_FOLDER);
[$c, $j] = api('GET', "folders/$main/items?limit=100");
$stale = [];
foreach ($j['items'] ?? [] as $it) {
    if (($it['type'] ?? '') === 'design' && isset($titles[$it['design']['title'] ?? ''])) {
        $stale[$it['design']['id']] = $it['design']['title'];
    }
}

printf("  main folder : %s (%s)\n", MAIN_FOLDER, $main);
printf("  to archive  : %d existing design(s)\n", count($stale));
printf("  to import   : %d fresh PDF(s)\n\n", count($SET) * 2);

if (!$APPLY) { echo "  dry run — re-run with --apply\n"; exit(0); }

// ── 1. archive the old batch ──
$archive = folderId('Archive', $main);
$moved = 0;
foreach ($stale as $id => $title) {
    [$c] = api('POST', 'folders/move', ['to_folder_id' => $archive, 'item_id' => $id]);
    if ($c === 200 || $c === 204) { $moved++; printf("  archived  %s\n", $title); }
    else printf("  FAILED to archive %s (HTTP %d)\n", $title, $c);
    usleep(350000);
}
echo "\n";

// ── 2. import the fresh PDFs, keeping the new IDs ──
$new = [];
foreach ($SET as [$slug, $name]) {
    foreach (['post' => $name, 'story' => "$name — Story"] as $kind => $title) {
        $f = POSTS_DIR . "/$slug/out/$slug-$kind.pdf";
        if (!is_file($f)) { printf("  MISSING %s\n", $f); continue; }
        [$c, $j, $raw] = api('POST', 'imports', null, file_get_contents($f), [
            'Import-Metadata: ' . json_encode([
                'title_base64' => base64_encode(mb_substr($title, 0, 50)),
                'mime_type'    => 'application/pdf',
            ]),
        ]);
        if ($c !== 200 || empty($j['job']['id'])) { printf("  IMPORT FAILED %s: %s\n", $title, substr($raw, 0, 160)); continue; }

        $job = $j['job']['id'];
        for ($i = 0; $i < 40; $i++) {
            sleep(3);
            [$sc, $s] = api('GET', "imports/$job");
            $st = $s['job']['status'] ?? '?';
            if ($st === 'success') {
                foreach ($s['job']['result']['designs'] ?? [] as $d) {
                    $new[$d['id']] = $title;
                    printf("  imported  %s\n", $title);
                }
                break 1;
            }
            if ($st === 'failed') { printf("  IMPORT FAILED %s\n", $title); break; }
        }
        usleep(400000);
    }
}
echo "\n";

// ── 3. file the new ones into the main folder ──
$filed = 0;
foreach ($new as $id => $title) {
    [$c] = api('POST', 'folders/move', ['to_folder_id' => $main, 'item_id' => $id]);
    if ($c === 200 || $c === 204) $filed++;
    else printf("  FAILED to file %s (HTTP %d)\n", $title, $c);
    usleep(350000);
}

printf("  %d archived, %d imported, %d filed into '%s'\n", $moved, count($new), $filed, MAIN_FOLDER);
echo "  old versions remain in the Archive sub-folder — delete them in Canva when happy.\n";
