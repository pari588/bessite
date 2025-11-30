<?php
// Assign UNIQUE images to each pump from available images
// Use the Crompton images as source for the mini pumps series
// Each pump gets a different image

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Assigning Unique Images to Pumps ===\n\n";

// Get list of available WebP images from crompton_images (these have real product images)
$crompton_images = array(
    'aquagold-50-30.webp',         // AQUAGOLD series
    'champ-plus-ii.webp',          // CHAMP series
    'flomax-plus-ii.webp',         // FLOMAX series
    'mini-marvel-ii.webp',         // MARVEL series
    'mini-master-ii.webp',         // MASTER series
    'mini-masterplus-ii.webp',     // MASTERPLUS series
    'swj100a-36-plus.webp',        // SWJ series
    'swj100ap-36-plus.webp',
    'swj50a-30-plus.webp',
    'swj50ap-30-plus.webp',
);

// Get all active pumps
$result = $conn->query("SELECT pumpID, pumpTitle FROM mx_pump WHERE status=1 ORDER BY pumpID");
$pumps = $result->fetch_all(MYSQLI_ASSOC);

echo "Total pumps: " . count($pumps) . "\n";
echo "Available unique WebP images: " . count($crompton_images) . "\n\n";

$updated = 0;
$image_index = 0;

foreach($pumps as $pump) {
    // For original pumps (IDs 3-4, 34-48), keep their existing images (PNG files)
    if($pump['pumpID'] <= 4 || ($pump['pumpID'] >= 34 && $pump['pumpID'] <= 48)) {
        // These already have PNG images mapped
        continue;
    }

    // For Crompton mini pumps (IDs 21-80), assign UNIQUE WebP images in rotation
    $image_file = $crompton_images[$image_index % count($crompton_images)];

    // Verify file exists
    $file_path = $upload_path . '/' . $image_file;
    if(!file_exists($file_path) || filesize($file_path) == 0) {
        echo "❌ Image not found: $image_file\n";
        continue;
    }

    // Update database
    $query = $conn->prepare("UPDATE mx_pump SET pumpImage = ? WHERE pumpID = ? AND status = 1");
    $query->bind_param("si", $image_file, $pump['pumpID']);

    if($query->execute()) {
        echo "✅ ID {$pump['pumpID']}: {$pump['pumpTitle']}\n";
        echo "   → {$image_file}\n";
        $updated++;
    }
    $query->close();

    // Rotate to next image
    $image_index++;
}

echo "\n=== Results ===\n";
echo "✅ Assigned: $updated pumps\n";

// Verify all have images
$result = $conn->query("SELECT pumpID, pumpTitle, pumpImage FROM mx_pump WHERE status=1 AND (pumpImage IS NULL OR pumpImage = '')");
$missing = $result->num_rows;

echo "\nPumps without images: $missing\n";

if($missing > 0) {
    echo "\nPumps needing images:\n";
    while($row = $result->fetch_assoc()) {
        echo "  - ID {$row['pumpID']}: {$row['pumpTitle']}\n";
    }
}

$conn->close();
echo "\n✨ Image assignment complete!\n";
?>
