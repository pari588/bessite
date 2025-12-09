<?php
/**
 * TDS Non-Salary Calculator Endpoint
 * Calculates TDS on non-salary payments (contract fees, interest, rent, winnings, etc.)
 *
 * Endpoint: POST /api/calculator_non_salary.php
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
  $required = ['deductee_type', 'is_pan_available', 'residential_status', 'is_206ab_applicable',
               'is_pan_operative', 'nature_of_payment', 'credit_amount', 'credit_date'];

  foreach ($required as $field) {
    if (!isset($input[$field]) && $input[$field] !== false) {
      throw new Exception("Missing required field: $field");
    }
  }

  // Validate field types and values
  if (!in_array($input['deductee_type'], ['individual', 'huf', 'company', 'firm', 'trust', 'local_authority', 'body_of_individuals'])) {
    throw new Exception("Invalid deductee_type. Must be one of: individual, huf, company, firm, trust, local_authority, body_of_individuals");
  }

  if (!in_array($input['residential_status'], ['resident', 'non_resident'])) {
    throw new Exception("Invalid residential_status. Must be 'resident' or 'non_resident'");
  }

  if (!is_bool($input['is_pan_available'])) {
    throw new Exception("is_pan_available must be a boolean (true/false)");
  }

  if (!is_bool($input['is_206ab_applicable'])) {
    throw new Exception("is_206ab_applicable must be a boolean");
  }

  if (!is_bool($input['is_pan_operative'])) {
    throw new Exception("is_pan_operative must be a boolean");
  }

  if (!is_numeric($input['credit_amount']) || $input['credit_amount'] <= 0) {
    throw new Exception("credit_amount must be a positive number");
  }

  if (!is_numeric($input['credit_date'])) {
    throw new Exception("credit_date must be in milliseconds (EPOCH timestamp × 1000)");
  }

  // Initialize API client
  $api = new SandboxTDSAPI($_SESSION['firm_id'], $pdo, function($stage, $status, $msg, $req = null, $res = null) {
    // Logging callback - can be used to log API calls
  }, 'production');

  // Call Calculator API
  $result = $api->calculateNonSalaryTDS(
    $input['deductee_type'],
    $input['is_pan_available'],
    $input['residential_status'],
    $input['is_206ab_applicable'],
    $input['is_pan_operative'],
    $input['nature_of_payment'],
    $input['credit_amount'],
    $input['credit_date']
  );

  if ($result['status'] === 'failed') {
    http_response_code(400);
    echo json_encode(['error' => $result['error'] ?? 'Calculation failed']);
    exit;
  }

  // Return successful calculation
  http_response_code(200);
  echo json_encode([
    'status' => 'success',
    'data' => [
      'deduction_rate' => $result['deduction_rate'],
      'deduction_amount' => $result['deduction_amount'],
      'section' => $result['section'],
      'threshold' => $result['threshold'],
      'due_date' => $result['due_date'],
      'pan_status' => $result['pan_status']
    ]
  ]);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'error' => $e->getMessage(),
    'details' => 'Failed to calculate TDS on non-salary payment'
  ]);
}

?>
