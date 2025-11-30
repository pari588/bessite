<?php
// Final fix: Map all pumps to EXISTING files (PNG + Crompton WebP)
// Use available files instead of trying to convert

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "=== Final Pump Image Fix ===\n\n";

// Mapping of pump IDs to ACTUAL AVAILABLE image files (PNG or WebP that exist with content)
$correct_mapping = array(
    // Original pumps - use PNG
    3 => 'v4-stainless-steel-pumps.png',
    4 => 'v4-stainless-steel-pumps.png',
    34 => 'borewell-submersible-pump-100w-v__530x530.png',
    35 => 'borewell-submersible-pump-100w-v__530x530.png',
    36 => 'borewell-submersible-pump-3w__530x530.png',
    37 => 'borewell-submersible-pump-3w__530x530.png',
    38 => 'borewell-submersible-pump-100w-v__530x530.png',
    39 => 'borewell-submersible-pump-100w-v__530x530.png',
    40 => 'borewell-submersible-pump-3w__530x530.png',
    41 => 'borewell-submersible-pump-100w-v__530x530.png',
    42 => 'borewell-submersible-pump-100w-v__530x530.png',
    43 => 'vertical-openwell__530x530.png',
    44 => 'horizontal-openwell__530x530.png',
    45 => 'v-6-50-feet-per-stage-pumps__530x530.png',
    46 => 'v4-stainless-steel-pumps.png',
    47 => 'mb-centrifugal-monoset-pump__530x530.png',
    48 => 'mb-centrifugal-monoset-pump__530x530.png',
    30 => 'mb-centrifugal-monoset-pump__530x530.png',
    31 => 'mb-centrifugal-monoset-pump__530x530.png',
    32 => 'mb-centrifugal-monoset-pump__530x530.png',
    33 => 'mb-centrifugal-monoset-pump__530x530.png',

    // Crompton mini pumps - use WebP (copied from crompton_images)
    21 => 'mini-marvel-ii.webp',
    22 => 'aquagold-50-30.webp',
    23 => 'aquagold-50-30.webp',
    24 => 'mini-masterplus-ii.webp',
    25 => 'mini-masterplus-ii.webp',
    26 => 'mini-masterplus-ii.webp',
    27 => 'mini-masterplus-ii.webp',
    28 => 'mini-masterplus-ii.webp',
    29 => 'mini-masterplus-ii.webp',
    49 => 'mini-marvel-ii.webp',
    50 => 'mini-marvel-ii.webp',
    51 => 'mini-master-ii.webp',
    52 => 'mini-masterplus-ii.webp',
    53 => 'mini-marvel-ii.webp',
    54 => 'mini-masterplus-ii.webp',
    55 => 'mini-masterplus-ii.webp',
    56 => 'mini-masterplus-ii.webp',
    57 => 'mini-masterplus-ii.webp',
    58 => 'mini-masterplus-ii.webp',
    59 => 'mini-masterplus-ii.webp',
    60 => 'mini-masterplus-ii.webp',
    61 => 'mini-masterplus-ii.webp',
    62 => 'champ-plus-ii.webp',
    63 => 'mini-master-ii.webp',
    64 => 'mini-master-ii.webp',
    65 => 'champ-plus-ii.webp',
    66 => 'mini-masterplus-ii.webp',
    67 => 'mini-marvel-ii.webp',
    68 => 'mini-master-ii.webp',
    69 => 'aquagold-50-30.webp',
    70 => 'aquagold-50-30.webp',
    71 => 'flomax-plus-ii.webp',
    72 => 'mini-masterplus-ii.webp',
    73 => 'mini-masterplus-ii.webp',
    74 => 'champ-plus-ii.webp',
    75 => 'champ-plus-ii.webp',
    77 => 'swj100ap-36-plus.webp',
    78 => 'swj100a-36-plus.webp',
    79 => 'swj50ap-30-plus.webp',
    80 => 'swj50a-30-plus.webp',
);

$updated = 0;
$errors = array();

echo "Updating database with CORRECT image references...\n\n";

foreach($correct_mapping as $pump_id => $image_file) {
    // Verify file exists
    $file_path = $upload_path . '/' . $image_file;

    if(!file_exists($file_path)) {
        echo "❌ ID $pump_id: File not found: {$image_file}\n";
        $errors[] = "ID $pump_id: File not found: {$image_file}";
        continue;
    }

    $size = filesize($file_path);
    if($size == 0) {
        echo "❌ ID $pump_id: File is empty (0 bytes): {$image_file}\n";
        $errors[] = "ID $pump_id: File is empty: {$image_file}";
        continue;
    }

    // Get pump title
    $result = $conn->query("SELECT pumpTitle FROM mx_pump WHERE pumpID = $pump_id AND status = 1");
    if(!$result || $result->num_rows == 0) {
        continue;
    }

    $row = $result->fetch_assoc();
    $title = $row['pumpTitle'];

    // Update database
    $query = $conn->prepare("UPDATE mx_pump SET pumpImage = ? WHERE pumpID = ? AND status = 1");
    $query->bind_param("si", $image_file, $pump_id);

    if($query->execute()) {
        echo "✅ ID $pump_id: {$title}\n";
        echo "   File: {$image_file} (" . number_format($size) . " bytes)\n";
        $updated++;
    } else {
        echo "❌ ID $pump_id: UPDATE FAILED\n";
        $errors[] = "ID $pump_id: Failed to update database";
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

// Final verification
echo "\n=== Final Verification ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE status=1");
$total = $result->fetch_assoc()['cnt'];

$result = $conn->query("
    SELECT pumpID, pumpTitle, pumpImage
    FROM mx_pump
    WHERE status=1
");

$valid = 0;
$invalid = 0;
$invalid_list = array();

while($row = $result->fetch_assoc()) {
    $file = $upload_path . '/' . $row['pumpImage'];
    $filesize = file_exists($file) ? filesize($file) : 0;

    if(file_exists($file) && $filesize > 0) {
        $valid++;
    } else {
        $invalid++;
        $invalid_list[] = "ID {$row['pumpID']}: {$row['pumpTitle']} → {$row['pumpImage']} (size: $filesize)";
    }
}

echo "Total pumps: $total\n";
echo "✅ Valid images (file exists + has content): $valid\n";
echo "❌ Invalid images: $invalid\n";

if($invalid > 0) {
    echo "\nInvalid pumps:\n";
    foreach(array_slice($invalid_list, 0, 10) as $item) {
        echo "  - $item\n";
    }
    if(count($invalid_list) > 10) {
        echo "  ... and " . (count($invalid_list) - 10) . " more\n";
    }
} else {
    echo "\n🎉 SUCCESS! All 61 pumps now have VALID images!\n";
}

$conn->close();
?>
