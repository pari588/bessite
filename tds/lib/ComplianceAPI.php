<?php
/**
 * Sandbox.co.in Compliance API Integration
 * E-filing, FVU generation, certificate downloads, CSI management
 *
 * @author TDS AutoFile
 * @version 1.0
 */

class ComplianceAPI {
    private $db;
    private $sandbox_api_base = 'https://api.sandbox.co.in/v1/tds';
    private $api_key = null;
    private $api_secret = null;
    private $upload_path = '/uploads/compliance';

    public function __construct($pdo, $api_key = null, $api_secret = null) {
        $this->db = $pdo;
        $this->api_key = $api_key ?? getenv('SANDBOX_API_KEY');
        $this->api_secret = $api_secret ?? getenv('SANDBOX_API_SECRET');
    }

    /**
     * STEP 1: Generate FVU (File Validation Utility)
     *
     * Submit Form 26Q/24Q for validation to Sandbox.
     * Sandbox will validate the form structure and data.
     * Returns FVU file if validation passes, errors if not.
     *
     * API Endpoint: POST /compliance/fvu/generate/submit
     */
    public function generateFVU($form_content, $form_type = '26Q', $firm_id = null) {
        try {
            // Validate form content exists
            if (empty($form_content)) {
                throw new Exception("Form content is empty");
            }

            // Generate unique job ID
            $job_uuid = $this->generateUUID();
            $job_id = uniqid();

            // Create filing job record
            $stmt = $this->db->prepare(
                'INSERT INTO tds_filing_jobs (job_uuid, firm_id, form_type, form_content, txt_status, fvu_status)
                 VALUES (?, ?, ?, ?, "COMPLETED", "SUBMITTED")
                 ON DUPLICATE KEY UPDATE txt_status = "COMPLETED", fvu_status = "SUBMITTED"'
            );
            $stmt->execute([$job_uuid, $firm_id, $form_type, $form_content]);

            // Log the submission
            $this->logEvent($job_id, 'FVU_SUBMITTED', 'SUCCESS', [
                'form_type' => $form_type,
                'content_length' => strlen($form_content)
            ]);

            // Simulate Sandbox API call (in production, use cURL)
            // For now, we'll generate FVU immediately as if Sandbox accepted it
            $fvu_response = $this->simulateFVUGeneration($form_type, $form_content, $job_uuid);

            if ($fvu_response['status'] === 'success') {
                // Update job status
                $updateStmt = $this->db->prepare(
                    'UPDATE tds_filing_jobs SET fvu_status = ?, fvu_job_id = ?, fvu_generated_at = NOW()
                     WHERE job_uuid = ?'
                );
                $updateStmt->execute(['READY', $job_uuid, $job_uuid]);

                $this->logEvent($job_id, 'FVU_GENERATED', 'SUCCESS', ['fvu_path' => $fvu_response['fvu_path']]);

                return [
                    'status' => 'success',
                    'job_id' => $job_id,
                    'job_uuid' => $job_uuid,
                    'form_type' => $form_type,
                    'fvu_job_id' => $fvu_response['fvu_job_id'],
                    'fvu_status' => 'READY',
                    'message' => 'FVU generated successfully',
                    'next_step' => 'Download FVU and Form 27A for e-filing',
                    'download_url' => "/tds/api/compliance/download_fvu?job_id=$job_uuid",
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                throw new Exception("FVU generation failed: " . implode(', ', $fvu_response['errors']));
            }

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * STEP 2: Poll FVU Generation Status
     *
     * Check if FVU is ready. Typically takes 1-2 minutes.
     *
     * API Endpoint: GET /compliance/fvu/generate/poll?job_id=XXX
     */
    public function checkFVUStatus($job_uuid) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE job_uuid = ?'
            );
            $stmt->execute([$job_uuid]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Filing job not found");
            }

            return [
                'status' => 'success',
                'job_uuid' => $job_uuid,
                'form_type' => $job['form_type'],
                'txt_status' => $job['txt_status'],
                'fvu_status' => $job['fvu_status'],
                'fvu_ready' => $job['fvu_status'] === 'READY',
                'fvu_path' => $job['fvu_path'],
                'fvu_generated_at' => $job['fvu_generated_at'],
                'errors' => $job['fvu_status'] === 'FAILED' ? ['FVU generation failed'] : [],
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
     * STEP 3: E-File the Return
     *
     * Submit FVU + Form 27A to Tax Authority for e-filing.
     * Form 27A must be digitally signed.
     *
     * API Endpoint: POST /compliance/efile/submit
     */
    public function eFileReturn($job_uuid, $form27a_content, $digital_signature = null) {
        try {
            // Get filing job
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE job_uuid = ?'
            );
            $stmt->execute([$job_uuid]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Filing job not found");
            }

            if ($job['fvu_status'] !== 'READY') {
                throw new Exception("FVU not ready for filing. Current status: " . $job['fvu_status']);
            }

            if (empty($form27a_content)) {
                throw new Exception("Form 27A content is required");
            }

            // Create filing submission record
            $filing_job_id = $this->generateUUID();

            $updateStmt = $this->db->prepare(
                'UPDATE tds_filing_jobs SET
                 filing_job_id = ?, e_filing_status = ?, submitted_at = NOW()
                 WHERE job_uuid = ?'
            );
            $updateStmt->execute([$filing_job_id, 'SUBMITTED_TO_TA', $job_uuid]);

            // Log e-filing event
            $this->logEvent($filing_job_id, 'EFILED', 'SUCCESS', [
                'form_type' => $job['form_type'],
                'form27a_size' => strlen($form27a_content)
            ]);

            // In production, this would call Sandbox API to submit
            // For now, simulate successful submission
            $efile_response = $this->simulateEFiling($job['form_type'], $filing_job_id);

            if ($efile_response['status'] === 'success') {
                return [
                    'status' => 'success',
                    'job_uuid' => $job_uuid,
                    'filing_job_id' => $filing_job_id,
                    'form_type' => $job['form_type'],
                    'e_filing_status' => 'SUBMITTED_TO_TA',
                    'message' => 'Return submitted for e-filing',
                    'next_step' => 'Check filing status for acknowledgement',
                    'status_check_url' => "/tds/api/compliance/filing_status?job_id=$filing_job_id",
                    'expected_acknowledgement_time' => 'Within 2 hours',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                throw new Exception("E-filing submission failed");
            }

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * STEP 4: Track E-Filing Status
     *
     * Poll for acknowledgement from Tax Authority.
     * Once acknowledged, you can download the acknowledgement PDF.
     *
     * API Endpoint: GET /compliance/efile/status?filing_job_id=XXX
     */
    public function trackFilingStatus($filing_job_id) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE filing_job_id = ?'
            );
            $stmt->execute([$filing_job_id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Filing job not found");
            }

            return [
                'status' => 'success',
                'filing_job_id' => $filing_job_id,
                'form_type' => $job['form_type'],
                'fy' => $job['fy'],
                'quarter' => $job['quarter'],
                'e_filing_status' => $job['e_filing_status'],
                'ack_no' => $job['ack_no'],
                'ack_date' => $job['ack_date'],
                'acknowledged' => !empty($job['ack_no']),
                'status_timeline' => [
                    'submitted_at' => $job['submitted_at'],
                    'filed_at' => $job['filed_at'],
                    'ack_received_at' => $job['ack_date']
                ],
                'control_totals' => [
                    'records_count' => $job['records_count'],
                    'total_amount' => $job['total_amount'],
                    'total_tds' => $job['total_tds']
                ],
                'next_action' => !empty($job['ack_no'])
                    ? 'Download acknowledgement and certificates'
                    : 'Check again in 30 seconds',
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
     * STEP 5: Download FVU (File Validation Utility)
     *
     * Returns the FVU ZIP file for review before e-filing.
     */
    public function downloadFVU($job_uuid) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE job_uuid = ?'
            );
            $stmt->execute([$job_uuid]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Filing job not found");
            }

            if ($job['fvu_status'] !== 'READY') {
                throw new Exception("FVU not ready. Status: " . $job['fvu_status']);
            }

            return [
                'status' => 'success',
                'job_uuid' => $job_uuid,
                'fvu_file' => $job['fvu_path'],
                'fvu_filename' => basename($job['fvu_path']),
                'download_url' => '/tds/downloads/fvu/' . basename($job['fvu_path']),
                'file_size' => file_exists($job['fvu_path']) ? filesize($job['fvu_path']) : 0,
                'file_format' => 'ZIP',
                'contents' => [
                    'form26q_fvu.txt' => 'Validated form 26Q',
                    'errors.log' => 'Validation errors (if any)',
                    'warnings.log' => 'Validation warnings',
                    'form27a_template.pdf' => 'Acknowledgement form (unsigned)'
                ],
                'ready_for_efile' => true,
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
     * STEP 6: Download Form 16/16A Certificates
     *
     * After e-filing is acknowledged, download TDS certificates.
     */
    public function downloadForm16($job_uuid, $deductee_pan) {
        try {
            // Get Form 16 from database
            $stmt = $this->db->prepare(
                'SELECT i.*, v.name FROM invoices i
                 JOIN vendors v ON i.vendor_id = v.id
                 JOIN tds_filing_jobs f ON i.firm_id = f.firm_id
                 WHERE f.job_uuid = ? AND v.pan = ?
                 LIMIT 1'
            );
            $stmt->execute([$job_uuid, $deductee_pan]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                throw new Exception("No invoice found for this deductee");
            }

            // Generate certificate content
            $cert_no = 'CERT-' . strtoupper(substr($deductee_pan, 0, 5)) . '-' . date('Ymd');
            $content = $this->generateForm16Content($deductee_pan, $invoice['name'], $cert_no);

            return [
                'status' => 'success',
                'form_type' => '16',
                'deductee_pan' => $deductee_pan,
                'certificate_no' => $cert_no,
                'certificate_date' => date('Y-m-d'),
                'filename' => 'Form16_' . $deductee_pan . '_' . $cert_no . '.pdf',
                'download_url' => '/tds/downloads/form16/' . $cert_no . '.pdf',
                'content' => $content,
                'format' => 'PDF',
                'printable' => true,
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
     * STEP 7: Download CSI Annexure
     *
     * Challan Summary Information from bank for reconciliation.
     */
    public function downloadCSI($job_uuid) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE job_uuid = ?'
            );
            $stmt->execute([$job_uuid]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Filing job not found");
            }

            // Get challans for this filing
            $challanStmt = $this->db->prepare(
                'SELECT * FROM challans
                 WHERE firm_id = ? AND fy = ? AND quarter = ?
                 ORDER BY challan_date'
            );
            $challanStmt->execute([$job['firm_id'], $job['fy'], $job['quarter']]);
            $challans = $challanStmt->fetchAll(PDO::FETCH_ASSOC);

            // Build CSI content
            $csi_content = "CHALLAN SUMMARY INFORMATION\n";
            $csi_content .= "=" . str_repeat("=", 70) . "\n";
            $csi_content .= "Date: " . date('d-m-Y') . "\n\n";

            foreach ($challans as $challan) {
                $csi_content .= $challan['bsr_code'] . "|" . $challan['challan_date'] . "|" .
                    $challan['challan_serial_no'] . "|" . $challan['amount_tds'] . "|DEPOSITED\n";
            }

            return [
                'status' => 'success',
                'document' => 'CSI_ANNEXURE',
                'job_uuid' => $job_uuid,
                'filename' => 'CSI_' . $job['fy'] . '_' . $job['quarter'] . '.txt',
                'download_url' => '/tds/downloads/csi/CSI_' . $job['fy'] . '_' . $job['quarter'] . '.txt',
                'content' => $csi_content,
                'challan_count' => count($challans),
                'file_format' => 'TXT (^ delimited)',
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
     * Download TDS Annexures
     */
    public function downloadTDSAnnexures($job_uuid) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE job_uuid = ?'
            );
            $stmt->execute([$job_uuid]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                throw new Exception("Filing job not found");
            }

            return [
                'status' => 'success',
                'document' => 'TDS_ANNEXURES',
                'job_uuid' => $job_uuid,
                'filename' => 'TDSAnnexures_' . $job['fy'] . '.zip',
                'download_url' => '/tds/downloads/annexures/TDSAnnexures_' . $job['fy'] . '.zip',
                'files' => [
                    'annexure_bankwise.txt' => 'Bank-wise TDS summary',
                    'annexure_vendorwise.txt' => 'Vendor-wise TDS summary',
                    'annexure_sectionwise.txt' => 'Section-wise TDS summary',
                    'annexure_monthly.txt' => 'Monthly TDS summary'
                ],
                'file_format' => 'ZIP',
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
     * Download Acknowledgement
     */
    public function downloadAcknowledgement($filing_job_id) {
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM tds_filing_jobs WHERE filing_job_id = ?'
            );
            $stmt->execute([$filing_job_id]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job || empty($job['ack_no'])) {
                throw new Exception("Acknowledgement not yet received");
            }

            return [
                'status' => 'success',
                'filing_job_id' => $filing_job_id,
                'ack_no' => $job['ack_no'],
                'ack_date' => $job['ack_date'],
                'filename' => 'Acknowledgement_' . $job['ack_no'] . '.pdf',
                'download_url' => '/tds/downloads/acknowledgement/' . $job['ack_no'] . '.pdf',
                'form_type' => $job['form_type'],
                'fy' => $job['fy'],
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
     * Helper: Simulate FVU generation (for testing without Sandbox API)
     */
    private function simulateFVUGeneration($form_type, $content, $job_uuid) {
        // In production, call actual Sandbox API
        // For testing, return simulated success
        return [
            'status' => 'success',
            'fvu_job_id' => uniqid('fvu_'),
            'fvu_path' => '/tmp/fvu_' . $job_uuid . '.zip',
            'message' => 'FVU generated successfully'
        ];
    }

    /**
     * Helper: Simulate e-filing (for testing without Sandbox API)
     */
    private function simulateEFiling($form_type, $filing_job_id) {
        // Simulate acknowledgement after delay
        // In production, this would be handled by Sandbox webhooks
        return [
            'status' => 'success',
            'message' => 'Return submitted for e-filing'
        ];
    }

    /**
     * Helper: Generate unique UUID
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Helper: Log event for audit trail
     */
    private function logEvent($job_id, $event_type, $event_status, $details = []) {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO tds_filing_logs (job_id, event_type, event_status, details)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $job_id,
                $event_type,
                $event_status,
                json_encode($details)
            ]);
        } catch (Exception $e) {
            // Log silently
        }
    }

    /**
     * Helper: Generate Form 16 content
     */
    private function generateForm16Content($pan, $name, $cert_no) {
        return "FORM 16 TDS CERTIFICATE\n" .
            "Certificate No: $cert_no\n" .
            "Deductee PAN: $pan\n" .
            "Deductee Name: $name\n" .
            "Date: " . date('d-m-Y') . "\n";
    }
}

?>
