<?php
/**
 * TDS & TCS Reports API
 * Form generation (26Q, 24Q, 16, 27Q, 27EQ, etc.) in NS1 format
 *
 * @author TDS AutoFile
 * @version 1.0
 */

class ReportsAPI {
    private $db;
    private $ns1_delimiter = '^';
    private $upload_path = '/uploads/reports';

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Generate Form 26Q (Quarterly TDS Return)
     * NS1 Format: ^ delimited
     *
     * @param int $firm_id Firm ID
     * @param string $fy Financial year (2025-26)
     * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
     * @return array Generated form data
     */
    public function generateForm26Q($firm_id, $fy, $quarter) {
        try {
            // Get firm details
            $firmStmt = $this->db->prepare('SELECT * FROM firms WHERE id = ?');
            $firmStmt->execute([$firm_id]);
            $firm = $firmStmt->fetch(PDO::FETCH_ASSOC);

            if (!$firm) {
                throw new Exception("Firm not found");
            }

            // Get invoices for the quarter
            // Note: Include all invoices regardless of allocation status
            // Allocation is about matching to challans, not about form inclusion
            $invoiceStmt = $this->db->prepare(
                'SELECT i.*, v.name, v.pan FROM invoices i
                 JOIN vendors v ON i.vendor_id = v.id
                 WHERE i.firm_id = ? AND i.fy = ? AND i.quarter = ?
                 ORDER BY i.invoice_date'
            );
            $invoiceStmt->execute([$firm_id, $fy, $quarter]);
            $invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($invoices)) {
                throw new Exception("No invoices found for the selected quarter");
            }

            // Build NS1 content
            $lines = [];

            // Header record
            $lines[] = $this->buildForm26QHeader($firm, $fy, $quarter);

            // Aggregate by deductee (vendor)
            $deductees = [];
            foreach ($invoices as $invoice) {
                $pan = $invoice['pan'];
                if (!isset($deductees[$pan])) {
                    $deductees[$pan] = [
                        'pan' => $pan,
                        'name' => $invoice['name'],
                        'total_amount' => 0,
                        'total_tds' => 0,
                        'invoice_count' => 0,
                        'invoices' => []
                    ];
                }
                $deductees[$pan]['total_amount'] += (float)$invoice['base_amount'];
                $deductees[$pan]['total_tds'] += (float)$invoice['total_tds'];
                $deductees[$pan]['invoice_count']++;
                $deductees[$pan]['invoices'][] = $invoice;
            }

            // Deductee records
            foreach ($deductees as $deductee) {
                $lines[] = $this->buildForm26QDeducteeRecord($deductee);
            }

            // Summary record
            $total_amount = array_sum(array_column($deductees, 'total_amount'));
            $total_tds = array_sum(array_column($deductees, 'total_tds'));
            $lines[] = $this->buildForm26QSummary($firm, count($deductees), $total_amount, $total_tds);

            $content = implode("\n", $lines);
            $filename = 'Form26Q_' . strtoupper($firm['tan']) . '_' . str_replace('-', '', $fy) . '_' . $quarter . '.txt';

            return [
                'status' => 'success',
                'form' => '26Q',
                'fy' => $fy,
                'quarter' => $quarter,
                'filename' => $filename,
                'content' => $content,
                'size' => strlen($content),
                'deductees_count' => count($deductees),
                'total_amount' => round($total_amount, 2),
                'total_tds' => round($total_tds, 2),
                'invoices_count' => count($invoices),
                'format' => 'NS1 (^ delimited)',
                'ready_for_fvu' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Build Form 26Q header record
     */
    private function buildForm26QHeader($firm, $fy, $quarter) {
        $fy_formatted = $this->formatFY($fy);
        return implode($this->ns1_delimiter, [
            'HEADER',
            $firm['tan'] ?? '',
            $firm['deductor_name'] ?? '',
            $fy_formatted,
            $quarter,
            $firm['pan'] ?? '',
            date('Ymd'),
            '1.0'
        ]);
    }

    /**
     * Build Form 26Q deductee record
     */
    private function buildForm26QDeducteeRecord($deductee) {
        return implode($this->ns1_delimiter, [
            'DEDUCTEE',
            $deductee['pan'],
            substr($deductee['name'], 0, 80), // Limit name to 80 chars
            (int)$deductee['total_amount'],
            (int)$deductee['total_tds'],
            $deductee['invoice_count'],
            date('Ymd')
        ]);
    }

    /**
     * Build Form 26Q summary record
     */
    private function buildForm26QSummary($firm, $deductee_count, $total_amount, $total_tds) {
        return implode($this->ns1_delimiter, [
            'SUMMARY',
            $firm['pan'] ?? '',
            $deductee_count,
            (int)$total_amount,
            (int)$total_tds,
            date('Ymd'),
            'VALID'
        ]);
    }

    /**
     * Generate Form 24Q (Annual TDS Consolidation)
     * Aggregates all quarterly data into annual return
     */
    public function generateForm24Q($firm_id, $fy) {
        try {
            $firmStmt = $this->db->prepare('SELECT * FROM firms WHERE id = ?');
            $firmStmt->execute([$firm_id]);
            $firm = $firmStmt->fetch(PDO::FETCH_ASSOC);

            if (!$firm) {
                throw new Exception("Firm not found");
            }

            // Get all invoices for the FY
            // Note: Include all invoices regardless of allocation status
            // Allocation is about matching to challans, not about form inclusion
            $invoiceStmt = $this->db->prepare(
                'SELECT i.*, v.name, v.pan FROM invoices i
                 JOIN vendors v ON i.vendor_id = v.id
                 WHERE i.firm_id = ? AND i.fy = ?
                 ORDER BY i.quarter, i.invoice_date'
            );
            $invoiceStmt->execute([$firm_id, $fy]);
            $invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($invoices)) {
                throw new Exception("No invoices found for this FY");
            }

            $lines = [];

            // Header
            $lines[] = $this->buildForm24QHeader($firm, $fy);

            // Aggregate by deductee
            $deductees = [];
            $quarterly_summary = [];

            foreach ($invoices as $invoice) {
                $pan = $invoice['pan'];
                $quarter = $invoice['quarter'];

                if (!isset($deductees[$pan])) {
                    $deductees[$pan] = [
                        'pan' => $pan,
                        'name' => $invoice['name'],
                        'total_amount' => 0,
                        'total_tds' => 0,
                        'invoice_count' => 0
                    ];
                }

                $deductees[$pan]['total_amount'] += (float)$invoice['base_amount'];
                $deductees[$pan]['total_tds'] += (float)$invoice['total_tds'];
                $deductees[$pan]['invoice_count']++;

                // Quarterly tracking
                if (!isset($quarterly_summary[$quarter])) {
                    $quarterly_summary[$quarter] = [
                        'amount' => 0,
                        'tds' => 0,
                        'deductees' => 0
                    ];
                }
            }

            // Deductee records
            foreach ($deductees as $deductee) {
                $lines[] = $this->buildForm24QDeducteeRecord($deductee);
            }

            // Summary
            $total_amount = array_sum(array_column($deductees, 'total_amount'));
            $total_tds = array_sum(array_column($deductees, 'total_tds'));
            $lines[] = $this->buildForm24QSummary($firm, count($deductees), $total_amount, $total_tds);

            $content = implode("\n", $lines);
            $filename = 'Form24Q_' . strtoupper($firm['tan']) . '_' . str_replace('-', '', $fy) . '.txt';

            return [
                'status' => 'success',
                'form' => '24Q',
                'fy' => $fy,
                'filename' => $filename,
                'content' => $content,
                'size' => strlen($content),
                'deductees_count' => count($deductees),
                'total_amount' => round($total_amount, 2),
                'total_tds' => round($total_tds, 2),
                'invoices_count' => count($invoices),
                'format' => 'NS1 (^ delimited)',
                'ready_for_submission' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Build Form 24Q header
     */
    private function buildForm24QHeader($firm, $fy) {
        $fy_formatted = $this->formatFY($fy);
        return implode($this->ns1_delimiter, [
            'HEADER',
            $firm['tan'] ?? '',
            $firm['deductor_name'] ?? '',
            $fy_formatted,
            'ANNUAL',
            $firm['pan'] ?? '',
            date('Ymd'),
            '1.0'
        ]);
    }

    /**
     * Build Form 24Q deductee record
     */
    private function buildForm24QDeducteeRecord($deductee) {
        return implode($this->ns1_delimiter, [
            'DEDUCTEE',
            $deductee['pan'],
            substr($deductee['name'], 0, 80),
            (int)$deductee['total_amount'],
            (int)$deductee['total_tds'],
            $deductee['invoice_count'],
            date('Ymd')
        ]);
    }

    /**
     * Build Form 24Q summary
     */
    private function buildForm24QSummary($firm, $deductee_count, $total_amount, $total_tds) {
        return implode($this->ns1_delimiter, [
            'SUMMARY',
            $firm['pan'] ?? '',
            $deductee_count,
            (int)$total_amount,
            (int)$total_tds,
            date('Ymd'),
            'VALID'
        ]);
    }

    /**
     * Generate Form 16 (TDS Certificate for individual)
     */
    public function generateForm16($firm_id, $deductee_pan, $fy) {
        try {
            $firmStmt = $this->db->prepare('SELECT * FROM firms WHERE id = ?');
            $firmStmt->execute([$firm_id]);
            $firm = $firmStmt->fetch(PDO::FETCH_ASSOC);

            $invoiceStmt = $this->db->prepare(
                'SELECT i.*, v.name FROM invoices i
                 JOIN vendors v ON i.vendor_id = v.id
                 WHERE i.firm_id = ? AND v.pan = ? AND i.fy = ?
                 ORDER BY i.quarter, i.invoice_date'
            );
            $invoiceStmt->execute([$firm_id, $deductee_pan, $fy]);
            $invoices = $invoiceStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($invoices)) {
                throw new Exception("No invoices found for this deductee");
            }

            $first = $invoices[0];
            $total_amount = 0;
            $total_tds = 0;

            $lines = [];
            $lines[] = "FORM 16 - TDS CERTIFICATE";
            $lines[] = "=" . str_repeat("=", 40);
            $lines[] = "";
            $lines[] = "Certificate No.: " . $this->generateCertificateNo($firm_id, $deductee_pan, $fy);
            $lines[] = "Certificate Date: " . date('d-m-Y');
            $lines[] = "";
            $lines[] = "DEDUCTOR DETAILS:";
            $lines[] = "Name: " . $firm['deductor_name'];
            $lines[] = "PAN: " . $firm['pan'];
            $lines[] = "TAN: " . $firm['tan'];
            $lines[] = "";
            $lines[] = "DEDUCTEE DETAILS:";
            $lines[] = "PAN: " . $deductee_pan;
            $lines[] = "Name: " . $first['name'];
            $lines[] = "";
            $lines[] = "FINANCIAL YEAR: " . $fy;
            $lines[] = "";
            $lines[] = "TRANSACTION DETAILS:";
            $lines[] = str_repeat("-", 80);
            $lines[] = "Date       | Invoice No | Amount     | TDS        | Remarks";
            $lines[] = str_repeat("-", 80);

            $quarter_summary = [];
            foreach ($invoices as $inv) {
                $total_amount += (float)$inv['base_amount'];
                $total_tds += (float)$inv['total_tds'];

                $lines[] = sprintf(
                    "%-10s | %-10s | %10.2f | %10.2f | %s",
                    $inv['invoice_date'],
                    $inv['invoice_no'],
                    $inv['base_amount'],
                    $inv['total_tds'],
                    $inv['quarter']
                );

                if (!isset($quarter_summary[$inv['quarter']])) {
                    $quarter_summary[$inv['quarter']] = 0;
                }
                $quarter_summary[$inv['quarter']] += (float)$inv['total_tds'];
            }

            $lines[] = str_repeat("-", 80);
            $lines[] = "";
            $lines[] = "SUMMARY:";
            $lines[] = "Total Turnover: ₹" . number_format($total_amount, 2);
            $lines[] = "Total TDS Deducted: ₹" . number_format($total_tds, 2);
            $lines[] = "Average TDS Rate: " . round(($total_tds / max($total_amount, 1)) * 100, 2) . "%";
            $lines[] = "";
            $lines[] = "QUARTERLY BREAKUP:";
            foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
                $tds = $quarter_summary[$q] ?? 0;
                $lines[] = "$q: ₹" . number_format($tds, 2);
            }
            $lines[] = "";
            $lines[] = "Certified that above particulars are true and correct.";
            $lines[] = "Issued at: " . $firm['deductor_name'] . " on " . date('d-m-Y');
            $lines[] = "";
            $lines[] = "(Digital Signature Placeholder)";
            $lines[] = $firm['pan'] . " / Authorized Signatory";

            $content = implode("\n", $lines);
            $filename = 'Form16_' . $deductee_pan . '_' . str_replace('-', '', $fy) . '.txt';

            return [
                'status' => 'success',
                'form' => '16',
                'deductee_pan' => $deductee_pan,
                'deductee_name' => $first['name'],
                'fy' => $fy,
                'filename' => $filename,
                'content' => $content,
                'size' => strlen($content),
                'total_amount' => round($total_amount, 2),
                'total_tds' => round($total_tds, 2),
                'invoices_count' => count($invoices),
                'certificate_date' => date('Y-m-d'),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate Form 16A (TDS Certificate for non-individual)
     */
    public function generateForm16A($firm_id, $deductee_pan, $fy) {
        // Similar structure to Form 16 with minor differences for non-individuals
        $result = $this->generateForm16($firm_id, $deductee_pan, $fy);
        if ($result['status'] === 'success') {
            $result['form'] = '16A';
            $result['filename'] = str_replace('Form16_', 'Form16A_', $result['filename']);
            $result['content'] = str_replace('FORM 16 -', 'FORM 16A -', $result['content']);
            $result['content'] = str_replace('(Non-individual', 'for Non-Individual', $result['content']);
        }
        return $result;
    }

    /**
     * Generate CSI Annexure (Challan Summary Information)
     */
    public function generateCSIAnnexure($firm_id, $fy, $quarter) {
        try {
            $challanStmt = $this->db->prepare(
                'SELECT * FROM challans
                 WHERE firm_id = ? AND fy = ? AND quarter = ?
                 ORDER BY challan_date'
            );
            $challanStmt->execute([$firm_id, $fy, $quarter]);
            $challans = $challanStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($challans)) {
                throw new Exception("No challans found for this period");
            }

            $lines = [];
            $lines[] = "CSI ANNEXURE - CHALLAN SUMMARY INFORMATION";
            $lines[] = "=" . str_repeat("=", 60);
            $lines[] = "";
            $lines[] = "FY: $fy | Quarter: $quarter";
            $lines[] = "Generated on: " . date('d-m-Y H:i:s');
            $lines[] = "";
            $lines[] = str_repeat("-", 100);
            $lines[] = sprintf(
                "%-15s | %-12s | %-10s | %-15s | %-15s | %-15s",
                "Challan Date",
                "Serial No",
                "BSR Code",
                "Amount TDS",
                "Amount Int",
                "Amount Penalty"
            );
            $lines[] = str_repeat("-", 100);

            $total_tds = 0;
            $total_int = 0;
            $total_penalty = 0;

            foreach ($challans as $challan) {
                $lines[] = sprintf(
                    "%-15s | %-12s | %-10s | %15.2f | %15.2f | %15.2f",
                    $challan['challan_date'],
                    $challan['challan_serial_no'] ?? '',
                    $challan['bsr_code'] ?? '',
                    $challan['amount_tds'],
                    $challan['amount_interest'] ?? 0,
                    $challan['amount_penalty'] ?? 0
                );

                $total_tds += (float)$challan['amount_tds'];
                $total_int += (float)($challan['amount_interest'] ?? 0);
                $total_penalty += (float)($challan['amount_penalty'] ?? 0);
            }

            $lines[] = str_repeat("-", 100);
            $lines[] = sprintf(
                "%-15s | %-12s | %-10s | %15.2f | %15.2f | %15.2f",
                "TOTAL",
                "",
                "",
                $total_tds,
                $total_int,
                $total_penalty
            );
            $lines[] = str_repeat("-", 100);

            $content = implode("\n", $lines);
            $filename = 'CSI_Annexure_' . str_replace('-', '', $fy) . '_' . $quarter . '.txt';

            return [
                'status' => 'success',
                'document' => 'CSI_ANNEXURE',
                'fy' => $fy,
                'quarter' => $quarter,
                'filename' => $filename,
                'content' => $content,
                'size' => strlen($content),
                'challan_count' => count($challans),
                'total_tds' => round($total_tds, 2),
                'total_interest' => round($total_int, 2),
                'total_penalty' => round($total_penalty, 2),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate TDS Annexures (Supporting documents)
     */
    public function generateTDSAnnexures($firm_id, $fy, $quarter) {
        try {
            $annexures = [];

            // Annexure 1: Bank-wise Summary
            $annexures['bankwise'] = $this->generateBankwiseSummary($firm_id, $fy, $quarter);

            // Annexure 2: Vendor-wise Summary
            $annexures['vendorwise'] = $this->generateVendorwiseSummary($firm_id, $fy, $quarter);

            // Annexure 3: Section-wise Summary
            $annexures['sectionwise'] = $this->generateSectionwiseSummary($firm_id, $fy, $quarter);

            // Annexure 4: Monthly Summary
            $annexures['monthly'] = $this->generateMonthlySummary($firm_id, $fy, $quarter);

            return [
                'status' => 'success',
                'document' => 'TDS_ANNEXURES',
                'fy' => $fy,
                'quarter' => $quarter,
                'annexures' => $annexures,
                'files_count' => count($annexures),
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate BSR-wise summary (bank information not stored in current schema)
     */
    private function generateBankwiseSummary($firm_id, $fy, $quarter) {
        // Note: Grouping by BSR Code since bank_name column doesn't exist
        $stmt = $this->db->prepare(
            'SELECT bsr_code, COUNT(*) as challan_count, SUM(amount_tds) as total_tds
             FROM challans
             WHERE firm_id = ? AND fy = ? AND quarter = ?
             GROUP BY bsr_code'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $content = "BSR CODE-WISE SUMMARY\n";
        $content .= str_repeat("=", 50) . "\n\n";
        $content .= "BSR Code | Challans | Total TDS\n";
        $content .= str_repeat("-", 50) . "\n";

        foreach ($results as $row) {
            $content .= sprintf(
                "%-10s | %8d | %12.2f\n",
                $row['bsr_code'],
                $row['challan_count'],
                $row['total_tds']
            );
        }

        return [
            'name' => 'Annexure 1 - BSR Code-wise Summary',
            'filename' => 'annexure_bsr_' . $quarter . '.txt',
            'content' => $content
        ];
    }

    /**
     * Generate vendor-wise summary
     */
    private function generateVendorwiseSummary($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT v.name, v.pan, COUNT(i.id) as invoice_count, SUM(i.total_tds) as total_tds
             FROM invoices i
             JOIN vendors v ON i.vendor_id = v.id
             WHERE i.firm_id = ? AND i.fy = ? AND i.quarter = ?
             GROUP BY v.id, v.name, v.pan
             ORDER BY total_tds DESC'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $content = "VENDOR-WISE SUMMARY\n";
        $content .= str_repeat("=", 70) . "\n\n";
        $content .= "Vendor Name | PAN | Invoices | Total TDS\n";
        $content .= str_repeat("-", 70) . "\n";

        foreach ($results as $row) {
            $content .= sprintf(
                "%-20s | %-10s | %8d | %12.2f\n",
                substr($row['name'], 0, 20),
                $row['pan'],
                $row['invoice_count'],
                $row['total_tds']
            );
        }

        return [
            'name' => 'Annexure 2 - Vendor-wise Summary',
            'filename' => 'annexure_vendorwise_' . $quarter . '.txt',
            'content' => $content
        ];
    }

    /**
     * Generate section-wise summary
     */
    private function generateSectionwiseSummary($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT section_code, COUNT(*) as invoice_count, SUM(base_amount) as total_amount,
                    SUM(total_tds) as total_tds, AVG(tds_rate) as avg_rate
             FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?
             GROUP BY section_code
             ORDER BY total_tds DESC'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $content = "SECTION-WISE SUMMARY\n";
        $content .= str_repeat("=", 80) . "\n\n";
        $content .= "Section | Count | Total Amount | Total TDS | Avg Rate\n";
        $content .= str_repeat("-", 80) . "\n";

        foreach ($results as $row) {
            $content .= sprintf(
                "%-8s | %5d | %12.2f | %9.2f | %8.2f%%\n",
                $row['section_code'],
                $row['invoice_count'],
                $row['total_amount'],
                $row['total_tds'],
                $row['avg_rate']
            );
        }

        return [
            'name' => 'Annexure 3 - Section-wise Summary',
            'filename' => 'annexure_sectionwise_' . $quarter . '.txt',
            'content' => $content
        ];
    }

    /**
     * Generate monthly summary
     */
    private function generateMonthlySummary($firm_id, $fy, $quarter) {
        $stmt = $this->db->prepare(
            'SELECT MONTH(invoice_date) as month, COUNT(*) as invoice_count,
                    SUM(base_amount) as total_amount, SUM(total_tds) as total_tds
             FROM invoices
             WHERE firm_id = ? AND fy = ? AND quarter = ?
             GROUP BY MONTH(invoice_date)
             ORDER BY month'
        );
        $stmt->execute([$firm_id, $fy, $quarter]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $content = "MONTHLY SUMMARY\n";
        $content .= str_repeat("=", 70) . "\n\n";
        $content .= "Month | Invoices | Total Amount | Total TDS\n";
        $content .= str_repeat("-", 70) . "\n";

        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        foreach ($results as $row) {
            $content .= sprintf(
                "%-5s | %8d | %12.2f | %10.2f\n",
                $months[$row['month']] ?? '',
                $row['invoice_count'],
                $row['total_amount'],
                $row['total_tds']
            );
        }

        return [
            'name' => 'Annexure 4 - Monthly Summary',
            'filename' => 'annexure_monthly_' . $quarter . '.txt',
            'content' => $content
        ];
    }

    /**
     * Helper: Format FY from 2025-26 to 2025-2026
     */
    private function formatFY($fy) {
        $parts = explode('-', $fy);
        return $parts[0] . '-' . ($parts[0] + 1);
    }

    /**
     * Helper: Generate unique certificate number
     */
    private function generateCertificateNo($firm_id, $pan, $fy) {
        return 'CERT-' . strtoupper(substr($pan, 0, 5)) . '-' . date('Ymd') . '-' . str_pad($firm_id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate master data report
     */
    public function generateMasterDataReport($firm_id) {
        try {
            $firmStmt = $this->db->prepare('SELECT * FROM firms WHERE id = ?');
            $firmStmt->execute([$firm_id]);
            $firm = $firmStmt->fetch(PDO::FETCH_ASSOC);

            $vendorStmt = $this->db->prepare(
                'SELECT DISTINCT pan, name FROM vendors WHERE firm_id = ? ORDER BY name'
            );
            $vendorStmt->execute([$firm_id]);
            $vendors = $vendorStmt->fetchAll(PDO::FETCH_ASSOC);

            $lines = [];
            $lines[] = "MASTER DATA REPORT";
            $lines[] = "=" . str_repeat("=", 70);
            $lines[] = "";
            $lines[] = "FIRM DETAILS:";
            $lines[] = "Name: " . $firm['deductor_name'];
            $lines[] = "PAN: " . $firm['pan'];
            $lines[] = "TAN: " . $firm['tan'];
            $lines[] = "Type: " . ($firm['deductor_type'] ?? 'COMPANY');
            $lines[] = "";
            $lines[] = "VENDOR MASTER:";
            $lines[] = str_repeat("-", 70);
            $lines[] = sprintf("%-15s | %-50s", "PAN", "Vendor Name");
            $lines[] = str_repeat("-", 70);

            foreach ($vendors as $vendor) {
                $lines[] = sprintf(
                    "%-15s | %-50s",
                    $vendor['pan'],
                    substr($vendor['name'], 0, 50)
                );
            }

            $content = implode("\n", $lines);
            $filename = 'MasterData_' . $firm_id . '.txt';

            return [
                'status' => 'success',
                'report' => 'MASTER_DATA',
                'filename' => $filename,
                'content' => $content,
                'vendors_count' => count($vendors),
                'timestamp' => date('Y-m-d H:i:s')
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
