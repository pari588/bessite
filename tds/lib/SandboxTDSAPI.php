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
      }
      $encoded_content = base64_encode($form_content);

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
        'json_url' => $response['data']['json_url'] ?? null
      ];
    } catch (Exception $e) {
      $this->log('analytics_submit_tds', 'failed', $e->getMessage(),
        json_encode(compact('tan', 'quarter', 'form', 'fy')));
      throw $e;
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

      if (is_array($form_content)) {
        $form_content = json_encode($form_content);
      }
      $encoded_content = base64_encode($form_content);

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

      $this->log('analytics_submit_tcs', 'success', "TCS Analytics job submitted: $jobId",
        json_encode(['tan' => $tan]));

      return [
        'status' => 'success',
        'job_id' => $jobId,
        'tan' => $tan,
        'quarter' => $quarter,
        'financial_year' => $fy,
        'form' => '27EQ',
        'job_status' => $response['data']['status'] ?? 'unknown',
        'json_url' => $response['data']['json_url'] ?? null
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
