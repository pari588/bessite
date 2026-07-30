<?php
/**
 * build-promo-hub.php — regenerate the single review page at /uploads/promo/
 *
 * Publication status is read LIVE from Instagram rather than typed in, so the
 * hub cannot drift out of date the way a hand-maintained list would. A local
 * post is matched to a published one by comparing the first line of its caption
 * against the first line of the live caption — reliable, because that line is
 * the headline and is unique per post.
 *
 * Re-run after publishing anything:
 *   php scripts/build-promo-hub.php
 */

require_once '/home/bombayengg/instagram-config.php';
date_default_timezone_set('Asia/Kolkata');

const ROOT = '/home/bombayengg/public_html/uploads/promo';
const WEB  = 'https://www.bombayengg.net/uploads/promo';

/** slug => [folder, display name, one-line description] */
$ITEMS = [
    ['monsoon',                  'Monsoon Motor Damage',        'Seasonal campaign — flood and surge damage', 'monsoon-post.jpg'],
    ['intro',                    'Introduction Post',           'Who BES is — first post on the account',     'intro-post.jpg'],
    ['product-ie4',              'Super Premium IE4',           'Energy-efficient motor, product post',       'ie4-post.jpg'],
    ['posts/hazardous-area',     'Hazardous Area Motors',       'Flameproof / increased safety / non-sparking', 'hazardous-area-post.jpg'],
    ['posts/fire-pump-motors',   'UL Listed Fire Pump Motors',  'Fire pump motors, UL 1004-5 / NFPA 20',      'fire-pump-motors-post.jpg'],
    ['posts/read-the-nameplate', 'How to Read a Nameplate',     'Knowledge post — the empty competitor lane', 'read-the-nameplate-post.jpg'],
    ['posts/sewage-drainage',    'Sewage & Drainage Pumps',     'Monsoon-relevant pump post',                 'sewage-drainage-post.jpg'],
    ['posts/booster-pumps',      'Pressure Booster Pumps',      'High-rise water pressure',                   'booster-pumps-post.jpg'],
];

// ── live Instagram state ────────────────────────────────────────────────────
function igMedia(): array
{
    $url = IG_GRAPH . '/' . IG_USER_ID . '/media?fields=id,caption,permalink,timestamp,media_type'
         . '&limit=50&access_token=' . IG_ACCESS_TOKEN;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $j = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $j['data'] ?? [];
}

function firstLine(string $s): string
{
    $s = trim(strtok($s, "\n"));
    return mb_strtolower(preg_replace('/\s+/', ' ', $s));
}

/** Review state written by core/promo-feedback.php */
$review = is_file('/home/bombayengg/promo-feedback.json')
        ? (json_decode(file_get_contents('/home/bombayengg/promo-feedback.json'), true) ?: [])
        : [];

$live = igMedia();
$published = [];
foreach ($live as $m) {
    if (!empty($m['caption'])) $published[firstLine($m['caption'])] = $m;
}
echo '  instagram: ' . count($live) . " post(s) live\n";

// ── build the cards ─────────────────────────────────────────────────────────
$cards = [];
$nReady = $nLive = $nApproved = 0;

