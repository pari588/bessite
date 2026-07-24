<?php
/**
 * Pump Product Schema Markup Generator
 * Generates JSON-LD structured data for Product and BreadcrumbList schemas
 */

/**
 * Generate Product Schema JSON-LD for pump or motor detail pages
 * Works with both pump and motor products
 */
if (!function_exists('generatePumpProductSchema')) {
    function generatePumpProductSchema($productData, $detailData = null)
    {
        // Support both pump and motor products
        $productID = !empty($productData['pumpID']) ? $productData['pumpID'] : (!empty($productData['motorID']) ? $productData['motorID'] : null);
        if (empty($productData) || empty($productID)) {
            return '';
        }

        $baseUrl = SITEURL;
        $uploadUrl = UPLOADURL;

        // Determine product type and get correct fields
        $isMotor = !empty($productData['motorID']);
        $isPump = !empty($productData['pumpID']);

        // Trim description at sentence boundary (not mid-word). Up to 300 chars, ending at full stop.
        $trimAtSentence = function ($text, $maxLen = 300) {
            $text = trim(strip_tags($text));
            if (strlen($text) <= $maxLen) return $text;
            $cut = substr($text, 0, $maxLen);
            $lastStop = strrpos($cut, '.');
            return $lastStop !== false && $lastStop > $maxLen * 0.5
                ? substr($cut, 0, $lastStop + 1)
                : rtrim(substr($cut, 0, strrpos($cut, ' '))) . '…';
        };

        if ($isMotor) {
            $title = $productData['motorTitle'] ?? '';
            $description = $trimAtSentence($productData['motorDesc'] ?? '', 300);
            $image = !empty($productData['motorImage']) ? $uploadUrl . '/motor/530_530_crop_100/' . $productData['motorImage'] : $baseUrl . '/images/logo.png';
            $category = $productData['motorCategoryTitle'] ?? $productData['categoryTitle'] ?? 'Industrial Motors';
        } else {
            $title = $productData['pumpTitle'] ?? '';
            $description = $trimAtSentence($productData['pumpFeatures'] ?? '', 300);
            $image = !empty($productData['pumpImage']) ? $uploadUrl . '/pump/530_530_crop_100/' . $productData['pumpImage'] : $baseUrl . '/images/logo.png';
            $category = $productData['pumpType'] ?? $productData['categoryTitle'] ?? 'Water Pumps';
        }

        // Business decision (2026-07): prices are NOT published anywhere — the site
        // shows a "Call for Price" CTA instead. Structured data must match the visible
        // page, so no price is ever taken from the mrp field for schema output.
        $price = null;  // null = no public price
        $availability = 'https://schema.org/InStock';
        $priceCurrency = 'INR';

        // Google requires at least one of offers / review / aggregateRating on a
        // Product item; without a price (quote-based products) the item is flagged
        // invalid in Search Console. Emit no Product schema in that case.
        if ($price === null && empty($detailData['rating'])) {
            return '';
        }

        // Build absolute offer URL
        $offerUrl = $baseUrl . ($_SERVER['REQUEST_URI'] ?? '/');
        // Strip query string for canonical
        if (strpos($offerUrl, '?') !== false) {
            $offerUrl = substr($offerUrl, 0, strpos($offerUrl, '?'));
        }

        // SKU should be URL-safe and reasonably short (Google rejects special chars and very long values).
        // Use the seoUri (already sanitised: lowercase alphanumeric + hyphens) instead of raw title.
        // The original title still appears as MPN — Manufacturer Part Number — which allows freer formatting.
        $skuValue = $productData['seoUri'] ?? $title;
        // Hard cap at 60 chars as a safety belt for SKU length validation
        if (strlen($skuValue) > 60) {
            $skuValue = substr($skuValue, 0, 60);
        }

        // Build Product Schema
        $schema = array(
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $title,
            "sku" => $skuValue,         // URL-safe identifier (slug)
            "mpn" => $title,            // Manufacturer Part Number (raw model name)
            "category" => $category,
            "description" => $description,
            "image" => $image,
            "brand" => array(
                "@type" => "Brand",
                "name" => "Crompton"
            ),
            "manufacturer" => array(
                "@type" => "Organization",
                "name" => "Crompton Greaves"
            ),
            // Only include offers when we have a real numeric price
            // (Google rejects Product schema with non-numeric price values)
            "offers" => $price !== null ? array(
                "@type" => "Offer",
                "url" => $offerUrl,
                "priceCurrency" => $priceCurrency,
                "price" => number_format($price, 2, '.', ''),
                "availability" => $availability,
                "seller" => array(
                    "@type" => "Organization",
                    "name" => "Bombay Engineering Syndicate",
                    "url" => $baseUrl
                ),
                "shippingDetails" => array(
                    "@type" => "OfferShippingDetails",
                    "shippingRate" => array(
                        "@type" => "MonetaryAmount",
                        // Freight charged at 1% of product price
                        "value" => number_format($price * 0.01, 2, '.', ''),
                        "currency" => "INR"
                    ),
                    "shippingDestination" => array(
                        "@type" => "DefinedRegion",
                        "addressCountry" => "IN"
                    ),
                    "deliveryTime" => array(
                        "@type" => "ShippingDeliveryTime",
                        "handlingTime" => array(
                            "@type" => "QuantitativeValue",
                            "minValue" => 1,
                            "maxValue" => 2,
                            "unitCode" => "DAY"
                        ),
                        "transitTime" => array(
                            "@type" => "QuantitativeValue",
                            "minValue" => 2,
                            "maxValue" => 7,
                            "unitCode" => "DAY"
                        )
                    )
                ),
                "hasMerchantReturnPolicy" => array(
                    "@type" => "MerchantReturnPolicy",
                    "applicableCountry" => "IN",
                    "returnPolicyCategory" => "https://schema.org/MerchantReturnNotPermitted"
                )
            ) : null,
        );
        // Drop null offers (when no price was known) so JSON doesn't include "offers": null
        if ($schema['offers'] === null) {
            unset($schema['offers']);
        }

        // Add rating if available (optional)
        if (!empty($detailData['rating'])) {
            $schema["aggregateRating"] = array(
                "@type" => "AggregateRating",
                "ratingValue" => $detailData['rating'],
                "ratingCount" => 1
            );
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

/**
 * Generate BreadcrumbList Schema JSON-LD for pump pages
 */
if (!function_exists('generatePumpBreadcrumbSchema')) {
    function generatePumpBreadcrumbSchema($breadcrumbs = array())
    {
        if (empty($breadcrumbs)) {
            return '';
        }

        $baseUrl = SITEURL;
        $itemListElement = array();
        $position = 1;

        // Add Home link
        $itemListElement[] = array(
            "@type" => "ListItem",
            "position" => $position++,
            "name" => "Home",
            "item" => $baseUrl . "/"
        );

        // Add provided breadcrumbs — coerce any relative URLs to absolute
        // (Schema.org requires fully qualified URLs for item / @id fields)
        foreach ($breadcrumbs as $breadcrumb) {
            $url = $breadcrumb['url'];
            if (!empty($url) && stripos($url, 'http') !== 0) {
                $url = $baseUrl . '/' . ltrim($url, '/');
            }
            $itemListElement[] = array(
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $breadcrumb['name'],
                "item" => $url
            );
        }

        $schema = array(
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $itemListElement
        );

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

/**
 * Output Product Schema as HTML script tag
 */
if (!function_exists('echoProductSchema')) {
    function echoProductSchema($pumpData, $detailData = null)
    {
        $schema = generatePumpProductSchema($pumpData, $detailData);
        if (!empty($schema)) {
            echo "\n<!-- Product Schema (JSON-LD) -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $schema;
            echo "\n" . '</script>' . "\n";
        }
    }
}

/**
 * Output BreadcrumbList Schema as HTML script tag
 */
if (!function_exists('echoBreadcrumbSchema')) {
    function echoBreadcrumbSchema($breadcrumbs = array())
    {
        $schema = generatePumpBreadcrumbSchema($breadcrumbs);
        if (!empty($schema)) {
            echo "\n<!-- BreadcrumbList Schema (JSON-LD) -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $schema;
            echo "\n" . '</script>' . "\n";
        }
    }
}

/**
 * Generate Article schema JSON-LD for Knowledge Center articles
 * Includes embedded FAQPage if any "Q: / A:" or "?:" Q&A pairs are detected
 */
if (!function_exists('generateArticleSchema')) {
    function generateArticleSchema($kCenter, $articleUrl)
    {
        if (empty($kCenter) || empty($kCenter['knowledgeCenterTitle'])) {
            return '';
        }

        $baseUrl = SITEURL;
        $uploadUrl = UPLOADURL;

        $title = $kCenter['knowledgeCenterTitle'];
        $rawContent = strip_tags($kCenter['knowledgeCenterContent'] ?? '');
        $rawContent = preg_replace('/\s+/', ' ', $rawContent);
        $description = !empty($kCenter['synopsis'])
            ? trim(strip_tags($kCenter['synopsis']))
            : trim(substr($rawContent, 0, 250));
        // Word count for AI engines (signal of depth)
        $wordCount = str_word_count($rawContent);

        $image = !empty($kCenter['knowledgeCenterImage'])
            ? $uploadUrl . '/knowledge-center/' . $kCenter['knowledgeCenterImage']
            : $baseUrl . '/images/logo.png';

        $datePublished = !empty($kCenter['datePublish'])
            ? date('c', strtotime($kCenter['datePublish']))
            : date('c');

        $schema = array(
            "@context"        => "https://schema.org",
            "@type"           => "TechArticle",
            "headline"        => $title,
            "description"     => $description,
            "image"           => $image,
            "datePublished"   => $datePublished,
            "dateModified"    => $datePublished,
            "wordCount"       => $wordCount,
            "inLanguage"      => "en-IN",
            "articleSection"  => "Knowledge Center",
            "author"          => array(
                "@type" => "Organization",
                "name"  => "Bombay Engineering Syndicate",
                "url"   => $baseUrl
            ),
            "publisher"       => array(
                "@type" => "Organization",
                "name"  => "Bombay Engineering Syndicate",
                "logo"  => array(
                    "@type" => "ImageObject",
                    "url"   => $baseUrl . "/images/logo.png"
                )
            ),
            "mainEntityOf Page" => array(
                "@type" => "WebPage",
                "@id"   => $articleUrl
            )
        );
        // Fix the key (PHP array key can't have space — using string above for clarity, replace here)
        unset($schema["mainEntityOf Page"]);
        $schema["mainEntityOfPage"] = array(
            "@type" => "WebPage",
            "@id"   => $articleUrl
        );

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

/**
 * Generate FAQPage schema from article HTML — extracts <h*>?</h*> + <p> patterns
 * Returns empty string if no Q&A pattern found
 */
if (!function_exists('generateFAQSchema')) {
    function generateFAQSchema($htmlContent)
    {
        if (empty($htmlContent)) return '';

        $faqs = array();

        // Pattern 1: <strong>Q: ...</strong> followed by <p>...</p> (manual Q&A blocks)
        if (preg_match_all('/<strong>\s*Q[:.]?\s*([^<]+)<\/strong>\s*(?:<br[^>]*>\s*)?<p[^>]*>(.*?)<\/p>/is', $htmlContent, $m1, PREG_SET_ORDER)) {
            foreach ($m1 as $match) {
                $q = trim(strip_tags($match[1]));
                $a = trim(strip_tags($match[2]));
                if (strlen($q) > 5 && strlen($a) > 10) {
                    $faqs[] = array('q' => $q, 'a' => $a);
                }
            }
        }

        // Pattern 2: Headings ending in "?" followed by paragraph
        if (empty($faqs) && preg_match_all('/<(h[2-5])[^>]*>([^<]*\?)<\/\1>\s*(?:<p[^>]*>(.*?)<\/p>)/is', $htmlContent, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $match) {
                $q = trim(strip_tags($match[2]));
                $a = trim(strip_tags($match[3]));
                if (strlen($q) > 5 && strlen($a) > 10) {
                    $faqs[] = array('q' => $q, 'a' => $a);
                }
            }
        }

        if (empty($faqs)) return '';

        $mainEntity = array();
        foreach ($faqs as $faq) {
            $mainEntity[] = array(
                "@type" => "Question",
                "name"  => $faq['q'],
                "acceptedAnswer" => array(
                    "@type" => "Answer",
                    "text"  => $faq['a']
                )
            );
        }

        $schema = array(
            "@context" => "https://schema.org",
            "@type"    => "FAQPage",
            "mainEntity" => $mainEntity
        );

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

/**
 * Generate ItemList schema for category listing pages
 * $products: array of product rows with at least 'seoUri' (or 'pumpSlug'/'motorSlug') and title
 * $catSlug:  the category's URL path (e.g. "pump/residential-pumps/3-inch-borewell")
 * $type:     "pump" or "motor"
 */
if (!function_exists('generateItemListSchema')) {
    function generateItemListSchema($products, $catSlug, $type = 'pump')
    {
        if (empty($products) || !is_array($products)) return '';

        $baseUrl = SITEURL;
        $items = array();
        $position = 1;

        foreach ($products as $p) {
            $titleField = ($type === 'motor') ? 'motorTitle' : 'pumpTitle';
            $slugField  = ($type === 'motor') ? 'motorSeoUri' : 'pumpSeoUri';

            $title = $p[$titleField] ?? $p['title'] ?? '';
            // Fallback to common keys
            $slug  = $p[$slugField] ?? $p['seoUri'] ?? $p['slug'] ?? '';
            if (empty($title) || empty($slug)) continue;

            $items[] = array(
                "@type"    => "ListItem",
                "position" => $position++,
                "url"      => $baseUrl . '/' . trim($catSlug, '/') . '/' . $slug . '/',
                "name"     => $title
            );
        }

        if (empty($items)) return '';

        $schema = array(
            "@context"        => "https://schema.org",
            "@type"           => "ItemList",
            "itemListOrder"   => "https://schema.org/ItemListOrderAscending",
            "numberOfItems"   => count($items),
            "itemListElement" => $items
        );

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

if (!function_exists('echoItemListSchema')) {
    function echoItemListSchema($products, $catSlug, $type = 'pump')
    {
        $schema = generateItemListSchema($products, $catSlug, $type);
        if (!empty($schema)) {
            echo "\n<!-- ItemList Schema (JSON-LD) -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $schema;
            echo "\n" . '</script>' . "\n";
        }
    }
}

/**
 * Output Article + FAQ schemas as HTML script tags
 */
if (!function_exists('echoArticleSchema')) {
    function echoArticleSchema($kCenter, $articleUrl)
    {
        $articleSchema = generateArticleSchema($kCenter, $articleUrl);
        if (!empty($articleSchema)) {
            echo "\n<!-- TechArticle Schema (JSON-LD) -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $articleSchema;
            echo "\n" . '</script>' . "\n";
        }

        $faqSchema = generateFAQSchema($kCenter['knowledgeCenterContent'] ?? '');
        if (!empty($faqSchema)) {
            echo "\n<!-- FAQPage Schema (JSON-LD) -->\n";
            echo '<script type="application/ld+json">' . "\n";
            echo $faqSchema;
            echo "\n" . '</script>' . "\n";
        }
    }
}

?>
