<?php
/**
 * SandboxTDSAPI - Integration with Sandbox.co.in TDS Compliance APIs
 * Handles authentication, FVU generation, and e-filing
 *
 * @link https://developer.sandbox.co.in/docs/tds
 */

class SandboxTDSAPI {
  private $apiKey;
  private $apiSecret;
  private $environment;
  private $baseUrl;
  private $accessToken;
  private $tokenExpiresAt;
  private $pdo;
  private $firmId;
  private $logCallback;

  /**
   * Initialize Sandbox TDS API client
   *
   * @param int $firm_id Firm ID with API credentials
   * @param PDO $pdo Database connection
   * @param callable $logCallback Optional callback for logging API calls
   * @param string $environment Optional environment ('sandbox' or 'production'), defaults to 'sandbox'
   */
  public function __construct($firm_id, PDO $pdo, $logCallback = null, $environment = 'sandbox') {
    $this->pdo = $pdo;
    $this->firmId = $firm_id;
    $this->logCallback = $logCallback;

    // Fetch API credentials from database for specified environment
    $stmt = $pdo->prepare('SELECT * FROM api_credentials WHERE firm_id=? AND environment=? AND is_active=1');
    $stmt->execute([$firm_id, $environment]);
    $cred = $stmt->fetch();

    if (!$cred) {
      throw new Exception("No active API credentials found for firm $firm_id in $environment environment");
    }

    $this->apiKey = $cred['api_key'];
    $this->apiSecret = $cred['api_secret'];
    $this->environment = $cred['environment'];
    $this->accessToken = $cred['access_token'];
    $this->tokenExpiresAt = strtotime($cred['token_expires_at'] ?? '-1 minute');

    // Set API base URL
    $this->baseUrl = ($this->environment === 'production')
      ? 'https://api.sandbox.co.in'
      : 'https://test-api.sandbox.co.in';
  }

