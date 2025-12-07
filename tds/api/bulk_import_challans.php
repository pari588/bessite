<?php
/**
 * Bulk Import Challans from CSV
 *
 * Expected CSV columns:
 * - bsr_code (required)
 * - challan_date (required, YYYY-MM-DD)
 * - challan_serial_no (required)
 * - amount_tds (required)
 * - surcharge (optional)
 * - health_and_education_cess (optional)
 * - interest (optional)
 *
 * Returns JSON with import results
 */

require_once __DIR__.'/../lib/auth.php'; auth_require();
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/helpers.php';
require_once __DIR__.'/../lib/ajax_helpers.php';

// Validate file upload
if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    json_err('No file uploaded or upload error');
}

$file = $_FILES['csv_file'];
$tmpFile = $file['tmp_name'];

// Only accept CSV
if (!in_array($file['type'], ['text/csv', 'application/vnd.ms-excel'])) {
    json_err('Only CSV files are accepted');
}

// Parse CSV
$rows = [];
if (($handle = fopen($tmpFile, 'r')) !== FALSE) {
    $header = fgetcsv($handle);

    // Normalize header (lowercase, trim)
    $header = array_map(function($h) { return strtolower(trim($h)); }, $header);

    while (($row = fgetcsv($handle)) !== FALSE) {
        $rows[] = array_combine($header, $row);
    }
    fclose($handle);
}

if (empty($rows)) {
    json_err('CSV file is empty');
}

// Get firm ID from session
$firm_id = $_SESSION['active_firm_id'] ?? 1;
if (!$firm_id) {
    json_err('No firm selected. Please select a firm first.');
}

// Verify firm exists
$stmt = $pdo->prepare('SELECT id FROM firms WHERE id = ?');
$stmt->execute([$firm_id]);
if (!$stmt->fetch()) {
    json_err('Selected firm does not exist');
}

// Process each row
$successful = 0;
$failed = 0;
$errors = [];

foreach ($rows as $i => $row) {
    try {
        $bsr = trim($row['bsr_code'] ?? '');
        $challan_date = trim($row['challan_date'] ?? '');
        $serial = trim($row['challan_serial_no'] ?? '');
        $amount_tds = (float)($row['amount_tds'] ?? 0);
        $surcharge = isset($row['surcharge']) && trim($row['surcharge']) !== '' ? (float)$row['surcharge'] : 0;
        $cess = isset($row['health_and_education_cess']) && trim($row['health_and_education_cess']) !== '' ? (float)$row['health_and_education_cess'] : 0;
        $interest = isset($row['interest']) && trim($row['interest']) !== '' ? (float)$row['interest'] : 0;

        // Validate required fields
        if (!$bsr || !$challan_date || !$serial || $amount_tds <= 0) {
            throw new Exception('Missing or invalid required fields');
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $challan_date)) {
            throw new Exception('Invalid date format (use YYYY-MM-DD)');
        }

        // Validate date is reasonable
        $dateObj = DateTime::createFromFormat('Y-m-d', $challan_date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $challan_date) {
            throw new Exception('Invalid challan date');
        }

        // Resolve FY/Quarter
        list($fy, $q) = fy_quarter_from_date($challan_date);

        // Insert challan with Sandbox API compatible fields
        $ins = $pdo->prepare('
            INSERT INTO challans (
                firm_id, bsr_code, challan_date, challan_serial_no, amount_tds,
                surcharge, health_and_education_cess, interest, fy, quarter
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $ins->execute([
            $firm_id, $bsr, $challan_date, $serial, $amount_tds,
            $surcharge, $cess, $interest, $fy, $q
        ]);

        $successful++;

    } catch (Exception $e) {
        $failed++;
        $errors[] = [
            'row' => $i + 2, // +2 for header and 0-indexing
            'error' => $e->getMessage()
        ];
    }
}

// Log import
try {
    $errorJson = !empty($errors) ? json_encode($errors) : null;
    $logStmt = $pdo->prepare('
        INSERT INTO csv_imports (firm_id, import_type, file_name, total_rows, successful_rows, failed_rows, error_details, imported_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $logStmt->execute([
        $firm_id,
        'challans',
        $file['name'],
        count($rows),
        $successful,
        $failed,
        $errorJson,
        $_SESSION['uid'] ?? null
    ]);
} catch (Exception $e) {
    // Log import failed, but don't fail the response
}

// Return results
json_ok([
    'total' => count($rows),
    'successful' => $successful,
    'failed' => $failed,
    'errors' => $errors
]);
