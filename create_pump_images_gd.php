<?php
echo "CREATING PUMP PRODUCT IMAGES (GD Library)\n";
echo "==========================================\n\n";

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// Get all pump products
$result = $conn->query("SELECT pumpID, pumpTitle FROM mx_pump WHERE status=1 AND pumpID >= 21 ORDER BY pumpID");

$thumb_dir = '/home/bombayengg/public_html/uploads/pump/235_235_crop_100/';

// Check if GD is available
if (!extension_loaded('gd')) {
    die("✗ GD library is not available\n");
}

echo "✓ GD library available\n\n";

$created = 0;
$failed = 0;

while ($row = $result->fetch_assoc()) {
    $pump_id = $row['pumpID'];
    $pump_title = $row['pumpTitle'];
    $filename = 'pump_' . $pump_id . '.webp';
    $file_path = $thumb_dir . $filename;
    
    echo "Creating: $pump_title (ID: $pump_id)\n";
    
    try {
        // Create image: 235x235 with gradient blue background
        $image = imagecreatetruecolor(235, 235);
        
        // Create a nice gradient blue color
        $blue = imagecolorallocate($image, 52, 152, 219);
        $dark_blue = imagecolorallocate($image, 41, 128, 185);
        
        // Fill with blue
        imagefilledrectangle($image, 0, 0, 235, 235, $blue);
        
        // Add a border
        $white = imagecolorallocate($image, 255, 255, 255);
        imagerectangle($image, 2, 2, 232, 232, $white);
        
        // Add text
        $black = imagecolorallocate($image, 255, 255, 255);
        
        // Break text into multiple lines if needed
        $text_lines = wordwrap($pump_title, 20, "\n", true);
        $lines = explode("\n", $text_lines);
        
        $y_pos = 50;
        $line_height = 40;
        
        foreach ($lines as $line) {
            imagestring($image, 3, 20, $y_pos, substr($line, 0, 25), $black);
            $y_pos += $line_height;
            if ($y_pos > 200) break;
        }
        
        // Save as WebP
        if (imagewebp($image, $file_path, 80)) {
            echo "  ✓ Created WebP image\n";
            imagedestroy($image);
            
            // Update database
            $escaped_filename = $conn->real_escape_string($filename);
            $update_sql = "UPDATE mx_pump SET pumpImage='$escaped_filename' WHERE pumpID=$pump_id";
            
            if ($conn->query($update_sql)) {
                echo "  ✓ Database updated\n";
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
    
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "IMAGE CREATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Successfully created: $created\n";
echo "✗ Failed: $failed\n";
echo "\nImages saved to: $thumb_dir\n";

// Verify files were created
$files = glob($thumb_dir . 'pump_*.webp');
echo "Files created: " . count($files) . "\n";

$conn->close();
?>
