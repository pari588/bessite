<?php
/**
 * ig-publish.php — publish an image to Instagram (@besyndicate)
 *
 * Credentials live in /home/bombayengg/instagram-config.php (outside web root,
 * mode 600). The token is a permanent system-user token; a 190/104 error means
 * it was revoked in Business Settings, not that the script broke.
 *
 * Instagram will NOT accept image bytes — it fetches a public URL itself. So
 * the image must already be reachable on bombayengg.net. It also rejects PNG:
 * feed and story images must be JPEG.
 *
 * Usage:
 *   php scripts/ig-publish.php --image=URL --caption="..."          # feed post
 *   php scripts/ig-publish.php --image=URL --type=story             # story
 *   php scripts/ig-publish.php --image=URL --caption-file=FILE
 *   php scripts/ig-publish.php --image=URL --dry-run                # validate only
 *
 * --dry-run runs every preflight check and stops before anything is created,
 * which is the safe way to test. Nothing is posted without an explicit run.
 */

require_once '/home/bombayengg/instagram-config.php';

$opt = getopt('', ['image:', 'caption::', 'caption-file::', 'type::', 'dry-run']);

$image   = $opt['image'] ?? '';
$caption = $opt['caption'] ?? (isset($opt['caption-file']) ? @file_get_contents($opt['caption-file']) : '');
$type    = $opt['type'] ?? 'feed';
$dryRun  = array_key_exists('dry-run', $opt);

if ($image === '') {
    fwrite(STDERR, "usage: ig-publish.php --image=URL [--caption=TEXT|--caption-file=F] [--type=feed|story] [--dry-run]\n");
    exit(2);
}
if (!in_array($type, ['feed', 'story'], true)) {
    fwrite(STDERR, "--type must be 'feed' or 'story'\n");
    exit(2);
}

/** GET/POST helper. Never echoes the URL — it carries the token. */
function ig_call(string $path, array $params, string $method = 'GET'): array
{
    $params['access_token'] = IG_ACCESS_TOKEN;
    $url = IG_GRAPH . '/' . ltrim($path, '/');

    $ch = curl_init();
    if ($method === 'POST') {
        curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_POST => true,
                                CURLOPT_POSTFIELDS => http_build_query($params)]);
    } else {
        curl_setopt_array($ch, [CURLOPT_URL => $url . '?' . http_build_query($params)]);
    }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120]);

    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        fwrite(STDERR, "curl failed: $err\n");
        exit(1);
    }
    $json = json_decode($raw, true) ?: [];
    if ($code !== 200 || isset($json['error'])) {
        $e = $json['error'] ?? [];
        fwrite(STDERR, sprintf("HTTP %d — %s (code %s%s)\n", $code,
            $e['message'] ?? substr($raw, 0, 300), $e['code'] ?? '?',
            isset($e['error_subcode']) ? '/' . $e['error_subcode'] : ''));
        exit(1);
    }
    return $json;
}

// ---------- preflight ----------
// Cheaper to fail here than to have Instagram fetch a 404 and return an opaque error.
echo "preflight\n";

$h = @get_headers($image, true);
if (!$h || !preg_match('/\b200\b/', $h[0])) {
    fwrite(STDERR, "  image URL not reachable: $image\n");
    exit(1);
}
$ctype = $h['Content-Type'] ?? '';
if (is_array($ctype)) $ctype = end($ctype);
printf("  url        %s\n  type       %s\n", $image, $ctype ?: '?');
if (stripos($ctype, 'jpeg') === false && stripos($ctype, 'jpg') === false) {
    fwrite(STDERR, "  Instagram accepts JPEG only — this is '$ctype'. Use the .jpg render.\n");
    exit(1);
}

$limit = ig_call(IG_USER_ID . '/content_publishing_limit', ['fields' => 'config,quota_usage']);
$used  = $limit['data'][0]['quota_usage'] ?? 0;
$total = $limit['data'][0]['config']['quota_total'] ?? '?';
printf("  quota      %s of %s used in the last 24h\n", $used, $total);
printf("  target     @%s (%s), %s\n", 'besyndicate', IG_USER_ID, $type);
if ($caption !== '') printf("  caption    %d chars\n", mb_strlen($caption));

if ($dryRun) {
    echo "dry run — nothing created, nothing published.\n";
    exit(0);
}

// ---------- 1. container ----------
echo "creating container\n";
$params = ['image_url' => $image];
if ($type === 'story')          $params['media_type'] = 'STORIES';
elseif ($caption !== '')        $params['caption']    = $caption;   // stories take no caption

$container = ig_call(IG_USER_ID . '/media', $params, 'POST');
$cid = $container['id'] ?? '';
if ($cid === '') { fwrite(STDERR, "no container id returned\n"); exit(1); }
echo "  container  $cid\n";

// ---------- 2. wait for Instagram to fetch and process the image ----------
echo "waiting for processing\n";
$status = '';
for ($i = 0; $i < 30; $i++) {
    sleep(2);
    $s = ig_call($cid, ['fields' => 'status_code,status']);
    $status = $s['status_code'] ?? '';
    if ($status === 'FINISHED') { echo "  ready\n"; break; }
    if ($status === 'ERROR' || $status === 'EXPIRED') {
        fwrite(STDERR, "  container $status: " . ($s['status'] ?? '') . "\n");
        exit(1);
    }
}
if ($status !== 'FINISHED') {
    fwrite(STDERR, "  timed out in state '$status' — container id $cid is still valid for 24h\n");
    exit(1);
}

// ---------- 3. publish ----------
echo "publishing\n";
$pub = ig_call(IG_USER_ID . '/media_publish', ['creation_id' => $cid], 'POST');
$mid = $pub['id'] ?? '';
echo "  media id   $mid\n";

$info = ig_call($mid, ['fields' => 'permalink,timestamp']);
echo "  permalink  " . ($info['permalink'] ?? '?') . "\n";
echo "published.\n";
