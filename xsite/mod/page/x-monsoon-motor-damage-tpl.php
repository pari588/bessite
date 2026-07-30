<?php
/**
 * Landing Page: Monsoon-damaged motors → energy-efficient replacement
 *
 * Campaign page. Deliberately BRAND-FREE (no manufacturer named) and PRICE-FREE, per
 * site policy. Single call-to-action: phone 98200 42210.
 *
 * Framing: the customer's default reaction to a flooded motor is to rewind it. The page
 * makes the commercial case for replacing with a high-efficiency motor instead, and leads
 * with genuinely useful safety guidance so it reads as help rather than a pitch.
 */
/**
 * FULL VERSION IS LIVE.
 * Controls the detailed sections: safety notice, damage symptoms, the rewind-vs-replace
 * comparison and the motor specification grid. Set to false to fall back to the short
 * version (hero + closing CTA only) — the content stays in the file either way.
 */
$showFullContent = true;

$besPhone     = '+919820042210';
$besPhoneDisp = '98200 42210';
?>
<!-- Shared landing-page styles: supplies the .lp-* section padding. Without this the
     sections render with padding:0 and every heading butts into the text below it. -->
<link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/landing.css'); ?>" />
<style>
.page-header .page-header__inner h1 { color: #ffffff !important; }

/* ── Heading rhythm.
   The theme sets .section-title__title to 44px with margin-bottom:0, so headings sat
   directly on their sub-text. Bring them to the size used elsewhere on the landing
   pages and give every heading real breathing room. ── */
.lp-hero .lp-hero__title{font-size:31px;line-height:1.32;color:#1a1a2e;margin:0 0 16px}
.mm-lead{margin:0 0 16px}
.lp-hero .lp-hero__text{margin:0 0 26px}
.lp-hero .lp-hero__tagline{margin-bottom:18px}
.section-title{margin-bottom:34px}
.section-title__title{font-size:30px!important;line-height:1.34;color:#1a1a2e;margin:0 0 12px!important}
.section-title__text{font-size:16px;line-height:1.7;color:#5b6875;margin:0 auto;max-width:760px}
.lp-hero__features{margin-bottom:26px}
.lp-feature{margin-bottom:11px}

/* ── page-specific styling, built on the shared landing-page vocabulary ── */
.mm-alert{background:#fff8ec;border:1px solid #f5dcae;border-left-color:#d9880e;border-radius:10px;padding:20px 24px;margin:0 0 26px}
.mm-alert h3{font-size:17px;color:#8a5a00;margin:0 0 8px}
.mm-alert p{font-size:15px;line-height:1.75;color:#6b5228;margin:0}
.mm-sign-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:8px}
.mm-sign{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid #e8eef4;border-radius:10px;padding:16px 18px}
.mm-sign__mark{flex:0 0 auto;width:26px;height:26px;border-radius:50%;background:#fdecec;color:#c0392b;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700}
.mm-sign p{margin:0;font-size:14.5px;line-height:1.65;color:#54626f}
.mm-sign strong{display:block;color:#1d2b39;font-size:15px;margin-bottom:2px}
.mm-compare{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;margin-top:10px}
.mm-col{border-radius:12px;padding:24px 26px;border:1px solid #e6ecf2;background:#fff}
.mm-col--bad{background:#fdf7f7;border-color:#f0dede}
.mm-col--good{background:#f2faf5;border-color:#cfe9db}
.mm-col h3{font-size:18px;margin:0 0 14px;color:#1d2b39}
.mm-col ul{list-style:none;padding:0;margin:0}
.mm-col li{position:relative;padding-left:26px;margin-bottom:11px;font-size:15px;line-height:1.7;color:#54626f}
.mm-col li::before{position:absolute;left:0;top:0;font-weight:700}
.mm-col--bad li::before{content:'\2715';color:#c0392b}
.mm-col--good li::before{content:'\2713';color:#1c7a3e}
.mm-spec{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px;margin-top:10px}
.mm-spec__item{background:#fff;border:1px solid #e8eef4;border-radius:10px;padding:20px 22px}
.mm-spec__item h4{font-size:15.5px;color:#157bba;margin:0 0 7px}
.mm-spec__item p{font-size:14.5px;line-height:1.7;color:#54626f;margin:0}
.mm-callbar{background:linear-gradient(135deg,#0f5a8f 0%,#157bba 100%);border-radius:14px;padding:34px 30px;text-align:center;color:#fff;margin:8px 0 0}
.mm-callbar h2{color:#fff;font-size:26px;margin:0 0 10px;line-height:1.35}
.mm-callbar p{color:#dceefb;font-size:15.5px;line-height:1.7;margin:0 0 20px}
.mm-callbtn{display:inline-block;background:#fff;color:#0f5a8f!important;font-size:21px;font-weight:700;padding:15px 34px;border-radius:8px;text-decoration:none;letter-spacing:.4px}
.mm-callbtn:hover{background:#eaf4fb;color:#0b4870!important}
.mm-callnote{display:block;margin-top:13px;font-size:13.5px;color:#cfe6f7}
.mm-lead{font-size:17.5px;line-height:1.75;color:#283746;font-weight:500}
.mm-alert{margin:0 0 40px}
.mm-sign-grid{margin-top:0}
.mm-compare{margin-top:0}
.mm-spec{margin-top:0}
.lp-cta .mm-callbar{margin:0}
@media(max-width:767px){
  .mm-callbar{padding:26px 18px}.mm-callbar h2{font-size:21px}
  .mm-callbtn{font-size:19px;padding:14px 24px;display:block}
  .mm-col,.mm-alert{padding:18px 18px}
  .col-lg-5 .mm-callbar{margin-top:28px}
  .section-title{margin-bottom:26px}
  .section-title__title{font-size:25px!important}
  .lp-hero .lp-hero__title{font-size:25px}
}
</style>

<!--Page Header Start-->
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);"></div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li>Monsoon Motor Damage</li>
            </ul>
            <h1>Motor Damaged in the Monsoon?</h1>
        </div>
    </div>
</section>
<!--Page Header End-->

<!-- HERO -->
<section class="lp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="lp-hero__content">
                    <span class="lp-hero__tagline">Monsoon Support &mdash; Mumbai &amp; Ahmedabad</span>
                    <h2 class="lp-hero__title">Flooded, tripping or burnt-out motors &mdash; replaced with energy-efficient ones</h2>
                    <p class="mm-lead">Every monsoon, water gets into motors in basements, godowns, pump rooms and ground-floor plants. Windings short, insulation gives way, bearings rust &mdash; and production stops.</p>
                    <p class="lp-hero__text">If a motor has failed after water ingress or a monsoon power surge, talk to us before you send it for rewinding. We supply <strong>energy-efficient replacement motors from ready stock</strong>, sized to your existing installation, so you are running again quickly &mdash; and paying less to run it.</p>

                    <div class="lp-hero__features">
                        <div class="lp-feature"><i class="fas fa-check-circle"></i><span>Ready stock &mdash; fast replacement during breakdown</span></div>
                        <div class="lp-feature"><i class="fas fa-check-circle"></i><span>High-efficiency IE3 &amp; IE4 motors, lower running cost</span></div>
                        <div class="lp-feature"><i class="fas fa-check-circle"></i><span>Correct frame, mounting &amp; rating &mdash; drop-in fit</span></div>
                        <div class="lp-feature"><i class="fas fa-check-circle"></i><span>Free sizing advice from engineers, since 1957</span></div>
                    </div>

                    <div class="lp-hero__cta">
                        <a href="tel:<?php echo $besPhone; ?>" class="thm-btn lp-btn"><i class="fas fa-phone-alt"></i> Call <?php echo $besPhoneDisp; ?></a>
                        <a href="<?php echo SITEURL; ?>/product-inquiry/" class="thm-btn lp-btn lp-btn--outline">Send Motor Details</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="mm-callbar">
                    <h2>Motor down right now?</h2>
                    <p>Tell us the HP/kW, frame and mounting &mdash; we will confirm availability straight away.</p>
                    <a href="tel:<?php echo $besPhone; ?>" class="mm-callbtn"><i class="fas fa-phone-alt"></i> <?php echo $besPhoneDisp; ?></a>
                    <span class="mm-callnote">Mon&ndash;Sat, 10:00 AM &ndash; 6:00 PM</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SAFETY FIRST + DAMAGE SIGNS  (hidden while $showFullContent is false) -->
<?php if ($showFullContent): ?>
<section class="lp-products" style="padding-top:38px;">
    <div class="container">
        <div class="mm-alert">
            <h3><i class="fas fa-exclamation-triangle"></i> Before you switch it back on</h3>
            <p>Do not restart a motor that has been standing in water. Powering up damp windings is the most common way a repairable motor becomes a scrap motor &mdash; and it is a genuine shock and fire risk. Isolate the supply, let it dry, and have the insulation resistance checked with a megger before the motor is energised again.</p>
        </div>

        <div class="section-title text-center">
            <h2 class="section-title__title">Signs the monsoon has damaged your motor</h2>
            <p class="section-title__text">Common failures we are called out for between June and September</p>
        </div>

        <div class="mm-sign-grid">
            <div class="mm-sign"><div class="mm-sign__mark">!</div><p><strong>Trips the moment it starts</strong>Moisture has bridged the winding insulation and the protection is doing its job. Repeated resetting will burn it out.</p></div>
            <div class="mm-sign"><div class="mm-sign__mark">!</div><p><strong>Hums but will not turn</strong>Often a seized bearing after water ingress, or a lost phase. Leaving it humming overheats the winding within minutes.</p></div>
            <div class="mm-sign"><div class="mm-sign__mark">!</div><p><strong>Burning smell or scorched varnish</strong>The winding insulation has already broken down. This motor will not survive another season without attention.</p></div>
            <div class="mm-sign"><div class="mm-sign__mark">!</div><p><strong>Low insulation resistance</strong>A megger reading below roughly 1 M&#8486; means the windings are damp or contaminated and must not be energised.</p></div>
            <div class="mm-sign"><div class="mm-sign__mark">!</div><p><strong>Rust streaks or a water line on the body</strong>Clear evidence of submersion. Bearings and shaft will have corroded even if it still runs.</p></div>
            <div class="mm-sign"><div class="mm-sign__mark">!</div><p><strong>Running hotter or noisier than before</strong>Typical after a rewind or a soaking &mdash; efficiency has dropped and the motor is drawing more current for the same work.</p></div>
        </div>
    </div>
</section>

<!-- REWIND vs REPLACE -->
<section class="lp-why-us">
    <div class="container">
        <div class="section-title text-center">
            <h2 class="section-title__title">Rewind it again, or replace it properly?</h2>
            <p class="section-title__text">Rewinding looks cheaper on the day. Over a season of running, it usually is not.</p>
        </div>

        <div class="mm-compare">
            <div class="mm-col mm-col--bad">
                <h3>Rewinding a water-damaged motor</h3>
                <ul>
                    <li>Efficiency typically drops with each rewind &mdash; the motor quietly costs more to run for the rest of its life</li>
                    <li>The original insulation system and fits are never fully restored</li>
                    <li>Corroded bearings and shaft usually remain the weak point</li>
                    <li>Days of downtime while the motor is away at the winder</li>
                    <li>Higher chance of a repeat failure in the same season</li>
                </ul>
            </div>
            <div class="mm-col mm-col--good">
                <h3>Replacing with a high-efficiency motor</h3>
                <ul>
                    <li>Full rated efficiency from day one, with IE3 or IE4 performance</li>
                    <li>Lower power draw for the same output &mdash; the saving repeats every month</li>
                    <li>Fresh insulation, bearings and seals suited to damp conditions</li>
                    <li>Back in production quickly from ready stock</li>
                    <li>Full manufacturer warranty and service support</li>
                </ul>
            </div>
        </div>
        <p style="text-align:center;font-size:15px;color:#54626f;margin:22px auto 0;max-width:820px;line-height:1.75;">On a motor that runs long hours, the electricity it consumes dwarfs what it cost to buy. That is why moving up an efficiency class often pays for itself well before the next monsoon &mdash; we are happy to work the numbers through for your actual running hours and tariff.</p>
    </div>
</section>

<!-- WHAT WE SUPPLY -->
<section class="lp-products">
    <div class="container">
        <div class="section-title text-center">
            <h2 class="section-title__title">The replacement motors we supply</h2>
            <p class="section-title__text">Specified for Indian conditions &mdash; humidity, voltage swings and continuous duty</p>
        </div>

        <div class="mm-spec">
            <div class="mm-spec__item"><h4>IE3 &amp; IE4 efficiency</h4><p>Premium and super-premium efficiency classes that cut running cost on continuously operated drives and meet current energy norms.</p></div>
            <div class="mm-spec__item"><h4>IP55 to IP66 protection</h4><p>Sealed against dust and water jets, with higher ingress protection available for wash-down areas and exposed installations.</p></div>
            <div class="mm-spec__item"><h4>Class F insulation</h4><p>Class F insulation worked at Class B temperature rise, giving thermal headroom and longer winding life in hot, humid plants.</p></div>
            <div class="mm-spec__item"><h4>TEFC construction</h4><p>Totally enclosed fan-cooled, in cast iron or aluminium bodies, for the dusty and damp conditions typical of Indian shop floors.</p></div>
            <div class="mm-spec__item"><h4>0.37 kW to 630 kW</h4><p>Fractional through to large industrial ratings, in 2, 4, 6 and 8-pole speeds with foot, flange and combined mountings.</p></div>
            <div class="mm-spec__item"><h4>Flameproof options</h4><p>Certified hazardous-area motors for chemical, pharma and paint plants where an ordinary replacement is not permitted.</p></div>
        </div>
    </div>
</section>

<?php endif; /* $showFullContent */ ?>

<!-- CTA -->
<section class="lp-cta">
    <div class="container">
        <div class="mm-callbar">
            <h2>Send us the nameplate &mdash; we will do the rest</h2>
            <p>A photograph of the motor nameplate is usually all we need to identify the correct replacement: rating, frame, mounting and speed. Our engineers will confirm the right motor and its availability, with no obligation.</p>
            <a href="tel:<?php echo $besPhone; ?>" class="mm-callbtn"><i class="fas fa-phone-alt"></i> Call <?php echo $besPhoneDisp; ?></a>
            <span class="mm-callnote">Bombay Engineering Syndicate &mdash; supplying Indian industry since 1957 &middot; Mumbai &amp; Ahmedabad &middot; pan-India dispatch</span>
        </div>
    </div>
</section>
