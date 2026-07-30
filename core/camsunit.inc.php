<?php
/**
 * Camsunit Biometric API Integration
 *
 * API Documentation: https://camsbiometrics.com/application/biometric-web-api.html
 *
 * This class handles:
 * - Loading punch logs from biometric device
 * - Adding/removing users to/from device
 * - Decrypting encrypted API responses
 *
 * TEST MODE: When CAMS_TEST_MODE is true, returns mock data instead of calling API
 */

class CamsunitAPI {
    private $apiUrl;
    private $authToken;
    private $securityKey;
    private $stgid;
    private $testMode;
    private $lastError;
    private $lastResponse;

    /**
     * Constructor - Initialize with config values
     */
    public function __construct() {
        $this->apiUrl = defined('CAMS_API_URL') ? CAMS_API_URL : 'https://api.camsunit.com';
        $this->authToken = defined('CAMS_AUTH_TOKEN') ? CAMS_AUTH_TOKEN : '';
        $this->securityKey = defined('CAMS_SECURITY_KEY') ? CAMS_SECURITY_KEY : '';
        $this->stgid = defined('CAMS_STGID') ? CAMS_STGID : '';
        $this->testMode = defined('CAMS_TEST_MODE') ? CAMS_TEST_MODE : true; // Default to test mode
        $this->lastError = null;
        $this->lastResponse = null;
    }

    /**
     * Check if running in test mode
     */
    public function isTestMode() {
        return $this->testMode;
    }

