<?php
// Fix pump image references in database to match actual files

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Pump Image Fix ===\n\n";

// Mapping of pump IDs to actual available images
// Based on pump titles and available files
$image_map = array(
    3 => 'v4-stainless-steel-pumps.webp',           // V-4 Stainless Steel Pumps
    4 => 'v4-stainless-steel-pumps.webp',           // V-4 Stainless Steel Pumps
    21 => 'mb-centrifugal-monoset-pump__530x530.webp',  // Mini Everest Mini Pump
    22 => 'mb-centrifugal-monoset-pump__530x530.webp',  // AQUAGOLD DURA 150
    23 => 'mb-centrifugal-monoset-pump__530x530.webp',  // AQUAGOLD 150
    24 => 'mb-centrifugal-monoset-pump__530x530.webp',  // WIN PLUS I
    25 => 'mb-centrifugal-monoset-pump__530x530.webp',  // ULTIMO II
    26 => 'mb-centrifugal-monoset-pump__530x530.webp',  // ULTIMO I
    27 => 'mb-centrifugal-monoset-pump__530x530.webp',  // STAR PLUS I
    28 => 'mb-centrifugal-monoset-pump__530x530.webp',  // STAR DURA I
    29 => 'mb-centrifugal-monoset-pump__530x530.webp',  // PRIMO I
    30 => 'mb-centrifugal-monoset-pump__530x530.webp',  // CMB10NV PLUS
    31 => 'mb-centrifugal-monoset-pump__530x530.webp',  // DMB10D PLUS
    32 => 'mb-centrifugal-monoset-pump__530x530.webp',  // DMB10DCSL
    33 => 'mb-centrifugal-monoset-pump__530x530.webp',  // CMB05NV PLUS
    34 => 'borewell-submersible-pump-100w-v__530x530.webp',  // SWJ1
    35 => 'borewell-submersible-pump-100w-v__530x530.webp',  // SWJ100AT-36 PLUS
    36 => 'borewell-submersible-pump-3w__530x530.webp',      // SWJ50AT-30 PLUS
    37 => 'borewell-submersible-pump-3w__530x530.webp',      // 3W12AP1D
    38 => 'borewell-submersible-pump-100w-v__530x530.webp',  // 3W10AP1D
    39 => 'borewell-submersible-pump-100w-v__530x530.webp',  // 3W10AK1A
    40 => 'borewell-submersible-pump-3w__530x530.webp',      // 4W7BU1AU
    41 => 'borewell-submersible-pump-100w-v__530x530.webp',  // 4W14BU2EU
    42 => 'borewell-submersible-pump-100w-v__530x530.webp',  // 4W10BU1AU
    43 => 'vertical-openwell__530x530.webp',        // OWE12(1PH)Z-28
    44 => 'horizontal-openwell__530x530.webp',      // OWE052(1PH)Z-21FS
    45 => 'v-6-50-feet-per-stage-pumps__530x530.webp', // Mini Force I
    46 => 'v4-stainless-steel-pumps.webp',          // CFMSMB5D1.00-V24
    47 => 'mb-centrifugal-monoset-pump__530x530.webp',  // ARMOR1.5-DSU
    48 => 'mb-centrifugal-monoset-pump__530x530.webp',  // ARMOR1.0-CQU
    49 => 'mb-centrifugal-monoset-pump__530x530.webp',  // NILE PLUS I
    50 => 'mb-centrifugal-monoset-pump__530x530.webp',  // NILE DURA I
    51 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI SUMO I
    52 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MASTERPLUS I
    53 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MARVEL I
    54 => 'mb-centrifugal-monoset-pump__530x530.webp',  // GLORY PLUS I
    55 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI CREST I
    56 => 'mb-centrifugal-monoset-pump__530x530.webp',  // CHAMP PLUS I
    57 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MASTER PLUS I
    58 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MASTER DURA I
    59 => 'mb-centrifugal-monoset-pump__530x530.webp',  // GLIDE PLUS II
    60 => 'mb-centrifugal-monoset-pump__530x530.webp',  // GLIDE PLUS I
    61 => 'mb-centrifugal-monoset-pump__530x530.webp',  // FLOMAX PLUS I
    62 => 'mb-centrifugal-monoset-pump__530x530.webp',  // CHAMP DURA I
    63 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MASTER I
    64 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MASTER II
    65 => 'mb-centrifugal-monoset-pump__530x530.webp',  // CHAMP PLUS II
    66 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MASTERPLUS II
    67 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MARVEL II
    68 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MINI MASTER II
    69 => 'mb-centrifugal-monoset-pump__530x530.webp',  // AQUAGOLD 50-30
    70 => 'mb-centrifugal-monoset-pump__530x530.webp',  // AQUAGOLD 100-33
    71 => 'mb-centrifugal-monoset-pump__530x530.webp',  // FLOMAX PLUS II
    72 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MASTER DURA II
    73 => 'mb-centrifugal-monoset-pump__530x530.webp',  // MASTER PLUS II
    74 => 'mb-centrifugal-monoset-pump__530x530.webp',  // STAR PLUS II
    75 => 'mb-centrifugal-monoset-pump__530x530.webp',  // CHAMP DURA II
    77 => 'borewell-submersible-pump-100w-v__530x530.webp',  // SWJ100AP-36 PLUS
    78 => 'borewell-submersible-pump-100w-v__530x530.webp',  // SWJ100A-36 PLUS
    79 => 'borewell-submersible-pump-3w__530x530.webp',      // SWJ50AP-30 PLUS
    80 => 'borewell-submersible-pump-3w__530x530.webp',      // SWJ50A-30 PLUS
);

$updated = 0;
$failed = 0;

foreach($image_map as $pump_id => $image_file) {
    // Verify image exists
    if(!file_exists("/home/bombayengg/public_html/uploads/pump/" . $image_file)) {
        echo "❌ Image not found: $image_file\n";
        $failed++;
        continue;
    }

    // Update database
    $query = $conn->prepare("UPDATE mx_pump SET pumpImage = ? WHERE pumpID = ? AND status = 1");
    $query->bind_param("si", $image_file, $pump_id);

    if($query->execute()) {
        echo "✅ Updated ID $pump_id with image: $image_file\n";
        $updated++;
    } else {
        echo "❌ Failed to update ID $pump_id\n";
        $failed++;
    }
    $query->close();
}

echo "\n=== Summary ===\n";
echo "✅ Updated: $updated\n";
echo "❌ Failed: $failed\n";

$conn->close();
echo "\n✨ Image fix complete!\n";
?>
