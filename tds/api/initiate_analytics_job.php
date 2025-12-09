<?php
/**
 * Initiate Analytics Job
 * Endpoint: /tds/api/initiate_analytics_job.php
 *
 * Creates a record for an analytics job being initiated
 * Used to track Potential Notice Analysis jobs from Sandbox
 *
 * Parameters (POST):
 *   - filing_job_id (required): Local filing job ID
 *   - job_id (required): Job ID returned from Sandbox Analytics API
 *   - job_type (optional): Type of job - 'potential_notices', 'risk_assessment', 'form_validation'
 *   - fy (optional): Financial year
 *   - quarter (optional): Quarter
 *   - form (optional): Form type (26Q, 27Q, etc)
 */

header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
require_once __DIR__.'/../lib/db.php';

auth_require();

try {
    $filingJobId = $_POST['filing_job_id'] ?? null;
    $jobId = $_POST['job_id'] ?? null;
    $jobType = $_POST['job_type'] ?? 'potential_notices';
    $fy = $_POST['fy'] ?? null;
    $quarter = $_POST['quarter'] ?? null;
    $form = $_POST['form'] ?? null;

    if (!$filingJobId || !$jobId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing required parameters: filing_job_id, job_id']);
        exit;
    }

    // Validate job type
    if (!in_array($jobType, ['potential_notices', 'risk_assessment', 'form_validation'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Invalid job_type']);
        exit;
    }

    // Get filing job details to extract firm_id and auto-fill fy/quarter
    $stmt = $pdo->prepare("SELECT firm_id, fy, quarter FROM tds_filing_jobs WHERE id = ?");
    $stmt->execute([$filingJobId]);
    $filingJob = $stmt->fetch();

    if (!$filingJob) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Filing job not found']);
        exit;
    }

    $firmId = $filingJob['firm_id'];
    $fy = $fy ?? $filingJob['fy'];
    $quarter = $quarter ?? $filingJob['quarter'];

    // Check if job_id already exists
    $stmt = $pdo->prepare("SELECT id FROM analytics_jobs WHERE job_id = ?");
    $stmt->execute([$jobId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'Analytics job already tracked']);
        exit;
    }

    // Insert new analytics job record
    $userId = $_SESSION['uid'] ?? null;
    $stmt = $pdo->prepare("
        INSERT INTO analytics_jobs (
            filing_job_id, firm_id, job_id, job_type, fy, quarter, form, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted', ?)
    ");
    $stmt->execute([
        $filingJobId,
        $firmId,
        $jobId,
        $jobType,
        $fy,
        $quarter,
        $form,
        $userId
    ]);

    $analyticsJobId = $pdo->lastInsertId();

    echo json_encode([
        'ok' => true,
        'msg' => 'Analytics job initiated and tracking started',
        'data' => [
            'analytics_job_id' => $analyticsJobId,
            'job_id' => $jobId,
            'job_type' => $jobType,
            'status' => 'submitted',
            'initiated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => $e->getMessage()
    ]);
}
?>