foreach ($ITEMS as [$slug, $name, $desc, $postFile]) {
    $dir = ROOT . '/' . $slug;
    if (!is_dir($dir)) { echo "  !! missing $slug\n"; continue; }

    $capFile = "$dir/ig-caption.txt";
    $cap     = is_file($capFile) ? file_get_contents($capFile) : '';
    $hit     = $cap ? ($published[firstLine($cap)] ?? null) : null;

    $thumb = is_file("$dir/out/thumb-post.jpg") ? "$slug/out/thumb-post.jpg" : null;
    $jpg   = is_file("$dir/out/$postFile")      ? "$slug/out/$postFile"      : null;
    $pdf   = is_file("$dir/out/" . str_replace('.jpg', '.pdf', $postFile))
           ? "$slug/out/" . str_replace('.jpg', '.pdf', $postFile) : null;
    $story = null;
    foreach (glob("$dir/out/*story*.jpg") as $g) {
        if (strpos(basename($g), 'thumb') === false) { $story = $slug . '/out/' . basename($g); break; }
    }

    $key = str_starts_with($slug, 'posts/') ? substr($slug, 6) : $slug;
    $rv  = $review[$key] ?? null;
    $rst = $rv['status'] ?? 'new';

    if ($hit) { $nLive++;  $badge = 'live';  $bt = 'Published ' . date('j M', strtotime($hit['timestamp'])); }
    else      { $nReady++; $badge = 'ready'; $bt = 'Ready — not published'; }
    if ($rst === 'approved') $nApproved++;

    $rbadge = '';
    if ($rst === 'approved') {
        $rbadge = '<span class="rv rv--ok">&#10003; Approved by you</span>';
    } elseif ($rst === 'changes') {
        $note = trim((string)($rv['note'] ?? ''));
        $rbadge = '<span class="rv rv--chg">Changes requested</span>'
                . ($note !== '' ? '<p class="rvn">' . htmlspecialchars(mb_substr($note, 0, 160))
                                . (mb_strlen($note) > 160 ? '&hellip;' : '') . '</p>' : '');
    }

    /**
     * The five batch posts live under posts/ and share ONE review page with
     * anchors — they have no index.html of their own, so linking to the folder
     * returns 403 (directory listing is off, and should stay off). Link to the
     * anchor instead.
     */
    $reviewUrl = str_starts_with($slug, 'posts/')
               ? 'posts/#' . substr($slug, 6)
               : $slug . '/';

    $links = [];
    $links[] = '<a class="b" href="' . $reviewUrl . '">Review page</a>';
    if ($jpg)   $links[] = '<a href="' . $jpg . '" download>Feed JPG</a>';
    if ($story) $links[] = '<a href="' . $story . '" download>Story</a>';
    if ($pdf)   $links[] = '<a href="' . $pdf . '" download>PDF</a>';
    if ($hit)   $links[] = '<a class="ig" href="' . htmlspecialchars($hit['permalink']) . '" target="_blank" rel="noopener">View on Instagram</a>';

    $head = $cap ? htmlspecialchars(trim(strtok($cap, "\n"))) : '';

    $cards[] = '<article class="card ' . $badge . '">'
        . ($thumb ? '<img src="' . $thumb . '" alt="' . htmlspecialchars($name) . '">' : '<div class="noimg">no artwork</div>')
        . '<div class="cb">'
        . '<span class="badge badge--' . $badge . '">' . $bt . '</span>'
        . '<h3>' . htmlspecialchars($name) . '</h3>'
        . '<p class="d">' . htmlspecialchars($desc) . '</p>'
        . ($head ? '<p class="q">&ldquo;' . $head . '&rdquo;</p>' : '')
        . $rbadge
        . '<div class="links">' . implode('', $links) . '</div>'
        . '</div></article>';
}

$gen = date('j M Y, H:i');

$page = <<<HTML
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>BES — Social posts</title>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--ink:#1a1a2e;--ink-2:#5a6068;--brand:#157bba;--brand-2:#4b8db6;
 --tint:#eaf4fb;--tint-2:#f8fafb;--line:#e4ebf1;--live:#1a7f4b;--warn:#a9761f}
*{margin:0;padding:0;box-sizing:border-box}
body{background:var(--tint-2);color:var(--ink);font-family:"Barlow",system-ui,sans-serif;
 padding:34px 20px 80px;line-height:1.55}
.wrap{max-width:1240px;margin:0 auto}
.mast{display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;
 border-bottom:1px solid var(--line);margin-bottom:24px;flex-wrap:wrap;gap:12px}
.mast img{height:46px}
.mast span{font-family:"Barlow Condensed",sans-serif;font-weight:600;font-size:14px;
 letter-spacing:.2em;text-transform:uppercase;color:var(--brand-2)}
