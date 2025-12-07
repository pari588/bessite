<?php
/**
 * API Endpoint: GET /tds/api/filing/check-status
 * Check status of TDS filing job (FVU generation and e-filing)
 *
 * Parameters:
 * - job_id (int): Filing job ID
 *
 * Returns: Current job status and next steps
 */

require_once __DIR__.'/../../lib/auth.php'; auth_require();
require_once __DIR__.'/../../lib/db.php';
require_once __DIR__.'/../../lib/ajax_helpers.php';
require_once __DIR__.'/../../lib/SandboxTDSAPI.php';

try {
  $jobId = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);

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

  // ====== FVU GENERATION STATUS ======
  if ($job['fvu_status'] === 'pending' || $job['fvu_status'] === 'submitted') {
    // Poll Sandbox for FVU status
    try {
      $api = new SandboxTDSAPI($job['firm_id'], $pdo);
      $fvuStatus = $api->pollFVUJobStatus($job['fvu_job_id']);

      // Update local job record
      if ($fvuStatus['status'] === 'succeeded') {
        // Download FVU and Form 27A files
        $files = $api->downloadFVUFiles($fvuStatus['fvu_url'], $fvuStatus['form27a_url']);

        // Save files
        $fvuPath = saveCoreFile($jobId, 'fvu', $files['fvu_content']);
        $form27aPath = saveCoreFile($jobId, 'form27a', $files['form27a_content']);

        $stmt = $pdo->prepare('
          UPDATE tds_filing_jobs
          SET fvu_status=?, fvu_file_path=?, form27a_file_path=?, fvu_generated_at=NOW()
          WHERE id=?
        ');
        $stmt->execute(['succeeded', $fvuPath, $form27aPath, $jobId]);

        logFiling('fvu_download', 'succeeded', "FVU and Form 27A files downloaded", $jobId);

      } elseif ($fvuStatus['status'] === 'failed') {
        $stmt = $pdo->prepare('
          UPDATE tds_filing_jobs
          SET fvu_status=?, fvu_error_message=?
          WHERE id=?
        ');
        $stmt->execute(['failed', $fvuStatus['error'], $jobId]);

        logFiling('fvu_poll', 'failed', "FVU generation failed: " . $fvuStatus['error'], $jobId);
      }

      // Re-fetch updated job
      $stmt = $pdo->prepare('SELECT * FROM tds_filing_jobs WHERE id=?');
      $stmt->execute([$jobId]);
      $job = $stmt->fetch();

    } catch (Exception $e) {
      logFiling('fvu_poll', 'error', "Error polling FVU status: " . $e->getMessage(), $jobId);
      // Continue with cached status
    }
  }

  // ====== E-FILING STATUS ======
  if ($job['filing_status'] === 'submitted' || $job['filing_status'] === 'processing') {
    // Poll Sandbox for e-filing status
    try {
      $api = new SandboxTDSAPI($job['firm_id'], $pdo);
      $filingStatus = $api->pollEFilingStatus($job['filing_job_id']);

      $newStatus = $filingStatus['status'];
      $ackNo = $filingStatus['ack_no'];

      if ($newStatus === 'acknowledged' || $newStatus === 'accepted') {
        $stmt = $pdo->prepare('
          UPDATE tds_filing_jobs
          SET filing_status=?, filing_ack_no=?, filing_date=NOW()
          WHERE id=?
        ');
        $stmt->execute([$newStatus, $ackNo, $jobId]);

        logFiling('efile_poll', 'succeeded', "E-filing acknowledged: $ackNo", $jobId);

      } elseif ($newStatus === 'rejected') {
        $stmt = $pdo->prepare('
          UPDATE tds_filing_jobs
          SET filing_status=?, filing_error_message=?
          WHERE id=?
        ');
        $stmt->execute(['rejected', $filingStatus['error'], $jobId]);

        logFiling('efile_poll', 'failed', "E-filing rejected: " . $filingStatus['error'], $jobId);
      }

      // Re-fetch updated job
      $stmt = $pdo->prepare('SELECT * FROM tds_filing_jobs WHERE id=?');
      $stmt->execute([$jobId]);
      $job = $stmt->fetch();

    } catch (Exception $e) {
      logFiling('efile_poll', 'error', "Error polling e-filing status: " . $e->getMessage(), $jobId);
    }
  }

  // ====== DETERMINE NEXT ACTION ======
  $nextAction = null;
  $canSubmitEFiling = false;

  if ($job['fvu_status'] === 'succeeded' && !$job['filing_job_id']) {
    $nextAction = 'Submit for e-filing using /api/filing/submit';
    $canSubmitEFiling = true;
  }

  // Get recent logs
  $stmt = $pdo->prepare('
    SELECT stage, status, message, created_at
    FROM tds_filing_logs
    WHERE job_id=?
    ORDER BY created_at DESC
    LIMIT 10
  ');
  $stmt->execute([$jobId]);
  $logs = $stmt->fetchAll();

  // Build response
  json_ok([
    'job_id' => (int)$jobId,
    'fy' => $job['fy'],
    'quarter' => $job['quarter'],
    'status_overview' => [
      'txt_generation' => $job['txt_file_path'] ? 'completed' : 'pending',
      'csi_download' => $job['csi_file_path'] ? 'completed' : 'pending',
      'fvu_generation' => $job['fvu_status'],
      'e_filing' => $job['filing_status']
    ],
    'fvu_details' => [
      'job_id' => $job['fvu_job_id'],
      'status' => $job['fvu_status'],
      'error' => $job['fvu_error_message'],
      'generated_at' => $job['fvu_generated_at']
    ],
    'filing_details' => [
      'job_id' => $job['filing_job_id'],
      'status' => $job['filing_status'],
      'ack_no' => $job['filing_ack_no'],
      'error' => $job['filing_error_message'],
      'filed_at' => $job['filing_date']
    ],
    'control_totals' => [
      'records' => (int)$job['control_total_records'],
      'amount' => (float)$job['control_total_amount'],
      'tds' => (float)$job['control_total_tds']
    ],
    'next_action' => $nextAction,
    'can_submit_efiling' => $canSubmitEFiling,
    'recent_logs' => array_map(fn($log) => [
      'stage' => $log['stage'],
      'status' => $log['status'],
      'message' => $log['message'],
      'timestamp' => $log['created_at']
    ], $logs),
    'created_at' => $job['created_at'],
    'updated_at' => $job['updated_at']
  ]);

} catch (Exception $e) {
  json_err('Error: ' . $e->getMessage());
}

/**
 * Log filing event
 */
function logFiling($stage, $status, $message, $job_id) {
  global $pdo;
  $stmt = $pdo->prepare('
    INSERT INTO tds_filing_logs (job_id, stage, status, message, created_at)
    VALUES (?, ?, ?, ?, NOW())
  ');
  $stmt->execute([$job_id, $stage, $status, $message]);
}

/**
 * Save file to filing directory
 */
function saveCoreFile($job_id, $type, $content) {
  $dir = __DIR__ . '/../../uploads/filings/' . $job_id;
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  $ext = $type === 'fvu' ? 'zip' : 'pdf';
  $filename = 'form26q_' . $type . '.' . $ext;
  $path = $dir . '/' . $filename;
  file_put_contents($path, $content);
  return $path;
}

?>
