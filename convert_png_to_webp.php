<?php
// Convert PNG files to WebP format

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Converting PNG to WebP ===\n\n";

// Get all PNG files
$png_files = glob($upload_path . '/*.png');

echo "Found " . count($png_files) . " PNG files\n\n";

$success = 0;
$failed = 0;

foreach($png_files as $png_file) {
    $filename = basename($png_file);
    $webp_file = $upload_path . '/' . str_replace('.png', '.webp', $filename);

    // Skip if WebP already exists and has content
    if(file_exists($webp_file) && filesize($webp_file) > 1000) {
        echo "⏭️  Skip: $filename (WebP already exists)\n";
        continue;
    }

    // Convert PNG to WebP
    if(convertPNGtoWebP($png_file, $webp_file)) {
        $size = filesize($webp_file);
        echo "✅ {$filename} → " . basename($webp_file) . " (" . number_format($size) . " bytes)\n";
        $success++;
    } else {
        echo "❌ Failed: {$filename}\n";
        $failed++;
    }
}

echo "\n=== Results ===\n";
echo "✅ Converted: $success\n";
echo "❌ Failed: $failed\n";
echo "\n✨ WebP conversion complete!\n";

function convertPNGtoWebP($png_file, $webp_file) {
    try {
        // Load PNG
        $img = @imagecreatefrompng($png_file);
        if(!$img) return false;

        // Save as WebP (quality 85)
        $result = @imagewebp($img, $webp_file, 85);
        imagedestroy($img);

        return $result && file_exists($webp_file) && filesize($webp_file) > 0;
    } catch(Exception $e) {
        return false;
    }
}
?>
