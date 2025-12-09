<?php
/**
 * Poll Sandbox Analytics Job
 * Endpoint: /tds/api/poll_analytics_job.php
 *
 * Polls the status of a Potential Notice Analysis job from Sandbox Analytics API
 *
 * Parameters (POST/GET):
 *   - job_id (required): Analytics job ID from Sandbox
 *   - filing_job_id (optional): Local TDS filing job ID for tracking
 */

header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/SandboxTDSAPI.php';

auth_require();

try {
    $jobId = $_POST['job_id'] ?? $_GET['job_id'] ?? '';
    $filingJobId = $_POST['filing_job_id'] ?? $_GET['filing_job_id'] ?? null;

    if (empty($jobId)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Missing required parameter: job_id']);
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

    // Poll the job status
    $result = $api->pollAnalyticsJob($jobId);

    // Update local analytics_jobs table if we have tracking info
    if ($filingJobId) {
        try {
            $stmt = $pdo->prepare("
                UPDATE analytics_jobs
                SET status = ?,
                    report_url = ?,
                    error_message = ?,
                    last_polled_at = NOW(),
                    poll_count = poll_count + 1,
                    completed_at = IF(? IN ('succeeded', 'failed'), NOW(), NULL)
                WHERE job_id = ? AND filing_job_id = ?
            ");
            $stmt->execute([
                $result['status'],
                $result['report_url'],
                $result['error'] ?? null,
                $result['status'],
                $jobId,
                $filingJobId
            ]);
        } catch (Exception $e) {
            // Log but don't fail - update succeeded even if local tracking fails
            error_log("Failed to update analytics_jobs: " . $e->getMessage());
        }
    }

    echo json_encode([
        'ok' => true,
        'msg' => 'Analytics job status retrieved',
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
