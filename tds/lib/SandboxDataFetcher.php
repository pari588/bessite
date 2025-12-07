<?php
/**
 * SandboxDataFetcher - Fetch real data from Sandbox.co.in API
 *
 * This class handles fetching invoices, challans, and other data
 * from the Sandbox.co.in API for the currently selected firm.
 * Uses JWT token authentication (compatible with SandboxTDSAPI)
 */
class SandboxDataFetcher {
    private $pdo;
    private $firmId;
    private $apiKey;
    private $apiSecret;
    private $accessToken;
    private $tokenExpiresAt;
    private $environment;
    private $baseUrl;

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
                SELECT api_key, api_secret, access_token, token_expires_at, environment
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
            $this->environment = $cred['environment'] ?? 'sandbox';

            // Set API base URL
            $this->baseUrl = ($this->environment === 'production')
                ? 'https://api.sandbox.co.in'
                : 'https://test-api.sandbox.co.in';

            // Check if token is still valid
            if (!empty($cred['access_token']) && !empty($cred['token_expires_at'])) {
                $tokenExpires = strtotime($cred['token_expires_at']);
                if ($tokenExpires > time() + 300) { // 5 min buffer
                    $this->accessToken = $cred['access_token'];
                    $this->tokenExpiresAt = $tokenExpires;
                }
            }

