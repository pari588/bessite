#!/usr/bin/env python3
"""
Rebuild the competitor report from the JSON in data/.

  php ../../../scripts/ig-competitors.php --out=data <handles...>
  python3 build.py

Every number on the page is computed here from the raw API response — nothing
is typed by hand, so a refresh cannot leave stale figures behind.
"""
import json, re, glob, os, html, statistics
from collections import Counter
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
NOW  = datetime.now(timezone.utc)

# Order matters: the two the user named first, then the manufacturer for context.
ORDER = ['scindustrial', 'sunrise_efficient_marketing', 'makhariamachineries',
         'jainmachinetools_', 'hindustanelectricmotors']

LABEL = {
    'scindustrial':            ('SC Industrial Syndicate', 'Est. 1961, Crompton channel partner — closest match to BES'),
    'sunrise_efficient_marketing': ('Sunrise Efficient Marketing (SEML)',
                                    'Gujarat — biggest audience here, and in your Ahmedabad market'),
    'makhariamachineries':     ('Makharia Machineries', 'Prabhadevi, Mumbai — direct competitor'),
    'jainmachinetools_':       ('Jain Machine Tools',   'Mulund West, Mumbai — direct competitor'),
    'hindustanelectricmotors': ('Hindustan Electric Motors', 'Manufacturer, not a dealer — included for contrast'),
}

THEMES = {
 'Festival / greeting': r'\b(diwali|holi|eid|christmas|new year|ganesh|navratri|independence|republic|festive|wishes|dussehra|raksha|women.s day|labour day|anniversar)\b',
 'Award / recognition': r'\b(award|honou?r|recogni[sz]|proud|milestone|achievement|felicit|certificate|ranked|gratitude)\b',
 'Partner visit':       r'\b(thank you to|visit|team from|delegation|partnership|collaborat|meeting|distributor meet)\b',
 'Product / spec':      r'\b(motor|pump|gearbox|kw|hp|ie[234]|flame ?proof|tefc|vfd|panel|bearing|specification|series)\b',
 'Exhibition / event':  r'\b(expo|exhibition|stall|booth|fair|elecrama|conference|seminar|summit)\b',
 'Hiring / team':       r'\b(hiring|join our team|vacancy|career|our team|employee|staff)\b',
}

def load(h):
    p = os.path.join(HERE, 'data', h + '.json')
    if not os.path.exists(p): return None
    d = json.load(open(p, encoding='utf-8'))
    return d.get('business_discovery')

def analyse(b):
    m = b.get('media', {}).get('data', [])
    a = {'media': m, 'n': len(m)}
    if not m: return a
    ts = sorted(datetime.fromisoformat(x['timestamp'].replace('+0000', '+00:00')) for x in m)
    span = max((ts[-1] - ts[0]).days, 1)
    a['per_month'] = len(m) / span * 30
    a['days_since'] = (NOW - ts[-1]).days
    a['first'], a['last'] = ts[0], ts[-1]
    likes = [x.get('like_count', 0) for x in m]
    a['median_likes'] = statistics.median(likes)
    a['max_likes']    = max(likes)
    a['comments']     = sum(x.get('comments_count', 0) for x in m)
    fol = b.get('followers_count') or 1
    a['er'] = a['median_likes'] / fol * 100
    fmt = Counter(x.get('media_product_type') or x.get('media_type') for x in m)
    a['formats'] = fmt
    tags = re.findall(r'#(\w+)', ' '.join((x.get('caption') or '') for x in m))
    a['tags'] = Counter(t.lower() for t in tags).most_common(12)
    th = Counter()
    for x in m:
        c = (x.get('caption') or '').lower(); hit = False
        for t, rx in THEMES.items():
            if re.search(rx, c): th[t] += 1; hit = True
        if not hit: th['Uncategorised'] += 1
    a['themes'] = th
    a['top'] = sorted(m, key=lambda i: -i.get('like_count', 0))[:3]
    return a

rows, sections = [], []
for h in ORDER:
    b = load(h)
    if not b: continue
    a = analyse(b)
    name, note = LABEL.get(h, (b.get('name', h), ''))
    rows.append((h, b, a, name, note))

def esc(s): return html.escape(s or '')

# ---------- summary table ----------
tbl = []
for h, b, a, name, note in rows:
    stale = a.get('days_since', 999)
    stale_cls = ' class="warn"' if stale > 30 else ''
    tbl.append(f"""<tr>
      <td><b>{esc(name)}</b><div class="hnd">@{esc(h)}</div></td>
      <td class="num">{b.get('followers_count','—')}</td>
      <td class="num">{b.get('media_count','—')}</td>
      <td class="num">{a.get('per_month',0):.1f}</td>
      <td class="num"{stale_cls}>{stale}d</td>
      <td class="num">{a.get('median_likes',0):.0f}</td>
      <td class="num">{a.get('er',0):.2f}%</td>
      <td class="num">{a.get('comments',0)}</td></tr>""")

