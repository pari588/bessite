<?php
echo "CREATING PLACEHOLDER PUMP IMAGES\n";
echo "=================================\n\n";

$conn = new mysqli('localhost', 'bombayengg', 'oCFCrCMwKyy5jzg', 'bombayengg');

// Get all pump products from database
$result = $conn->query("SELECT pumpID, pumpTitle FROM mx_pump WHERE status=1 ORDER BY pumpID");

$upload_dir = '/home/bombayengg/public_html/uploads/pump/';
$thumb_dir = $upload_dir . '235_235_crop_100/';

$created = 0;
$failed = 0;

echo "Creating placeholder images...\n\n";

while ($row = $result->fetch_assoc()) {
    $pump_id = $row['pumpID'];
    $pump_title = $row['pumpTitle'];
    
    // Create filename: pump_ID.webp
    $filename = 'pump_' . $pump_id . '.webp';
    $full_path = $upload_dir . $filename;
    $thumb_path = $thumb_dir . $filename;
    
    echo "Processing: $pump_title (ID: $pump_id)\n";
    
    // Create a placeholder image using ImageMagick
    $cmd = "convert -size 235x235 xc:lightblue -pointsize 16 -fill black -gravity center -annotate +0+0 '" . escapeshellarg(substr($pump_title, 0, 30)) . "' " . escapeshellarg($thumb_path);
    
    $output = null;
    $return_var = null;
    exec($cmd, $output, $return_var);
    
    if ($return_var === 0 && file_exists($thumb_path)) {
        echo "  ✓ Created placeholder (235x235)\n";
        $created++;
        
        // Also update the database with the filename
        $update_sql = "UPDATE mx_pump SET pumpImage='" . $conn->real_escape_string($filename) . "' WHERE pumpID=$pump_id";
        if ($conn->query($update_sql)) {
            echo "  ✓ Updated database\n";
        } else {
            echo "  ✗ Database update failed\n";
        }
    } else {
        echo "  ✗ Failed to create image\n";
        $failed++;
    }
    
    echo "\n";
}

echo str_repeat("=", 50) . "\n";
echo "PLACEHOLDER IMAGE CREATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Successfully created: $created\n";
echo "✗ Failed: $failed\n";
echo "\nNext: Replace placeholder images with actual product images\n";
echo "Location: /home/bombayengg/public_html/uploads/pump/235_235_crop_100/\n";

$conn->close();
?>
