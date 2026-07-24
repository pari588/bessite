<?php
/**
 * Service/Support Page Template
 *
 * This template displays manufacturer support contacts.
 *
 * Editable in xadmin via:
 * - Page > Edit "Support" page for pageTitle, pageContent (intro), synopsis (notice)
 * - Service Manufacturers for adding/editing manufacturer contacts
 */

// Fetch manufacturers from database
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT * FROM `" . $DB->pre . "service_manufacturer` WHERE status=? ORDER BY sortOrder ASC, manufacturerID ASC";
$DB->dbRows();
$manufacturers = $DB->rows;

// SEO Meta Tags - can be overridden by page meta settings
$metaTitle = $TPL->data["pageTitle"] . " | Crompton & CG Power Helpline | Bombay Engineering Syndicate";
$metaDescription = "Get support for Crompton pumps and CG Power motors. Find official helpline numbers, WhatsApp support, and service center contacts. Bombay Engineering Syndicate - Authorized Distributor in Mumbai.";
$metaKeywords = "Crompton customer care, Crompton helpline number, CG Power support, pump service center Mumbai, motor repair helpline, Crompton WhatsApp support, CG Power contact, pump service India, motor service center, Bombay Engineering support";
$canonicalUrl = SITEURL . "/service/";

// Override page meta if function exists
if (function_exists('setPageMeta')) {
    setPageMeta($metaTitle, $metaDescription, $metaKeywords);
}
?>

<!-- SEO: Breadcrumb Schema -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "<?php echo SITEURL; ?>/"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "<?php echo htmlspecialchars($TPL->data["pageTitle"]); ?>",
            "item": "<?php echo SITEURL; ?>/service/"
        }
    ]
}
</script>

<!-- SEO: ContactPage Schema -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ContactPage",
    "name": "Customer Support - Bombay Engineering Syndicate",
    "description": "<?php echo $metaDescription; ?>",
    "url": "<?php echo $canonicalUrl; ?>",
    "mainEntity": {
        "@type": "Organization",
        "name": "Bombay Engineering Syndicate",
        "url": "<?php echo SITEURL; ?>",
        "contactPoint": [
            {
                "@type": "ContactPoint",
                "contactType": "customer support",
                "telephone": "+91-22-23422878",
                "areaServed": "IN",
                "availableLanguage": ["English", "Hindi"]
            }
        ]
    }
}
</script>

<?php if (!empty($manufacturers)): ?>
<!-- SEO: Manufacturer List Schema -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "Manufacturer Support Contacts",
    "description": "Official customer support contacts for our partner manufacturers",
    "itemListElement": [
        <?php
        $schemaItems = [];
        foreach ($manufacturers as $index => $mfr) {
            $item = '{
                "@type": "ListItem",
                "position": ' . ($index + 1) . ',
                "item": {
                    "@type": "Organization",
                    "name": "' . htmlspecialchars($mfr["name"]) . '"';
            if (!empty($mfr["description"])) {
                $item .= ',
                    "description": "' . htmlspecialchars(strip_tags($mfr["description"])) . '"';
            }
            if (!empty($mfr["website"])) {
                $item .= ',
                    "url": "' . htmlspecialchars($mfr["website"]) . '"';
            }
            if (!empty($mfr["phoneNumber"])) {
                $item .= ',
                    "telephone": "' . htmlspecialchars($mfr["phoneNumber"]) . '"';
            }
            if (!empty($mfr["email"])) {
                $item .= ',
                    "email": "' . htmlspecialchars($mfr["email"]) . '"';
            }
            $item .= '
                }
            }';
            $schemaItems[] = $item;
        }
        echo implode(",\n        ", $schemaItems);
        ?>
    ]
}
</script>
<?php endif; ?>

<!--Page Header Start-->
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);">
    </div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li><?php echo $TPL->data["pageTitle"] ?></li>
            </ul>
            <h2><?php echo $TPL->data["pageTitle"] ?></h2>
        </div>
    </div>
</section>
<!--Page Header End-->

<!-- Service Page Styles -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Manrope:wght@400;500;600;700&display=swap');

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-40px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(40px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.service-page {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);
    position: relative;
    overflow: hidden;
}

.service-page::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 400px;
    background: linear-gradient(135deg, #0a1f3d 0%, #0f2d52 50%, #143d6b 100%);
    clip-path: polygon(0 0, 100% 0, 100% 70%, 0 100%);
    z-index: 0;
}

.service-page::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 400px;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    z-index: 1;
    pointer-events: none;
}

