#!/usr/bin/env python3
"""Generate the review page from build.py's POSTS list, so it can never
drift from what was actually rendered."""
import html, os
from build import POSTS, BRAND

HERE = os.path.dirname(os.path.abspath(__file__))
KIND_LABEL = {'motor': 'Motor · CG Power', 'pump': 'Pump · Crompton'}

cards = []
for p in POSTS:
    logo, brand_alt, _, headbar = BRAND[p['kind']]
    cap = open(os.path.join(HERE, p['slug'], 'ig-caption.txt'), encoding='utf-8').read().rstrip()
    fold, rest = cap.split('\n\n', 1)
    hl = p['headline'].replace('<br>', ' ').replace('<em>', '').replace('</em>', '')
    cards.append(f"""
<section class="post" id="{p['slug']}">
  <div class="phead">
    <div>
      <span class="kind kind--{p['kind']}">{KIND_LABEL[p['kind']]}</span>
      <h2>{html.escape(p['title'])}</h2>
      <p class="hl">&ldquo;{html.escape(hl)}&rdquo;</p>
    </div>
  </div>
  <div class="two">
    <div class="shots">
      <div class="card">
        <img src="{p['slug']}/out/thumb-post.jpg" alt="Feed post">
        <div class="cb"><div class="ct">Feed &middot; 1080&times;1350</div>
          <a href="{p['slug']}/out/{p['slug']}-post.jpg" download>JPG</a>
          <a class="pdf" href="{p['slug']}/out/{p['slug']}-post.pdf" download>PDF for Canva</a></div>
      </div>
      <div class="card">
        <img src="{p['slug']}/out/thumb-story.jpg" alt="Story">
        <div class="cb"><div class="ct">Story &middot; 1080&times;1920</div>
          <a href="{p['slug']}/out/{p['slug']}-story.jpg" download>JPG</a>
          <a class="pdf" href="{p['slug']}/out/{p['slug']}-story.pdf" download>PDF for Canva</a></div>
      </div>
    </div>
    <div class="pane">
      <div class="ph"><span class="pt">Caption</span>
        <button type="button" data-slug="{p['slug']}">Copy caption</button></div>
      <pre id="f-{p['slug']}">{html.escape(fold)}</pre>
      <span class="fold">&uarr; shows before Instagram&rsquo;s &ldquo;more&rdquo; link</span>
      <pre id="r-{p['slug']}">{html.escape(rest)}</pre>
    </div>
  </div>
</section>""")

nav = ' '.join(f'<a href="#{p["slug"]}">{html.escape(p["title"])}</a>' for p in POSTS)

