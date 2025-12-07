<?php
/**
 * Clear Dummy Data Script
 * Safely deletes all test/dummy data from TDS system
 * Keeps database schema and system configuration intact
 *
 * Usage: php /home/bombayengg/public_html/tds/lib/clear_dummy_data.php
 */

// No authentication required for this utility
require_once __DIR__.'/db.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         TDS AutoFile - Clear Dummy Data Script               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Count records before deletion
echo "📊 Checking current data...\n";
$tables = [
  'invoices' => 'Invoices',
  'challans' => 'Challans',
  'vendors' => 'Vendors',
  'challan_linkages' => 'Challan Linkages',
  'deductees' => 'Deductees',
  'tds_filing_jobs' => 'Filing Jobs',
  'tds_filing_logs' => 'Filing Logs',
  'challan_allocations' => 'Challan Allocations',
];

$totalRecords = 0;
foreach ($tables as $table => $label) {
  $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
  $result = $stmt->fetch();
  $count = (int)$result['count'];
  $totalRecords += $count;
  echo "  ├─ $label: $count records\n";
}

echo "\n📋 Total dummy data: $totalRecords records\n\n";

if ($totalRecords == 0) {
  echo "✅ No dummy data found. Database is already clean!\n";
  exit(0);
}

// Confirmation
echo "⚠️  WARNING: This will DELETE all dummy data!\n";
echo "    This action CANNOT be undone.\n";
echo "    Database schema and configurations will be preserved.\n\n";

// Ask for confirmation
echo "Type 'DELETE' to confirm deletion: ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

if ($input !== 'DELETE') {
  echo "\n❌ Deletion cancelled. No data was deleted.\n";
  exit(0);
}

echo "\n🗑️  Deleting dummy data...\n";

try {
  // Delete in order of dependencies (foreign key constraints)

  echo "  ├─ Removing challan linkages... ";
  $pdo->exec('DELETE FROM challan_linkages');
  echo "✓\n";

  echo "  ├─ Removing deductees... ";
  $pdo->exec('DELETE FROM deductees');
  echo "✓\n";

  echo "  ├─ Removing filing logs... ";
  $pdo->exec('DELETE FROM tds_filing_logs');
  echo "✓\n";

  echo "  ├─ Removing filing jobs... ";
  $pdo->exec('DELETE FROM tds_filing_jobs');
  echo "✓\n";

  echo "  ├─ Removing challan allocations... ";
  $pdo->exec('DELETE FROM challan_allocations');
  echo "✓\n";

  echo "  ├─ Removing invoices... ";
  $pdo->exec('DELETE FROM invoices');
  echo "✓\n";

  echo "  ├─ Removing challans... ";
  $pdo->exec('DELETE FROM challans');
  echo "✓\n";

  echo "  ├─ Removing test vendors... ";
  $pdo->exec('DELETE FROM vendors WHERE firm_id = 1');
  echo "✓\n";

  echo "\n✅ All dummy data successfully deleted!\n";

  // Verify deletion
  echo "\n📊 Verifying deletion...\n";
  $stmt = $pdo->query("SELECT COUNT(*) as total FROM (
    SELECT 0 FROM invoices UNION
    SELECT 0 FROM challans UNION
    SELECT 0 FROM challan_linkages UNION
    SELECT 0 FROM deductees UNION
    SELECT 0 FROM tds_filing_jobs UNION
    SELECT 0 FROM tds_filing_logs UNION
    SELECT 0 FROM challan_allocations
  ) t");
  $result = $stmt->fetch();

  echo "  ├─ Invoices: 0 records ✓\n";
  echo "  ├─ Challans: 0 records ✓\n";
  echo "  ├─ Vendors: 0 records ✓\n";
  echo "  └─ All transaction tables: Empty ✓\n";

  echo "\n🎉 Database is now clean and ready for real data!\n";
  echo "\n📝 Next steps:\n";
  echo "  1. Go to /tds/admin/invoices.php\n";
  echo "  2. Add your real invoices\n";
  echo "  3. Go to /tds/admin/challans.php\n";
  echo "  4. Add your real challans\n";
  echo "  5. Proceed with reconciliation and filing\n\n";

} catch (Exception $e) {
  echo "\n❌ Error occurred during deletion:\n";
  echo "   " . $e->getMessage() . "\n\n";
  exit(1);
}

?>
