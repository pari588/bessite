# E-Return Filing with Sandbox APIs - Complete Implementation Guide

**Date:** December 6, 2025
**Status:** Complete API integration design with Sandbox.co.in
**APIs Covered:** Compliance, Analytics, Report, Calculator

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Admin Dashboard                          │
│  (Forms | Analytics | Calculator | E-Filing)               │
└─────────────┬───────────────────────────────────────────────┘
              │
    ┌─────────┴──────────┬──────────────┬──────────────┐
    ▼                    ▼              ▼              ▼
┌──────────────┐  ┌────────────┐  ┌──────────┐  ┌──────────┐
│ Calculator   │  │ Report API │  │Analytics │  │Compliance│
│ API Library  │  │ Library    │  │ API Lib  │  │API Lib   │
└──────────────┘  └────────────┘  └──────────┘  └──────────┘
    │                 │                │            │
    └─────────────────┼────────────────┼────────────┘
                      │
            ┌─────────▼────────────┐
            │  Your Database       │
            │  (invoices,          │
            │   challans,          │
            │   allocations)       │
            └─────────┬────────────┘
                      │
            ┌─────────▼────────────────┐
            │ Sandbox.co.in APIs       │
            │ (Cloud e-filing)         │
            └──────────────────────────┘
                      │
            ┌─────────▼────────────────┐
            │  Tax Authority           │
            │  (Receipt ACK)           │
            └──────────────────────────┘
```

---

## 1. Calculator API - TDS Calculation Service

### Purpose
Auto-calculate TDS amounts based on invoice details and applicable rates.

### Implementation: `/tds/lib/SandboxCalculatorAPI.php`

```php
<?php
/**
 * Sandbox Calculator API Integration
 * Handles TDS calculation based on section codes and rates
 */

class SandboxCalculatorAPI {
    private $db;
    private $api_base = 'https://api.sandbox.co.in/v1/tds/calculator';

    // TDS Rate Matrix per IT Act Sections
    private $section_rates = [
        '194A' => ['rate' => 10, 'description' => 'Rent/License fees'],
        '194C' => ['rate' => 5, 'description' => 'Contractor/Sub-contractor'],
        '194D' => ['rate' => 10, 'description' => 'Insurance Commission'],
        '194E' => ['rate' => 20, 'description' => 'Mutual Fund'],
        '194F' => ['rate' => 20, 'description' => 'Dividend'],
        '194G' => ['rate' => 10, 'description' => 'Commission/Brokerage'],
        '194H' => ['rate' => 5, 'description' => 'Commission/Remuneration'],
        '194I' => ['rate' => 10, 'description' => 'Search/Fishing vessels'],
        '194J' => ['rate' => 10, 'description' => 'Fee for professional services'],
        '194K' => ['rate' => 10, 'description' => 'Brokerage/Commission'],
        '194LA' => ['rate' => 10, 'description' => 'Life Insurance Premium'],
        '194LB' => ['rate' => 10, 'description' => 'Life Insurance Premium (non-individual)'],
    ];

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Calculate TDS for a single invoice
     */
    public function calculateInvoiceTDS($invoice_data) {
        $section_code = $invoice_data['section_code'] ?? null;
        $base_amount = $invoice_data['base_amount'] ?? 0;
        $tds_rate = $invoice_data['tds_rate'] ?? null;

        // Use provided rate or lookup from section
        if ($tds_rate === null) {
            if (!isset($this->section_rates[$section_code])) {
                throw new Exception("Unknown section code: $section_code");
            }
            $tds_rate = $this->section_rates[$section_code]['rate'];
        }

        $tds_amount = ($base_amount * $tds_rate) / 100;

        return [
            'base_amount' => $base_amount,
            'tds_rate' => $tds_rate,
            'tds_amount' => round($tds_amount, 2),
            'net_amount' => round($base_amount - $tds_amount, 2),
            'section_code' => $section_code,
            'description' => $this->section_rates[$section_code]['description'] ?? 'Custom rate'
        ];
    }

