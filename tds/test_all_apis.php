<?php
/**
 * Comprehensive API Test Script
 * Tests all three Sandbox APIs (Reports, Analytics, Calculator)
 * Date: December 9, 2025
 */

require_once __DIR__.'/lib/db.php';
require_once __DIR__.'/lib/SandboxTDSAPI.php';
require_once __DIR__.'/lib/helpers.php';

echo "=== COMPREHENSIVE API TEST SCRIPT ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: Production\n";
echo str_repeat("=", 50) . "\n\n";

// Get firm data
$firm = $pdo->query('SELECT id, tan FROM firms LIMIT 1')->fetch();
$firm_id = $firm['id'] ?? 1;
$firm_tan = $firm['tan'] ?? 'MUMT14861A';

echo "Firm ID: $firm_id\n";
echo "Firm TAN: $firm_tan\n";
echo str_repeat("-", 50) . "\n\n";

// Test 1: Reports API - TDS
echo "TEST 1: Reports API - TDS Form (24Q)\n";
echo str_repeat("-", 50) . "\n";
try {
    $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');

    // Get current FY
    $today = date('Y-m-d');
    [$curFy, $curQ] = fy_quarter_from_date($today);

    echo "Submitting TDS Report (24Q)...\n";
    echo "  TAN: $firm_tan\n";
    echo "  Quarter: Q4\n";
    echo "  Form: 24Q\n";
    echo "  Financial Year: FY $curFy\n";

    $result = $api->submitTDSReportsJob($firm_tan, 'Q4', '24Q', "FY $curFy");

    if ($result['status'] === 'success') {
        echo "✅ SUCCESS - Job ID: " . $result['job_id'] . "\n";
    } else {
        echo "❌ FAILED - Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Reports API - TCS
echo "TEST 2: Reports API - TCS Form (27EQ)\n";
echo str_repeat("-", 50) . "\n";
try {
    $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');

    $today = date('Y-m-d');
    [$curFy, $curQ] = fy_quarter_from_date($today);

    echo "Submitting TCS Report (27EQ)...\n";
    echo "  TAN: $firm_tan\n";
    echo "  Quarter: Q1\n";
    echo "  Financial Year: FY $curFy\n";

    $result = $api->submitTCSReportsJob($firm_tan, 'Q1', "FY $curFy");

    if ($result['status'] === 'success') {
        echo "✅ SUCCESS - Job ID: " . $result['job_id'] . "\n";
    } else {
        echo "❌ FAILED - Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Analytics API - TDS
echo "TEST 3: Analytics API - TDS\n";
echo str_repeat("-", 50) . "\n";
try {
    $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');

    $today = date('Y-m-d');
    [$curFy, $curQ] = fy_quarter_from_date($today);

    // Create simple test form content
    $testContent = json_encode([
        'test' => 'data',
        'timestamp' => time()
    ]);

    echo "Submitting TDS Analytics Job...\n";
    echo "  TAN: $firm_tan\n";
    echo "  Quarter: $curQ\n";
    echo "  Form: 26Q\n";
    echo "  Financial Year: FY $curFy\n";

    $result = $api->submitTDSAnalyticsJob($firm_tan, $curQ, '26Q', "FY $curFy", $testContent);

    if ($result['status'] === 'success') {
        echo "✅ SUCCESS - Job ID: " . $result['job_id'] . "\n";
    } else {
        echo "❌ FAILED - Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Analytics API - TCS
echo "TEST 4: Analytics API - TCS\n";
echo str_repeat("-", 50) . "\n";
try {
    $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');

    $today = date('Y-m-d');
    [$curFy, $curQ] = fy_quarter_from_date($today);

    $testContent = json_encode([
        'test' => 'data',
        'timestamp' => time()
    ]);

    echo "Submitting TCS Analytics Job...\n";
    echo "  TAN: $firm_tan\n";
    echo "  Quarter: $curQ\n";
    echo "  Financial Year: FY $curFy\n";

    $result = $api->submitTCSAnalyticsJob($firm_tan, $curQ, "FY $curFy", $testContent);

    if ($result['status'] === 'success') {
        echo "✅ SUCCESS - Job ID: " . $result['job_id'] . "\n";
    } else {
        echo "❌ FAILED - Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION - " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Calculator API
echo "TEST 5: Calculator API\n";
echo str_repeat("-", 50) . "\n";
try {
    $api = new SandboxTDSAPI($firm_id, $pdo, null, 'production');

    echo "Testing Calculator API...\n";
    echo "  TAN: $firm_tan\n";

    $result = $api->calculateTDSLiability($firm_tan, [
        'income' => 100000,
        'tax_rate' => 10
    ]);

    if ($result['status'] === 'success') {
        echo "✅ SUCCESS - Result: " . json_encode($result['data']) . "\n";
    } else {
        echo "❌ FAILED - Error: " . ($result['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION - " . $e->getMessage() . "\n";
}
echo "\n";

echo str_repeat("=", 50) . "\n";
echo "TEST COMPLETE\n";
echo str_repeat("=", 50) . "\n";
?>