page = f"""<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>BES — 5 posts ready to publish</title>
<link href="https://fonts.googleapis.com/css2?family=Archivo+Black&family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{{--ink:#1a1a2e;--ink-2:#5a6068;--brand:#157bba;--brand-2:#4b8db6;
 --tint:#eaf4fb;--tint-2:#f8fafb;--line:#e4ebf1;--warn:#a9761f;--pump:#0f7d8c}}
*{{margin:0;padding:0;box-sizing:border-box}}
body{{background:var(--tint-2);color:var(--ink);font-family:"Barlow",system-ui,sans-serif;
 padding:34px 20px 80px;line-height:1.55}}
.wrap{{max-width:1200px;margin:0 auto}}
.mast{{display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;
 border-bottom:1px solid var(--line);margin-bottom:22px;flex-wrap:wrap;gap:12px}}
.mast img{{height:44px}}
.mast span{{font-family:"Barlow Condensed",sans-serif;font-weight:600;font-size:14px;
 letter-spacing:.2em;text-transform:uppercase;color:var(--brand-2)}}
.flag{{display:inline-flex;align-items:center;gap:10px;background:#fdf3e0;border:1px solid #f0dcb4;
 color:var(--warn);font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:15px;
 letter-spacing:.18em;text-transform:uppercase;padding:10px 18px;margin-bottom:14px}}
.flag::before{{content:"";width:10px;height:10px;border-radius:50%;background:var(--warn)}}
h1{{font-family:"Archivo Black",sans-serif;font-size:clamp(26px,5vw,40px);line-height:1.04;letter-spacing:-.02em}}
.sub{{margin-top:10px;color:var(--ink-2);font-size:17px;max-width:70ch}}
.nav{{margin:18px 0 0;display:flex;flex-wrap:wrap;gap:8px}}
.nav a{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:14px;letter-spacing:.1em;
 text-transform:uppercase;text-decoration:none;padding:8px 14px;background:#fff;
 border:1px solid #cfe2f2;color:var(--brand)}}
.rule{{height:4px;margin:22px 0 8px;background:var(--brand)}}

.post{{background:#fff;border:1px solid var(--line);padding:24px 26px;margin-top:26px}}
.phead{{margin-bottom:18px}}
.kind{{display:inline-block;font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:12px;
 letter-spacing:.16em;text-transform:uppercase;padding:4px 10px;color:#fff;margin-bottom:8px}}
.kind--motor{{background:var(--brand)}} .kind--pump{{background:var(--pump)}}
h2{{font-family:"Archivo Black",sans-serif;font-size:25px;letter-spacing:-.01em}}
.hl{{margin-top:6px;color:var(--ink-2);font-size:17px;font-style:italic}}
.two{{display:grid;grid-template-columns:minmax(240px,330px) 1fr;gap:26px;align-items:start}}
@media(max-width:860px){{.two{{grid-template-columns:1fr}}}}
.shots{{display:grid;gap:14px}}
.card{{border:1px solid var(--line);overflow:hidden}}
.card img{{width:100%;height:auto;display:block;border-bottom:1px solid var(--line)}}
.cb{{padding:11px 14px 14px}}
.ct{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:14px;
 letter-spacing:.13em;text-transform:uppercase;color:var(--ink-2);margin-bottom:9px}}
.cb a{{display:block;text-align:center;text-decoration:none;font-family:"Barlow Condensed",sans-serif;
 font-weight:700;font-size:14px;letter-spacing:.13em;text-transform:uppercase;
 padding:10px;background:var(--brand);color:#fff}}
.cb a.pdf{{margin-top:6px;background:#fff;border:1px solid #cfe2f2;color:var(--brand)}}
.pane{{border:1px solid var(--line)}}
.ph{{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
 padding:13px 18px;border-bottom:1px solid var(--line);background:var(--tint)}}
.pt{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:18px;
 letter-spacing:.14em;text-transform:uppercase}}
button{{font-family:"Barlow Condensed",sans-serif;font-weight:700;font-size:14px;letter-spacing:.12em;
 text-transform:uppercase;padding:9px 16px;background:var(--brand);color:#fff;border:0;cursor:pointer}}
button:focus-visible{{outline:3px solid var(--warn);outline-offset:2px}}
pre{{margin:0;padding:18px;white-space:pre-wrap;word-wrap:break-word;
 font-family:"Barlow",system-ui,sans-serif;font-size:15px;line-height:1.6}}
.fold{{display:block;margin:0 18px;padding:6px 0;border-top:1px dashed #cbd8e2;
 font-family:"Barlow Condensed",sans-serif;font-weight:600;font-size:11px;letter-spacing:.16em;
 text-transform:uppercase;color:var(--ink-2)}}
.note{{margin-top:30px;padding:22px 26px;background:var(--tint);border:1px solid #cfe2f2;
 border-left:8px solid var(--brand);color:var(--ink-2);font-size:15px}}
.note b{{color:var(--ink)}} .note ul{{margin:10px 0 0 20px}} .note li{{margin-bottom:9px}}
code{{background:#fff;border:1px solid var(--line);padding:1px 6px;font-size:13px}}
</style></head><body><div class="wrap">

<div class="mast">
  <img src="brand/logo-blue.png" alt="Bombay Engineering Syndicate">
  <span>Internal review</span>
</div>

<div class="flag">Draft — none of these have been published</div>
<h1>Five posts, ready to go</h1>
<p class="sub">Three motor posts and two pump posts, all on the approved template. Every spec comes
from your own product database. The dealership mark switches automatically with the product type —
CG Power on motors, Crompton on pumps — and the header line switches with it.</p>
<div class="nav">{nav}</div>
<div class="rule"></div>

{''.join(cards)}

<div class="note">
  <b>How these were built</b>
  <ul>
    <li><b>One builder, not five copies.</b> <code>build.py</code> holds every post's copy and specs;
    <code>base.html</code> holds the layout. Change the layout once and all five follow. Adding a
    sixth post is a dozen lines.</li>
    <li><b>The brand rule is enforced in code.</b> <code>build_one()</code> asserts that a motor post
    carries the CG monogram and a pump post carries the Crompton wordmark. If those ever disagree the
    build stops rather than shipping the wrong mark.</li>
    <li><b>Four product posts, one knowledge post.</b> The nameplate post is deliberately not selling
    anything &mdash; the competitor scan found that lane completely empty, and it is the one that
    earns the &ldquo;send us the nameplate&rdquo; habit the other posts depend on.</li>
    <li><b>PDF export for Canva.</b> Each artboard also renders as a PDF with the type
    kept as real text, not flattened. Drag one into Canva (Create a design &rarr; Import file)
    and the headline, specs and phone number arrive as <b>editable text boxes</b>, not a
    picture. Fonts are Archivo Black and Barlow Condensed &mdash; both in Canva's library, so
    they should match rather than substitute.</li>
    <li><b>Every post is tagged</b>, 14&ndash;18 hashtags, buying-intent first. SC Industrial writes
    good copy and uses none, which is why they have 91 followers.</li>
  </ul>
</div>

<div class="note" style="border-left-color:#a9761f;background:#fdf3e0;border-color:#f0dcb4">
  <b>Two things to check before publishing</b>
  <ul>
    <li><b>The nameplate photograph says &ldquo;Crompton Greaves Ltd&rdquo;</b> because it is a real
    plate from a pre-2016 motor, taken from your knowledge centre. That is historically correct and
    it is exactly the kind of plate a customer would send you &mdash; but it sits on a post carrying
    the CG mark. If that bothers you, say so and I will shoot a different plate.</li>
    <li><b>Sewage pump specs are qualitative</b>, not numeric, because
    <code>mx_pump_detail</code> has no figures for that category. Give me head, discharge and solids
    handling and I will put real numbers on the plate.</li>
  </ul>
</div>

</div>
<script>
document.querySelectorAll('button[data-slug]').forEach(function(b){{
  b.addEventListener('click',function(){{
    var s=b.dataset.slug;
    var t=document.getElementById('f-'+s).textContent+'\\n\\n'+document.getElementById('r-'+s).textContent;
    navigator.clipboard.writeText(t).then(function(){{
      b.textContent='Copied';setTimeout(function(){{b.textContent='Copy caption';}},1600);
    }});
  }});
}});
</script>
</body></html>"""

open(os.path.join(HERE, 'index.html'), 'w', encoding='utf-8').write(page)
print(f"  index.html written ({len(page)} bytes, {len(POSTS)} posts)")
