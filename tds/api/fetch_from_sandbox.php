<?php
/**
 * Fetch real data from Sandbox.co.in API
 * Endpoint: /tds/api/fetch_from_sandbox.php
 *
 * Parameters:
 *   - action: 'invoices', 'challans', or 'deductees'
 *   - fy: Financial Year (e.g., "2025-26")
 *   - quarter: Quarter (Q1, Q2, Q3, Q4)
 *   - firm_id: Firm ID to fetch data for
 */

require_once __DIR__.'/../lib/auth.php';
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxDataFetcher.php';

// Check authentication
auth_require();

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $fy = $_GET['fy'] ?? $_POST['fy'] ?? '';
    $quarter = $_GET['quarter'] ?? $_POST['quarter'] ?? '';
    $firmId = $_GET['firm_id'] ?? $_POST['firm_id'] ?? null;

    if (empty($action) || empty($fy) || empty($quarter)) {
        throw new Exception("Missing required parameters: action, fy, quarter");
    }

    // Validate FY format
    if (!preg_match('/^\d{4}-\d{2}$/', $fy)) {
        throw new Exception("Invalid FY format. Use format like: 2025-26");
    }

    // Validate quarter
    if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
        throw new Exception("Invalid quarter. Use Q1, Q2, Q3, or Q4");
    }

    // Get firm ID if not provided
    if (empty($firmId)) {
        $stmt = $pdo->prepare("SELECT id FROM firms LIMIT 1");
        $stmt->execute();
        $firm = $stmt->fetch();
        $firmId = $firm['id'] ?? null;
    }

    if (empty($firmId)) {
        throw new Exception("No firm configured");
    }

    // Initialize fetcher
    $fetcher = new SandboxDataFetcher($pdo, $firmId);

    $result = [];

    // Handle different actions
    switch ($action) {
        case 'invoices':
            $invoices = $fetcher->fetchInvoices($fy, $quarter);
            $import = $fetcher->importInvoices($invoices, $fy, $quarter);
            $result = [
                'status' => 'success',
                'action' => 'invoices',
                'message' => 'Invoices fetched and imported from Sandbox',
                'data' => [
                    'fetched' => count($invoices),
                    'imported' => $import['count'],
                    'details' => $import
                ]
            ];
            break;

        case 'challans':
            $challans = $fetcher->fetchChallans($fy, $quarter);
            $import = $fetcher->importChallans($challans, $fy, $quarter);
            $result = [
                'status' => 'success',
                'action' => 'challans',
                'message' => 'Challans fetched and imported from Sandbox',
                'data' => [
                    'fetched' => count($challans),
                    'imported' => $import['count'],
                    'details' => $import
                ]
            ];
            break;

        case 'deductees':
            $deductees = $fetcher->fetchDeductees($fy, $quarter);
            $result = [
                'status' => 'success',
                'action' => 'deductees',
                'message' => 'Deductees fetched from Sandbox',
                'data' => [
                    'count' => count($deductees),
                    'deductees' => $deductees
                ]
            ];
            break;

        case 'all':
            $invoices = $fetcher->fetchInvoices($fy, $quarter);
            $challans = $fetcher->fetchChallans($fy, $quarter);
            $deductees = $fetcher->fetchDeductees($fy, $quarter);

            $invImport = $fetcher->importInvoices($invoices, $fy, $quarter);
            $chalImport = $fetcher->importChallans($challans, $fy, $quarter);

            $result = [
                'status' => 'success',
                'action' => 'all',
                'message' => 'All data fetched and imported from Sandbox',
                'data' => [
                    'invoices' => [
                        'fetched' => count($invoices),
                        'imported' => $invImport['count']
                    ],
                    'challans' => [
                        'fetched' => count($challans),
                        'imported' => $chalImport['count']
                    ],
                    'deductees' => [
                        'count' => count($deductees)
                    ],
                    'summary' => "Imported " . ($invImport['count'] + $chalImport['count']) . " records total"
                ]
            ];
            break;

        default:
            throw new Exception("Unknown action: $action");
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
