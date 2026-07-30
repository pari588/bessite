<?php
/**
 * CAMS Biometric - Fetch Data from Device
 *
 * Pulls user list and punch logs directly from the device
 * Uses AES-256-ECB decryption for encrypted responses
 */

// Initialize
require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

// Device Configuration
$SERIAL_NUMBER = CAMS_STGID;
$AUTH_TOKEN = CAMS_AUTH_TOKEN;
$SECURITY_KEY = CAMS_SECURITY_KEY;
$API_URL = 'https://robot.camsbiometrics.com/external/api3.0/biometric';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>CAMS Biometric - Fetch Data</h2>";
echo "<pre>";

/**
 * Decrypt AES-256-ECB response
 */
function decryptResponse($encrypted, $key) {
    $data = base64_decode($encrypted);
    if ($data === false) return null;

    // AES-256-ECB requires 32-byte key
    // Pad or hash the key to 32 bytes
    $keyBytes = substr(str_pad($key, 32, "\0"), 0, 32);

    $decrypted = openssl_decrypt($data, 'aes-256-ecb', $keyBytes, OPENSSL_RAW_DATA);

    if ($decrypted === false) {
        // Try with SHA256 hashed key
        $keyBytes = hash('sha256', $key, true);
        $decrypted = openssl_decrypt($data, 'aes-256-ecb', $keyBytes, OPENSSL_RAW_DATA);
    }

    return $decrypted;
}

/**
 * Make API request to device
 */
function makeRequest($apiUrl, $serialNumber, $authToken, $payload) {
    $payload['OperationID'] = bin2hex(random_bytes(6));
    $payload['AuthToken'] = $authToken;
    $payload['Time'] = date('Y-m-d H:i:s') . ' GMT +0530';

    $url = $apiUrl . '?stgid=' . $serialNumber;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return ['response' => $response, 'httpCode' => $httpCode, 'error' => $error];
}

// =====================================================
// STEP 1: Fetch User List
// =====================================================
echo "=== Fetching User List ===\n";

$result = makeRequest($API_URL, $SERIAL_NUMBER, $AUTH_TOKEN, [
    'Load' => ['UserInfo' => []]
]);

echo "HTTP Code: {$result['httpCode']}\n";
echo "Raw Response: " . substr($result['response'], 0, 100) . "...\n";

$decrypted = decryptResponse($result['response'], $SECURITY_KEY);

if ($decrypted) {
    echo "Decrypted: $decrypted\n";
    $data = json_decode($decrypted, true);
    if ($data) {
        echo "Parsed JSON:\n";
        print_r($data);
    }
} else {
    // Maybe response is not encrypted (plain JSON)
    $data = json_decode($result['response'], true);
    if ($data) {
        echo "Response (not encrypted):\n";
        print_r($data);
    } else {
        echo "Could not decrypt or parse response.\n";

        // Debug: try different key formats
        echo "\n--- Trying different decryption methods ---\n";

        $encrypted = base64_decode($result['response']);

        // Method: Direct key (32 bytes, padded with zeros)
        $key1 = str_pad($SECURITY_KEY, 32, "\0");
        $d1 = openssl_decrypt($encrypted, 'aes-256-ecb', $key1, OPENSSL_RAW_DATA);
        echo "Method 1 (padded): " . ($d1 ? $d1 : "FAILED") . "\n";

        // Method: SHA256 hash of key
        $key2 = hash('sha256', $SECURITY_KEY, true);
        $d2 = openssl_decrypt($encrypted, 'aes-256-ecb', $key2, OPENSSL_RAW_DATA);
        echo "Method 2 (SHA256): " . ($d2 ? $d2 : "FAILED") . "\n";

        // Method: Key as-is (if exactly 32 chars)
        $d3 = openssl_decrypt($encrypted, 'aes-256-ecb', $SECURITY_KEY, OPENSSL_RAW_DATA);
        echo "Method 3 (as-is): " . ($d3 ? $d3 : "FAILED") . "\n";

        // Method: MD5 + SHA1 combined
        $key4 = md5($SECURITY_KEY, true) . substr(sha1($SECURITY_KEY, true), 0, 16);
        $d4 = openssl_decrypt($encrypted, 'aes-256-ecb', $key4, OPENSSL_RAW_DATA);
        echo "Method 4 (MD5+SHA1): " . ($d4 ? $d4 : "FAILED") . "\n";

        // AES-128-ECB
        $d5 = openssl_decrypt($encrypted, 'aes-128-ecb', substr($SECURITY_KEY, 0, 16), OPENSSL_RAW_DATA);
        echo "Method 5 (AES-128): " . ($d5 ? $d5 : "FAILED") . "\n";
    }
}

// =====================================================
// STEP 2: Fetch Punch Logs
// =====================================================
echo "\n\n=== Fetching Punch Logs (Last 30 days) ===\n";

$startDate = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00 GMT +0530';
$endDate = date('Y-m-d') . ' 23:59:59 GMT +0530';

echo "Date Range: $startDate to $endDate\n";

$result = makeRequest($API_URL, $SERIAL_NUMBER, $AUTH_TOKEN, [
    'Load' => [
        'PunchLog' => [
            'Filter' => [
                'StartTime' => $startDate,
                'EndTime' => $endDate,
                'OffSet' => '0'
            ]
        ]
    ]
]);

echo "HTTP Code: {$result['httpCode']}\n";
echo "Raw Response: " . substr($result['response'], 0, 100) . "...\n";

$decrypted = decryptResponse($result['response'], $SECURITY_KEY);

if ($decrypted) {
    echo "Decrypted: $decrypted\n";
} else {
    $data = json_decode($result['response'], true);
    if ($data) {
        echo "Response (not encrypted):\n";
        print_r($data);
    }
}

echo "</pre>";
?>
