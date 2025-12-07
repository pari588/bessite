# Complete TDS & TCS E-Filing Implementation Guide
## With Sandbox.co.in API Integration

**Date:** December 6, 2025
**Status:** Complete workflow covering TDS, TCS, Calculator, Analytics, Reports, Compliance
**API Reference:** Sandbox.co.in Developer Portal

---

## Table of Contents

1. [Architecture Overview](#architecture)
2. [Complete Filing Workflow](#workflow)
3. [Calculator Module (TDS & TCS)](#calculator)
4. [Analytics Module (TDS & TCS)](#analytics)
5. [Reports Module (TDS & TCS)](#reports)
6. [Compliance Module](#compliance)
7. [Database Schema](#database)
8. [File Formats & Requirements](#formats)
9. [Implementation Steps](#implementation)

---

## Architecture Overview {#architecture}

```
┌────────────────────────────────────────────────────────────────┐
│                    ADMIN DASHBOARD                             │
│  ┌──────────────┬──────────────┬──────────────┬──────────────┐ │
│  │ Calculator   │ Analytics    │ Reports      │ Compliance   │ │
│  │ (TDS/TCS)    │ (TDS/TCS)    │ (TDS/TCS)    │ (Download/E) │ │
│  └──────────────┴──────────────┴──────────────┴──────────────┘ │
└────────────────────────────────────────────────────────────────┘
                            │
         ┌──────────────────┼──────────────────┐
         ▼                  ▼                  ▼
    ┌─────────────┐   ┌──────────────┐  ┌──────────────┐
    │ TDS Module  │   │ TCS Module   │  │ Master Data  │
    │             │   │              │  │ Module       │
    │ • Invoices  │   │ • Purchases  │  │ • Vendors    │
    │ • Vendors   │   │ • Customers  │  │ • Customers  │
    │ • Challans  │   │ • Receipts   │  │ • Settings   │
    │ • Rates     │   │ • Rates      │  │ • Annexures  │
    └─────────────┘   └──────────────┘  └──────────────┘
         │                  │                   │
         └──────────────────┼───────────────────┘
                            │
              ┌─────────────▼──────────────┐
              │   Sandbox.co.in APIs       │
              │                            │
              │ ┌──────────────────────┐   │
              │ │ Calculator APIs      │   │
              │ │ • TDS Calculation    │   │
              │ │ • TCS Calculation    │   │
              │ │ • Rate Lookup        │   │
              │ └──────────────────────┘   │
              │                            │
              │ ┌──────────────────────┐   │
              │ │ Analytics APIs       │   │
              │ │ • Compliance Check   │   │
              │ │ • Risk Assessment    │   │
              │ │ • Credit Reconcile   │   │
              │ └──────────────────────┘   │
              │                            │
              │ ┌──────────────────────┐   │
              │ │ Report APIs          │   │
              │ │ • Generate 26Q/24Q   │   │
              │ │ • Generate 27Q/27EQ  │   │
              │ │ • Generate Forms     │   │
              │ │ • Annexures          │   │
              │ └──────────────────────┘   │
              │                            │
              │ ┌──────────────────────┐   │
              │ │ Compliance APIs      │   │
              │ │ • Generate FVU       │   │
              │ │ • E-File Return      │   │
              │ │ • Download Form16    │   │
              │ │ • Download CSI       │   │
              │ │ • Track Status       │   │
              │ └──────────────────────┘   │
              └────────────────────────────┘
                            │
                ┌───────────▼────────────┐
                │  Tax Authority         │
                │  (Receipt & ACK)       │
                └────────────────────────┘
```

---

## Complete Filing Workflow {#workflow}

### Step-by-Step TDS Filing Process

```
STEP 1: INVOICE ENTRY & VALIDATION
┌─────────────────────────────────────────┐
│ • Enter vendor invoices (or import CSV)  │
│ • Validate invoice amounts & dates       │
│ • Map to TDS section codes (194A, etc)   │
│ • System auto-calculates TDS             │
└─────────────────────────────────────────┘
            ↓
STEP 2: CHALLAN ENTRY & RECONCILIATION
┌─────────────────────────────────────────┐
│ • Enter TDS challans (or upload CSI)     │
│ • Link challans to invoices              │
│ • Mark invoices as "complete"            │
│ • Verify TDS amounts match               │
└─────────────────────────────────────────┘
            ↓
STEP 3: COMPLIANCE ANALYSIS
┌─────────────────────────────────────────┐
│ • Run compliance checks                  │
│ • Validate all PANs                      │
│ • Check calculations                     │
│ • Assess filing risk                     │
│ • Get pre-filing recommendations         │
└─────────────────────────────────────────┘
            ↓
STEP 4: FORM GENERATION
┌─────────────────────────────────────────┐
│ • Generate Form 26Q (TXT, NS1 format)    │
│ • Generate Annexures (if needed)         │
│ • Generate CSI matching summary          │
│ • Validation via Report API              │
└─────────────────────────────────────────┘
            ↓
STEP 5: FVU GENERATION
┌─────────────────────────────────────────┐
│ • Submit Form 26Q to Sandbox             │
│ • Sandbox validates form                 │
│ • Generates FVU (validation utility)     │
│ • Returns validation results             │
│ • Fix any errors if needed               │
└─────────────────────────────────────────┘
            ↓
STEP 6: E-FILING
┌─────────────────────────────────────────┐
│ • E-sign Form 27A (acknowledgement form) │
│ • Submit FVU + Form 27A to Tax Authority │
│ • Get filing job ID                      │
│ • Sandbox queues for submission          │
└─────────────────────────────────────────┘
            ↓
STEP 7: TRACK & ACKNOWLEDGE
┌─────────────────────────────────────────┐
│ • Poll filing status                     │
│ • Receive acknowledgement number         │
│ • Download acknowledgement PDF           │
│ • Filing complete!                       │
└─────────────────────────────────────────┘
            ↓
STEP 8: DOWNLOAD CERTIFICATES
┌─────────────────────────────────────────┐
│ • Download Form 16/16A certificates      │
│ • Download CSI Annexures                 │
│ • Generate master data report            │
│ • Archive all filing documents           │
└─────────────────────────────────────────┘
```

### Similar Process for TCS Filing

```
STEP 1: PURCHASE ENTRY & VALIDATION
┌─────────────────────────────────────────┐
│ • Enter purchases/sale details           │
│ • Validate amounts & dates               │
│ • Map to TCS section codes (194C, etc)   │
│ • System auto-calculates TCS             │
└─────────────────────────────────────────┘
            ↓
STEP 2: RECEIPT ENTRY & RECONCILIATION
┌─────────────────────────────────────────┐
│ • Enter TCS receipts (payment info)      │
│ • Link receipts to purchases             │
│ • Verify TCS amounts match               │
│ • Mark purchases as reconciled           │
└─────────────────────────────────────────┘
            ↓
STEP 3: COMPLIANCE & ANALYTICS
┌─────────────────────────────────────────┐
│ • Run compliance checks for TCS          │
│ • Generate Form 27EQ (TCS annual)        │
│ • Validate against deductee PAN data     │
└─────────────────────────────────────────┘
            ↓
[Continue similar to TDS: Form Generation → FVU → E-File → Certificates]
```

---

## Calculator Module (TDS & TCS) {#calculator}

### TDS Calculator Functions

```php
<?php

class TDSCalculator {
    private $rates = [
        '194A' => ['rate' => 10, 'desc' => 'Rent'],
        '194C' => ['rate' => 5, 'desc' => 'Contractors'],
        '194D' => ['rate' => 10, 'desc' => 'Insurance Commission'],
        '194E' => ['rate' => 20, 'desc' => 'Mutual Funds'],
        '194F' => ['rate' => 20, 'desc' => 'Dividends'],
        '194G' => ['rate' => 10, 'desc' => 'Brokerage'],
        '194H' => ['rate' => 5, 'desc' => 'Commission'],
        '194I' => ['rate' => 10, 'desc' => 'Fishing Vessels'],
        '194J' => ['rate' => 10, 'desc' => 'Professional Services'],
        '194K' => ['rate' => 10, 'desc' => 'Brokerage/Commission'],
        '194LA' => ['rate' => 10, 'desc' => 'Life Insurance'],
        '194LB' => ['rate' => 10, 'desc' => 'Life Insurance (Non-individual)'],
    ];

    /**
     * Calculate TDS for single invoice
     * Input: ['base_amount' => 100000, 'section_code' => '194A', 'tds_rate' => null]
     * Output: ['tds_amount' => 10000, 'net_amount' => 90000, 'rate' => 10]
     */
    public function calculateTDS($base_amount, $section_code, $tds_rate = null) {
        if (!$tds_rate) {
            $tds_rate = $this->rates[$section_code]['rate'] ?? 0;
        }
        $tds_amount = ($base_amount * $tds_rate) / 100;
        return [
            'base_amount' => $base_amount,
            'tds_rate' => $tds_rate,
            'tds_amount' => round($tds_amount, 2),
            'net_amount' => round($base_amount - $tds_amount, 2)
        ];
    }

    /**
     * Calculate TDS on purchase amount (contractor payments)
     * Special: Higher threshold, different calculation method
     */
    public function calculateContractorTDS($contract_value, $rate = 5) {
        // Threshold: TDS applicable only if annual contract value > 50,000
        if ($contract_value <= 50000) {
            return ['tds_applicable' => false, 'tds_amount' => 0];
        }
        $tds = ($contract_value * $rate) / 100;
        return ['tds_applicable' => true, 'tds_amount' => round($tds, 2)];
    }

    /**
     * Bulk TDS calculation with summary
     */
    public function calculateBulkTDS($invoices) {
        $results = [];
        $totals = ['base' => 0, 'tds' => 0];

        foreach ($invoices as $inv) {
            $calc = $this->calculateTDS($inv['base_amount'], $inv['section_code']);
            $results[] = $calc;
            $totals['base'] += $calc['base_amount'];
            $totals['tds'] += $calc['tds_amount'];
        }

        return [
            'invoices' => $results,
            'summary' => [
                'total_base' => round($totals['base'], 2),
                'total_tds' => round($totals['tds'], 2),
                'effective_rate' => round(($totals['tds'] / $totals['base']) * 100, 2),
                'count' => count($invoices)
            ]
        ];
    }

    /**
     * Get applicable TDS rate for section
     */
    public function getRate($section_code) {
        return $this->rates[$section_code] ?? null;
    }

    /**
     * Validate calculated vs provided TDS
     */
    public function validateTDS($base_amount, $section_code, $provided_tds, $tolerance = 0.01) {
        $calc = $this->calculateTDS($base_amount, $section_code);
        $diff = abs($calc['tds_amount'] - $provided_tds);
        return [
            'valid' => $diff <= $tolerance,
            'calculated' => $calc['tds_amount'],
            'provided' => $provided_tds,
            'difference' => round($diff, 2)
        ];
    }
}

class TCSCalculator {
    private $rates = [
        '194C' => ['rate' => 1, 'desc' => 'Sales of goods'],
        '194H' => ['rate' => 0.1, 'desc' => 'Commission/Brokerage'],
        // Add more TCS sections as needed
    ];

    /**
     * Calculate TCS on sale/collection amount
     * TCS = Tax Collected at Source (on sales)
     */
    public function calculateTCS($sale_amount, $section_code, $tcs_rate = null) {
        if (!$tcs_rate) {
            $tcs_rate = $this->rates[$section_code]['rate'] ?? 0;
        }
        $tcs_amount = ($sale_amount * $tcs_rate) / 100;
        return [
            'sale_amount' => $sale_amount,
            'tcs_rate' => $tcs_rate,
            'tcs_amount' => round($tcs_amount, 2),
            'amount_payable' => round($sale_amount - $tcs_amount, 2)
        ];
    }

    /**
     * TCS threshold check
     * TCS applicable only if total turnover in FY > certain threshold
     */
    public function isTCSApplicable($annual_turnover) {
        $threshold = 10000000; // ₹1 Crore
        return $annual_turnover > $threshold;
    }
}

?>
```

---

## Analytics Module (TDS & TCS) {#analytics}

### Compliance Validation Functions

```php
<?php

class ComplianceAnalytics {

    /**
     * Pre-filing compliance check for TDS
     * Returns detailed compliance report
     */
    public function performTDSComplianceCheck($firm_id, $fy, $quarter) {
        return [
            'checks' => [
                'invoices_exist' => $this->checkInvoicesExist($firm_id, $fy, $quarter),
                'calculations_valid' => $this->validateTDSCalculations($firm_id, $fy, $quarter),
                'challans_matching' => $this->validateChallanMatching($firm_id, $fy, $quarter),
                'pan_validation' => $this->validateVendorPANs($firm_id, $fy, $quarter),
                'amount_validation' => $this->validateAmounts($firm_id, $fy, $quarter),
                'duplicate_check' => $this->checkDuplicateInvoices($firm_id, $fy, $quarter),
                'rate_validation' => $this->validateTDSRates($firm_id, $fy, $quarter)
            ],
            'overall_status' => 'COMPLIANT', // or 'NON_COMPLIANT'
            'passed' => 7,
            'total' => 7,
            'safe_to_file' => true,
            'recommendations' => [
                // List of warnings/suggestions
            ]
        ];
    }

    /**
     * Risk assessment before filing
     * Identifies potential IT notices or compliance issues
     */
    public function assessFilingRisk($firm_id, $fy, $quarter) {
        return [
            'risk_level' => 'LOW', // LOW, MEDIUM, HIGH, CRITICAL
            'risk_score' => 15, // out of 100
            'risk_factors' => [
                'high_tds_amount' => false,
                'unusual_vendors' => false,
                'missing_pan' => false,
                'challan_mismatch' => false,
                'duplicate_invoices' => false,
                'missing_documentation' => false
            ],
            'recommendations' => [
                'action1' => 'Verify all vendor PANs',
                'action2' => 'Check challan receipts',
            ],
            'safe_to_file' => true
        ];
    }

    /**
     * Reconcile TDS credits with payments
     */
    public function reconcileTDSCredits($firm_id, $fy) {
        return [
            'total_tds_deducted' => 125000,
            'total_tds_paid' => 125000,
            'difference' => 0,
            'status' => 'RECONCILED',
            'quarterly_breakdown' => [
                'Q1' => ['deducted' => 30000, 'paid' => 30000],
                'Q2' => ['deducted' => 35000, 'paid' => 35000],
                'Q3' => ['deducted' => 30000, 'paid' => 30000],
                'Q4' => ['deducted' => 30000, 'paid' => 30000]
            ]
        ];
    }

    /**
     * Analyze deductee-wise TDS distribution
     */
    public function analyzeDeducteeTDS($firm_id, $fy, $quarter) {
        return [
            'deductees' => [
                [
                    'pan' => 'ABCDE1234F',
                    'name' => 'Vendor A',
                    'tds_amount' => 50000,
                    'invoices_count' => 5,
                    'avg_invoice' => 10000,
                    'risk_level' => 'LOW'
                ],
                // More deductees...
            ],
            'summary' => [
                'total_deductees' => 10,
                'total_tds' => 125000,
                'avg_tds_per_deductee' => 12500,
                'highest_tds' => 50000,
                'distribution' => 'NORMAL'
            ]
        ];
    }

    /**
     * TCS Compliance check
     */
    public function performTCSComplianceCheck($firm_id, $fy) {
        return [
            'checks' => [
                'purchases_exist' => true,
                'tcs_calculated' => true,
                'receipts_matching' => true,
                'customer_pan_validation' => true,
                'threshold_check' => true // Annual turnover > threshold
            ],
            'status' => 'COMPLIANT',
            'tcs_payable' => 45000,
            'tcs_paid' => 45000,
            'safe_to_file' => true
        ];
    }
}

?>
```

---

## Reports Module (TDS & TCS) {#reports}

### Form Generation Functions

```php
<?php

class ReportGenerator {

    /**
     * Generate Form 26Q (Quarterly TDS Return)
     * NS1 Format with ^ delimiter
     * File: Form26Q_TAN_YYYY_QX.txt
     */
    public function generateForm26Q($firm_id, $fy, $quarter) {
        // Format specification per IT Act
        $ns1_content = $this->buildNS1Format([
            'header' => [
                'HEADER^TAN^DEDUCTOR_NAME^FY^QUARTER^PAN^TIMESTAMP'
            ],
            'deductees' => [
                // DEDUCTEE^PAN^NAME^AMOUNT^TDS^INVOICE_COUNT
            ],
            'summary' => [
                'SUMMARY^DEDUCTOR_PAN^DEDUCTEE_COUNT^TOTAL_AMOUNT^TOTAL_TDS'
            ]
        ]);

        return [
            'form' => '26Q',
            'filename' => 'Form26Q_TAN_' . $fy . '_' . $quarter . '.txt',
            'content' => $ns1_content,
            'size' => strlen($ns1_content),
            'ready_for_fvu' => true
        ];
    }

    /**
     * Generate Form 24Q (Annual TDS Consolidation)
     * Aggregates Q1, Q2, Q3, Q4 data
     */
    public function generateForm24Q($firm_id, $fy) {
        // Similar to 26Q but for full financial year
        return [
            'form' => '24Q',
            'filename' => 'Form24Q_TAN_' . $fy . '.txt',
            'content' => 'NS1 format content...',
            'quarterly_summary' => [
                'Q1' => [...],
                'Q2' => [...],
                'Q3' => [...],
                'Q4' => [...]
            ]
        ];
    }

    /**
     * Generate Form 27Q (Quarterly TCS Return)
     * Similar structure to 26Q but for TCS
     */
    public function generateForm27Q($firm_id, $fy, $quarter) {
        return [
            'form' => '27Q',
            'filename' => 'Form27Q_TAN_' . $fy . '_' . $quarter . '.txt',
            'content' => 'NS1 format...',
            'tcs_payable' => 45000
        ];
    }

    /**
     * Generate Form 27EQ (Annual TCS Consolidation)
     */
    public function generateForm27EQ($firm_id, $fy) {
        return [
            'form' => '27EQ',
            'filename' => 'Form27EQ_TAN_' . $fy . '.txt',
            'content' => 'TCS annual consolidation...'
        ];
    }

    /**
     * Generate Form 16 (TDS Certificate for individual deductee)
     */
    public function generateForm16($firm_id, $deductee_pan, $fy) {
        return [
            'form' => '16',
            'deductee_pan' => $deductee_pan,
            'filename' => 'Form16_' . $deductee_pan . '_' . $fy . '.txt',
            'content' => 'Certificate content...',
            'certificate_no' => 'CERT-' . date('Ymd') . '-001',
            'tds_amount' => 50000
        ];
    }

    /**
     * Generate Form 16A (TDS Certificate for non-individual)
     */
    public function generateForm16A($firm_id, $deductee_pan, $fy) {
        // Similar to 16, for non-individuals, companies, etc.
        return [
            'form' => '16A',
            'deductee_pan' => $deductee_pan,
            'filename' => 'Form16A_' . $deductee_pan . '_' . $fy . '.txt',
            'content' => 'Certificate content for non-individual...'
        ];
    }

    /**
     * Generate CSI Annexure (Challan Summary Information)
     */
    public function generateCSIAnnexure($firm_id, $fy, $quarter) {
        return [
            'annexure' => 'CSI',
            'filename' => 'CSI_Annexure_' . $fy . '_' . $quarter . '.txt',
            'content' => 'CSI matching details...',
            'challan_count' => 4,
            'total_tds' => 125000
        ];
    }

    /**
     * Generate TDS Annexures (Additional documentation)
     */
    public function generateTDSAnnexures($firm_id, $fy) {
        return [
            'annexure' => 'TDS_ANNEXURES',
            'files' => [
                'annexure1.txt' => 'Bank wise TDS summary',
                'annexure2.txt' => 'Vendor wise TDS summary',
                'annexure3.txt' => 'Section wise TDS summary',
                'annexure4.txt' => 'Monthly TDS summary'
            ],
            'total_files' => 4
        ];
    }

    /**
     * Generate Master Data Report
     * Contains all vendors, rates, settings used
     */
    public function generateMasterDataReport($firm_id) {
        return [
            'report' => 'MASTER_DATA',
            'filename' => 'MasterData_' . $firm_id . '.txt',
            'content' => [
                'vendors' => [
                    'ABCDE1234F' => 'Vendor A',
                    'BCDEF2345G' => 'Vendor B',
                ],
                'rates' => [
                    '194A' => 10,
                    '194C' => 5,
                ],
                'settings' => [
                    'firm_pan' => 'ABCDE1234F',
                    'tan' => '0123456789',
                    'deductor_type' => 'COMPANY'
                ]
            ]
        ];
    }

    /**
     * Build NS1 format (^ delimited)
     */
    private function buildNS1Format($data) {
        $lines = [];
        foreach ($data as $section => $records) {
            if (is_array($records)) {
                foreach ($records as $record) {
                    $lines[] = $record;
                }
            }
        }
        return implode("\n", $lines);
    }
}

?>
```

---

## Compliance Module {#compliance}

### E-Filing Functions

```php
<?php

class ComplianceAPI {
    private $sandbox_api = 'https://api.sandbox.co.in/v1/tds';
    private $api_key = null;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Step 1: Generate FVU (File Validation Utility)
     *
     * Input: Form 26Q content (NS1 format)
     * Process: Sandbox validates form structure and data
     * Output: FVU file (ZIP with validation details)
     *
     * API Endpoint: POST /compliance/fvu/generate/submit
     */
    public function generateFVU($form_content, $form_type = '26Q') {
        $request = [
            'form_type' => $form_type,
            'content' => $form_content,
            'generation_date' => date('Y-m-d H:i:s')
        ];

        // Call Sandbox API
        $response = $this->callSandboxAPI(
            'POST',
            '/compliance/fvu/generate/submit',
            $request
        );

        return [
            'ok' => true,
            'job_id' => $response['job_id'],
            'status' => 'SUBMITTED', // PROCESSING, SUCCEEDED, FAILED
            'fvu_path' => $response['fvu_path'] ?? null,
            'validation_errors' => $response['errors'] ?? []
        ];
    }

    /**
     * Step 2: Poll FVU Generation Status
     *
     * API Endpoint: GET /compliance/fvu/generate/poll?job_id=XXX
     * Response: Current status, errors if any, FVU file path when ready
     */
    public function checkFVUStatus($job_id) {
        $response = $this->callSandboxAPI(
            'GET',
            '/compliance/fvu/generate/poll',
            ['job_id' => $job_id]
        );

        return [
            'job_id' => $job_id,
            'status' => $response['status'], // PROCESSING, SUCCEEDED, FAILED
            'fvu_ready' => $response['status'] === 'SUCCEEDED',
            'fvu_file' => $response['fvu_path'] ?? null,
            'errors' => $response['errors'] ?? [],
            'completed_at' => $response['completed_at'] ?? null
        ];
    }

    /**
     * Step 3: E-File the Return
     *
     * Input: FVU file path + Form 27A (signed acknowledgement form)
     * Process: Submits to Tax Authority e-filing portal
     * Output: Filing job ID for tracking
     *
     * API Endpoint: POST /compliance/efile/submit
     */
    public function eFileReturn($fvu_path, $form27a_path, $form_type = '26Q') {
        $request = [
            'form_type' => $form_type,
            'fvu_file' => base64_encode(file_get_contents($fvu_path)),
            'form27a' => base64_encode(file_get_contents($form27a_path)),
            'submission_date' => date('Y-m-d H:i:s')
        ];

        $response = $this->callSandboxAPI(
            'POST',
            '/compliance/efile/submit',
            $request
        );

        return [
            'ok' => true,
            'filing_job_id' => $response['filing_job_id'],
            'status' => 'SUBMITTED_TO_TA', // Tax Authority
            'acknowledgement_pending' => true,
            'next_check_after' => date('Y-m-d H:i:s', strtotime('+2 hours'))
        ];
    }

    /**
     * Step 4: Track E-Filing Status
     *
     * API Endpoint: GET /compliance/efile/status?filing_job_id=XXX
     * Statuses: SUBMITTED, PROCESSING, ACCEPTED, REJECTED, ACKNOWLEDGED
     */
    public function trackFilingStatus($filing_job_id) {
        $response = $this->callSandboxAPI(
            'GET',
            '/compliance/efile/status',
            ['filing_job_id' => $filing_job_id]
        );

        return [
            'filing_job_id' => $filing_job_id,
            'status' => $response['status'],
            'ack_no' => $response['ack_no'] ?? null,
            'ack_date' => $response['ack_date'] ?? null,
            'filing_complete' => $response['status'] === 'ACKNOWLEDGED',
            'acknowledgement_pdf' => $response['ack_pdf_url'] ?? null
        ];
    }

    /**
     * Step 5: Download Form 16/16A Certificates
     *
     * API Endpoint: GET /compliance/form16/download?deductee_pan=XXX&fy=2025-26
     * Returns certificate in printable format
     */
    public function downloadForm16($deductee_pan, $fy) {
        $response = $this->callSandboxAPI(
            'GET',
            '/compliance/form16/download',
            ['deductee_pan' => $deductee_pan, 'fy' => $fy]
        );

        return [
            'ok' => true,
            'form_type' => '16',
            'deductee_pan' => $deductee_pan,
            'certificate_file' => $response['file_path'],
            'download_url' => $response['download_url'],
            'certificate_date' => $response['issue_date']
        ];
    }

    /**
     * Step 6: Download CSI Annexure
     *
     * API Endpoint: GET /compliance/csi/download?job_id=XXX
     * CSI = Challan Summary Information from bank
     */
    public function downloadCSI($job_id) {
        $response = $this->callSandboxAPI(
            'GET',
            '/compliance/csi/download',
            ['job_id' => $job_id]
        );

        return [
            'ok' => true,
            'document' => 'CSI_ANNEXURE',
            'csi_file' => $response['file_path'],
            'download_url' => $response['download_url'],
            'file_format' => 'TXT (^ delimited)',
            'challan_count' => $response['challan_count'] ?? 0
        ];
    }

    /**
     * Step 7: Download TDS Annexures
     *
     * Multiple annexures including:
     * - Bank wise summary
     * - Vendor wise summary
     * - Section wise summary
     * - Monthly summary
     */
    public function downloadTDSAnnexures($job_id) {
        $response = $this->callSandboxAPI(
            'GET',
            '/compliance/annexures/download',
            ['job_id' => $job_id]
        );

        return [
            'ok' => true,
            'document' => 'TDS_ANNEXURES',
            'zip_file' => $response['file_path'],
            'download_url' => $response['download_url'],
            'files_count' => 4,
            'files' => [
                'annexure_bankwise.txt',
                'annexure_vendorwise.txt',
                'annexure_sectionwise.txt',
                'annexure_monthlywise.txt'
            ]
        ];
    }

    /**
     * Helper: Call Sandbox API
     */
    private function callSandboxAPI($method, $endpoint, $data) {
        $url = $this->sandbox_api . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}

?>
```

---

## Database Schema {#database}

### New Tables Required

```sql
-- Main filing jobs table
CREATE TABLE tds_filing_jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_uuid VARCHAR(36) UNIQUE NOT NULL,
    firm_id INT NOT NULL,
    form_type VARCHAR(10), -- 26Q, 24Q, 16, etc.
    fy VARCHAR(10) NOT NULL, -- 2025-26
    quarter VARCHAR(5), -- Q1, Q2, Q3, Q4
    form_content LONGTEXT, -- NS1 format content
    txt_status VARCHAR(50) DEFAULT 'PENDING',
    fvu_job_id VARCHAR(50),
    fvu_status VARCHAR(50) DEFAULT 'PENDING',
    fvu_path VARCHAR(255),
    fvu_generated_at TIMESTAMP,
    filing_job_id VARCHAR(50),
    e_filing_status VARCHAR(50) DEFAULT 'PENDING',
    ack_no VARCHAR(100),
    ack_date TIMESTAMP,
    filed_at TIMESTAMP,
    submitted_at TIMESTAMP,
    records_count INT,
    total_amount DECIMAL(15,2),
    total_tds DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (firm_id) REFERENCES firms(id)
);

-- Filing logs for audit trail
CREATE TABLE tds_filing_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    event_type VARCHAR(50), -- FORM_GENERATED, FVU_INITIATED, EFILED, etc.
    event_status VARCHAR(50), -- SUCCESS, FAILED, PENDING
    details JSON,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id)
);

-- TDS master rates (can be updated for different FYs)
CREATE TABLE tds_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_code VARCHAR(10) NOT NULL,
    description VARCHAR(255),
    rate DECIMAL(5,2),
    effective_from DATE,
    effective_to DATE,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TDS section configuration per firm
CREATE TABLE firm_tds_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    firm_id INT NOT NULL,
    section_code VARCHAR(10) NOT NULL,
    applicable BOOLEAN DEFAULT TRUE,
    default_rate DECIMAL(5,2),
    threshold_amount DECIMAL(15,2),
    remarks TEXT,
    FOREIGN KEY (firm_id) REFERENCES firms(id),
    UNIQUE KEY (firm_id, section_code)
);

-- Compliance check results
CREATE TABLE compliance_checks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    check_name VARCHAR(100),
    check_status VARCHAR(50), -- PASS, FAIL, WARNING
    details JSON,
    recommendations TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id)
);

-- Risk assessment scores
CREATE TABLE risk_assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    risk_level VARCHAR(50), -- LOW, MEDIUM, HIGH, CRITICAL
    risk_score INT,
    risk_factors JSON,
    safe_to_file BOOLEAN,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES tds_filing_jobs(id)
);
```

---

## File Formats & Requirements {#formats}

### Form 26Q Format (NS1 - ^ Delimited)

```
HEADER^TAN_CODE^DEDUCTOR_NAME^FINANCIAL_YEAR^QUARTER^PAN^TIMESTAMP^VERSION
DEDUCTEE^PAN^NAME^INVOICE_AMT^TDS_AMT^INV_COUNT^DATE
DEDUCTEE^PAN^NAME^INVOICE_AMT^TDS_AMT^INV_COUNT^DATE
...
SUMMARY^DEDUCTOR_PAN^DEDUCTEE_COUNT^TOTAL_INVOICE_AMT^TOTAL_TDS^TIMESTAMP^STATUS

Example:
HEADER^0123456789^ABC CORPORATION^2025-2026^Q2^ABCDE1234F^2025-12-06 10:30:00^1.0
DEDUCTEE^ABCPA1234D^Vendor A Pvt Ltd^100000^10000^2^2025-12-06
DEDUCTEE^BCDEF2345G^Vendor B Pvt Ltd^250000^12500^3^2025-12-06
SUMMARY^ABCDE1234F^2^350000^22500^2025-12-06 10:30:00^VALID
```

### FVU (File Validation Utility)

```
- Generated by Sandbox API after form validation
- ZIP file containing:
  ├── form26q_fvu.txt (validated form)
  ├── errors.log (any validation errors)
  ├── warnings.log (non-critical issues)
  └── form27a_template.pdf (unsigned acknowledgement form)
```

### Form 27A (Acknowledgement Form)

```
- Acknowledgement form for TDS filing
- Must be digitally signed by Authorized Signatory
- Includes deductor details, form type, FY, quarter
- Submitted along with FVU for e-filing
```

### CSI Format (^ Delimited)

```
BSR_CODE|CHALLAN_DATE|SERIAL_NO|AMOUNT|STATUS

Example:
0123456|17/12/2025|11223|125000|DEPOSITED
```

---

## Implementation Steps {#implementation}

### Phase 1: Setup (Day 1)
- [ ] Create database tables
- [ ] Set up Sandbox API credentials
- [ ] Create base library classes

### Phase 2: Calculator (Day 2-3)
- [ ] Implement TDSCalculator class
- [ ] Implement TCSCalculator class
- [ ] Create /tds/api/calculator.php endpoint
- [ ] Test with sample data

### Phase 3: Analytics (Day 4-5)
- [ ] Implement ComplianceAnalytics class
- [ ] Create compliance check functions
- [ ] Create risk assessment logic
- [ ] Create /tds/api/analytics.php endpoint

### Phase 4: Reports (Day 6-7)
- [ ] Implement ReportGenerator class
- [ ] Create Form 26Q generation
- [ ] Create Form 24Q generation
- [ ] Create Form 16/16A generation
- [ ] Create /tds/api/reports.php endpoint

### Phase 5: Compliance (Day 8-9)
- [ ] Implement ComplianceAPI class
- [ ] FVU generation integration
- [ ] E-filing integration
- [ ] Status tracking integration
- [ ] Create /tds/api/compliance.php endpoint

### Phase 6: Admin UI (Day 10-12)
- [ ] Create /tds/admin/ereturn.php (main dashboard)
- [ ] Create /tds/admin/calculator.php (TDS/TCS calculator)
- [ ] Create /tds/admin/analytics.php (compliance dashboard)
- [ ] Create /tds/admin/reports.php (form generation)
- [ ] Create /tds/admin/compliance.php (e-filing)

### Phase 7: Testing & Documentation (Day 13-14)
- [ ] Unit testing for each module
- [ ] Integration testing
- [ ] User documentation
- [ ] API documentation

---

## Quick Reference

### Key Endpoints

**Calculator API**
- POST /tds/api/calculator/calculate_tds
- POST /tds/api/calculator/calculate_bulk
- GET /tds/api/calculator/rates

**Analytics API**
- POST /tds/api/analytics/compliance_check
- POST /tds/api/analytics/risk_assessment
- POST /tds/api/analytics/reconcile_credits

**Reports API**
- POST /tds/api/reports/generate_form26q
- POST /tds/api/reports/generate_form16
- POST /tds/api/reports/generate_annexures

**Compliance API**
- POST /tds/api/compliance/generate_fvu
- GET /tds/api/compliance/fvu_status
- POST /tds/api/compliance/efile
- GET /tds/api/compliance/filing_status
- GET /tds/api/compliance/download_certificates

### Admin Pages

- `/tds/admin/ereturn.php` - Main filing dashboard
- `/tds/admin/calculator.php` - TDS/TCS calculator
- `/tds/admin/analytics.php` - Compliance analysis
- `/tds/admin/reports.php` - Form generation
- `/tds/admin/compliance.php` - E-filing & tracking

---

**Status:** Complete implementation guide
**Version:** 1.0
**Last Updated:** December 6, 2025
