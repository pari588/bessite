# SEO Improvement Plan: Phase 3 & Phase 4
**Site:** https://www.bombayengg.net
**Date:** 2026-02-20
**Status:** Planning — Not yet implemented

---

## Audit Summary (Current State)

### What's Already Done (Phase 1 & 2)
- Dynamic `<title>`, `og:title`, `og:description`, `og:url` on all pages including landing pages
- Canonical URLs with query string stripping
- Hreflang tags (en-IN + x-default)
- LocalBusiness + Organization JSON-LD schema in header.php
- Product schema on pump/motor detail pages
- BreadcrumbList schema on category/detail pages
- FAQPage schema on all 4 landing pages
- robots.txt with AI crawler support + sitemap reference
- HTTPS enforced with 301 redirects, www normalization
- Security headers (HSTS, CSP, X-Frame-Options, Permissions-Policy)
- Browser caching (1yr for static assets), GZIP compression, ETags disabled
- Google Fonts with `display=swap` and `preconnect`
- Scripts deferred (except jQuery — see below)
- 4 local landing pages: motors-in-mumbai, motors-in-ahmedabad, pumps-in-mumbai, pumps-in-ahmedabad
- Google Maps with business name pins on all pages
- Correct business hours (Mon-Sat 10:00 AM - 6:00 PM) everywhere
- Full Mumbai address (Modern House, Kala Ghoda, 400001) everywhere

### What's Broken or Missing
1. **Landing pages NOT in sitemap.xml** — Google can't discover them efficiently
2. **jQuery loaded render-blocking** (header.php line 111) — no `defer` attribute
3. **No `loading="lazy"` on any images** — all images load immediately
4. **No image width/height attributes** — CLS risk on all pages
5. **404 page image missing alt text** (x-404.php line 6)
6. **404 page has no links** — dead end, no way back to site
7. **Homepage FAQ accordion has no FAQPage schema** — missed opportunity
8. **Knowledge center articles have no Article schema**
9. **No `llms.txt` file** — referenced in robots.txt but doesn't exist
10. **Footer content is database-driven** — need to verify it has proper links
11. **H2/H3 nesting issue on homepage** — line 124 should be H3
12. **No related products/cross-linking** on detail pages
13. **Sitemap lastmod dates stale** (all show 2025-11-29)
14. **No image sitemap** — product images not indexed

---

## Phase 3: Technical SEO & Performance

**Goal:** Fix all technical issues, improve page speed, ensure complete indexability.

### 3.1 Sitemap Overhaul (CRITICAL)
**Priority: HIGH — Direct impact on indexing**

- **Add 4 landing pages to sitemap.xml:**
  - `https://www.bombayengg.net/motors-in-mumbai` (priority 0.8)
  - `https://www.bombayengg.net/motors-in-ahmedabad` (priority 0.8)
  - `https://www.bombayengg.net/pumps-in-mumbai` (priority 0.8)
  - `https://www.bombayengg.net/pumps-in-ahmedabad` (priority 0.8)
- **Update all `<lastmod>` dates** to current date
- **Update `sitemap_index.xml`** lastmod date
- **Consider adding image sitemap** entries for product photos
- **Files:** `xsite/sitemap.xml`, `sitemap_index.xml`

### 3.2 Page Speed: Render-Blocking jQuery Fix
**Priority: HIGH — Core Web Vitals (LCP)**

- Add `defer` attribute to jQuery script tag in header.php (line 111)
- Verify no inline scripts depend on jQuery being immediately available
- If dependency exists, move jQuery to footer or use `DOMContentLoaded` wrapper
- **File:** `xsite/mod/header.php`

### 3.3 Image Lazy Loading
**Priority: HIGH — Core Web Vitals (LCP, page weight)**

- Add `loading="lazy"` to all `<img>` tags below the fold
- Do NOT lazy-load the hero/slider images (above fold)
- Do NOT lazy-load the logo
- Target files:
  - `xsite/mod/home/x-home.php` — partner logos, feature icons, service images
  - `xsite/mod/footer.php` — decorative shapes
  - All 4 landing page templates — brand logos, map iframes (already have lazy)
  - Product detail pages (pump/motor templates)

### 3.4 Image Dimensions (CLS Fix)
**Priority: MEDIUM — Core Web Vitals (CLS)**

- Add explicit `width` and `height` attributes to all `<img>` tags
- Focus on: logo, homepage slider, partner logos, 404 image
- This prevents layout shifts during page load
- **Files:** header.php, home/x-home.php, footer.php, x-404.php

### 3.5 Fix 404 Page
**Priority: MEDIUM — User experience + crawl efficiency**

