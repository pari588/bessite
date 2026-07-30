<?php
// Ensure Composer autoloader is included
require_once 'vendor/autoload.php';
use Carbon\Carbon;
$logger = require __DIR__ . '/logger.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Include attendance logic files
require_once './ShiftAttendance.php';

 

// Load configuration from cams.conf
$config = [];
$configPath = $_ENV['CAMS_CONF_PATH'] ?? './cams.conf';
if (file_exists($configPath)) {
    $config = parse_ini_file($configPath, true);
} else {
    $logger->error("Error: Configuration file '$configPath' not found.\n");
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Configuration file '$configPath' not found."]);
    exit;
}

// Database configuration (prioritize .env over ini file)
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? ($config['database']['host'] ?? 'localhost'),
    'port' => (int)($_ENV['DB_PORT'] ?? ($config['database']['port'] ?? 3306)), // Default MariaDB port
    'user' => $_ENV['DB_USER'] ?? ($config['database']['username'] ?? 'root'), // MariaDB username default
    'password' => $_ENV['DB_PASSWORD'] ?? ($config['database']['password'] ?? ''),
    'database' => $_ENV['DB_NAME'] ?? ($config['database']['database'] ?? ''),
];

if (empty($dbConfig['database'])) {
    $logger->error('❌ [database] section or DB_NAME missing in config');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '[database] section or DB_NAME missing in config']);
    exit;
}

// ✅ Default policy embedded 
const DEFAULT_ATTENDANCE_POLICY = [
    'Day_Attendance' => [
        'working_days' => [
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00'],
            ['start_time' => '09:00', 'end_time' => '18:00', 'break_start' => '13:00', 'break_end' => '14:00']
        ],
        'overtime_slab_minute' => 0,
        'minimum_working_hours' => 0,
        'threshold_start_time' => 0,
        'threshold_end_time' => 30,
        'policy' => 'once_a_day'
    ]
];

// Load shift data from JSON file
$shiftData = [];

 $jsonFilePath = './day-attendance.json';

 try {
    if (file_exists($jsonFilePath)) {
        $jsonString = file_get_contents($jsonFilePath);
        $decoded = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            $shiftData = $decoded;
            $logger->info("✅ Policy loaded successfully from: {$jsonFilePath}");
        } else {
            $logger->error("⚠️ JSON decode error in {$jsonFilePath}: " . json_last_error_msg() . ", using default policy");
        }
    } else {
        $logger->warning("⚠️ Policy file not found: {$jsonFilePath}, using default policy");
    }
} catch (Throwable $error) {
    $logger->error("❌ Error reading or parsing {$jsonFilePath}: " . $error->getMessage() . ", using default policy");
    $shiftData = [];
}


/**
 * Helper: Validates incoming request payload.
 * @param  array $payload - The request body payload.
 * @return  array|null - Error response array if invalid, null otherwise.
 */
function validateRequest(array $payload): ?array
{
    if (!isset($payload['RealTime'])) {
        return ['status' => 'error', 'message' => 'Missing RealTime data'];
    }
    $realTime = $payload['RealTime'];
    
    $serialNumber = $realTime['SerialNumber'] ?? null;
    
    $authToken = $realTime['AuthToken'] ?? null;

    if (!$serialNumber || !$authToken) {
        return ['status' => 'error', 'message' => 'Missing SerialNumber or AuthToken'];
    }
    return null;
}

/**
 * Helper: Fetches device record from DB.
 * @param  PDO $pdo - PDO database connection.
 * @param  string $serialNumber - Device serial number.
 * @param  string $authToken - Device authentication token.
 * @return  array|null - Device record or null.
 */