h1{font-family:"Archivo Black",sans-serif;font-size:clamp(27px,5vw,42px);line-height:1.04;letter-spacing:-.02em}
.sub{margin-top:10px;color:var(--ink-2);font-size:17px;max-width:72ch}
.stats{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0 6px}
.stat{background:#fff;border:1px solid var(--line);padding:12px 18px;min-width:132px}
.stat b{display:block;font-family:"Archivo Black",sans-serif;font-size:26px;color:var(--brand)}
.stat span{font-family:"Barlow Condensed",sans-serif;font-weight:600;font-size:12px;
 letter-spacing:.14em;text-transform:uppercase;color:var(--ink-2)}
.rule{height:4px;margin:20px 0 30px;background:var(--brand)}
h2{font-family:"Archivo Black",sans-serif;font-size:21px;margin:34px 0 14px}

.grid{display:grid;gap:22px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}
.card{background:#fff;border:1px solid var(--line);display:flex;flex-direction:column;overflow:hidden}
.card.live{border-color:#bfe0cd}
.card img{width:100%;height:auto;display:block;border-bottom:1px solid var(--line)}
.noimg{padding:44px 0;text-align:center;color:var(--ink-2);background:var(--tint);
 font-family:"Barlow Condensed",sans-serif;letter-spacing:.14em;text-transform:uppercase;font-size:13px}
.cb{padding:15px 17px 18px;display:flex;flex-direction:column;flex:1 1 auto}
.badge{align-self:flex-start;font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:11px;
 letter-spacing:.15em;text-transform:uppercase;padding:4px 9px;color:#fff;margin-bottom:9px}
.badge--live{background:var(--live)} .badge--ready{background:var(--warn)}
h3{font-family:"Archivo Black",sans-serif;font-size:17px;letter-spacing:-.01em}
.d{margin-top:5px;font-size:14px;color:var(--ink-2)}
.q{margin-top:9px;font-size:14px;font-style:italic;color:var(--ink)}
.rv{display:inline-block;margin-top:9px;font-family:"Barlow Condensed",sans-serif;font-weight:700;
 font-size:11px;letter-spacing:.14em;text-transform:uppercase;padding:4px 9px;color:#fff}
.rv--ok{background:var(--live)} .rv--chg{background:var(--warn)}
.rvn{margin-top:7px;font-size:13px;color:var(--ink-2);font-style:italic}
.links{margin-top:auto;padding-top:13px;display:flex;flex-wrap:wrap;gap:6px}
.links a{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:12px;letter-spacing:.11em;
 text-transform:uppercase;text-decoration:none;padding:7px 11px;border:1px solid #cfe2f2;color:var(--brand)}
.links a.b{background:var(--brand);color:#fff;border-color:var(--brand)}
.links a.ig{background:var(--live);color:#fff;border-color:var(--live)}
.tools{display:grid;gap:18px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}
.tool{background:#fff;border:1px solid var(--line);border-left:8px solid var(--brand);padding:18px 20px}
.tool h3{font-size:16px;margin-bottom:6px}
.tool p{font-size:14px;color:var(--ink-2)}
.tool a{display:inline-block;margin-top:10px;font-family:"Barlow Condensed",sans-serif;font-weight:700;
 font-size:12px;letter-spacing:.11em;text-transform:uppercase;text-decoration:none;
 padding:7px 12px;background:var(--brand);color:#fff}
.foot{margin-top:36px;padding-top:18px;border-top:1px solid var(--line);font-size:13px;color:var(--ink-2)}
code{background:#fff;border:1px solid var(--line);padding:1px 6px;font-size:12.5px}
</style></head><body><div class="wrap">

<div class="mast">
  <img src="posts/brand/logo-blue.png" alt="Bombay Engineering Syndicate">
  <span>Internal · social review</span>
</div>

<h1>Social posts</h1>
<p class="sub">Everything built for @besyndicate in one place. Published status is read live from
Instagram each time this page is rebuilt, so it reflects the account rather than a list someone
remembered to update.</p>

<div class="stats">
  <div class="stat"><b>{$nLive}</b><span>published</span></div>
  <div class="stat"><b>{$nReady}</b><span>ready to post</span></div>
  <div class="stat"><b>{$nApproved}</b><span>approved by you</span></div>
  <div class="stat"><b>10</b><span>designs in Canva</span></div>
</div>
<div class="rule"></div>

<div class="grid">
HTML;

$page .= implode("\n", $cards);

$page .= <<<HTML
</div>

<h2>Reference</h2>
<div class="tools">
  <div class="tool">
    <h3>Competitor scan</h3>
    <p>Five rival accounts measured through Instagram's Business Discovery API — cadence,
    engagement, Reels advantage, and where the gap is.</p>
    <a href="competitors/">Open report</a>
  </div>
  <div class="tool">
    <h3>Canva</h3>
    <p>All ten artboards imported as editable designs and filed under
    <b>BES Social Posts</b>. Re-run the organiser after any new import.</p>
    <a href="https://www.canva.com/folder/FAHQ2SIG_8Q" target="_blank" rel="noopener">Open folder</a>
  </div>
  <div class="tool">
    <h3>Instagram</h3>
    <p>Live account. Publishing is manual — nothing goes out without being asked for.</p>
    <a href="https://www.instagram.com/besyndicate/" target="_blank" rel="noopener">@besyndicate</a>
  </div>
</div>

<div class="foot">
  Rebuild this page with <code>php scripts/build-promo-hub.php</code> — it re-reads Instagram,
  so run it after publishing anything.<br>
  Generated {$gen} IST.
</div>

</div></body></html>
HTML;

file_put_contents(ROOT . '/index.html', $page);
chmod(ROOT . '/index.html', 0644);
printf("  wrote index.html — %d published, %d ready\n", $nLive, $nReady);
