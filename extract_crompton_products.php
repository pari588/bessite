<?php
/**
 * Extract products from Crompton website and prepare for import
 * This script scrapes product data, downloads images, and converts them to WebP
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.inc.php';

// Define categories to extract
$cromptonCategories = [
    'shallow-well-pumps' => [
        'name' => 'Shallow Well Pumps',
        'categoryPID' => 26,
        'url' => 'https://www.crompton.co.in/collections/shallow-well-pumps'
    ],
    '3-inch-borewell-submersible' => [
        'name' => '3-Inch Borewell Submersibles',
        'categoryPID' => 27,
        'url' => 'https://www.crompton.co.in/collections/3-inch-borewell-submersible'
    ],
    '4-inch-borewell-submersible' => [
        'name' => '4-Inch Borewell Submersibles',
        'categoryPID' => 28,
        'url' => 'https://www.crompton.co.in/collections/4-inch-borewell-submersible'
    ],
    'openwell-pumps' => [
        'name' => 'Openwell Pumps',
        'categoryPID' => 29,
        'url' => 'https://www.crompton.co.in/collections/openwell-pumps'
    ],
    'booster-pumps' => [
        'name' => 'Booster Pumps',
        'categoryPID' => 30,
        'url' => 'https://www.crompton.co.in/collections/booster-pumps'
    ],
    'control-panels' => [
        'name' => 'Control Panels',
        'categoryPID' => 31,
        'url' => 'https://www.crompton.co.in/collections/control-panels'
    ]
];

// Create uploads directory for pump images if not exists
$uploadDir = UPLOADPATH . '/pump';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Function to sanitize filenames for SEO
function sanitizeFilename($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Function to extract product data from Crompton using cURL
function extractProductsFromCrompton($url, $categoryName) {
    $products = [];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        return $products;
    }

    // Parse HTML using DOMDocument
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Find all product containers
    $productNodes = $xpath->query("//div[contains(@class, 'product-item')] | //div[contains(@class, 'ProductItem')] | //a[contains(@class, 'ProductItem')]");

    foreach ($productNodes as $productNode) {
        $product = extractProductFromNode($productNode);
        if ($product) {
            $products[] = $product;
        }
    }

    return $products;
}

// Function to extract individual product data
function extractProductFromNode($node) {
    try {
        $product = [];

        // Get product title/name
        $titleNode = $node->querySelector('h3') ?? $node->querySelector('a');
        $product['title'] = trim($titleNode ? $titleNode->textContent : '');

        if (!$product['title']) return null;

        // Get product model/SKU from title
        $product['model'] = extractModel($product['title']);

        // Get product image
        $imgNode = $node->querySelector('img');
        if ($imgNode && $imgNode->hasAttribute('src')) {
            $product['image'] = $imgNode->getAttribute('src');
        } elseif ($imgNode && $imgNode->hasAttribute('data-src')) {
            $product['image'] = $imgNode->getAttribute('data-src');
        }

        // Get product price
        $priceNode = $node->querySelector('[class*="price"]');
        $product['price'] = $priceNode ? trim($priceNode->textContent) : '';

        // Get product description/details
        $descNode = $node->querySelector('[class*="desc"]') ?? $node->querySelector('p');
        $product['description'] = $descNode ? trim($descNode->textContent) : '';

        return !empty($product['title']) ? $product : null;
    } catch (Exception $e) {
        return null;
    }
}

// Function to extract model from title
function extractModel($title) {
    // Match patterns like "SWJ100AP-36", "NJPC-32", etc.
    if (preg_match('/([A-Z]+[\d\-]+[A-Z]?)[\s\-]/', $title, $matches)) {
        return trim($matches[1]);
    }
    // Fallback: use first few words
    $parts = explode(' ', $title);
    return isset($parts[0]) ? $parts[0] : '';
}

// Function to download and convert image to WebP
function downloadAndConvertImage($imageUrl, $filename, $uploadDir) {
    try {
        // Handle relative URLs
        if (strpos($imageUrl, 'http') !== 0) {
            $imageUrl = 'https://www.crompton.co.in' . $imageUrl;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $imageUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        $imageData = curl_exec($ch);
        curl_close($ch);

        if (!$imageData) {
            return false;
        }

        // Save original image temporarily
        $tempFile = $uploadDir . '/' . $filename . '.tmp';
        file_put_contents($tempFile, $imageData);

        // Detect image type and convert to WebP
        $mimeType = mime_content_type($tempFile);
        $webpFile = $uploadDir . '/' . $filename . '.webp';

        // Use ImageMagick or GD to convert
        if (extension_loaded('imagick')) {
            try {
                $image = new Imagick($tempFile);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(85);
                $image->writeImage($webpFile);
                $image->destroy();
            } catch (Exception $e) {
                // Fallback to GD
                convertWithGD($tempFile, $webpFile);
            }
        } else {
            convertWithGD($tempFile, $webpFile);
        }

        unlink($tempFile);
        return basename($webpFile);
    } catch (Exception $e) {
        return false;
    }
}

// Function to convert image using GD
function convertWithGD($sourceFile, $destFile) {
    $imageInfo = getimagesize($sourceFile);
    $mimeType = $imageInfo['mime'] ?? '';

    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($sourceFile);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourceFile);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourceFile);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    // WebP conversion requires PHP 7.0+ with webp support
    if (function_exists('imagewebp')) {
        imagewebp($image, $destFile, 85);
        imagedestroy($image);
        return true;
    }

    return false;
}

// Main extraction process
$log = "=== Crompton Product Extraction Log ===\n";
$log .= date('Y-m-d H:i:s') . "\n\n";

$allProducts = [];

foreach ($cromptonCategories as $key => $category) {
    $log .= "Extracting from: {$category['name']}\n";
    $log .= "URL: {$category['url']}\n";

    $products = extractProductsFromCrompton($category['url'], $category['name']);

    $log .= "Found: " . count($products) . " products\n\n";

    foreach ($products as $product) {
        $product['categoryPID'] = $category['categoryPID'];
        $product['categoryName'] = $category['name'];
        $allProducts[] = $product;
    }
}

$log .= "\n=== Total Products Found: " . count($allProducts) . " ===\n";

// Save log
file_put_contents($uploadDir . '/extraction_log.txt', $log);

// Save products as JSON for review
file_put_contents($uploadDir . '/extracted_products.json', json_encode($allProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "<pre>";
echo $log;
echo "\n\nProducts JSON saved to: " . UPLOADURL . "/pump/extracted_products.json";
echo "</pre>";

?>
