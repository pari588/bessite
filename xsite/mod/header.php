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
  . "script-src 'self' 'unsafe-inline' https://www.bombayengg.net; "
  . "style-src 'self' 'unsafe-inline'; "
  . "img-src 'self' data:; "
  . "font-src 'self'; "
  . "object-src 'none'; "
  . "frame-ancestors 'none'; "
  . "base-uri 'self'; "
  . "form-action 'self';",
    false
);

// ────────────────────────────────────────────────────────────────────────────────
// End of security headers
// ────────────────────────────────────────────────────────────────────────────────

// To fetch header user info data.
$siteSettingInfo = getSiteInfo();
// End.
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bombay Engineering Syndicate - Motors & Engineering Solutions in Mumbai & Ahmedabad</title>
    <meta name="description" content="Bombay Engineering Syndicate offers energy-efficient motors and engineering solutions in Mumbai, Maharashtra, and Ahmedabad, Gujarat. Contact us for customized industrial needs!">
    <meta name="keywords" content="motors Mumbai, motors Ahmedabad, pumps, water pumps, submersible pumps, residential pumps, energy-efficient motors, engineering solutions Mumbai, industrial motors Gujarat, Bombay Engineering Syndicate">

    <?php echo mxGetMeta(); ?>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(SITEURL . '/' . LIBDIR . '/js/jquery-3.3.1.min.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/config.js.php', getJsVars()); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/common.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/dialog.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/validate.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(COREURL . '/js/form.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(ADMINURL . '/core-admin/js/common.inc.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo mxGetUrl(ADMINURL . '/core-admin/js/inside.inc.js'); ?>"></script>

    <meta property="og:title" content="Bombay Engineering Syndicate - Motors in Mumbai & Ahmedabad" />
    <meta property="og:description" content="Providing energy-efficient motors and engineering solutions in Mumbai and Ahmedabad since 1957." />
    <meta property="og:url" content="<?php echo SITEURL; ?>" />
    <meta property="og:image" content="<?php echo SITEURL; ?>/images/moters.jpeg" />
    <meta property="og:image:type" content="image/jpeg" />
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="en_IN" />

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "LocalBusiness",
            "name": "Bombay Engineering Syndicate",
            "description": "Supplier of energy-efficient motors and engineering solutions in Mumbai and Ahmedabad since 1957.",
            "url": "<?php echo SITEURL; ?>",
            "telephone": ["+919820042210", "+919825014977"],
            "email": "besyndicate@gmail.com",
            "address": [{
                    "@type": "PostalAddress",
                    "streetAddress": "17, Dr.V.B.Gandhi Marg (Forbes Street), Fort",
                    "addressLocality": "Mumbai",
                    "addressRegion": "Maharashtra",
                    "postalCode": "400023",
                    "addressCountry": "IN"
                },
                {
                    "@type": "PostalAddress",
                    "streetAddress": "F-10, Satyam Complex, Near Prerna Tirth Derasar, Jodhpurgam, Satellite",
                    "addressLocality": "Ahmedabad",
                    "addressRegion": "Gujarat",
                    "postalCode": "380015",
                    "addressCountry": "IN"
                }
            ],
            "openingHours": "Mo-Fr 09:00-18:00",
            "geo": [{
                    "@type": "GeoCoordinates",
                    "latitude": "18.9333",
                    "longitude": "72.8333"
                },
                {
                    "@type": "GeoCoordinates",
                    "latitude": "23.0225",
                    "longitude": "72.5714"
                }
            ],
            "sameAs": [
                "<?php echo !empty($siteSettingInfo['facebookUrl']) ? $siteSettingInfo['facebookUrl'] : ''; ?>",
                "<?php echo !empty($siteSettingInfo['twitterUrl']) ? $siteSettingInfo['twitterUrl'] : ''; ?>",
                "<?php echo !empty($siteSettingInfo['instaUrl']) ? $siteSettingInfo['instaUrl'] : ''; ?>",
                "<?php echo !empty($siteSettingInfo['pintrestUrl']) ? $siteSettingInfo['pintrestUrl'] : ''; ?>"
            ]
        }
    </script>

    <link href="<?php echo UPLOADURL; ?>/setting/<?php echo $MXSET['FAVICON']; ?>" rel="shortcut icon" type="image/x-icon" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/bootstrap/css/bootstrap.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/animate/animate.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/animate/custom-animate.css') ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/fontawesome/css/all.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/jarallax/jarallax.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-magnific-popup/jquery.magnific-popup.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/nouislider/nouislider.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/nouislider/nouislider.pips.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/odometer/odometer.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/tiny-slider/tiny-slider.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/reey-font/stylesheet.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/owl-carousel/owl.carousel.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/owl-carousel/owl.theme.default.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/bxslider/jquery.bxslider.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/bootstrap-select/css/bootstrap-select.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/jquery-ui/jquery-ui.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/timepicker/timePicker.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/twenty-twenty/twentytwenty.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/mellis.css') ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/mellis-responsive.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/style.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/device.css'); ?>" />
    <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/css/material-design-3.css'); ?>" />
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
                                <a href="<?php echo SITEURL . '/product-inquiry/' ?>" class="thm-btn main-header__btn">Motor Enquiry form</a>
                                <a href="<?php echo SITEURL . '/pump-inquiry/' ?>" class="thm-btn main-header__btn">Pump Enquiry form</a>
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
                            <a href="#" class="mobile-nav__toggler"><i class="fa fa-bars"></i></a>
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
                    <span class="mobile-nav__close mobile-nav__toggler"><i class="fa fa-times"></i></span>
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
                            <a href="<?php echo SITEURL . '/product-inquiry/' ?>" class="thm-btn main-header__btn">Motor Enquiry form</a>
                        </div>
                        <div class="main-header__btn-box">
                            <a href="<?php echo SITEURL . '/pump-inquiry/' ?>" class="thm-btn main-header__btn">Pump Enquiry form</a>
                        </div>
                    </div>
                </div>
        </header>
        <div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div>
        </div>

        <div class="mx-container">
            <!-- Content from includes like x-product-inquiry.inc.php goes here -->
