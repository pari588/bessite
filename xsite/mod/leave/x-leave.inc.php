<?php
// Start: To verify user pin
function verifyUserPin()
{
    global $DB;
    $response['err'] = 1;
    $response['msg'] = "";

    $userPinArr = array();
    for ($n = 1; $n <= 4; $n++) {
        $pin = $_POST["pin_" . $n];
        array_push($userPinArr, $pin);
    }
    $userPIN = implode("", $userPinArr);
    if ($userPIN != "") {
        $DB->vals = array(1, trim($userPIN));
        $DB->types = "is";
        $DB->sql = "SELECT userID,userPin,displayName,isLeaveManager  FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userPin=?";
        $DB->dbRow();
        if ($DB->numRows) {
            if ($DB->row["userPin"] != "") {
                $response['err'] = 0;
                $_SESSION['LEAVEUSERID'] = $DB->row["userID"];
                $_SESSION['LEAVEUSERNAME'] = $DB->row["displayName"];
                $_SESSION['ISLEAVEMANAGER'] = $DB->row["isLeaveManager"];
                $response['msg'] = "PIN is available!";
            }
        } else {
            $response['err'] = 1;
            $response['msg'] = "Please enter a valid PIN!";
        }
    } else {
        $response['msg'] = "Enter 4 digit PIN code!";
    }
    return $response;
}
// End.

function getDatesInRange($startDate = "", $endDate = "") // get date array within range
{
    $dates = [];
    $currentDate = strtotime($startDate);
    while ($currentDate <= strtotime($endDate)) {
        $dates[] = date('Y-m-d', $currentDate);
        $currentDate = strtotime('+1 day', $currentDate);
    }
    return $dates;
}

function getSeventhDate($date = '') // get seventh date from selected date
{
    $sevethdate = "";
    if(isset($date) && $date != ''){
        $dateTime = new DateTime($date);
        $sevenDaysLater = clone $dateTime;
        $sevenDaysLater->modify('+7 days');
        $sevethdate = $sevenDaysLater->format('Y-m-d');
    }
   
    return $sevethdate;
}

function addLeaveUser()
{
    $response = array("err" => 1,"msg"=>"Something went wrong!");
    if(!isset($_SESSION['ISLEAVEMANAGER']) || $_SESSION['ISLEAVEMANAGER'] == 0){
        $_POST['userID'] = $_SESSION['LEAVEUSERID'] ?? 0;
    }
    if(isset($_POST['userID']) && $_POST['userID'] > 0 && isset($_POST['fromDate']) && $_POST['fromDate'] !='' && isset($_POST['toDate']) && $_POST['toDate']!='' && isset($_POST['leaveType']) && $_POST['leaveType'] > 0){
        $userID = $_POST['userID'] ?? 0;
        $response = addEmployeeLeave($_POST);
        if(isset($response['err']) &&  $response['err'] == 0){
            $response["msg"] = "Leave application submitted.";
            $seventhDay = getSeventhDate(date('Y-m-d H:i:s'));
            if ($seventhDay > $_POST['fromDate']) {
                $response = updateUnauthLeavesCnt($userID);
                if($response["err"]=="0"){
                    $response["msg"] = "Leave application submitted.";
                }
            }
        }
    }
    return $response;
}
// End.

function updateUnauthLeavesCnt($userID = 0)
{
    $response = array("err" => 1,"msg"=>"Error occured while updating unauthorized count");
    global $DB;
    $DB->sql = "UPDATE ".$DB->pre . "x_admin_user SET unauthorized = (unauthorized+1) WHERE userID =".$userID; 
    if($DB->dbQuery()){
        $response = array("err" => 0,'msg'=>'');
    }
    return $response;
}

