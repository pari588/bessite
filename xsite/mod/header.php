<?php
// ────────────────────────────────────────────────────────────────────────────────
// Security headers – insert *before* any HTML output
// ────────────────────────────────────────────────────────────────────────────────

// 1. Enforce HTTPS for 1 year (including subdomains)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains", false);

// 2. Prevent MIME sniffing
header("X-Content-Type-Options: nosniff", false);

// 3. Control referrer information
header("Referrer-Policy: strict-origin-when-cross-origin", false);

// 4. Lock down powerful browser features
header("Permissions-Policy: geolocation=(), microphone=(), camera=()", false);

// 5. Prevent clickjacking
header("X-Frame-Options: DENY", false);

// 6. Comprehensive Content Security Policy
header(
    "Content-Security-Policy: "
  . "default-src 'self'; "
  . "script-src 'self' 'unsafe-inline' https://www.bombayengg.net https://www.google.com https://www.gstatic.com https://www.googletagmanager.com; "
  . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
  . "img-src 'self' data: https://www.google-analytics.com https://www.googletagmanager.com https://maps.gstatic.com https://maps.googleapis.com; "
  . "font-src 'self' https://fonts.gstatic.com data:; "
  . "connect-src 'self' https://www.google.com https://www.google-analytics.com https://www.googletagmanager.com; "
  . "frame-src https://www.google.com https://www.google.com/maps/; "
  . "object-src 'none'; "
  . "frame-ancestors 'none'; "
  . "base-uri 'self'; "
  . "form-action 'self';",
    false
);

// 7. Content Language for SEO
header("Content-Language: en-IN", false);

// ────────────────────────────────────────────────────────────────────────────────
// End of security headers
// ────────────────────────────────────────────────────────────────────────────────

