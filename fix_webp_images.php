<?php
// Fix: Map pumps to correct WebP images and copy from crompton_images

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$upload_path = '/home/bombayengg/public_html/uploads/pump';
$crompton_path = $upload_path . '/crompton_images';

echo "=== WebP Image Fix ===\n\n";

// Mapping of pump IDs to their correct WebP images from crompton_images
$webp_mapping = array(
    // Mini pumps - use generic centrifugal image (will fallback to available names)
    21 => 'mini-marvel-ii.webp',           // Mini Everest Mini Pump
    22 => 'aquagold-50-30.webp',           // AQUAGOLD DURA 150
    23 => 'aquagold-50-30.webp',           // AQUAGOLD 150
    24 => 'mini-masterplus-ii.webp',       // WIN PLUS I
    25 => 'mini-masterplus-ii.webp',       // ULTIMO II
    26 => 'mini-masterplus-ii.webp',       // ULTIMO I
    27 => 'mini-masterplus-ii.webp',       // STAR PLUS I
    28 => 'mini-masterplus-ii.webp',       // STAR DURA I
    29 => 'mini-masterplus-ii.webp',       // PRIMO I
    49 => 'mini-marvel-ii.webp',           // NILE PLUS I
    50 => 'mini-marvel-ii.webp',           // NILE DURA I
    51 => 'mini-master-ii.webp',           // MINI SUMO I
    52 => 'mini-masterplus-ii.webp',       // MINI MASTERPLUS I
    53 => 'mini-marvel-ii.webp',           // MINI MARVEL I
    54 => 'mini-masterplus-ii.webp',       // GLORY PLUS I
    55 => 'mini-masterplus-ii.webp',       // MINI CREST I
    56 => 'mini-masterplus-ii.webp',       // CHAMP PLUS I
    57 => 'mini-masterplus-ii.webp',       // MASTER PLUS I
    58 => 'mini-masterplus-ii.webp',       // MASTER DURA I
    59 => 'mini-masterplus-ii.webp',       // GLIDE PLUS II
    60 => 'mini-masterplus-ii.webp',       // GLIDE PLUS I
    61 => 'mini-masterplus-ii.webp',       // FLOMAX PLUS I
    62 => 'mini-masterplus-ii.webp',       // CHAMP DURA I
    63 => 'mini-master-ii.webp',           // MINI MASTER I
    64 => 'mini-master-ii.webp',           // MINI MASTER II
    65 => 'champ-plus-ii.webp',            // CHAMP PLUS II
    66 => 'mini-masterplus-ii.webp',       // MINI MASTERPLUS II
    67 => 'mini-marvel-ii.webp',           // MINI MARVEL II
    68 => 'mini-master-ii.webp',           // MINI MASTER II (duplicate)
    69 => 'aquagold-50-30.webp',           // AQUAGOLD 50-30
    70 => 'aquagold-50-30.webp',           // AQUAGOLD 100-33
    71 => 'flomax-plus-ii.webp',           // FLOMAX PLUS II
    72 => 'mini-masterplus-ii.webp',       // MASTER DURA II
    73 => 'mini-masterplus-ii.webp',       // MASTER PLUS II
    74 => 'champ-plus-ii.webp',            // STAR PLUS II
    75 => 'champ-plus-ii.webp',            // CHAMP DURA II
    77 => 'swj100ap-36-plus.webp',         // SWJ100AP-36 PLUS
    78 => 'swj100a-36-plus.webp',          // SWJ100A-36 PLUS
    79 => 'swj50ap-30-plus.webp',          // SWJ50AP-30 PLUS
    80 => 'swj50a-30-plus.webp',           // SWJ50A-30 PLUS
);

