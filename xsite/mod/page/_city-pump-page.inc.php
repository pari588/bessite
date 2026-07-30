<?php
/**
 * Shared renderer for Tier-1 city PUMP landing pages.
 * Each city template defines $cfg (unique local content) and calls renderCityPumpPage($cfg).
 * Uses shared /css/landing.css. Design: Trust & Authority. No pricing (Call-for-Price).
 */
if (!function_exists('renderCityPumpPage')) {
    function renderCityPumpPage($cfg)
    {
        // Decode-then-escape so config values may contain "&" or "&amp;" without double-encoding.
        $e = function ($s) { return htmlspecialchars(html_entity_decode($s, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8'); };
        $pageUrl = SITEURL . '/' . $cfg['seoUri'] . '/';
        ?>
<link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/landing.css'); ?>" />
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": <?php echo json_encode('Pumps in ' . $cfg['city'] . ' - Crompton & Kirloskar Pump Dealer & Supplier', JSON_UNESCAPED_UNICODE); ?>,
    "url": "<?php echo $pageUrl; ?>",
    "inLanguage": "en-IN",
    "isPartOf": { "@type": "WebSite", "name": "Bombay Engineering Syndicate", "url": "<?php echo SITEURL; ?>/" },
    "about": { "@type": "Organization", "name": "Bombay Engineering Syndicate", "@id": "<?php echo SITEURL; ?>/#organization" }
}
</script>

<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);"></div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li>Pumps in <?php echo $e($cfg['city']); ?></li>
            </ul>
            <h2>Pumps in <?php echo $e($cfg['city']); ?></h2>
        </div>
    </div>
</section>
<style>.page-header .page-header__inner h1, .page-header .page-header__inner h2 { color:#fff !important; }</style>

<section class="lp-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="lp-hero__content">
                    <span class="lp-hero__tagline">Authorised Crompton &amp; Kirloskar Pump Dealer Since 1957</span>
                    <h1 class="lp-hero__title"><?php echo $e($cfg['h1']); ?></h1>
                    <p class="lp-hero__text"><?php echo $cfg['intro']; ?></p>
                    <div class="lp-hero__features">
                        <?php foreach ($cfg['features'] as $f) { ?>
                        <div class="lp-feature"><i class="fas fa-check-circle"></i><span><?php echo $f; ?></span></div>
                        <?php } ?>
                    </div>
                    <div class="lp-hero__cta">
                        <a href="tel:+919324706905" class="thm-btn lp-btn"><i class="fas fa-phone-alt"></i> Call: +91 93247 06905</a>
                        <a href="#city-inquiry" class="thm-btn lp-btn lp-btn--outline">Get a Quote</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="lp-hero__card">
                    <div class="lp-card__header"><i class="fas fa-truck"></i><h3><?php echo $e($cfg['serve_title']); ?></h3></div>
                    <div class="lp-card__body">
                        <p><strong>Bombay Engineering Syndicate</strong></p>
                        <p>Supplied &amp; dispatched from our Mumbai (Fort) head office to <?php echo $e($cfg['serve_areas']); ?>.</p>
                        <p><i class="fas fa-phone-alt"></i> <a href="tel:+919324706905">+91 93247 06905</a></p>
                        <p><i class="fas fa-envelope"></i> <a href="mailto:besyndicate@gmail.com">besyndicate@gmail.com</a></p>
                        <p><i class="fas fa-clock"></i> Mon - Sat: 10:00 AM - 6:00 PM</p>
                    </div>
                    <div class="lp-card__map">
                        <iframe src="https://www.google.com/maps?q=<?php echo urlencode($cfg['map_q']); ?>&z=<?php echo $e($cfg['map_z']); ?>&output=embed" width="100%" height="200" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Pump supply coverage across <?php echo $e($cfg['city']); ?>"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lp-products">
    <div class="container">
        <div class="section-title text-center">
            <h2 class="section-title__title">Pumps We Supply in <?php echo $e($cfg['city']); ?></h2>
            <p class="section-title__text"><?php echo $e($cfg['products_sub']); ?></p>
        </div>
        <div class="row">
            <?php foreach ($cfg['products'] as $p) { ?>
            <div class="col-lg-4 col-md-6"><div class="lp-product-card"><div class="lp-product-card__icon"><i class="fas <?php echo $e($p['icon']); ?>"></i></div><h3><?php echo $e($p['title']); ?></h3><p><?php echo $p['desc']; ?></p><ul><?php foreach ($p['bullets'] as $b) { echo '<li>' . $e($b) . '</li>'; } ?></ul></div></div>
            <?php } ?>
        </div>
    </div>
</section>

<section class="lp-why-us">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2>Why <?php echo $e($cfg['city']); ?> Trusts Bombay Engineering for Pumps</h2>
                <div class="lp-why-list">
                    <?php foreach ($cfg['why'] as $w) { ?>
                    <div class="lp-why-item"><div class="lp-why-item__number"><?php echo $e($w['num']); ?></div><div class="lp-why-item__content"><h4><?php echo $e($w['title']); ?></h4><p><?php echo $w['desc']; ?></p></div></div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="lp-stats">
                    <div class="lp-stat-card"><div class="lp-stat__number">67+</div><div class="lp-stat__label">Years in Business</div></div>
                    <div class="lp-stat-card"><div class="lp-stat__number">10000+</div><div class="lp-stat__label">Pumps Supplied</div></div>
                    <div class="lp-stat-card"><div class="lp-stat__number">89+</div><div class="lp-stat__label">Pump Models</div></div>
                    <div class="lp-stat-card"><div class="lp-stat__number">2</div><div class="lp-stat__label">Brands: Crompton &amp; Kirloskar</div></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lp-coverage">
    <div class="container">
        <div class="section-title text-center">
            <h2 class="section-title__title"><?php echo $e($cfg['coverage_title']); ?></h2>
            <p class="section-title__text">We deliver Crompton &amp; Kirloskar pumps, spares and support across the region.</p>
        </div>
        <p class="lp-coverage__intro"><?php echo $cfg['coverage_intro']; ?></p>
        <div class="lp-coverage__grid">
            <?php foreach ($cfg['coverage'] as $c) { ?>
            <div class="lp-coverage__region"><h3><?php echo $e($c['title']); ?></h3><p class="lp-coverage__lead"><?php echo $c['lead']; ?></p><p><?php echo $c['desc']; ?></p></div>
            <?php } ?>
        </div>
    </div>
</section>

<section class="lp-cta" id="city-inquiry">
    <div class="container">
        <div class="lp-cta__inner">
            <h2 class="lp-cta__title">Request a Pump Quote for <?php echo $e($cfg['city']); ?></h2>
            <p class="lp-cta__text">Tell us your requirement &mdash; head, flow, application and power supply &mdash; and our team will recommend the right Crompton or Kirloskar pump and share a quotation.</p>
            <div class="lp-cta__actions">
                <a href="tel:+919324706905" class="thm-btn lp-btn lp-btn--large"><i class="fas fa-phone-alt"></i> Call: +91 93247 06905</a>
                <a href="<?php echo SITEURL; ?>/contact-us/" class="thm-btn lp-btn lp-btn--outline lp-btn--large">Contact Us</a>
            </div>
            <p class="lp-cta__note"><i class="fas fa-truck"></i> <?php echo $e($cfg['cta_note']); ?></p>
        </div>
    </div>
</section>
        <?php
    }
}