function getDevice(PDO $pdo, string $serialNumber, string $authToken): ?array
{
    $stmt = $pdo->prepare('SELECT id, client_id FROM camsDevice WHERE serial_number = ? AND auth_token = ? AND status = ? LIMIT 1');
    $stmt->execute([$serialNumber, $authToken, 'A']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Helper: Recalculates punch_type based on direction.
 * @param  PDO $pdo - PDO database connection.
 * @param  string $userId - User ID.
 * @param  string $punchDate - Punch date (YYYY-MM-DD).
 * @param  int $direction - Direction setting.
 */
function recalculatePunchTypes(PDO $pdo, string $userId, string $punchDate, int $direction): void
{
    $logger->debug("direction: " . $direction);

    $stmt = $pdo->prepare(
        'SELECT id, punch_time, punch_type FROM camsPunch WHERE user_id = ? AND DATE(punch_time) = ? ORDER BY punch_time ASC'
    );
    $stmt->execute([$userId, $punchDate]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) return;

    if ($direction === 0) { // punch_type = actual_punch_type
        $stmt = $pdo->prepare(
            'UPDATE camsPunch SET punch_type = actual_punch_type WHERE user_id = ? AND DATE(punch_time) = ?'
        );
        $stmt->execute([$userId, $punchDate]);
    } elseif ($direction === 1) { // Zigzag IN/OUT
        foreach ($rows as $i => $row) {
            $newType = $i % 2 === 0 ? 0 : 1;
            $stmt = $pdo->prepare('UPDATE camsPunch SET punch_type = ? WHERE id = ?');
            $stmt->execute([$newType, $row['id']]);
        }
    } elseif ($direction === 2) { // First IN, Last OUT, Rest Intermediate
        $lastIndex = count($rows) - 1;
        foreach ($rows as $i => $row) {
            $newType = ($i === 0) ? 0 : (($i === $lastIndex) ? 1 : 5);

            $stmt = $pdo->prepare('UPDATE camsPunch SET punch_type = ? WHERE id = ?');
            
            $stmt->execute([$newType, $row['id']]);
        }
    } elseif ($direction === 3) { // Always IN

        $stmt = $pdo->prepare(
            'UPDATE camsPunch SET punch_type = 0 WHERE user_id = ? AND DATE(punch_time) = ?'
        );
        $stmt->execute([$userId, $punchDate]);
    } elseif ($direction === 4) { // Always OUT

        $stmt = $pdo->prepare(
            'UPDATE camsPunch SET punch_type = 1 WHERE user_id = ? AND DATE(punch_time) = ?'
        );
        $stmt->execute([$userId, $punchDate]);
    }
}

/**
 * Loads user-specific attendance policy data from a "model" (e.g., database).
 * For now, it returns a hardcoded shift policy, overriding the global policy.
 * This can be extended to fetch from a DB based on $userId.
 * @param  PDO $connection - PDO database connection.
 * @param  string $userId - The user ID.
 * @param  array $currentPolicy - The currently loaded policy (from file or default).
 * @return  array - The effective attendance policy for the user.
 */
function getModelData(PDO $connection, string $userId, array $currentPolicy): array
{
    // This is the hardcoded mock policy
    // WARNING: Array keys here are hardcoded. If these should also be dynamic,
    // they would need a different mechanism (e.g., loading from a dynamic source
    // that itself uses the placeholder mappings).
    $mockUserSpecificPolicy = [
        "Shift_Attendance" => [
            "shift_hours" => [
                [
                    "label" => "Morning Shift",
                    "start_time" => "07:00",
                    "end_time" => "15:00",
                    "break_start" => "11:00",
                    "break_end" => "11:30"
                ],
                [
                    "label" => "Evening Shift",
                    "start_time" => "15:00",
                    "end_time" => "20:00",
                    "break_start" => "18:00",
                    "break_end" => "18:30"
                ],
                [
                    "label" => "Night Shift",
                    "start_time" => "23:00",
                    "end_time" => "05:00",
                    "break_start" => "03:00",
                    "break_end" => "03:30"
                ]
            ],
            "Over_look_time" => 30,
            "overtime_slab_minute" => 30,
            "minimum_working_hours" => 7,
            "threshold_start_time" => 10,
            "threshold_end_time" => 10,
            "policy" => "strict"
        ]
    ];

    $logger->debug("ℹ️ getModelData called for user {$userId}. Returning hardcoded shift policy.");
    return $mockUserSpecificPolicy; // Return the hardcoded policy
}


/**
 * Main function to evaluate attendance, dispatching to day or shift logic.
 * @param  PDO $connection - PDO database connection.
 * @param  array $policy - Attendance policy.
 * @param  string $userId - User ID.
 * @param  string $punchTime - Punch time (YYYY-MM-DD HH:mm:ss).
 * @param  string $punchDate - Punch date (YYYY-MM-DD).
 * @param  int $direction - Direction setting.
 */
function handleAttendanceEvaluation(PDO $connection, array $policy, string $userId, string $punchTime, string $punchDate, int $direction): void
{
    global $DEFAULT_ATTENDANCE_POLICY; // Access global default policy

    if (empty($policy)) {
        $policy = $DEFAULT_ATTENDANCE_POLICY;
    }

    $logger->debug('Policy: ' . print_r($policy, true));

    $schedule = null;
    
     
    
             $mode = 'shift';
        $schedule = $policy['Shift_Attendance'];
        ShiftAttendance::handleShiftPunch($connection, $userId, $punchTime, $schedule);
    
    }

/**
 * Helper: Strips timezone from a datetime string.
 * @param  string $datetimeStr - Datetime string (e.g., "YYYY-MM-DD HH:mm:ss GMT +0530").
 * @return  string|null - Stripped datetime string (e.g., "YYYY-MM-DD HH:mm:ss").
 */
function stripTimezone(string $datetimeStr): ?string
{
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $datetimeStr, $matches)) {
        return $matches[0];
    }
    return null;
}

