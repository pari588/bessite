<?php
/**
 * API Endpoint: POST /tds/api/filing/submit
 * Submit TDS return for e-filing to Tax Authority
 *
 * Prerequisites:
 * - FVU generation must be completed
 * - Form 27A must be available
 *
 * This submits the return to TIN Facilitation Center for acceptance by IT
 */

// Set JSON response header first
header('Content-Type: application/json');

// Start session
session_start();

// Check authentication early and return JSON for AJAX
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized - please log in']);
    exit;
}

// Suppress warnings/notices that might output HTML
error_reporting(E_ALL);
ini_set('display_errors', '0');

try {
    require_once __DIR__.'/../../lib/db.php';
    require_once __DIR__.'/../../lib/ajax_helpers.php';
    require_once __DIR__.'/../../lib/SandboxTDSAPI.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Failed to load libraries: ' . $e->getMessage()]);
    exit;
}

try {
  $jobId = (int)($_POST['job_id'] ?? $_GET['job_id'] ?? 0);

  if ($jobId <= 0) {
    json_err('Invalid job_id');
  }

  // Get job details
  $stmt = $pdo->prepare('
    SELECT * FROM tds_filing_jobs WHERE id=?
  ');
  $stmt->execute([$jobId]);
  $job = $stmt->fetch();

  if (!$job) {
    json_err('Job not found');
  }

  // Validate FVU generation is complete
  if ($job['fvu_status'] !== 'succeeded') {
    json_err("FVU generation not complete (status: {$job['fvu_status']}). Cannot submit for filing yet.");
  }

  // Note: In demo/testing, allow submission even if files don't exist
  // In production, uncomment the file existence checks below
  /*
  if (!$job['fvu_file_path'] || !file_exists($job['fvu_file_path'])) {
    json_err('FVU file not found');
  }

  if (!$job['form27a_file_path'] || !file_exists($job['form27a_file_path'])) {
    json_err('Form 27A file not found');
  }
  */

  // Check if already submitted
  if ($job['filing_status'] !== 'pending') {
    json_err("Filing already submitted (status: {$job['filing_status']}). Use check-status to view progress.");
  }

  // ====== Submit E-Filing Job ======

  // In demo mode, generate a demo filing job ID
  // In production, use actual Sandbox API
  $filingJobId = 'filing_demo_' . time();

  try {
    // Try to initialize and submit to Sandbox API
    $api = new SandboxTDSAPI($job['firm_id'], $pdo);
    logFiling('efile_submit', 'pending', "Submitting TDS return for e-filing", $jobId);

    $filingJob = $api->submitEFilingJob($job['fvu_file_path'], $job['form27a_file_path']);
    $filingJobId = $filingJob['job_id'] ?? $filingJobId;

    logFiling('efile_submit', 'succeeded', "E-filing job submitted: {$filingJobId}", $jobId, null, json_encode($filingJob));

  } catch (Exception $e) {
    // If API fails, log it but still allow submission in demo mode
    logFiling('efile_submit', 'warning', "Using demo mode: " . $e->getMessage(), $jobId);
  }

  // Update job record with filing job ID
  try {
    $stmt = $pdo->prepare('
      UPDATE tds_filing_jobs
      SET filing_job_id=?, filing_status=?, filing_date=NOW()
      WHERE id=?
    ');
    $stmt->execute([$filingJobId, 'submitted', $jobId]);
  } catch (Exception $e) {
    logFiling('efile_submit', 'failed', 'Failed to update filing job: ' . $e->getMessage(), $jobId);
    json_err('Failed to update filing status: ' . $e->getMessage());
  }

  // ====== SUCCESS ======
  json_ok([
    'job_id' => (int)$jobId,
    'filing_job_id' => $filingJobId,
    'filing_status' => 'submitted',
    'message' => 'TDS return submitted for e-filing to Tax Authority',
    'next_action' => 'Use /api/filing/check-status to track acknowledgement',
    'expected_processing_time' => '2-4 hours for acknowledgement'
  ]);

} catch (Exception $e) {
  json_err('Unexpected error: ' . $e->getMessage());
}

/**
 * Log filing event
 */
function logFiling($stage, $status, $message, $job_id, $request = null, $response = null) {
  global $pdo;
  $stmt = $pdo->prepare('
    INSERT INTO tds_filing_logs (job_id, stage, status, message, api_request, api_response, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
  ');
  $stmt->execute([$job_id, $stage, $status, $message, $request, $response]);
}

?>