.service-intro {
    position: relative;
    z-index: 2;
    padding: 60px 0 80px;
}

.service-intro__content {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    animation: fadeInUp 0.8s ease-out;
}

.service-intro__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 10px 24px;
    border-radius: 50px;
    color: #ffffff;
    font-family: 'Manrope', sans-serif;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    margin-bottom: 24px;
}

.service-intro__badge svg {
    width: 18px;
    height: 18px;
}

.service-intro__title {
    font-family: 'Libre Baskerville', serif;
    font-size: 42px;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.25;
    margin-bottom: 20px;
}

.service-intro__subtitle {
    font-family: 'Manrope', sans-serif;
    font-size: 18px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.7;
    max-width: 650px;
    margin: 0 auto;
}

.service-notice {
    position: relative;
    z-index: 2;
    max-width: 900px;
    margin: -40px auto 60px;
    padding: 0 20px;
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.service-notice__card {
    background: #ffffff;
    border-radius: 16px;
    padding: 32px 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(21, 123, 186, 0.1);
    display: flex;
    align-items: flex-start;
    gap: 24px;
}

.service-notice__icon {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #157bba 0%, #1e90d0 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(21, 123, 186, 0.25);
}

.service-notice__icon svg {
    width: 28px;
    height: 28px;
    color: #ffffff;
}

.service-notice__text h3 {
    font-family: 'Libre Baskerville', serif;
    font-size: 20px;
    font-weight: 700;
    color: #0a1f3d;
    margin-bottom: 10px;
}

.service-notice__text p {
    font-family: 'Manrope', sans-serif;
    font-size: 16px;
    color: #64748b;
    line-height: 1.7;
    margin: 0;
}

.service-notice__text strong {
    color: #157bba;
    font-weight: 600;
}

.service-manufacturers {
    position: relative;
    z-index: 2;
    padding: 0 0 80px;
}

.service-manufacturers__header {
    text-align: center;
    margin-bottom: 50px;
    animation: fadeInUp 0.8s ease-out 0.3s both;
}

.service-manufacturers__label {
    font-family: 'Manrope', sans-serif;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: #157bba;
    margin-bottom: 12px;
}

.service-manufacturers__title {
    font-family: 'Libre Baskerville', serif;
    font-size: 32px;
    font-weight: 700;
    color: #0a1f3d;
}

.service-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
}

.manufacturer-card {
    background: #ffffff;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.8s ease-out 0.4s both;
}

.manufacturer-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.12);
    border-color: transparent;
}

.manufacturer-card__header {
    padding: 30px 30px 25px;
    position: relative;
    overflow: hidden;
}

.manufacturer-card__header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}

.manufacturer-card__logo {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 1;
}

