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
   */
  public function __construct($firm_id, PDO $pdo, $logCallback = null) {
    $this->pdo = $pdo;
    $this->firmId = $firm_id;
    $this->logCallback = $logCallback;

    // Fetch API credentials from database
    $stmt = $pdo->prepare('SELECT * FROM api_credentials WHERE firm_id=? AND is_active=1');
    $stmt->execute([$firm_id]);
    $cred = $stmt->fetch();

    if (!$cred) {
      throw new Exception("No active API credentials found for firm $firm_id");
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
   * Submit Potential Notice Analysis job to Sandbox Analytics API
   * Initiates analysis to check and avoid potential notices towards TDS return
   *
   * @param string $tan TAN identifier (e.g., AHMA09719B)
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $form Form type (24Q, 26Q, 27Q)
   * @param string $fy Financial year (e.g., FY 2024-25)
   * @return array Job details including job_id, status, and json_url
   * @throws Exception
   */
  public function submitAnalyticsJob($tan, $quarter, $form, $fy) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.analytics.potential_notice.request',
        'tan' => $tan,
        'quarter' => $quarter,
        'form' => $form,
        'financial_year' => $fy
      ];

      $response = $this->makeAuthenticatedRequest(
        'POST',
        '/tds/analytics/potential-notices',
        $payload
      );

      $jobId = $response['data']['job_id'] ?? null;
      $status = $response['data']['status'] ?? 'unknown';

      $this->log('analytics_submit', 'success', "Analytics job submitted: $jobId", json_encode($payload), json_encode(['job_id' => $jobId, 'status' => $status]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'tan' => $response['data']['tan'] ?? $tan,
        'quarter' => $response['data']['quarter'] ?? $quarter,
        'financial_year' => $response['data']['financial_year'] ?? $fy,
        'form' => $response['data']['form'] ?? $form,
        'job_status' => $status,
        'created_at' => $response['data']['created_at'] ?? null,
        'json_url' => $response['data']['json_url'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('analytics_submit', 'failed', $e->getMessage(), json_encode(compact('tan', 'quarter', 'form', 'fy')));
      throw $e;
    }
  }

  /**
   * Fetch all Potential Notice Analysis jobs from Sandbox Analytics API
   * Retrieves list of analytics jobs with filtering and pagination
   *
   * @param string $tan TAN identifier
   * @param string $quarter Quarter (Q1, Q2, Q3, Q4)
   * @param string $form Form type (24Q, 26Q, 27Q)
   * @param string $fy Financial year (e.g., FY 2024-25)
   * @param int $pageSize Number of records (max 50)
   * @param string|null $lastEvaluatedKey Pagination marker
   * @return array List of jobs with pagination info
   * @throws Exception
   */
  public function fetchAnalyticsJobs($tan, $quarter, $form, $fy, $pageSize = 50, $lastEvaluatedKey = null) {
    try {
      $this->ensureValidToken();

      $payload = [
        '@entity' => 'in.co.sandbox.tds.analytics.potential_notice.jobs.search',
        'tan' => $tan,
        'quarter' => $quarter,
        'form' => $form,
        'financial_year' => $fy,
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

      $this->log('analytics_fetch', 'success', "Fetched " . count($items) . " analytics jobs", json_encode(['tan' => $tan, 'form' => $form]));

      return [
        'status' => 'success',
        'count' => count($items),
        'jobs' => $items,
        'last_evaluated_key' => $nextKey,
        'has_more' => $nextKey ? true : false
      ];
    } catch (Exception $e) {
      $this->log('analytics_fetch', 'failed', $e->getMessage(), json_encode(compact('tan', 'quarter', 'form', 'fy')));
      throw $e;
    }
  }

  /**
   * Poll Potential Notice Analysis job from Sandbox Analytics API
   * Checks the results of Potential Notice analysis for compliance risks
   *
   * @param string $job_id Job ID from analytics-analysis request
   * @return array Job status with potential notice report details
   * @throws Exception
   */
  public function pollAnalyticsJob($job_id) {
    try {
      $this->ensureValidToken();

      $response = $this->makeAuthenticatedRequest(
        'GET',
        '/tds/analytics/potential-notices',
        ['job_id' => $job_id]
      );

      $status = $response['data']['status'] ?? 'unknown';
      $reportUrl = $response['data']['potential_notice_report_url'] ?? null;

      $this->log('analytics_poll', 'success', "Analytics job status: $status", json_encode(['job_id' => $job_id]), json_encode(['report_url' => $reportUrl]));

      return [
        'status' => $status,
        'job_id' => $job_id,
        'form' => $response['data']['form'] ?? null,
        'quarter' => $response['data']['quarter'] ?? null,
        'financial_year' => $response['data']['financial_year'] ?? null,
        'tan' => $response['data']['tan'] ?? null,
        'report_url' => $reportUrl,
        'error' => $response['data']['error'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('analytics_poll', 'failed', $e->getMessage(), json_encode(['job_id' => $job_id]));
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
      'Authorization' => $this->accessToken,
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
