<?php
/**
 * Update Service/Support Page Content in Database
 * This populates the pageContent field so it's visible in xadmin
 */

require_once(__DIR__ . "/core/core.inc.php");

$pageContent = <<<'HTML'
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
                <h1 class="service-intro__title" id="support-title">Service &amp; Support Assistance</h1>
                <p class="service-intro__subtitle">Get the help you need for your pumps and motors. We guide you to the right support channels for quick resolution.</p>
            </div>
        </div>
    </article>

    <div class="service-notice">
        <div class="service-notice__card">
            <div class="service-notice__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="service-notice__text">
                <h3>Important Notice</h3>
                <p><strong>Bombay Engineering Syndicate</strong> is an authorized distributor and does not directly undertake servicing or repairs. However, we are happy to assist you in connecting with the manufacturer's service network. For the best and fastest support, we recommend contacting the manufacturer's helpline directly.</p>
            </div>
        </div>
    </div>

    <div class="service-manufacturers">
        <div class="container">
            <div class="service-manufacturers__header">
                <div class="service-manufacturers__label">Official Support Channels</div>
                <h2 class="service-manufacturers__title">Manufacturer Helplines</h2>
            </div>

            <div class="service-cards">
                <!-- Crompton Card -->
                <div class="manufacturer-card manufacturer-card--crompton">
                    <div class="manufacturer-card__header">
                        <div class="manufacturer-card__logo">
                            <div class="manufacturer-card__logo-icon">
                                <img src="https://www.bombayengg.net/uploads/home/crompton.png" alt="Crompton">
                            </div>
                            <div class="manufacturer-card__logo-text">
                                <h3>Crompton</h3>
                                <span>Consumer Electricals</span>
                            </div>
                        </div>
                    </div>
                    <div class="manufacturer-card__body">
                        <p class="manufacturer-card__description">For residential pumps, domestic water solutions, and home electrical products. Crompton offers comprehensive after-sales support across India.</p>
                        <div class="manufacturer-card__contacts">
                            <a href="tel:9228880505" class="contact-item">
                                <div class="contact-item__icon contact-item__icon--phone">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Helpline</div>
                                    <div class="contact-item__value">9228880505</div>
                                </div>
                            </a>
                            <a href="https://wa.me/917428713838" target="_blank" class="contact-item">
                                <div class="contact-item__icon contact-item__icon--whatsapp">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">WhatsApp</div>
                                    <div class="contact-item__value">+91 7428713838</div>
                                </div>
                            </a>
                            <a href="https://www.crompton.co.in" target="_blank" class="contact-item">
                                <div class="contact-item__icon contact-item__icon--web">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Website</div>
                                    <div class="contact-item__value">www.crompton.co.in</div>
                                </div>
                            </a>
                        </div>
                        <div class="manufacturer-card__address">
                            <div class="manufacturer-card__address-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div class="manufacturer-card__address-text">
                                <strong>Corporate Office</strong>
                                Crompton Greaves Consumer Electricals Ltd., 05GBD, Godrej Business District, Pirojshanagar, Vikhroli (West), Mumbai - 400079
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CG Power Card -->
                <div class="manufacturer-card manufacturer-card--cgpower">
                    <div class="manufacturer-card__header">
                        <div class="manufacturer-card__logo">
                            <div class="manufacturer-card__logo-icon">
                                <img src="https://www.bombayengg.net/uploads/home/cgpower.jpg" alt="CG Power">
                            </div>
                            <div class="manufacturer-card__logo-text">
                                <h3>CG Power</h3>
                                <span>Industrial Solutions</span>
                            </div>
                        </div>
                    </div>
                    <div class="manufacturer-card__body">
                        <p class="manufacturer-card__description">For industrial motors, drives, transformers, and power equipment. CG Power provides specialized technical support for commercial and industrial applications.</p>
                        <div class="manufacturer-card__contacts">
                            <a href="tel:02267592439" class="contact-item">
                                <div class="contact-item__icon contact-item__icon--phone">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Helpline</div>
                                    <div class="contact-item__value">022-67592439</div>
                                </div>
                            </a>
                            <a href="mailto:service.cg@cgglobal.com" class="contact-item">
                                <div class="contact-item__icon contact-item__icon--email">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Email Support</div>
                                    <div class="contact-item__value">service.cg@cgglobal.com</div>
                                </div>
                            </a>
                            <a href="https://www.cgglobal.com" target="_blank" class="contact-item">
                                <div class="contact-item__icon contact-item__icon--web">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                                </div>
                                <div class="contact-item__content">
                                    <div class="contact-item__label">Website</div>
                                    <div class="contact-item__value">www.cgglobal.com</div>
                                </div>
                            </a>
                        </div>
                        <div class="manufacturer-card__address">
                            <div class="manufacturer-card__address-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <div class="manufacturer-card__address-text">
                                <strong>Corporate Office</strong>
                                CG Power and Industrial Solutions Ltd., CG House, 6th Floor, Dr. Annie Besant Road, Worli, Mumbai - 400030
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <aside class="service-bes">
        <div class="service-bes__inner">
            <div class="service-bes__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <h2 class="service-bes__title">Need Our Assistance?</h2>
            <p class="service-bes__text">While we don't provide direct servicing, our experienced team at Bombay Engineering Syndicate is always ready to help guide you through the process, answer product queries, and connect you with the right support channels.</p>
            <a href="https://www.bombayengg.net/contact-us/" class="service-bes__cta">
                Contact Our Team
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
            </a>
        </div>
    </aside>
</main>
HTML;

$synopsis = "Get support for Crompton pumps and CG Power motors. Find official helpline numbers, WhatsApp support, and service center contacts. Bombay Engineering Syndicate - Authorized Distributor in Mumbai.";

// Update database
$DB->vals = array($pageContent, $synopsis, 7);
$DB->types = "ssi";
$DB->sql = "UPDATE `" . $DB->pre . "page` SET pageContent = ?, synopsis = ? WHERE pageID = ?";

if ($DB->dbQuery()) {
    echo "SUCCESS: Support page content updated in database!\n";
    echo "- pageContent: " . strlen($pageContent) . " characters\n";
    echo "- synopsis: " . strlen($synopsis) . " characters\n";
    echo "\nYou can now see the content in xadmin Page editor.\n";
} else {
    echo "ERROR: Failed to update database.\n";
}