/**
 * Helper: Extracts date part from a datetime string.
 * @param  string $datetimeStr - Datetime string.
 * @return  string|null - Date string (YYYY-MM-DD).
 */
function extractDate(string $datetimeStr): ?string
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $datetimeStr, $matches)) {
        return $matches[0];
    }
    return null;
}

/**
 * Helper: Converts punch type string to integer code.
 * @param  string $type - Punch type string (e.g., "CheckIn", "CheckOut").
 * @return  int - Integer code.
 */
function getAttType(string $type): int
{
    switch ($type) {
        case 'CheckOut': return 1;
        case 'CheckIn': return 0;
        case 'BreakOut': return 3;
        case 'BreakIn': return 4;
        default: return 5; // Intermediate
    }
}

/**
 * Helper: Converts input type string to integer code.
 * @param  string $input - Input type string (e.g., "Fingerprint", "Face").
 * @return  int - Integer code.
 */
function getInputType(string $input): int
{
    switch ($input) {
        case 'Fingerprint': return 0;
        case 'Face': return 1;
        case 'Card': return 3;
        case 'Password': return 4;
        case 'Others': return 5;
        default: return 9; // Unknown
    }
}

/**
 * Processes a PunchLog entry from the device.
 * @param  PDO $connection - PDO database connection.
 * @param  array $device - Device record from DB.
 * @param  array $punchLog - Punch log data from payload.
 */
