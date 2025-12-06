<?php
/**
 * TDS & TCS Calculator API
 * Handles auto-calculation of TDS/TCS based on invoice amounts and rates
 *
 * @author TDS AutoFile
 * @version 1.0
 */

class CalculatorAPI {
    private $db;
    private $tds_rates = [];
    private $tcs_rates = [];

    public function __construct($pdo) {
        $this->db = $pdo;
        $this->loadTDSRates();
        $this->loadTCSRates();
    }

    /**
     * Load TDS rates from database or use defaults
     */
    private function loadTDSRates() {
        $this->tds_rates = [
            '194A' => ['rate' => 10, 'desc' => 'Rent/License fees', 'category' => 'TDS'],
            '194C' => ['rate' => 5, 'desc' => 'Contractor/Sub-contractor', 'category' => 'TDS'],
            '194D' => ['rate' => 10, 'desc' => 'Insurance Commission', 'category' => 'TDS'],
            '194E' => ['rate' => 20, 'desc' => 'Mutual Fund', 'category' => 'TDS'],
            '194F' => ['rate' => 20, 'desc' => 'Dividend', 'category' => 'TDS'],
            '194G' => ['rate' => 10, 'desc' => 'Commission/Brokerage', 'category' => 'TDS'],
            '194H' => ['rate' => 5, 'desc' => 'Commission/Remuneration', 'category' => 'TDS'],
            '194I' => ['rate' => 10, 'desc' => 'Search/Fishing vessels', 'category' => 'TDS'],
            '194J' => ['rate' => 10, 'desc' => 'Fee for professional services', 'category' => 'TDS'],
            '194K' => ['rate' => 10, 'desc' => 'Brokerage/Commission', 'category' => 'TDS'],
            '194LA' => ['rate' => 10, 'desc' => 'Life Insurance Premium', 'category' => 'TDS'],
            '194LB' => ['rate' => 10, 'desc' => 'Life Insurance Premium (non-individual)', 'category' => 'TDS'],
        ];
    }

    /**
     * Load TCS rates from database or use defaults
     */
    private function loadTCSRates() {
        $this->tcs_rates = [
            '206C' => ['rate' => 1, 'desc' => 'Sale of goods (Motor vehicle)', 'category' => 'TCS'],
            '206C-1H' => ['rate' => 0.1, 'desc' => 'Sale of goods (other)', 'category' => 'TCS'],
            '194C_TCS' => ['rate' => 0.1, 'desc' => 'Collection/Receipt from deductee', 'category' => 'TCS'],
        ];
    }

    /**
     * Calculate TDS for single invoice
     *
     * @param float $base_amount Invoice amount
     * @param string $section_code TDS section (194A, 194C, etc.)
     * @param float|null $custom_rate Override default rate
     * @return array Calculation result
     */
    public function calculateInvoiceTDS($base_amount, $section_code, $custom_rate = null) {
        if (!isset($this->tds_rates[$section_code])) {
            throw new Exception("Unknown TDS section code: $section_code");
        }

        $rate = $custom_rate ?? $this->tds_rates[$section_code]['rate'];
        $tds_amount = ($base_amount * $rate) / 100;

        return [
            'status' => 'success',
            'base_amount' => round($base_amount, 2),
            'tds_rate' => $rate,
            'tds_amount' => round($tds_amount, 2),
            'net_amount' => round($base_amount - $tds_amount, 2),
            'section_code' => $section_code,
            'section_description' => $this->tds_rates[$section_code]['desc']
        ];
    }

    /**
     * Calculate TCS for single transaction
     *
     * @param float $sale_amount Sale/Collection amount
     * @param string $section_code TCS section (206C, etc.)
     * @param float|null $custom_rate Override default rate
     * @return array Calculation result
     */
    public function calculateTransactionTCS($sale_amount, $section_code = '206C-1H', $custom_rate = null) {
        if (!isset($this->tcs_rates[$section_code])) {
            throw new Exception("Unknown TCS section code: $section_code");
        }

        $rate = $custom_rate ?? $this->tcs_rates[$section_code]['rate'];

        // TCS threshold check
        $threshold = 30000; // TCS applicable only if amount > 30,000
        if ($sale_amount <= $threshold) {
            return [
                'status' => 'success',
                'sale_amount' => round($sale_amount, 2),
                'tcs_applicable' => false,
                'reason' => "Amount below threshold (₹$threshold)",
                'tcs_amount' => 0,
                'amount_payable' => round($sale_amount, 2)
            ];
        }

        $tcs_amount = ($sale_amount * $rate) / 100;

        return [
            'status' => 'success',
            'sale_amount' => round($sale_amount, 2),
            'tcs_applicable' => true,
            'tcs_rate' => $rate,
            'tcs_amount' => round($tcs_amount, 2),
            'amount_payable' => round($sale_amount - $tcs_amount, 2),
            'section_code' => $section_code,
            'section_description' => $this->tcs_rates[$section_code]['desc']
        ];
    }

