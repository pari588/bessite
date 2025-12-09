<?php
/**
 * Analytics API - Fetch Analytics Jobs
 *
 * Fetches historical TDS or TCS analytics jobs with pagination and filtering
 * Endpoint: /tds/api/fetch_analytics_jobs.php
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

    // Validate type parameter
    $type = $input['type'] ?? 'tds';
    if (!in_array($type, ['tds', 'tcs'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type. Allowed: tds, tcs']);
        exit;
    }

    // Validate required fields for TDS
    if ($type === 'tds') {
        $required_fields = ['tan', 'quarter', 'fy'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field for TDS: $field"]);
                exit;
            }
        }

        $form = $input['form'] ?? null;
        if ($form && !in_array($form, ['24Q', '26Q', '27Q'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid form type. Allowed: 24Q, 26Q, 27Q']);
            exit;
        }
    } else {
        // TCS requires tan, quarter, fy
        $required_fields = ['tan', 'quarter', 'fy'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field for TCS: $field"]);
                exit;
            }
        }
    }

    // Validate quarter
    if (!preg_match('/^Q[1-4]$/', $input['quarter'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid quarter. Format: Q1, Q2, Q3, Q4']);
        exit;
    }

    // Validate TAN format
    if (!preg_match('/^[A-Z]{4}[0-9]{5}[A-Z]{1}$/', strtoupper($input['tan']))) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid TAN format. Expected: XXXXXNXXXXX (e.g., AHMA09719B)']);
        exit;
    }

    // Validate FY format
    if (!preg_match('/^FY \d{4}-\d{2}$/', $input['fy'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid FY format. Expected: FY YYYY-YY (e.g., FY 2024-25)']);
        exit;
    }

    // Pagination parameters
    $page_size = $input['page_size'] ?? 50;
    $last_evaluated_key = $input['last_evaluated_key'] ?? null;

    // Validate page size
    if ($page_size < 1 || $page_size > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Page size must be between 1 and 100']);
        exit;
    }

    // Initialize API
    $firm_id = $_SESSION['firm_id'] ?? 1;
    $api = new SandboxTDSAPI($firm_id, $pdo, function($msg) { /* logging callback */ }, 'production');

    // Fetch jobs based on type
    if ($type === 'tds') {
        $result = $api->fetchTDSAnalyticsJobs(
            strtoupper($input['tan']),
            $input['quarter'],
            $input['form'] ?? null,
            $input['fy'],
            $page_size,
            $last_evaluated_key
        );
    } else {
        $result = $api->fetchTCSAnalyticsJobs(
            strtoupper($input['tan']),
            $input['quarter'],
            $input['fy'],
            $page_size,
            $last_evaluated_key
        );
    }

    if ($result['error']) {
        http_response_code(400);
        echo json_encode([
            'error' => $result['error'],
            'details' => $result['details'] ?? null
        ]);
        exit;
    }

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'type' => $type,
        'count' => $result['count'] ?? 0,
        'jobs' => $result['jobs'] ?? [],
        'has_more' => $result['has_more'] ?? false,
        'last_evaluated_key' => $result['last_evaluated_key'] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>
