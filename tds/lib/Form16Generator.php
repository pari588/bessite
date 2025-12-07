<?php
/**
 * Form 16 Certificate Generator
 * Generates TDS certificates per Income Tax Act 1961
 *
 * Form 16 is issued to employees/contractors for TDS deducted
 * Required for claiming TDS credit in personal tax returns
 */

class Form16Generator {
  private $pdo;
  private $firmId;
  private $fy;
  private $firm = null;
  private $error = null;

  public function __construct($pdo, $firmId, $fy) {
    $this->pdo = $pdo;
    $this->firmId = $firmId;
    $this->fy = $fy;
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
   * Generate Form 16 Part A and B in NS1 format
   * Part A: Basic details
   * Part B: Deduction details by section
   */
  public function generateForm16($deducteePan, $deducteeName) {
    if (!$this->firm) {
      return null;
    }

    // Get deductee transactions for the FY
    $stmt = $this->pdo->prepare(
      "SELECT
        i.invoice_no,
        i.invoice_date,
        i.base_amount,
        i.total_tds,
        i.section_code
      FROM invoices i
      JOIN vendors v ON i.vendor_id = v.id
      WHERE v.pan = ? AND i.firm_id = ? AND i.fy = ? AND i.allocation_status = 'complete'
      ORDER BY i.invoice_date"
    );
    $stmt->execute([$deducteePan, $this->firmId, $this->fy]);
    $transactions = $stmt->fetchAll();

    if (empty($transactions)) {
      $this->error = "No transactions found for $deducteePan in FY $this->fy";
      return null;
    }

    $lines = [];

    // PART A: Deductor and Deductee Details
    $lines[] = $this->buildPartAHeader();
    $lines[] = $this->buildPartADeductorDetails();
    $lines[] = $this->buildPartADeducteeDetails($deducteePan, $deducteeName);

    // PART B: Deduction Details
    $lines[] = $this->buildPartBHeader();

    $totalTds = 0;
    $totalGross = 0;

    foreach ($transactions as $txn) {
      $lines[] = $this->buildPartBRecord($txn);
      $totalTds += (float)$txn['total_tds'];
      $totalGross += (float)$txn['base_amount'];
    }

    // Summary
    $lines[] = $this->buildPartBSummary(count($transactions), $totalGross, $totalTds);

    // Footer
    $lines[] = 'FL^' . (count($lines) + 1) . '^' . date('Y-m-d');

    return implode("\n", $lines);
  }

  /**
   * Build Part A Header
   */
  private function buildPartAHeader() {
    $fy = $this->fy;
    $ay = substr($fy, 5, 2); // Assessment year

    return implode('^', [
      'PA',                           // Part A
      '16',                           // Form Type
      $this->firm['pan'],             // Deductor PAN
      $this->firm['tan'],             // Deductor TAN
      $ay,                            // Assessment Year
      date('Y-m-d'),                  // Certificate Issue Date
      '',                             // Receipt Number
      '',                             // Reserved
    ]);
  }

  /**
   * Build Deductor Details in Part A
   */
  private function buildPartADeductorDetails() {
    $address = implode(', ', array_filter([
      $this->firm['address1'],
      $this->firm['address2'],
      $this->firm['address3'],
      $this->firm['pincode']
    ]));

    return implode('^', [
      'DD',                                       // Deductor Details
      $this->firm['display_name'],               // Name
      $address,                                   // Address
      $this->firm['state_code'] ?? '',           // State Code
      $this->firm['pincode'] ?? '',              // PIN Code
      $this->firm['phone'] ?? '',                // Phone
      $this->firm['email'] ?? '',                // Email
      $this->firm['rp_name'] ?? '',              // RP Name
      $this->firm['rp_designation'] ?? '',       // RP Designation
    ]);
  }

  /**
   * Build Deductee Details in Part A
   */
  private function buildPartADeducteeDetails($pan, $name) {
    return implode('^', [
      'CD',                           // Deductee Details
      $pan,                           // Deductee PAN
      $name,                          // Deductee Name
      '',                             // Address
      '',                             // State Code
      '',                             // PIN Code
    ]);
  }

  /**
   * Build Part B Header
   */
  private function buildPartBHeader() {
    return 'PB^' . $this->fy . '^Deduction Details';
  }

  /**
   * Build individual transaction record in Part B
   */
  private function buildPartBRecord($txn) {
    return implode('^', [
      'TR',                                                          // Transaction Record
      $txn['invoice_no'],                                           // Invoice/Reference No
      date('d-m-Y', strtotime($txn['invoice_date'])),              // Payment Date
      $txn['section_code'],                                        // TDS Section
      number_format((float)$txn['base_amount'], 2, '.', ''),       // Payment Amount
      number_format((float)$txn['total_tds'], 2, '.', ''),         // TDS Deducted
      '',                                                            // Surcharge
      '',                                                            // Cess
      '',                                                            // Reserved
    ]);
  }

  /**
   * Build Part B Summary
   */
  private function buildPartBSummary($count, $totalGross, $totalTds) {
    return implode('^', [
      'SU',                                                 // Summary
      (int)$count,                                        // Total Payments
      number_format($totalGross, 2, '.', ''),            // Total Payment Amount
      number_format($totalTds, 2, '.', ''),              // Total TDS Deducted
      '',                                                  // Surcharge
      '',                                                  // Cess
    ]);
  }

  /**
   * Generate bulk Form 16 certificates for all deductees in FY
   * Returns array of [deductee_pan => file_path]
   */
  public function generateBulkForm16($outputDir = null) {
    if (!$outputDir) {
      $outputDir = __DIR__ . '/../uploads/forms/16';
    }

    if (!is_dir($outputDir)) {
      mkdir($outputDir, 0755, true);
    }

    // Get unique deductees from invoices for this FY
    $stmt = $this->pdo->prepare(
      "SELECT DISTINCT v.pan, v.name
      FROM invoices i
      JOIN vendors v ON i.vendor_id = v.id
      WHERE i.firm_id = ? AND i.fy = ? AND i.allocation_status = 'complete'
      ORDER BY v.pan"
    );
    $stmt->execute([$this->firmId, $this->fy]);
    $deductees = $stmt->fetchAll();

    if (empty($deductees)) {
      $this->error = 'No deductees found for this FY';
      return null;
    }

    $results = [];

    foreach ($deductees as $deductee) {
      $content = $this->generateForm16($deductee['pan'], $deductee['name']);
      if ($content) {
        $filename = sprintf('Form16_%s_%s_%s.txt',
          $this->firm['tan'],
          $deductee['pan'],
          $this->fy
        );
        $filepath = $outputDir . '/' . $filename;

        if (file_put_contents($filepath, $content) !== false) {
          $results[$deductee['pan']] = $filepath;
        }
      }
    }

    return !empty($results) ? $results : null;
  }

  /**
   * Generate Form 16 Part A (simplified version for digital transmission)
   */
  public function generateForm16PartA($deducteePan, $deducteeName) {
    if (!$this->firm) {
      return null;
    }

    // Get summary for deductee
    $stmt = $this->pdo->prepare(
      "SELECT
        COUNT(*) as payment_count,
        SUM(base_amount) as total_gross,
        SUM(total_tds) as total_tds,
        GROUP_CONCAT(DISTINCT section_code) as sections
      FROM invoices i
      JOIN vendors v ON i.vendor_id = v.id
      WHERE v.pan = ? AND i.firm_id = ? AND i.fy = ? AND i.allocation_status = 'complete'"
    );
    $stmt->execute([$deducteePan, $this->firmId, $this->fy]);
    $summary = $stmt->fetch();

    if (!$summary || $summary['total_tds'] == 0) {
      return null;
    }

    $fy = $this->fy;
    $ay = substr($fy, 5, 2);

    return [
      'certificate_no' => sprintf('16/%s/%s/%s', $ay, $this->firm['tan'], uniqid()),
      'deductor_pan' => $this->firm['pan'],
      'deductor_tan' => $this->firm['tan'],
      'deductor_name' => $this->firm['display_name'],
      'deductee_pan' => $deducteePan,
      'deductee_name' => $deducteeName,
      'fy' => $fy,
      'assessment_year' => $ay,
      'issue_date' => date('Y-m-d'),
      'payment_count' => (int)$summary['payment_count'],
      'total_gross' => (float)$summary['total_gross'],
      'total_tds' => (float)$summary['total_tds'],
      'sections' => explode(',', $summary['sections']),
    ];
  }
}
?>
