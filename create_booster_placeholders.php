<?php
/*
 * Create placeholder WebP images for Booster Pumps
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$thumbDir = $uploadDir . '/235_235_crop_100';
$largeDir = $uploadDir . '/530_530_crop_100';

$products = array(
    'cfmsmb3d0-50-v24' => 'CFMSMB3D0.50-V24 - 0.5 HP',
    'mini-force-ii' => 'MINI FORCE II - 0.5 HP'
);

echo "Creating placeholder WebP images for Booster Pumps...\n\n";

foreach ($products as $filename => $label) {
    // Create main image (530x530)
    $mainPath = $uploadDir . '/' . $filename . '.webp';

    $img = imagecreatetruecolor(530, 530);
    imagefilledrectangle($img, 0, 0, 529, 529, imagecolorallocate($img, 220, 200, 180));

    // Gradient effect lines
    $lineColor = imagecolorallocate($img, 180, 120, 80);
    imageline($img, 0, 0, 529, 529, $lineColor);
    imageline($img, 529, 0, 0, 529, $lineColor);

    // Text
    $textColor = imagecolorallocate($img, 40, 40, 40);
    imagestring($img, 5, 80, 230, "PRESSURE BOOSTER PUMP", $textColor);
    imagestring($img, 5, 150, 260, strtoupper($filename), $textColor);
    imagestring($img, 2, 120, 290, "(Placeholder - To be replaced with actual product image)", $textColor);

    if (file_exists($mainPath)) {
        unlink($mainPath);
    }

    ob_start();
    imagewebp($img, null, 85);
    $webp_data = ob_get_clean();
    file_put_contents($mainPath, $webp_data);

    if (filesize($mainPath) > 0) {
        echo "✓ Main: $mainPath (" . filesize($mainPath) . " bytes)\n";
    }
    imagedestroy($img);

    // Create thumbnail (235x235)
    $thumbPath = $thumbDir . '/' . $filename . '.webp';

    $img = imagecreatetruecolor(235, 235);
    imagefilledrectangle($img, 0, 0, 234, 234, imagecolorallocate($img, 220, 200, 180));

    $lineColor = imagecolorallocate($img, 180, 120, 80);
    imageline($img, 0, 0, 234, 234, $lineColor);
    imageline($img, 234, 0, 0, 234, $lineColor);

    $textColor = imagecolorallocate($img, 40, 40, 40);
    imagestring($img, 3, 20, 100, "BOOSTER PUMP", $textColor);
    imagestring($img, 2, 30, 120, strtoupper($filename), $textColor);

    if (file_exists($thumbPath)) {
        unlink($thumbPath);
    }

    ob_start();
    imagewebp($img, null, 80);
    $webp_data = ob_get_clean();
    file_put_contents($thumbPath, $webp_data);

    if (filesize($thumbPath) > 0) {
        echo "✓ Thumb: $thumbPath (" . filesize($thumbPath) . " bytes)\n";
    }
    imagedestroy($img);

    // Create large variant (530x530)
    $largePath = $largeDir . '/' . $filename . '.webp';

    $img = imagecreatetruecolor(530, 530);
    imagefilledrectangle($img, 0, 0, 529, 529, imagecolorallocate($img, 220, 200, 180));

    $lineColor = imagecolorallocate($img, 180, 120, 80);
    imageline($img, 0, 0, 529, 529, $lineColor);
    imageline($img, 529, 0, 0, 529, $lineColor);

    $textColor = imagecolorallocate($img, 40, 40, 40);
    imagestring($img, 5, 80, 230, "PRESSURE BOOSTER PUMP", $textColor);
    imagestring($img, 5, 150, 260, strtoupper($filename), $textColor);
    imagestring($img, 2, 120, 290, "(Placeholder - To be replaced with actual product image)", $textColor);

    if (file_exists($largePath)) {
        unlink($largePath);
    }

    ob_start();
    imagewebp($img, null, 85);
    $webp_data = ob_get_clean();
    file_put_contents($largePath, $webp_data);

    if (filesize($largePath) > 0) {
        echo "✓ Large: $largePath (" . filesize($largePath) . " bytes)\n";
    }
    imagedestroy($img);

    echo "\n";
}

echo "✓ All booster pump placeholder images created successfully!\n";
?>