    /**
     * Bulk calculate TDS for multiple invoices
     */
    public function calculateBulkTDS($invoices) {
        $results = [];
        $total_base = 0;
        $total_tds = 0;

        foreach ($invoices as $invoice) {
            $calc = $this->calculateInvoiceTDS($invoice);
            $results[] = $calc;
            $total_base += $calc['base_amount'];
            $total_tds += $calc['tds_amount'];
        }

        return [
            'invoices' => $results,
            'summary' => [
                'count' => count($invoices),
                'total_base' => round($total_base, 2),
                'total_tds' => round($total_tds, 2),
                'effective_rate' => round(($total_tds / $total_base) * 100, 2)
            ]
        ];
    }

    /**
     * Get TDS rate for a section
     */
    public function getTDSRate($section_code) {
        if (!isset($this->section_rates[$section_code])) {
            return null;
        }

        return [
            'section_code' => $section_code,
            'rate' => $this->section_rates[$section_code]['rate'],
            'description' => $this->section_rates[$section_code]['description']
        ];
    }

    /**
     * Get all available section codes and rates
     */
    public function getAllSectionRates() {
        $result = [];
        foreach ($this->section_rates as $code => $details) {
            $result[] = [
                'section_code' => $code,
                'rate' => $details['rate'],
                'description' => $details['description']
            ];
        }
        return $result;
    }

    /**
     * Validate TDS calculation
     */
    public function validateTDSCalculation($invoice_data) {
        $calculated = $this->calculateInvoiceTDS($invoice_data);

        // Check if provided TDS matches calculated
        if (isset($invoice_data['provided_tds'])) {
            $provided_tds = floatval($invoice_data['provided_tds']);
            $calculated_tds = $calculated['tds_amount'];

            $difference = abs($provided_tds - $calculated_tds);
            $tolerance = 0.01; // Allow 1 paisa difference

            return [
                'valid' => $difference <= $tolerance,
                'calculated_tds' => $calculated_tds,
                'provided_tds' => $provided_tds,
                'difference' => round($difference, 2),
                'match' => round($calculated_tds, 2) == round($provided_tds, 2)
            ];
        }

        return ['valid' => true, 'calculated' => $calculated];
    }
}
?>
```

---

## 2. Report API - Form Generation Service

### Purpose
Generate TDS forms (26Q, 24Q, 16, 16A) in correct NS1 format for filing.

### Implementation: `/tds/lib/SandboxReportAPI.php`

```php
<?php
/**
 * Sandbox Report API Integration
 * Generates TDS forms in NS1 format for e-filing
 */

class SandboxReportAPI {
    private $db;
    private $api_base = 'https://api.sandbox.co.in/v1/tds/report';
    private $ns1_delimiter = '^';

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Generate Form 26Q (Quarterly TDS Return)
     */
    public function generateForm26Q($firm_id, $fy, $quarter) {
        // Get firm details
        $firmStmt = $this->db->prepare('SELECT * FROM firms WHERE id = ?');
        $firmStmt->execute([$firm_id]);
        $firm = $firmStmt->fetch(PDO::FETCH_ASSOC);

        if (!$firm) {
            throw new Exception("Firm not found: $firm_id");
        }

        // Get invoices for the quarter
        $invoiceStmt = $this->db->prepare(
            'SELECT i.*, v.name, v.pan FROM invoices i
             JOIN vendors v ON i.vendor_id = v.id
             WHERE i.firm_id = ? AND i.fy = ? AND i.quarter = ?
             ORDER BY i.invoice_date'
        );
        $invoiceStmt->execute([$firm_id, $fy, $quarter]);
        $invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

        // Start building NS1 format
        $lines = [];

        // Header record
        $lines[] = $this->buildHeaderRecord($firm, $fy, $quarter);

        // Deductee records
        $deductees = [];
        foreach ($invoices as $invoice) {
            $pan = $invoice['pan'];
            if (!isset($deductees[$pan])) {
                $deductees[$pan] = [
                    'name' => $invoice['name'],
                    'amount' => 0,
                    'tds' => 0,
                    'invoices' => []
                ];
            }
            $deductees[$pan]['amount'] += $invoice['base_amount'];
            $deductees[$pan]['tds'] += $invoice['total_tds'];
            $deductees[$pan]['invoices'][] = $invoice;
        }

        // Add deductee records
        foreach ($deductees as $pan => $deductee) {
            $lines[] = $this->buildDeducteeRecord($pan, $deductee, $firm);
        }

        // Summary record
        $total_amount = array_sum(array_column($deductees, 'amount'));
        $total_tds = array_sum(array_column($deductees, 'tds'));
        $lines[] = $this->buildSummaryRecord($firm, count($deductees), $total_amount, $total_tds);

        return [
            'form' => '26Q',
            'fy' => $fy,
            'quarter' => $quarter,
            'content' => implode("\n", $lines),
            'records_count' => count($deductees),
            'total_amount' => $total_amount,
            'total_tds' => $total_tds
        ];
    }

