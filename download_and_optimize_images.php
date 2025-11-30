<?php
// Download images from Crompton and optimize with ImageMagick
// Keep them clean without adding any background

$images = [
    'cmb10nv-plus' => 'https://www.crompton.co.in/cdn/shop/files/CMB10NVPLUSPL1.png?v=1729838873&width=533',
    'dmb10d-plus' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1_7a4633b1-75a5-4aa2-a853-36db40d0d5a5.png?v=1729836460&width=533',
    'dmb10dcsl' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1.png?v=1729771509&width=533',
    'cmb05nv-plus' => 'https://www.crompton.co.in/cdn/shop/files/CMB05NVPLUSPL_0.png?v=1729837771&width=533'
];

$uploadDir = '/home/bombayengg/public_html/uploads/pump/';

echo "=== Downloading and Optimizing Images with ImageMagick ===\n\n";

foreach ($images as $name => $url) {
    echo "Processing: $name\n";

    // Download original PNG
    $tempPng = "/tmp/{$name}_original.png";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        file_put_contents($tempPng, $imageData);
        echo "  ✓ Downloaded PNG\n";

        // Optimize with ImageMagick
        // Just optimize and convert to WebP, keep transparent areas as-is
        $outputWebp = $uploadDir . $name . '.webp';

        // ImageMagick command:
        // 1. Strip unnecessary metadata
        // 2. Compress with quality 90
        // 3. Keep all transparency intact
        // 4. No background added
        $command = sprintf(
            'convert "%s" -strip -quality 90 -define webp:lossless=false "%s"',
            $tempPng,
            $outputWebp
        );

        shell_exec($command . ' 2>&1');

        if (file_exists($outputWebp)) {
            $size = filesize($outputWebp);
            echo "  ✓ Optimized to WebP (" . round($size / 1024, 2) . " KB)\n";
        } else {
            echo "  ✗ Optimization failed\n";
        }

        unlink($tempPng);
    } else {
        echo "  ✗ Failed to download (HTTP $httpCode)\n";
    }

    echo "\n";
}

echo "\n=== Regenerating Thumbnails ===\n\n";

// Regenerate thumbnails using ImageMagick
// Preserve transparency, no background added
$images_list = ['cmb10nv-plus.webp', 'dmb10d-plus.webp', 'dmb10dcsl.webp', 'cmb05nv-plus.webp'];

// 235x235 Thumbnails
echo "235x235 Thumbnails:\n";
$thumb235Dir = $uploadDir . '235_235_crop_100/';

foreach ($images_list as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb235Dir . $image;

    // Use -trim to remove any whitespace, then resize
    $command = sprintf(
        'convert "%s" -trim +repage -resize 235x235 -quality 90 -define webp:lossless=false "%s"',
        $sourcePath,
        $thumbPath
    );

    shell_exec($command . ' 2>&1');

    if (file_exists($thumbPath)) {
        $size = filesize($thumbPath);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

// 530x530 Thumbnails
echo "\n530x530 Thumbnails:\n";
$thumb530Dir = $uploadDir . '530_530_crop_100/';

foreach ($images_list as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb530Dir . $image;

    // Use -trim to remove any whitespace, then resize
    $command = sprintf(
        'convert "%s" -trim +repage -resize 530x530 -quality 90 -define webp:lossless=false "%s"',
        $sourcePath,
        $thumbPath
    );

    shell_exec($command . ' 2>&1');

    if (file_exists($thumbPath)) {
        $size = filesize($thumbPath);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

echo "\n=== Verification ===\n\n";

echo "Main Images:\n";
foreach ($images_list as $image) {
    $path = $uploadDir . $image;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

echo "\n235x235 Thumbnails:\n";
foreach ($images_list as $image) {
    $path = $thumb235Dir . $image;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

echo "\n530x530 Thumbnails:\n";
foreach ($images_list as $image) {
    $path = $thumb530Dir . $image;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

echo "\n✅ All images downloaded, optimized, and thumbnails generated!\n";

?>