    /**
     * Bulk calculate TDS for multiple invoices
     *
     * @param array $invoices Array of invoice data
     * @return array Results with summary
     */
    public function calculateBulkTDS($invoices) {
        if (empty($invoices)) {
            return ['status' => 'error', 'message' => 'No invoices provided'];
        }

        $results = [];
        $summary = [
            'total_base_amount' => 0,
            'total_tds' => 0,
            'invoice_count' => count($invoices),
            'by_section' => []
        ];

        foreach ($invoices as $invoice) {
            try {
                $calc = $this->calculateInvoiceTDS(
                    $invoice['base_amount'],
                    $invoice['section_code'],
                    $invoice['tds_rate'] ?? null
                );
                $results[] = $calc;

                // Summary calculations
                $summary['total_base_amount'] += $calc['base_amount'];
                $summary['total_tds'] += $calc['tds_amount'];

                // Group by section
                $section = $invoice['section_code'];
                if (!isset($summary['by_section'][$section])) {
                    $summary['by_section'][$section] = [
                        'count' => 0,
                        'amount' => 0,
                        'tds' => 0
                    ];
                }
                $summary['by_section'][$section]['count']++;
                $summary['by_section'][$section]['amount'] += $calc['base_amount'];
                $summary['by_section'][$section]['tds'] += $calc['tds_amount'];

            } catch (Exception $e) {
                $results[] = [
                    'status' => 'error',
                    'invoice_no' => $invoice['invoice_no'] ?? 'N/A',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Round summary totals
        $summary['total_base_amount'] = round($summary['total_base_amount'], 2);
        $summary['total_tds'] = round($summary['total_tds'], 2);
        $summary['effective_rate'] = $summary['total_base_amount'] > 0
            ? round(($summary['total_tds'] / $summary['total_base_amount']) * 100, 2)
            : 0;

        return [
            'status' => 'success',
            'calculations' => $results,
            'summary' => $summary
        ];
    }

    /**
     * Bulk calculate TCS for multiple transactions
     */
    public function calculateBulkTCS($transactions) {
        if (empty($transactions)) {
            return ['status' => 'error', 'message' => 'No transactions provided'];
        }

        $results = [];
        $summary = [
            'total_sale_amount' => 0,
            'total_tcs' => 0,
            'transaction_count' => count($transactions),
            'tcs_applicable_count' => 0,
            'by_section' => []
        ];

        foreach ($transactions as $txn) {
            try {
                $calc = $this->calculateTransactionTCS(
                    $txn['sale_amount'],
                    $txn['section_code'] ?? '206C-1H',
                    $txn['tcs_rate'] ?? null
                );
                $results[] = $calc;

                $summary['total_sale_amount'] += $calc['sale_amount'];
                $summary['total_tcs'] += $calc['tcs_amount'];
                if ($calc['tcs_applicable']) {
                    $summary['tcs_applicable_count']++;
                }

            } catch (Exception $e) {
                $results[] = [
                    'status' => 'error',
                    'transaction_id' => $txn['id'] ?? 'N/A',
                    'error' => $e->getMessage()
                ];
            }
        }

        $summary['total_sale_amount'] = round($summary['total_sale_amount'], 2);
        $summary['total_tcs'] = round($summary['total_tcs'], 2);
        $summary['effective_rate'] = $summary['total_sale_amount'] > 0
            ? round(($summary['total_tcs'] / $summary['total_sale_amount']) * 100, 4)
            : 0;

        return [
            'status' => 'success',
            'calculations' => $results,
            'summary' => $summary
        ];
    }

    /**
     * Validate TDS calculation
     * Compare calculated TDS with provided TDS amount
     *
     * @param float $base_amount Invoice amount
     * @param string $section_code TDS section
     * @param float $provided_tds TDS amount to validate
     * @param float $tolerance Allowed difference (default 0.01)
     * @return array Validation result
     */
    public function validateTDSCalculation($base_amount, $section_code, $provided_tds, $tolerance = 0.01) {
        try {
            $calc = $this->calculateInvoiceTDS($base_amount, $section_code);
            $difference = abs($calc['tds_amount'] - $provided_tds);
            $is_valid = $difference <= $tolerance;

            return [
                'status' => 'success',
                'valid' => $is_valid,
                'calculated_tds' => $calc['tds_amount'],
                'provided_tds' => round($provided_tds, 2),
                'difference' => round($difference, 2),
                'tolerance' => $tolerance,
                'message' => $is_valid ? 'TDS calculation is correct' : 'TDS calculation mismatch',
                'match_percentage' => round((1 - ($difference / max($calc['tds_amount'], $provided_tds, 0.01))) * 100, 2)
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get TDS rate for a section
     */
    public function getTDSRate($section_code) {
        if (!isset($this->tds_rates[$section_code])) {
            return null;
        }

        return [
            'section_code' => $section_code,
            'rate' => $this->tds_rates[$section_code]['rate'],
            'description' => $this->tds_rates[$section_code]['desc']
        ];
    }

    /**
     * Get TCS rate for a section
     */
    public function getTCSRate($section_code) {
        if (!isset($this->tcs_rates[$section_code])) {
            return null;
        }

        return [
            'section_code' => $section_code,
            'rate' => $this->tcs_rates[$section_code]['rate'],
            'description' => $this->tcs_rates[$section_code]['desc']
        ];
    }

    /**
     * Get all available TDS section codes and rates
     */
    public function getAllTDSRates() {
        $result = [];
        foreach ($this->tds_rates as $code => $details) {
            $result[] = [
                'section_code' => $code,
                'rate' => $details['rate'],
                'description' => $details['desc'],
                'category' => 'TDS'
            ];
        }
        return $result;
    }

    /**
     * Get all available TCS section codes and rates
     */
    public function getAllTCSRates() {
        $result = [];
        foreach ($this->tcs_rates as $code => $details) {
            $result[] = [
                'section_code' => $code,
                'rate' => $details['rate'],
                'description' => $details['desc'],
                'category' => 'TCS'
            ];
        }
        return $result;
    }

    /**
     * Special calculation: Contractor TDS (194C)
     * Has threshold - TDS only applicable if annual contract > 50,000
     */
    public function calculateContractorTDS($contract_value, $rate = 5) {
        $threshold = 50000; // Only TDS if contract > 50,000

        if ($contract_value <= $threshold) {
            return [
                'status' => 'success',
                'contract_value' => round($contract_value, 2),
                'tds_applicable' => false,
                'reason' => "Contract value below threshold (₹$threshold)",
                'tds_amount' => 0,
                'net_amount' => round($contract_value, 2)
            ];
        }

        $tds_amount = ($contract_value * $rate) / 100;

        return [
            'status' => 'success',
            'contract_value' => round($contract_value, 2),
            'tds_applicable' => true,
            'tds_rate' => $rate,
            'tds_amount' => round($tds_amount, 2),
            'net_amount' => round($contract_value - $tds_amount, 2),
            'section_code' => '194C'
        ];
    }

    /**
     * Special calculation: Salary TDS
     * Special handling for salary deductions
     */
    public function calculateSalaryTDS($gross_salary, $year = 2025) {
        // Simplified calculation - should integrate with actual salary TDS rules
        $tax_slabs = [
            ['limit' => 250000, 'rate' => 0],
            ['limit' => 500000, 'rate' => 5],
            ['limit' => 750000, 'rate' => 10],
            ['limit' => 1000000, 'rate' => 15],
            ['limit' => 1250000, 'rate' => 20],
            ['limit' => 1500000, 'rate' => 25],
            ['limit' => PHP_FLOAT_MAX, 'rate' => 30]
        ];

        $tax = 0;
        $prev_limit = 0;

        foreach ($tax_slabs as $slab) {
            if ($gross_salary <= $slab['limit']) {
                $tax += (($gross_salary - $prev_limit) * $slab['rate']) / 100;
                break;
            } else {
                $tax += (($slab['limit'] - $prev_limit) * $slab['rate']) / 100;
                $prev_limit = $slab['limit'];
            }
        }

        // Add surcharge and cess if applicable
        $surcharge = 0;
        if ($gross_salary > 5000000) {
            $surcharge = ($tax * 25) / 100;
        } elseif ($gross_salary > 1000000) {
            $surcharge = ($tax * 15) / 100;
        }

        $cess = ($tax + $surcharge) * 0.04;
        $total_tax = $tax + $surcharge + $cess;

        return [
            'status' => 'success',
            'gross_salary' => round($gross_salary, 2),
            'base_tax' => round($tax, 2),
            'surcharge' => round($surcharge, 2),
            'cess' => round($cess, 2),
            'total_tax' => round($total_tax, 2),
            'net_salary' => round($gross_salary - $total_tax, 2),
            'effective_rate' => round(($total_tax / $gross_salary) * 100, 2)
        ];
    }

    /**
     * Recalculate all invoices in database for a given FY/Quarter
     */
    public function recalculateQuarterTDS($firm_id, $fy, $quarter) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM invoices
                 WHERE firm_id = ? AND fy = ? AND quarter = ?'
            );
            $stmt->execute([$firm_id, $fy, $quarter]);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $updated = 0;
            $errors = [];

            foreach ($invoices as $invoice) {
                try {
                    $calc = $this->calculateInvoiceTDS(
                        $invoice['base_amount'],
                        $invoice['section_code']
                    );

                    // Update invoice with recalculated TDS
                    $updateStmt = $this->db->prepare(
                        'UPDATE invoices SET total_tds = ? WHERE id = ?'
                    );
                    $updateStmt->execute([$calc['tds_amount'], $invoice['id']]);
                    $updated++;

                } catch (Exception $e) {
                    $errors[] = [
                        'invoice_id' => $invoice['id'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'status' => 'success',
                'recalculated' => $updated,
                'total' => count($invoices),
                'errors' => $errors,
                'message' => "Recalculated $updated invoices"
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

?>