// To fetch header user info data.
$siteSettingInfo = getSiteInfo();
// End.
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-IN">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-W1JJNG8VRL"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-W1JJNG8VRL');
    </script>

    <!-- Character Set & Viewport -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Primary Meta Tags (dynamic from DB, fallback to defaults) -->
    <?php echo mxGetMeta(); ?>
    <?php if (empty($TPL->metaTitle)): ?>
    <title>Industrial Motors & Pumps | Bombay Engineering Syndicate</title>
    <?php endif; ?>
    <?php if (empty($TPL->metaDesc)): ?>
    <meta name="description" content="Leading industrial motors & submersible pumps supplier in Mumbai & Ahmedabad. Authorised CG Power motor dealer & Crompton pump dealer. Energy-efficient motors, water pumps. Trusted since 1957." />
    <?php endif; ?>
    <?php if (empty($TPL->metaKeyword)): ?>
    <meta name="keywords" content="industrial motors, submersible pumps, water pumps, energy-efficient motors, motor supplier, pump dealer, Mumbai, Ahmedabad, Fort, Kala Ghoda, Crompton, CG Power, Kirloskar, AC motors, induction motors, electric pumps, booster pumps, borewell pumps" />
    <?php endif; ?>

    <!-- Language & Content -->
    <meta name="language" content="en-IN" />
    <meta name="revisit-after" content="7" />
    <meta name="author" content="Bombay Engineering Syndicate" />
    <meta name="copyright" content="© <?php echo date('Y'); ?> Bombay Engineering Syndicate. All rights reserved." />

    <!-- Local Business Specific -->
    <meta name="geo.position" content="18.9333;72.8333" />
    <meta name="ICBM" content="18.9333, 72.8333" />
    <meta name="geo.placename" content="Mumbai, Maharashtra, India" />
    <meta name="geo.region" content="IN-MH" />
    <meta name="city" content="Mumbai, Ahmedabad" />
    <meta name="state" content="Maharashtra, Gujarat" />
    <meta name="country" content="India" />

    <!-- Search Engine Robots -->
    <?php
    // Paginated pages (with offset/showRec) should not be indexed — canonical handles consolidation
    $isPaginated = isset($_GET['offset']) || isset($_GET['showRec']);
    $is404 = ($TPL->pageType === '404');
    if ($isPaginated || $is404):
    ?>
    <meta name="robots" content="noindex, follow" />
    <?php else: ?>
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
    <meta name="googlebot" content="index, follow" />
    <meta name="googlebot-news" content="index, follow" />
    <meta name="bingbot" content="index, follow" />
    <?php endif; ?>

    <!-- Format Detection & Canonical -->
    <meta name="format-detection" content="telephone=yes" />
    <?php
    // Build current page URL for canonical and hreflang (without query strings for cleaner SEO)
    $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    // Default to "/" for homepage when REQUEST_URI is missing/empty after rewrite
    if (empty($currentPath)) {
        $currentPath = '/';
    }
    $currentPageUrl = rtrim(SITEURL, '/') . $currentPath;
    ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($currentPageUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <link rel="alternate" hreflang="en-IN" href="<?php echo htmlspecialchars($currentPageUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($currentPageUrl, ENT_QUOTES, 'UTF-8'); ?>" />

    <script src="<?php echo mxGetUrl(SITEURL . '/' . LIBDIR . '/js/jquery-3.3.1.min.js'); ?>"></script>
    <script defer src="<?php echo mxGetUrl(COREURL . '/config.js.php', getJsVars()); ?>"></script>
    <script defer src="<?php echo mxGetUrl(COREURL . '/js/common.inc.js'); ?>"></script>
    <script defer src="<?php echo mxGetUrl(COREURL . '/js/dialog.inc.js'); ?>"></script>
    <script defer src="<?php echo mxGetUrl(COREURL . '/js/validate.inc.js'); ?>"></script>
    <script defer src="<?php echo mxGetUrl(COREURL . '/js/form.inc.js'); ?>"></script>

    <!-- Open Graph Tags - Dynamic for Pump Pages, Static for Others -->
    <meta property="og:title" content="<?php echo defined('WHATSAPP_OG_TITLE') ? htmlspecialchars(WHATSAPP_OG_TITLE, ENT_QUOTES, 'UTF-8') : 'Motors & Pumps Supplier in Mumbai'; ?>" />
    <meta property="og:description" content="<?php echo defined('WHATSAPP_OG_DESCRIPTION') ? htmlspecialchars(WHATSAPP_OG_DESCRIPTION, ENT_QUOTES, 'UTF-8') : 'Energy-efficient motors, submersible pumps & industrial solutions. Trusted supplier since 1957. Locations: Mumbai & Ahmedabad. Free Enquiry Form.'; ?>" />
    <meta property="og:url" content="<?php echo htmlspecialchars($currentPageUrl, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="og:image" content="<?php echo defined('WHATSAPP_OG_IMAGE') ? WHATSAPP_OG_IMAGE : SITEURL . '/images/motors.jpeg'; ?>" />
    <meta property="og:image:secure_url" content="<?php echo defined('WHATSAPP_OG_IMAGE') ? WHATSAPP_OG_IMAGE : SITEURL . '/images/motors.jpeg'; ?>" />
    <meta property="og:image:width" content="<?php echo defined('WHATSAPP_OG_TYPE') && WHATSAPP_OG_TYPE === 'product' ? '530' : '1200'; ?>" />
    <meta property="og:image:height" content="<?php echo defined('WHATSAPP_OG_TYPE') && WHATSAPP_OG_TYPE === 'product' ? '530' : '630'; ?>" />
    <meta property="og:image:type" content="image/webp" />
    <meta property="og:image:alt" content="<?php echo defined('WHATSAPP_OG_TITLE') ? htmlspecialchars(WHATSAPP_OG_TITLE, ENT_QUOTES, 'UTF-8') : 'Bombay Engineering Syndicate - Industrial Motors and Pumps'; ?>" />
    <meta property="og:type" content="<?php echo defined('WHATSAPP_OG_TYPE') ? WHATSAPP_OG_TYPE : 'website'; ?>" />
    <meta property="og:locale" content="en_IN" />
    <meta property="og:site_name" content="Bombay Engineering Syndicate" />
    <meta property="og:logo" content="<?php echo UPLOADURL; ?>/setting/logo-m.png" />

    <!-- Twitter Card Tags - Dynamic for Pump Pages, Static for Others -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo defined('WHATSAPP_OG_TITLE') ? htmlspecialchars(WHATSAPP_OG_TITLE, ENT_QUOTES, 'UTF-8') : 'Motors & Pumps Supplier'; ?>" />
    <meta name="twitter:description" content="<?php echo defined('WHATSAPP_OG_DESCRIPTION') ? htmlspecialchars(WHATSAPP_OG_DESCRIPTION, ENT_QUOTES, 'UTF-8') : 'Leading supplier of energy-efficient industrial motors and pumps. Serving Mumbai & Ahmedabad since 1957.'; ?>" />
    <meta name="twitter:image" content="<?php echo defined('WHATSAPP_OG_IMAGE') ? WHATSAPP_OG_IMAGE : SITEURL . '/images/motors.jpeg'; ?>" />
    <meta name="twitter:creator" content="@BombayEngg" />

    <!-- Organization Schema with Location Departments -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Bombay Engineering Syndicate",
            "@id": "<?php echo SITEURL; ?>/#organization",
            "alternateName": "BES",
            "url": "<?php echo SITEURL; ?>",
            "logo": "<?php echo SITEURL; ?>/images/logo.png",
            "image": "<?php echo SITEURL; ?>/images/motors.jpeg",
            "foundingDate": "1957",
            "description": "Leading supplier of energy-efficient industrial motors, submersible pumps, and engineering solutions. Serving Mumbai and Ahmedabad since 1957.",
            "email": "besyndicate@gmail.com",
            "department": [
                {
                    "@type": "ElectricalStore",
                    "name": "Bombay Engineering Syndicate - Mumbai",
                    "@id": "<?php echo SITEURL; ?>/mumbai/#organization",
                    "url": "<?php echo SITEURL; ?>/mumbai/",
                    "telephone": "+919324706905",
                    "email": "besyndicate@gmail.com",
                    "address": {
                        "@type": "PostalAddress",
                        "streetAddress": "2nd Floor, Modern House, Dr. V.B. Gandhi Marg, Kala Ghoda, Fort",
                        "addressLocality": "Mumbai",
                        "addressRegion": "Maharashtra",
                        "postalCode": "400001",
                        "addressCountry": "IN"
                    },
                    "geo": {
                        "@type": "GeoCoordinates",
                        "latitude": "18.9275",
                        "longitude": "72.8334"
                    },
                    "openingHoursSpecification": [
                        {
                            "@type": "OpeningHoursSpecification",
                            "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
                            "opens": "10:00",
                            "closes": "18:00"
                        }
                    ],
                    "priceRange": "$$",
                    "areaServed": [
                        {"@type": "State", "name": "Maharashtra"},
                        {"@type": "City", "name": "Mumbai"},
                        {"@type": "City", "name": "Thane"},
                        {"@type": "City", "name": "Navi Mumbai"},
                        {"@type": "City", "name": "Pune"},
                        {"@type": "City", "name": "Nashik"},
                        {"@type": "City", "name": "Nagpur"},
                        {"@type": "City", "name": "Vasai-Virar"},
                        {"@type": "Place", "name": "Andheri"},
                        {"@type": "Place", "name": "Bandra"},
                        {"@type": "Place", "name": "Borivali"},
                        {"@type": "Place", "name": "Powai"},
                        {"@type": "Place", "name": "Worli"},
                        {"@type": "Place", "name": "Lower Parel"},
                        {"@type": "Place", "name": "Kurla"}
                    ]
                },
                {
                    "@type": "ElectricalStore",
                    "name": "Bombay Engineering Syndicate - Ahmedabad",
                    "@id": "<?php echo SITEURL; ?>/ahmedabad/#organization",
                    "url": "<?php echo SITEURL; ?>/ahmedabad/",
                    "telephone": "+919825014977",
                    "email": "besahmedabad@gmail.com",
                    "address": {
                        "@type": "PostalAddress",
                        "streetAddress": "Office No. 611, 612, Ratnanjali Solitaire, Near Sachet - 4, Prerna Tirth Derasar Road, Jodhpurgam, Satellite",
                        "addressLocality": "Ahmedabad",
                        "addressRegion": "Gujarat",
                        "postalCode": "380015",
                        "addressCountry": "IN"
                    },
                    "geo": {
                        "@type": "GeoCoordinates",
                        "latitude": "23.0309",
                        "longitude": "72.5277"
                    },
                    "openingHoursSpecification": [
                        {
                            "@type": "OpeningHoursSpecification",
                            "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],
                            "opens": "10:00",
                            "closes": "18:00"
                        }
                    ],
                    "priceRange": "$$",
                    "areaServed": [
                        {"@type": "State", "name": "Gujarat"},
                        {"@type": "City", "name": "Ahmedabad"},
                        {"@type": "City", "name": "Gandhinagar"},
                        {"@type": "City", "name": "Surat"},
                        {"@type": "City", "name": "Vadodara"},
                        {"@type": "City", "name": "Rajkot"},
                        {"@type": "City", "name": "Jamnagar"},
                        {"@type": "City", "name": "Bhavnagar"},
                        {"@type": "Place", "name": "Satellite"},
                        {"@type": "Place", "name": "Bopal"},
                        {"@type": "Place", "name": "Naranpura"},
                        {"@type": "Place", "name": "Maninagar"},
                        {"@type": "Place", "name": "Vatva GIDC"},
                        {"@type": "Place", "name": "Sanand"},
                        {"@type": "Place", "name": "Vastrapur"}
                    ]
                }
            ],
            "contactPoint": [
                {
                    "@type": "ContactPoint",
                    "telephone": "+919324706905",
                    "contactType": "Customer Service",
                    "areaServed": "IN",
                    "availableLanguage": "en"
                },
                {
                    "@type": "ContactPoint",
                    "telephone": "+919825014977",
                    "contactType": "Customer Service",
                    "areaServed": "IN",
                    "availableLanguage": "en"
                }
            ]<?php
                // sameAs — pull from site_setting if populated, else default to BES Google Business Profile placeholder.
                // Replace these with real URLs when ready (LinkedIn, Facebook, YouTube, Instagram).
                $orgSocialUrls = array_values(array_filter([
                    $siteSettingInfo['facebookUrl'] ?? '',
                    $siteSettingInfo['twitterUrl'] ?? '',
                    $siteSettingInfo['instaUrl'] ?? '',
                    $siteSettingInfo['pintrestUrl'] ?? ''
                ]));
                if (!empty($orgSocialUrls)): ?>,
            "sameAs": <?php echo json_encode($orgSocialUrls); ?>
            <?php endif; ?>
        }
    </script>

    <!-- Services offered (boosts local-pack visibility for "motor repair Mumbai", "pump installation Ahmedabad", etc.) -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Service",
            "@id": "<?php echo SITEURL; ?>/#services",
            "provider": {
                "@type": "Organization",
                "@id": "<?php echo SITEURL; ?>/#organization",
                "name": "Bombay Engineering Syndicate"
            },
            "areaServed": [
                {"@type": "Country", "name": "India"},
                {"@type": "State", "name": "Maharashtra"},
                {"@type": "State", "name": "Gujarat"}
            ],
            "hasOfferCatalog": {
                "@type": "OfferCatalog",
                "name": "Industrial Motor and Pump Services",
                "itemListElement": [
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Industrial Motor Supply & Sales",
                            "description": "Authorised CG Power industrial motor supply across the full power range — HV, LV, energy-efficient (IE2/IE3/IE4), hazardous-area (Ex db/eb/ec/pz), FHP commercial, and special application motors."
                        }
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Submersible and Industrial Pump Supply",
                            "description": "Crompton submersible borewell pumps, openwell pumps, monoblock pumps, booster pumps, mini pumps for residential, agricultural, and industrial applications."
                        }
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Motor Installation and Commissioning",
                            "description": "On-site motor installation, alignment, commissioning, and start-up support for new equipment and replacements."
                        }
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Motor Repair and Rewinding",
                            "description": "Industrial motor rewinding, bearing replacement, and factory-grade repair services with Class F/H insulation and quality testing."
                        }
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Pump Maintenance and Repair",
                            "description": "Pump troubleshooting, impeller and seal replacement, mechanical seal kits, and bearing replacement for residential, agricultural, and industrial pumps."
                        }
                    },
                    {
                        "@type": "Offer",
                        "itemOffered": {
                            "@type": "Service",
                            "name": "Application Engineering and Sizing",
                            "description": "Free expert consultation on motor selection, pump sizing, NPSH calculation, energy efficiency upgrades, and starter/VFD specification."
                        }
                    }
                ]
            }
        }
    </script>