    /**
     * Set test mode on/off
     */
    public function setTestMode($mode) {
        $this->testMode = (bool) $mode;
    }

    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Get last API response
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }

    /**
     * Load punch logs from device
     *
     * @param string $fromDate Start date (YYYY-MM-DD)
     * @param string $toDate End date (YYYY-MM-DD)
     * @param int $offset Pagination offset (0, 300, 600, etc.)
     * @return array|false Array of punch records or false on error
     */
    public function loadPunchLog($fromDate, $toDate, $offset = 0) {
        // Test mode - return mock data
        if ($this->testMode) {
            return $this->getMockPunchLogs($fromDate, $toDate);
        }

        // Validate STGID
        if (empty($this->stgid)) {
            $this->lastError = 'CAMS_STGID not configured';
            return false;
        }

        // Build API request
        $endpoint = '/api/fetch';
        $params = [
            'stgid' => $this->stgid,
            'sdate' => $fromDate,
            'edate' => $toDate,
            'limit' => 300,
            'offset' => $offset
        ];

        $response = $this->makeRequest($endpoint, $params);

        if ($response === false) {
            return false;
        }

        // Parse response
        return $this->parsePunchLogResponse($response);
    }

    /**
     * Add user to biometric device
     *
     * @param string $userId Unique user ID for device
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @return bool Success/failure
     */
    public function addUser($userId, $firstName, $lastName) {
        // Test mode - simulate success
        if ($this->testMode) {
            $this->lastResponse = [
                'success' => true,
                'message' => '[TEST MODE] User added successfully',
                'userId' => $userId
            ];
            return true;
        }

        if (empty($this->stgid)) {
            $this->lastError = 'CAMS_STGID not configured';
            return false;
        }

        $endpoint = '/api/adduser';
        $params = [
            'stgid' => $this->stgid,
            'user_id' => $userId,
            'first_name' => $firstName,
            'last_name' => $lastName
        ];

        $response = $this->makeRequest($endpoint, $params, 'POST');

        if ($response === false) {
            return false;
        }

        return isset($response['status']) && $response['status'] === 'success';
    }

    /**
     * Delete user from biometric device
     *
     * @param string $userId User ID to remove
     * @return bool Success/failure
     */
    public function deleteUser($userId) {
        // Test mode - simulate success
        if ($this->testMode) {
            $this->lastResponse = [
                'success' => true,
                'message' => '[TEST MODE] User deleted successfully',
                'userId' => $userId
            ];
            return true;
        }

        if (empty($this->stgid)) {
            $this->lastError = 'CAMS_STGID not configured';
            return false;
        }

        $endpoint = '/api/deleteuser';
        $params = [
            'stgid' => $this->stgid,
            'user_id' => $userId
        ];

        $response = $this->makeRequest($endpoint, $params, 'POST');

        if ($response === false) {
            return false;
        }

        return isset($response['status']) && $response['status'] === 'success';
    }

    /**
     * Get device status
     *
     * @return array|false Device info or false on error
     */
    public function getDeviceStatus() {
        // Test mode
        if ($this->testMode) {
            return [
                'status' => 'online',
                'device_name' => 'TEST_DEVICE_001',
                'stgid' => 'TEST_STGID',
                'last_sync' => date('Y-m-d H:i:s'),
                'user_count' => 25,
                'punch_count_today' => 48,
                'test_mode' => true
            ];
        }

        if (empty($this->stgid)) {
            $this->lastError = 'CAMS_STGID not configured';
            return false;
        }

        $endpoint = '/api/status';
        $params = [
            'stgid' => $this->stgid
        ];

        return $this->makeRequest($endpoint, $params);
    }

    /**
     * Sync punch logs to database
     *
     * @param string $fromDate Start date
     * @param string $toDate End date
     * @return array Result with counts
     */
    public function syncPunchLogsToDatabase($fromDate, $toDate) {
        global $db;

        $result = [
            'success' => false,
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Fetch punch logs
        $offset = 0;
        $allLogs = [];

        do {
            $logs = $this->loadPunchLog($fromDate, $toDate, $offset);

            if ($logs === false) {
                $result['errors'][] = $this->lastError;
                break;
            }

            $allLogs = array_merge($allLogs, $logs);
            $offset += 300;

        } while (count($logs) >= 300);

        // Process each punch log
        foreach ($allLogs as $log) {
            $result['processed']++;

            // Find employee by biometricID
            $biometricId = $log['user_id'];
            $punchTime = $log['punch_time'];
            $punchDate = date('Y-m-d', strtotime($punchTime));
            $punchTimeOnly = date('H:i:s', strtotime($punchTime));

            // Look up user by biometricID
            $sql = "SELECT userID, displayName, workStartTime, workEndTime, lateGraceMinutes
                    FROM mx_x_admin_user
                    WHERE biometricID = ? AND status = 1";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $biometricId);
            $stmt->execute();
            $userResult = $stmt->get_result();
            $user = $userResult->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $result['skipped']++;
                $result['errors'][] = "No employee found with biometricID: $biometricId";
                continue;
            }

            $userId = $user['userID'];

            // Get default work times from HR settings if not set per employee
            $scheduledIn = $user['workStartTime'] ?: '09:00:00';
            $scheduledOut = $user['workEndTime'] ?: '18:00:00';
            $graceMinutes = $user['lateGraceMinutes'] ?: 15;

            // Check if attendance record exists for this date
            $sql = "SELECT attendanceID, checkIn, checkOut FROM mx_attendance
                    WHERE userID = ? AND attendanceDate = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("is", $userId, $punchDate);
            $stmt->execute();
            $attResult = $stmt->get_result();
            $existingAtt = $attResult->fetch_assoc();
            $stmt->close();

            if ($existingAtt) {
                // Update existing record
                $attendanceId = $existingAtt['attendanceID'];
                $checkIn = $existingAtt['checkIn'];
                $checkOut = $existingAtt['checkOut'];

                // Determine if this is check-in or check-out
                if (empty($checkIn)) {
                    // First punch - check-in
                    $this->updateAttendanceCheckIn($attendanceId, $punchTime, $scheduledIn, $graceMinutes, $log);
                } else if (empty($checkOut) || strtotime($punchTime) > strtotime($checkOut)) {
                    // Later punch - check-out (take the latest)
                    $this->updateAttendanceCheckOut($attendanceId, $punchTime, $scheduledOut, $log);
                }

                $result['updated']++;
            } else {
                // Create new attendance record
                $this->createAttendanceRecord($userId, $punchDate, $punchTime, $scheduledIn, $scheduledOut, $graceMinutes, $log);
                $result['inserted']++;
            }
        }

        $result['success'] = empty($result['errors']) || $result['processed'] > 0;
        return $result;
    }

    /**
     * Update attendance check-in time
     */
    private function updateAttendanceCheckIn($attendanceId, $checkInTime, $scheduledIn, $graceMinutes, $rawLog) {
        global $db;

        $checkInTimeOnly = date('H:i:s', strtotime($checkInTime));
        $scheduledInTimestamp = strtotime($scheduledIn);
        $actualInTimestamp = strtotime($checkInTimeOnly);
        $graceTimestamp = $scheduledInTimestamp + ($graceMinutes * 60);

        $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
        $lateMinutes = $isLate ? round(($actualInTimestamp - $scheduledInTimestamp) / 60) : 0;

        $biometricRaw = json_encode($rawLog);

        $sql = "UPDATE mx_attendance SET
                checkIn = ?,
                isLate = ?,
                lateMinutes = ?,
                biometricRaw = ?,
                syncedAt = NOW(),
                source = 'biometric'
                WHERE attendanceID = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("siisi", $checkInTime, $isLate, $lateMinutes, $biometricRaw, $attendanceId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Update attendance check-out time
     */
    private function updateAttendanceCheckOut($attendanceId, $checkOutTime, $scheduledOut, $rawLog) {
        global $db;

        $checkOutTimeOnly = date('H:i:s', strtotime($checkOutTime));
        $scheduledOutTimestamp = strtotime($scheduledOut);
        $actualOutTimestamp = strtotime($checkOutTimeOnly);

        $isEarlyCheckout = ($actualOutTimestamp < $scheduledOutTimestamp) ? 1 : 0;
        $earlyMinutes = $isEarlyCheckout ? round(($scheduledOutTimestamp - $actualOutTimestamp) / 60) : 0;

        // Calculate working hours
        $sql = "SELECT checkIn FROM mx_attendance WHERE attendanceID = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $attendanceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $checkIn = $row['checkIn'];
        $workingHours = 0;
        if ($checkIn) {
            $workingHours = round((strtotime($checkOutTime) - strtotime($checkIn)) / 3600, 2);
        }

        $biometricRaw = json_encode($rawLog);

        $sql = "UPDATE mx_attendance SET
                checkOut = ?,
                isEarlyCheckout = ?,
                earlyMinutes = ?,
                workingHours = ?,
                biometricRaw = CONCAT(IFNULL(biometricRaw, ''), ?, '\n'),
                syncedAt = NOW()
                WHERE attendanceID = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("siidsi", $checkOutTime, $isEarlyCheckout, $earlyMinutes, $workingHours, $biometricRaw, $attendanceId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Create new attendance record
     */
    private function createAttendanceRecord($userId, $attendanceDate, $checkInTime, $scheduledIn, $scheduledOut, $graceMinutes, $rawLog) {
        global $db;

        $checkInTimeOnly = date('H:i:s', strtotime($checkInTime));
        $scheduledInTimestamp = strtotime($scheduledIn);
        $actualInTimestamp = strtotime($checkInTimeOnly);
        $graceTimestamp = $scheduledInTimestamp + ($graceMinutes * 60);

        $isLate = ($actualInTimestamp > $graceTimestamp) ? 1 : 0;
        $lateMinutes = $isLate ? round(($actualInTimestamp - $scheduledInTimestamp) / 60) : 0;

        $biometricRaw = json_encode($rawLog);

        $sql = "INSERT INTO mx_attendance (
                    userID, attendanceDate, scheduledIn, scheduledOut,
                    checkIn, isLate, lateMinutes,
                    attendanceStatus, source, biometricRaw, syncedAt, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'present', 'biometric', ?, NOW(), 1)";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("issssiss",
            $userId, $attendanceDate, $scheduledIn, $scheduledOut,
            $checkInTime, $isLate, $lateMinutes, $biometricRaw
        );
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Make HTTP request to Camsunit API
     *
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param string $method HTTP method (GET or POST)
     * @return array|false Decoded response or false on error
     */
    private function makeRequest($endpoint, $params = [], $method = 'GET') {
        $url = $this->apiUrl . $endpoint;

        // Add auth token to params
        $params['auth_token'] = $this->authToken;

        $ch = curl_init();

        if ($method === 'GET') {
            $url .= '?' . http_build_query($params);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = "CURL Error: $error";
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = "HTTP Error: $httpCode";
            return false;
        }

        // Check if response is encrypted
        $decoded = json_decode($response, true);

        if ($decoded === null) {
            // Try to decrypt
            $decrypted = $this->decryptResponse($response);
            if ($decrypted === false) {
                $this->lastError = "Failed to parse response";
                return false;
            }
            $decoded = json_decode($decrypted, true);
        }

        $this->lastResponse = $decoded;

        // Check for API errors
        if (isset($decoded['error'])) {
            $this->lastError = $decoded['error'];
            return false;
        }

        return $decoded;
    }

    /**
     * Decrypt AES-256 encrypted response
     *
     * @param string $encrypted Base64 encoded encrypted data
     * @return string|false Decrypted string or false on error
     */
    private function decryptResponse($encrypted) {
        if (empty($this->securityKey)) {
            return false;
        }

        $data = base64_decode($encrypted);
        if ($data === false) {
            return false;
        }

        // AES-256-CBC requires 32-byte key and 16-byte IV
        $key = substr(hash('sha256', $this->securityKey, true), 0, 32);

        // IV is typically prepended to encrypted data
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encryptedData, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted;
    }

    /**
     * Parse punch log response into standardized format
     */
    private function parsePunchLogResponse($response) {
        $logs = [];

        if (!isset($response['data']) || !is_array($response['data'])) {
            return $logs;
        }

        foreach ($response['data'] as $record) {
            $logs[] = [
                'user_id' => $record['user_id'] ?? $record['userid'] ?? '',
                'punch_time' => $record['punch_time'] ?? $record['punchtime'] ?? '',
                'punch_type' => $record['punch_type'] ?? 'auto', // in/out/auto
                'device_id' => $record['device_id'] ?? $this->stgid,
                'raw' => $record
            ];
        }

        return $logs;
    }

    /**
     * Generate mock punch logs for test mode
     */
    private function getMockPunchLogs($fromDate, $toDate) {
        $logs = [];

        // Get employees with biometricID from database
        global $db;
        if (!$db) {
            // Return static mock data if no DB connection
            return $this->getStaticMockData($fromDate, $toDate);
        }

        $sql = "SELECT userID, biometricID, displayName, workStartTime, workEndTime
                FROM mx_x_admin_user
                WHERE biometricID IS NOT NULL AND biometricID != '' AND status = 1";
        $result = $db->query($sql);

        if (!$result || $result->num_rows == 0) {
            return $this->getStaticMockData($fromDate, $toDate);
        }

        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }

        // Generate punch logs for each day in range
        $startDate = new DateTime($fromDate);
        $endDate = new DateTime($toDate);
        $endDate->modify('+1 day');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayOfWeek = $date->format('N'); // 1=Mon, 7=Sun

            // Skip weekends
            if ($dayOfWeek >= 6) {
                continue;
            }

            foreach ($employees as $emp) {
                // 90% attendance rate in test mode
                if (rand(1, 100) > 90) {
                    continue; // Skip this day (absent)
                }

                $workStart = $emp['workStartTime'] ?: '09:00:00';
                $workEnd = $emp['workEndTime'] ?: '18:00:00';

                // Randomize check-in time (some early, some on time, some late)
                $variation = rand(-15, 30); // -15 to +30 minutes
                $checkInTime = date('H:i:s', strtotime($workStart) + ($variation * 60));

                // Randomize check-out time
                $outVariation = rand(-30, 60); // -30 to +60 minutes
                $checkOutTime = date('H:i:s', strtotime($workEnd) + ($outVariation * 60));

                // Check-in punch
                $logs[] = [
                    'user_id' => $emp['biometricID'],
                    'punch_time' => $dateStr . ' ' . $checkInTime,
                    'punch_type' => 'in',
                    'device_id' => 'TEST_DEVICE_001',
                    'raw' => [
                        'test_mode' => true,
                        'employee_name' => $emp['displayName']
                    ]
                ];

                // Check-out punch
                $logs[] = [
                    'user_id' => $emp['biometricID'],
                    'punch_time' => $dateStr . ' ' . $checkOutTime,
                    'punch_type' => 'out',
                    'device_id' => 'TEST_DEVICE_001',
                    'raw' => [
                        'test_mode' => true,
                        'employee_name' => $emp['displayName']
                    ]
                ];
            }
        }

        return $logs;
    }

    /**
     * Static mock data when no database connection
     */
    private function getStaticMockData($fromDate, $toDate) {
        $mockEmployees = [
            ['id' => 'EMP001', 'name' => 'Test Employee 1'],
            ['id' => 'EMP002', 'name' => 'Test Employee 2'],
            ['id' => 'EMP003', 'name' => 'Test Employee 3']
        ];

        $logs = [];
        $startDate = new DateTime($fromDate);
        $endDate = new DateTime($toDate);
        $endDate->modify('+1 day');

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $dayOfWeek = $date->format('N');

            if ($dayOfWeek >= 6) continue;

            foreach ($mockEmployees as $emp) {
                $checkIn = sprintf('%02d:%02d:00', rand(8, 9), rand(0, 59));
                $checkOut = sprintf('%02d:%02d:00', rand(17, 19), rand(0, 59));

                $logs[] = [
                    'user_id' => $emp['id'],
                    'punch_time' => $dateStr . ' ' . $checkIn,
                    'punch_type' => 'in',
                    'device_id' => 'TEST_DEVICE_001',
                    'raw' => ['test_mode' => true, 'employee_name' => $emp['name']]
                ];

                $logs[] = [
                    'user_id' => $emp['id'],
                    'punch_time' => $dateStr . ' ' . $checkOut,
                    'punch_type' => 'out',
                    'device_id' => 'TEST_DEVICE_001',
                    'raw' => ['test_mode' => true, 'employee_name' => $emp['name']]
                ];
            }
        }

        return $logs;
    }

    /**
     * Sync all employees to Camsunit device
     * Adds employees who have biometricID set but may not be on device yet
     *
     * @return array Result with counts
     */
    public function syncEmployeesToDevice() {
        global $db;

        $result = [
            'success' => false,
            'total' => 0,
            'added' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
            'test_mode' => $this->testMode
        ];

        // Get all active employees with biometricID
        $sql = "SELECT userID, biometricID, userName, displayName, employeeCode
                FROM mx_x_admin_user
                WHERE biometricID IS NOT NULL AND biometricID != '' AND status = 1";
        $dbResult = $db->query($sql);

        if (!$dbResult) {
            $result['errors'][] = 'Failed to fetch employees from database';
            return $result;
        }

        while ($emp = $dbResult->fetch_assoc()) {
            $result['total']++;

            // Parse name into first/last
            $nameParts = explode(' ', trim($emp['displayName'] ?: $emp['userName']), 2);
            $firstName = $nameParts[0] ?? 'Employee';
            $lastName = $nameParts[1] ?? $emp['employeeCode'] ?? '';

            $addResult = $this->addUser($emp['biometricID'], $firstName, $lastName);

            if ($addResult) {
                $result['added']++;
            } else {
                $result['failed']++;
                $result['errors'][] = "Failed to add {$emp['displayName']} (ID: {$emp['biometricID']}): " . $this->lastError;
            }
        }

        $result['success'] = $result['failed'] == 0;
        return $result;
    }

    /**
     * Get list of users registered on Camsunit device
     *
     * @return array|false List of device users or false on error
     */
    public function getDeviceUsers() {
        // Test mode
        if ($this->testMode) {
            global $db;
            $users = [];

            // Return employees with biometricID as mock device users
            $sql = "SELECT biometricID as user_id, displayName as name, employeeCode
                    FROM mx_x_admin_user
                    WHERE biometricID IS NOT NULL AND biometricID != '' AND status = 1";
            $result = $db->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $users[] = [
                        'user_id' => $row['user_id'],
                        'name' => $row['name'],
                        'employee_code' => $row['employeeCode'],
                        'test_mode' => true
                    ];
                }
            }

            return $users;
        }

        if (empty($this->stgid)) {
            $this->lastError = 'CAMS_STGID not configured';
            return false;
        }

        $endpoint = '/api/users';
        $params = [
            'stgid' => $this->stgid
        ];

        $response = $this->makeRequest($endpoint, $params);

        if ($response === false) {
            return false;
        }

        return $response['data'] ?? [];
    }

    /**
     * Generate a unique biometric ID for an employee
     * Format: EMP + userID padded to 5 digits (e.g., EMP00001)
     *
     * @param int $userID Employee user ID
     * @return string Biometric ID
     */
    public function generateBiometricID($userID) {
        return 'EMP' . str_pad($userID, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Assign biometric ID to employee and sync to device
     *
     * @param int $userID Employee user ID
     * @param string|null $biometricID Custom biometric ID (auto-generated if null)
     * @return array Result
     */
    public function assignBiometricID($userID, $biometricID = null) {
        global $db;

        $result = [
            'success' => false,
            'biometricID' => null,
            'message' => ''
        ];

        // Get employee details
        $sql = "SELECT userID, userName, displayName, employeeCode, biometricID
                FROM mx_x_admin_user WHERE userID = ? AND status = 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $empResult = $stmt->get_result();
        $emp = $empResult->fetch_assoc();
        $stmt->close();

        if (!$emp) {
            $result['message'] = 'Employee not found';
            return $result;
        }

        // Generate or use provided biometric ID
        $newBiometricID = $biometricID ?: $this->generateBiometricID($userID);

        // Check if this biometric ID is already used by another employee
        $sql = "SELECT userID FROM mx_x_admin_user WHERE biometricID = ? AND userID != ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $newBiometricID, $userID);
        $stmt->execute();
        $checkResult = $stmt->get_result();
        if ($checkResult->num_rows > 0) {
            $result['message'] = 'Biometric ID already assigned to another employee';
            return $result;
        }
        $stmt->close();

        // Update employee record
        $sql = "UPDATE mx_x_admin_user SET biometricID = ? WHERE userID = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $newBiometricID, $userID);
        $updateSuccess = $stmt->execute();
        $stmt->close();

        if (!$updateSuccess) {
            $result['message'] = 'Failed to update employee record';
            return $result;
        }

        // Sync to Camsunit device
        $nameParts = explode(' ', trim($emp['displayName'] ?: $emp['userName']), 2);
        $firstName = $nameParts[0] ?? 'Employee';
        $lastName = $nameParts[1] ?? $emp['employeeCode'] ?? '';

        $addResult = $this->addUser($newBiometricID, $firstName, $lastName);

        if ($addResult) {
            $result['success'] = true;
            $result['biometricID'] = $newBiometricID;
            $result['message'] = 'Biometric ID assigned and synced to device';
        } else {
            // Rollback - but keep the biometric ID in DB for manual sync later
            $result['success'] = true; // Partial success
            $result['biometricID'] = $newBiometricID;
            $result['message'] = 'Biometric ID assigned but device sync failed: ' . $this->lastError;
        }

        return $result;
    }

    /**
     * Remove employee from Camsunit device and clear biometric ID
     *
     * @param int $userID Employee user ID
     * @return array Result
     */
    public function removeBiometricID($userID) {
        global $db;

        $result = [
            'success' => false,
            'message' => ''
        ];

        // Get current biometric ID
        $sql = "SELECT biometricID FROM mx_x_admin_user WHERE userID = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $empResult = $stmt->get_result();
        $emp = $empResult->fetch_assoc();
        $stmt->close();

        if (!$emp || empty($emp['biometricID'])) {
            $result['message'] = 'No biometric ID assigned to this employee';
            return $result;
        }

        // Remove from device first
        $deleteResult = $this->deleteUser($emp['biometricID']);

        // Clear from database regardless of device result
        $sql = "UPDATE mx_x_admin_user SET biometricID = NULL WHERE userID = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $userID);
        $updateSuccess = $stmt->execute();
        $stmt->close();

        if ($updateSuccess) {
            $result['success'] = true;
            if ($deleteResult) {
                $result['message'] = 'Biometric ID removed from database and device';
            } else {
                $result['message'] = 'Biometric ID removed from database (device removal failed: ' . $this->lastError . ')';
            }
        } else {
            $result['message'] = 'Failed to update database';
        }

        return $result;
    }

    /**
     * Get sync status - compare database employees with device users
     *
     * @return array Sync status with matched/unmatched lists
     */
    public function getSyncStatus() {
        global $db;

        $status = [
            'database_employees' => [],
            'device_users' => [],
            'synced' => [],
            'not_on_device' => [],
            'not_in_database' => [],
            'no_biometric_id' => []
        ];

        // Get all active employees
        $sql = "SELECT userID, userName, displayName, employeeCode, biometricID
                FROM mx_x_admin_user WHERE status = 1 ORDER BY displayName";
        $result = $db->query($sql);

        $dbEmployees = [];
        while ($row = $result->fetch_assoc()) {
            $dbEmployees[$row['userID']] = $row;
            $status['database_employees'][] = $row;

            if (empty($row['biometricID'])) {
                $status['no_biometric_id'][] = $row;
            }
        }

        // Get device users
        $deviceUsers = $this->getDeviceUsers();
        if ($deviceUsers === false) {
            $deviceUsers = [];
        }

        $deviceUserMap = [];
        foreach ($deviceUsers as $user) {
            $deviceUserMap[$user['user_id']] = $user;
            $status['device_users'][] = $user;
        }

        // Compare
        foreach ($dbEmployees as $emp) {
            if (!empty($emp['biometricID'])) {
                if (isset($deviceUserMap[$emp['biometricID']])) {
                    $status['synced'][] = $emp;
                } else {
                    $status['not_on_device'][] = $emp;
                }
            }
        }

        // Find device users not in database
        foreach ($deviceUsers as $user) {
            $found = false;
            foreach ($dbEmployees as $emp) {
                if ($emp['biometricID'] === $user['user_id']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $status['not_in_database'][] = $user;
            }
        }

        return $status;
    }

    /**
     * Bulk assign biometric IDs to employees without one
     *
     * @return array Result with counts
     */
    public function bulkAssignBiometricIDs() {
        global $db;

        $result = [
            'success' => false,
            'total' => 0,
            'assigned' => 0,
            'failed' => 0,
            'details' => []
        ];

        // Get employees without biometric ID
        $sql = "SELECT userID, userName, displayName, employeeCode
                FROM mx_x_admin_user
                WHERE (biometricID IS NULL OR biometricID = '') AND status = 1";
        $dbResult = $db->query($sql);

        while ($emp = $dbResult->fetch_assoc()) {
            $result['total']++;

            $assignResult = $this->assignBiometricID($emp['userID']);

            if ($assignResult['success']) {
                $result['assigned']++;
                $result['details'][] = [
                    'userID' => $emp['userID'],
                    'name' => $emp['displayName'],
                    'biometricID' => $assignResult['biometricID'],
                    'status' => 'success'
                ];
            } else {
                $result['failed']++;
                $result['details'][] = [
                    'userID' => $emp['userID'],
                    'name' => $emp['displayName'],
                    'error' => $assignResult['message'],
                    'status' => 'failed'
                ];
            }
        }

        $result['success'] = $result['failed'] == 0;
        return $result;
    }
}

/**
 * Helper function to get CamsunitAPI instance
 */
function getCamsunitAPI() {
    static $instance = null;
    if ($instance === null) {
        $instance = new CamsunitAPI();
    }
    return $instance;
}
?>
