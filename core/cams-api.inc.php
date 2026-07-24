<?php
/**
 * CAMS Biometric API Helper
 *
 * Functions to add/delete users on the biometric device
 * Updated 8-Jan-2026: Uses encrypted requests (AES-256-ECB)
 */

/**
 * Make encrypted API request to CAMS
 *
 * @param array $payload - Request payload
 * @return array - ['success' => bool, 'data' => array|null, 'message' => string]
 */
function camsMakeRequest($payload) {
    $apiUrl = 'https://robot.camsbiometrics.com/external/api3.0/biometric';
    $stgid = defined('CAMS_STGID') ? CAMS_STGID : '';
    $authToken = defined('CAMS_AUTH_TOKEN') ? CAMS_AUTH_TOKEN : '';
    $securityKey = defined('CAMS_SECURITY_KEY') ? CAMS_SECURITY_KEY : '';

    if (empty($stgid) || empty($authToken)) {
        return ['success' => false, 'data' => null, 'message' => 'CAMS configuration missing'];
    }

    // Add auth token to payload
    $payload['AuthToken'] = $authToken;

    $jsonPayload = json_encode($payload);

    // Encrypt the request if security key is set
    if (!empty($securityKey)) {
        $encrypted = openssl_encrypt($jsonPayload, 'aes-256-ecb', $securityKey, OPENSSL_RAW_DATA);
        $requestBody = base64_encode($encrypted);
    } else {
        $requestBody = $jsonPayload;
    }

    $url = $apiUrl . '?stgid=' . $stgid;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'data' => null, 'message' => 'CURL Error: ' . $error];
    }

    // Decrypt response
    $result = camsDecryptResponse($response);

    if ($result && isset($result['StatusCode'])) {
        $success = ($result['StatusCode'] == 0);
        $message = $result['Status'] ?? ($success ? 'Success' : 'Error');
        return ['success' => $success, 'data' => $result, 'message' => $message];
    }

    return ['success' => false, 'data' => null, 'message' => 'Could not parse response'];
}

/**
 * Add user to CAMS biometric device
 *
 * @param string $userId - Biometric ID (must be unique on device)
 * @param string $firstName - Employee first name
 * @param string $lastName - Employee last name (optional)
 * @return array - ['success' => bool, 'message' => string]
 */
function camsAddUser($userId, $firstName, $lastName = '') {
    if (empty($userId) || empty($firstName)) {
        return ['success' => false, 'message' => 'User ID and First Name are required'];
    }

    $payload = [
        'Add' => [
            'User' => [
                'UserID' => (string)$userId,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'UserType' => 'User'
            ]
        ],
        'OperationID' => 'ADD_' . time() . '_' . rand(1000, 9999),
        'Time' => date('Y-m-d H:i:s') . ' GMT +0530'
    ];

    $result = camsMakeRequest($payload);

    // Log the request
    camsLogApi('ADD_USER', $userId, $firstName, json_encode($result), $result['success'] ? 200 : 500);

    if ($result['success']) {
        return ['success' => true, 'message' => 'User added to biometric device'];
    } else {
        $statusCode = $result['data']['StatusCode'] ?? 'unknown';
        return ['success' => false, 'message' => 'CAMS Error: ' . $result['message'] . ' (Code: ' . $statusCode . ')'];
    }
}

/**
 * Delete user from CAMS biometric device
 *
 * @param string $userId - Biometric ID to delete
 * @return array - ['success' => bool, 'message' => string]
 */
function camsDeleteUser($userId) {
    if (empty($userId)) {
        return ['success' => false, 'message' => 'User ID is required'];
    }

    $payload = [
        'Delete' => [
            'User' => [
                'UserID' => (string)$userId
            ]
        ],
        'OperationID' => 'DEL_' . time() . '_' . rand(1000, 9999),
        'Time' => date('Y-m-d H:i:s') . ' GMT +0530'
    ];

    $result = camsMakeRequest($payload);

    // Log the request
    camsLogApi('DELETE_USER', $userId, '', json_encode($result), $result['success'] ? 200 : 500);

    if ($result['success']) {
        return ['success' => true, 'message' => 'User deleted from biometric device'];
    } else {
        $statusCode = $result['data']['StatusCode'] ?? 'unknown';
        return ['success' => false, 'message' => 'CAMS Error: ' . $result['message'] . ' (Code: ' . $statusCode . ')'];
    }
}

/**
 * Pull punch logs from CAMS device
 *
 * @param string $startDate - Start date (Y-m-d format)
 * @param string $endDate - End date (Y-m-d format)
 * @return array - ['success' => bool, 'data' => array, 'message' => string]
 */
function camsLoadPunchLog($startDate, $endDate) {
    $payload = [
        'Load' => [
            'PunchLog' => [
                'Filter' => [
                    'StartTime' => $startDate . ' 00:00:00 GMT +0530',
                    'EndTime' => $endDate . ' 23:59:59 GMT +0530'
                ]
            ]
        ]
    ];

    $result = camsMakeRequest($payload);

    if ($result['success'] && isset($result['data']['PunchLog']['Log'])) {
        return [
            'success' => true,
            'data' => $result['data']['PunchLog']['Log'],
            'total' => $result['data']['PunchLog']['ActualRowCount'] ?? count($result['data']['PunchLog']['Log']),
            'message' => 'Punch logs retrieved'
        ];
    }

    return $result;
}

/**
 * Trigger device sync
 *
 * @return array - ['success' => bool, 'message' => string]
 */
function camsSyncDevice() {
    $payload = ['Sync' => []];
    $result = camsMakeRequest($payload);
    return $result;
}

/**
 * Decrypt CAMS API response
 */
function camsDecryptResponse($response) {
    $securityKey = defined('CAMS_SECURITY_KEY') ? CAMS_SECURITY_KEY : '';

    // Try plain JSON first
    $data = json_decode($response, true);
    if ($data) {
        return $data;
    }

    // Try decryption with security key
    if (!empty($securityKey)) {
        $encrypted = base64_decode($response);
        if ($encrypted) {
            // Try with zero padding first (CAMS uses this)
            $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $securityKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
            if ($decrypted) {
                $data = json_decode(trim($decrypted), true);
                if ($data) {
                    return $data;
                }
            }

            // Try without zero padding
            $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $securityKey, OPENSSL_RAW_DATA);
            if ($decrypted) {
                $data = json_decode(trim($decrypted), true);
                if ($data) {
                    return $data;
                }
            }
        }
    }

    return null;
}

/**
 * Log CAMS API calls
 */
function camsLogApi($action, $userId, $name, $response, $httpCode) {
    $logDir = defined('ROOTPATH') ? ROOTPATH . '/logs' : dirname(__FILE__) . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/cams-api-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] [$action] UserID=$userId, Name=$name, HTTP=$httpCode, Response=" . substr($response, 0, 200) . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}
