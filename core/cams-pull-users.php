<?php
/**
 * CAMS - Pull User Data from Device
 * Run after clicking "Resend All User Data" in API dashboard
 */

require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

$SERIAL_NUMBER = CAMS_STGID;
$AUTH_TOKEN = CAMS_AUTH_TOKEN;
$SECURITY_KEY = CAMS_SECURITY_KEY;
$API_URL = 'https://robot.camsbiometrics.com/external/api3.0/biometric';

echo "=== CAMS Pull User Data ===\n";
echo "Serial: $SERIAL_NUMBER\n";
echo "Auth Token: " . substr($AUTH_TOKEN, 0, 10) . "...\n";
echo "Security Key: " . substr($SECURITY_KEY, 0, 10) . "...\n\n";

// Request to load users
$payload = [
    'OperationID' => bin2hex(random_bytes(6)),
    'AuthToken' => $AUTH_TOKEN,
    'Time' => date('Y-m-d H:i:s') . ' GMT +0530',
    'Load' => ['UserInfo' => []]
];

$url = $API_URL . '?stgid=' . $SERIAL_NUMBER;
echo "URL: $url\n";
echo "Payload: " . json_encode($payload) . "\n\n";

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

echo "HTTP Code: $httpCode\n";
if ($error) echo "cURL Error: $error\n";
echo "Response length: " . strlen($response) . " bytes\n";
echo "Raw Response (first 200 chars): " . substr($response, 0, 200) . "\n\n";

// Try to decrypt if it's base64 encoded
if (!empty($response)) {
    // Check if it's plain JSON first
    $jsonData = json_decode($response, true);
    if ($jsonData !== null) {
        echo "=== Response is Plain JSON ===\n";
        print_r($jsonData);
    } else {
        // Try to decrypt
        echo "=== Attempting Decryption ===\n";
        $encrypted = base64_decode($response);

        if ($encrypted !== false) {
            // Method 1: Direct key
            $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $SECURITY_KEY, OPENSSL_RAW_DATA);
            if ($decrypted) {
                echo "Decrypted (Method 1):\n" . trim($decrypted) . "\n\n";
                $data = json_decode(trim($decrypted), true);
                if ($data) {
                    echo "=== Parsed User Data ===\n";
                    print_r($data);

                    // Store users in database
                    if (isset($data['Load']['UserInfo']) && is_array($data['Load']['UserInfo'])) {
                        echo "\n=== Storing Users in Database ===\n";
                        foreach ($data['Load']['UserInfo'] as $user) {
                            $userId = $user['UserID'] ?? null;
                            $firstName = $user['FirstName'] ?? '';
                            $lastName = $user['LastName'] ?? '';
                            $userType = ($user['UserType'] ?? '') === 'Admin' ? '1' : '0';

                            if ($userId) {
                                // Check if exists
                                $stmt = $db->prepare('SELECT id FROM camsUser WHERE user_id = ? LIMIT 1');
                                $stmt->bind_param('s', $userId);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $existing = $result->fetch_assoc();
                                $stmt->close();

                                if ($existing) {
                                    $stmt = $db->prepare('UPDATE camsUser SET first_name = ?, last_name = ?, type = ?, time_updated = NOW() WHERE id = ?');
                                    $stmt->bind_param('sssi', $firstName, $lastName, $userType, $existing['id']);
                                    $stmt->execute();
                                    $stmt->close();
                                    echo "  Updated: $userId - $firstName $lastName\n";
                                } else {
                                    $stmt = $db->prepare('INSERT INTO camsUser (user_id, first_name, last_name, type, status) VALUES (?, ?, ?, ?, "A")');
                                    $stmt->bind_param('ssss', $userId, $firstName, $lastName, $userType);
                                    $stmt->execute();
                                    $stmt->close();
                                    echo "  Inserted: $userId - $firstName $lastName\n";
                                }
                            }
                        }
                    }
                }
            } else {
                // Method 2: With zero padding
                $decrypted = openssl_decrypt($encrypted, 'aes-256-ecb', $SECURITY_KEY, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING);
                if ($decrypted) {
                    echo "Decrypted (Method 2 - Zero Padding):\n" . trim($decrypted) . "\n";
                } else {
                    echo "Decryption failed with all methods.\n";
                    echo "Base64 decoded length: " . strlen($encrypted) . " bytes\n";
                }
            }
        } else {
            echo "Response is not valid base64.\n";
        }
    }
}

echo "\nDone.\n";
?>
