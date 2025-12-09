<?php
/**
 * Submit Analytics Job
 * Endpoint: /tds/api/submit_analytics_job.php
 *
 * Initiates a Potential Notice Analysis job at Sandbox
 * This checks the TDS return for potential compliance issues before filing
 *
 * Parameters (POST):
 *   - tan (required): TAN identifier (e.g., AHMA09719B)
 *   - quarter (required): Q1, Q2, Q3, or Q4
 *   - form (required): 24Q, 26Q, or 27Q
 *   - fy (required): Financial year (e.g., FY 2024-25)
 *   - filing_job_id (optional): Local filing job ID to link with
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
    $filingJobId = $_POST['filing_job_id'] ?? $_GET['filing_job_id'] ?? null;

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

    // Submit the job
    $result = $api->submitAnalyticsJob($tan, $quarter, $form, $fy);

    // If successful and we have a filing job ID, create tracking record
    if ($result['status'] === 'success' && $filingJobId) {
        try {
            $userId = $_SESSION['uid'] ?? null;
            $stmt = $pdo->prepare("
                INSERT INTO analytics_jobs (
                    filing_job_id, firm_id, job_id, job_type, fy, quarter, form, status, created_by
                ) VALUES (?, ?, ?, 'potential_notices', ?, ?, ?, 'submitted', ?)
            ");

            // Convert FY format from "FY 2024-25" to "2024-25"
            $fyShort = str_replace('FY ', '', $fy);

            $stmt->execute([
                $filingJobId,
                $firmId,
                $result['job_id'],
                $fyShort,
                $quarter,
                $form,
                $userId
            ]);
        } catch (Exception $e) {
            // Log but don't fail - submission succeeded even if local tracking fails
            error_log("Failed to create analytics job tracking: " . $e->getMessage());
        }
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Analytics job submitted successfully',
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
