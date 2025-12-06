<?php
/**
 * SandboxDataFetcher - Fetch real data from Sandbox.co.in API
 *
 * This class handles fetching invoices, challans, and other data
 * from the Sandbox.co.in API for the currently selected firm.
 */
class SandboxDataFetcher {
    private $pdo;
    private $firmId;
    private $apiKey;
    private $apiSecret;
    private $accessToken;
    private $apiBaseUrl = 'https://developer.sandbox.co.in/api';

    public function __construct(PDO $pdo, $firmId) {
        $this->pdo = $pdo;
        $this->firmId = $firmId;

        // Load API credentials for this firm
        $this->loadCredentials();
    }

    /**
     * Load API credentials from database
     */
    private function loadCredentials() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT api_key, api_secret, access_token, token_expires_at
                FROM api_credentials
                WHERE firm_id = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$this->firmId]);
            $cred = $stmt->fetch();

            if (!$cred) {
                throw new Exception("No API credentials found for firm {$this->firmId}");
            }

            $this->apiKey = $cred['api_key'];
            $this->apiSecret = $cred['api_secret'];

            // Check if token is still valid
            if (!empty($cred['access_token']) && !empty($cred['token_expires_at'])) {
                if (strtotime($cred['token_expires_at']) > time()) {
                    $this->accessToken = $cred['access_token'];
                }
            }

            // Generate new token if needed
            if (empty($this->accessToken)) {
                $this->generateAccessToken();
            }
        } catch (Exception $e) {
            throw new Exception("Failed to load credentials: " . $e->getMessage());
        }
    }

    /**
     * Generate OAuth2 access token
     */
    private function generateAccessToken() {
        try {
            $url = $this->apiBaseUrl . '/v1/auth/token';

            $postData = [
                'grant_type' => 'client_credentials',
                'client_id' => $this->apiKey,
                'client_secret' => $this->apiSecret
            ];

            $response = $this->makeRequest('POST', $url, $postData);

            if (empty($response['access_token'])) {
                throw new Exception("Failed to generate access token");
            }

            $this->accessToken = $response['access_token'];

            // Save token to database
            $expiresAt = date('Y-m-d H:i:s', time() + ($response['expires_in'] ?? 3600));
            $stmt = $this->pdo->prepare("
                UPDATE api_credentials
                SET access_token = ?, token_generated_at = NOW(), token_expires_at = ?
                WHERE firm_id = ?
            ");
            $stmt->execute([$this->accessToken, $expiresAt, $this->firmId]);

        } catch (Exception $e) {
            throw new Exception("Token generation failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch invoices from API for selected FY and Quarter
     *
     * @param string $fy Financial Year (e.g., "2025-26")
     * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
     * @return array List of invoices
     */
    public function fetchInvoices($fy, $quarter) {
        try {
            // Get firm's TAN from database
            $stmt = $this->pdo->prepare("SELECT tan FROM firms WHERE id = ?");
            $stmt->execute([$this->firmId]);
            $firm = $stmt->fetch();

            if (!$firm || empty($firm['tan'])) {
                throw new Exception("TAN not found for firm");
            }

            $tan = $firm['tan'];

            // Calculate date range for quarter
            $dates = $this->getQuarterDateRange($fy, $quarter);

            // Prepare API request
            $url = $this->apiBaseUrl . '/v1/tds/invoices';
            $params = [
                'tan' => $tan,
                'from_date' => $dates['start'],
                'to_date' => $dates['end'],
                'limit' => 100,
                'offset' => 0
            ];

            $response = $this->makeRequest('GET', $url, [], $params);

            if (empty($response['data'])) {
                return [];
            }

            return $this->transformInvoices($response['data']);

        } catch (Exception $e) {
            throw new Exception("Failed to fetch invoices: " . $e->getMessage());
        }
    }

    /**
     * Fetch challans from API for selected FY and Quarter
     *
     * @param string $fy Financial Year (e.g., "2025-26")
     * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
     * @return array List of challans
     */
    public function fetchChallans($fy, $quarter) {
        try {
            // Get firm's TAN from database
            $stmt = $this->pdo->prepare("SELECT tan FROM firms WHERE id = ?");
            $stmt->execute([$this->firmId]);
            $firm = $stmt->fetch();

            if (!$firm || empty($firm['tan'])) {
                throw new Exception("TAN not found for firm");
            }

            $tan = $firm['tan'];

            // Calculate date range for quarter
            $dates = $this->getQuarterDateRange($fy, $quarter);

            // Prepare API request
            $url = $this->apiBaseUrl . '/v1/tds/challans';
            $params = [
                'tan' => $tan,
                'from_date' => $dates['start'],
                'to_date' => $dates['end'],
                'limit' => 100,
                'offset' => 0
            ];

            $response = $this->makeRequest('GET', $url, [], $params);

            if (empty($response['data'])) {
                return [];
            }

            return $this->transformChallans($response['data']);

        } catch (Exception $e) {
            throw new Exception("Failed to fetch challans: " . $e->getMessage());
        }
    }

    /**
     * Fetch deductees from API
     *
     * @param string $fy Financial Year
     * @param string $quarter Quarter
     * @return array List of deductees
     */
    public function fetchDeductees($fy, $quarter) {
        try {
            // Get firm's TAN
            $stmt = $this->pdo->prepare("SELECT tan FROM firms WHERE id = ?");
            $stmt->execute([$this->firmId]);
            $firm = $stmt->fetch();

            if (!$firm || empty($firm['tan'])) {
                throw new Exception("TAN not found for firm");
            }

            $tan = $firm['tan'];

            // Get date range
            $dates = $this->getQuarterDateRange($fy, $quarter);

            // Prepare API request
            $url = $this->apiBaseUrl . '/v1/tds/deductees';
            $params = [
                'tan' => $tan,
                'from_date' => $dates['start'],
                'to_date' => $dates['end'],
                'limit' => 100,
                'offset' => 0
            ];

            $response = $this->makeRequest('GET', $url, [], $params);

            if (empty($response['data'])) {
                return [];
            }

            return $this->transformDeductees($response['data']);

        } catch (Exception $e) {
            throw new Exception("Failed to fetch deductees: " . $e->getMessage());
        }
    }

    /**
     * Import fetched invoices into local database
     */
    public function importInvoices($invoices, $fy, $quarter) {
        try {
            $count = 0;
            $stmt = $this->pdo->prepare("
                INSERT INTO invoices
                (firm_id, fy, quarter, invoice_number, invoice_date, vendor_id, vendor_name,
                 vendor_pan, base_amount, section_code, rate_percent, total_tds, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                base_amount = VALUES(base_amount),
                total_tds = VALUES(total_tds),
                updated_at = NOW()
            ");

            foreach ($invoices as $inv) {
                try {
                    // Get or create vendor
                    $vendorId = $this->getOrCreateVendor($inv['vendor_name'] ?? 'Unknown', $inv['vendor_pan'] ?? null);

                    $stmt->execute([
                        $this->firmId,
                        $fy,
                        $quarter,
                        $inv['invoice_number'],
                        $inv['invoice_date'],
                        $vendorId,
                        $inv['vendor_name'] ?? 'Unknown',
                        $inv['vendor_pan'] ?? null,
                        $inv['base_amount'] ?? 0,
                        $inv['section_code'] ?? '194A',
                        $inv['rate_percent'] ?? 0,
                        $inv['tds_amount'] ?? 0
                    ]);
                    $count++;
                } catch (Exception $e) {
                    // Log error but continue with other invoices
                    error_log("Failed to import invoice {$inv['invoice_number']}: " . $e->getMessage());
                }
            }

            return [
                'status' => 'success',
                'message' => "Imported $count invoices",
                'count' => $count
            ];

        } catch (Exception $e) {
            throw new Exception("Failed to import invoices: " . $e->getMessage());
        }
    }

    /**
     * Import fetched challans into local database
     */
    public function importChallans($challans, $fy, $quarter) {
        try {
            $count = 0;
            $stmt = $this->pdo->prepare("
                INSERT INTO challans
                (firm_id, fy, quarter, bsr_code, challan_serial_number, challan_date,
                 bank_code, amount_tds, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                amount_tds = VALUES(amount_tds),
                updated_at = NOW()
            ");

            foreach ($challans as $challan) {
                try {
                    $stmt->execute([
                        $this->firmId,
                        $fy,
                        $quarter,
                        $challan['bsr_code'],
                        $challan['serial_number'],
                        $challan['challan_date'],
                        $challan['bank_code'] ?? null,
                        $challan['amount_tds'] ?? 0
                    ]);
                    $count++;
                } catch (Exception $e) {
                    error_log("Failed to import challan {$challan['bsr_code']}: " . $e->getMessage());
                }
            }

            return [
                'status' => 'success',
                'message' => "Imported $count challans",
                'count' => $count
            ];

        } catch (Exception $e) {
            throw new Exception("Failed to import challans: " . $e->getMessage());
        }
    }

    /**
     * Make HTTP request to API
     */
    private function makeRequest($method, $url, $postData = [], $params = []) {
        try {
            // Add query parameters
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            // Set headers
            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // Set method
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL error: $error");
            }

            if ($httpCode >= 400) {
                throw new Exception("API error ($httpCode): $response");
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON response: " . json_last_error_msg());
            }

            return $data ?? [];

        } catch (Exception $e) {
            throw new Exception("Request failed: " . $e->getMessage());
        }
    }

    /**
     * Transform API invoice format to local format
     */
    private function transformInvoices($apiInvoices) {
        $transformed = [];
        foreach ($apiInvoices as $inv) {
            $transformed[] = [
                'invoice_number' => $inv['invoice_no'] ?? $inv['invoice_number'],
                'invoice_date' => $inv['invoice_date'],
                'vendor_name' => $inv['deductee_name'] ?? 'Unknown',
                'vendor_pan' => $inv['deductee_pan'] ?? null,
                'base_amount' => $inv['amount'] ?? 0,
                'section_code' => $inv['section'] ?? '194A',
                'rate_percent' => $this->getRate($inv['section'] ?? '194A'),
                'tds_amount' => $inv['tds_amount'] ?? 0
            ];
        }
        return $transformed;
    }

    /**
     * Transform API challan format to local format
     */
    private function transformChallans($apiChallans) {
        $transformed = [];
        foreach ($apiChallans as $challan) {
            $transformed[] = [
                'bsr_code' => $challan['bsr_code'],
                'serial_number' => $challan['serial_no'] ?? $challan['serial_number'],
                'challan_date' => $challan['challan_date'],
                'bank_code' => $challan['bank_code'] ?? null,
                'amount_tds' => $challan['amount'] ?? 0
            ];
        }
        return $transformed;
    }

    /**
     * Transform API deductee format to local format
     */
    private function transformDeductees($apiDeductees) {
        $transformed = [];
        foreach ($apiDeductees as $deductee) {
            $transformed[] = [
                'pan' => $deductee['pan'],
                'name' => $deductee['name'],
                'type' => $deductee['type'] ?? 'individual'
            ];
        }
        return $transformed;
    }

    /**
     * Get quarter date range
     */
    private function getQuarterDateRange($fy, $quarter) {
        // FY format: 2025-26, 2024-25, etc.
        $startYear = (int)substr($fy, 0, 4);

        switch ($quarter) {
            case 'Q1':
                $start = "{$startYear}-04-01";
                $end = "{$startYear}-06-30";
                break;
            case 'Q2':
                $start = "{$startYear}-07-01";
                $end = "{$startYear}-09-30";
                break;
            case 'Q3':
                $start = "{$startYear}-10-01";
                $end = "{$startYear}-12-31";
                break;
            case 'Q4':
                $start = ($startYear + 1) . "-01-01";
                $end = ($startYear + 1) . "-03-31";
                break;
            default:
                throw new Exception("Invalid quarter: $quarter");
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * Get TDS rate for section
     */
    private function getRate($section) {
        $rates = [
            '194A' => 10,    // Rent
            '194C' => 1,     // Contractors
            '194D' => 10,    // Insurance
            '194E' => 10,    // Interest
            '194F' => 20,    // Dividends
            '194G' => 6,     // Royalties
            '194H' => 10,    // Commission
            '194I' => 20,    // FDI
            '194J' => 10,    // Professional fees
            '194K' => 20,    // Non-resident
            '194LA' => 20,   // Sponsorship
            '194LB' => 30    // Winnings
        ];

        return $rates[$section] ?? 10;
    }

    /**
     * Get or create vendor record
     */
    private function getOrCreateVendor($name, $pan) {
        try {
            // Check if vendor exists
            $stmt = $this->pdo->prepare("SELECT id FROM vendors WHERE pan = ? OR name = ?");
            $stmt->execute([$pan, $name]);
            $vendor = $stmt->fetch();

            if ($vendor) {
                return $vendor['id'];
            }

            // Create new vendor
            $stmt = $this->pdo->prepare("
                INSERT INTO vendors (firm_id, name, pan, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$this->firmId, $name, $pan]);

            return $this->pdo->lastInsertId();

        } catch (Exception $e) {
            // Return a placeholder if vendor creation fails
            return 0;
        }
    }
}
