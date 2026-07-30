<?php
/**
 * IndexNow submitter — pings Bing/Yandex/Seznam (and indirectly Google) about URL changes.
 *
 * Modes:
 *   --all      Submit every URL in sitemap.xml (use after major content changes)
 *   --recent   Submit URLs with lastmod from today only (efficient default for cron)
 *   --url=URL  Submit a single URL
 *
 * Run after sitemap regeneration. Safe to call multiple times — IndexNow dedups internally.
 */

$_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
$_SERVER['REQUEST_URI']   = '/';
$_SERVER['SERVER_PORT']   = '443';
$_SERVER['HTTPS']         = 'on';
$_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';

$KEY      = "7528870504d6114b733a66c221179aaf";
$HOST     = "www.bombayengg.net";
$KEY_URL  = "https://{$HOST}/{$KEY}.txt";
$ENDPOINT = "https://api.indexnow.org/indexnow";
$SITEMAP  = "/home/bombayengg/public_html/xsite/sitemap.xml";

// Parse args
$mode = 'recent';
$singleUrl = null;
foreach ($argv as $arg) {
    if ($arg === '--all') $mode = 'all';
    elseif ($arg === '--recent') $mode = 'recent';
    elseif (strpos($arg, '--url=') === 0) {
        $mode = 'single';
        $singleUrl = substr($arg, 6);
    }
}

// Build URL list
$urls = [];
if ($mode === 'single') {
    $urls = [$singleUrl];
} else {
    if (!file_exists($SITEMAP)) {
        echo "Sitemap not found at $SITEMAP\n";
        exit(1);
    }
    $xml = simplexml_load_file($SITEMAP);
    $today = date('Y-m-d');
    foreach ($xml->url as $u) {
        $loc = (string)$u->loc;
        $lastmod = (string)$u->lastmod;
        if ($mode === 'recent') {
            if (strpos($lastmod, $today) === 0) $urls[] = $loc;
        } else {
            $urls[] = $loc;
        }
    }
}

if (empty($urls)) {
    echo "No URLs to submit (mode=$mode).\n";
    exit(0);
}

echo "Submitting " . count($urls) . " URLs to IndexNow (mode=$mode)...\n";

// IndexNow accepts up to 10,000 URLs per request. Chunk just in case.
$chunks = array_chunk($urls, 1000);
$totalSubmitted = 0;

foreach ($chunks as $chunkIndex => $chunk) {
    $payload = json_encode([
        'host'        => $HOST,
        'key'         => $KEY,
        'keyLocation' => $KEY_URL,
        'urlList'     => $chunk,
    ]);

    $ch = curl_init($ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json; charset=utf-8',
            'Host: api.indexnow.org',
        ],
        CURLOPT_POSTFIELDS     => $payload,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $statusMap = [
        200 => 'OK — URLs received',
        202 => 'Accepted — URLs received (key still verifying)',
        400 => 'Bad request',
        403 => 'Forbidden — key mismatch (check key file at ' . $KEY_URL . ')',
        422 => 'Unprocessable — URLs don\'t match host',
        429 => 'Too many requests',
    ];
    $msg = $statusMap[$http] ?? 'Unknown';
    echo "  Chunk " . ($chunkIndex + 1) . ": " . count($chunk) . " URLs → HTTP $http ($msg)\n";
    if ($http >= 400) {
        echo "  Response: " . substr($resp, 0, 300) . "\n";
    } else {
        $totalSubmitted += count($chunk);
    }
}

echo "\nDone. Total successfully submitted: $totalSubmitted\n";
echo "Bing/Yandex/Seznam/Naver will recrawl within 24 hours.\n";