// Keep existing non-crompton WebPs (original pump images)
$preserve_webps = array(
    3 => 'v4-stainless-steel-pumps.webp',
    4 => 'v4-stainless-steel-pumps.webp',
    34 => 'borewell-submersible-pump-100w-v__530x530.webp',
    35 => 'borewell-submersible-pump-100w-v__530x530.webp',
    36 => 'borewell-submersible-pump-3w__530x530.webp',
    37 => 'borewell-submersible-pump-3w__530x530.webp',
    38 => 'borewell-submersible-pump-100w-v__530x530.webp',
    39 => 'borewell-submersible-pump-100w-v__530x530.webp',
    40 => 'borewell-submersible-pump-3w__530x530.webp',
    41 => 'borewell-submersible-pump-100w-v__530x530.webp',
    42 => 'borewell-submersible-pump-100w-v__530x530.webp',
    43 => 'vertical-openwell__530x530.webp',
    44 => 'horizontal-openwell__530x530.webp',
    45 => 'v-6-50-feet-per-stage-pumps__530x530.webp',
    46 => 'v4-stainless-steel-pumps.webp',
    47 => 'mb-centrifugal-monoset-pump__530x530.webp',
    48 => 'mb-centrifugal-monoset-pump__530x530.webp',
    30 => 'mb-centrifugal-monoset-pump__530x530.webp',
    31 => 'mb-centrifugal-monoset-pump__530x530.webp',
    32 => 'mb-centrifugal-monoset-pump__530x530.webp',
    33 => 'mb-centrifugal-monoset-pump__530x530.webp',
);

$updated = 0;
$copied = 0;
$errors = array();

// First, copy WebP files from crompton_images to main pump folder
echo "Step 1: Copying WebP files from crompton_images/\n";
foreach(glob($crompton_path . '/*.webp') as $src) {
    $filename = basename($src);
    $dest = $upload_path . '/' . $filename;

    if(!file_exists($dest) || filesize($dest) == 0) {
        if(@copy($src, $dest)) {
            echo "✅ Copied: $filename (" . number_format(filesize($src)) . " bytes)\n";
            $copied++;
        } else {
            echo "❌ Failed to copy: $filename\n";
            $errors[] = "Failed to copy $filename";
        }
    } else {
        echo "⏭️  Already exists: $filename\n";
    }
}

echo "\nStep 2: Updating database with correct WebP image references\n";

// Update database with correct WebP references
$all_mapping = array_merge($webp_mapping, $preserve_webps);

foreach($all_mapping as $pump_id => $webp_file) {
    // Get pump title
    $result = $conn->query("SELECT pumpTitle FROM mx_pump WHERE pumpID = $pump_id AND status = 1");
    if(!$result || $result->num_rows == 0) {
        continue;
    }

    $row = $result->fetch_assoc();
    $title = $row['pumpTitle'];

    // Verify file exists
    $webp_file_path = $upload_path . '/' . $webp_file;
    if(!file_exists($webp_file_path)) {
        echo "❌ ID $pump_id: WebP file not found: $webp_file\n";
        $errors[] = "ID $pump_id: WebP file not found: $webp_file";
        continue;
    }

    // Update database
    $query = $conn->prepare("UPDATE mx_pump SET pumpImage = ? WHERE pumpID = ? AND status = 1");
    $query->bind_param("si", $webp_file, $pump_id);

    if($query->execute()) {
        echo "✅ ID $pump_id: {$title}\n   Image: {$webp_file}\n";
        $updated++;
    } else {
        echo "❌ ID $pump_id: UPDATE FAILED\n";
        $errors[] = "ID $pump_id: Failed to update database";
    }
    $query->close();
}

echo "\n=== Results ===\n";
echo "✅ Copied WebP files: $copied\n";
echo "✅ Updated DB records: $updated\n";
echo "❌ Errors: " . count($errors) . "\n";

if(count($errors) > 0) {
    echo "\nError Details:\n";
    foreach($errors as $err) {
        echo "  - $err\n";
    }
}

// Verify all pumps now have valid WebP files
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
$missing_files = array();

while($row = $result->fetch_assoc()) {
    $file = $upload_path . '/' . $row['pumpImage'];
    if(file_exists($file) && filesize($file) > 0 && strpos($row['pumpImage'], '.webp') !== false) {
        $valid++;
    } else {
        $invalid++;
        $missing_files[] = "ID {$row['pumpID']}: {$row['pumpImage']}";
    }
}

echo "Total pumps: $total\n";
echo "✅ Valid WebP images: $valid\n";
echo "❌ Invalid/Missing: $invalid\n";

if($invalid > 0) {
    echo "\nInvalid files:\n";
    foreach($missing_files as $f) {
        echo "  - $f\n";
    }
} else {
    echo "\n✨ All pumps now have valid WebP images!\n";
}

$conn->close();
?>