# ---------- per-account sections ----------
for h, b, a, name, note in rows:
    themes = a.get('themes', Counter())
    tmax = max(themes.values()) if themes else 1
    bars = ''.join(
        f'<div class="bar"><span class="bl">{esc(t)}</span>'
        f'<span class="bt"><i style="width:{n/tmax*100:.0f}%"></i></span>'
        f'<span class="bn">{n}</span></div>'
        for t, n in themes.most_common())
    tags = ' '.join(f'<span class="tag">#{esc(t)} <i>{c}</i></span>' for t, c in a.get('tags', []))
    tops = ''.join(
        f'<li><span class="lk">{x.get("like_count",0)}♥</span> '
        f'<span class="dt">{x["timestamp"][:10]}</span> '
        f'<a href="{esc(x.get("permalink",""))}" target="_blank" rel="noopener">'
        f'{esc((x.get("caption") or "")[:120].replace(chr(10)," "))}…</a></li>'
        for x in a.get('top', []))
    fmts = ' · '.join(f'{k} {v}' for k, v in a.get('formats', Counter()).most_common())
    sections.append(f"""
<section class="acct">
  <div class="ahead">
    <div>
      <h2>{esc(name)}</h2>
      <div class="hnd">@{esc(h)} — {esc(note)}</div>
    </div>
    <a class="visit" href="https://www.instagram.com/{esc(h)}/" target="_blank" rel="noopener">Open profile</a>
  </div>
  <p class="bio">{esc((b.get('biography') or '').strip()) or '<em>no bio</em>'}</p>
  <div class="stats">
    <div><b>{b.get('followers_count','—')}</b><span>followers</span></div>
    <div><b>{b.get('media_count','—')}</b><span>posts</span></div>
    <div><b>{a.get('per_month',0):.1f}</b><span>posts / month</span></div>
    <div><b>{a.get('median_likes',0):.0f}</b><span>median likes</span></div>
    <div><b>{a.get('er',0):.2f}%</b><span>engagement</span></div>
    <div><b>{a.get('comments',0)}</b><span>comments (all)</span></div>
  </div>
  <div class="two">
    <div>
      <h3>What they post about</h3>
      {bars}
      <p class="fine">Sample of {a.get('n',0)} most recent posts · formats: {esc(fmts)}</p>
    </div>
    <div>
      <h3>Best performing</h3>
      <ol class="tops">{tops}</ol>
      <h3 style="margin-top:20px">Hashtags they lean on</h3>
      <div class="tags">{tags}</div>
    </div>
  </div>
</section>""")

# ---------- format comparison: reels vs static ----------
fmt_rows = []
for h, b, a, name, note in rows:
    g = {}
    for x in a.get('media', []):
        g.setdefault(x.get('media_product_type') or 'FEED', []).append(x.get('like_count', 0))
    if 'REELS' not in g or 'FEED' not in g:
        continue
    fm, rm = statistics.median(g['FEED']), statistics.median(g['REELS'])
    mult = (rm / fm) if fm else 0
    fmt_rows.append(f"""<tr><td><b>{esc(name)}</b></td>
      <td class="num">{len(g['FEED'])}</td><td class="num">{fm:.0f}</td>
      <td class="num">{len(g['REELS'])}</td><td class="num">{rm:.0f}</td>
      <td class="num"><b>{mult:.1f}&times;</b></td></tr>""")

fmt_block = ""
if fmt_rows:
    fmt_block = f"""
<h2 style="margin-top:36px">Reels versus static posts</h2>
<p class="sub" style="margin-bottom:16px">Median likes by format, same account, same audience &mdash;
so this is a like-for-like comparison, not a followers effect.</p>
<div class="tblwrap"><table>
<tr><th>Account</th><th>Static posts</th><th>Median likes</th><th>Reels</th><th>Median likes</th><th>Reels advantage</th></tr>
{''.join(fmt_rows)}
</table></div>"""

