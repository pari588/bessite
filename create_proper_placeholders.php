<?php
/*
 * Create proper placeholder WebP images using GD with truecolor
 */

$uploadDir = '/home/bombayengg/public_html/uploads/pump';
$thumbDir = $uploadDir . '/235_235_crop_100';
$largeDir = $uploadDir . '/530_530_crop_100';

$products = array(
    '4w12bf1-5e' => '4W12BF1.5E - Water-filled 1.5 HP',
    '4w14bf1-5e' => '4W14BF1.5E - Water-filled 1.5 HP',
    '4vo1-7-bue-u4s' => '4VO1/7 - Oil-filled 1 HP',
    '4vo1-10-bue-u4s' => '4VO1/10 - Oil-filled 1 HP',
    '4vo7bu1eu' => '4VO7BU1EU - Oil-filled 1 HP',
    '4vo10bu1eu' => '4VO10BU1EU - Oil-filled 1 HP',
    '4vo1-5-12-bue-u4s' => '4VO1.5/12 - Oil-filled 1.5 HP',
    '4vo1-5-14-bue-u4s' => '4VO1.5/14 - Oil-filled 1.5 HP'
);

echo "Creating proper placeholder WebP images using GD...\n\n";

foreach ($products as $filename => $label) {
    // Create main image (530x530) with proper colors
    $mainPath = $uploadDir . '/' . $filename . '.webp';

    $img = imagecreatetruecolor(530, 530);
    imagefilledrectangle($img, 0, 0, 529, 529, imagecolorallocate($img, 200, 210, 220));

    // Gradient effect lines
    $lineColor = imagecolorallocate($img, 100, 150, 200);
    imageline($img, 0, 0, 529, 529, $lineColor);
    imageline($img, 529, 0, 0, 529, $lineColor);

    // Text
    $textColor = imagecolorallocate($img, 30, 30, 50);
    imagestring($img, 5, 100, 230, "4-INCH BOREWELL SUBMERSIBLE", $textColor);
    imagestring($img, 5, 150, 260, strtoupper($filename), $textColor);
    imagestring($img, 2, 120, 290, "(Placeholder - To be replaced with actual product image)", $textColor);

    // Save WebP
    if (file_exists($mainPath)) {
        unlink($mainPath);
    }

    ob_start();
    imagewebp($img, null, 85);
    $webp_data = ob_get_clean();
    file_put_contents($mainPath, $webp_data);

    if (filesize($mainPath) > 0) {
        echo "✓ Main: $mainPath (" . filesize($mainPath) . " bytes)\n";
    } else {
        echo "✗ Failed: $mainPath\n";
    }
    imagedestroy($img);

    // Create thumbnail (235x235)
    $thumbPath = $thumbDir . '/' . $filename . '.webp';

    $img = imagecreatetruecolor(235, 235);
    imagefilledrectangle($img, 0, 0, 234, 234, imagecolorallocate($img, 200, 210, 220));

    $lineColor = imagecolorallocate($img, 100, 150, 200);
    imageline($img, 0, 0, 234, 234, $lineColor);
    imageline($img, 234, 0, 0, 234, $lineColor);

    $textColor = imagecolorallocate($img, 30, 30, 50);
    imagestring($img, 3, 20, 100, strtoupper($filename), $textColor);

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
    imagefilledrectangle($img, 0, 0, 529, 529, imagecolorallocate($img, 200, 210, 220));

    $lineColor = imagecolorallocate($img, 100, 150, 200);
    imageline($img, 0, 0, 529, 529, $lineColor);
    imageline($img, 529, 0, 0, 529, $lineColor);

    $textColor = imagecolorallocate($img, 30, 30, 50);
    imagestring($img, 5, 100, 230, "4-INCH BOREWELL SUBMERSIBLE", $textColor);
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

echo "✓ All placeholder images created successfully!\n";
?>