  /**
   * Authenticate with Sandbox API and get access token
   *
   * @return string Access token (JWT)
   * @throws Exception If authentication fails
   */
  public function authenticate() {
    try {
      $response = $this->makeRequest('POST', '/authenticate', [], [
        'x-api-key' => $this->apiKey,
        'x-api-secret' => $this->apiSecret,
        'x-api-version' => '1.0'
      ]);

      if (!isset($response['data']['access_token'])) {
        throw new Exception('Invalid authentication response: ' . json_encode($response));
      }

      $this->accessToken = $response['data']['access_token'];
      $this->tokenExpiresAt = time() + 86400; // 24 hours

      // Store in database
      $stmt = $this->pdo->prepare('
        UPDATE api_credentials
        SET access_token=?, token_generated_at=NOW(), token_expires_at=DATE_ADD(NOW(), INTERVAL 24 HOUR)
        WHERE firm_id=?
      ');
      $stmt->execute([$this->accessToken, $this->firmId]);

      $this->log('authenticate', 'success', 'Token generated');

      return $this->accessToken;
    } catch (Exception $e) {
      $this->log('authenticate', 'failed', $e->getMessage());
      throw $e;
    }
  }

  /**
   * Download CSI file (Challan Status Information) from bank
   * Requires OTP verification via bank statement
   *
   * @param string $fy Fiscal year (e.g., '2025-26')
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @return string CSI file content
   * @throws Exception
   */
  public function downloadCSI($fy, $quarter) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest('GET', '/tds/compliance/csi/download', [
        'fy' => $fy,
        'quarter' => $quarter
      ]);

      if (!isset($response['data'])) {
        throw new Exception('Invalid CSI download response');
      }

      $csiContent = $response['data'];
      $this->log('csi_download', 'success', "CSI downloaded for $fy $quarter");

      return $csiContent;
    } catch (Exception $e) {
      $this->log('csi_download', 'failed', $e->getMessage(), json_encode(['fy' => $fy, 'quarter' => $quarter]));
      throw $e;
    }
  }

  /**
   * Submit FVU (File Validation Utility) generation job to Sandbox
   * This is an async operation - use pollFVUJobStatus() to track progress
   *
   * @param string $txtContent Form 26Q TXT content (NS1 format)
   * @param string $csiContent CSI file content
   * @return array ['job_id' => string, 'status' => 'submitted']
   * @throws Exception
   */
  public function submitFVUGenerationJob($txtContent, $csiContent) {
    try {
      $this->ensureValidToken();

      $payload = [
        'txt_file' => base64_encode($txtContent),
        'csi_file' => base64_encode($csiContent)
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/compliance/fvu/generate',
        $payload
      );

      if (!isset($response['data']['job_id'])) {
        throw new Exception('No job_id in FVU generation response');
      }

      $jobId = $response['data']['job_id'];
      $this->log('fvu_submit', 'success', "FVU job submitted: $jobId", json_encode(['txt_size' => strlen($txtContent), 'csi_size' => strlen($csiContent)]));

      return [
        'job_id' => $jobId,
        'status' => 'submitted'
      ];
    } catch (Exception $e) {
      $this->log('fvu_submit', 'failed', $e->getMessage());
      throw $e;
    }
  }

  /**
   * Poll FVU job status
   * Sandbox API is async - this checks the current status
   *
   * @param string $job_id Job ID from submitFVUGenerationJob()
   * @return array [
   *   'status' => 'pending|processing|succeeded|failed',
   *   'fvu_url' => string|null,
   *   'form27a_url' => string|null,
   *   'error' => string|null
   * ]
   * @throws Exception
   */
  public function pollFVUJobStatus($job_id) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/compliance/e-file/poll',
        ['job_id' => $job_id]
      );

      $status = $response['data']['status'] ?? 'unknown';
      $this->log('fvu_poll', 'success', "FVU job status: $status", json_encode(['job_id' => $job_id]));

      return [
        'status' => $status,
        'fvu_url' => $response['data']['fvu_url'] ?? null,
        'form27a_url' => $response['data']['form27a_url'] ?? null,
        'error' => $response['data']['error'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('fvu_poll', 'failed', $e->getMessage(), json_encode(['job_id' => $job_id]));
      throw $e;
    }
  }

  /**
   * Download FVU and Form 27A files from Sandbox
   *
   * @param string $fvuUrl Download URL for FVU file
   * @param string $form27aUrl Download URL for Form 27A
   * @return array ['fvu_content' => string, 'form27a_content' => string]
   * @throws Exception
   */
  public function downloadFVUFiles($fvuUrl, $form27aUrl) {
    try {
      $fvuContent = $this->downloadFile($fvuUrl);
      $form27aContent = $this->downloadFile($form27aUrl);

      $this->log('fvu_download', 'success', 'FVU and Form 27A files downloaded', json_encode([
        'fvu_size' => strlen($fvuContent),
        'form27a_size' => strlen($form27aContent)
      ]));

      return [
        'fvu_content' => $fvuContent,
        'form27a_content' => $form27aContent
      ];
    } catch (Exception $e) {
      $this->log('fvu_download', 'failed', $e->getMessage());
      throw $e;
    }
  }

  /**
   * Submit TDS return for e-filing to TIN Facilitation Center
   * This submits the FVU and Form 27A files for official filing
   * This is an async operation - use pollEFilingStatus() to track progress
   *
   * @param string $fvuZipPath Path to FVU ZIP file (or content)
   * @param string $form27aPath Path to Form 27A file (or content)
   * @return array ['job_id' => string, 'status' => 'submitted']
   * @throws Exception
   */
  public function submitEFilingJob($fvuZipPath, $form27aPath) {
    try {
      $this->ensureValidToken();

      // Load file contents if paths are provided
      $fvuContent = is_file($fvuZipPath) ? file_get_contents($fvuZipPath) : $fvuZipPath;
      $form27aContent = is_file($form27aPath) ? file_get_contents($form27aPath) : $form27aPath;

      $payload = [
        'fvu_zip' => base64_encode($fvuContent),
        'form27a' => base64_encode($form27aContent)
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/compliance/tin-fc/deductors/e-file/fvu',
        $payload
      );

      if (!isset($response['data']['job_id'])) {
        throw new Exception('No job_id in e-filing response');
      }

      $jobId = $response['data']['job_id'];
      $this->log('efile_submit', 'success', "E-filing job submitted: $jobId", json_encode([
        'fvu_size' => strlen($fvuContent),
        'form27a_size' => strlen($form27aContent)
      ]));

      return [
        'job_id' => $jobId,
        'status' => 'submitted'
      ];
    } catch (Exception $e) {
      $this->log('efile_submit', 'failed', $e->getMessage());
      throw $e;
    }
  }

  /**
   * Poll e-filing job status
   * Check if TDS return has been accepted by tax authority
   *
   * @param string $job_id Job ID from submitEFilingJob()
   * @return array [
   *   'status' => 'pending|processing|acknowledged|rejected|accepted',
   *   'ack_no' => string|null,
   *   'error' => string|null
   * ]
   * @throws Exception
   */
  public function pollEFilingStatus($job_id) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/compliance/e-file/poll',
        ['job_id' => $job_id]
      );

      $status = $response['data']['status'] ?? 'unknown';
      $ackNo = $response['data']['ack_no'] ?? null;

      $this->log('efile_poll', 'success', "E-filing job status: $status", json_encode(['job_id' => $job_id, 'ack_no' => $ackNo]));

      return [
        'status' => $status,
        'ack_no' => $ackNo,
        'error' => $response['data']['error'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('efile_poll', 'failed', $e->getMessage(), json_encode(['job_id' => $job_id]));
      throw $e;
    }
  }

  /**
   * Ensure access token is valid, refresh if needed
   *
   * @throws Exception
   */
  private function ensureValidToken() {
    if (!$this->accessToken || time() > $this->tokenExpiresAt - 300) {
      // Token expires in less than 5 minutes, refresh
      $this->authenticate();
    }
  }

  /**
   * Make HTTP request to Sandbox API
   *
   * @param string $method HTTP method (GET, POST, etc)
   * @param string $endpoint API endpoint path
   * @param array $data Request payload
   * @param array $headers Custom headers
   * @return array Decoded JSON response
   * @throws Exception
   */
  private function makeRequest($method, $endpoint, $data = [], $headers = []) {
    $ch = curl_init();

    $headers = $this->prepareHeaders($headers);

    curl_setopt_array($ch, [
      CURLOPT_URL => $this->baseUrl . $endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    if (!empty($data) && $method !== 'GET') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'GET' && !empty($data)) {
      // Append query params to URL
      $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
      $url .= '?' . http_build_query($data);
      curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint . '?' . http_build_query($data));
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

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception("Invalid JSON response: $response");
    }

    return $decoded;
  }

  /**
   * Make authenticated request with access token
   *
   * @param string $method HTTP method
   * @param string $endpoint API endpoint
   * @param array $data Request payload
   * @return array Decoded response
   * @throws Exception
   */
  private function makeAuthenticatedRequest($method, $endpoint, $data = []) {
    $headers = [
      'Authorization' => 'Bearer ' . $this->accessToken,
      'x-api-key' => $this->apiKey,
      'x-api-version' => '1.0',
      'Content-Type' => 'application/json'
    ];

    return $this->makeRequest($method, $endpoint, $data, $headers);
  }

  /**
   * Prepare HTTP headers
   *
   * @param array $customHeaders Additional headers to include
   * @return array Formatted header array
   */
  private function prepareHeaders($customHeaders = []) {
    $default = [
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
      'User-Agent' => 'TDS-AutoFile/1.0'
    ];

    $merged = array_merge($default, $customHeaders);
    $formatted = [];
    foreach ($merged as $key => $value) {
      $formatted[] = "$key: $value";
    }

    return $formatted;
  }

  /**
   * Download file from URL
   *
   * @param string $url URL to download from
   * @return string File content
   * @throws Exception
   */
  private function downloadFile($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_FOLLOWLOCATION => true,
    ]);

    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
      throw new Exception("Failed to download file: HTTP $httpCode");
    }

    return $content;
  }

  /**
   * ANALYTICS API - Submit TDS Potential Notice Analysis Job
   *
   * Analyzes TDS returns to identify compliance risks and potential tax notices
   *
   * @param string $tan TAN identifier (e.g., AHMA09719B)
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $form Form type (24Q, 26Q, 27Q)
   * @param string $fy Financial year (e.g., FY 2024-25)
   * @param mixed $form_content Form data (array or JSON string)
   * @return array Job details including job_id and status
   * @throws Exception
   */
  public function submitTDSAnalyticsJob($tan, $quarter, $form, $fy, $form_content) {
    try {
      $this->ensureValidToken();

      // Ensure form_content is base64 encoded
      if (is_array($form_content)) {
        $form_content = json_encode($form_content);
      } else if (!is_string($form_content)) {
        $form_content = (string)$form_content;
      }
      $encoded_content = base64_encode($form_content);

      // Submit form with all content in single request
      $payload = [
        '@entity' => 'in.co.sandbox.tds.analytics.potential_notices.job',
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => $form,
        'form_content' => $encoded_content
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/analytics/potential-notices',
        $payload
      );

      $jobId = $response['data']['job_id'] ?? null;
      $status = $response['data']['status'] ?? 'unknown';

      $this->log('analytics_submit_tds', 'success', "TDS Analytics job submitted: $jobId",
        json_encode(['tan' => $tan, 'form' => $form]),
        json_encode(['job_id' => $jobId, 'status' => $status]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => $form,
        'job_status' => $status,
        'created_at' => $response['data']['created_at'] ?? null,
        'error' => null
      ];
    } catch (Exception $e) {
      $this->log('analytics_submit_tds', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'form', 'fy')));
      return [
        'status' => 'failed',
        'error' => $e->getMessage(),
        'details' => 'Failed to submit TDS analytics job'
      ];
    }
  }

  /**
   * ANALYTICS API - Fetch TDS Analytics Jobs
   *
   * Search and retrieve historical TDS analytics jobs with pagination
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $form Form type (24Q, 26Q, 27Q)
   * @param string $fy Financial year
   * @param int $pageSize Number of records (max 50)
   * @param string|null $lastEvaluatedKey Pagination marker
   * @return array List of jobs with pagination info
   * @throws Exception
   */
  public function fetchTDSAnalyticsJobs($tan, $quarter, $form, $fy, $pageSize = 50, $lastEvaluatedKey = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.analytics.potential_notices.search.request',
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => $form,
        'page_size' => min($pageSize, 50)
      ];

      if ($lastEvaluatedKey) {
        $payload['last_evaluated_key'] = $lastEvaluatedKey;
      }

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/analytics/potential-notices/search',
        $payload
      );

      $items = $response['data']['items'] ?? [];
      $nextKey = $response['data']['last_evaluated_key'] ?? null;

      $this->log('analytics_fetch_tds', 'success', "Fetched " . count($items) . " TDS analytics jobs",
        json_encode(['tan' => $tan, 'form' => $form]));

      return [
        'status' => 'success',
        'count' => count($items),
        'jobs' => $items,
        'last_evaluated_key' => $nextKey,
        'has_more' => $nextKey ? true : false
      ];
    } catch (Exception $e) {
      $this->log('analytics_fetch_tds', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'form', 'fy')));
      throw $e;
    }
  }

  /**
   * ANALYTICS API - Poll TDS Analytics Job Status
   *
   * Check the status and results of a TDS potential notice analysis job
   *
   * @param string $job_id Job ID from analytics job submission
   * @return array Job status with risk assessment details
   * @throws Exception
   */
  public function pollTDSAnalyticsJob($job_id) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/analytics/potential-notices',
        ['job_id' => $job_id],
        'query'
      );

      $status = $response['data']['status'] ?? 'unknown';
      $riskLevel = $response['data']['risk_level'] ?? null;
      $riskScore = $response['data']['risk_score'] ?? 0;

      $this->log('analytics_poll_tds', 'success', "TDS Analytics job status: $status",
        json_encode(['job_id' => $job_id]),
        json_encode(['status' => $status, 'risk_level' => $riskLevel, 'risk_score' => $riskScore]));

      return [
        'status' => $status,
        'job_id' => $job_id,
        'form' => $response['data']['form'] ?? null,
        'quarter' => $response['data']['quarter'] ?? null,
        'financial_year' => $response['data']['financial_year'] ?? null,
        'tan' => $response['data']['tan'] ?? null,
        'risk_level' => $riskLevel,
        'risk_score' => $riskScore,
        'potential_notices_count' => $response['data']['potential_notices_count'] ?? 0,
        'report_url' => $response['data']['report_url'] ?? null,
        'issues' => $response['data']['issues'] ?? [],
        'error' => $response['data']['error'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('analytics_poll_tds', 'failed', $e->getMessage(),
        json_encode(['job_id' => $job_id]));
      throw $e;
    }
  }

  /**
   * ANALYTICS API - Submit TCS Potential Notice Analysis Job
   *
   * Analyzes TCS (Tax Collected at Source) returns for Form 27EQ
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $fy Financial year
   * @param mixed $form_content Form data (array or JSON string)
   * @return array Job details
   * @throws Exception
   */
  public function submitTCSAnalyticsJob($tan, $quarter, $fy, $form_content) {
    try {
      $this->ensureValidToken();

      // Ensure form_content is base64 encoded
      if (is_array($form_content)) {
        $form_content = json_encode($form_content);
      } else if (!is_string($form_content)) {
        $form_content = (string)$form_content;
      }
      $encoded_content = base64_encode($form_content);

      // Submit form with all content in single request
      $payload = [
        '@entity' => 'in.co.sandbox.tcs.analytics.potential_notices.job',
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => '27EQ',
        'form_content' => $encoded_content
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tcs/analytics/potential-notices',
        $payload
      );

      $jobId = $response['data']['job_id'] ?? null;
      $status = $response['data']['status'] ?? 'unknown';

      $this->log('analytics_submit_tcs', 'success', "TCS Analytics job submitted: $jobId",
        json_encode(['tan' => $tan]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => '27EQ',
        'job_status' => $status,
        'created_at' => $response['data']['created_at'] ?? null,
        'error' => null
      ];
    } catch (Exception $e) {
      $this->log('analytics_submit_tcs', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'fy')));
      throw $e;
    }
  }

  /**
   * ANALYTICS API - Fetch TCS Analytics Jobs
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter
   * @param string $fy Financial year
   * @param int $pageSize Number of records
   * @param string|null $lastEvaluatedKey Pagination marker
   * @return array List of TCS analytics jobs
   * @throws Exception
   */
  public function fetchTCSAnalyticsJobs($tan, $quarter, $fy, $pageSize = 50, $lastEvaluatedKey = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tcs.analytics.potential_notices.search.request',
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => '27EQ',
        'page_size' => min($pageSize, 50)
      ];

      if ($lastEvaluatedKey) {
        $payload['last_evaluated_key'] = $lastEvaluatedKey;
      }

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tcs/analytics/potential-notices/search',
        $payload
      );

      $items = $response['data']['items'] ?? [];

      $this->log('analytics_fetch_tcs', 'success', "Fetched " . count($items) . " TCS analytics jobs");

      return [
        'status' => 'success',
        'count' => count($items),
        'jobs' => $items,
        'last_evaluated_key' => $response['data']['last_evaluated_key'] ?? null,
        'has_more' => isset($response['data']['last_evaluated_key'])
      ];
    } catch (Exception $e) {
      $this->log('analytics_fetch_tcs', 'failed', $e->getMessage());
      throw $e;
    }
  }

  /**
   * ANALYTICS API - Poll TCS Analytics Job Status
   *
   * @param string $job_id Job ID
   * @return array Job status with risk details
   * @throws Exception
   */
  public function pollTCSAnalyticsJob($job_id) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tcs/analytics/potential-notices',
        ['job_id' => $job_id],
        'query'
      );

      $this->log('analytics_poll_tcs', 'success', "TCS Analytics job status: " . $response['data']['status']);

      return [
        'status' => $response['data']['status'] ?? 'unknown',
        'job_id' => $job_id,
        'tan' => $response['data']['tan'] ?? null,
        'quarter' => $response['data']['quarter'] ?? null,
        'financial_year' => $response['data']['financial_year'] ?? null,
        'form' => '27EQ',
        'risk_level' => $response['data']['risk_level'] ?? null,
        'risk_score' => $response['data']['risk_score'] ?? 0,
        'potential_notices_count' => $response['data']['potential_notices_count'] ?? 0,
        'report_url' => $response['data']['report_url'] ?? null,
        'error' => $response['data']['error'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('analytics_poll_tcs', 'failed', $e->getMessage());
      throw $e;
    }
  }

  /**
   * CALCULATOR API - Calculate TDS on Non-Salary Payments
   *
   * Calculates TDS (Tax Deducted at Source) on various non-salary payments
   * including contract services, interest, winnings, rent, etc.
   * This is a synchronous operation - returns calculation immediately.
   *
   * @param string $deducteeType Type of entity (individual, huf, company, firm, trust, etc.)
   * @param bool $isPanAvailable Is PAN available?
   * @param string $residentialStatus Residential status (resident or non_resident)
   * @param bool $is206abApplicable Is Section 206AB applicable?
   * @param bool $isPanOperative Is PAN operative?
   * @param string $natureOfPayment Type of payment (fees, interest, rent, winnings, etc.)
   * @param float $creditAmount Payment amount in INR
   * @param int $creditDate Payment date in milliseconds (EPOCH)
   * @return array Deduction rate, amount, section, threshold, due date, pan_status
   * @throws Exception
   */
  public function calculateNonSalaryTDS($deducteeType, $isPanAvailable, $residentialStatus, $is206abApplicable, $isPanOperative, $natureOfPayment, $creditAmount, $creditDate) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.calculator.non_salary.request',
        'deductee_type' => $deducteeType,
        'is_pan_available' => $isPanAvailable,
        'residential_status' => $residentialStatus,
        'is_206ab_applicable' => $is206abApplicable,
        'is_pan_operative' => $isPanOperative,
        'nature_of_payment' => $natureOfPayment,
        'credit_amount' => $creditAmount,
        'credit_date' => $creditDate
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/calculator/non-salary',
        $payload
      );

      $data = $response['data'] ?? [];

      $this->log('calculator_non_salary', 'success', "Non-salary TDS calculated",
        json_encode(['amount' => $creditAmount, 'nature' => $natureOfPayment]),
        json_encode(['rate' => $data['deduction_rate'] ?? 0, 'amount' => $data['deduction_amount'] ?? 0]));

      return [
        'status' => 'success',
        'deduction_rate' => $data['deduction_rate'] ?? 0,
        'deduction_amount' => $data['deduction_amount'] ?? 0,
        'section' => $data['section'] ?? null,
        'threshold' => $data['threshold'] ?? 0,
        'due_date' => $data['due_date'] ?? null,
        'pan_status' => $data['pan_status'] ?? 'unknown'
      ];
    } catch (Exception $e) {
      $this->log('calculator_non_salary', 'failed', $e->getMessage(),
        json_encode(compact('creditAmount', 'natureOfPayment')));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * CALCULATOR API - Calculate TCS on Transactions
   *
   * Calculates TCS (Tax Collected at Source) on various transactions
   * including goods sales, services, materials, scrap sales, e-commerce, etc.
   * This is a synchronous operation.
   *
   * @param string $collecteeType Type of entity making payment (individual, huf, company, firm, trust, etc.)
   * @param bool $isPanAvailable Is PAN available?
   * @param string $residentialStatus Residential status (resident or non_resident)
   * @param bool $is206ccaApplicable Is Section 206CCA applicable?
   * @param bool $isPanOperative Is PAN operative?
   * @param string $natureOfPayment Type of transaction (goods, services, material, scrap, e_commerce, etc.)
   * @param float $paymentAmount Transaction amount in INR (including GST)
   * @param int $paymentDate Transaction date in milliseconds (EPOCH)
   * @return array Collection rate, amount, section, threshold, due date, pan_status
   * @throws Exception
   */
  public function calculateTCS($collecteeType, $isPanAvailable, $residentialStatus, $is206ccaApplicable, $isPanOperative, $natureOfPayment, $paymentAmount, $paymentDate) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tcs.calculator.request',
        'collectee_type' => $collecteeType,
        'is_pan_available' => $isPanAvailable,
        'residential_status' => $residentialStatus,
        'is_206cca_applicable' => $is206ccaApplicable,
        'is_pan_operative' => $isPanOperative,
        'nature_of_payment' => $natureOfPayment,
        'payment_amount' => $paymentAmount,
        'payment_date' => $paymentDate
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tcs/calculator',
        $payload
      );

      $data = $response['data'] ?? [];

      $this->log('calculator_tcs', 'success', "TCS calculated",
        json_encode(['amount' => $paymentAmount, 'nature' => $natureOfPayment]),
        json_encode(['rate' => $data['collection_rate'] ?? 0, 'amount' => $data['collection_amount'] ?? 0]));

      return [
        'status' => 'success',
        'collection_rate' => $data['collection_rate'] ?? 0,
        'collection_amount' => $data['collection_amount'] ?? 0,
        'section' => $data['section'] ?? null,
        'threshold' => $data['threshold'] ?? 0,
        'due_date' => $data['due_date'] ?? null,
        'pan_status' => $data['pan_status'] ?? 'unknown'
      ];
    } catch (Exception $e) {
      $this->log('calculator_tcs', 'failed', $e->getMessage(),
        json_encode(compact('paymentAmount', 'natureOfPayment')));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * CALCULATOR API - Submit Salary TDS Calculation Job (Async)
   *
   * Submit bulk salary data for TDS calculation.
   * This is an asynchronous operation - use pollSalaryTDSJob() to check status.
   *
   * @param array $employees Array of employee data with employee_id, pan, gross_salary, month
   * @param string $financialYear Financial year (e.g., "2024-25")
   * @return array Job details including job_id and status
   * @throws Exception
   */
  public function submitSalaryTDSJob($employees, $financialYear) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.calculator.salary.request',
        'employees' => $employees
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/calculator/salary?financial_year=' . urlencode($financialYear),
        $payload
      );

      $jobId = $response['data']['job_id'] ?? null;
      $status = $response['data']['status'] ?? 'unknown';

      $this->log('calculator_salary_submit', 'success', "Salary TDS job submitted: $jobId",
        json_encode(['employee_count' => count($employees), 'fy' => $financialYear]),
        json_encode(['job_id' => $jobId, 'status' => $status]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'financial_year' => $financialYear,
        'employee_count' => count($employees),
        'job_status' => $status,
        'error' => null
      ];
    } catch (Exception $e) {
      $this->log('calculator_salary_submit', 'failed', $e->getMessage(),
        json_encode(['employee_count' => count($employees ?? []), 'fy' => $financialYear]));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * CALCULATOR API - Poll Salary TDS Job Status (Async)
   *
   * Check the status of a submitted salary TDS calculation job.
   * Job statuses: created → queued → processing → succeeded or failed
   *
   * @param string $jobId Job ID from submitSalaryTDSJob()
   * @param string $financialYear Financial year
   * @return array Job status with results URL when complete
   * @throws Exception
   */
  public function pollSalaryTDSJob($jobId, $financialYear) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/calculator/salary?job_id=' . urlencode($jobId) . '&financial_year=' . urlencode($financialYear),
        []
      );

      $status = $response['data']['status'] ?? 'unknown';
      $workbookUrl = $response['data']['workbook_url'] ?? null;
      $recordCount = $response['data']['record_count'] ?? 0;

      $this->log('calculator_salary_poll', 'success', "Salary TDS job status: $status",
        json_encode(['job_id' => $jobId]),
        json_encode(['status' => $status, 'records' => $recordCount]));

      return [
        'status' => $status,
        'job_id' => $jobId,
        'financial_year' => $financialYear,
        'workbook_url' => $workbookUrl,
        'record_count' => $recordCount
      ];
    } catch (Exception $e) {
      $this->log('calculator_salary_poll', 'failed', $e->getMessage(),
        json_encode(['job_id' => $jobId]));
      return [
        'status' => 'failed',
        'error' => $e->getMessage(),
        'job_id' => $jobId
      ];
    }
  }

  /**
   * CALCULATOR API - Calculate Salary TDS Synchronously
   *
   * Calculate TDS on salary immediately (no job submission/polling).
   * Returns base64-encoded Excel workbook with calculations.
   * This is a synchronous operation.
   *
   * @param array $employees Array of employee data with detailed salary/deductions
   * @param string $financialYear Financial year (e.g., "2024-25")
   * @return array Workbook data (base64-encoded Excel), record count, financial year
   * @throws Exception
   */
  public function calculateSalaryTDSSync($employees, $financialYear) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.calculator.salary.sync.request',
        'employees' => $employees
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/calculator/salary/sync?financial_year=' . urlencode($financialYear),
        $payload
      );

      $workbookData = $response['data']['workbook_data'] ?? null;
      $recordCount = $response['data']['record_count'] ?? 0;

      $this->log('calculator_salary_sync', 'success', "Salary TDS calculated (sync)",
        json_encode(['employee_count' => count($employees), 'fy' => $financialYear]),
        json_encode(['records' => $recordCount, 'workbook_size' => strlen($workbookData ?? '')]));

      return [
        'status' => 'success',
        'workbook_data' => $workbookData,
        'record_count' => $recordCount,
        'financial_year' => $financialYear,
        'employee_count' => count($employees)
      ];
    } catch (Exception $e) {
      $this->log('calculator_salary_sync', 'failed', $e->getMessage(),
        json_encode(['employee_count' => count($employees ?? []), 'fy' => $financialYear]));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * REPORTS API - Submit TDS Reports Job (Async)
   *
   * Create a TDS report generation job for forms 24Q, 26Q, or 27Q
   * This is an asynchronous operation - use pollTDSReportsJob() to check status
   *
   * @param string $tan TAN identifier (e.g., AHMA09719B)
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $form Form type (24Q, 26Q, 27Q)
   * @param string $financialYear Financial year (e.g., "FY 2024-25")
   * @param string|null $previousReceiptNumber Previous receipt number (optional)
   * @return array Job details including job_id and status
   * @throws Exception
   */
  public function submitTDSReportsJob($tan, $quarter, $form, $financialYear, $previousReceiptNumber = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.reports.request',
        'tan' => $tan,
        'quarter' => $quarter,
        'form' => $form,
        'financial_year' => $financialYear
      ];

      if ($previousReceiptNumber) {
        $payload['previous_receipt_number'] = $previousReceiptNumber;
      }

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/reports/txt',
        $payload
      );

      $jobId = $response['data']['job_id'] ?? null;
      $status = $response['data']['status'] ?? 'unknown';

      $this->log('reports_submit_tds', 'success', "TDS Reports job submitted: $jobId",
        json_encode(['tan' => $tan, 'form' => $form, 'quarter' => $quarter]),
        json_encode(['job_id' => $jobId, 'status' => $status]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'tan' => $tan,
        'quarter' => $quarter,
        'form' => $form,
        'financial_year' => $financialYear,
        'job_status' => $status,
        'json_url' => $response['data']['json_url'] ?? null,
        'created_at' => $response['data']['created_at'] ?? null,
        'error' => null
      ];
    } catch (Exception $e) {
      $this->log('reports_submit_tds', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'form', 'financialYear')));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * REPORTS API - Poll TDS Reports Job Status (Async)
   *
   * Check the status of a submitted TDS reports job
   * Job statuses: created → queued → processing → succeeded or failed
   *
   * @param string $jobId Job ID from submitTDSReportsJob()
   * @return array Job status with download URL when complete
   * @throws Exception
   */
  public function pollTDSReportsJob($jobId) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/reports/txt?job_id=' . urlencode($jobId),
        []
      );

      $status = $response['data']['status'] ?? 'unknown';
      $txtUrl = $response['data']['txt_url'] ?? null;
      $validationReportUrl = $response['data']['validation_report_url'] ?? null;

      $this->log('reports_poll_tds', 'success', "TDS Reports job status: $status",
        json_encode(['job_id' => $jobId]),
        json_encode(['status' => $status]));

      return [
        'status' => $status,
        'job_id' => $jobId,
        'tan' => $response['data']['tan'] ?? null,
        'quarter' => $response['data']['quarter'] ?? null,
        'form' => $response['data']['form'] ?? null,
        'financial_year' => $response['data']['financial_year'] ?? null,
        'txt_url' => $txtUrl,
        'validation_report_url' => $validationReportUrl,
        'created_at' => $response['data']['created_at'] ?? null,
        'updated_at' => $response['data']['updated_at'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('reports_poll_tds', 'failed', $e->getMessage(),
        json_encode(['job_id' => $jobId]));
      return [
        'status' => 'failed',
        'error' => $e->getMessage(),
        'job_id' => $jobId
      ];
    }
  }

  /**
   * REPORTS API - Search TDS Reports Jobs
   *
   * Search and retrieve historical TDS reports jobs with pagination
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter (Q1-Q4)
   * @param string $form Form type (24Q, 26Q, 27Q)
   * @param string $financialYear Financial year
   * @param int $pageSize Number of records (max 50)
   * @param string|null $lastEvaluatedKey Pagination marker
   * @param int|null $fromDate Start date (milliseconds, optional)
   * @param int|null $toDate End date (milliseconds, optional)
   * @return array List of jobs with pagination info
   * @throws Exception
   */
  public function searchTDSReportsJobs($tan, $quarter, $form, $financialYear, $pageSize = 50, $lastEvaluatedKey = null, $fromDate = null, $toDate = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.reports.jobs.search',
        'tan' => $tan,
        'quarter' => $quarter,
        'form' => $form,
        'financial_year' => $financialYear,
        'page_size' => min($pageSize, 50)
      ];

      if ($lastEvaluatedKey) {
        $payload['last_evaluated_key'] = $lastEvaluatedKey;
      }

      if ($fromDate) {
        $payload['from_date'] = $fromDate;
      }

      if ($toDate) {
        $payload['to_date'] = $toDate;
      }

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/reports/txt/search',
        $payload
      );

      $items = $response['data']['items'] ?? [];
      $nextKey = $response['data']['last_evaluated_key'] ?? null;

      $this->log('reports_search_tds', 'success', "Found " . count($items) . " TDS reports jobs",
        json_encode(['tan' => $tan, 'form' => $form]));

      return [
        'status' => 'success',
        'count' => count($items),
        'jobs' => $items,
        'last_evaluated_key' => $nextKey,
        'has_more' => $nextKey ? true : false
      ];
    } catch (Exception $e) {
      $this->log('reports_search_tds', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'form', 'financialYear')));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * REPORTS API - Submit TCS Reports Job (Async)
   *
   * Create a TCS report generation job (Form 27EQ)
   * This is an asynchronous operation - use pollTCSReportsJob() to check status
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $financialYear Financial year (e.g., "FY 2024-25")
   * @param string|null $previousReceiptNumber Previous receipt number (optional)
   * @return array Job details including job_id and status
   * @throws Exception
   */
  public function submitTCSReportsJob($tan, $quarter, $financialYear, $previousReceiptNumber = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tcs.reports.request',
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $financialYear
      ];

      if ($previousReceiptNumber) {
        $payload['previous_receipt_number'] = $previousReceiptNumber;
      }

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tcs/reports/txt',
        $payload
      );

      $jobId = $response['data']['job_id'] ?? null;
      $status = $response['data']['status'] ?? 'unknown';

      $this->log('reports_submit_tcs', 'success', "TCS Reports job submitted: $jobId",
        json_encode(['tan' => $tan, 'quarter' => $quarter]),
        json_encode(['job_id' => $jobId, 'status' => $status]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $financialYear,
        'job_status' => $status,
        'json_url' => $response['data']['json_url'] ?? null,
        'created_at' => $response['data']['created_at'] ?? null,
        'error' => null
      ];
    } catch (Exception $e) {
      $this->log('reports_submit_tcs', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'financialYear')));
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * REPORTS API - Poll TCS Reports Job Status (Async)
   *
   * Check the status of a submitted TCS reports job
   *
   * @param string $jobId Job ID from submitTCSReportsJob()
   * @return array Job status with download URL when complete
   * @throws Exception
   */
  public function pollTCSReportsJob($jobId) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tcs/reports/txt?job_id=' . urlencode($jobId),
        []
      );

      $status = $response['data']['status'] ?? 'unknown';
      $txtUrl = $response['data']['txt_url'] ?? null;
      $validationReportUrl = $response['data']['validation_report_url'] ?? null;

      $this->log('reports_poll_tcs', 'success', "TCS Reports job status: $status",
        json_encode(['job_id' => $jobId]),
        json_encode(['status' => $status]));

      return [
        'status' => $status,
        'job_id' => $jobId,
        'tan' => $response['data']['tan'] ?? null,
        'quarter' => $response['data']['quarter'] ?? null,
        'financial_year' => $response['data']['financial_year'] ?? null,
        'txt_url' => $txtUrl,
        'validation_report_url' => $validationReportUrl,
        'created_at' => $response['data']['created_at'] ?? null,
        'updated_at' => $response['data']['updated_at'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('reports_poll_tcs', 'failed', $e->getMessage(),
        json_encode(['job_id' => $jobId]));
      return [
        'status' => 'failed',
        'error' => $e->getMessage(),
        'job_id' => $jobId
      ];
    }
  }

  /**
   * REPORTS API - Search TCS Reports Jobs
   *
   * Search and retrieve historical TCS reports jobs with pagination
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter (Q1-Q4)
   * @param string $financialYear Financial year
   * @param int $pageSize Number of records (max 50)
   * @param string|null $lastEvaluatedKey Pagination marker
   * @param int|null $fromDate Start date (milliseconds, optional)
   * @param int|null $toDate End date (milliseconds, optional)
   * @return array List of jobs with pagination info
   * @throws Exception
   */
  public function searchTCSReportsJobs($tan, $quarter, $financialYear, $pageSize = 50, $lastEvaluatedKey = null, $fromDate = null, $toDate = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tcs.reports.jobs.search',
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $financialYear,
        'page_size' => min($pageSize, 50)
      ];

      if ($lastEvaluatedKey) {
        $payload['last_evaluated_key'] = $lastEvaluatedKey;
      }

      if ($fromDate) {
        $payload['from_date'] = $fromDate;
      }

      if ($toDate) {
        $payload['to_date'] = $toDate;
      }

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tcs/reports/txt/search',
        $payload
      );

      $items = $response['data']['items'] ?? [];
      $nextKey = $response['data']['last_evaluated_key'] ?? null;

      $this->log('reports_search_tcs', 'success', "Found " . count($items) . " TCS reports jobs");

      return [
        'status' => 'success',
        'count' => count($items),
        'jobs' => $items,
        'last_evaluated_key' => $nextKey,
        'has_more' => $nextKey ? true : false
      ];
    } catch (Exception $e) {
      $this->log('reports_search_tcs', 'failed', $e->getMessage());
      return [
        'status' => 'failed',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * Log API activity to database
   *
   * @param string $stage Processing stage
   * @param string $status Status (success/failed)
   * @param string $message Log message
   * @param string $request Request data
   * @param string $response Response data
   */
  private function log($stage, $status, $message, $request = null, $response = null) {
    if ($this->logCallback && is_callable($this->logCallback)) {
      call_user_func($this->logCallback, $stage, $status, $message, $request, $response);
    }
  }
}
?>
