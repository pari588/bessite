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

require_once __DIR__.'/../../lib/auth.php'; auth_require();
require_once __DIR__.'/../../lib/db.php';
require_once __DIR__.'/../../lib/ajax_helpers.php';
require_once __DIR__.'/../../lib/SandboxTDSAPI.php';

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

  if (!$job['fvu_file_path'] || !file_exists($job['fvu_file_path'])) {
    json_err('FVU file not found');
  }

  if (!$job['form27a_file_path'] || !file_exists($job['form27a_file_path'])) {
    json_err('Form 27A file not found');
  }

  // Check if already submitted
  if ($job['filing_status'] !== 'pending') {
    json_err("Filing already submitted (status: {$job['filing_status']}). Use check-status to view progress.");
  }

  // ====== Initialize Sandbox API ======
  try {
    $api = new SandboxTDSAPI($job['firm_id'], $pdo);
  } catch (Exception $e) {
    logFiling('efile_init', 'failed', $e->getMessage(), $jobId);
    json_err('Sandbox API configuration error: ' . $e->getMessage());
  }

  // ====== Submit E-Filing Job ======
  try {
    logFiling('efile_submit', 'pending', "Submitting TDS return for e-filing", $jobId);

    $filingJob = $api->submitEFilingJob($job['fvu_file_path'], $job['form27a_file_path']);

    // Update job record
    $stmt = $pdo->prepare('
      UPDATE tds_filing_jobs
      SET filing_job_id=?, filing_status=?
      WHERE id=?
    ');
    $stmt->execute([$filingJob['job_id'], 'submitted', $jobId]);

    logFiling('efile_submit', 'succeeded', "E-filing job submitted: {$filingJob['job_id']}", $jobId, null, json_encode($filingJob));

  } catch (Exception $e) {
    logFiling('efile_submit', 'failed', $e->getMessage(), $jobId);
    json_err('E-filing submission failed: ' . $e->getMessage());
  }

  // ====== SUCCESS ======
  json_ok([
    'job_id' => (int)$jobId,
    'filing_job_id' => $filingJob['job_id'],
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
