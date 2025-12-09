<?php
/**
 * Fetch Analytics Jobs
 * Endpoint: /tds/api/fetch_analytics_jobs.php
 *
 * Retrieves list of Potential Notice Analysis jobs from Sandbox
 * This allows you to see all historical analytics jobs for a TAN/form combination
 *
 * Parameters (POST/GET):
 *   - tan (required): TAN identifier (e.g., AHMA09719B)
 *   - quarter (required): Q1, Q2, Q3, or Q4
 *   - form (required): 24Q, 26Q, or 27Q
 *   - fy (required): Financial year (e.g., FY 2024-25)
 *   - page_size (optional): Number of records to return (default 20, max 50)
 *   - last_evaluated_key (optional): Pagination marker for next page
 */

header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';

auth_require();

try {
    $tan = $_POST['tan'] ?? $_GET['tan'] ?? '';
    $quarter = $_POST['quarter'] ?? $_GET['quarter'] ?? '';
    $form = $_POST['form'] ?? $_GET['form'] ?? '';
    $fy = $_POST['fy'] ?? $_GET['fy'] ?? '';
    $pageSize = (int)($_POST['page_size'] ?? $_GET['page_size'] ?? 20);
    $lastEvaluatedKey = $_POST['last_evaluated_key'] ?? $_GET['last_evaluated_key'] ?? null;

    // Validate required parameters
    if (empty($tan) || empty($quarter) || empty($form) || empty($fy)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing required parameters: tan, quarter, form, fy']);
        exit;
    }

    // Validate TAN format
    if (!preg_match('/^[A-Z]{4}[0-9]{5}[A-Z]{1}$/', $tan)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid TAN format. Expected format: ABCD12345E']);
        exit;
    }

    // Validate quarter
    if (!in_array($quarter, ['Q1', 'Q2', 'Q3', 'Q4'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid quarter. Must be Q1, Q2, Q3, or Q4']);
        exit;
    }

    // Validate form
    if (!in_array($form, ['24Q', '26Q', '27Q'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid form. Must be 24Q, 26Q, or 27Q']);
        exit;
    }

    // Validate FY format
    if (!preg_match('/^FY \d{4}-\d{2}$/', $fy)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid FY format. Expected format: FY 2024-25']);
        exit;
    }

    // Validate page size
    $pageSize = min(max($pageSize, 1), 50);

    // Get firm ID
    $stmt = $pdo->prepare("SELECT id FROM firms LIMIT 1");
    $stmt->execute();
    $firm = $stmt->fetch();
    $firmId = $firm['id'] ?? null;

    if (!$firmId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'No firm configured']);
        exit;
    }

    // Initialize Sandbox API
    $api = new SandboxTDSAPI($firmId, $pdo);

    // Fetch the jobs
    $result = $api->fetchAnalyticsJobs($tan, $quarter, $form, $fy, $pageSize, $lastEvaluatedKey);

    echo json_encode([
        'ok' => true,
        'msg' => 'Analytics jobs retrieved',
        'data' => $result
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}
?>
