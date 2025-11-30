#!/usr/bin/env php
<?php
/**
 * Extract Actual Shallow Well Pump Images from Crompton Website
 */

echo "\n" . str_repeat("=", 90) . "\n";
echo "EXTRACTING SHALLOW WELL PUMP IMAGES FROM CROMPTON.CO.IN\n";
echo str_repeat("=", 90) . "\n\n";

// Crompton product pages for shallow well pumps
$productPages = [
    'SWJ100AP-36 PLUS' => 'https://www.crompton.co.in/products/swj100ap-36-plus-shallow-well-jet-pump',
    'SWJ100A-36 PLUS' => 'https://www.crompton.co.in/products/swj100a-36-plus-shallow-well-jet-pump',
    'SWJ50AP-30 PLUS' => 'https://www.crompton.co.in/products/swj50ap-30-plus-shallow-well-pump',
    'SWJ50A-30 PLUS' => 'https://www.crompton.co.in/products/swj50a-30-plus-shallow-well-pump',
];

$uploadDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';

echo "Fetching product pages from Crompton...\n\n";

foreach ($productPages as $productName => $url) {
    echo "Processing: $productName\n";
    echo "  URL: $url\n";
    
    // Fetch the product page
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]
    ]);
    
    $html = @file_get_contents($url, false, $context);
    
    if ($html === false) {
        echo "  ✗ Failed to fetch page\n\n";
        continue;
    }
    
    // Extract image URLs from the page
    // Look for product images in common patterns
    $patterns = [
        '/"image":\s*"([^"]*\.(?:jpg|jpeg|png|webp)[^"]*)"/i',
        '/<img[^>]*src="([^"]*products[^"]*\.(?:jpg|jpeg|png|webp))"/i',
        '/src="([^"]*cdn[^"]*(?:jpg|jpeg|png|webp))"/i',
        '/<meta[^>]*property="og:image"[^>]*content="([^"]*)"/i'
    ];
    
    $imageUrls = [];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            $imageUrls = array_merge($imageUrls, $matches[1]);
        }
    }
    
    // Clean up and filter image URLs
    $imageUrls = array_unique($imageUrls);
    $validImages = [];
    
    foreach ($imageUrls as $imgUrl) {
        // Clean up URL
        $imgUrl = trim($imgUrl);
        
        // Skip tracking pixels and small images
        if (empty($imgUrl) || strpos($imgUrl, 'tracking') !== false || strpos($imgUrl, '1x1') !== false) {
            continue;
        }
        
        // Make absolute URL if needed
        if (strpos($imgUrl, 'http') === false) {
            if (strpos($imgUrl, '/') === 0) {
                $imgUrl = 'https://www.crompton.co.in' . $imgUrl;
            } else {
                $imgUrl = 'https://www.crompton.co.in/' . $imgUrl;
            }
        }
        
        $validImages[] = $imgUrl;
    }
    
    echo "  Found " . count($validImages) . " image URLs\n";
    
    if (empty($validImages)) {
        echo "  ✗ No product images found\n\n";
        continue;
    }
    
    // Download the first (largest) valid image
    $downloaded = false;
    
    foreach ($validImages as $index => $imgUrl) {
        echo "  Trying image " . ($index + 1) . ": " . substr($imgUrl, 0, 80) . "...\n";
        
        $imageData = @file_get_contents($imgUrl, false, $context);
        
        if ($imageData && strlen($imageData) > 5000) {  // At least 5KB
            // Create filename from product name
            $filename = strtolower(str_replace(' ', '-', $productName));
            $filename = preg_replace('/[^a-z0-9-]/', '', $filename);
            $filename = $filename . '.jpg';
            
            $filepath = $uploadDir . $filename;
            file_put_contents($filepath, $imageData);
            
            // Verify it's a valid image
            $imageInfo = @getimagesize($filepath);
            
            if ($imageInfo !== false && $imageInfo[0] > 100 && $imageInfo[1] > 100) {
                echo "  ✓ Downloaded: $filename (" . number_format(strlen($imageData)) . " bytes)\n";
                $downloaded = true;
                break;
            } else {
                unlink($filepath);
                echo "  ✗ Not a valid image\n";
            }
        }
    }
    
    if (!$downloaded) {
        echo "  ✗ Failed to download valid image\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 90) . "\n";
echo "Image extraction complete!\n";
echo str_repeat("=", 90) . "\n\n";

?>
