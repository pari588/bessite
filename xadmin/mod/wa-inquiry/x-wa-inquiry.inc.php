<?php
// ============================================================================
// WhatsApp Inquiry - Admin Module Controller
// ============================================================================

if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");

    $action = $_POST["xAction"];

    // Update inquiry status
    if ($action == "updateStatus") {
        $inquiryID = intval($_POST["inquiryID"] ?? 0);
        $inquiryStatus = $_POST["inquiryStatus"] ?? '';
        $salesNotes = $_POST["salesNotes"] ?? '';
        $followUpDate = $_POST["followUpDate"] ?? null;

        $validStatuses = ['new', 'contacted', 'quoted', 'converted', 'closed'];
        if (!$inquiryID || !in_array($inquiryStatus, $validStatuses)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            exit;
        }

        $DB->table = $DB->pre . "wa_inquiry";
        $DB->data = [
            'inquiryStatus' => $inquiryStatus,
            'salesNotes' => $salesNotes,
        ];
        if ($followUpDate) {
            $DB->data['followUpDate'] = $followUpDate;
        }
        $DB->dbUpdate("inquiryID='" . $inquiryID . "'");

        echo json_encode(['status' => 'success']);
        exit;
    }

} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "wa_inquiry", "PK" => "inquiryID", "UDIR" => array()));
}
?>
