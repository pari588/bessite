<?php
$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Generating SEO URLs ===\n\n";

// Get all pumps without proper SEO URLs
$result = $conn->query("SELECT pumpID, pumpTitle, seoUri FROM mx_pump WHERE (seoUri IS NULL OR seoUri = '' OR seoUri NOT LIKE '%-%-') AND status=1 ORDER BY pumpID");

$updated = 0;
while($row = $result->fetch_assoc()) {
    $seo_uri = strtolower(trim(preg_replace('/[^a-z0-9\-]/', '-', str_replace(' ', '-', $row['pumpTitle'])), '-'));
    
    if(empty($seo_uri)) {
        echo "⚠️  Skipped: {$row['pumpTitle']} (no valid URI)\n";
        continue;
    }
    
    $query = $conn->prepare("UPDATE mx_pump SET seoUri = ? WHERE pumpID = ? AND status = 1");
    $query->bind_param("si", $seo_uri, $row['pumpID']);
    
    if($query->execute()) {
        echo "✅ ID {$row['pumpID']}: {$row['pumpTitle']} → $seo_uri\n";
        $updated++;
    }
    $query->close();
}

echo "\n=== Results ===\n✅ Updated: $updated\n";
$conn->close();
?>
