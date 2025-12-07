<?php
/**
 * TDS26QGenerator - Form 26Q Generation per Income Tax Act 1961
 * Generates NS1 (^ delimited) format TDS return file
 *
 * @link https://developer.sandbox.co.in/api-reference/tds/prepare-tds-return
 */

class TDS26QGenerator {
  private $pdo;
  private $firm;
  private $fy;
  private $quarter;
  private $jobId;

  /**
   * Initialize TDS 26Q generator
   *
   * @param PDO $pdo Database connection
   * @param int $firm_id Firm ID
   * @param string $fy Fiscal year (e.g., '2025-26')
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param int $job_id Filing job ID for tracking
   */
  public function __construct($pdo, $firm_id, $fy, $quarter, $job_id) {
    $this->pdo = $pdo;
    $this->fy = $fy;
    $this->quarter = $quarter;
    $this->jobId = $job_id;

    // Load firm details
    $stmt = $pdo->prepare('SELECT * FROM firms WHERE id=?');
    $stmt->execute([$firm_id]);
    $this->firm = $stmt->fetch();

    if (!$this->firm) {
      throw new Exception("Firm ID $firm_id not found");
    }

    // Validate firm setup
    $this->validateFirmSetup();
  }

  /**
   * Validate firm has required fields for 26Q filing
   *
   * @throws Exception If required fields are missing
   */
  private function validateFirmSetup() {
    $required = [
      'tan' => 'TAN (Tax Account Number)',
      'pan' => 'PAN',
      'display_name' => 'Display Name',
      'address1' => 'Address Line 1',
      'state_code' => 'State Code',
      'pincode' => 'PIN Code',
      'email' => 'Email',
      'rp_name' => 'Responsible Person Name',
      'rp_mobile' => 'Responsible Person Mobile'
    ];

    $missing = [];
    foreach ($required as $field => $label) {
      if (empty($this->firm[$field])) {
        $missing[] = $label;
      }
    }

    if (!empty($missing)) {
      throw new Exception('Missing firm setup: ' . implode(', ', $missing));
    }
  }

