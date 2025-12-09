<?php
/**
 * Get Analytics Jobs
 * Endpoint: /tds/api/get_analytics_jobs.php
 *
 * Retrieves analytics job information from local tracking
 *
 * Parameters (POST/GET):
 *   - filing_job_id (optional): Get jobs for specific filing job
 *   - job_id (optional): Get specific job by ID
 *   - status (optional): Filter by status (submitted, queued, processing, succeeded, failed)
 *   - limit (optional): Number of records to return (default 50)
 */

header('Content-Type: application/json');
require_once __DIR__.'/../lib/auth.php';
require_once __DIR__.'/../lib/db.php';

auth_require();

try {
    $filingJobId = $_POST['filing_job_id'] ?? $_GET['filing_job_id'] ?? null;
    $jobId = $_POST['job_id'] ?? $_GET['job_id'] ?? null;
    $status = $_POST['status'] ?? $_GET['status'] ?? null;
    $limit = min((int)($_POST['limit'] ?? $_GET['limit'] ?? 50), 100);

    $sql = "SELECT * FROM analytics_jobs WHERE 1=1";
    $params = [];

    if ($filingJobId) {
        $sql .= " AND filing_job_id = ?";
        $params[] = $filingJobId;
    }

    if ($jobId) {
        $sql .= " AND job_id = ?";
        $params[] = $jobId;
    }

    if ($status) {
        if (!in_array($status, ['submitted', 'queued', 'processing', 'succeeded', 'failed'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Invalid status filter']);
            exit;
        }
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY initiated_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'msg' => 'Analytics jobs retrieved',
        'data' => [
            'count' => count($jobs),
            'jobs' => $jobs
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
