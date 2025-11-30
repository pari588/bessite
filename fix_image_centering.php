<?php
// Download images and create thumbnails WITHOUT aggressive cropping
// Keep products centered and properly framed

$images = [
    'cmb10nv-plus' => 'https://www.crompton.co.in/cdn/shop/files/CMB10NVPLUSPL1.png?v=1729838873&width=533',
    'dmb10d-plus' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1_7a4633b1-75a5-4aa2-a853-36db40d0d5a5.png?v=1729836460&width=533',
    'dmb10dcsl' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1.png?v=1729771509&width=533',
    'cmb05nv-plus' => 'https://www.crompton.co.in/cdn/shop/files/CMB05NVPLUSPL_0.png?v=1729837771&width=533'
];

$uploadDir = '/home/bombayengg/public_html/uploads/pump/';

echo "=== Downloading and Optimizing Images (Centered, No Aggressive Cropping) ===\n\n";

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
        // NO trim - keep original framing
        // Just strip metadata and compress
        $outputWebp = $uploadDir . $name . '.webp';

        $command = sprintf(
            'convert "%s" -strip -quality 90 -define webp:lossless=false "%s"',
            $tempPng,
            $outputWebp
        );

        shell_exec($command . ' 2>&1');

        if (file_exists($outputWebp)) {
            $size = filesize($outputWebp);
            echo "  ✓ Optimized to WebP (" . round($size / 1024, 2) . " KB)\n";
        }

        unlink($tempPng);
    } else {
        echo "  ✗ Failed to download (HTTP $httpCode)\n";
    }

    echo "\n";
}

echo "\n=== Regenerating Thumbnails (Centered, Properly Sized) ===\n\n";

$images_list = ['cmb10nv-plus.webp', 'dmb10d-plus.webp', 'dmb10dcsl.webp', 'cmb05nv-plus.webp'];

// 235x235 Thumbnails - Proper centering without aggressive crop
echo "235x235 Thumbnails (Centered):\n";
$thumb235Dir = $uploadDir . '235_235_crop_100/';

foreach ($images_list as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb235Dir . $image;

    // Resize to fit in 235x235 without distortion, centered
    // -background none: transparent background
    // -gravity center: center the image
    // -extent 235x235: create 235x235 canvas
    $command = sprintf(
        'convert "%s" -background none -gravity center -resize 235x235 -extent 235x235 -quality 90 -define webp:lossless=false "%s"',
        $sourcePath,
        $thumbPath
    );

    shell_exec($command . ' 2>&1');

    if (file_exists($thumbPath)) {
        $size = filesize($thumbPath);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

// 530x530 Thumbnails - Proper centering without aggressive crop
echo "\n530x530 Thumbnails (Centered):\n";
$thumb530Dir = $uploadDir . '530_530_crop_100/';

foreach ($images_list as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb530Dir . $image;

    // Resize to fit in 530x530 without distortion, centered
    $command = sprintf(
        'convert "%s" -background none -gravity center -resize 530x530 -extent 530x530 -quality 90 -define webp:lossless=false "%s"',
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

echo "\n✅ All images centered and properly sized!\n";

?>
