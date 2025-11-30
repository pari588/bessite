<?php
/*
 * Download Crompton Agricultural Pump images and convert to WebP
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$tempDir = $uploadDir . '/crompton_images_temp';

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Agricultural pump image URLs from Crompton CDN
$products = array(
    // Borewell submersibles
    array(
        'filename' => '100w25ra5tp-50',
        'title' => '100W25RA5TP-50 (100W Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-3_3c2dbb49-6036-49e4-ba29-62dd4df07355.png?v=1732182605'
    ),
    array(
        'filename' => '100w15ra3tp-50',
        'title' => '100W15RA3TP-50 (100W Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-3_3c2dbb49-6036-49e4-ba29-62dd4df07355.png?v=1732182605'
    ),
    array(
        'filename' => '100w12ra3tp-50',
        'title' => '100W12RA3TP-50 (100W Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-3_3c2dbb49-6036-49e4-ba29-62dd4df07355.png?v=1732182605'
    ),
    array(
        'filename' => 'min32-26',
        'title' => 'MIN32-26 (3HP Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_7ec22af3-c47b-4cfe-9a21-3110a108c2f4.png?v=1704169895'
    ),
    array(
        'filename' => 'mik32-27',
        'title' => 'MIK32-27 (3HP Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_9dca5208-a59d-42a3-84ab-00b2175c9e84.png?v=1702988036'
    ),
    array(
        'filename' => 'mip52-27',
        'title' => 'MIP52-27 (5HP Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_d17ca48b-9dfc-4186-87e7-c3cbcabdae53.png?v=1704193205'
    ),
    array(
        'filename' => 'minh52-30',
        'title' => 'MINH52-30 (5HP Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_ea250116-b2e8-4d63-b70d-57c722d3d8fc.png?v=1704194199'
    ),
    array(
        'filename' => 'mip7-52-30',
        'title' => 'MIP7.52-30 (7.5HP Submersible)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/1_f05841be-5a6d-4818-b599-2d3b9fd16508.png?v=1704194766'
    ),

    // Centrifugal
    array(
        'filename' => 'mbg1-52',
        'title' => 'MBG1.52 (1HP Centrifugal)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_3e73c59d-b235-4fbc-b029-9128c5ee35d7.png?v=1702988573'
    ),
    array(
        'filename' => 'mbg12-3phase',
        'title' => 'MBG12(3PHASE) (1HP Centrifugal)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_4154eeb0-8516-4a01-af2f-dbb3dbdb1ea5.png?v=1702387410'
    ),

    // Open Well
    array(
        'filename' => 'mbq22-1ph-14',
        'title' => 'MBQ22-1PH-14 (1HP Open Well)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/Untitled-1_5aa81c1c-554d-4098-a1e6-9e0da280f8b5.png?v=1704195437'
    ),
    array(
        'filename' => 'mad052-1ph-y-14',
        'title' => 'MAD052(1PH)Y-14 (0.5HP Domestic)',
        'url' => 'https://www.crompton.co.in/cdn/shop/files/CopyofUntitled-1_05ac0669-5357-4da2-8a5c-b2a8b88ec380.png?v=1702982394'
    ),
);

echo "\n=== CROMPTON AGRICULTURAL PUMP IMAGE DOWNLOADER ===\n\n";

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