<?php if (defined('WHATSAPP_OG_TYPE') && WHATSAPP_OG_TYPE === 'article' && defined('ARTICLE_PUBLISHED_DATE')): ?>
    <meta property="article:published_time" content="<?php echo htmlspecialchars(ARTICLE_PUBLISHED_DATE, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="article:modified_time" content="<?php echo htmlspecialchars(ARTICLE_PUBLISHED_DATE, ENT_QUOTES, 'UTF-8'); ?>" />
    <meta property="article:author" content="Bombay Engineering Syndicate" />
<?php endif; ?>

    <link rel="icon" href="<?php echo SITEURL; ?>/favicon.ico" sizes="any" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITEURL; ?>/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo SITEURL; ?>/favicon-16x16.png" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITEURL; ?>/apple-touch-icon.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
    // ---- Critical (render-blocking) CSS: core layout only ----
    $criticalCss = array(
        SITEURL . '/vendors/bootstrap/css/bootstrap.min.css',
        // jarallax.css must be blocking: the jarallax JS initialises at DOM-ready
        // and paints section backgrounds; without its positioning rules the
        // parallax background renders invisible (tiny file, no perf cost).
        SITEURL . '/vendors/jarallax/jarallax.css',
        SITEURL . '/css/mellis.css',
        SITEURL . '/css/mellis-responsive.css',
        SITEURL . '/css/style.css',
        SITEURL . '/css/device.css',
        SITEURL . '/css/material-design-3.css',
    );
    // ---- Deferred CSS: plugin/widget styles for below-the-fold features.
    // Loaded async via media="print" swap so they don't block first paint.
    $deferredCss = array(
        SITEURL . '/vendors/fontawesome/css/all.min.css',
        SITEURL . '/vendors/animate/animate.min.css',
        SITEURL . '/vendors/animate/custom-animate.css',
        SITEURL . '/vendors/jquery-magnific-popup/jquery.magnific-popup.css',
        SITEURL . '/vendors/nouislider/nouislider.min.css',
        SITEURL . '/vendors/nouislider/nouislider.pips.css',
        SITEURL . '/vendors/odometer/odometer.min.css',
        SITEURL . '/vendors/tiny-slider/tiny-slider.min.css',
        SITEURL . '/vendors/reey-font/stylesheet.css',
        SITEURL . '/vendors/owl-carousel/owl.carousel.min.css',
        SITEURL . '/vendors/owl-carousel/owl.theme.default.min.css',
        SITEURL . '/vendors/bxslider/jquery.bxslider.css',
        SITEURL . '/vendors/bootstrap-select/css/bootstrap-select.min.css',
        SITEURL . '/vendors/jquery-ui/jquery-ui.css',
        SITEURL . '/vendors/timepicker/timePicker.css',
        SITEURL . '/vendors/twenty-twenty/twentytwenty.css',
    );
    // Google Fonts (already display=swap) — async as well
    $deferredFonts = array(
        'https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap',
        'https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap',
        'https://fonts.googleapis.com/css2?family=Parisienne&display=swap',
    );
    foreach ($criticalCss as $cssUrl) {
        echo '<link rel="stylesheet" href="' . mxGetUrl($cssUrl) . '" />' . "\n    ";
    }
    foreach ($deferredFonts as $fontUrl) {
        echo '<link rel="stylesheet" href="' . $fontUrl . '" media="print" onload="this.media=\'all\'" />' . "\n    ";
    }
    foreach ($deferredCss as $cssUrl) {
        echo '<link rel="stylesheet" href="' . mxGetUrl($cssUrl) . '" media="print" onload="this.media=\'all\'" />' . "\n    ";
    }
    ?>
    <noscript>
        <?php foreach (array_merge($deferredFonts, array()) as $fontUrl) {
            echo '<link rel="stylesheet" href="' . $fontUrl . '" />' . "\n        ";
        }
        foreach ($deferredCss as $cssUrl) {
            echo '<link rel="stylesheet" href="' . mxGetUrl($cssUrl) . '" />' . "\n        ";
        } ?>
    </noscript>

    <!-- Business Contact Information Meta Tags -->
    <meta name="business:contact_data:street_address" content="2nd Floor, Modern House, Dr. V.B. Gandhi Marg, Kala Ghoda, Fort, Mumbai" />
    <meta name="business:contact_data:locality" content="Mumbai" />
    <meta name="business:contact_data:region" content="Maharashtra" />
    <meta name="business:contact_data:postal_code" content="400001" />
    <meta name="business:contact_data:country_name" content="India" />
    <meta name="business:contact_data:phone_number" content="+919324706905" />
    <meta name="business:contact_data:email" content="besyndicate@gmail.com" />

    <!-- Business Hours Meta Tags -->
    <meta name="business:hours:monday" content="10:00-18:00" />
    <meta name="business:hours:tuesday" content="10:00-18:00" />
    <meta name="business:hours:wednesday" content="10:00-18:00" />
    <meta name="business:hours:thursday" content="10:00-18:00" />
    <meta name="business:hours:friday" content="10:00-18:00" />
    <meta name="business:hours:saturday" content="10:00-18:00" />
    <meta name="business:hours:sunday" content="closed" />

    <!-- Mobile & Accessibility Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="Bombay Engineering" />
    <meta name="accessibility" content="compliant" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="theme-color" content="#1a1a1a" />

    <!-- Google reCAPTCHA — lazy-loaded: ~375 KB, so only fetch it on pages that
         actually contain a captcha, and only on first user interaction
         (5 s fallback so the widget is always ready before a submit). -->
    <script>
    (function () {
        var loaded = false;
        function loadRecaptcha() {
            if (loaded) return;
            loaded = true;
            var s = document.createElement('script');
            s.src = 'https://www.google.com/recaptcha/api.js';
            s.async = true;
            s.defer = true;
            document.head.appendChild(s);
        }
        document.addEventListener('DOMContentLoaded', function () {
            // Pages mark captcha usage either with a .g-recaptcha widget div or a
            // hidden g-recaptcha-response field (inquiry forms).
            if (!document.querySelector('.g-recaptcha, [name="g-recaptcha-response"]')) return;
            // Some templates (contact-us) load api.js themselves — don't double-load.
            if (document.querySelector('script[src*="recaptcha/api.js"]')) { loaded = true; return; }
            ['pointerdown', 'keydown', 'touchstart', 'scroll'].forEach(function (evt) {
                window.addEventListener(evt, loadRecaptcha, { passive: true, once: true });
            });
            setTimeout(loadRecaptcha, 5000);
        });
    })();
    </script>