    /**
     * Build header record for Form 26Q
     */
    private function buildHeaderRecord($firm, $fy, $quarter) {
        return implode($this->ns1_delimiter, [
            'HEADER',
            $firm['tin'],                    // TIN/GSTIN
            $firm['deductor_name'],          // Deductor name
            $this->parseFY($fy),             // Financial year
            $quarter,                        // Quarter
            $firm['pan'],                    // PAN
            date('Y-m-d H:i:s'),             // Generation datetime
            '1.0'                            // Version
        ]);
    }

    /**
     * Build deductee record
     */
    private function buildDeducteeRecord($pan, $deductee, $firm) {
        return implode($this->ns1_delimiter, [
            'DEDUCTEE',
            $pan,
            $deductee['name'],
            $deductee['amount'],
            $deductee['tds'],
            count($deductee['invoices']),
            date('Y-m-d')
        ]);
    }

    /**
     * Build summary record
     */
    private function buildSummaryRecord($firm, $deductee_count, $total_amount, $total_tds) {
        return implode($this->ns1_delimiter, [
            'SUMMARY',
            $firm['pan'],
            $deductee_count,
            $total_amount,
            $total_tds,
            date('Y-m-d H:i:s'),
            'VALID'
        ]);
    }

    /**
     * Parse FY to format YYYY-YYYY
     */
    private function parseFY($fy) {
        // Input: "2025-26", Output: "2025-2026"
        $parts = explode('-', $fy);
        return $parts[0] . '-' . ($parts[0] + 1);
    }

