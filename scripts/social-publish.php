<?php
/**
 * social-publish.php — publish one post to Instagram and/or the Facebook Page.
 *
 * Both run off the same system-user token already stored for Instagram: it
 * carries pages_manage_posts, and the Page grants CREATE_CONTENT, so Facebook
 * needed no extra authorisation.
 *
 * "Simultaneously" is a slight fiction worth naming: these are two separate
 * APIs and either can fail on its own. This publishes Instagram FIRST, because
 * it is the fussier of the two (JPEG only, aspect-ratio limits, async job) — if
 * it rejects the image there is no point putting a half-campaign on Facebook.
 * Facebook only runs once Instagram has succeeded, unless --only=fb.
 *
 * Usage:
 *   php scripts/social-publish.php --slug=hazardous-area --dry-run
 *   php scripts/social-publish.php --slug=hazardous-area
 *   php scripts/social-publish.php --slug=hazardous-area --only=fb
 *   php scripts/social-publish.php --image=URL --caption-file=F --only=ig
 *
 * Slugs resolve against uploads/promo/. Anything not in that map needs
 * --image and --caption-file given explicitly.
 */

require_once '/home/bombayengg/instagram-config.php';
date_default_timezone_set('Asia/Kolkata');

const PAGE_ID  = '1017975244725475';
const WEB_ROOT = '/home/bombayengg/public_html';
const WEB_URL  = 'https://www.bombayengg.net';

/** slug => [image path relative to web root, caption file] */
const POSTS = [
    'monsoon'            => ['uploads/promo/monsoon/out/monsoon-post.jpg',            'uploads/promo/monsoon/ig-caption.txt'],
    'intro'              => ['uploads/promo/intro/out/intro-post.jpg',                'uploads/promo/intro/ig-caption.txt'],
    'product-ie4'        => ['uploads/promo/product-ie4/out/ie4-post.jpg',            'uploads/promo/product-ie4/ig-caption.txt'],
    'hazardous-area'     => ['uploads/promo/posts/hazardous-area/out/hazardous-area-post.jpg',         'uploads/promo/posts/hazardous-area/ig-caption.txt'],
    'fire-pump-motors'   => ['uploads/promo/posts/fire-pump-motors/out/fire-pump-motors-post.jpg',     'uploads/promo/posts/fire-pump-motors/ig-caption.txt'],
    'read-the-nameplate' => ['uploads/promo/posts/read-the-nameplate/out/read-the-nameplate-post.jpg', 'uploads/promo/posts/read-the-nameplate/ig-caption.txt'],
    'sewage-drainage'    => ['uploads/promo/posts/sewage-drainage/out/sewage-drainage-post.jpg',       'uploads/promo/posts/sewage-drainage/ig-caption.txt'],
    'booster-pumps'      => ['uploads/promo/posts/booster-pumps/out/booster-pumps-post.jpg',           'uploads/promo/posts/booster-pumps/ig-caption.txt'],
    'dewatering-pumps'   => ['uploads/promo/posts/dewatering-pumps/out/dewatering-pumps-post.jpg',     'uploads/promo/posts/dewatering-pumps/ig-caption.txt'],
    'forced-cooling'     => ['uploads/promo/posts/forced-cooling/out/forced-cooling-post.jpg',         'uploads/promo/posts/forced-cooling/ig-caption.txt'],
];

$opt    = getopt('', ['slug:', 'image:', 'caption-file:', 'only:', 'dry-run']);
$only   = $opt['only'] ?? 'both';
$dry    = array_key_exists('dry-run', $opt);

function fail(string $m): void { fwrite(STDERR, "  $m\n"); exit(1); }

function call(string $url, ?array $post = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 180]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true) ?: [], $raw];
}

/** The Page's own token — derived from the system-user token, not stored. */
function pageToken(): string
{
    static $t = null;
    if ($t) return $t;
    [$c, $j] = call(IG_GRAPH . '/me/accounts?fields=id,access_token&access_token=' . IG_ACCESS_TOKEN);
    foreach ($j['data'] ?? [] as $p) if ($p['id'] === PAGE_ID) return $t = $p['access_token'];
    fail('could not obtain a Page access token — is the Page still assigned to the system user?');
}

