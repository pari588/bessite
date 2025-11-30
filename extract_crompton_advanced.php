<?php
/**
 * Advanced Crompton Product Extractor
 * Extracts products from Crompton website using direct API/scraping
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.inc.php';

// Create uploads directory
$uploadDir = UPLOADPATH . '/pump';
$imageCacheDir = $uploadDir . '/crompton_images';
if (!is_dir($imageCacheDir)) {
    mkdir($imageCacheDir, 0755, true);
}

// Categories mapping
$categoryMapping = [
    'shallow-well-pumps' => 26,
    '3-inch-borewell-submersible' => 27,
    '4-inch-borewell-submersible' => 28,
    'openwell-pumps' => 29,
    'booster-pumps' => 30,
    'control-panels' => 31
];

$cromptonBaseUrl = 'https://www.crompton.co.in/collections';

// Function to fetch page content
function fetchPage($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);

    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode == 200 ? $content : false;
}

// Function to extract products from HTML
function extractProducts($html) {
    $products = [];

    // Try multiple selector patterns
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($dom);

    // Look for product containers - try multiple patterns
    $patterns = [
        "//div[contains(@class, 'ProductItem')]",
        "//a[contains(@class, 'ProductItem')]",
        "//div[contains(@data-type, 'product')]",
        "//div[contains(@class, 'product-tile')]"
    ];

    foreach ($patterns as $pattern) {
        $nodes = $xpath->query($pattern);
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $product = parseProductNode($node, $xpath);
                if ($product && !empty($product['title'])) {
                    $products[] = $product;
                }
            }
            if (count($products) > 0) break;
        }
    }

    return $products;
}

// Function to parse individual product node
function parseProductNode($node, $xpath) {
    try {
        $product = [];

        // Try to get title/name
        $titleNodes = $xpath->query(".//h3 | .//a[@class] | .//span[@data-title]", $node);
        foreach ($titleNodes as $titleNode) {
            $text = trim($titleNode->textContent);
            if (!empty($text) && strlen($text) > 3) {
                $product['title'] = $text;
                break;
            }
        }

        if (empty($product['title'])) return null;

        // Get image
        $imgNodes = $xpath->query(".//img", $node);
        if ($imgNodes->length > 0) {
            $imgNode = $imgNodes->item(0);
            $product['image_url'] = $imgNode->getAttribute('src') ?: $imgNode->getAttribute('data-src');
        }

        // Get price
        $priceNodes = $xpath->query(".//*[contains(@class, 'price')] | .//*[contains(text(), '₹')]", $node);
        if ($priceNodes->length > 0) {
            $product['price'] = trim($priceNodes->item(0)->textContent);
        }

        // Get description/specs
        $descNodes = $xpath->query(".//p | .//div[contains(@class, 'description')]", $node);
        if ($descNodes->length > 0) {
            $product['description'] = trim($descNodes->item(0)->textContent);
        }

        // Extract model from title
        if (preg_match('/([A-Z]{2,}[\d\-]+[A-Z]?)/i', $product['title'], $matches)) {
            $product['model'] = $matches[1];
        } else {
            $product['model'] = substr($product['title'], 0, 20);
        }

        return $product;
    } catch (Exception $e) {
        return null;
    }
}

// Function to download image
function downloadImage($imageUrl, $filename) {
    global $imageCacheDir;

    if (empty($imageUrl)) return false;

    // Handle relative URLs
    if (strpos($imageUrl, 'http') !== 0) {
        $imageUrl = 'https://www.crompton.co.in' . $imageUrl;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $imageUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || empty($imageData)) {
        return false;
    }

    $filepath = $imageCacheDir . '/' . sanitizeFilename($filename) . '.png';

    if (file_put_contents($filepath, $imageData)) {
        return basename($filepath);
    }

    return false;
}

// Function to convert image to WebP
function convertToWebP($sourceFile, $destDir) {
    $sourceFilePath = $destDir . '/' . $sourceFile;
    $destFileName = basename($sourceFile, pathinfo($sourceFile, PATHINFO_EXTENSION)) . '.webp';
    $destFilePath = $destDir . '/' . $destFileName;

    if (!file_exists($sourceFilePath)) {
        return false;
    }

    try {
        if (extension_loaded('imagick')) {
            $image = new Imagick($sourceFilePath);
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(85);
            $image->writeImage($destFilePath);
            $image->destroy();
            unlink($sourceFilePath);
            return $destFileName;
        } else {
            // Use GD
            return convertImageWithGD($sourceFilePath, $destFilePath);
        }
    } catch (Exception $e) {
        return false;
    }
}

// Function to convert with GD
function convertImageWithGD($source, $dest) {
    $imageInfo = @getimagesize($source);
    if (!$imageInfo) return false;

    $mime = $imageInfo['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    if (!$image || !function_exists('imagewebp')) {
        return false;
    }

    imagewebp($image, $dest, 85);
    imagedestroy($image);
    unlink($source);
    return basename($dest);
}

function sanitizeFilename($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

// Main execution
$output = "=== Crompton Product Extraction Report ===\n";
$output .= date('Y-m-d H:i:s') . "\n\n";

$allProducts = [];
$extracted_count = 0;

foreach ($categoryMapping as $slug => $categoryPID) {
    $url = "$cromptonBaseUrl/$slug";
    $output .= "Fetching: $url\n";

    $html = fetchPage($url);
    if (!$html) {
        $output .= "  ✗ Failed to fetch page\n";
        continue;
    }

    $products = extractProducts($html);
    $output .= "  ✓ Found " . count($products) . " products\n";

    foreach ($products as $product) {
        $product['categoryPID'] = $categoryPID;
        $product['slug'] = $slug;

        // Download and convert image
        if (!empty($product['image_url'])) {
            $imageName = sanitizeFilename($product['title']);
            if (downloadImage($product['image_url'], $imageName)) {
                $webpName = convertToWebP($imageName . '.png', $imageCacheDir);
                if ($webpName) {
                    $product['image_file'] = $webpName;
                }
            }
        }

        $allProducts[] = $product;
        $extracted_count++;
    }
}

$output .= "\n=== Summary ===\n";
$output .= "Total Products Extracted: $extracted_count\n";
$output .= "Categories Processed: " . count($categoryMapping) . "\n";

// Save detailed JSON
$jsonFile = $uploadDir . '/crompton_extraction_' . date('YmdHis') . '.json';
file_put_contents($jsonFile, json_encode($allProducts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$output .= "Data saved to: " . basename($jsonFile) . "\n";

// Save summary CSV for review
$csvFile = $uploadDir . '/crompton_products_' . date('YmdHis') . '.csv';
$fp = fopen($csvFile, 'w');
fputcsv($fp, ['Title', 'Model', 'Price', 'Image', 'Category PID', 'URL']);
foreach ($allProducts as $p) {
    fputcsv($fp, [
        $p['title'] ?? '',
        $p['model'] ?? '',
        $p['price'] ?? '',
        $p['image_file'] ?? '',
        $p['categoryPID'] ?? '',
        $p['image_url'] ?? ''
    ]);
}
fclose($fp);
$output .= "CSV saved to: " . basename($csvFile) . "\n";

echo "<pre>" . htmlspecialchars($output) . "</pre>";

?>
