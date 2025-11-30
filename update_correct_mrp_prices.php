<?php
/**
 * Update MRP Prices from Official Crompton Website
 * Replaces incorrect prices with actual Crompton MRP
 */

$DBHOST = 'localhost';
$DBNAME = 'bombayengg';
$DBUSER = 'bombayengg';
$DBPASS = 'oCFCrCMwKyy5jzg';

$conn = mysqli_connect($DBHOST, $DBUSER, $DBPASS, $DBNAME);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// Official Crompton MRP prices from their website
$crompton_prices = [
    // Mini Pumps
    'MINI MASTER I' => '₹12,025.00',
    'MINI MASTERPLUS I' => '₹13,050.00',
    'MINI MARVEL I' => '₹7,950.00',
    'MINI CREST I' => '₹6,375.00',
    'MINI SUMO I' => '₹6,025.00',
    'CHAMP PLUS I' => '₹6,025.00',
    'CHAMP DURA I' => '₹6,375.00',
    'FLOMAX PLUS I' => '₹12,900.00',
    'GLIDE PLUS I' => '₹5,850.00',
    'GLIDE PLUS II' => '₹4,525.00',
    'MASTER PLUS I' => '₹12,350.00',
    'MASTER DURA I' => '₹12,700.00',
    'NILE PLUS I' => '₹5,850.00',
    'NILE DURA I' => '₹6,700.00',
    'PRIMO I' => '₹5,400.00',
    'STAR PLUS I' => '₹8,950.00',
    'STAR DURA I' => '₹9,525.00',
    'ULTIMO I' => '₹5,675.00',
    'ULTIMO II' => '₹4,400.00',
    'WIN PLUS I' => '₹5,400.00',
    'Mini Everest Mini Pump' => '₹22,775.00',
    'AQUAGOLD 150' => '₹15,550.00',
    'AQUAGOLD DURA 150' => '₹15,550.00',
    'AQUAGOLD 50-30' => '₹8,575.00', // From Crompton website
    'AQUAGOLD 100-33' => '₹10,525.00', // From Crompton website

    // DMB/CMB Pumps
    'DMB10D PLUS' => '₹17,925.00',
    'DMB10DCSL' => '₹17,850.00',
    'CMB05NV PLUS' => '₹12,575.00',
    'CMB10NV PLUS' => '₹16,150.00',
];

$updated = 0;
$log = [];

foreach ($crompton_prices as $pump_pattern => $correct_price) {
    // Find pump by title
    $query = "SELECT pumpID, pumpTitle FROM mx_pump WHERE status = 1 AND pumpTitle LIKE '%$pump_pattern%'";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        $log[] = "[ERROR] Query failed for $pump_pattern: " . mysqli_error($conn);
        continue;
    }

    while ($pump = mysqli_fetch_assoc($result)) {
        $pumpID = intval($pump['pumpID']);
        $title = $pump['pumpTitle'];

        // Update detail records with correct price
        $update_query = "UPDATE mx_pump_detail SET mrp = '$correct_price' WHERE pumpID = $pumpID";

        if (mysqli_query($conn, $update_query)) {
            $updated++;
            $log[] = "[✓] $title - Updated to $correct_price";
        } else {
            $log[] = "[ERROR] Failed to update $title: " . mysqli_error($conn);
        }
    }
}

echo "Updating Crompton MRP Prices from Official Website...\n";
echo str_repeat("=", 80) . "\n\n";
echo "Records Updated: $updated\n\n";
echo "Update Log:\n";
foreach ($log as $entry) {
    echo "$entry\n";
}

// Save log
$logfile = "MRP_PRICE_UPDATE_LOG_" . date('YmdHis') . ".txt";
file_put_contents($logfile, implode("\n", $log));
echo "\nLog saved: $logfile\n";

mysqli_close($conn);
?>