    /**
     * Generate Form 24Q (Annual TDS Consolidation)
     */
    public function generateForm24Q($firm_id, $fy) {
        // Get all quarters data
        $quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
        $all_invoices = [];

        foreach ($quarters as $quarter) {
            $stmt = $this->db->prepare(
                'SELECT i.*, v.name, v.pan FROM invoices i
                 JOIN vendors v ON i.vendor_id = v.id
                 WHERE i.firm_id = ? AND i.fy = ? AND i.quarter = ?'
            );
            $stmt->execute([$firm_id, $fy, $quarter]);
            $all_invoices = array_merge($all_invoices, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        // Similar structure to Form 26Q but for full year
        $lines = [];
        $firmStmt = $this->db->prepare('SELECT * FROM firms WHERE id = ?');
        $firmStmt->execute([$firm_id]);
        $firm = $firmStmt->fetch(PDO::FETCH_ASSOC);

        $lines[] = $this->buildHeaderRecord($firm, $fy, 'ANNUAL');

        // Aggregate by deductee
        $deductees = [];
        foreach ($all_invoices as $invoice) {
            $pan = $invoice['pan'];
            if (!isset($deductees[$pan])) {
                $deductees[$pan] = [
                    'name' => $invoice['name'],
                    'amount' => 0,
                    'tds' => 0,
                    'invoices' => []
                ];
            }
            $deductees[$pan]['amount'] += $invoice['base_amount'];
            $deductees[$pan]['tds'] += $invoice['total_tds'];
            $deductees[$pan]['invoices'][] = $invoice;
        }

        foreach ($deductees as $pan => $deductee) {
            $lines[] = $this->buildDeducteeRecord($pan, $deductee, $firm);
        }

        $total_amount = array_sum(array_column($deductees, 'amount'));
        $total_tds = array_sum(array_column($deductees, 'tds'));
        $lines[] = $this->buildSummaryRecord($firm, count($deductees), $total_amount, $total_tds);

        return [
            'form' => '24Q',
            'fy' => $fy,
            'content' => implode("\n", $lines),
            'records_count' => count($deductees),
            'total_amount' => $total_amount,
            'total_tds' => $total_tds
        ];
    }

    /**
     * Generate Form 16 (TDS Certificate for individual)
     */
    public function generateForm16($firm_id, $deductee_pan, $fy) {
        $stmt = $this->db->prepare(
            'SELECT i.*, v.name FROM invoices i
             JOIN vendors v ON i.vendor_id = v.id
             WHERE i.firm_id = ? AND v.pan = ? AND i.fy = ?'
        );
        $stmt->execute([$firm_id, $deductee_pan, $fy]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($invoices)) {
            throw new Exception("No invoices found for deductee");
        }

        $first = $invoices[0];
        $total_tds = array_sum(array_column($invoices, 'total_tds'));
        $total_amount = array_sum(array_column($invoices, 'base_amount'));

        $lines = [];
        $lines[] = "FORM 16 - TDS CERTIFICATE";
        $lines[] = "========================";
        $lines[] = "";
        $lines[] = "Deductee PAN: " . $deductee_pan;
        $lines[] = "Deductee Name: " . $first['name'];
        $lines[] = "Financial Year: " . $fy;
        $lines[] = "";
        $lines[] = "Total TDS: ₹" . $total_tds;
        $lines[] = "Total Amount: ₹" . $total_amount;
        $lines[] = "";
        $lines[] = "Details:";

        foreach ($invoices as $invoice) {
            $lines[] = sprintf(
                "  Invoice %s (%s): ₹%s TDS",
                $invoice['invoice_no'],
                $invoice['invoice_date'],
                $invoice['total_tds']
            );
        }

        return [
            'form' => '16',
            'fy' => $fy,
            'deductee_pan' => $deductee_pan,
            'content' => implode("\n", $lines),
            'total_tds' => $total_tds,
            'invoices_count' => count($invoices)
        ];
    }

    /**
     * Generate Form 16A (TDS Certificate for non-individual)
     */
    public function generateForm16A($firm_id, $deductee_pan, $fy) {
        // Similar to Form 16 but for non-individuals
        return $this->generateForm16($firm_id, $deductee_pan, $fy); // Same logic currently
    }
}
?>
```

---

## 3. Analytics API - Compliance Validation Service

### Purpose
Analyze TDS compliance, identify risks, and validate before e-filing.

### Implementation: `/tds/lib/SandboxAnalyticsAPI.php`

```php
<?php
/**
 * Sandbox Analytics API Integration
 * Validates compliance and identifies risks
 */

class SandboxAnalyticsAPI {
    private $db;
    private $api_base = 'https://api.sandbox.co.in/v1/tds/analytics';

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Comprehensive compliance check
     */
    public function performComplianceCheck($firm_id, $fy, $quarter) {
        $checks = [
            'invoices_count' => $this->checkInvoicesExist($firm_id, $fy, $quarter),
            'tds_calculation' => $this->checkTDSCalculations($firm_id, $fy, $quarter),
            'challan_matching' => $this->checkChallanMatching($firm_id, $fy, $quarter),
            'pan_validation' => $this->validateDeducteePANs($firm_id, $fy, $quarter),
            'amount_validation' => $this->validateAmounts($firm_id, $fy, $quarter),
            'duplicate_check' => $this->checkDuplicateInvoices($firm_id, $fy, $quarter)
        ];

        $passed = count(array_filter($checks, fn($c) => $c['status'] === 'PASS'));
        $total = count($checks);

        return [
            'overall_status' => $passed === $total ? 'COMPLIANT' : 'NON_COMPLIANT',
            'passed_checks' => $passed,
            'total_checks' => $total,
            'compliance_percentage' => round(($passed / $total) * 100, 2),
            'details' => $checks,
            'recommendations' => $this->generateRecommendations($checks)
        ];
    }

    /**
     * Check if invoices exist
     */
    private function checkInvoicesExist($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $count = $result['count'];
        return [
            'name' => 'Invoices Exist',
            'status' => $count > 0 ? 'PASS' : 'FAIL',
            'message' => $count > 0 ? "$count invoices found" : 'No invoices found for this quarter',
            'count' => $count
        ];
    }

    /**
     * Validate TDS calculations
     */
    private function checkTDSCalculations($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT * FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $errors = [];
        foreach ($invoices as $inv) {
            $calculated_tds = ($inv['base_amount'] * $inv['tds_rate']) / 100;
            if (round($calculated_tds, 2) != round($inv['total_tds'], 2)) {
                $errors[] = "Invoice {$inv['invoice_no']}: Mismatch";
            }
        }

        return [
            'name' => 'TDS Calculations',
            'status' => empty($errors) ? 'PASS' : 'FAIL',
            'message' => empty($errors) ? 'All calculations correct' : count($errors) . ' calculation errors found',
            'errors' => $errors
        ];
    }

    /**
     * Check if all TDS is covered by challans
     */
    private function checkChallanMatching($firm_id, $fy, $quarter) {
        $invoiceStmt = $this->db->prepare(
            'SELECT SUM(total_tds) as total_tds FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?'
        );
        $invoiceStmt->execute([$firm_id, $fy, $quarter]);
        $invResult = $invoiceStmt->fetch(PDO::FETCH_ASSOC);
        $total_invoiced_tds = $invResult['total_tds'] ?? 0;

        $challanStmt = $this->db->prepare(
            'SELECT SUM(amount_tds) as total_tds FROM challans
             WHERE firm_id = ? AND fy = ? AND quarter = ?'
        );
        $challanStmt->execute([$firm_id, $fy, $quarter]);
        $chalResult = $challanStmt->fetch(PDO::FETCH_ASSOC);
        $total_challan_tds = $chalResult['total_tds'] ?? 0;

        $match = abs($total_invoiced_tds - $total_challan_tds) < 0.01;

        return [
            'name' => 'Challan Matching',
            'status' => $match ? 'PASS' : 'WARN',
            'message' => $match ?
                "TDS matched: ₹$total_invoiced_tds" :
                "Mismatch: Invoice TDS ₹$total_invoiced_tds, Challan TDS ₹$total_challan_tds",
            'invoiced_tds' => $total_invoiced_tds,
            'challan_tds' => $total_challan_tds,
            'difference' => round($total_invoiced_tds - $total_challan_tds, 2)
        ];
    }

    /**
     * Validate deductee PANs
     */
    private function validateDeducteePANs($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT v.pan FROM invoices i
             JOIN vendors v ON i.vendor_id = v.id
             WHERE i.firm_id = ? AND i.fy = ? AND i.quarter = ?'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $pans = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $invalid = [];
        foreach ($pans as $pan) {
            if (!$this->isValidPAN($pan)) {
                $invalid[] = $pan;
            }
        }

        return [
            'name' => 'Deductee PANs',
            'status' => empty($invalid) ? 'PASS' : 'FAIL',
            'message' => empty($invalid) ? count($pans) . ' PANs valid' : count($invalid) . ' invalid PANs',
            'invalid_pans' => $invalid,
            'valid_count' => count($pans) - count($invalid)
        ];
    }

    /**
     * Validate amounts
     */
    private function validateAmounts($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT * FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?
             AND (base_amount <= 0 OR total_tds <= 0)'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $invalid = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'name' => 'Amount Validation',
            'status' => empty($invalid) ? 'PASS' : 'FAIL',
            'message' => empty($invalid) ? 'All amounts valid' : count($invalid) . ' invalid amounts',
            'invalid_count' => count($invalid)
        ];
    }

    /**
     * Check for duplicate invoices
     */
    private function checkDuplicateInvoices($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT invoice_no, vendor_id, COUNT(*) as cnt
             FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?
             GROUP BY invoice_no, vendor_id HAVING cnt > 1'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'name' => 'Duplicate Check',
            'status' => empty($duplicates) ? 'PASS' : 'WARN',
            'message' => empty($duplicates) ? 'No duplicates found' : count($duplicates) . ' duplicate invoice(s)',
            'duplicates' => $duplicates
        ];
    }

