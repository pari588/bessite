<?php
/**
 * Crompton Pumps Bulk Import Script
 * Direct Database Insert via Admin Functions
 */

ob_start();
require_once('./core/core.inc.php');
require_once('./xadmin/inc/site.inc.php');
require_once('./xadmin/mod/pump/x-pump.inc.php');

global $DB;

$results = array('added' => 0, 'failed' => 0, 'errors' => array());

try {
    // Step 1: Ensure Residential Pumps category exists
    $DB->vals = array('Residential Pumps');
    $DB->types = 's';
    $DB->sql = "SELECT categoryPID FROM " . $DB->pre . "pump_category WHERE categoryTitle=? LIMIT 1";
    $cat = $DB->dbRow();

    if (!$cat) {
        // Create category
        $cat_data = array(
            'categoryTitle' => 'Residential Pumps',
            'seoUri' => 'residential-pumps',
            'parentID' => 0,
            'status' => 1,
            'addDate' => date('Y-m-d H:i:s'),
            'xOrder' => 0
        );
        $DB->table = $DB->pre . "pump_category";
        $DB->data = $cat_data;
        if (!$DB->dbInsert()) {
            throw new Exception("Failed to create Residential Pumps category");
        }
        $cat_id = $DB->insertID;
    } else {
        $cat_id = $cat['categoryPID'];
    }

    // Step 2: Insert all Crompton products
    $products = array(
        array('name' => 'Mini Everest Mini Pump', 'desc' => 'Compact pump for gardening, lawn sprinkling', 'hp' => '', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '25mm x 25mm', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'AQUAGOLD DURA 150', 'desc' => 'Durable aquagold pump', 'hp' => '1.5', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'AQUAGOLD 150', 'desc' => 'Standard aquagold pump', 'hp' => '1.5', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'WIN PLUS I', 'desc' => 'Window pump series', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '25mm x 25mm', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'ULTIMO II', 'desc' => 'Entry-level pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'ULTIMO I', 'desc' => 'Basic pump model', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'STAR PLUS I', 'desc' => 'Star series pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'STAR DURA I', 'desc' => 'Durable star series', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'PRIMO I', 'desc' => 'Premium pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Mini Pump'),
        array('name' => 'CMB10NV PLUS', 'desc' => 'Centrifugal monoblock pump, 0.5 HP', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => '', 'type' => 'Monoblock Pump'),
        array('name' => 'DMB10D PLUS', 'desc' => 'Centrifugal monoblock pump, 1.0 HP', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => '', 'type' => 'Monoblock Pump'),
        array('name' => 'DMB10DCSL', 'desc' => 'Centrifugal monoblock pump, 1440 RPM', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => '', 'type' => 'Monoblock Pump'),
        array('name' => 'CMB05NV PLUS', 'desc' => 'Centrifugal monoblock pump with brass impeller', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => 'B.I.S. Compliant', 'mnre' => '', 'type' => 'Monoblock Pump'),
        array('name' => 'SWJ1', 'desc' => 'Shallow well jet pump with 8m suction', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Shallow Well Pump'),
        array('name' => 'SWJ100AT-36 PLUS', 'desc' => 'Shallow well jet pump with tank', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Shallow Well Pump'),
        array('name' => 'SWJ50AT-30 PLUS', 'desc' => 'Shallow well pump with tank', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Shallow Well Pump'),
        array('name' => '3W12AP1D', 'desc' => '3-inch water-filled submersible pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '3 inch (75mm)', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Borewell Submersible'),
        array('name' => '3W10AP1D', 'desc' => '3-inch water-filled submersible pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '3 inch (75mm)', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Borewell Submersible'),
        array('name' => '3W10AK1A', 'desc' => '3-inch water-filled submersible pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '3 inch (75mm)', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Borewell Submersible'),
        array('name' => '4W7BU1AU', 'desc' => '4-inch water-filled submersible, 7 stages', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '4 inch (100mm)', 'stages' => '7', 'isi' => '', 'mnre' => '', 'type' => 'Borewell Submersible'),
        array('name' => '4W14BU2EU', 'desc' => '4-inch water-filled submersible, 14 stages', 'hp' => '2.0', 'kw' => '1.5', 'phase' => 'Single Phase', 'pipe' => '4 inch (100mm)', 'stages' => '14', 'isi' => '', 'mnre' => '', 'type' => 'Borewell Submersible'),
        array('name' => '4W10BU1AU', 'desc' => '4-inch water-filled submersible, 10 stages', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '4 inch (100mm)', 'stages' => '10', 'isi' => '', 'mnre' => '', 'type' => 'Borewell Submersible'),
        array('name' => 'OWE12(1PH)Z-28', 'desc' => 'Centrifugal openwell pump', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Openwell Submersible'),
        array('name' => 'OWE052(1PH)Z-21FS', 'desc' => 'Centrifugal openwell pump', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Openwell Submersible'),
        array('name' => 'Mini Force I', 'desc' => 'Automatic pressure booster pump', 'hp' => '0.5', 'kw' => '0.37', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Booster Pump'),
        array('name' => 'CFMSMB5D1.00-V24', 'desc' => 'Centrifugal booster pump, single stage', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '1', 'isi' => '', 'mnre' => '', 'type' => 'Booster Pump'),
        array('name' => 'ARMOR1.5-DSU', 'desc' => 'Control panel with timers, 1.5 HP', 'hp' => '1.5', 'kw' => '1.1', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Control Panel'),
        array('name' => 'ARMOR1.0-CQU', 'desc' => 'Control panel compatible with submersible pumps', 'hp' => '1.0', 'kw' => '0.75', 'phase' => 'Single Phase', 'pipe' => '', 'stages' => '', 'isi' => '', 'mnre' => '', 'type' => 'Control Panel'),
    );

    foreach ($products as $prod) {
        $kwhp = '';
        if ($prod['hp'] && $prod['kw']) {
            $kwhp = $prod['hp'] . 'HP / ' . $prod['kw'] . 'kW';
        } else if ($prod['hp']) {
            $kwhp = $prod['hp'] . 'HP';
        } else if ($prod['kw']) {
            $kwhp = $prod['kw'] . 'kW';
        }

        $seoUri = strtolower(preg_replace('/[^a-z0-9]+/', '-', $prod['name']));

        $ins_data = array(
            'pumpTitle' => $prod['name'],
            'categoryPID' => $cat_id,
            'pumpFeatures' => $prod['desc'],
            'kwhp' => $kwhp,
            'supplyPhase' => $prod['phase'],
            'deliveryPipe' => $prod['pipe'],
            'noOfStage' => $prod['stages'],
            'isi' => $prod['isi'],
            'mnre' => $prod['mnre'],
            'pumpType' => $prod['type'],
            'seoUri' => $seoUri,
            'status' => 1,
            'addDate' => date('Y-m-d H:i:s')
        );

        $DB->table = $DB->pre . "pump";
        $DB->data = $ins_data;

        if ($DB->dbInsert()) {
            $results['added']++;
        } else {
            $results['failed']++;
            $results['errors'][] = $prod['name'];
        }
    }

} catch (Exception $e) {
    $results['errors'][] = $e->getMessage();
}

// Output results
ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crompton Pumps Import - Complete</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 5px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #2196F3; padding-bottom: 10px; }
        .success { color: #4CAF50; font-size: 16px; font-weight: bold; }
        .info { color: #2196F3; font-size: 14px; margin: 10px 0; }
        .error { color: #f44336; font-size: 14px; }
        .stat { margin: 15px 0; }
        .stat-label { font-weight: bold; display: inline-block; width: 150px; }
        .stat-value { color: #2196F3; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Crompton Residential Pumps Import</h1>

        <div class="success">✓ Import Complete</div>

        <div class="stat">
            <span class="stat-label">Products Added:</span>
            <span class="stat-value"><?php echo $results['added']; ?></span>
        </div>

        <div class="stat">
            <span class="stat-label">Failed:</span>
            <span class="stat-value" style="color: <?php echo $results['failed'] > 0 ? '#f44336' : '#4CAF50'; ?>">
                <?php echo $results['failed']; ?>
            </span>
        </div>

        <?php if (count($results['errors']) > 0): ?>
        <div class="error">
            <strong>Errors:</strong>
            <ul>
                <?php foreach ($results['errors'] as $err): ?>
                <li><?php echo $err; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="info">
            <p>The products are now available in your admin panel at: <strong>xadmin → Pump Management</strong></p>
            <p>They will appear on the frontend under the "Residential Pumps" category.</p>
        </div>
    </div>
</body>
</html>

<?php
// Optional: log the import
$log = "Database import completed at " . date('Y-m-d H:i:s') . " - Added: {$results['added']}, Failed: {$results['failed']}\n";
file_put_contents('database_backups/import_log.txt', $log, FILE_APPEND);
?>
