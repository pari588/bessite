<?php
$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

echo "=== Fixing SEO URLs ===\n\n";

// Manually map SEO URLs for products with "I" or "II" in names
$seo_mapping = array(
    'MINI MASTER II' => 'mini-master-ii',
    'CHAMP PLUS II' => 'champ-plus-ii',
    'MINI MASTERPLUS II' => 'mini-masterplus-ii',
    'MINI MARVEL II' => 'mini-marvel-ii',
    'MINI CREST II' => 'mini-crest-ii',
    'FLOMAX PLUS II' => 'flomax-plus-ii',
    'MASTER DURA II' => 'master-dura-ii',
    'MASTER PLUS II' => 'master-plus-ii',
    'STAR PLUS II' => 'star-plus-ii',
    'CHAMP DURA II' => 'champ-dura-ii',
    'NILE PLUS I' => 'nile-plus-i',
    'NILE DURA I' => 'nile-dura-i',
    'MINI SUMO I' => 'mini-sumo-i',
    'MINI MASTERPLUS I' => 'mini-masterplus-i',
    'MINI MARVEL I' => 'mini-marvel-i',
    'GLORY PLUS I' => 'glory-plus-i',
    'MINI CREST I' => 'mini-crest-i',
    'CHAMP PLUS I' => 'champ-plus-i',
    'MASTER PLUS I' => 'master-plus-i',
    'MASTER DURA I' => 'master-dura-i',
    'GLIDE PLUS II' => 'glide-plus-ii',
    'GLIDE PLUS I' => 'glide-plus-i',
    'FLOMAX PLUS I' => 'flomax-plus-i',
    'CHAMP DURA I' => 'champ-dura-i',
    'MINI MASTER I' => 'mini-master-i',
    'WIN PLUS I' => 'win-plus-i',
    'ULTIMO II' => 'ultimo-ii',
    'ULTIMO I' => 'ultimo-i',
    'STAR PLUS I' => 'star-plus-i',
    'STAR DURA I' => 'star-dura-i',
    'PRIMO I' => 'primo-i',
    'Mini Everest Mini Pump' => 'mini-everest-mini-pump',
);

$updated = 0;
foreach($seo_mapping as $title => $seo_uri) {
    $query = $conn->prepare("UPDATE mx_pump SET seoUri = ? WHERE pumpTitle = ? AND status = 1");
    $query->bind_param("ss", $seo_uri, $title);
    
    if($query->execute()) {
        if($query->affected_rows > 0) {
            echo "✅ $title → $seo_uri\n";
            $updated++;
        }
    }
    $query->close();
}

echo "\n=== Results ===\n✅ Updated: $updated\n";

// Verify all pumps have SEO URLs
$result = $conn->query("SELECT COUNT(*) as missing FROM mx_pump WHERE (seoUri IS NULL OR seoUri = '') AND status=1");
$row = $result->fetch_assoc();
if($row['missing'] == 0) {
    echo "✅ All pumps have SEO URLs!\n";
} else {
    echo "⚠️  Missing SEO URLs: " . $row['missing'] . "\n";
}

$conn->close();
?>