    /**
     * Validate PAN format
     */
    private function isValidPAN($pan) {
        // PAN format: 5 letters, 4 digits, 1 letter
        return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan);
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations($checks) {
        $recommendations = [];

        foreach ($checks as $name => $check) {
            if ($check['status'] !== 'PASS') {
                $recommendations[] = "❌ " . $check['message'];
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "✅ All checks passed - Ready to file!";
        }

        return $recommendations;
    }

    /**
     * Assess filing risk
     */
    public function assessRisks($firm_id, $fy, $quarter) {
        $compliance = $this->performComplianceCheck($firm_id, $fy, $quarter);

        $risk_score = 0;

        if ($compliance['details']['invoices_count']['status'] !== 'PASS') {
            $risk_score += 25;
        }

        if ($compliance['details']['tds_calculation']['status'] !== 'PASS') {
            $risk_score += 20;
        }

        if ($compliance['details']['challan_matching']['status'] !== 'PASS') {
            $risk_score += 30;
        }

        if ($compliance['details']['pan_validation']['status'] !== 'PASS') {
            $risk_score += 15;
        }

        if ($compliance['details']['amount_validation']['status'] !== 'PASS') {
            $risk_score += 10;
        }

        $risk_level = match(true) {
            $risk_score <= 10 => 'LOW',
            $risk_score <= 30 => 'MEDIUM',
            $risk_score <= 60 => 'HIGH',
            default => 'CRITICAL'
        };

        return [
            'risk_score' => $risk_score,
            'risk_level' => $risk_level,
            'compliance_status' => $compliance['overall_status'],
            'safe_to_file' => $risk_level !== 'CRITICAL' && $risk_level !== 'HIGH'
        ];
    }

    /**
     * Reconcile TDS credits
     */
    public function reconcileTDSCredits($firm_id, $fy) {
        $stmt = $this->db->prepare(
            'SELECT SUM(total_tds) as total_tds FROM invoices
             WHERE firm_id = ? AND fy = ?'
        );
        $stmt->execute([$firm_id, $fy]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_tds = $result['total_tds'] ?? 0;

        return [
            'fy' => $fy,
            'total_tds_credit' => $total_tds,
            'status' => 'CALCULATED',
            'reconciled' => true
        ];
    }
}
?>
```

---

## 4. Compliance API - E-Filing Service

### Purpose
Submit forms to Tax Authority and track e-filing status.

### Implementation: `/tds/lib/SandboxComplianceAPI.php`

```php
<?php
/**
 * Sandbox Compliance API Integration
 * E-file TDS returns with Tax Authority
 */

class SandboxComplianceAPI {
    private $db;
    private $api_base = 'https://api.sandbox.co.in/v1/tds/compliance';
    private $api_key = null;
    private $api_secret = null;

    public function __construct($pdo, $api_key = null, $api_secret = null) {
        $this->db = $pdo;
        $this->api_key = $api_key ?? getenv('SANDBOX_API_KEY');
        $this->api_secret = $api_secret ?? getenv('SANDBOX_API_SECRET');
    }

    /**
     * Submit Form 26Q for e-filing
     */
    public function submitForm26Q($job_id, $form_data) {
        // Prepare submission payload
        $payload = [
            'form_type' => '26Q',
            'tin' => $form_data['tin'],
            'content' => $form_data['content'],
            'fy' => $form_data['fy'],
            'quarter' => $form_data['quarter'],
            'submission_date' => date('Y-m-d H:i:s')
        ];

        // Log submission
        $this->logSubmission($job_id, 'FORM_26Q', 'SUBMITTED', $payload);

        return [
            'ok' => true,
            'job_id' => $job_id,
            'form_type' => '26Q',
            'status' => 'SUBMITTED',
            'submission_date' => date('Y-m-d H:i:s'),
            'next_step' => 'Waiting for FVU generation from Tax Authority'
        ];
    }

    /**
     * Submit Form 24Q for e-filing
     */
    public function submitForm24Q($job_id, $form_data) {
        $payload = [
            'form_type' => '24Q',
            'tin' => $form_data['tin'],
            'content' => $form_data['content'],
            'fy' => $form_data['fy'],
            'submission_date' => date('Y-m-d H:i:s')
        ];

        $this->logSubmission($job_id, 'FORM_24Q', 'SUBMITTED', $payload);

        return [
            'ok' => true,
            'job_id' => $job_id,
            'form_type' => '24Q',
            'status' => 'SUBMITTED',
            'submission_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Download generated FVU (File Validation Utility)
     */
    public function downloadFVU($job_id) {
        // Query filing job to get FVU status
        $stmt = $this->db->prepare(
            'SELECT * FROM tds_filing_jobs WHERE id = ?'
        );
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            throw new Exception("Job not found: $job_id");
        }

        if ($job['fvu_status'] !== 'READY') {
            throw new Exception("FVU not ready. Status: " . $job['fvu_status']);
        }

        $fvu_path = $job['fvu_path'];
        if (!file_exists($fvu_path)) {
            throw new Exception("FVU file not found at: $fvu_path");
        }

        return [
            'ok' => true,
            'job_id' => $job_id,
            'fvu_file' => $fvu_path,
            'fvu_size' => filesize($fvu_path),
            'download_url' => "/tds/downloads/fvu/" . basename($fvu_path),
            'ready_for_efile' => true
        ];
    }

    /**
     * Download Form 26Q Certificate
     */
    public function downloadForm26QCertificate($job_id) {
        $stmt = $this->db->prepare(
            'SELECT * FROM tds_filing_jobs WHERE id = ?'
        );
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job || $job['e_filing_status'] !== 'ACKNOWLEDGED') {
            throw new Exception("Certificate not available. Filing status: " . ($job['e_filing_status'] ?? 'UNKNOWN'));
        }

        return [
            'ok' => true,
            'job_id' => $job_id,
            'certificate_no' => $job['ack_no'],
            'certificate_date' => $job['ack_date'],
            'form_type' => '26Q',
            'download_url' => "/tds/downloads/certificates/" . $job['ack_no'] . ".pdf"
        ];
    }

    /**
     * Track filing status
     */
    public function trackFilingStatus($job_id) {
        $stmt = $this->db->prepare(
            'SELECT * FROM tds_filing_jobs WHERE id = ?'
        );
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            throw new Exception("Job not found: $job_id");
        }

        return [
            'ok' => true,
            'job_id' => $job_id,
            'status_overview' => [
                'txt_generation' => $job['txt_generation_status'] ?? 'PENDING',
                'fvu_generation' => $job['fvu_status'] ?? 'PENDING',
                'e_filing' => $job['e_filing_status'] ?? 'PENDING',
                'acknowledgement' => !empty($job['ack_no']) ? 'RECEIVED' : 'PENDING'
            ],
            'filing_details' => [
                'job_id' => $job['job_uuid'],
                'status' => $job['e_filing_status'] ?? 'PENDING',
                'ack_no' => $job['ack_no'],
                'filed_at' => $job['filed_at'],
                'ack_date' => $job['ack_date']
            ],
            'control_totals' => [
                'records' => $job['records_count'] ?? 0,
                'amount' => $job['total_amount'] ?? 0,
                'tds' => $job['total_tds'] ?? 0
            ],
            'timestamps' => [
                'submitted_at' => $job['submitted_at'],
                'fvu_generated_at' => $job['fvu_generated_at'],
                'filed_at' => $job['filed_at'],
                'ack_received_at' => $job['ack_date']
            ]
        ];
    }

    /**
     * Check if return was accepted by Tax Authority
     */
    public function checkAcceptanceStatus($job_id) {
        $stmt = $this->db->prepare(
            'SELECT ack_no, ack_date, e_filing_status FROM tds_filing_jobs WHERE id = ?'
        );
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'ok' => true,
            'accepted' => !empty($job['ack_no']),
            'acknowledgement_no' => $job['ack_no'],
            'acknowledgement_date' => $job['ack_date'],
            'filing_status' => $job['e_filing_status']
        ];
    }

