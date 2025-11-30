<?php
// Download and convert DMB-CMB pump images to WebP

$images = [
    'cmb10nv-plus.webp' => 'https://www.crompton.co.in/cdn/shop/files/CMB10NVPLUSPL1.png?v=1729838873&width=533',
    'dmb10d-plus.webp' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1_7a4633b1-75a5-4aa2-a853-36db40d0d5a5.png?v=1729836460&width=533',
    'dmb10dcsl.webp' => 'https://www.crompton.co.in/cdn/shop/files/DMB10DCSLPL1.png?v=1729771509&width=533',
    'cmb05nv-plus.webp' => 'https://www.crompton.co.in/cdn/shop/files/CMB05NVPLUSPL_0.png?v=1729837771&width=533'
];

$uploadDir = __DIR__ . '/uploads/pump/';

echo "=== Downloading DMB-CMB Pump Images ===\n\n";

foreach ($images as $filename => $url) {
    echo "Downloading: $filename\n";
    echo "URL: $url\n";

    // Create temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'dmb_cmb_');

    // Download image
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $imageData = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200 && !empty($imageData)) {
        file_put_contents($tempFile, $imageData);

        // Convert to WebP if not already
        $outputFile = $uploadDir . $filename;

        // Get image type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tempFile);
        finfo_close($finfo);

        echo "  MIME Type: $mimeType\n";

        // If it's PNG, convert to WebP
        if ($mimeType == 'image/png' || strpos($mimeType, 'image') !== false) {
            // Create image from file
            if ($mimeType == 'image/png') {
                $image = imagecreatefrompng($tempFile);
            } elseif ($mimeType == 'image/jpeg') {
                $image = imagecreatefromjpeg($tempFile);
            } else {
                // Try generic creation
                $image = imagecreatefromstring(file_get_contents($tempFile));
            }

            if ($image) {
                // Save as WebP with quality 90
                imagewebp($image, $outputFile, 90);
                imagedestroy($image);
                echo "  ✓ Converted and saved to: $filename\n";
                echo "  Size: " . filesize($outputFile) . " bytes\n\n";
            } else {
                echo "  ✗ Failed to create image from file\n\n";
            }
        } else {
            // Just copy if already WebP
            copy($tempFile, $outputFile);
            echo "  ✓ Saved to: $filename\n";
            echo "  Size: " . filesize($outputFile) . " bytes\n\n";
        }
    } else {
        echo "  ✗ Failed to download (HTTP $httpCode)\n\n";
    }

    // Clean up temp file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

echo "\n=== Verification ===\n\n";
echo "Images in uploads/pump/:\n";

$files = [
    'cmb10nv-plus.webp',
    'dmb10d-plus.webp',
    'dmb10dcsl.webp',
    'cmb05nv-plus.webp'
];

foreach ($files as $file) {
    $path = $uploadDir . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $size_kb = round($size / 1024, 2);
        echo "✓ $file ($size_kb KB)\n";
    } else {
        echo "✗ $file (NOT FOUND)\n";
    }
}

?>
