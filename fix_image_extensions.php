<?php
// Fix: Convert .webp references to .png (actual working files)

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Fixing Image Extensions ===\n\n";
echo "Converting .webp references to .png (actual working files)\n\n";

$upload_path = '/home/bombayengg/public_html/uploads/pump';

// Get all pumps with webp extension
$result = $conn->query("
    SELECT pumpID, pumpTitle, pumpImage
    FROM mx_pump
    WHERE pumpImage LIKE '%.webp'
    ORDER BY pumpID
");

$updated = 0;
$errors = array();

while($row = $result->fetch_assoc()) {
    $old_image = $row['pumpImage'];

    // Convert .webp to .png
    $new_image = str_replace('.webp', '.png', $old_image);

    // Verify PNG file exists
    $png_file = $upload_path . "/" . $new_image;

    if(!file_exists($png_file)) {
        $errors[] = "ID {$row['pumpID']}: PNG file not found: $new_image";
        echo "❌ ID {$row['pumpID']}: PNG NOT FOUND: $new_image\n";
        continue;
    }

    // Check file size
    $filesize = filesize($png_file);
    if($filesize == 0) {
        $errors[] = "ID {$row['pumpID']}: PNG file is empty: $new_image";
        echo "❌ ID {$row['pumpID']}: PNG is EMPTY (0 bytes): $new_image\n";
        continue;
    }

    // Update database
    $query = $conn->prepare("UPDATE mx_pump SET pumpImage = ? WHERE pumpID = ? AND status = 1");
    $query->bind_param("si", $new_image, $row['pumpID']);

    if($query->execute()) {
        echo "✅ ID {$row['pumpID']}: {$row['pumpTitle']}\n";
        echo "   OLD: {$old_image} (empty 0 bytes)\n";
        echo "   NEW: {$new_image} (" . number_format($filesize) . " bytes)\n";
        $updated++;
    } else {
        $errors[] = "ID {$row['pumpID']}: Failed to update database";
        echo "❌ ID {$row['pumpID']}: UPDATE FAILED\n";
    }
    $query->close();
}

echo "\n=== Results ===\n";
echo "✅ Updated: $updated\n";
echo "❌ Errors: " . count($errors) . "\n";

if(count($errors) > 0) {
    echo "\nError Details:\n";
    foreach($errors as $err) {
        echo "  - $err\n";
    }
}

// Verify all images now exist
echo "\n=== Verification ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE status=1");
$total = $result->fetch_assoc()['cnt'];

$result = $conn->query("
    SELECT pumpID, pumpImage
    FROM mx_pump
    WHERE status=1
");

$valid = 0;
$invalid = 0;
while($row = $result->fetch_assoc()) {
    $file = $upload_path . "/" . $row['pumpImage'];
    if(file_exists($file) && filesize($file) > 0) {
        $valid++;
    } else {
        $invalid++;
    }
}

echo "Total pumps: $total\n";
echo "✅ Valid images: $valid\n";
echo "❌ Invalid images: $invalid\n";

if($invalid == 0) {
    echo "\n✨ All pumps now have valid image files!\n";
} else {
    echo "\n⚠️  Warning: $invalid pumps still have invalid images\n";
}

$conn->close();
?>