- Add `alt="Page not found"` to 404 image
- Add navigation links: Home, Motors, Pumps, Contact Us
- Add search suggestion or popular products
- Add proper meta robots noindex (404s shouldn't be indexed)
- **File:** `xsite/inc/x-404.php`

### 3.6 Homepage Heading Hierarchy Fix
**Priority: LOW — Semantic correctness**

- Change the H2 at line 124 in x-home.php that follows H3s to maintain proper H1 > H2 > H3 nesting
- Audit all pages for heading hierarchy issues
- **File:** `xsite/mod/home/x-home.php`

### 3.7 Create llms.txt
**Priority: LOW — AI discoverability**

- robots.txt references `/llms.txt` but file doesn't exist
- Create it with: company overview, product categories, key pages, contact info
- Format per the llms.txt standard
- **File:** `xsite/llms.txt` (served at root via .htaccess rewrite)

---

## Phase 4: Content & Authority SEO

**Goal:** Add structured data, improve internal linking, strengthen content signals for higher rankings.

### 4.1 Homepage FAQ Schema
**Priority: HIGH — Rich results eligibility**

- Homepage has an FAQ accordion section but no FAQPage JSON-LD schema
- Add `application/ld+json` FAQPage markup matching the accordion content
- This can generate FAQ rich snippets in Google search results
- **File:** `xsite/mod/home/x-home.php`

### 4.2 Knowledge Center Article Schema
**Priority: HIGH — Rich results for educational content**

- 15 knowledge center articles exist with no Article schema
- Add `Article` or `TechArticle` JSON-LD to each article page:
  - `headline`, `author` (Organization), `datePublished`, `dateModified`
  - `publisher` with logo, `image`, `description`
- This enables article rich results in Google
- **Files:** Knowledge center template files in `xsite/mod/knowledge-center/`

### 4.3 Internal Linking Strategy
**Priority: HIGH — Link equity distribution + crawlability**

#### 4.3.1 Navigation Links to Landing Pages
- Verify the 4 landing pages are accessible from the main navigation menu
- If not in database menu, add them under Motors/Pumps dropdowns
- Or add a "Locations" section in the footer

#### 4.3.2 Related Products on Detail Pages
- On each motor detail page, add "Related Motors" section linking to 3-4 other motor types
- On each pump detail page, add "Related Pumps" section
- This distributes link equity and keeps users engaged

#### 4.3.3 Knowledge Center Cross-Links
- On motor detail pages, link to relevant knowledge articles (e.g., "Motor Efficiency Classes" from IE3 motor page)
- On pump detail pages, link to "Crompton Pump Selection Guide"
- In knowledge articles, link back to relevant product pages

#### 4.3.4 Landing Page Interlinking
- motors-in-mumbai should link to pumps-in-mumbai ("Also need pumps?")
- Each city page should link to the other city page ("Also available in Ahmedabad/Mumbai")
- All landing pages should link to the main product category pages

### 4.4 LocalBusiness Schema Enhancement
**Priority: MEDIUM — Local SEO signals**

- Current schema in header.php is good but could be enhanced:
  - Add `hasOfferCatalog` with product categories
  - Add `areaServed` with specific city-level GeoShape data
  - Add `knowsAbout` with product expertise areas
  - Add `award` or `certification` if applicable (ISO, authorized dealer certs)
  - Add `review` / `aggregateRating` if customer reviews exist
- Consider separate LocalBusiness schema per office (Mumbai + Ahmedabad) on their respective landing pages

### 4.5 Product Schema Enhancement
**Priority: MEDIUM — Product rich results**

- Current product schema exists on detail pages
- Enhance with:
  - `brand.name` (CG Power, Crompton, Kirloskar)
  - `offers.priceValidUntil`
  - `offers.availability` (InStock/OutOfStock)
  - `aggregateRating` if review data available
  - `additionalProperty` for technical specs (kW, voltage, RPM, mounting type)
  - `isRelatedTo` linking to other products

### 4.6 Google Business Profile Integration
**Priority: MEDIUM — Local pack rankings**

- Ensure Google Business Profile for both Mumbai and Ahmedabad offices matches exactly:
  - Mumbai: Ground Floor, Modern House, 17, Dr. V.B. Gandhi Marg, Kala Ghoda, Fort, Mumbai, Maharashtra - 400001
  - Ahmedabad: Office No. 611-612, Ratnanjali Solitaire, Satellite, Ahmedabad - 380015
- NAP (Name, Address, Phone) consistency across:
  - Website (all pages) ✅ done
  - Google Business Profile
  - IndiaMART, TradeIndia, JustDial, Sulekha listings
- Add website link, business hours, product categories to GBP

### 4.7 Footer Content Enhancement
**Priority: MEDIUM — Sitewide internal links**

- Footer is database-driven (`$siteSettingInfo["siteFooterInfo"]`)
- Should contain structured sections:
  - **Quick Links:** Home, About, Motors, Pumps, Knowledge Center, Contact
  - **Our Brands:** CG Power, Crompton, Kirloskar (linking to filtered product pages)
  - **Locations:** Mumbai Office, Ahmedabad Office (linking to landing pages)
  - **Contact Info:** Both office addresses, phone numbers, email
  - **Social Links:** If social profiles exist
- This provides sitewide internal link juice to key pages

### 4.8 Image Alt Tag Audit & WebP Conversion
**Priority: MEDIUM — Image SEO + performance**

- Audit ALL image alt tags across the site, especially:
  - Product images on detail pages (likely from database)
  - Homepage slider images
  - 404 page image (currently missing)
- Convert key images to WebP format with JPEG/PNG fallbacks
- Add `srcset` for responsive images on product pages
- Add image sitemap entries for top product images

### 4.9 Content Enrichment for Landing Pages
**Priority: LOW — Long-tail keyword targeting**

- Add "Industries We Serve" section to each landing page:
  - Pharmaceuticals, Oil & Gas, Textiles, Construction, Water Treatment, etc.
  - This captures industry-specific search queries
- Add customer testimonial snippets (even 2-3 per page)
- Add "Certifications & Standards" section (BIS, IS standards, etc.)

### 4.10 Structured Data Testing & Monitoring
**Priority: LOW — Ongoing maintenance**

- Test all structured data with Google Rich Results Test after implementation
- Set up Google Search Console monitoring for:
  - Index coverage issues
  - Rich results eligibility
  - Core Web Vitals scores
  - Mobile usability issues
- Monitor landing page indexing status specifically

---

## Implementation Priority Matrix

| Priority | Task | Impact | Effort |
|----------|------|--------|--------|
| P0 | 3.1 Add landing pages to sitemap | High | Low |
| P0 | 3.2 Fix render-blocking jQuery | High | Low |
| P1 | 3.3 Image lazy loading | High | Medium |
| P1 | 4.1 Homepage FAQ schema | High | Low |
| P1 | 4.2 Knowledge center Article schema | High | Medium |
| P1 | 4.3 Internal linking strategy | High | Medium |
| P2 | 3.4 Image dimensions (CLS) | Medium | Medium |
| P2 | 3.5 Fix 404 page | Medium | Low |
| P2 | 4.4 LocalBusiness schema enhancement | Medium | Low |
| P2 | 4.5 Product schema enhancement | Medium | Medium |
| P2 | 4.6 Google Business Profile sync | Medium | Low (manual) |
| P2 | 4.7 Footer content enhancement | Medium | Low (DB update) |
| P2 | 4.8 Image alt audit + WebP | Medium | High |
| P3 | 3.6 Homepage heading fix | Low | Low |
| P3 | 3.7 Create llms.txt | Low | Low |
| P3 | 4.9 Landing page content enrichment | Low | Medium |
| P3 | 4.10 Structured data monitoring | Low | Low |

---

## Competitor Landscape (for context)

Top organic results for "industrial motors supplier mumbai" are dominated by:
1. **IndiaMART** — directory listings
2. **TradeIndia** — directory listings
3. **JustDial / Sulekha** — local directories
4. **VEMC (vemc.co.in)** — direct competitor, strong Mumbai presence since 1948
5. **Indian Electric Co (indianelectric.com)** — authorized Siemens/CG Power dealer
6. **Navbharat Motor** — manufacturer in Andheri

**Key takeaway:** To compete, Bombay Engineering needs:
- Strong local SEO (Google Business Profile + NAP consistency + local schema)
- Product-rich pages with technical specs (already good)
- Directory presence (IndiaMART, TradeIndia, JustDial, Sulekha)
- Trust signals (67+ years, authorized dealer badges, ISO if applicable)

---

## Files Affected

### Phase 3
| File | Changes |
|------|---------|
| `xsite/sitemap.xml` | Add 4 landing page URLs, update lastmod dates |
| `sitemap_index.xml` | Update lastmod date |
| `xsite/mod/header.php` | Add `defer` to jQuery, fix heading nesting |
| `xsite/mod/home/x-home.php` | Lazy loading, image dimensions, heading fix |
| `xsite/mod/footer.php` | Lazy loading on decorative images |
| `xsite/inc/x-404.php` | Alt text, nav links, noindex |
| `xsite/llms.txt` | Create new file |
| All 4 landing page templates | Lazy loading on brand logos |

### Phase 4
| File | Changes |
|------|---------|
| `xsite/mod/home/x-home.php` | FAQPage schema JSON-LD |
| `xsite/mod/knowledge-center/*.php` | Article schema JSON-LD |
| Motor/pump detail templates | Related products section, knowledge links |
| All 4 landing pages | Interlinking sections |
| `xsite/mod/header.php` | Enhanced LocalBusiness schema |
| Pump/motor detail schema | Enhanced Product schema |
| Database: footer content | Structured footer with links |
| Database: navigation menu | Add landing pages to menu |

---

## Notes
- Phase 3 is purely technical — no content changes, minimal risk
- Phase 4 involves content and structural changes — review each before implementing
- Google Business Profile changes (4.6) are manual, not code changes
- Directory listings (IndiaMART etc.) are external — separate effort
- All changes should be backed up before implementation (rollback script exists)
