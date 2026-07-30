<?php
/**
 * ig-competitors.php — pull public competitor data via Instagram Business Discovery
 *
 * Business Discovery is Instagram's own read-only endpoint for looking up other
 * PUBLIC Business/Creator accounts. It returns only what any visitor to the
 * profile can already see: bio, follower/post counts, and public post captions
 * with their like and comment counts. No private data, no scraping.
 *
 * A "(#110) Invalid user id" means the handle is wrong, the account is private,
 * or it is a personal account — Business Discovery only resolves Business and
 * Creator accounts. It does NOT mean the account does not exist.
 *
 * Usage:
 *   php scripts/ig-competitors.php handle1 handle2 ...
 *   php scripts/ig-competitors.php --out=DIR handle1 handle2 ...
 */

require_once '/home/bombayengg/instagram-config.php';

$args = array_slice($argv, 1);
$out  = null;
$handles = [];
foreach ($args as $a) {
    if (str_starts_with($a, '--out=')) $out = substr($a, 6);
    else $handles[] = ltrim($a, '@');
}

if (!$handles) {
    fwrite(STDERR, "usage: ig-competitors.php [--out=DIR] handle1 handle2 ...\n");
    exit(2);
}

$fields = 'username,name,biography,website,followers_count,follows_count,media_count,'
        . 'media.limit(40){caption,like_count,comments_count,media_type,media_product_type,timestamp,permalink}';

foreach ($handles as $h) {
    // NOTE: nested {…} must not be shell-globbed — this is why the curl CLI needs -g.
    // Using PHP's HTTP client sidesteps that entirely.
    $url = IG_GRAPH . '/' . IG_USER_ID . '?fields=' . rawurlencode("business_discovery.username($h){$fields}")
         . '&access_token=' . rawurlencode(IG_ACCESS_TOKEN);
    // rawurlencode would escape the parens Graph needs, so rebuild carefully:
    $url = IG_GRAPH . '/' . IG_USER_ID . '?fields=business_discovery.username(' . $h . '){' . $fields . '}'
         . '&access_token=' . IG_ACCESS_TOKEN;

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60]);
    $raw = curl_exec($ch);
    curl_close($ch);

    $j = json_decode($raw, true);
    if (isset($j['error'])) {
        printf("  %-26s — %s\n", $h, $j['error']['message']);
        continue;
    }
    $b = $j['business_discovery'] ?? [];
    printf("  %-26s followers %-7s posts %-6s %s\n",
        $h, $b['followers_count'] ?? '?', $b['media_count'] ?? '?', $b['name'] ?? '');

    if ($out) {
        if (!is_dir($out)) mkdir($out, 0755, true);
        file_put_contents("$out/$h.json", json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
