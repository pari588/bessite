# Introduction post — caption

Status: **DRAFT — nothing published.**
Target: @besyndicate (Instagram Business Account 17841438500653421)

---

## Instagram caption

> Bombay Engineering Syndicate. Supplying Indian industry since 1957.
>
> For close to seventy years we have supplied electric motors, pumps and spares
> to factories, contractors, builders and consultants across India. Customers
> come to us for one reason: the right machine, in stock, sized properly the
> first time.
>
> Motors from 0.37 to 630 kW · IE2, IE3 and IE4 · flame proof and standard TEFC
> Pumps for industry, buildings and agriculture · spares and service backup
>
> 📍 Mumbai — Fort, Kala Ghoda
> 📍 Ahmedabad — Satellite
>
> We will use this page for what we stock, what nearly seven decades on the shop
> floor has taught us, and the occasional thing worth knowing before you buy.
>
> Tell us what you are running and we will tell you what fits.
> 📞 98200 42210 · bombayengg.net
>
> #industrialmotors #electricmotor #waterpumps #ie3motors #flameproofmotor
> #mumbai #ahmedabad #factorymaintenance #plantengineering #manufacturingindia
> #industrialsupplies #motorsandpumps

---

## Notes on the copy

- **First line does the work.** Instagram truncates after roughly 125 characters,
  so the name and the year sit before the fold. Everything after it is for people
  who already decided to tap "more".
- **No pricing**, per the standing rule.
- **No brand names.** The monsoon campaign was explicitly brand-free and I kept
  the same rule here. If you want the dealerships named on the introduction post,
  say so — but remember CG Power (motors) and Crompton (pumps) are separate
  companies and must never be run together in one phrase.
- **"Close to seventy years"** — 1957 to 2026 is 69 years. Accurate as written.
- The line about what the page will be used for sets an expectation that this is
  not purely an ad feed. Worth keeping if you intend to post knowledge-centre
  material, which is what will actually build the following.

---

## Files

| Where | File |
|---|---|
| Instagram feed | `out/intro-post.jpg` (1080×1350) |
| Story | `out/intro-story.jpg` (1080×1920) |

To publish once approved:

```
php scripts/ig-publish.php \
  --image=https://www.bombayengg.net/uploads/promo/intro/out/intro-post.jpg \
  --caption-file=/home/bombayengg/public_html/uploads/promo/intro/ig-caption.txt
```
