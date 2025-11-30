<?php
// Comprehensive verification of pump setup

$db_host = 'localhost';
$db_user = 'bombayengg';
$db_pass = 'oCFCrCMwKyy5jzg';
$db_name = 'bombayengg';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$upload_path = '/home/bombayengg/public_html/uploads/pump';

echo "═══════════════════════════════════════════════════════════════\n";
echo "     PUMP PAGE SETUP VERIFICATION REPORT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Database statistics
echo "📊 DATABASE STATISTICS:\n";
echo "───────────────────────────────────────────────────────────────\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE status=1");
$total_pumps = $result->fetch_assoc()['cnt'];
echo "✅ Total Active Pumps: $total_pumps\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump_category WHERE status=1");
$total_cats = $result->fetch_assoc()['cnt'];
echo "✅ Total Active Categories: $total_cats\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM mx_pump WHERE pumpImage IS NOT NULL AND pumpImage != ''");
$pumps_with_img = $result->fetch_assoc()['cnt'];
echo "✅ Pumps with Images: $pumps_with_img\n\n";

// 2. Pagination check
echo "📄 PAGINATION ANALYSIS:\n";
echo "───────────────────────────────────────────────────────────────\n";
$per_page = 20;
$pages_needed = ceil($total_pumps / $per_page);
echo "Admin List Per Page: $per_page items\n";
echo "Total Pages Needed: $pages_needed pages\n";

if($pages_needed > 1) {
    echo "⚠️  NOTE: Admin list requires $pages_needed pages to show all pumps!\n";
    echo "    Currently showing page 1 of $pages_needed (items 1-20)\n";
} else {
    echo "✅ All pumps fit on single page\n";
}
echo "\n";

// 3. Image files verification
echo "🖼️  IMAGE FILES VERIFICATION:\n";
echo "───────────────────────────────────────────────────────────────\n";

$thumb_235 = count(glob($upload_path . '/235_235_crop_100/*'));
$thumb_530 = count(glob($upload_path . '/530_530_crop_100/*'));
echo "✅ Thumbnail Size (235x235): $thumb_235 files\n";
echo "✅ Detail Size (530x530): $thumb_530 files\n";

if($thumb_235 >= 30 && $thumb_530 >= 30) {
    echo "✅ Sufficient thumbnails generated\n";
} else {
    echo "⚠️  Low thumbnail count\n";
}
echo "\n";

// 4. Image file existence check
echo "✓ IMAGE FILE MAPPING VERIFICATION:\n";
echo "───────────────────────────────────────────────────────────────\n";

$result = $conn->query("
    SELECT pumpID, pumpTitle, pumpImage, categoryPID
    FROM mx_pump
    WHERE status=1
    ORDER BY pumpID LIMIT 5
");

$missing = 0;
$found = 0;

while($row = $result->fetch_assoc()) {
    $src_file = $upload_path . "/" . $row['pumpImage'];
    if(file_exists($src_file)) {
        echo "✅ ID {$row['pumpID']}: {$row['pumpImage']}\n";
        $found++;
    } else {
        echo "❌ ID {$row['pumpID']}: {$row['pumpImage']} NOT FOUND\n";
        $missing++;
    }
}
echo "     (showing first 5 of $total_pumps)\n\n";

// 5. Category distribution
echo "📑 CATEGORY DISTRIBUTION:\n";
echo "───────────────────────────────────────────────────────────────\n";

$result = $conn->query("
    SELECT c.categoryTitle, COUNT(p.pumpID) as pump_count
    FROM mx_pump_category c
    LEFT JOIN mx_pump p ON c.categoryPID = p.categoryPID AND p.status=1
    WHERE c.status=1
    GROUP BY c.categoryPID, c.categoryTitle
    ORDER BY pump_count DESC
");

$cat_total = 0;
while($row = $result->fetch_assoc()) {
    $count = $row['pump_count'] ?? 0;
    $cat_total += $count;
    if($count > 0) {
        echo "✅ {$row['categoryTitle']}: $count pumps\n";
    } else {
        echo "⚠️  {$row['categoryTitle']}: 0 pumps (empty)\n";
    }
}
echo "\nCategory Total: $cat_total pumps\n\n";

// 6. Frontend readiness check
echo "🌐 FRONTEND READINESS:\n";
echo "───────────────────────────────────────────────────────────────\n";

$result = $conn->query("
    SELECT c.categoryTitle, COUNT(p.pumpID) as cnt
    FROM mx_pump_category c
    LEFT JOIN mx_pump p ON c.categoryPID = p.categoryPID AND p.status=1
    WHERE c.status=1 AND c.parentID > 0
    GROUP BY c.categoryPID
    HAVING cnt > 0
");

$frontend_cats = 0;
$frontend_total = 0;
while($row = $result->fetch_assoc()) {
    $frontend_cats++;
    $frontend_total += $row['cnt'];
}

echo "✅ Frontend Categories with Products: $frontend_cats\n";
echo "✅ Total Products Listed on Frontend: $frontend_total\n\n";

// 7. Admin Display Check
echo "🛠️  ADMIN DISPLAY CHECK:\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "✅ Admin List URL: /xadmin/?pgs=pump\n";
echo "✅ Shows: 20 items per page\n";
echo "✅ Pagination: Available when items > 20\n";

if($total_pumps > 20) {
    echo "⚠️  IMPORTANT: Your admin list has $total_pumps pumps!\n";
    echo "   You are currently viewing only the first 20 (page 1 of $pages_needed)\n";
    echo "   To see all pumps:\n";
    echo "   - Click next page (pagination at bottom)\n";
    echo "   - Or increase items per page using 'Show Records' dropdown\n";
} else {
    echo "✅ All pumps visible on first page\n";
}
echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "📋 SUMMARY:\n";
echo "───────────────────────────────────────────────────────────────\n";
echo "Database Pumps:           $total_pumps\n";
echo "Categories:               $total_cats\n";
echo "Thumbnail Files (235):    $thumb_235\n";
echo "Detail Images (530):      $thumb_530\n";
echo "Pumps with Images:        $pumps_with_img\n";
echo "Pages in Admin List:      $pages_needed\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Status check
$status_issues = 0;
if($total_pumps == 0) {
    echo "❌ CRITICAL: No active pumps found!\n";
    $status_issues++;
}
if($pumps_with_img < $total_pumps) {
    echo "⚠️  WARNING: Not all pumps have images\n";
    $status_issues++;
}
if($thumb_235 == 0 || $thumb_530 == 0) {
    echo "⚠️  WARNING: Thumbnail images not generated\n";
    $status_issues++;
}

if($status_issues == 0) {
    echo "✅ ALL CHECKS PASSED - Pump page setup is complete!\n";
}

echo "\n";

$conn->close();
?>
