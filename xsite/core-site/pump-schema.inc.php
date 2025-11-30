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

        if ($isMotor) {
            $title = $productData['motorTitle'] ?? '';
            $description = substr(strip_tags($productData['motorDesc'] ?? ''), 0, 160);
            $image = !empty($productData['motorImage']) ? $uploadUrl . '/motor/530_530_crop_100/' . $productData['motorImage'] : $baseUrl . '/images/logo.png';
        } else {
            $title = $productData['pumpTitle'] ?? '';
            $description = substr(strip_tags($productData['pumpFeatures'] ?? ''), 0, 160);
            $image = !empty($productData['pumpImage']) ? $uploadUrl . '/pump/530_530_crop_100/' . $productData['pumpImage'] : $baseUrl . '/images/logo.png';
        }

        // Get price from detail record if available
        $price = 'Contact for Price';
        $availability = 'https://schema.org/InStock';
        $priceCurrency = 'INR';

        if (!empty($detailData) && is_array($detailData)) {
            // Extract price from mrp field (e.g., "₹12,025.00" -> "12025.00")
            if (!empty($detailData['mrp'])) {
                $mrp = $detailData['mrp'];
                // Remove currency symbol and commas
                $price = str_replace(['₹', ','], '', $mrp);
                $price = floatval($price);
            }
        }

        // Build Product Schema
        $schema = array(
            "@context" => "https://schema.org/",
            "@type" => "Product",
            "name" => $title,
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
            "offers" => array(
                "@type" => "Offer",
                "url" => $_SERVER['REQUEST_URI'] ?? $baseUrl,
                "priceCurrency" => $priceCurrency,
                "price" => is_numeric($price) ? $price : "Contact for Price",
                "availability" => $availability,
                "seller" => array(
                    "@type" => "Organization",
                    "name" => "Bombay Engineering Syndicate",
                    "url" => $baseUrl
                )
            )
        );

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

        // Add provided breadcrumbs
        foreach ($breadcrumbs as $breadcrumb) {
            $itemListElement[] = array(
                "@type" => "ListItem",
                "position" => $position++,
                "name" => $breadcrumb['name'],
                "item" => $breadcrumb['url']
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

?>
