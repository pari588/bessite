<?php
/**
 * TDS Form 24Q Generator
 * Generates official Form 24Q (Annual TDS Return) in NS1 format
 * per Income Tax Act 1961
 *
 * Form 24Q is an annual consolidation of all quarterly TDS filings (26Q, 27Q, etc.)
 * Generated automatically after all quarters are filed
 */

class TDS24QGenerator {
  private $pdo;
  private $firmId;
  private $fy;
  private $firm = null;
  private $error = null;
  private $control_total_records = 0;
  private $control_total_gross = 0;
  private $control_total_tds = 0;

  public function __construct($pdo, $firmId, $fy) {
    $this->pdo = $pdo;
    $this->firmId = $firmId;
    $this->fy = $fy; // e.g., "2025-26"
    $this->loadFirm();
  }

  private function loadFirm() {
    $stmt = $this->pdo->prepare('SELECT * FROM firms WHERE id = ?');
    $stmt->execute([$this->firmId]);
    $this->firm = $stmt->fetch();
    if (!$this->firm) {
      $this->error = 'Firm not found';
    }
  }

  public function getError() {
    return $this->error;
  }

  /**
   * Validate firm has all mandatory fields for Form 24Q
   */
  private function validateFirm() {
    if (!$this->firm) return false;
    $mandatory = ['pan', 'tan', 'display_name', 'address1', 'pincode'];
    foreach ($mandatory as $field) {
      if (empty($this->firm[$field])) {
        $this->error = "Firm missing mandatory field: $field";
        return false;
      }
    }
    return true;
  }

  /**
   * Get aggregated deductees for the full FY
   */
  private function getDeductees() {
    $stmt = $this->pdo->prepare(
      "SELECT
        d.pan,
        d.name,
        d.section_code,
        SUM(d.total_gross) as total_gross,
        SUM(d.total_tds) as total_tds,
        SUM(d.payment_count) as payment_count
      FROM deductees d
      JOIN tds_filing_jobs tfj ON d.job_id = tfj.id
      WHERE tfj.firm_id = ? AND tfj.fy = ? AND tfj.filing_status IN ('accepted', 'acknowledged', 'succeeded')
      GROUP BY d.pan, d.section_code
      ORDER BY d.pan, d.section_code"
    );
    $stmt->execute([$this->firmId, $this->fy]);
    return $stmt->fetchAll();
  }

  /**
   * Generate Form 24Q content in NS1 format
   * File Header (FH) → Batch Header (BH) → Data Records (DR) → Deductee Records (DR) → Total Record (TR) → File Trailer (FL)
   */
  public function generateTXT() {
    if (!$this->validateFirm()) {
      return null;
    }

    // Get deductees for full FY
    $deductees = $this->getDeductees();
    if (empty($deductees)) {
      $this->error = 'No completed filings found for this FY';
      return null;
    }

    $lines = [];

    // FILE HEADER (FH)
    $lines[] = $this->buildFH();

    // BATCH HEADER (BH)
    $lines[] = $this->buildBH();

    // DATA RECORDS (DR) - One for each deductee aggregate
    $totalGross = 0;
    $totalTds = 0;
    $totalCount = 0;
    $recordCount = 0;

    foreach ($deductees as $deductee) {
      $lines[] = $this->buildDR($deductee);
      $totalGross += (float)$deductee['total_gross'];
      $totalTds += (float)$deductee['total_tds'];
      $totalCount += (int)$deductee['payment_count'];
      $recordCount++;
    }

    $this->control_total_records = $recordCount;
    $this->control_total_gross = $totalGross;
    $this->control_total_tds = $totalTds;

    // TOTAL RECORD (TR) - Summary
    $lines[] = $this->buildTR($recordCount, $totalGross, $totalTds);

    // FILE TRAILER (FL)
    $lines[] = $this->buildFL(count($lines) + 1);

    return implode("\n", $lines);
  }

