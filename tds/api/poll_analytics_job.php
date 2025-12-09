<?php
/**
 * Analytics API - Poll Analytics Job Status
 *
 * Polls the status of a TDS or TCS analytics job
 * Endpoint: /tds/api/poll_analytics_job.php
 * Method: GET
 * Query Parameters: job_id, type (tds or tcs)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/SandboxTDSAPI.php';

header('Content-Type: application/json');

try {
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get query parameters
    $job_id = $_GET['job_id'] ?? null;
    $type = $_GET['type'] ?? 'tds'; // Default to TDS

    // Validate job_id
    if (empty($job_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameter: job_id']);
        exit;
    }

    // Validate type
    if (!in_array($type, ['tds', 'tcs'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type. Allowed: tds, tcs']);
        exit;
    }

    // Initialize API
    $api = new SandboxTDSAPI(
        SANDBOX_API_KEY,
        SANDBOX_API_SECRET,
        function($msg) { /* logging callback */ }
    );

    // Poll job based on type
    if ($type === 'tds') {
        $result = $api->pollTDSAnalyticsJob($job_id);
    } else {
        $result = $api->pollTCSAnalyticsJob($job_id);
    }

    if ($result['error']) {
        http_response_code(400);
        echo json_encode([
            'error' => $result['error'],
            'details' => $result['details'] ?? null
        ]);
        exit;
    }

    // Success response - return full result
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'job_id' => $job_id,
        'type' => $type,
        'status' => $result['status'],
        'risk_level' => $result['risk_level'] ?? null,
        'risk_score' => $result['risk_score'] ?? null,
        'potential_notices_count' => $result['potential_notices_count'] ?? null,
        'report_url' => $result['report_url'] ?? null,
        'issues' => $result['issues'] ?? [],
        'error' => $result['error'] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
