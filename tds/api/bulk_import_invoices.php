<?php
/**
 * Bulk Import Invoices from CSV
 *
 * Expected CSV columns:
 * - vendor_name (required)
 * - vendor_pan (required)
 * - invoice_no (required)
 * - invoice_date (required, YYYY-MM-DD)
 * - base_amount (required)
 * - section_code (required)
 * - tds_rate (optional, will be auto-calculated)
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
        $vendor_name = trim($row['vendor_name'] ?? '');
        $vendor_pan = strtoupper(trim($row['vendor_pan'] ?? ''));
        $invoice_no = trim($row['invoice_no'] ?? '');
        $invoice_date = trim($row['invoice_date'] ?? '');
        $base_amount = (float)($row['base_amount'] ?? 0);
        $section = strtoupper(trim($row['section_code'] ?? ''));
        $tds_rate = isset($row['tds_rate']) && trim($row['tds_rate']) !== '' ? (float)$row['tds_rate'] : null;

        // Validate required fields
        if (!$vendor_name || !$vendor_pan || !$invoice_no || !$invoice_date || $base_amount <= 0 || !$section) {
            throw new Exception('Missing required fields');
        }

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $invoice_date)) {
            throw new Exception('Invalid date format (use YYYY-MM-DD)');
        }

        // Find or create vendor
        $vs = $pdo->prepare('SELECT id FROM vendors WHERE firm_id=? AND (pan=? OR name=?) ORDER BY id DESC LIMIT 1');
        $vs->execute([$firm_id, $vendor_pan, $vendor_name]);
        $vendor_id = $vs->fetchColumn();

        if (!$vendor_id) {
            $insV = $pdo->prepare('INSERT INTO vendors (firm_id,name,pan) VALUES (?,?,?)');
            $insV->execute([$firm_id, $vendor_name, $vendor_pan]);
            $vendor_id = $pdo->lastInsertId();
        }

        // Resolve FY/Quarter
        list($fy, $q) = fy_quarter_from_date($invoice_date);

        // Resolve TDS rate if not provided
        if ($tds_rate === null) {
            $stm = $pdo->prepare('SELECT rate FROM tds_rates WHERE section_code=? AND effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?) ORDER BY effective_from DESC LIMIT 1');
            $stm->execute([$section, $invoice_date, $invoice_date]);
            $row_rate = $stm->fetch();
            $tds_rate = $row_rate ? (float)$row_rate['rate'] : 0.0;
        }

        // Calculate TDS amount
        $tds_amt = round($base_amount * $tds_rate / 100, 2);
        $total_tds = $tds_amt;

        // Insert invoice
        $insI = $pdo->prepare('
            INSERT INTO invoices (
                firm_id, vendor_id, invoice_no, invoice_date, base_amount,
                section_code, tds_rate, tds_amount, surcharge_amount, cess_amount,
                total_tds, fy, quarter
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $insI->execute([
            $firm_id, $vendor_id, $invoice_no, $invoice_date, $base_amount,
            $section, $tds_rate, $tds_amt, 0, 0, $total_tds, $fy, $q
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
        'invoices',
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
