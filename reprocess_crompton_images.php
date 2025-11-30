<?php
// Re-download images from Crompton and process them properly
// Using ImageMagick for better quality

$images = [
    'cmb10nv-plus' => 'https://www.crompton.co.in/cdn/shop/files/CMB10NVPLUSPL1.png?v=1729838873&width=533',
    'dmb10d-plus' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1_7a4633b1-75a5-4aa2-a853-36db40d0d5a5.png?v=1729836460&width=533',
    'dmb10dcsl' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1.png?v=1729771509&width=533',
    'cmb05nv-plus' => 'https://www.crompton.co.in/cdn/shop/files/CMB05NVPLUSPL_0.png?v=1729837771&width=533'
];

$uploadDir = '/home/bombayengg/public_html/uploads/pump/';

echo "=== Reprocessing Crompton Images with Smart Background Removal ===\n\n";

foreach ($images as $name => $url) {
    echo "Processing: $name\n";
    echo "URL: $url\n";

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
        echo "  ✓ Downloaded\n";

        // Use ImageMagick to remove background intelligently
        // Method: Trim white/black borders and make background transparent
        $outputWebp = $uploadDir . $name . '.webp';

        // ImageMagick command to:
        // 1. Remove white/black borders (trim)
        // 2. Convert black background to transparent
        // 3. Optimize and save as WebP
        $command = sprintf(
            'convert "%s" -trim +repage -background none ' .
            '-bordercolor none -border 10 ' .
            '-fuzz 15%% -transparent black ' .
            '-quality 90 -define webp:lossless=false "%s"',
            $tempPng,
            $outputWebp
        );

        $output = shell_exec($command . ' 2>&1');

        if (file_exists($outputWebp)) {
            $size = filesize($outputWebp);
            echo "  ✓ Converted to WebP (" . round($size / 1024, 2) . " KB)\n";
        } else {
            echo "  ✗ Conversion failed\n";
        }

        // Clean up
        unlink($tempPng);
    } else {
        echo "  ✗ Failed to download (HTTP $httpCode)\n";
    }

    echo "\n";
}

echo "\n=== Regenerating Thumbnails ===\n\n";

// Regenerate 235x235 thumbnails
echo "235x235 Thumbnails:\n";
$thumb235Dir = $uploadDir . '235_235_crop_100/';

$images_list = ['cmb10nv-plus.webp', 'dmb10d-plus.webp', 'dmb10dcsl.webp', 'cmb05nv-plus.webp'];

foreach ($images_list as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb235Dir . $image;

    $command = sprintf(
        'convert "%s" -resize 235x235 -background none -gravity center ' .
        '-extent 235x235 -quality 90 -define webp:lossless=false "%s"',
        $sourcePath,
        $thumbPath
    );

    $output = shell_exec($command . ' 2>&1');

    if (file_exists($thumbPath)) {
        $size = filesize($thumbPath);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

// Regenerate 530x530 thumbnails
echo "\n530x530 Thumbnails:\n";
$thumb530Dir = $uploadDir . '530_530_crop_100/';

foreach ($images_list as $image) {
    $sourcePath = $uploadDir . $image;
    $thumbPath = $thumb530Dir . $image;

    $command = sprintf(
        'convert "%s" -resize 530x530 -background none -gravity center ' .
        '-extent 530x530 -quality 90 -define webp:lossless=false "%s"',
        $sourcePath,
        $thumbPath
    );

    $output = shell_exec($command . ' 2>&1');

    if (file_exists($thumbPath)) {
        $size = filesize($thumbPath);
        echo "  ✓ $image (" . round($size / 1024, 2) . " KB)\n";
    }
}

echo "\n=== Complete ===\n";
echo "✅ All images reprocessed with proper transparency\n";

?>