  /**
   * Build File Header (FH) record
   * Format: FH^FT^PAN^TAN^AY^[other fields]
   */
  private function buildFH() {
    $fy = $this->fy; // "2025-26"
    $ay = substr($fy, 5, 2); // "26" (Assessment Year)

    return implode('^', [
      'FH',                           // Record Type
      '24Q',                          // Form Type
      $this->firm['pan'],             // PAN
      $this->firm['tan'],             // TAN
      $ay,                            // Assessment Year
      '',                             // ITCD (if applicable)
      'N',                            // Correction Indicator
      'N',                            // Revised Return
      '',                             // Reserved Field
    ]);
  }

  /**
   * Build Batch Header (BH) record
   * Contains batch sequence info
   */
  private function buildBH() {
    $fy = $this->fy;
    $ay = substr($fy, 5, 2);

    return implode('^', [
      'BH',                           // Record Type
      '1',                            // Batch Sequence Number
      $this->firm['pan'],             // PAN
      $this->firm['tan'],             // TAN
      $ay,                            // Assessment Year
      '24Q',                          // Form Type
      '',                             // Reserved Field
    ]);
  }

  /**
   * Build Data Record (DR) - One deductee per record
   * Format: DR^PAN^Name^Section^Gross Amount^TDS^Count^...
   */
  private function buildDR($deductee) {
    $sectionMap = [
      '194A' => '192',  // Dividends
      '194C' => '194',  // Contractor
      '194H' => '195',  // Commission
      '194I' => '196',  // Rent
      '194J' => '197',  // Professional fees
      '194Q' => '198',  // Specified financial transaction
    ];

    $section = $deductee['section_code'];
    $sectionCode = $sectionMap[$section] ?? $section;

    return implode('^', [
      'DR',                                   // Record Type
      $deductee['pan'],                      // Deductee PAN
      substr($deductee['name'], 0, 60),      // Deductee Name (max 60)
      '00',                                   // Relationship Code (00 = other)
      $sectionCode,                          // Section Code
      number_format((float)$deductee['total_gross'], 2, '.', ''), // Gross Amount
      number_format((float)$deductee['total_tds'], 2, '.', ''),   // TDS Amount
      (int)$deductee['payment_count'],       // Count of payments
      '',                                     // Surcharge
      '',                                     // Cess
      '',                                     // Reserved
    ]);
  }

  /**
   * Build Total Record (TR) - Overall summary
   */
  private function buildTR($recordCount, $totalGross, $totalTds) {
    return implode('^', [
      'TR',                                                    // Record Type
      (int)$recordCount,                                      // Total Records
      number_format($totalGross, 2, '.', ''),               // Total Gross
      number_format($totalTds, 2, '.', ''),                 // Total TDS
      '',                                                     // Total Surcharge
      '',                                                     // Total Cess
      '',                                                     // Reserved
    ]);
  }

  /**
   * Build File Trailer (FL) record
   */
  private function buildFL($totalLines) {
    return implode('^', [
      'FL',                           // Record Type
      $totalLines,                    // Total Lines in File
      date('Y-m-d'),                  // File Generation Date
      '',                             // Reserved
    ]);
  }

  /**
   * Save Form 24Q to file and return path
   */
  public function saveTXT($outputDir = null) {
    if (!$outputDir) {
      $outputDir = __DIR__ . '/../uploads/forms/24q';
    }

    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0755, true);
    }

    $txt = $this->generateTXT();
    if (!$txt) return null;

    $filename = sprintf('Form24Q_%s_%s_%s.txt',
      $this->firm['tan'],
      $this->fy,
      date('YmdHis')
    );
    $filepath = $outputDir . '/' . $filename;

    if (file_put_contents($filepath, $txt) === false) {
      $this->error = 'Failed to save Form 24Q file';
      return null;
    }

    return $filepath;
  }

  /**
   * Get control totals from last generation
   */
  public function getControlTotals() {
    return [
      'records' => $this->control_total_records,
      'gross' => $this->control_total_gross,
      'tds' => $this->control_total_tds,
    ];
  }
}
?>
