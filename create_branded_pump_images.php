<?php
echo "CREATING BRANDED PUMP PRODUCT IMAGES\n";
echo "====================================\n\n";

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// Get all pump products
$result = $conn->query("SELECT pumpID, pumpTitle, categoryPID FROM mx_pump WHERE status=1 AND pumpID >= 21 ORDER BY categoryPID, pumpID");

$thumb_dir = '/home/bombayengg/public_html/uploads/pump/235_235_crop_100/';

$category_colors = array(
    24 => array(52, 152, 219),    // Mini Pumps - Blue
    25 => array(46, 204, 113),    // DMB-CMB - Green
    26 => array(155, 89, 182),    // Shallow Well - Purple
    27 => array(230, 126, 34),    // 3-Inch - Orange
    28 => array(231, 76, 60),     // 4-Inch - Red
    29 => array(26, 188, 156),    // Openwell - Turquoise
    30 => array(52, 73, 94),      // Booster - Dark Grey
    31 => array(241, 196, 15),    // Control Panels - Yellow
);

echo "Creating professional placeholder images...\n\n";

$created = 0;
$failed = 0;

while ($row = $result->fetch_assoc()) {
    $pump_id = $row['pumpID'];
    $pump_title = $row['pumpTitle'];
    $category_id = $row['categoryPID'];
    
    $filename = 'pump_' . $pump_id . '.webp';
    $file_path = $thumb_dir . $filename;
    
    echo "Creating: $pump_title (ID: $pump_id)\n";
    
    try {
        // Create image: 235x235
        $image = imagecreatetruecolor(235, 235);
        
        // Get category color
        $color_rgb = isset($category_colors[$category_id]) ? $category_colors[$category_id] : array(52, 152, 219);
        $bg_color = imagecolorallocate($image, $color_rgb[0], $color_rgb[1], $color_rgb[2]);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, 235, 235, $bg_color);
        
        // Add a subtle gradient effect with darker border
        $dark_color = imagecolorallocate($image, max(0, $color_rgb[0]-50), max(0, $color_rgb[1]-50), max(0, $color_rgb[2]-50));
        imagerectangle($image, 0, 0, 234, 234, $dark_color);
        imagerectangle($image, 1, 1, 233, 233, $bg_color);
        
        // Add white text
        $white = imagecolorallocate($image, 255, 255, 255);
        
        // Break text into multiple lines
        $text_lines = wordwrap($pump_title, 18, "\n", true);
        $lines = explode("\n", $text_lines);
        
        // Calculate vertical position
        $line_height = 15;
        $total_height = count($lines) * $line_height;
        $y_start = (235 - $total_height) / 2;
        
        // Draw text centered
        foreach ($lines as $idx => $line) {
            $y_pos = $y_start + ($idx * $line_height);
            $text_width = strlen($line) * 8;
            $x_pos = (235 - $text_width) / 2;
            imagestring($image, 2, $x_pos, $y_pos, substr($line, 0, 28), $white);
        }
        
        // Add "CROMPTON" branding at bottom
        $small_text = "CROMPTON";
        $text_width = strlen($small_text) * 6;
        $x_pos = (235 - $text_width) / 2;
        imagestring($image, 1, $x_pos, 210, $small_text, $white);
        
        // Save as WebP with quality 80
        if (imagewebp($image, $file_path, 80)) {
            echo "  ✓ Created (". filesize($file_path) . " bytes)\n";
            imagedestroy($image);
            
            // Update database
            $escaped_filename = $conn->real_escape_string($filename);
            $update_sql = "UPDATE mx_pump SET pumpImage='$escaped_filename' WHERE pumpID=$pump_id";
            
            if ($conn->query($update_sql)) {
                $created++;
            } else {
                echo "  ✗ Database update failed\n";
                $failed++;
            }
        } else {
            echo "  ✗ WebP encoding failed\n";
            imagedestroy($image);
            $failed++;
        }
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "IMAGE CREATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Successfully created: $created\n";
echo "✗ Failed: $failed\n";

// Verify
$files = glob($thumb_dir . 'pump_*.webp');
echo "✓ Total files in directory: " . count($files) . "\n";

$total_size = 0;
foreach ($files as $file) {
    $total_size += filesize($file);
}

echo "✓ Total size: " . round($total_size / 1024, 2) . " KB\n";
echo "\n✓ Images are ready to display on frontend!\n";

$conn->close();
?>
