<?php
/**
 * Analytics API - Submit TDS Potential Notice Analysis Job
 *
 * Submits Form 24Q, 26Q, or 27Q for risk analysis
 * Endpoint: /tds/api/submit_analytics_job_tds.php
 * Method: POST
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/SandboxTDSAPI.php';

header('Content-Type: application/json');

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }

    // Validate required fields
    $required_fields = ['tan', 'quarter', 'form', 'fy', 'form_content'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit;
        }
    }

    // Validate form type
    $valid_forms = ['24Q', '26Q', '27Q'];
    if (!in_array($input['form'], $valid_forms)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid form type. Allowed: 24Q, 26Q, 27Q']);
        exit;
    }

    // Validate quarter
    if (!preg_match('/^Q[1-4]$/', $input['quarter'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid quarter. Format: Q1, Q2, Q3, Q4']);
        exit;
    }

    // Validate TAN format (Indian Tax Account Number)
    if (!preg_match('/^[A-Z]{4}[0-9]{5}[A-Z]{1}$/', strtoupper($input['tan']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid TAN format. Expected: XXXXXNXXXXX (e.g., AHMA09719B)']);
        exit;
    }

    // Validate FY format (e.g., FY 2024-25)
    if (!preg_match('/^FY \d{4}-\d{2}$/', $input['fy'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid FY format. Expected: FY YYYY-YY (e.g., FY 2024-25)']);
        exit;
    }

    // Initialize API
    $api = new SandboxTDSAPI(
        SANDBOX_API_KEY,
        SANDBOX_API_SECRET,
        function($msg) { /* logging callback */ }
    );

    // Submit job
    $result = $api->submitTDSAnalyticsJob(
        strtoupper($input['tan']),
        $input['quarter'],
        $input['form'],
        $input['fy'],
        $input['form_content']
    );

    if ($result['error']) {
        http_response_code(400);
        echo json_encode([
            'error' => $result['error'],
            'details' => $result['details'] ?? null
        ]);
        exit;
    }

    // Success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'job_id' => $result['job_id'],
        'tan' => $result['tan'],
        'quarter' => $result['quarter'],
        'form' => $result['form'],
        'financial_year' => $result['financial_year'],
        'status' => $result['job_status'],
        'created_at' => $result['created_at'],
        'message' => "Analytics job submitted successfully. Job ID: {$result['job_id']}"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