</head>

<body>
    <div class="preloader">
        <div class="preloader__content">
            <div></div>
            <div></div>
        </div>
    </div>

    <?php
    $headerStyle = "";
    if (isset($TPL->uriArr[0]) && $TPL->uriArr[0] == "driver") {
        $headerStyle = "style='display:none'";
    }
    ?>

    <div class="page-wrapper">
        <header class="main-header" <?php echo $headerStyle; ?>>
            <div class="main-header__top">
                <div class="main-header__top-wrapper">
                    <div class="main-header__top-inner">
                        <div class="main-header__top-left">
                            <ul class="list-unstyled main-header__contact-list">
                                <?php if (trim($siteSettingInfo["contactMail"] ?? "") !== "") { ?>
                                    <li>
                                        <div class="icon">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="text">
                                            <p><a href="mailto:<?php echo $siteSettingInfo["contactMail"] ?>"><?php echo $siteSettingInfo["contactMail"] ?></a></p>
                                        </div>
                                    </li>
                                <?php } ?>
                                <?php if (trim($siteSettingInfo["contactNo"] ?? "") !== "") { ?>
                                    <li>
                                        <div class="icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="text">
                                            <p><a href="tel:<?php echo $siteSettingInfo["contactNo"] ?>"><?php echo $siteSettingInfo["contactNo"] ?></a></p>
                                        </div>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div class="main-header__top-right">
                            <div class="main-header__social">
                                <?php if (isset($siteSettingInfo["twitterUrl"])) { ?>
                                    <a href="<?php echo $siteSettingInfo["twitterUrl"] ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                                <?php } ?>
                                <?php if (isset($siteSettingInfo["facebookUrl"])) { ?>
                                    <a href="<?php echo $siteSettingInfo["facebookUrl"] ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                                <?php } ?>
                                <?php if (isset($siteSettingInfo["pintrestUrl"])) { ?>
                                    <a href="<?php echo $siteSettingInfo["pintrestUrl"] ?>" target="_blank"><i class="fab fa-pinterest-p"></i></a>
                                <?php } ?>
                                <?php if (isset($siteSettingInfo["instaUrl"])) { ?>
                                    <a href="<?php echo $siteSettingInfo["instaUrl"] ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                                <?php } ?>
                            </div>
                            <div class="main-header__btn-box">
                                <a href="<?php echo SITEURL . '/product-inquiry/' ?>" class="thm-btn main-header__btn btn-enquiry-boxed">Motor Enquiry form</a>
                                <a href="<?php echo SITEURL . '/pump-inquiry/' ?>" class="thm-btn main-header__btn btn-enquiry-boxed">Pump Enquiry form</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <nav class="main-menu">
                <div class="main-menu__wrapper">
                    <div class="main-menu__wrapper-inner">
                        <div class="main-menu__left">
                            <div class="main-menu__logo">
                                <a href="<?php echo SITEURL . '/' ?>"><img src="<?php echo SITEURL . '/images/logo.png' ?>" alt="Bombay Engineering Syndicate"></a>
                            </div>
                        </div>
                        <div class="menu-wrap">
                            <a href="#" class="mobile-nav__toggler" aria-label="Toggle navigation menu"><i class="fa fa-bars"></i></a>
                            <ul class="menu-list">
                                <?php echo getMenu("Header"); ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="mobile-nav__wrapper">
                <div class="mobile-nav__overlay mobile-nav__toggler"></div>
                <div class="mobile-nav__content">
                    <span class="mobile-nav__close mobile-nav__toggler" aria-label="Close navigation menu"><i class="fa fa-times"></i></span>
                    <div class="logo-box">
                        <a href="<?php echo SITEURL . '/' ?>"><img src="<?php echo SITEURL . '/images/logo.png' ?>" alt="Bombay Engineering Syndicate"></a>
                    </div>
                    <div class="mobile-nav__container"></div>
                    <ul class="mobile-nav__contact list-unstyled">
                        <?php if (trim($siteSettingInfo["contactMail"] ?? "") !== "") { ?>
                            <li>
                                <i class="fa fa-envelope"></i>
                                <a href="mailto:<?php echo $siteSettingInfo["contactMail"] ?>"><?php echo $siteSettingInfo["contactMail"] ?></a>
                            </li>
                        <?php } ?>
                        <?php if (trim($siteSettingInfo["contactNo"] ?? "") !== "") { ?>
                            <li>
                                <i class="fa fa-phone-alt"></i>
                                <a href="tel:<?php echo $siteSettingInfo["contactNo"] ?>"><?php echo $siteSettingInfo["contactNo"] ?></a>
                            </li>
                        <?php } ?>
                    </ul>
                    <div class="mobile-nav__top">
                        <div class="mobile-nav__social">
                            <div class="main-header__social">
                                <?php if (trim($siteSettingInfo["twitterUrl"] ?? "") !== "") { ?>
                                    <a href="<?php echo $siteSettingInfo["twitterUrl"] ?>"><i class="fab fa-twitter"></i></a>
                                <?php } ?>
                                <?php if (trim($siteSettingInfo["facebookUrl"] ?? "") !== "") { ?>
                                    <a href="<?php echo $siteSettingInfo["facebookUrl"] ?>"><i class="fab fa-facebook-square"></i></a>
                                <?php } ?>
                                <?php if (trim($siteSettingInfo["pintrestUrl"] ?? "") !== "") { ?>
                                    <a href="<?php echo $siteSettingInfo["pintrestUrl"] ?>"><i class="fab fa-pinterest-p"></i></a>
                                <?php } ?>
                                <?php if (trim($siteSettingInfo["instaUrl"] ?? "") !== "") { ?>
                                    <a href="<?php echo $siteSettingInfo["instaUrl"] ?>"><i class="fab fa-instagram"></i></a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="main-header__btn-box">
                            <a href="<?php echo SITEURL . '/product-inquiry/' ?>" class="thm-btn main-header__btn btn-enquiry-boxed">Motor Enquiry form</a>
                        </div>
                        <div class="main-header__btn-box">
                            <a href="<?php echo SITEURL . '/pump-inquiry/' ?>" class="thm-btn main-header__btn btn-enquiry-boxed">Pump Enquiry form</a>
                        </div>
                    </div>
                </div>
        </header>
        <div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div>
        </div>

        <main>
        <div class="mx-container">
            <!-- Content from includes like x-product-inquiry.inc.php goes here -->