function processPunchLog(PDO $connection, array $device, array $punchLog): void
{
    global $shiftData; // Access global shiftData loaded from JSON

    
    $userId = $punchLog['UserId'] ?? null;
    
    $logTime = $punchLog['LogTime'] ?? null;

    $logger->debug("LogTime: {$logTime}");

    $punchTime = stripTimezone($logTime);
    $punchDate = extractDate($logTime);

    $logger->debug("PunchTime (stripped): " . ($punchTime ?? 'NULL'));
    $logger->debug("PunchDate (extracted): " . ($punchDate ?? 'NULL'));

    if (!$punchTime || !$punchDate) {
        $logger->error("Invalid LogTime format received for UserID: {$userId}");
        return;
    }

    
    $actualType = getAttType($punchLog['Type'] ?? '');
    
    $inputType = getInputType($punchLog['InputType'] ?? '');

    // Check if punch already exists
    $stmt = $connection->prepare(
        'SELECT id FROM camsPunch WHERE user_id = ? AND punch_time = ? AND device_id = ? LIMIT 1'
    );
    
    $stmt->execute([$userId, $punchTime, $device['id']]);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $logger->info("Existing punch records count: " . count($existing));

    if (empty($existing)) {
        $stmt = $connection->prepare(
            'INSERT INTO camsPunch
             (user_id, punch_time, actual_punch_type, punch_type, input_type, device_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        
        $stmt->execute([$userId, $punchTime, $actualType, 9, $inputType, $device['id']]);
        $logger->info("New Record Inserted");
    } else {
        $stmt = $connection->prepare(
            'UPDATE camsPunch SET actual_punch_type = ? WHERE id = ?'
        );
        
        $stmt->execute([$actualType, $existing[0]['id']]);
        $logger->info("Existing record updated");
    }

    // Get configuration direction

    $stmt = $connection->prepare(
        'SELECT direction FROM camsConfiguration WHERE client_id = ? AND status = ? LIMIT 1'
    );
    
    $stmt->execute([$device['id'], 'A']);
    $configRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $direction = $configRow ? (int)$configRow['direction'] : 0;

    recalculatePunchTypes($connection, $userId, $punchDate, $direction);

    // --- NEW FEATURE: Get model data for the user ---
    $effectivePolicy = $shiftData; // Start with the globally loaded shiftData
    // Uncomment the line below if you want to test or load your own shiftData via getModelData
    // $effectivePolicy = getModelData($connection, $userId, $shiftData);
    // --- END NEW FEATURE ---

    handleAttendanceEvaluation($connection, $effectivePolicy, $userId, $punchTime, $punchDate, $direction);
}

/**
 * Processes a UserUpdated entry from the device.
 * @param  PDO $connection - PDO database connection.
 * @param  array $userData - User updated data from payload.
 */
function processUserUpdated(PDO $connection, array $userData): void
{
    
    $userId = $userData['UserID']
        
        ?? ($userData['Template'][0]['UserID'] ?? null)
        
        ?? ($userData['Photo']['UserId'] ?? null);

    if (!$userId) {
        $logger->error('UserUpdated ignored: no UserID found in payload');
        return;
    }

    $stmt = $connection->prepare('SELECT id, template FROM camsUser WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $exists = count($rows) > 0;
    $existingUser = $rows[0] ?? null;

    if (!$exists && isset($userData['UserID'])) {
        
        $firstName = $userData['FirstName'] ?? '';
        
        $lastName = $userData['LastName'] ?? '';
        
        $typeVal = ($userData['UserType'] ?? '') === 'Admin' ? '1' : '0';
        
        $templates = $userData['Template'] ?? [];

        
        if (isset($userData['Photo'])) {
            
            $p = $userData['Photo'];
            $templates[] = [
                'Type' => 'Photo',
                
                'Format' => $p['Type'] ?? '',
                
                'Size' => $p['Size'] ?? '',
                
                'Data' => $p['Data'] ?? '',
                
                'UserID' => $userId
            ];
        }

        $logger->info("Insert new user: {$userId}");

        $stmt = $connection->prepare(
            'INSERT INTO camsUser (user_id, first_name, last_name, type, template, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $firstName, $lastName, $typeVal, json_encode($templates), 'A']);
        return;
    }

    if (!$exists) {
        $logger->error("UserUpdated ignored for non-existent user {$userId}");
        return;
    }

    $updates = [];
    $params = [];

    
    if (array_key_exists('FirstName', $userData)) {
        $updates[] = 'first_name = ?';
        
        $params[] = $userData['FirstName'];
    }
    
    if (array_key_exists('LastName', $userData)) {
        $updates[] = 'last_name = ?';
        
        $params[] = $userData['LastName'];
    }
    
    if (array_key_exists('UserType', $userData)) {
        
        $typeVal = $userData['UserType'] === 'Admin' ? '1' : '0';
        $updates[] = 'type = ?';
        $params[] = $typeVal;
    }

    $existingTemplates = [];
    
    if (!empty($existingUser['template'])) {
        try {
            
            $decodedTemplates = json_decode($existingUser['template'], true);
            if (is_array($decodedTemplates)) {
                $existingTemplates = $decodedTemplates;
            }
        } catch (Throwable $e) {
            $logger->error("Error decoding existing templates for user {$userId}: " . $e->getMessage());
        }
    }

    
    if (isset($userData['Template']) && is_array($userData['Template'])) {
        
        foreach ($userData['Template'] as $t) {
            
            $newType = $t['Type'] ?? null;
            
            $newIndex = $t['Index'] ?? null;

            $found = false;
            foreach ($existingTemplates as $idx => $et) {
                
                $existingType = $et['Type'] ?? null;
                
                $existingIndex = $et['Index'] ?? null;

                if ($existingType === $newType && $existingIndex === $newIndex) {
                    $existingTemplates[$idx] = $t;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $existingTemplates[] = $t;
            }
        }
    }

    
    if (isset($userData['Photo'])) {
        
        $p = $userData['Photo'];
        $photoEntry = [
            'Type' => 'Photo',
            
            'Format' => $p['Type'] ?? '',
            
            'Size' => $p['Size'] ?? '',
            
            'Data' => $p['Data'] ?? '',
            
            'UserID' => $userId
        ];

        $photoFound = false;
        foreach ($existingTemplates as $idx => $et) {
            
            if (($et['Type'] ?? '') === 'Photo') {
                $existingTemplates[$idx] = $photoEntry;
                $photoFound = true;
                break;
            }
        }
        if (!$photoFound) {
            $existingTemplates[] = $photoEntry;
        }
    }

    
    if ((isset($userData['Template']) && is_array($userData['Template'])) || isset($userData['Photo'])) {
        $updates[] = 'template = ?';
        $params[] = json_encode($existingTemplates);
    }

    if (count($updates) > 0) {
        
        $params[] = $existingUser['id'];

        $sql = "UPDATE camsUser SET " . implode(', ', $updates) . " WHERE id = ?";
        $logger->info("Updating user: {$userId}");
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
    }
}

// Main request handling logic
header("Content-Type: application/json;charset=utf-8"); // Ensure JSON response

$rawdata = file_get_contents("php://input");
$payload = json_decode($rawdata, true);

$response = ['status' => 'error', 'message' => 'An unexpected error occurred'];
$pdo = null;

try {
    $validationError = validateRequest($payload);
    if ($validationError) {
        http_response_code(400);
        echo json_encode($validationError);
        exit;
    }

    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}", // MariaDB DSN
        $dbConfig['user'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->beginTransaction();

    
    $realTime = $payload['RealTime'];
    
    $punchLog = $realTime['PunchLog'] ?? null;
    
    $userUpdated = $realTime['UserUpdated'] ?? null;

    
    $device = getDevice($pdo, $realTime['SerialNumber'], $realTime['AuthToken']);

    if (!$device) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid device or token']);
        exit;
    }

    if ($punchLog) {
        processPunchLog($pdo, $device, $punchLog);
    }
    if ($userUpdated) {
        processUserUpdated($pdo, $userUpdated);
    }

    $pdo->commit();
    $response = ['status' => 'done'];
    http_response_code(200);

} catch (Throwable $e) {
    $logger->error('❌ Error: ' . $e->getMessage());
    if ($pdo && $pdo->inTransaction()) {
        try {
            $pdo->rollBack();
            $logger->error('↩️ Transaction rolled back');
        } catch (Throwable $rollbackErr) {
            $logger->error('❌ Rollback failed: ' . $rollbackErr->getMessage());
        }
    }
    $response = ['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()];
    http_response_code(500);
} finally {
    if ($pdo) {
        $pdo = null; // Close connection
    }
}

echo json_encode($response);
exit;