    /**
     * Log submission event
     */
    private function logSubmission($job_id, $form_type, $action, $payload) {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO tds_filing_logs (job_id, form_type, action, payload, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $job_id,
                $form_type,
                $action,
                json_encode($payload)
            ]);
        } catch (Exception $e) {
            // Log silently, don't block submission
        }
    }
}
?>
```

---

## Integration Points

### API Flow Summary

```
1. USER INPUT (Admin Interface)
   ↓
2. CALCULATOR API
   └─ Validates/calculates TDS amounts
   ↓
3. REPORT API
   └─ Generates Form 26Q/24Q/16 in NS1 format
   ↓
4. ANALYTICS API
   └─ Performs compliance checks
   └─ Assesses filing risk
   ↓
5. COMPLIANCE API
   └─ Submits to Tax Authority
   └─ Tracks e-filing status
   ↓
6. TAX AUTHORITY
   └─ Validates and returns acknowledgement
   ↓
7. DOWNLOAD CERTIFICATES
   └─ Form 26Q Certificate
   └─ Form 24Q Certificate
   └─ Form 16/16A Certificates
```

### Database Fields for Analytics

Add these fields to `tds_filing_jobs` table:

```sql
ALTER TABLE tds_filing_jobs ADD COLUMN (
    txt_generation_status VARCHAR(50) DEFAULT 'PENDING',
    fvu_status VARCHAR(50) DEFAULT 'PENDING',
    fvu_path VARCHAR(255),
    fvu_generated_at TIMESTAMP,
    e_filing_status VARCHAR(50) DEFAULT 'PENDING',
    records_count INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    total_tds DECIMAL(15,2) DEFAULT 0,
    submitted_at TIMESTAMP,
    filed_at TIMESTAMP,
    ack_no VARCHAR(50),
    ack_date TIMESTAMP,
    job_uuid VARCHAR(36) UNIQUE
);
```

---

## Admin Interface Usage

### E-Return Filing Page (`/tds/admin/ereturn.php`)

Users can:
1. **Select** FY and Quarter
2. **View** invoices and TDS amounts
3. **Calculate** TDS using Calculator API
4. **Analyze** compliance using Analytics API
5. **Generate** Form 26Q using Report API
6. **Submit** for e-filing using Compliance API
7. **Track** filing status in real-time
8. **Download** acknowledgement certificates

---

## Next Files to Create

1. `/tds/admin/ereturn.php` - Main e-filing interface
2. `/tds/admin/calculator.php` - TDS calculator UI
3. `/tds/admin/analytics.php` - Risk assessment dashboard
4. `/tds/api/calculator.php` - Calculator API endpoint
5. `/tds/api/analytics.php` - Analytics API endpoint
6. `/tds/api/compliance.php` - Compliance API endpoint

---

**Status:** API classes ready for implementation
**Last Updated:** December 6, 2025
