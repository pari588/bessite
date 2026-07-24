<?php

require_once 'vendor/autoload.php'; // Included by cams-tester.php
$logger = require __DIR__ . '/logger.php';
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Carbon\Carbon;

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}


$config = [];
$configPath = $_ENV['CAMS_CONF_PATH'] ?? './cams.conf';
if (file_exists($configPath)) {
    $config = parse_ini_file($configPath, true);
} else {
    $logger->error("❌ Failed to load cams.conf: Configuration file '$configPath' not found.");
    throw new Exception("Configuration file '$configPath' not found.");
}

// Database configuration (prioritize .env over ini file)
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? ($config['database']['host'] ?? 'localhost'),
    'port' => (int)($_ENV['DB_PORT'] ?? ($config['database']['port'] ?? 3306)), // Default MariaDB port
    'user' => $_ENV['DB_USER'] ?? ($config['database']['username'] ?? 'root'), // MariaDB username
    'password' => $_ENV['DB_PASSWORD'] ?? ($config['database']['password'] ?? ''),
    'database' => $_ENV['DB_NAME'] ?? ($config['database']['database'] ?? ''),
];

if (empty($dbConfig['database'])) {
    throw new Exception('[database] section or DB_NAME missing in cams.conf or .env');
}

const CAMS_REST_API_ENDPOINT = 'https://robot.camsunit.com/external/api3.0/biometric?stgid=';

/**
 * Generate a unique 13-character operation ID
 * @return  string
 */
function generateOperationID(): string
{
    // 10 bytes -> 20 hex chars, slice to 13
    return substr(bin2hex(random_bytes(10)), 0, 13);
}

/**
 * Get current timestamp in required format
 * @return  string
 */
function getCurrentTimestamp(): string
{
    // Carbon automatically handles timezones correctly.
    // Assuming the device expects +0530, use Asia/Kolkata timezone.
    return Carbon::now('Asia/Kolkata')->format('Y-m-d H:i:s T');
}

/**
 * Add user to biometric device
 * @param  string $serialNumber Device serial number.
 * @param  string $userId User ID to add.
 * @return  array {success: bool, response: array|null, error: string|null}
 */
function addUser(string $serialNumber, string $userId): array
{
    global $dbConfig, $CAMS_REST_API_ENDPOINT;
    $connection = null;
    try {
        // MariaDB DSN uses 'mysql'
        $connection = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
            $dbConfig['user'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Fetch user details

        $stmt = $connection->prepare(
            'SELECT first_name, last_name, type, template FROM camsUser WHERE user_id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found in local DB');
        }

        // Fetch device auth token

        $stmt = $connection->prepare(
            'SELECT auth_token FROM camsDevice WHERE serial_number = ? LIMIT 1'
        );
        $stmt->execute([$serialNumber]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            throw new Exception('Device not found in local DB');
        }
        $authToken = $device['auth_token'];

        // Build payload
        $payload = [
            'Add' => [
                'User' => [
                    'UserID' => $userId,
                    'FirstName' => $user['first_name'],
                    'LastName' => $user['last_name'],
                    'UserType' => $user['type'] === '1' ? 'Admin' : 'User'
                ],
                // Ensure template is parsed JSON, handle potential null or non-string values safely
                'Template' => json_decode($user['template'] ?? '[]', true)
            ],
            'OperationID' => generateOperationID(),
            'AuthToken' => $authToken,
            'Time' => getCurrentTimestamp()
        ];

        // Send API request
        $client = new Client();
        $response = $client->post($CAMS_REST_API_ENDPOINT . $serialNumber, [
            'json' => $payload,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if (isset($responseData['Status']) && $responseData['Status'] === 'done') {
            $logger->info(sprintf('✅ User %s added to device %s', $userId, $serialNumber));
            return ['success' => true, 'response' => $responseData, 'error' => null];
        } else {
            $logger->error(sprintf('❌ Add failed: %s', json_encode($responseData)));
            return ['success' => false, 'response' => $responseData, 'error' => 'Add operation failed'];
        }
    } catch (Throwable $error) {
        $logger->error(sprintf('❌ Error in addUser: %s', $error->getMessage()));
        return ['success' => false, 'response' => null, 'error' => $error->getMessage()];
    } finally {
        if ($connection) {
            $connection = null; // Close connection
        }
    }
}

/**
 * Delete user from biometric device
 * @param  string $serialNumber Device serial number.
 * @param  string $userId User ID to delete.
 * @return  array {success: bool, response: array|null, error: string|null}
 */
function deleteUser(string $serialNumber, string $userId): array
{
    global $dbConfig, $CAMS_REST_API_ENDPOINT;
    $connection = null;
    try {
        // MariaDB DSN uses 'mysql'
        $connection = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
            $dbConfig['user'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Fetch device auth token
        $stmt = $connection->prepare(
            'SELECT auth_token FROM camsDevice WHERE serial_number = ? LIMIT 1'
        );
        $stmt->execute([$serialNumber]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) {
            throw new Exception('Device not found in local DB');
        }
        $authToken = $device['auth_token'];

        // Build payload
        $payload = [
            'Delete' => [
                'User' => [
                    'UserID' => $userId
                ]
            ],
            'OperationID' => generateOperationID(),
            'AuthToken' => $authToken,
            'Time' => getCurrentTimestamp()
        ];

        // Send API request
        $client = new Client();
        $response = $client->post($CAMS_REST_API_ENDPOINT . $serialNumber, [
            'json' => $payload,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        if (isset($responseData['Status']) && $responseData['Status'] === 'done') {
            $logger->info(sprintf('🗑️ User %s deleted from device %s', $userId, $serialNumber));
            return ['success' => true, 'response' => $responseData, 'error' => null];
        } else {
            $logger->error(sprintf('❌ Delete failed: %s', json_encode($responseData)));
            return ['success' => false, 'response' => $responseData, 'error' => 'Delete operation failed'];
        }
    } catch (Throwable $error) {
        $logger->error(sprintf('❌ Error in deleteUser: %s', $error->getMessage()));
        return ['success' => false, 'response' => null, 'error' => $error->getMessage()];
    } finally {
        if ($connection) {
            $connection = null; // Close connection
        }
    }
}