.manufacturer-card__logo-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.manufacturer-card__logo-icon img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.manufacturer-card__logo-text { color: #ffffff; }

.manufacturer-card__logo-text h3 {
    font-family: 'Libre Baskerville', serif;
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 4px;
    color: #ffffff;
}

.manufacturer-card__logo-text span {
    font-family: 'Manrope', sans-serif;
    font-size: 13px;
    opacity: 0.9;
    font-weight: 500;
}

.manufacturer-card__body { padding: 28px 30px 30px; }

.manufacturer-card__description {
    font-family: 'Manrope', sans-serif;
    font-size: 15px;
    color: #64748b;
    line-height: 1.7;
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e2e8f0;
}

.manufacturer-card__contacts {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 12px;
    transition: all 0.3s ease;
    text-decoration: none;
    border: 1px solid transparent;
}

.contact-item:hover {
    background: #ffffff;
    border-color: #157bba;
    transform: translateX(6px);
    box-shadow: 0 4px 16px rgba(21, 123, 186, 0.12);
}

.contact-item__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-item__icon--phone { background: linear-gradient(135deg, #157bba 0%, #1e90d0 100%); }
.contact-item__icon--whatsapp { background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); }
.contact-item__icon--email { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
.contact-item__icon--web { background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%); }

.contact-item__icon svg {
    width: 22px;
    height: 22px;
    color: #ffffff;
}

.contact-item__content { flex: 1; }

.contact-item__label {
    font-family: 'Manrope', sans-serif;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: #94a3b8;
    margin-bottom: 3px;
}

.contact-item__value {
    font-family: 'Manrope', sans-serif;
    font-size: 16px;
    font-weight: 600;
    color: #0a1f3d;
}

.contact-item__arrow {
    width: 32px;
    height: 32px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.contact-item:hover .contact-item__arrow { background: #157bba; }

.contact-item__arrow svg {
    width: 16px;
    height: 16px;
    color: #64748b;
    transition: all 0.3s ease;
}

.contact-item:hover .contact-item__arrow svg {
    color: #ffffff;
    transform: translateX(2px);
}

.manufacturer-card__address {
    margin-top: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 12px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.manufacturer-card__address-icon {
    width: 36px;
    height: 36px;
    background: #ffffff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.manufacturer-card__address-icon svg {
    width: 18px;
    height: 18px;
    color: #157bba;
}

.manufacturer-card__address-text {
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    color: #475569;
    line-height: 1.6;
}

.manufacturer-card__address-text strong {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #0a1f3d;
    margin-bottom: 4px;
}

.service-bes {
    position: relative;
    z-index: 2;
    padding: 60px 0 80px;
    background: linear-gradient(180deg, #0a1f3d 0%, #0f2d52 100%);
}

.service-bes__inner {
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
    padding: 0 20px;
    animation: fadeInUp 0.8s ease-out 0.5s both;
}

.service-bes__icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #157bba 0%, #1e90d0 100%);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
    box-shadow: 0 15px 40px rgba(21, 123, 186, 0.35);
    animation: float 3s ease-in-out infinite;
}

.service-bes__icon svg {
    width: 40px;
    height: 40px;
    color: #ffffff;
}

.service-bes__title {
    font-family: 'Libre Baskerville', serif;
    font-size: 28px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 16px;
}

.service-bes__text {
    font-family: 'Manrope', sans-serif;
    font-size: 17px;
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.8;
    margin-bottom: 32px;
}

.service-bes__cta {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    color: #0a1f3d;
    padding: 16px 32px;
    border-radius: 12px;
    font-family: 'Manrope', sans-serif;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.service-bes__cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
    color: #157bba;
}

.service-bes__cta svg {
    width: 20px;
    height: 20px;
    transition: transform 0.3s ease;
}

.service-bes__cta:hover svg { transform: translateX(4px); }

@media (max-width: 991px) {
    .service-intro__title { font-size: 34px; }
    .service-cards { grid-template-columns: 1fr; max-width: 550px; }
}

@media (max-width: 767px) {
    .service-page::before { height: 350px; }
    .service-intro { padding: 40px 0 70px; }
    .service-intro__title { font-size: 28px; }
    .service-intro__subtitle { font-size: 16px; }
    .service-notice__card { flex-direction: column; padding: 24px; text-align: center; }
    .service-notice__icon { margin: 0 auto; }
    .manufacturer-card__header { padding: 24px; }
    .manufacturer-card__body { padding: 24px; }
    .manufacturer-card__logo { flex-direction: column; text-align: center; }
    .contact-item { padding: 14px 16px; }
    .service-manufacturers__title { font-size: 26px; }
    .service-bes__title { font-size: 24px; }
}

@media (max-width: 480px) {
    .service-intro__badge { font-size: 11px; padding: 8px 18px; }
    .service-intro__title { font-size: 24px; }
    .contact-item__value { font-size: 14px; }
}
</style>

<!-- Service Page Content -->
<main class="service-page" role="main">
    <article class="service-intro" aria-labelledby="support-title">
        <div class="container">
            <div class="service-intro__content">
                <div class="service-intro__badge">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Customer Support
                </div>
                <!-- pageContent from database - editable in xadmin Page section -->
                <?php echo $TPL->data["pageContent"]; ?>
            </div>
        </div>
    </article>

    <?php if (!empty($TPL->data["synopsis"])): ?>
    <div class="service-notice">
        <div class="service-notice__card">
            <div class="service-notice__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="service-notice__text">
                <!-- synopsis from database - editable in xadmin Page section -->
                <?php echo $TPL->data["synopsis"]; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($manufacturers)): ?>
    <div class="service-manufacturers">
        <div class="container">
            <div class="service-manufacturers__header">
                <div class="service-manufacturers__label">Official Support Channels</div>
                <h2 class="service-manufacturers__title">Manufacturer Helplines</h2>
            </div>

            <div class="service-cards">
                <?php foreach ($manufacturers as $mfr):
                    // Determine logo path
                    $logoPath = '';
                    if (!empty($mfr["logo"])) {
                        // Check if it's in service-manufacturer folder or home folder
                        if (file_exists(UPLOADPATH . "/service-manufacturer/" . $mfr["logo"])) {
                            $logoPath = UPLOADURL . "/service-manufacturer/" . $mfr["logo"];
                        } elseif (file_exists(UPLOADPATH . "/home/" . $mfr["logo"])) {
                            $logoPath = UPLOADURL . "/home/" . $mfr["logo"];
                        }
                    }

                    // Parse card color for gradient
                    $cardColor = !empty($mfr["cardColor"]) ? $mfr["cardColor"] : '#003566';
                ?>
                <!-- <?php echo htmlspecialchars($mfr["name"]); ?> Card -->
                <div class="manufacturer-card">
                    <div class="manufacturer-card__header" style="background: linear-gradient(135deg, <?php echo $cardColor; ?> 0%, <?php echo $cardColor; ?>cc 50%, <?php echo $cardColor; ?>99 100%);">
                        <div class="manufacturer-card__logo">
                            <div class="manufacturer-card__logo-icon">
                                <?php if ($logoPath): ?>
                                <img src="<?php echo $logoPath; ?>" alt="<?php echo htmlspecialchars($mfr["name"]); ?>" onerror="this.style.display='none'">
                                <?php endif; ?>
                            </div>
                            <div class="manufacturer-card__logo-text">
                                <h3><?php echo htmlspecialchars($mfr["name"]); ?></h3>
                                <?php if (!empty($mfr["tagline"])): ?>
                                <span><?php echo htmlspecialchars($mfr["tagline"]); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="manufacturer-card__body">
                        <?php if (!empty($mfr["description"])): ?>
                        <p class="manufacturer-card__description"><?php echo htmlspecialchars($mfr["description"]); ?></p>
                        <?php endif; ?>

                        <div class="manufacturer-card__contacts">
                            <?php if (!empty($mfr["phoneNumber"])): ?>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $mfr["phoneNumber"]); ?>" class="contact-item" aria-label="Call <?php echo htmlspecialchars($mfr["name"]); ?> helpline">
                                <div class="contact-item__icon contact-item__icon--phone">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Helpline</div>
                                    <div class="contact-item__value"><?php echo htmlspecialchars($mfr["phoneNumber"]); ?></div>
                                </div>
                                <div class="contact-item__arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($mfr["whatsappNumber"])):
                                $waNumber = preg_replace('/[^0-9]/', '', $mfr["whatsappNumber"]);
                            ?>
                            <a href="https://wa.me/<?php echo $waNumber; ?>" target="_blank" rel="noopener" class="contact-item" aria-label="Chat with <?php echo htmlspecialchars($mfr["name"]); ?> on WhatsApp">
                                <div class="contact-item__icon contact-item__icon--whatsapp">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">WhatsApp</div>
                                    <div class="contact-item__value"><?php echo htmlspecialchars($mfr["whatsappNumber"]); ?></div>
                                </div>
                                <div class="contact-item__arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($mfr["email"])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($mfr["email"]); ?>" class="contact-item" aria-label="Email <?php echo htmlspecialchars($mfr["name"]); ?> support">
                                <div class="contact-item__icon contact-item__icon--email">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Email Support</div>
                                    <div class="contact-item__value"><?php echo htmlspecialchars($mfr["email"]); ?></div>
                                </div>
                                <div class="contact-item__arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($mfr["website"])):
                                $websiteDisplay = preg_replace('/^https?:\/\//', '', $mfr["website"]);
                            ?>
                            <a href="<?php echo htmlspecialchars($mfr["website"]); ?>" target="_blank" rel="noopener" class="contact-item" aria-label="Visit <?php echo htmlspecialchars($mfr["name"]); ?> official website">
                                <div class="contact-item__icon contact-item__icon--web">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Website</div>
                                    <div class="contact-item__value"><?php echo htmlspecialchars($websiteDisplay); ?></div>
                                </div>
                                <div class="contact-item__arrow">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($mfr["address"])): ?>
                        <div class="manufacturer-card__address">
                            <div class="manufacturer-card__address-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div class="manufacturer-card__address-text">
                                <strong>Corporate Office</strong>
                                <?php echo htmlspecialchars($mfr["address"]); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <aside class="service-bes" aria-label="Bombay Engineering Syndicate assistance">
        <div class="service-bes__inner">
            <div class="service-bes__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <h2 class="service-bes__title">Need Our Assistance?</h2>
            <p class="service-bes__text">While we don't provide direct servicing, our experienced team at Bombay Engineering Syndicate is always ready to help guide you through the process, answer product queries, and connect you with the right support channels.</p>
            <a href="<?php echo SITEURL; ?>/contact-us/" class="service-bes__cta" aria-label="Contact Bombay Engineering Syndicate team">
                Contact Our Team
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
            </a>
        </div>
    </aside>
</main>
