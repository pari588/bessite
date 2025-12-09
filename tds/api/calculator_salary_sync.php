<?php
/**
 * TDS Salary Calculator Synchronous Endpoint
 * Calculate TDS on salary immediately and return Excel workbook
 *
 * Endpoint: POST /api/calculator_salary_sync.php
 * Required: TDS Session with firm_id set
 */

require_once '../lib/session.php';
require_once '../lib/db.php';
require_once '../lib/SandboxTDSAPI.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed. Use POST.']);
  exit;
}

try {
  // Get request body
  $input = json_decode(file_get_contents('php://input'), true);

  if (!$input) {
    throw new Exception('Invalid JSON request body');
  }

  // Validate required fields
  if (empty($input['employees']) || !is_array($input['employees'])) {
    throw new Exception('employees must be an array of employee data with salary details');
  }

  if (empty($input['financial_year'])) {
    throw new Exception('financial_year is required (e.g., "2024-25")');
  }

  // Validate financial year format
  if (!preg_match('/^\d{4}-\d{2}$/', $input['financial_year'])) {
    throw new Exception('financial_year must be in format YYYY-YY (e.g., "2024-25")');
  }

  // Validate employee data structure for sync calculation
  foreach ($input['employees'] as $idx => $emp) {
    if (empty($emp['employee_id'])) {
      throw new Exception("Employee #" . ($idx + 1) . " missing employee_id");
    }
    if (empty($emp['pan'])) {
      throw new Exception("Employee #" . ($idx + 1) . " missing PAN");
    }
    // Sync requires detailed salary breakdown
    if (empty($emp['salary_details']) || !is_array($emp['salary_details'])) {
      throw new Exception("Employee #" . ($idx + 1) . " missing salary_details object");
    }
    if (!isset($emp['salary_details']['basic']) || $emp['salary_details']['basic'] < 0) {
      throw new Exception("Employee #" . ($idx + 1) . " salary_details.basic is required");
    }
  }

  if (count($input['employees']) > 1000) {
    throw new Exception('Maximum 1000 employees per request. For larger batches, use async job submission.');
  }

  // Initialize API client
  $api = new SandboxTDSAPI($_SESSION['firm_id'], $pdo, function($stage, $status, $msg, $req = null, $res = null) {
    // Logging callback
  }, 'production');

  // Call Calculator API
  $result = $api->calculateSalaryTDSSync($input['employees'], $input['financial_year']);

  if ($result['status'] === 'failed') {
    http_response_code(400);
    echo json_encode(['error' => $result['error'] ?? 'Calculation failed']);
    exit;
  }

  // Return successful calculation with workbook
  http_response_code(200);
  echo json_encode([
    'status' => 'success',
    'data' => [
      'workbook_data' => $result['workbook_data'],
      'record_count' => $result['record_count'],
      'financial_year' => $result['financial_year'],
      'employee_count' => $result['employee_count']
    ]
  ]);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'error' => $e->getMessage(),
    'details' => 'Failed to calculate salary TDS'
  ]);
}

?>
