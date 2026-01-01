<?php
ob_start();
header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once __DIR__.'/../lib/db.php';

    if (!isset($_SESSION['uid'])) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'msg' => 'Not authenticated']);
        exit;
    }

    $jobId = (int)($_POST['job_id'] ?? 0);

    if ($jobId <= 0) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'msg' => 'Invalid job ID']);
        exit;
    }

    // Check if job exists
    $stmt = $pdo->prepare('SELECT id FROM tds_filing_jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    if (!$stmt->fetch()) {
        ob_end_clean();
        echo json_encode(['ok' => false, 'msg' => 'Filing job not found']);
        exit;
    }

    // Delete related records first (logs and deductees)
    $pdo->prepare('DELETE FROM tds_filing_logs WHERE job_id = ?')->execute([$jobId]);
    $pdo->prepare('DELETE FROM deductees WHERE job_id = ?')->execute([$jobId]);

    // Delete the filing job
    $pdo->prepare('DELETE FROM tds_filing_jobs WHERE id = ?')->execute([$jobId]);

    ob_end_clean();
    echo json_encode(['ok' => true, 'msg' => 'Filing job deleted']);
    exit;

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
    exit;
}
