<?php
/**
 * Update Motor Product Images to Enhanced Versions
 * Changes .webp to .png for all motor products
 */

$host = 'localhost';
$user = 'bombayengg';
$pass = 'oCFCrCMwKyy5jzg';
$db = 'bombayengg';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

echo "Updating Motor Product Images\n";
echo "=============================\n\n";

// Get all motor products
$result = $conn->query("SELECT motorID, motorTitle, motorImage FROM mx_motor WHERE status = 1");

if ($result->num_rows == 0) {
    echo "No motor products found.\n";
    exit;
}

$updateCount = 0;
$errorCount = 0;

echo "Processing " . $result->num_rows . " motor products...\n\n";

while ($row = $result->fetch_assoc()) {
    $motorID = intval($row['motorID']);
    $oldImage = $row['motorImage'];

    // Convert .webp to .png
    $newImage = str_replace('.webp', '.png', $oldImage);

    // If already .png, skip
    if ($newImage == $oldImage) {
        echo "⊘ Skipped: {$row['motorTitle']} (already .png)\n";
        continue;
    }

    // Check if the new image file exists
    $imagePath = '/home/bombayengg/public_html/uploads/motor/' . $newImage;
    if (!file_exists($imagePath)) {
        echo "✗ ERROR: Image file not found: $newImage\n";
        echo "  Product: {$row['motorTitle']}\n";
        $errorCount++;
        continue;
    }

    // Update the database
    $newImageEsc = $conn->real_escape_string($newImage);
    $sql = "UPDATE mx_motor SET motorImage = '$newImageEsc' WHERE motorID = $motorID";

    if ($conn->query($sql) === TRUE) {
        echo "✓ Updated: {$row['motorTitle']}\n";
        echo "  {$oldImage} → {$newImage}\n";
        $updateCount++;
    } else {
        echo "✗ Database Error: " . $conn->error . "\n";
        echo "  Product: {$row['motorTitle']}\n";
        $errorCount++;
    }
}

echo "\n=============================\n";
echo "Summary:\n";
echo "  Updated: $updateCount products\n";
echo "  Errors: $errorCount\n";
echo "=============================\n\n";

// Verify
echo "Verification - Updated Motor Products:\n";
echo "======================================\n";

$result = $conn->query("SELECT motorID, motorTitle, motorImage FROM mx_motor WHERE status = 1 AND motorImage LIKE '%.png' LIMIT 15");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "✓ {$row['motorTitle']}\n  → {$row['motorImage']}\n";
    }
    echo "\n... and more\n";
} else {
    echo "No updated products found.\n";
}

// Summary count
$result = $conn->query("SELECT COUNT(*) as total_png FROM mx_motor WHERE motorImage LIKE '%.png' AND status = 1");
$row = $result->fetch_assoc();

echo "\n✅ Total Products with PNG Images: " . $row['total_png'] . "\n";
echo "✅ Motor product images updated successfully!\n";

$conn->close();
?>
