<?php
/**
 * WhatsApp Cloud API Webhook Endpoint
 * Handles inbound messages from Meta Cloud API
 *
 * Webhook URL: https://www.bombayengg.net/core/wa-webhook.php
 * Verification: GET with hub.verify_token
 * Messages: POST with JSON payload
 */

// Prevent session_start and www redirect from config.inc.php
define('WA_WEBHOOK_MODE', true);

// Load WhatsApp config (outside web root)
require_once dirname(__FILE__) . '/../../whatsapp-config.php';

// ==========================================
// GET: Webhook Verification (Meta challenge)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $token === WA_WEBHOOK_VERIFY_TOKEN) {
        waLog("Webhook verified successfully");
        http_response_code(200);
        echo $challenge;
    } else {
        waLog("Webhook verification failed. Mode=$mode, Token=$token", 'ERROR');
        http_response_code(403);
        echo 'Forbidden';
    }
    exit;
}

// ==========================================
// POST: Inbound Messages
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Always return 200 immediately (Meta requires this)
    http_response_code(200);
    echo 'OK';

    // Flush output so Meta gets 200 before we process
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        ob_end_flush();
        flush();
    }

    $rawBody = file_get_contents('php://input');
    waLog("Inbound: " . substr($rawBody, 0, 2000));

    // Verify signature (if app secret is configured)
    if (WA_APP_SECRET) {
        $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        $expectedSig = 'sha256=' . hash_hmac('sha256', $rawBody, WA_APP_SECRET);
        if (!hash_equals($expectedSig, $signature)) {
            waLog("Invalid signature. Expected: $expectedSig, Got: $signature", 'ERROR');
            exit;
        }
    }

    $payload = json_decode($rawBody, true);
    if (!$payload) {
        waLog("Invalid JSON payload", 'ERROR');
        exit;
    }

    // Extract message data from webhook payload
    $entry = $payload['entry'][0] ?? null;
    if (!$entry) exit;

    $changes = $entry['changes'][0] ?? null;
    if (!$changes) exit;

    $value = $changes['value'] ?? null;
    if (!$value) exit;

    // Handle message status updates (delivered, read, etc.)
    if (isset($value['statuses'])) {
        // Status update, not a message — ignore
        exit;
    }

    $messages = $value['messages'] ?? [];
    if (empty($messages)) exit;

    $message = $messages[0];
    $fromNumber = $message['from'] ?? '';
    $waMessageID = $message['id'] ?? '';
    $messageType = $message['type'] ?? 'text';
    $timestamp = $message['timestamp'] ?? '';

    // Extract message content based on type
    $messageBody = '';
    $buttonPayload = '';

    switch ($messageType) {
        case 'text':
            $messageBody = $message['text']['body'] ?? '';
            break;
        case 'interactive':
            $interactive = $message['interactive'] ?? [];
            $interactiveType = $interactive['type'] ?? '';
            if ($interactiveType === 'button_reply') {
                $buttonPayload = $interactive['button_reply']['id'] ?? '';
                $messageBody = $interactive['button_reply']['title'] ?? '';
            } elseif ($interactiveType === 'list_reply') {
                $buttonPayload = $interactive['list_reply']['id'] ?? '';
                $messageBody = $interactive['list_reply']['title'] ?? '';
            }
            break;
        case 'button':
            // Template quick reply button
            $buttonPayload = $message['button']['payload'] ?? '';
            $messageBody = $message['button']['text'] ?? '';
            break;
        default:
            $messageBody = "[Unsupported message type: $messageType]";
            break;
    }

    waLog("From: $fromNumber, Type: $messageType, Body: $messageBody, Payload: $buttonPayload");

    // Now bootstrap the app (DB, etc.) — avoid session and redirect
    $oldSessionStatus = session_status();
    // Prevent config.inc.php from calling session_start
    if ($oldSessionStatus === PHP_SESSION_NONE) {
        // We need to load config but skip session/redirect — load DB directly
    }

    // Load database directly (same pattern as cams-biometric-callback.php)
    // We already have DB credentials from config, load them manually
    $FOLDER = '';
    $DBHOST = 'localhost';
    $DBNAME = 'bombayengg';
    $DBUSER = 'bombayengg';
    $DBPASS = 'oCFCrCMwKyy5jzg';

    define("ROOTPATH", $_SERVER["DOCUMENT_ROOT"] . $FOLDER);
    define("COREPATH", ROOTPATH . "/core");
    define("UPLOADPATH", ROOTPATH . "/uploads");
    define("UPLOADURL", "https://www.bombayengg.net/uploads");
    define("SITEURL", "https://www.bombayengg.net");

    require_once COREPATH . '/db.inc.php';
    $DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);

    require_once COREPATH . '/leave-management.inc.php';
    require_once COREPATH . '/whatsapp-api.inc.php';
    require_once COREPATH . '/pump-matcher.inc.php';
    require_once COREPATH . '/wa-handlers.inc.php';

    $wa = new WhatsAppAPI($DB);

    // Mark message as read
    $wa->markRead($waMessageID);

    // Look up sender — try whatsappNumber first, then userMobile
    $user = lookupUserByPhone($DB, $fromNumber);

    // Log inbound message
    $wa->logMessage('inbound', $fromNumber, null, $user['userID'] ?? null, $waMessageID, $messageType, $messageBody, $buttonPayload, null, null, null);

    if (!$user) {
        // Unregistered number — route through customer flow
        routeCustomerMessage($DB, $wa, $fromNumber, $messageBody, $buttonPayload, $messageType);
        exit;
    }

    // Route to HRMS handler
    routeMessage($DB, $wa, $user, $fromNumber, $messageBody, $buttonPayload, $messageType);
    exit;
}

// Any other method
http_response_code(405);
echo 'Method Not Allowed';
exit;

/**
 * Simple file logger for webhook
 */
function waLog($message, $level = 'INFO')
{
    $logDir = ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__FILE__) . '/..') . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/whatsapp_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] [$level] $message\n", FILE_APPEND);
}
