<?php
/**
 * Download FVU File
 * Serves generated FVU ZIP files for download
 */

require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';

header('Content-Type: application/json');

try {
    // Get job_uuid from query parameter
    $job_uuid = $_GET['job_id'] ?? $_GET['job_uuid'] ?? null;

    if (empty($job_uuid)) {
        throw new Exception("Job UUID is required");
    }

    // Get filing job from database
    $stmt = $pdo->prepare('SELECT * FROM tds_filing_jobs WHERE fvu_job_id = ?');
    $stmt->execute([$job_uuid]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        throw new Exception("Filing job not found");
    }

    // Check if FVU file exists
    $fvu_path = $job['fvu_file_path'] ?? '';

    if (empty($fvu_path) || !file_exists($fvu_path)) {
        // Try to construct the path from uploads directory
        $upload_dir = __DIR__ . '/../uploads/fvu';
        $fvu_path = $upload_dir . '/FVU_' . $job_uuid . '.zip';

        if (!file_exists($fvu_path)) {
            throw new Exception("FVU file not found. Please generate FVU first.");
        }
    }

    // Check if this is a download request
    if ($_GET['action'] === 'download' || $_GET['download'] === '1') {
        // Serve the file for download
        if (!file_exists($fvu_path)) {
            http_response_code(404);
            die(json_encode([
                'ok' => false,
                'msg' => 'FVU file not found'
            ]));
        }

        $filename = 'FVU_' . $job['fy'] . '_' . $job['quarter'] . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($fvu_path));
        header('Cache-Control: public, must-revalidate');
        header('Pragma: public');

        readfile($fvu_path);
        exit;
    }

    // Otherwise return JSON info about the file
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'status' => 'success',
        'job_uuid' => $job_uuid,
        'fvu_status' => $job['fvu_status'],
        'fvu_file' => $fvu_path,
        'file_size' => file_exists($fvu_path) ? filesize($fvu_path) : 0,
        'download_url' => '/tds/api/download_fvu.php?job_id=' . urlencode($job_uuid) . '&download=1',
        'fy' => $job['fy'],
        'quarter' => $job['quarter'],
        'generated_at' => $job['fvu_generated_at'],
        'message' => 'FVU file ready for download'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
    exit;
}
