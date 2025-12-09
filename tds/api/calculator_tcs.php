<?php
/**
 * TCS Calculator Endpoint
 * Calculates TCS (Tax Collected at Source) on transactions
 *
 * Endpoint: POST /api/calculator_tcs.php
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
  $required = ['collectee_type', 'is_pan_available', 'residential_status', 'is_206cca_applicable',
               'is_pan_operative', 'nature_of_payment', 'payment_amount', 'payment_date'];

  foreach ($required as $field) {
    if (!isset($input[$field]) && $input[$field] !== false) {
      throw new Exception("Missing required field: $field");
    }
  }

  // Validate field types and values
  if (!in_array($input['collectee_type'], ['individual', 'huf', 'company', 'firm', 'trust', 'local_authority', 'body_of_individuals'])) {
    throw new Exception("Invalid collectee_type. Must be one of: individual, huf, company, firm, trust, local_authority, body_of_individuals");
  }

  if (!in_array($input['residential_status'], ['resident', 'non_resident'])) {
    throw new Exception("Invalid residential_status. Must be 'resident' or 'non_resident'");
  }

  if (!is_bool($input['is_pan_available'])) {
    throw new Exception("is_pan_available must be a boolean (true/false)");
  }

  if (!is_bool($input['is_206cca_applicable'])) {
    throw new Exception("is_206cca_applicable must be a boolean");
  }

  if (!is_bool($input['is_pan_operative'])) {
    throw new Exception("is_pan_operative must be a boolean");
  }

  if (!is_numeric($input['payment_amount']) || $input['payment_amount'] <= 0) {
    throw new Exception("payment_amount must be a positive number");
  }

  if (!is_numeric($input['payment_date'])) {
    throw new Exception("payment_date must be in milliseconds (EPOCH timestamp × 1000)");
  }

  // Initialize API client
  $api = new SandboxTDSAPI($_SESSION['firm_id'], $pdo, function($stage, $status, $msg, $req = null, $res = null) {
    // Logging callback
  }, 'production');

  // Call Calculator API
  $result = $api->calculateTCS(
    $input['collectee_type'],
    $input['is_pan_available'],
    $input['residential_status'],
    $input['is_206cca_applicable'],
    $input['is_pan_operative'],
    $input['nature_of_payment'],
    $input['payment_amount'],
    $input['payment_date']
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
      'collection_rate' => $result['collection_rate'],
      'collection_amount' => $result['collection_amount'],
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
    'details' => 'Failed to calculate TCS on transaction'
  ]);
}

?>