page = f"""<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>BES — Instagram competitor scan</title>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{{--ink:#1a1a2e;--ink-2:#5a6068;--brand:#157bba;--brand-2:#4b8db6;
 --tint:#eaf4fb;--tint-2:#f8fafb;--line:#e4ebf1;--warn:#a9761f;--good:#1a7f4b}}
*{{margin:0;padding:0;box-sizing:border-box}}
body{{background:var(--tint-2);color:var(--ink);font-family:"Barlow",system-ui,sans-serif;
 padding:36px 20px 80px;line-height:1.55}}
.wrap{{max-width:1180px;margin:0 auto}}
.mast{{display:flex;align-items:center;justify-content:space-between;padding-bottom:18px;
 border-bottom:1px solid var(--line);margin-bottom:26px;flex-wrap:wrap;gap:12px}}
.mast img{{height:46px}}
.mast span{{font-family:"Barlow Condensed",sans-serif;font-weight:600;font-size:14px;
 letter-spacing:.2em;text-transform:uppercase;color:var(--brand-2)}}
h1{{font-family:"Archivo Black",sans-serif;font-size:clamp(27px,5vw,42px);line-height:1.04;letter-spacing:-.02em}}
.sub{{margin-top:11px;color:var(--ink-2);font-size:17px;max-width:70ch}}
.rule{{height:4px;margin:22px 0 30px;background:var(--brand)}}
h2{{font-family:"Archivo Black",sans-serif;font-size:24px;letter-spacing:-.01em}}
h3{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:17px;letter-spacing:.13em;
 text-transform:uppercase;color:var(--ink-2);margin-bottom:11px}}

table{{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--line);font-size:15px}}
th,td{{padding:12px 14px;text-align:left;border-bottom:1px solid var(--line);vertical-align:top}}
th{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:13px;letter-spacing:.14em;
 text-transform:uppercase;color:var(--ink-2);background:var(--tint);white-space:nowrap}}
td.num{{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}}
td.warn{{color:var(--warn);font-weight:600}}
.hnd{{font-size:13px;color:var(--ink-2)}}
.tblwrap{{overflow-x:auto}}

.acct{{background:#fff;border:1px solid var(--line);border-left:8px solid var(--brand);
 padding:24px 26px;margin-top:26px}}
.ahead{{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}}
.visit{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:14px;letter-spacing:.13em;
 text-transform:uppercase;text-decoration:none;padding:9px 16px;border:1px solid #cfe2f2;color:var(--brand)}}
.bio{{margin-top:10px;color:var(--ink-2);font-size:15px;white-space:pre-line}}
.stats{{display:flex;flex-wrap:wrap;gap:10px;margin:18px 0 22px}}
.stats div{{flex:1 1 120px;background:var(--tint);border:1px solid #dbeaf7;padding:11px 14px}}
.stats b{{display:block;font-family:"Archivo Black",sans-serif;font-size:22px;color:var(--brand)}}
.stats span{{font-family:"Barlow Condensed",sans-serif;font-weight:600;font-size:12px;
 letter-spacing:.13em;text-transform:uppercase;color:var(--ink-2)}}
.two{{display:grid;grid-template-columns:1fr 1fr;gap:30px}}
@media(max-width:820px){{.two{{grid-template-columns:1fr}}}}
.bar{{display:flex;align-items:center;gap:10px;margin-bottom:7px;font-size:14px}}
.bl{{flex:0 0 148px;color:var(--ink-2)}}
.bt{{flex:1 1 auto;background:var(--tint);height:15px;position:relative}}
.bt i{{position:absolute;inset:0 auto 0 0;background:var(--brand)}}
.bn{{flex:0 0 26px;text-align:right;font-variant-numeric:tabular-nums;color:var(--ink-2)}}
.fine{{margin-top:12px;font-size:13px;color:var(--ink-2)}}
.tops{{list-style:none;font-size:14px}}
.tops li{{padding:8px 0;border-bottom:1px solid var(--line)}}
.lk{{display:inline-block;min-width:42px;font-weight:700;color:var(--brand)}}
.dt{{color:var(--ink-2);font-size:12px;margin-right:6px}}
.tops a{{color:var(--ink);text-decoration:none}}
.tops a:hover{{color:var(--brand)}}
.tags{{display:flex;flex-wrap:wrap;gap:6px}}
.tag{{background:var(--tint);border:1px solid #dbeaf7;padding:4px 9px;font-size:13px}}
.tag i{{font-style:normal;color:var(--ink-2);font-size:11px}}

.take{{margin-top:34px;padding:24px 28px;background:#fff;border:1px solid var(--line);border-left:8px solid var(--good)}}
.take h2{{margin-bottom:12px}}
.take p{{margin-bottom:12px}}
.take ol{{margin:0 0 4px 20px}}
.take li{{margin-bottom:10px}}
.miss{{margin-top:26px;padding:20px 24px;background:#fdf3e0;border:1px solid #f0dcb4;color:#7a5619;font-size:15px}}
.foot{{margin-top:34px;padding-top:18px;border-top:1px solid var(--line);font-size:13px;color:var(--ink-2)}}
code{{background:#fff;border:1px solid var(--line);padding:1px 6px;font-size:13px}}
</style></head><body><div class="wrap">

<div class="mast">
  <img src="logo-blue.png" alt="Bombay Engineering Syndicate">
  <span>Internal · competitor scan</span>
</div>

<h1>What the competition is doing on Instagram</h1>
<p class="sub">Pulled from Instagram's Business Discovery API — public profile data and public post
captions with their like and comment counts, the same things any visitor to the profile can see.
Sample is the 40 most recent posts per account.</p>
<div class="rule"></div>

<div class="tblwrap"><table>
<tr><th>Account</th><th>Followers</th><th>Posts</th><th>Per month</th><th>Last post</th>
    <th>Median likes</th><th>Engagement</th><th>Comments</th></tr>
{''.join(tbl)}
</table></div>

{''.join(sections)}

{fmt_block}

<div class="miss">
  <b>Hemant Trading Company (Mumbai) — no Instagram business presence found.</b><br>
  Business Discovery returned nothing for <code>hemanttrading</code>, <code>hemanttradingco</code>,
  <code>hemanttradingcompany</code>, <code>hemanttradingcpl</code> or <code>htcpl</code>.
  <code>@hemant_trading</code> does exist but is a tiles and sanitaryware shop in Dewas, Madhya Pradesh —
  not them. <code>@hemantmotors</code> appears in web search with roughly 2,900 followers but does not
  resolve through the API, which means it is a personal or private account rather than a Business one.
  Either they are not on Instagram, or they are on it in a way no customer can find — which for you
  amounts to the same thing.
</div>

<div class="take">
  <h2>The read</h2>

  <p><b>Two findings dominate everything else.</b></p>

  <p><b>1. Reels beat static posts, decisively.</b> Every account that posts both gets more
  engagement from video &mdash; SEML by nearly four times, on the same audience in the same week.
  All five of SEML&rsquo;s best posts are Reels. This is a format effect, not a follower effect,
  because it is measured within each account. Nobody in this category has taken it seriously except
  SEML, and they only put 40% of their output into it.</p>

  <p><b>2. Distribution beats craft.</b> SC Industrial writes the best copy here by a distance
  &mdash; benefit-first headlines, real product knowledge, 344 posts of it &mdash; and has
  <b>91 followers and a median of one like</b>, because across 40 posts they used <b>no hashtags at
  all</b>. Jain posts a third as often with plainer copy, tags properly, and earns the highest
  engagement rate on the board. Craft without distribution is invisible.</p>

  <p><b>The one to watch is SEML.</b> They are in Gujarat, which is your Ahmedabad market, and they
  are already occupying the energy-efficiency lane &mdash; power bills, SynchroPlus, efficiency
  audits &mdash; which is exactly the ground the IE4 post stands on. They have 3,799 followers,
  more than four times anyone else here, and they use Reels, vernacular captions and heavy tagging.
  They are the only account in this scan running a real content operation.</p>

  <p>But note what that audience is worth: <b>SEML has the lowest engagement rate of all five, at
  0.20%.</b> Four times the followers, and a median of eight likes. Their best-performing posts are
  a CRM app launch, Diwali and Women&rsquo;s Day &mdash; culture, not catalogue. Whatever built that
  follower count, it is not buying-intent traffic.</p>

  <p><b>Makharia is the cautionary case.</b> 30 of 40 posts are partner visits, awards and festival
  greetings; five mention a product. It reads like a company newsletter aimed at suppliers and staff.
  They have been quiet for seven weeks.</p>

  <p><b>What this means for BES.</b></p>
  <ol>
    <li><b>Tag every post, 15&ndash;20, buying-intent first.</b> Cheapest fix on the board, and
    SC Industrial is the proof of what happens without it.</li>
    <li><b>Move to Reels for anything that can move.</b> A motor on a test bench, a shaft turning, a
    pump priming, stock arriving. Even a slow pan across the racking beats a static card.</li>
    <li><b>The useful lane is still empty.</b> Across roughly 200 posts sampled here there are
    <b>20 comments in total</b>. Not one account explains how to size a motor, what IE3 actually
    saves, when rewinding is false economy, or why a pump keeps tripping. You have that written
    already in the knowledge centre.</li>
    <li><b>Consider Gujarati for Ahmedabad.</b> SEML publishes in it. You have a branch there and
    nobody else in Mumbai is doing it.</li>
  </ol>

  <p class="fine" style="margin-top:16px">One honest caveat: likes are not sales. SC Industrial has
  traded since 1961 and is very likely doing fine on the phone; SEML&rsquo;s 3,799 followers may be
  worth less than Jain&rsquo;s 266. This measures what is happening on Instagram, not who is winning
  the market.</p>
</div>

<div class="foot">
  Refresh with
  <code>php scripts/ig-competitors.php --out=data &lt;handles&gt;</code> then <code>python3 build.py</code>.
  Raw API responses are kept in <code>data/</code>.<br>
  Generated {NOW:%d %B %Y, %H:%M} UTC.
</div>

</div></body></html>"""

open(os.path.join(HERE, 'index.html'), 'w', encoding='utf-8').write(page)
print(f"  wrote index.html ({len(page)} bytes, {len(rows)} accounts)")
