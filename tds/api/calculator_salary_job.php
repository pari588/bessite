<?php
/**
 * TDS Salary Calculator Job Endpoints (Async)
 * Submit bulk salary data and poll job status
 *
 * POST /api/calculator_salary_job.php - Submit salary TDS calculation job
 * GET /api/calculator_salary_job.php - Poll job status
 *
 * Required: TDS Session with firm_id set
 */

require_once '../lib/session.php';
require_once '../lib/db.php';
require_once '../lib/SandboxTDSAPI.php';

header('Content-Type: application/json');

try {
  // Initialize API client
  $api = new SandboxTDSAPI($_SESSION['firm_id'], $pdo, function($stage, $status, $msg, $req = null, $res = null) {
    // Logging callback
  });

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Submit new salary TDS calculation job
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
      throw new Exception('Invalid JSON request body');
    }

    // Validate required fields
    if (empty($input['employees']) || !is_array($input['employees'])) {
      throw new Exception('employees must be an array of employee data');
    }

    if (empty($input['financial_year'])) {
      throw new Exception('financial_year is required (e.g., "2024-25")');
    }

    // Validate financial year format
    if (!preg_match('/^\d{4}-\d{2}$/', $input['financial_year'])) {
      throw new Exception('financial_year must be in format YYYY-YY (e.g., "2024-25")');
    }

    // Validate employee data structure
    foreach ($input['employees'] as $idx => $emp) {
      if (empty($emp['employee_id'])) {
        throw new Exception("Employee #" . ($idx + 1) . " missing employee_id");
      }
      if (empty($emp['pan'])) {
        throw new Exception("Employee #" . ($idx + 1) . " missing PAN");
      }
      if (!isset($emp['gross_salary']) || $emp['gross_salary'] < 0) {
        throw new Exception("Employee #" . ($idx + 1) . " missing or invalid gross_salary");
      }
      if (empty($emp['month'])) {
        throw new Exception("Employee #" . ($idx + 1) . " missing month");
      }
    }

    if (count($input['employees']) > 1000) {
      throw new Exception('Maximum 1000 employees per job. For larger batches, submit multiple jobs.');
    }

    // Submit job
    $result = $api->submitSalaryTDSJob($input['employees'], $input['financial_year']);

    if ($result['status'] === 'failed') {
      http_response_code(400);
      echo json_encode(['error' => $result['error'] ?? 'Job submission failed']);
      exit;
    }

    // Return job details
    http_response_code(201);
    echo json_encode([
      'status' => 'success',
      'data' => [
        'job_id' => $result['job_id'],
        'financial_year' => $result['financial_year'],
        'employee_count' => $result['employee_count'],
        'job_status' => $result['job_status']
      ]
    ]);

  } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Poll job status
    $jobId = $_GET['job_id'] ?? null;
    $financialYear = $_GET['financial_year'] ?? null;

    if (!$jobId) {
      throw new Exception('job_id query parameter is required');
    }

    if (!$financialYear) {
      throw new Exception('financial_year query parameter is required');
    }

    // Poll status
    $result = $api->pollSalaryTDSJob($jobId, $financialYear);

    if ($result['status'] === 'failed') {
      http_response_code(400);
      echo json_encode(['error' => $result['error'] ?? 'Job polling failed']);
      exit;
    }

    // Return job status
    http_response_code(200);
    echo json_encode([
      'status' => 'success',
      'data' => [
        'job_id' => $result['job_id'],
        'financial_year' => $result['financial_year'],
        'job_status' => $result['status'],
        'workbook_url' => $result['workbook_url'],
        'record_count' => $result['record_count']
      ]
    ]);

  } else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST or GET.']);
  }

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'error' => $e->getMessage(),
    'details' => 'Failed to process salary TDS calculation job'
  ]);
}

?>