function getLeaveList()
{
    $response = ['err'=>1,'msg'=>'No records found','data'=>[]];
    global $DB, $MXTOTREC, $MXSHOWREC;
    $data = [];
    $data["strPaging"] = '';

    $vals = array(1);
    $types = "i";
    $where = '';

    if (isset($_SESSION['LEAVEUSERID'])) {
        array_push($vals, $_SESSION['LEAVEUSERID']);
        $types = $types . "i";
        $where .= " AND userID=?";
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT leaveID FROM `" . $DB->pre . "leave` e
                WHERE status=? " . $where;
    $DB->dbQuery();
    if ($DB->numRows > 0) {
        $MXTOTREC = $DB->numRows;
        $MXSHOWREC = 10;
        $data["strPaging"] = getPaging("", "");
    }

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT leaveType,fromDate,toDate,reason,leaveStatus,snote FROM `" . $DB->pre . "leave` 
                WHERE status=? " . $where . mxOrderBy(" leaveID DESC ") . mxQryLimit();
    $leaves = $DB->dbRows();
    if ($DB->numRows > 0) {
        if ($data["strPaging"] != ""){
            $data['paging'] =   '<div class="row">
                <div class="col-xl-12">
                    <div class="product__showing-result">
                        <div class="product__showing-text-box">
                            <div class="mxpaging">'.$data["strPaging"].'
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        } 
        $leaveArr = getLeaveTypeArr(); // for all leaveTypes
        $str = '';
        foreach ($leaves as $leave) {
            $statusPopup = $statusPopupClass ='';
            if(isset($leave["snote"]) && $leave["snote"]!=''){
                $statusPopupClass='statusPopup';
                $statusPopup = '<span  class = "ls" data-leave-status='.$leave["leaveStatus"].' data-leave-note="'.htmlspecialchars($leave["snote"]).'"> <b>i</b></span>';
            }
            $leaveType = $leaveArr[$leave["leaveType"]];
            $str .= "<tr>
                <td colspan='2'  width='32%''>Type : " . ($leaveType ?? '') . "<br />
                    Date : ".$leave["fromDate"]."<br> to ".$leave["toDate"]."
                </td>
                <td  width='48%'>" . $leave["reason"] . "</td>
                <td class=".$statusPopupClass.">" . $leave["leaveStatus"]. $statusPopup. "</td>
                </tr>";
        }

        $data['tblData'] = $str;
        $response = ['err'=>0,'msg'=>'No records found','data'=>$data];
    }
    return $response;   
}

function getDateDiff($dt1 ='', $dt2 ='') // get days diff count
{
    $response= ['err'=>1,'msg'=>'Dates are empty'];
    if(isset($dt1) && $dt1!='' && isset($dt2) && $dt2!= ''){
        // Create DateTime objects for each date
        $date1 = new DateTime($dt1);
        $date2 = new DateTime($dt2);
        $interval = $date2->diff($date1); // Calculate the difference between the two dates
        $dtDiff = $interval->days; // Get the difference in days
        $response= ['err'=>0,'msg'=>'',"data"=>$interval->format('%R%a')];
    }
    return $response;
}
function validateLeave($leaveData = [])
{
    if(!isset($_SESSION['ISLEAVEMANAGER']) || $_SESSION['ISLEAVEMANAGER'] == 0){
        $leaveData['userID'] = $_SESSION['LEAVEUSERID'] ?? 0;
    }
    $response = array("err" => 1, "errCode" => "", "msg" => "Something went wrong!");
    $duplicateLeaves = validateDuplicateLeave($leaveData);
    if ($duplicateLeaves > 0) {
        $response = array("err" => 1,  "msg" => "You have already applied for leave on the selected date.");
    } else {
        $dt1 = date('Y-m-d');
        $dtDiff = 0;
        $dt2 = $_POST['fromDate'];
        $resp = getDateDiff($dt2, $dt1); // get days diff count
        if(isset($resp['err']) && $resp['err'] == 0){
            $dtDiff = $resp['data'];
        }
        $lcnt =  getUnauthLeaveCnt($leaveData['userID']); // get unauthorized leave count
        if ($lcnt >= 3 && $dtDiff < 7) {
            $response = array("err" => 1, "errCode" => "", "msg" => "You have used your maximum leave allowance with less than 7 days' notice.");
        } else if ($lcnt < 3  && $dtDiff < 7) {
            $extraMsg = "";
            if($lcnt > 0)
                $extraMsg = ", & you have already applied for " . $lcnt . " leave within the same period.";
            $msg = "You are applying for leave with less than 7 days notice".$extraMsg;
            $response = array("err" => 0, "errCode" => "leave3", "msg" => $msg);
        } else {
            $response = array("err" => 0, "errCode" => "", "msg" => "");
        }
    }

    return $response;
}

function  validateDuplicateLeave($leaveData = [])
{
    $flag = 0;
    if(isset($leaveData['userID']) && $leaveData['userID'] > 0){
        global $DB;
        $DB->vals = array($leaveData['userID'], 1);
        $DB->types = "ii";
        $DB->sql = "SELECT leaveID,fromDate,toDate FROM mx_leave WHERE userID=?  AND status=?";
        $DB->dbRows();
        if ($DB->numRows > 0) {
            foreach ($DB->rows as $k => $v) {
                $dates = getDatesInRange($v['fromDate'], $v['toDate']);
                if (in_array($leaveData['fromDate'], $dates)) {
                    $flag++;
                }
            }
        }
    }
    return $flag;
}

function getUnauthLeaveCnt($userID = 0)
{
    $count = 0;
    if(isset($userID) && $userID > 0){
        global $DB;
        $data = [];
        $DB->vals = array(1, $userID);
        $DB->types = "ii";
        $DB->sql = "SELECT unauthorized
                    FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND userID =?";
        $DB->dbRow();
        if ($DB->numRows > 0){
            $count = $DB->row['unauthorized'];
        }
    }
        
    return $count;
}

if (isset($_REQUEST["xAction"])) {
    require_once("../../../core/core.inc.php");
    $xAction = $_REQUEST["xAction"];
    $ignoreToken = $xAction == "uploadLocationImg" ? true : false;
    $MXRES = mxCheckRequest(false, $ignoreToken);
    if ($MXRES["err"] == 0) {
        switch ($xAction) {
            case "verifyUserPin":
                $MXRES = verifyUserPin($_POST);
                break;
            case "addLeaveUser":
                require_once(SITEPATH . '/inc/common.inc.php');
                require_once(ADMINPATH . "/inc/site.inc.php");
                $MXRES = addLeaveUser($_POST);
                break;
            case "validateLeave":
                require_once(SITEPATH . '/inc/common.inc.php');
                $MXRES = validateLeave($_POST);
                break;
            case "getHolidays":
                require_once(SITEPATH . '/inc/common.inc.php');
                $MXRES = getHolidaysDD($_POST['fromDate'], $_POST['toDate']);
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(
        array("TBL" => "leave", "PK" => "leaveID", "UDIR" => array())
    );
}
