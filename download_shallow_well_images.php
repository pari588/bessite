#!/usr/bin/env php
<?php
/**
 * Download Actual Shallow Well Pump Images from Crompton
 */

echo "\n" . str_repeat("=", 90) . "\n";
echo "DOWNLOADING SHALLOW WELL PUMP IMAGES FROM CROMPTON\n";
echo str_repeat("=", 90) . "\n\n";

$uploadDir = '/home/bombayengg/public_html/uploads/pump/crompton_images/';

// Product image URLs from Crompton website
// These are actual product image URLs
$productImages = [
    [
        'name' => 'SWJ100AP-36 PLUS',
        'filename' => 'swj100ap-36-plus.webp',
        'urls' => [
            'https://www.crompton.co.in/cdn/shop/products/swj100ap-36-plus-shallow-well-jet-pump.jpg',
            'https://www.crompton.co.in/cdn/shop/products/SWJ100AP.jpg',
        ]
    ],
    [
        'name' => 'SWJ100A-36 PLUS',
        'filename' => 'swj100a-36-plus.webp',
        'urls' => [
            'https://www.crompton.co.in/cdn/shop/products/swj100a-36-plus-shallow-well-pump.jpg',
            'https://www.crompton.co.in/cdn/shop/products/SWJ100A.jpg',
        ]
    ],
    [
        'name' => 'SWJ50AP-30 PLUS',
        'filename' => 'swj50ap-30-plus.webp',
        'urls' => [
            'https://www.crompton.co.in/cdn/shop/products/swj50ap-30-plus-shallow-well-pump.jpg',
            'https://www.crompton.co.in/cdn/shop/products/SWJ50AP.jpg',
        ]
    ],
    [
        'name' => 'SWJ50A-30 PLUS',
        'filename' => 'swj50a-30-plus.webp',
        'urls' => [
            'https://www.crompton.co.in/cdn/shop/products/swj50a-30-plus-shallow-well-pump.jpg',
            'https://www.crompton.co.in/cdn/shop/products/SWJ50A.jpg',
        ]
    ]
];

$downloadedCount = 0;
$failedCount = 0;

foreach ($productImages as $product) {
    echo "Downloading: " . $product['name'] . "...\n";
    
    $downloaded = false;
    
    foreach ($product['urls'] as $url) {
        echo "  Trying: " . $url . "...\n";
        
        // Download with timeout and user agent
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData !== false && strlen($imageData) > 0) {
            // Save temporary file
            $tempFile = $uploadDir . 'temp_' . uniqid() . '.jpg';
            file_put_contents($tempFile, $imageData);
            
            // Check if it's a valid image
            $imageInfo = @getimagesize($tempFile);
            
            if ($imageInfo !== false) {
                // Convert to WebP
                $image = @imagecreatefromjpeg($tempFile);
                if (!$image) {
                    $image = @imagecreatefrompng($tempFile);
                }
                
                if ($image) {
                    $webpPath = $uploadDir . $product['filename'];
                    imagewebp($image, $webpPath, 80);
                    imagedestroy($image);
                    
                    if (file_exists($webpPath)) {
                        $fileSize = filesize($webpPath);
                        echo "  ✓ Downloaded and converted: " . $product['filename'] . " (" . number_format($fileSize) . " bytes)\n";
                        $downloaded = true;
                        $downloadedCount++;
                        @unlink($tempFile);
                        break;
                    }
                }
            }
            @unlink($tempFile);
        }
    }
    
    if (!$downloaded) {
        echo "  ✗ Failed to download from all sources\n";
        $failedCount++;
    }
    echo "\n";
}

echo str_repeat("=", 90) . "\n";
echo "SUMMARY:\n";
echo "Successfully downloaded: $downloadedCount/4\n";
echo "Failed: $failedCount/4\n";
echo str_repeat("=", 90) . "\n\n";

if ($failedCount > 0) {
    echo "NOTE: If images failed to download, you can manually:\n";
    echo "1. Download from: https://www.crompton.co.in/products/shallow-well-pumps\n";
    echo "2. Place in: /uploads/pump/crompton_images/\n";
    echo "3. Name them: swj{model}.webp\n\n";
}

?>
