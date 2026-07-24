<?php

require_once 'vendor/autoload.php'; // Composer autoloader

// Include API client functions
require_once 'cams-rest-api.php';
$logger = require __DIR__ . '/logger.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

/**
 * Prompts user for input.
 * @param  string $query - The prompt message.
 * @return  string - User's trimmed input.
 */
function promptInput(string $query): string
{
    echo $query;
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    return $line;
}

/**
 * Loads database configuration from cams.conf (prioritizing .env).
 * @return  array - Database configuration array.
 * @throws  Exception If config file or database section is missing.
 */
function loadDbConfig(): array
{
    // Access the global config loaded in cams-rest-api.php
    global $config;

    $dbConfig = [
        'host' => $_ENV['DB_HOST'] ?? ($config['database']['host'] ?? 'localhost'),
        'port' => (int)($_ENV['DB_PORT'] ?? ($config['database']['port'] ?? 3306)), // Default MariaDB port
        'user' => $_ENV['DB_USER'] ?? ($config['database']['username'] ?? 'root'), // MariaDB username
        'password' => $_ENV['DB_PASSWORD'] ?? ($config['database']['password'] ?? ''),
        'database' => $_ENV['DB_NAME'] ?? ($config['database']['database'] ?? ''),
    ];

    if (empty($dbConfig['database'])) {
        throw new Exception('Missing [database] section or DB_NAME in cams.conf or .env');
    }
    return $dbConfig;
}

/**
 * Allows user to select an active device serial number from DB.
 * @param  PDO $db - PDO database connection.
 * @return  string - Selected serial number.
 * @throws  Exception If no active devices found or invalid selection.
 */
function selectSerialNumber(PDO $db): string
{
   $stmt = $db->prepare('SELECT serial_number FROM camsDevice WHERE status = "A"');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        throw new Exception('No active devices found');
    }

    echo "\nAvailable Devices:\n";
    foreach ($rows as $i => $r) {
        echo sprintf("%d: %s\n", $i + 1, $r['serial_number']);
    }

    $choice = promptInput('Enter choice (number): ');
    $idx = (int)$choice;
    if ($idx < 1 || $idx > count($rows)) {
        throw new Exception('Invalid selection');
    }
    return $rows[$idx - 1]['serial_number'];
}

/**
 * Adds a new device to the database.
 * @param  PDO $db - PDO database connection.
 */
function addDevice(PDO $db): void
{
    $clientId = '';
    while (true) {
        $clientId = promptInput('Enter Client ID: ');
        $stmt = $db->prepare('SELECT 1 FROM camsDevice WHERE client_id = ? LIMIT 1');
        $stmt->execute([$clientId]);
        $clientExists = $stmt->fetchColumn();
        if ($clientExists) {
            echo "⚠️ Client ID already exists. Please enter a different one.\n\n";
        } else {
            break;
        }
    }

    $serialNumber = '';
    while (true) {
        $serialNumber = promptInput('Enter Serial Number: ');
        $stmt = $db->prepare('SELECT 1 FROM camsDevice WHERE serial_number = ? LIMIT 1');
        $stmt->execute([$serialNumber]);
        $serialExists = $stmt->fetchColumn();
        if ($serialExists) {
            echo "⚠️ Serial Number already exists. Please enter a different one.\n\n";
        } else {
            break;
        }
    }

    $labelName = promptInput('Enter Label Name: ');
    $authToken = promptInput('Enter Auth Token: ');

    $stmt = $db->prepare(
        'INSERT INTO camsDevice (client_id, serial_number, label_name, auth_token, status) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$clientId, $serialNumber, $labelName, $authToken, 'A']);

    echo sprintf("\n✅ Device added with ID %d\n", $db->lastInsertId());
}

/**
 * Sets or updates device direction.
 * @param  PDO $db - PDO database connection.
 */
function setDirection(PDO $db): void
{
    $clientId = promptInput('Enter Client ID: ');

    $stmt = $db->prepare('SELECT direction FROM camsConfiguration WHERE client_id = ? LIMIT 1');
    $stmt->execute([$clientId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        $currentDirection = $rows[0]['direction'];
        $confirm = promptInput(
            sprintf("Client ID %s already exists with direction '%s'.\nWould you like to change it? (yes/no): ", $clientId, $currentDirection)
        );

        if (strtolower($confirm) === 'yes') {
            $newDirection = promptInput('Enter new Direction-Code: ');
            $stmt = $db->prepare(
                'UPDATE camsConfiguration SET direction = ?, status = ? WHERE client_id = ?'
            );
            $stmt->execute([$newDirection, 'A', $clientId]);
            echo sprintf("\n🔁 Direction updated for Client ID %s\n", $clientId);
        } else {
            echo "\nℹ️ No changes made.\n";
        }
    } else {
        $direction = promptInput('Enter Direction-Code: ');
        $stmt = $db->prepare(
            'INSERT INTO camsConfiguration (client_id, direction, status) VALUES (?, ?, ?)'
        );
        $stmt->execute([$clientId, $direction, 'A']);
        echo sprintf("\n✅ Direction added with ID %d\n", $db->lastInsertId());
    }
}

// Main execution logic
(function () {
    $db = null;
    try {
        $dbConfig = loadDbConfig();
        // MariaDB DSN uses 'mysql'
        $db = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}",
            $dbConfig['user'],
            $dbConfig['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $db->beginTransaction(); // Start transaction for tester operations

        echo "\nCAMS Biometric REST API Tester\n";
        echo "------------------------------\n";
        echo "1: Add User\n";
        echo "2: Delete User\n";
        echo "3: Add Device in DB\n";
        echo "4: Set Direction\n\n";

        $choice = promptInput('Enter choice (1, 2, 3, or 4): ');

        if ($choice === '1') {
            $serialNumber = selectSerialNumber($db);
            $userId = promptInput('Enter User ID: ');
            // Call addUser from cams-rest-api.php
            $result = addUser($serialNumber, $userId); 
            echo "\n➡️ Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            $db->commit(); // Commit the tester's transaction
        } elseif ($choice === '2') {
            $serialNumber = selectSerialNumber($db);
            $userId = promptInput('Enter User ID: ');
            // Call deleteUser from cams-rest-api.php
            $result = deleteUser($serialNumber, $userId); 
            echo "\n➡️ Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
            $db->commit(); // Commit the tester's transaction
        } elseif ($choice === '3') {
            addDevice($db);
            $db->commit(); // Commit the tester's transaction
        } elseif ($choice === '4') {
            setDirection($db);
            $db->commit(); // Commit the tester's transaction
        } else {
            echo '❌ Invalid choice' . "\n";
            $db->rollBack(); // Rollback if invalid choice
        }

    } catch (Throwable $err) {
        $logger->error('❌ Error: ' . $err->getMessage());
        if ($db && $db->inTransaction()) {
            try {
                $db->rollBack(); // Rollback on error
                $logger->error('↩️ Transaction rolled back');
            } catch (Throwable $rollbackErr) {
                $logger->error('❌ Rollback failed: ' . $rollbackErr->getMessage());
            }
        }
    } finally {
        if ($db) {
            $db = null; // Close connection
        }
    }
})();
