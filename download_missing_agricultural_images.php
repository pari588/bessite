<?php
/*
 * Download Crompton Missing Agricultural Pump images and convert to WebP
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$tempDir = $uploadDir . '/crompton_images_temp';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Missing agricultural pump image URLs from Crompton CDN
$products = array(
    // MIK22-18 - 2.2HP Submersible
    array(
        'filename' => 'mik22-18',
        'title' => 'MIK22-18 (2.2HP Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_0f1979ee-bc31-49e1-870e-8cf8179e6915.png?v=1702987765'
    ),
    // MBK22(1PH)-24 - 2.2HP Centrifugal
    array(
        'filename' => 'mbk22-1ph-24',
        'title' => 'MBK22(1PH)-24 (2.2HP Centrifugal)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_c830ffcd-9251-4033-ae1e-b1ae928d7a19.png?v=1702987127'
    ),
    // MBM12(1PH) - 1.2HP Centrifugal
    array(
        'filename' => 'mbm12-1ph',
        'title' => 'MBM12(1PH) (1.2HP Centrifugal)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Copyof1_a6f6129b-7523-4eff-a9ef-ca8e48b875bb.png?v=1702986683'
    ),
    // MBQ22(1PH)-12U - 2.2HP Centrifugal
    array(
        'filename' => 'mbq22-1ph-12u',
        'title' => 'MBQ22(1PH)-12U (2.2HP Centrifugal)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Copyof1_48024bf0-7f60-4337-8d1e-97d7f33d5491.png?v=1702986036'
    ),
    // MBG12(1PH)-21 - 1.2HP Centrifugal
    array(
        'filename' => 'mbg12-1ph-21',
        'title' => 'MBG12(1PH)-21 (1.2HP Centrifugal)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Copyof1_88efbdbb-f786-4c56-9f20-b3975949c528.png?v=1702985201'
    ),
    // MAD12(1PH)Y-30 - 1.2HP Domestic/Agricultural
    array(
        'filename' => 'mad12-1ph-y-30',
        'title' => 'MAD12(1PH)Y-30 (1.2HP Domestic)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/CopyofUntitled-1_74cbac60-3514-43d7-aa38-340faf5d6609.png?v=1702984497'
    ),
    // MAD052(1PH)Y-21+ - 0.5HP Domestic/Agricultural
    array(
        'filename' => 'mad052-1ph-y-21-plus',
        'title' => 'MAD052(1PH)Y-21+ (0.5HP Domestic)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/CopyofUntitled-1_c9838fec-011b-4742-96de-406c7dfdc2eb.png?v=1702984112'
    ),
    // MAD052(1PH)Y-18+ - 0.5HP Domestic/Agricultural
    array(
        'filename' => 'mad052-1ph-y-18-plus',
        'title' => 'MAD052(1PH)Y-18+ (0.5HP Domestic)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/CopyofUntitled-1_b1c6ebdd-c609-433d-b7dd-86d6fdd05902.png?v=1702983605'
    ),
);

echo "\n=== CROMPTON MISSING AGRICULTURAL PUMP IMAGE DOWNLOADER ===\n\n";

$successCount = 0;
$failureCount = 0;

foreach ($products as $product) {
    $filename = $product['filename'];
    $title = $product['title'];
    $url = $product['url'];

    echo "Processing: $title\n";

    $tempFile = $tempDir . '/' . basename($filename) . '_temp.png';

    // Download with curl
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200 || empty($imageData)) {
        echo "  ✗ Download failed (HTTP $httpCode)\n\n";
        $failureCount++;
        continue;
    }

    file_put_contents($tempFile, $imageData);

    // Verify it's a valid image
    $size = getimagesize($tempFile);
    if ($size === false) {
        unlink($tempFile);
        echo "  ✗ Invalid image format\n\n";
        $failureCount++;
        continue;
    }

    echo "  ✓ Downloaded (" . filesize($tempFile) . " bytes, " . $size[0] . "x" . $size[1] . ")\n";

    // Convert to WebP
    echo "  Converting to WebP...\n";

    $mainPath = $uploadDir . '/' . $filename . '.webp';
    $thumbDir_path = $uploadDir . '/235_235_crop_100';
    $largeDir_path = $uploadDir . '/530_530_crop_100';

    // Main image - resize to 530x530
    $cmd = "convert " . escapeshellarg($tempFile) . " -resize 530x530 -background white -gravity center -extent 530x530 -quality 85 -strip " . escapeshellarg($mainPath) . " 2>&1";
    $output = shell_exec($cmd);

    if (file_exists($mainPath) && filesize($mainPath) > 0) {
        echo "    ✓ Main: " . filesize($mainPath) . " bytes\n";

        // Thumbnail
        $thumbPath = $thumbDir_path . '/' . $filename . '.webp';
        $cmd = "convert " . escapeshellarg($mainPath) . " -resize 235x235 -background white -gravity center -extent 235x235 -quality 80 -strip " . escapeshellarg($thumbPath) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($thumbPath)) {
            echo "    ✓ Thumbnail: " . filesize($thumbPath) . " bytes\n";
        }

        // Large variant
        $largePath = $largeDir_path . '/' . $filename . '.webp';
        $cmd = "convert " . escapeshellarg($mainPath) . " -quality 85 -strip " . escapeshellarg($largePath) . " 2>&1";
        shell_exec($cmd);

        if (file_exists($largePath)) {
            echo "    ✓ Large variant: " . filesize($largePath) . " bytes\n";
        }

        $successCount++;
    } else {
        echo "    ✗ Conversion failed\n";
        $failureCount++;
    }

    // Clean up temp file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }

    echo "\n";
}

// Cleanup temp directory
@rmdir($tempDir);

echo "\n=== SUMMARY ===\n";
echo "Successfully processed: $successCount\n";
echo "Failed: $failureCount\n";
echo "\nImage processing complete!\n\n";
?>