// ── resolve inputs ──
if (isset($opt['slug'])) {
    if (!isset(POSTS[$opt['slug']])) fail('unknown slug. known: ' . implode(', ', array_keys(POSTS)));
    [$rel, $capRel] = POSTS[$opt['slug']];
    $image   = WEB_URL . '/' . $rel;
    $capFile = WEB_ROOT . '/' . $capRel;
} else {
    $image   = $opt['image'] ?? '';
    $capFile = $opt['caption-file'] ?? '';
}
if ($image === '')                 fail('need --slug or --image');
if ($capFile && !is_file($capFile)) fail("caption file not found: $capFile");
$caption = $capFile ? trim(file_get_contents($capFile)) : '';

// ── preflight ──
echo "preflight\n";
$h = @get_headers($image, true);
if (!$h || !preg_match('/\b200\b/', $h[0])) fail("image not reachable: $image");
$ctype = $h['Content-Type'] ?? '';
if (is_array($ctype)) $ctype = end($ctype);
printf("  image    %s\n  type     %s\n  caption  %d chars\n  target   %s\n",
    $image, $ctype, mb_strlen($caption),
    $only === 'both' ? 'Instagram + Facebook Page' : strtoupper($only));

if ($only !== 'fb' && stripos($ctype, 'jpeg') === false) {
    fail("Instagram accepts JPEG only — this is '$ctype'");
}

if ($dry) { echo "dry run — nothing published.\n"; exit(0); }

$results = [];

// ── Instagram first: fussier, and a failure here should stop the whole run ──
if ($only === 'both' || $only === 'ig') {
    echo "instagram\n";
    [$c, $j, $raw] = call(IG_GRAPH . '/' . IG_USER_ID . '/media', [
        'image_url' => $image, 'caption' => $caption, 'access_token' => IG_ACCESS_TOKEN,
    ]);
    if ($c !== 200 || empty($j['id'])) fail('  container failed: ' . substr($raw, 0, 300));
    $cid = $j['id'];
    echo "  container $cid\n";

    $ready = false;
    for ($i = 0; $i < 30; $i++) {
        sleep(2);
        [, $s] = call(IG_GRAPH . "/$cid?fields=status_code&access_token=" . IG_ACCESS_TOKEN);
        $st = $s['status_code'] ?? '';
        if ($st === 'FINISHED') { $ready = true; break; }
        if ($st === 'ERROR' || $st === 'EXPIRED') fail("  container $st");
    }
    if (!$ready) fail('  timed out waiting for Instagram to process the image');

    [$c, $j, $raw] = call(IG_GRAPH . '/' . IG_USER_ID . '/media_publish', [
        'creation_id' => $cid, 'access_token' => IG_ACCESS_TOKEN,
    ]);
    if ($c !== 200 || empty($j['id'])) fail('  publish failed: ' . substr($raw, 0, 300));
    [, $info] = call(IG_GRAPH . '/' . $j['id'] . '?fields=permalink&access_token=' . IG_ACCESS_TOKEN);
    $results['instagram'] = $info['permalink'] ?? $j['id'];
    echo '  published ' . $results['instagram'] . "\n";
}

// ── Facebook Page ──
if ($only === 'both' || $only === 'fb') {
    echo "facebook\n";
    // /photos with a url takes the image and the caption in one call and posts
    // it to the Page feed. published=true is the default but stated for clarity.
    [$c, $j, $raw] = call(IG_GRAPH . '/' . PAGE_ID . '/photos', [
        'url'          => $image,
        'caption'      => $caption,
        'published'    => 'true',
        'access_token' => pageToken(),
    ]);
    if ($c !== 200 || empty($j['post_id'] ?? $j['id'] ?? null)) {
        fwrite(STDERR, '  facebook failed: ' . substr($raw, 0, 300) . "\n");
        if (isset($results['instagram'])) {
            fwrite(STDERR, "  NOTE: Instagram already published — do not re-run without --only=fb\n");
        }
        exit(1);
    }
    $postId = $j['post_id'] ?? $j['id'];
    $results['facebook'] = 'https://www.facebook.com/' . $postId;
    echo "  published $postId\n";
}

echo "\n";
foreach ($results as $k => $v) printf("  %-10s %s\n", $k, $v);
echo "done.\n";