            // Generate new token if needed
            if (empty($this->accessToken)) {
                $this->authenticate();
            }
        } catch (Exception $e) {
            throw new Exception("Failed to load credentials: " . $e->getMessage());
        }
    }

    /**
     * Authenticate with Sandbox API and get JWT token
     */
    private function authenticate() {
        try {
            $headers = [
                'x-api-key' => $this->apiKey,
                'x-api-secret' => $this->apiSecret,
                'x-api-version' => '1.0',
                'Content-Type' => 'application/json'
            ];

            $response = $this->makeRequest('POST', '/authenticate', [], $headers);

            if (empty($response['data']['access_token'])) {
                throw new Exception("Failed to get access token: " . json_encode($response));
            }

            $this->accessToken = $response['data']['access_token'];
            $this->tokenExpiresAt = time() + 86400; // 24 hours

            // Save token to database
            $expiresAt = date('Y-m-d H:i:s', $this->tokenExpiresAt);
            $stmt = $this->pdo->prepare("
                UPDATE api_credentials
                SET access_token = ?, token_generated_at = NOW(), token_expires_at = ?
                WHERE firm_id = ?
            ");
            $stmt->execute([$this->accessToken, $expiresAt, $this->firmId]);

        } catch (Exception $e) {
            throw new Exception("Authentication failed: " . $e->getMessage());
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
            // Ensure valid token
            $this->ensureValidToken();

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

            // Use AWS Signature V4 for data endpoints
            $params = [
                'tan' => $tan,
                'from_date' => $dates['start'],
                'to_date' => $dates['end'],
                'limit' => 100,
                'offset' => 0
            ];

            // Try multiple possible endpoints
            $endpoints = [
                '/v1/tds/invoices',
                '/tds/invoices',
                '/data/invoices'
            ];

            $lastError = null;

            foreach ($endpoints as $baseEndpoint) {
                try {
                    // Build full URL with query params
                    $queryString = http_build_query($params);
                    $fullUrl = $this->baseUrl . $baseEndpoint . '?' . $queryString;

                    // Sign request with AWS SigV4
                    $response = $this->makeAuthenticatedRequest('GET', $baseEndpoint, $params);

                    // Handle both possible response formats
                    $invoices = $response['invoices'] ?? $response['data']['invoices'] ?? $response['data'] ?? [];

                    // If we got data, return it
                    if (!empty($invoices)) {
                        return $this->transformInvoices($invoices);
                    }

                } catch (Exception $e) {
                    // Try next endpoint
                    $lastError = $e->getMessage();
                    continue;
                }
            }

            // If we got here, no endpoint worked but didn't have errors
            if ($lastError) {
                throw new Exception("All endpoints failed: $lastError");
            }

            // No data found (HTTP 200 but empty)
            return [];

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
            // Ensure valid token
            $this->ensureValidToken();

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

            // Try multiple possible endpoints
            $endpoints = [
                '/v1/tds/challans',
                '/tds/challans',
                '/data/challans'
            ];

            $challans = [];
            $lastError = null;

            foreach ($endpoints as $baseEndpoint) {
                try {
                    $params = [
                        'tan' => $tan,
                        'from_date' => $dates['start'],
                        'to_date' => $dates['end'],
                        'limit' => 100,
                        'offset' => 0
                    ];

                    // Build query string
                    $endpoint = $baseEndpoint . '?' . http_build_query($params);

                    $response = $this->makeRequest('GET', $endpoint, []);

                    // Handle both possible response formats
                    $challans = $response['challans'] ?? $response['data']['challans'] ?? $response['data'] ?? [];

                    // If we got data, return it
                    if (!empty($challans)) {
                        return $this->transformChallans($challans);
                    }

                } catch (Exception $e) {
                    // Try next endpoint
                    $lastError = $e->getMessage();
                    continue;
                }
            }

            // If we got here, no endpoint worked but didn't have errors
            if ($lastError) {
                throw new Exception("All endpoints failed: $lastError");
            }

            // No data found (HTTP 200 but empty)
            return [];

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

            // Try multiple possible endpoints for deductees
            $endpoints = [
                '/v1/tds/deductees',
                '/tds/deductees',
                '/data/deductees'
            ];

            $lastError = null;

            foreach ($endpoints as $baseEndpoint) {
                try {
                    $params = [
                        'tan' => $tan,
                        'from_date' => $dates['start'],
                        'to_date' => $dates['end'],
                        'limit' => 100,
                        'offset' => 0
                    ];

                    $response = $this->makeRequest('GET', $baseEndpoint, [], $params);

                    // Handle both possible response formats
                    $deductees = $response['deductees'] ?? $response['data']['deductees'] ?? $response['data'] ?? [];

                    // If we got data, return it
                    if (!empty($deductees)) {
                        return $this->transformDeductees($deductees);
                    }

                } catch (Exception $e) {
                    // Try next endpoint
                    $lastError = $e->getMessage();
                    continue;
                }
            }

            // If we got here, no endpoint worked but didn't have errors
            if ($lastError) {
                throw new Exception("All endpoints failed: $lastError");
            }

            // No data found (HTTP 200 but empty)
            return [];

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
     * Make HTTP request to API with JWT authentication
     */
    private function makeRequest($method, $endpoint, $postData = [], $customHeaders = []) {
        try {
            $ch = curl_init();

            $url = $this->baseUrl . $endpoint;

            // Add query params if GET request
            if ($method === 'GET' && !empty($postData) && count($customHeaders) === 0) {
                $url .= '?' . http_build_query($postData);
            }

            // Build header array - convert associative array to string format
            $headerArray = [];

            // Add custom headers first (like x-api-key, x-api-secret)
            foreach ($customHeaders as $key => $value) {
                $headerArray[] = "$key: $value";
            }

            // Add standard headers
            $headerArray[] = 'Content-Type: application/json';
            $headerArray[] = 'Accept: application/json';

            // Add authorization header if token exists
            if (!empty($this->accessToken)) {
                $headerArray[] = "Authorization: {$this->accessToken}";
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headerArray,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            if (!empty($postData) && $method !== 'GET') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!empty($curlError)) {
                throw new Exception("cURL Error: $curlError");
            }

            if ($httpCode >= 400) {
                throw new Exception("API Error (HTTP $httpCode): $response");
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON response: " . json_last_error_msg() . " Response: $response");
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
     * Ensure access token is valid, refresh if needed
     */
    private function ensureValidToken() {
        if (!$this->accessToken || time() > $this->tokenExpiresAt - 300) {
            // Token expires in less than 5 minutes, refresh
            $this->authenticate();
        }
    }

    /**
     * Make AWS SigV4 authenticated request for data endpoints
     */
    private function makeAuthenticatedRequest($method, $endpoint, $params = []) {
        try {
            $timestamp = gmdate('Ymd\THis\Z');
            $dateStamp = gmdate('Ymd');

            // Build canonical request
            $queryString = '';
            if (!empty($params)) {
                $queryString = http_build_query($params);
            }

            $payloadHash = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'; // SHA256("")
            $canonicalHeaders = "host:" . parse_url($this->baseUrl, PHP_URL_HOST) . "\nx-amz-date:{$timestamp}\n";
            $signedHeaders = "host;x-amz-date";

            $canonicalRequest = "$method\n" .
                               "$endpoint\n" .
                               "$queryString\n" .
                               $canonicalHeaders . "\n" .
                               $signedHeaders . "\n" .
                               $payloadHash;

            // Create string to sign
            $algorithm = 'AWS4-HMAC-SHA256';
            $credentialScope = "{$dateStamp}/ap-south-1/execute-api/aws4_request";
            $canonicalRequestHash = hash('sha256', $canonicalRequest);

            $stringToSign = "{$algorithm}\n" .
                           "{$timestamp}\n" .
                           "{$credentialScope}\n" .
                           $canonicalRequestHash;

            // Calculate signature
            $kSecret = 'AWS4' . $this->apiSecret;
            $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
            $kRegion = hash_hmac('sha256', 'ap-south-1', $kDate, true);
            $kService = hash_hmac('sha256', 'execute-api', $kRegion, true);
            $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
            $signature = hash_hmac('sha256', $stringToSign, $kSigning);

            // Build authorization header
            $authorizationHeader = "{$algorithm} Credential={$this->apiKey}/{$credentialScope}, " .
                                  "SignedHeaders={$signedHeaders}, " .
                                  "Signature={$signature}";

            // Make request with AWS SigV4 headers
            $ch = curl_init();

            $url = $this->baseUrl . $endpoint;
            if (!empty($queryString)) {
                $url .= '?' . $queryString;
            }

            $headers = [
                'Authorization: ' . $authorizationHeader,
                'X-Amz-Date: ' . $timestamp,
                'Content-Type: application/json'
            ];

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!empty($curlError)) {
                throw new Exception("cURL Error: $curlError");
            }

            if ($httpCode >= 400) {
                throw new Exception("API Error (HTTP $httpCode): $response");
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON: " . json_last_error_msg());
            }

            return $data ?? [];

        } catch (Exception $e) {
            throw new Exception("AWS request failed: " . $e->getMessage());
        }
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
