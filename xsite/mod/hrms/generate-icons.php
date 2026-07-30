<?php
/**
 * PWA Icon Generator - BES HRMS
 * Creates professional icons for the HRMS PWA
 *
 * Design: Modern gradient blue background with "BES" text and person icon
 * Safe zone: 20% padding for maskable icons
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
$outputDir = __DIR__ . '/icons/';

// Ensure directory exists
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);

    // Enable alpha blending
    imagealphablending($image, true);
    imagesavealpha($image, true);

    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);

    // Draw gradient background
    for ($y = 0; $y < $size; $y++) {
        $ratio = $y / $size;
        $r = intval(30 + ($ratio * (59 - 30)));
        $g = intval(64 + ($ratio * (130 - 64)));
        $b = intval(175 + ($ratio * (246 - 175)));
        $lineColor = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $size, $y, $lineColor);
    }

    // Safe zone padding (20% on each side for maskable icons)
    $safeZone = $size * 0.20;
    $contentSize = $size - ($safeZone * 2);
    $centerX = $size / 2;
    $centerY = $size / 2;

    // Scale factor for content within safe zone
    $scale = $contentSize / 512;

    // Draw a person/HR silhouette icon in the upper area (smaller)
    $iconCenterY = $centerY - ($contentSize * 0.12);

    // Person head (circle) - smaller
    $headRadius = intval(28 * $scale);
    imagefilledellipse($image, $centerX, $iconCenterY - intval(20 * $scale), $headRadius * 2, $headRadius * 2, $white);

    // Person body (shoulders/torso arc) - smaller
    $bodyWidth = intval(80 * $scale);
    $bodyHeight = intval(45 * $scale);
    $bodyY = $iconCenterY + intval(28 * $scale);
    imagefilledarc($image, $centerX, $bodyY + intval(22 * $scale), $bodyWidth, $bodyHeight * 2, 180, 360, $white, IMG_ARC_PIE);

    // Draw "BES" text below the icon - smaller font
    $fontSize = intval($contentSize * 0.18);
    $text = "BES";

    // Try different font paths
    $fontPaths = [
        '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/google-droid-sans-fonts/DroidSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
    ];

    $fontFile = null;
    foreach ($fontPaths as $path) {
        if (file_exists($path)) {
            $fontFile = $path;
            break;
        }
    }

    $textY = $centerY + ($contentSize * 0.18);

    if ($fontFile) {
        // Calculate text position (centered)
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $x = ($size - $textWidth) / 2 - $bbox[0];
        $y = $textY + ($textHeight / 2);

        // Add subtle shadow
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 100);
        imagettftext($image, $fontSize, 0, $x + 1, $y + 1, $shadowColor, $fontFile, $text);

        // Main text
        imagettftext($image, $fontSize, 0, $x, $y, $white, $fontFile, $text);

        // Draw "HRMS" subtitle smaller - only on larger icons
        if ($size >= 128) {
            $subFontSize = intval($contentSize * 0.09);
            $subText = "HRMS";
            $subBbox = imagettfbbox($subFontSize, 0, $fontFile, $subText);
            $subWidth = $subBbox[2] - $subBbox[0];
            $subX = ($size - $subWidth) / 2 - $subBbox[0];
            $subY = $textY + $fontSize * 0.55 + $subFontSize;

            $lightWhite = imagecolorallocatealpha($image, 255, 255, 255, 50);
            imagettftext($image, $subFontSize, 0, $subX, $subY, $lightWhite, $fontFile, $subText);
        }

    } else {
        // Fallback: Draw text manually using filled rectangles to form letters
        drawBESText($image, $size, $white, $textY);
    }

    // Save the icon
    $filename = $outputDir . 'icon-' . $size . 'x' . $size . '.png';
    imagepng($image, $filename, 9); // Max compression
    imagedestroy($image);

    echo "Created: icon-{$size}x{$size}.png\n";
}

echo "\nAll icons generated successfully!\n";

/**
 * Fallback function to draw BES text without TTF fonts
 */
function drawBESText($image, $size, $color, $centerY) {
    $letterWidth = intval($size * 0.16);
    $letterHeight = intval($size * 0.20);
    $strokeWidth = max(2, intval($size * 0.035));
    $spacing = intval($size * 0.04);
    $totalWidth = ($letterWidth * 3) + ($spacing * 2);
    $startX = ($size - $totalWidth) / 2;
    $startY = $centerY - ($letterHeight / 2);

    // Letter B
    $bX = $startX;
    // Vertical line
    imagefilledrectangle($image, $bX, $startY, $bX + $strokeWidth, $startY + $letterHeight, $color);
    // Top horizontal
    imagefilledrectangle($image, $bX, $startY, $bX + $letterWidth * 0.7, $startY + $strokeWidth, $color);
    // Middle horizontal
    imagefilledrectangle($image, $bX, $startY + ($letterHeight / 2) - ($strokeWidth / 2), $bX + $letterWidth * 0.7, $startY + ($letterHeight / 2) + ($strokeWidth / 2), $color);
    // Bottom horizontal
    imagefilledrectangle($image, $bX, $startY + $letterHeight - $strokeWidth, $bX + $letterWidth * 0.7, $startY + $letterHeight, $color);
    // Right verticals (bumps of B)
    imagefilledrectangle($image, $bX + $letterWidth * 0.7 - $strokeWidth, $startY, $bX + $letterWidth * 0.7, $startY + ($letterHeight / 2), $color);
    imagefilledrectangle($image, $bX + $letterWidth * 0.7 - $strokeWidth, $startY + ($letterHeight / 2), $bX + $letterWidth * 0.7, $startY + $letterHeight, $color);

    // Letter E
    $eX = $startX + $letterWidth + $spacing;
    // Vertical line
    imagefilledrectangle($image, $eX, $startY, $eX + $strokeWidth, $startY + $letterHeight, $color);
    // Top horizontal
    imagefilledrectangle($image, $eX, $startY, $eX + $letterWidth * 0.7, $startY + $strokeWidth, $color);
    // Middle horizontal
    imagefilledrectangle($image, $eX, $startY + ($letterHeight / 2) - ($strokeWidth / 2), $eX + $letterWidth * 0.5, $startY + ($letterHeight / 2) + ($strokeWidth / 2), $color);
    // Bottom horizontal
    imagefilledrectangle($image, $eX, $startY + $letterHeight - $strokeWidth, $eX + $letterWidth * 0.7, $startY + $letterHeight, $color);

    // Letter S
    $sX = $startX + ($letterWidth + $spacing) * 2;
    // Top horizontal
    imagefilledrectangle($image, $sX, $startY, $sX + $letterWidth * 0.7, $startY + $strokeWidth, $color);
    // Top-left vertical
    imagefilledrectangle($image, $sX, $startY, $sX + $strokeWidth, $startY + ($letterHeight / 2), $color);
    // Middle horizontal
    imagefilledrectangle($image, $sX, $startY + ($letterHeight / 2) - ($strokeWidth / 2), $sX + $letterWidth * 0.7, $startY + ($letterHeight / 2) + ($strokeWidth / 2), $color);
    // Bottom-right vertical
    imagefilledrectangle($image, $sX + $letterWidth * 0.7 - $strokeWidth, $startY + ($letterHeight / 2), $sX + $letterWidth * 0.7, $startY + $letterHeight, $color);
    // Bottom horizontal
    imagefilledrectangle($image, $sX, $startY + $letterHeight - $strokeWidth, $sX + $letterWidth * 0.7, $startY + $letterHeight, $color);
}
