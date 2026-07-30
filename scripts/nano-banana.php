<?php
/**
 * nano-banana.php — CLI image generator (Gemini / "Nano Banana")
 *
 * Credentials live in /home/bombayengg/nano-banana-config.php (outside web
 * root, mode 600). The token there is a short-lived OAuth access token, so a
 * 401 here almost always means "paste a fresh token", not "the script broke".
 *
 * Auth note: this token authenticates as a ?key= query parameter, NOT as a
 * Bearer header — the Bearer path returns API_KEY_SERVICE_BLOCKED.
 *
 * Usage:
 *   php scripts/nano-banana.php --out=path.png --prompt="..." [--ratio=4:5]
 *                               [--size=2K] [--model=gemini-3-pro-image]
 *   php scripts/nano-banana.php --out=path.png --prompt-file=prompt.txt
 */

require_once '/home/bombayengg/nano-banana-config.php';

$opt = getopt('', ['out:', 'prompt:', 'prompt-file:', 'ratio::', 'size::', 'model::']);

$out = $opt['out'] ?? '';
$prompt = $opt['prompt'] ?? (isset($opt['prompt-file']) ? @file_get_contents($opt['prompt-file']) : '');
$ratio = $opt['ratio'] ?? '4:5';
$size  = $opt['size']  ?? '2K';
$model = $opt['model'] ?? 'gemini-3-pro-image';

if ($out === '' || trim((string)$prompt) === '') {
    fwrite(STDERR, "usage: nano-banana.php --out=FILE --prompt=TEXT|--prompt-file=FILE [--ratio=4:5] [--size=2K] [--model=...]\n");
    exit(2);
}

$url = NB_ENDPOINT . '/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode(NB_TOKEN);

$body = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'responseModalities' => ['IMAGE'],
        'imageConfig' => ['aspectRatio' => $ratio, 'imageSize' => $size],
    ],
], JSON_UNESCAPED_SLASHES);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 300,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$cerr = curl_error($ch);
curl_close($ch);

if ($resp === false) {
    fwrite(STDERR, "curl failed: $cerr\n");
    exit(1);
}

$json = json_decode($resp, true);

if ($code !== 200 || isset($json['error'])) {
    // Never echo the URL — it carries the token.
    $msg = $json['error']['message'] ?? substr($resp, 0, 600);
    fwrite(STDERR, "HTTP $code: $msg\n");
    exit(1);
}

// Walk every returned part; the image arrives as base64 inlineData.
$saved = false;
foreach ($json['candidates'][0]['content']['parts'] ?? [] as $part) {
    if (!isset($part['inlineData']['data'])) continue;
    $bin = base64_decode($part['inlineData']['data']);
    if ($bin === false || $bin === '') continue;
    if (!is_dir(dirname($out))) mkdir(dirname($out), 0755, true);
    file_put_contents($out, $bin);
    $saved = true;
    printf("saved %s  (%s, %.1f KB)\n", $out, $part['inlineData']['mimeType'] ?? '?', strlen($bin) / 1024);
    break;
}

if (!$saved) {
    $finish = $json['candidates'][0]['finishReason'] ?? '?';
    fwrite(STDERR, "no image in response (finishReason=$finish)\n");
    fwrite(STDERR, substr($resp, 0, 600) . "\n");
    exit(1);
}