  /**
   * Generate Form 26Q TXT file in NS1 format
   * Returns path to generated file
   *
   * @return string Path to generated TXT file
   * @throws Exception If generation fails
   */
  public function generateTXT() {
    try {
      // Fetch all validated invoices for this FY/Quarter
      $invoices = $this->getValidatedInvoices();
      if (empty($invoices)) {
        throw new Exception("No validated invoices found for $this->fy $this->quarter");
      }

      // Aggregate by deductee and section
      $deductees = $this->aggregateDeductees($invoices);
      if (empty($deductees)) {
        throw new Exception('No deductees to file');
      }

      // Build Form 26Q content
      $content = $this->buildFormContent($deductees, $invoices);

      // Save to file
      $filePath = $this->saveFile('txt', $content);

      // Update job record
      $stmt = $this->pdo->prepare('
        UPDATE tds_filing_jobs
        SET txt_file_path=?, txt_generated_at=NOW(), control_total_records=?, control_total_amount=?, control_total_tds=?
        WHERE id=?
      ');

      $totalRecords = count($deductees);
      $totalAmount = array_sum(array_map(fn($d) => $d['gross'], $deductees));
      $totalTDS = array_sum(array_map(fn($d) => $d['tds'], $deductees));

      $stmt->execute([$filePath, $totalRecords, $totalAmount, $totalTDS, $this->jobId]);

      return $filePath;
    } catch (Exception $e) {
      throw new Exception("TXT generation failed: " . $e->getMessage());
    }
  }

  /**
   * Get all invoices that are fully reconciled for this FY/Quarter
   *
   * @return array Invoice records with vendor details
   */
  private function getValidatedInvoices() {
    $stmt = $this->pdo->prepare('
      SELECT
        i.id, i.invoice_no, i.invoice_date,
        i.base_amount, i.total_tds, i.section_code, i.tds_rate,
        v.id as vendor_id, v.pan, v.name, v.category
      FROM invoices i
      JOIN vendors v ON v.id = i.vendor_id
      WHERE i.firm_id = ?
        AND i.fy = ?
        AND i.quarter = ?
        AND i.allocation_status = "complete"
      ORDER BY i.invoice_date, i.id
    ');

    $stmt->execute([$this->firm['id'], $this->fy, $this->quarter]);
    return $stmt->fetchAll();
  }

  /**
   * Aggregate invoices by deductee (PAN) and section code
   * Creates deductee summary with totals
   *
   * @param array $invoices Invoice records
   * @return array Aggregated deductee data
   */
  private function aggregateDeductees($invoices) {
    $deductees = [];

    foreach ($invoices as $inv) {
      // Key: PAN | Section Code
      $key = $inv['pan'] . '|' . $inv['section_code'];

      if (!isset($deductees[$key])) {
        $deductees[$key] = [
          'pan' => $inv['pan'],
          'name' => $inv['name'],
          'section' => $inv['section_code'],
          'category' => $inv['category'],
          'gross' => 0,
          'tds' => 0,
          'count' => 0,
          'invoices' => []
        ];
      }

      $deductees[$key]['gross'] += (float)$inv['base_amount'];
      $deductees[$key]['tds'] += (float)$inv['total_tds'];
      $deductees[$key]['count']++;
      $deductees[$key]['invoices'][] = $inv;
    }

    return $deductees;
  }

  /**
   * Build Form 26Q content in NS1 (^ delimited) format
   * Per IT Act Section 206AA and official form specifications
   *
   * @param array $deductees Aggregated deductee data
   * @param array $invoices Original invoice records
   * @return string Form content
   */
  private function buildFormContent($deductees, $invoices) {
    $lines = [];
    $lineNo = 1;

    // ==================== FH - File Header (18 fields) ====================
    $fh = [
      $lineNo++,                              // Line Number
      'FH',                                   // Record Type
      'NS1',                                  // Format
      'R',                                    // Sender Type
      date('dmY'),                            // Date
      '1',                                    // Version
      'D',                                    // Transmission Mode
      strtoupper($this->firm['tan']),        // TAN
      '1',                                    // Return Period (1=Quarterly)
      'TDS-AutoFile',                         // Software Name
      '',                                     // Batch ID
      '',                                     // Batch Number
      '',                                     // TDS Period From
      '',                                     // TDS Period To
      '',                                     // Transmitted on
      '',                                     // Reserved
      '',                                     // Reserved
      ''                                      // Reserved
    ];
    $lines[] = $this->formatLine($fh, 18);

    // ==================== BH - Batch Header (72 fields) ====================
    $totalDeductees = count($deductees);
    $totalGross = array_sum(array_map(fn($d) => $d['gross'], $deductees));
    $totalTDS = array_sum(array_map(fn($d) => $d['tds'], $deductees));

    $bh = [
      $lineNo++,                              // Line Number
      'BH',                                   // Record Type
      '1',                                    // Batch Number
      strtoupper($this->firm['tan']),        // TAN
      strtoupper($this->firm['pan']),        // PAN
      $this->firm['display_name'],            // Name
      '',                                     // Address 1
      '',                                     // Address 2
      '',                                     // Address 3
      '',                                     // City
      '',                                     // PIN
      '',                                     // State Code
      '',                                     // Phone
      '',                                     // Email
      '',                                     // Reserved
      '',                                     // Reserved
      $totalDeductees,                        // Number of Records
      $this->formatAmount($totalGross),      // Total Amount
      $this->formatAmount($totalTDS),        // Total TDS
      '',                                     // Reserved
      ...array_fill(0, 52, '')               // Remaining fields
    ];
    $lines[] = $this->formatLine($bh, 72);

    // ==================== DR - Deductee Records ====================
    $recordCount = 0;
    foreach ($deductees as $deductee) {
      $recordCount++;

      // Deductee Record
      $dr = [
        $lineNo++,                            // Line Number
        'DR',                                 // Record Type
        strtoupper($deductee['pan']),        // PAN
        $deductee['name'],                    // Name
        $deductee['section'],                 // TDS Section
        $this->formatAmount($deductee['gross']), // Gross Amount
        $this->formatAmount($deductee['tds']),  // TDS Amount
        $deductee['count'],                  // Number of Payments
        '',                                   // Reserved
        ...array_fill(0, 62, '')             // Remaining fields
      ];
      $lines[] = $this->formatLine($dr, 72);

      // Add payment details for each invoice under this deductee
      foreach ($deductee['invoices'] as $invoice) {
        // Payment Record (PR)
        $pr = [
          $lineNo++,                          // Line Number
          'PR',                               // Record Type
          $invoice['invoice_no'],             // Payment Reference
          date('dmY', strtotime($invoice['invoice_date'])), // Payment Date
          $this->formatAmount($invoice['base_amount']), // Amount
          $invoice['section_code'],           // Section Code
          $this->formatAmount($invoice['total_tds']),  // TDS Deducted
          '',                                 // Reserved
          ...array_fill(0, 64, '')           // Remaining fields
        ];
        $lines[] = $this->formatLine($pr, 72);
      }
    }

    // ==================== TL - Total Line ====================
    $tl = [
      $lineNo++,                              // Line Number
      'TL',                                   // Record Type
      $recordCount,                           // Total Records
      $this->formatAmount($totalGross),      // Total Amount
      $this->formatAmount($totalTDS),        // Total TDS
      '',                                     // Reserved
      ...array_fill(0, 66, '')               // Remaining fields
    ];
    $lines[] = $this->formatLine($tl, 72);

    return implode('', $lines);
  }

  /**
   * Format line with ^ delimiter and proper field count
   *
   * @param array $fields Fields to format
   * @param int $expectedCount Expected field count
   * @return string Formatted line with CRLF
   */
  private function formatLine($fields, $expectedCount) {
    // Pad or trim to expected count
    $fields = array_slice(array_pad($fields, $expectedCount, ''), 0, $expectedCount);
    return implode('^', $fields) . "\r\n";
  }

  /**
   * Format amount for TDS form (2 decimal places, no separators)
   *
   * @param float $amount Amount value
   * @return string Formatted amount
   */
  private function formatAmount($amount) {
    return number_format((float)$amount, 2, '.', '');
  }

  /**
   * Save file to uploads directory
   *
   * @param string $type File type (txt, csi, fvu)
   * @param string $content File content
   * @return string Full file path
   */
  private function saveFile($type, $content) {
    $dir = __DIR__ . '/../uploads/filings/' . $this->jobId;
    if (!is_dir($dir)) {
      if (!mkdir($dir, 0755, true)) {
        throw new Exception("Cannot create directory: $dir");
      }
    }

    $filename = $type === 'txt' ? 'form26q.txt' : 'form26q_' . $type;
    $path = $dir . '/' . $filename;

    if (file_put_contents($path, $content) === false) {
      throw new Exception("Cannot write file: $path");
    }

    chmod($path, 0644);
    return $path;
  }

  /**
   * Get generated TXT file path
   *
   * @return string|null Path if exists, null otherwise
   */
  public function getTXTPath() {
    $stmt = $this->pdo->prepare('SELECT txt_file_path FROM tds_filing_jobs WHERE id=?');
    $stmt->execute([$this->jobId]);
    return $stmt->fetchColumn();
  }

  /**
   * Get control totals for validation
   *
   * @return array ['records' => int, 'amount' => float, 'tds' => float]
   */
  public function getControlTotals() {
    $stmt = $this->pdo->prepare('
      SELECT control_total_records as records, control_total_amount as amount, control_total_tds as tds
      FROM tds_filing_jobs WHERE id=?
    ');
    $stmt->execute([$this->jobId]);
    return $stmt->fetch();
  }
}
?>
