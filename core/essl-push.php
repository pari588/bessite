<?php
/**
 * eSSL / ZKTeco "iclock" (ADMS Push) endpoint — direct device→our-server integration.
 * Replaces the CAMS cloud relay. Device pushes here directly over HTTPS.
 *
 * Device Comm setting -> Server: www.bombayengg.net  Port: 443 (HTTPS)  (path /iclock is fixed in firmware)
 * Routing: .htaccess rewrites ^iclock/ -> core/essl-push.php
 *
 * Protocol handled:
 *   GET  /iclock/cdata?SN=..&options=all   -> handshake, returns config block
 *   GET  /iclock/getrequest?SN=..          -> command poll, returns "OK"
 *   POST /iclock/cdata?SN=..&table=ATTLOG  -> punch upload (tab-delimited), returns "OK"
 * Punches feed the SAME pipeline as the CAMS callback via cams-punch-processor.inc.php.
 */

require_once dirname(__FILE__) . '/../config.inc.php';
require_once COREPATH . '/db.inc.php';
require_once dirname(__FILE__) . '/cams-punch-processor.inc.php';

$DB = new mxDb($DBHOST, $DBUSER, $DBPASS, $DBNAME);
$db = $DB->con;

header('Content-Type: text/plain; charset=utf-8');

// ---- helpers ----
function esslLog($msg) {
    $dir = ROOTPATH . '/logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($dir . '/essl-push-' . date('Y-m-d') . '.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

$SN     = $_GET['SN'] ?? ($_GET['sn'] ?? '');
$uri    = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$table  = $_GET['table'] ?? '';

esslLog("REQ $method $uri  SN=$SN table=$table");

// Validate device by serial
$deviceId = 0;
if ($SN !== '') {
    $st = $db->prepare("SELECT id FROM camsDevice WHERE serial_number=? AND status='A' LIMIT 1");
    $st->bind_param('s', $SN);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    $deviceId = $r['id'] ?? 0;
}
if (!$deviceId) {
    esslLog("Unknown/inactive device SN=$SN -> rejecting");
    // Still answer politely so the device doesn't spam errors; but do nothing.
    echo "OK";
    exit;
}

// ---- 1) Handshake: GET /iclock/cdata?...&options=all ----
if ($method === 'GET' && strpos($uri, '/iclock/cdata') !== false) {
    // TimeZone in minutes: IST = +5:30 = 330
    $cfg  = "GET OPTION FROM: $SN\n";
    $cfg .= "Stamp=9999\n";
    $cfg .= "OpStamp=9999\n";
    $cfg .= "ErrorDelay=30\n";
    $cfg .= "Delay=10\n";
    $cfg .= "TransTimes=00:00;14:05\n";
    $cfg .= "TransInterval=1\n";
    $cfg .= "TransFlag=1111000000\n";
    $cfg .= "TimeZone=330\n";
    $cfg .= "Realtime=1\n";
    $cfg .= "Encrypt=0\n";
    esslLog("Handshake -> sent config");
    echo $cfg;
    exit;
}

// ---- 2) Command poll: GET /iclock/getrequest ----
if ($method === 'GET' && strpos($uri, '/iclock/getrequest') !== false) {
    echo "OK";   // no queued commands
    exit;
}

// ---- 3) Data upload: POST /iclock/cdata ----
if ($method === 'POST' && strpos($uri, '/iclock/cdata') !== false) {
    $body = file_get_contents('php://input');
    if ($table !== 'ATTLOG') {
        // OPERLOG (user edits), OPTIONS, etc. — acknowledge, no attendance impact.
        esslLog("Non-ATTLOG table=$table (" . strlen($body) . " bytes) -> OK");
        echo "OK";
        exit;
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($body));
    $count = 0;
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        // ATTLOG: PIN \t YYYY-MM-DD HH:MM:SS \t status \t verify \t workcode \t ...
        $f = explode("\t", $line);
        if (count($f) < 2) continue;
        $pin      = trim($f[0]);
        $logTime  = trim($f[1]);
        if ($pin === '' || $logTime === '') continue;

        // Feed the SAME pipeline as CAMS. Type is a placeholder — recalculatePunchTypes()
        // derives IN/OUT from first/last punch of the day, so device status is irrelevant.
        $res = processPunchLog($db, $deviceId, [
            'UserId'    => $pin,
            'LogTime'   => $logTime,
            'Type'      => 'CheckIn',
            'InputType' => 'Fingerprint',
        ]);
        if (!empty($res['success'])) $count++;
    }
    esslLog("ATTLOG processed $count / " . count($lines) . " records");
    echo "OK: $count";   // ZKTeco/eSSL expects OK acknowledgement
    exit;
}

// Anything else
echo "OK";
