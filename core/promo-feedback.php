<?php
/**
 * promo-feedback.php — feedback + approval store for the social post drafts.
 *
 *   GET  /core/promo-feedback.php              -> all state as JSON
 *   POST /core/promo-feedback.php              -> save one post's feedback/status
 *        {slug, status: "changes"|"approved", note}
 *
 * State lives OUTSIDE the web root so it cannot be read by guessing a URL, and
 * so a careless deploy cannot wipe it.
 *
 * This endpoint is unauthenticated by design — the review pages are unlisted
 * (noindex, no links in) and the worst a stranger could do is write a note
 * nobody asked for. It is NOT a place for anything sensitive. Guards kept
 * proportionate: slugs must be on the known list, notes are capped, and every
 * write is stamped with time and IP so junk is identifiable.
 */

const STORE = '/home/bombayengg/promo-feedback.json';

const SLUGS = [
    'monsoon', 'intro', 'product-ie4',
    'hazardous-area', 'fire-pump-motors', 'read-the-nameplate',
    'sewage-drainage', 'booster-pumps', 'dewatering-pumps',
];

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function readStore(): array
{
    if (!is_file(STORE)) return [];
    return json_decode(file_get_contents(STORE), true) ?: [];
}

function writeStore(array $d): bool
{
    $tmp = STORE . '.tmp';
    if (file_put_contents($tmp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        return false;
    }
    @chmod($tmp, 0600);
    return rename($tmp, STORE);   // atomic, so a crash mid-write cannot truncate the file
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['ok' => true, 'data' => readStore()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method not allowed']);
    exit;
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad json']);
    exit;
}

$slug   = (string)($in['slug'] ?? '');
$status = (string)($in['status'] ?? 'changes');
$note   = trim((string)($in['note'] ?? ''));

if (!in_array($slug, SLUGS, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'unknown slug']);
    exit;
}
if (!in_array($status, ['changes', 'approved', 'clear'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'bad status']);
    exit;
}
if (mb_strlen($note) > 4000) $note = mb_substr($note, 0, 4000);

$d   = readStore();
$now = date('Y-m-d H:i');
$ip  = $_SERVER['REMOTE_ADDR'] ?? '?';

if ($status === 'clear') {
    unset($d[$slug]);
} else {
    $entry = $d[$slug] ?? ['history' => []];
    // Keep every note rather than overwriting — a review thread is more useful
    // than a single latest comment, and nothing said is silently lost.
    if ($note !== '' && $note !== ($entry['note'] ?? '')) {
        $entry['history'][] = ['at' => $now, 'note' => $note, 'status' => $status];
        if (count($entry['history']) > 40) array_shift($entry['history']);
    }
    $entry['note']    = $note;
    $entry['status']  = $status;
    $entry['updated'] = $now;
    $entry['ip']      = $ip;
    $d[$slug] = $entry;
}

if (!writeStore($d)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'could not save']);
    exit;
}

echo json_encode(['ok' => true, 'slug' => $slug, 'status' => $status, 'updated' => $now]);
