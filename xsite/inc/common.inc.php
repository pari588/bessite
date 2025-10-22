<?php
//START :  functions common in xsite and xadmin

//Function for Send Email
function sendEmail($emailData = array())
{
    require_once(ABSLIBPATH . "lib/phpmailer/MXPHPMailer.php");
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->SMTPDebug = false;        //true
    $mail->Host     = HOSTNAME;      // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;          // Enable SMTP authentication
    $mail->Username = USERNAME;      // SMTP username
    $mail->Password = PASSWORD;      // SMTP password
    $mail->SMTPSecure = 'tls';       // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;

    if (isset($emailData['fromEmail']) && isset($emailData['fromName'])) {
        $mail->From = $emailData['fromEmail'];
        $mail->FromName = $emailData['fromName'];
    } else {
        $mail->FromName = 'Bombay Engineering Syndicate';
        $mail->From = 'info@bombayengg.net';
    }

    $mail->SetFrom($mail->From, $mail->FromName);

    if (isset($emailData['toEmail']) && sizeof($emailData['toEmail']) > 0) {
        foreach ($emailData['toEmail'] as $email => $name) {
            $mail->AddAddress($email, $name);
        }
    }

    if (isset($emailData['ccEmail']) && sizeof($emailData['ccEmail']) > 0) {
        foreach ($emailData['ccEmail'] as $email => $name) {
            $mail->AddCC($email, $name);
        }
    }

    if (isset($emailData['bccEmail']) && sizeof($emailData['bccEmail']) > 0) {
        foreach ($emailData['bccEmail'] as $email => $name) {
            $mail->AddBCC($email, $name);
        }
    }

    if (isset($emailData['attachment']) && $emailData['attachment'] != "") {
        $attachment = explode(",", $emailData['attachment']);
        foreach ($attachment as $file) {
            $mail->AddAttachment($file);
        }
    }

    $mail->Subject = $emailData['subject'];
    $mail->Body    = $emailData['body'];
    $mail->ContentType = "text/html";

    if ($mail->Send()) {
        return true;
    } else {
        return false;
    }
}
//End

function getLeaveTypeArr()
{
    global $DB;
    $leaveType = [];
    $DB->vals = array(1);
    $DB->types = "i";
    $DB->sql = "SELECT leaveTypeID,leaveTypeName FROM " . $DB->pre . "leave_type 
                WHERE status = ?";
    $DB->dbRows();
    if ($DB->numRows > 0) {
        foreach ($DB->rows as $k => $v) {
            $leaveType[$v['leaveTypeID']]  = $v['leaveTypeName'];
        }
    }
    return $leaveType;
}


function getLeaveDetails($leaveID = 0)
{
    $resp = ['err' => 1, 'msg' => '"LeaveID cannot be null."'];
    if (isset($leaveID) && $leaveID > 0) {
        global $DB;
        $str = "";
        $srNo = 1;
        $subjectArr = array();
        $DB->vals = array($leaveID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT LT.leaveTypeName,L.leaveStatus,LD.leaveDate,LD.leaveTime,LD.lType,U.displayName
                    FROM `" . $DB->pre .  "leave` AS L 
                    LEFT JOIN `" . $DB->pre . "leave_details` AS LD ON L.leaveID=LD.leaveID 
                    LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON U.userID=L.userID 
                    LEFT JOIN `" . $DB->pre . "leave_type` AS LT ON L.leaveType=LT.leaveTypeID 
                    WHERE L.leaveID = ? AND L.status = ?";
        $totalLeaveDate = $DB->dbRows();
        $totalLeaves = $DB->numRows;
        foreach ($totalLeaveDate as  $key => $D) {
            if ($D['lType'] == 1) {
                $D['lType'] = "FullDay";
            } else if ($D['lType'] == 2) {
                $D['lType'] = "First-Half";
            } else if ($D['lType'] == 3) {
                $D['lType'] = "Second-Half";
            } else if ($D['lType'] == -1) {
                $D['lType'] = "Official Holiday";
            } else {
                $D['lType'] = "";
            }
            $past = "";
            if ($D['leaveDate'] < date("Y-m-d")) {
                $past = "Past";
            }
            $str .= '<p>' . $srNo . ') ' . date('l dS F Y', strtotime($D['leaveDate'])) . '   -  ' . $D['lType'] . '  ';
            if ($D['leaveTime'] !== NULL)
                $str .= $D['leaveTime'];
            $str .= '</p>';
            $srNo++;
            if (!in_array($D['lType'], $subjectArr)) {
                array_push($subjectArr, $D['lType']);
            }
        }
        $leaveData['leaveDate'] = $str;
        $leaveData['leaveStatus'] = $totalLeaveDate[0]['leaveStatus'];
        $leaveData['leaveTypeName'] = $totalLeaveDate[0]['leaveTypeName'];
        $leaveData['displayName'] = $totalLeaveDate[0]['displayName'];
        $leaveData['totalLeaves'] = $totalLeaves;
        if (count($leaveData) > 0)
            $resp = ['err' => 0, 'msg' => 'Leave data fetched successfully.', 'data' => $leaveData];
    }
    return $resp;
}

function sendLeaveMail($leaveID = 0, $reason = "")
{
    $response = ['err' => 1, 'msg' => '"LeaveID cannot be null."'];
    if (isset($leaveID) && $leaveID > 0) {
        $resp  = getLeaveDetails($leaveID);
        if ($resp['err'] == 0) {
            $leaveDetails = $resp['data'];
            if (isset($leaveDetails) && count($leaveDetails) > 0) {
                $newArr = [];
                $newArr = getCommunicationEmail();
                $mailData = array();
                $mailData['toEmail']  = $newArr;
                $mailData['bccEmail'] = array();
                $mailData['subject']  = "Leave Application for-" . $leaveDetails['displayName'];
                $strI = file_get_contents(ADMINURL . "/inc/html/leave.html");
                if (MXCON == 'LOCAL') {
                    $strI =  str_replace("{{logoUrl}}", "https://dev.maxdigi.co/bombay-eng1.0/uploads/setting/logo-m.png", $strI);
                } else {
                    $strI =  str_replace("{{logoUrl}}", SITEURL . "/uploads/setting/logo-m.png", $strI);
                }
                $strI =  str_replace("{{displayName}}", $leaveDetails['displayName'], $strI);

                $strI =  str_replace("{{leaveType}}", $leaveDetails['leaveTypeName'], $strI);
                $strI =  str_replace("{{TotalNoOfLeave}}", $leaveDetails['totalLeaves'], $strI);
                $strI =  str_replace("{{reason}}", $reason, $strI);
                $strI =  str_replace("{{leaveStatus}}", $leaveDetails['leaveStatus'], $strI);
                $strI =  str_replace("{{leaveDate}}", $leaveDetails['leaveDate'], $strI);
                $strI =  str_replace("{{URL}}", ADMINURL . "/employee-leave-list/?leaveID=" . $leaveID . '&showdialog=true', $strI);
                $mailData['body'] = $strI;
                if (MXCON == 'LIVE') {
                    if (sendEmail($mailData)) {
                        $response = ['err' => 0, 'msg' => 'Email send successfully.'];
                    }
                } else {
                    $response = ['err' => 0, 'msg' => 'Code has not been deployed to the live server.'];
                }
            }
        }
    }
    return $response;
}

function updateUserLeaves($year = '', $mon = '', $userID = 0)
{
    global $DB;
    $str = "ERR";
    if ($year == '' && $mon == '') {
        $year = date("Y");
        $mon = date("m");
    }
    $curMonthDate = $year . '-' . $mon . '-01';
    if ($userID <= 0) {
        $DB->sql = "SELECT roleID FROM `" . $DB->pre . "x_admin_role` 
                    WHERE roleName LIKE 'lead'";
        $DB->dbRow();
        $DB->vals = array(1, $DB->row['roleID'] ?? 0);
        $DB->types = "ii";
        $DB->sql = "SELECT U.userID,U.displayName FROM `" . $DB->pre . "x_admin_user` AS U
                    WHERE U.status=?  AND U.roleID =  ?";
        $currMonthData = $DB->dbRows();
    } else {
        $currMonthData = array(array("userID" => $userID));
    }
    foreach ($currMonthData as $d) {
        $data = array();
        $resp = calUserLeaves($d['userID'], $mon, $year);
        if (isset($resp['err']) && $resp['err'] == 0) {
            $data = $resp['data'];
            $data['userID'] = $d['userID'];
            $data["displayName"] = $d["displayName"] ?? "";
            $data['dateAdded'] = date('Y-m-d H:i:s');
            $DB->vals = array(1);
            $DB->types = "i";
            $DB->sql = "SELECT userLeavesID 
                        FROM `" . $DB->pre . "user_leaves`
                        WHERE status=? AND leaveDate = '$curMonthDate' AND userID='" . $data['userID'] . "'";
            $userLeavesData = $DB->dbRow();
            $DB->table = $DB->pre . "user_leaves";
            if ($DB->numRows > 0) {
                $data['dateModified'] = date("Y-m-d H:i:s");
                $DB->data = $data;
                if ($DB->dbUpdate("userLeavesID='" . $userLeavesData['userLeavesID'] . "'"))
                    $str = "OK";
            } else {
                $data['leaveDate'] = $curMonthDate;
                $DB->data = $data;
                if ($DB->dbInsert())
                    $str = "OK";
            }
        }
    }
    return $str;
}

function calUserLeaves($userID = 0, $mon = '', $yr = '')
{ //calculate Users leaves 
    $response = ['err' => 1, 'msg' => '"userID cannot be null."', 'data' => ''];
    if ($userID > 0) {
        global $DB, $TPL;
        $monthDate = $yr . '-' . $mon . '-01';

        if ($yr == '' && $mon == '') {
            $yr = date("Y");
            $mon = date("m");
        }
        $financialYear = getCurrFinancialYear();
        $startDate = $financialYear['start'] . '-04-01';
        $endDate = $financialYear['end']  . '-03-31';
        $fullDayLeave = $halfDayLeave = $appliedLeave = $monthlyappliedLeave = 0;
        $DB->vals = array(1, 1, -1, 'Cancel');
        $DB->types = "iiis";
        $DB->sql = "SELECT COUNT(*) AS totalMonthlyLeave,LD.lType
                    FROM `" . $DB->pre .  "leave_details` AS LD 
                    LEFT JOIN `" . $DB->pre . "leave` AS L ON LD.leaveID=L.leaveID 
                    WHERE  L.userID = '" . $userID . "' AND MONTH(LD.leaveDate) ='" . $mon . "' AND YEAR(LD.leaveDate) = '" . $yr . "' AND LD.status=? AND L.status=? AND LD.lType!=? AND L.leaveStatus!=?
                    AND DATE(LD.leaveDate) NOT IN (
                        SELECT DATE(ahDate) FROM mx_attendance_holidays WHERE ahDate >='" . $startDate . "' AND ahDate <='" . $endDate . "' AND status = 1
                    )
                    GROUP BY LD.lType";
        $MonthlyAppliedLeaveData = $DB->dbRows();
        if ($DB->numRows > 0) {
            foreach ($MonthlyAppliedLeaveData as $k => $v) {
                if ($v['lType'] == 1) {
                    $fullDayLeave = $v['totalMonthlyLeave'];
                } else {
                    $halfDayLeave += $v['totalMonthlyLeave'];
                }
            }
            $halfDayLeave = ($halfDayLeave / 2) ?? 0;
            $appliedLeave = $fullDayLeave + $halfDayLeave;
            $monthlyappliedLeave = $appliedLeave;
        }

        $fullDayLeave = $halfDayLeave = $appliedLeave = $yearlyAppliedLeave =  0;
        $DB->vals = array(1, 1, -1, 'Cancel');
        $DB->types = "iiis";
        $DB->sql = "SELECT  COUNT(*)  AS totalAppliedLeave,LD.lType 
                    FROM `" . $DB->pre .  "leave_details` AS LD 
                    LEFT JOIN `" . $DB->pre . "leave` AS L ON LD.leaveID=L.leaveID 
                    WHERE   L.userID = '" . $userID . "' AND LD.leaveDate >='" . $startDate . "' AND LD.leaveDate <='" . $endDate . "' AND LD.status=? AND L.status=? AND LD.lType!=? AND L.leaveStatus!=? 
                    AND DATE(LD.leaveDate) NOT IN (
                        SELECT DATE(ahDate) FROM " . $DB->pre . "attendance_holidays WHERE ahDate >=" . $startDate . " AND ahDate <='" . $endDate . "' AND status = 1
                    )
                    GROUP BY LD.lType";
        $appliedLeave = $DB->dbRows();
        if ($DB->numRows > 0) {
            foreach ($appliedLeave as $k => $v) {
                if ($v['lType'] == 1) {
                    $fullDayLeave = $v['totalAppliedLeave'];
                } else {
                    $halfDayLeave += $v['totalAppliedLeave'];
                }
            }
            $halfDayLeave = ($halfDayLeave / 2) ?? 0;
            $appliedLeave = $fullDayLeave + $halfDayLeave;
            $yearlyAppliedLeave = $appliedLeave;
        }

        $DB->vals = array($userID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT totalLeaves FROM " . $DB->pre . "x_admin_user WHERE userID = ? AND  status = ?";
        $userData = $DB->dbRow();

        if (isset($userData['totalLeaves']) && $userData['totalLeaves'] > 0) {
            $totolLeave = $userData['totalLeaves'];
        } else {
            $DB->vals = array(1);
            $DB->types = "i";
            $DB->sql = "SELECT totalLeave,FYStartDate,FYEndDate FROM " . $DB->pre . "leave_setting WHERE FYStartDate <= '" . $startDate . "' AND FYEndDate >= '" . $endDate . "' AND  status = ?";
            $res = $DB->dbRow();
            $totolLeave = $res['totalLeave'];
        }
        $balanceLeave = $totolLeave - $yearlyAppliedLeave;
        $data = array("yrAllowedLeaves" => $totolLeave, "yrBalanceLeaves" =>  $balanceLeave, "monAllowedLeaves" => $monthlyappliedLeave);
        $response = ['err' => 0, 'msg' => '', 'data' => $data];
    }

    return $response;
}


function getCurrFinancialYear()
{
    $currMon = date("m");
    if ($currMon > 3 && date("Y-m-d H:i:s") >= (date("Y")) . "-04-01 00:00:00") {
        $fStart = date("y");
        $fEnd   = (date("y") + 1);
    } else {
        $fStart = (date("y") - 1);
        $fEnd   = date("y");
    }
    return array("start" => $fStart, "end" => $fEnd);
}


function getUserLeaveData($userID = 0)
{
    $response = ['err' => 1, 'msg' => "User ID cannot be null.", 'data' => []];
    if (isset($userID) && $userID > 0) {
        global $DB;
        $disableDate = "date";
        $financialYear = getCurrFinancialYear();
        $startDate = $financialYear['start'] . '-' . '04-01';
        $endDate = $financialYear['end']  . '-' . '03-31';

        $DB->vals = array($userID, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT displayName,userName,userID,totalLeaves FROM " . $DB->pre . "x_admin_user  WHERE userID=? AND status=? ";
        $userData = $DB->dbRow();

        $DB->vals = array(1);
        $DB->types = "i";
        $DB->sql = "SELECT totalLeave,FYStartDate,FYEndDate FROM " . $DB->pre . "leave_setting WHERE FYStartDate <= '" . $startDate . "' AND FYEndDate >= '" . $endDate . "' AND  status = ?";
        $res = $DB->dbRow();
        $userData['totalLeaves'] = (isset($userData['totalLeaves']) && $userData['totalLeaves'] > 0) ? $userData['totalLeaves'] : $res['totalLeave'];

        $halfDayLeave = $fullDayLeave  = $monthlyappliedLeave = 0;
        $DB->vals = array(1, 1, -1, 'Cancel');
        $DB->types = "iiis";
        $DB->sql = "SELECT  COUNT(*)  AS totalAppliedLeave,LD.lType  
                    FROM `" . $DB->pre .  "leave_details` AS LD 
                    LEFT JOIN `" . $DB->pre . "leave` AS L ON LD.leaveID=L.leaveID 
                    WHERE  L.userID = '" . $userID . "' AND LD.leaveDate >='" . $startDate . "' AND LD.leaveDate <='" . $endDate . "' AND LD.status=? AND L.status=? AND LD.lType!=? AND L.leaveStatus!=? 
                    AND DATE(LD.leaveDate) NOT IN (
                        SELECT DATE(ahDate) FROM " . $DB->pre . "attendance_holidays WHERE ahDate >=" . $startDate . " AND ahDate <='" . $endDate . "' AND status = 1
                    )
                    GROUP BY LD.lType";
        $appliedLeave = $DB->dbRows();
        $userData['monthlyappliedLeave'] = 0;
        if ($DB->numRows > 0) {
            foreach ($appliedLeave as $k => $v) {
                if ($v['lType'] == 1) {
                    $fullDayLeave = $v['totalAppliedLeave'];
                } else {
                    $halfDayLeave += $v['totalAppliedLeave'];
                }
            }
            $halfDayLeave = ($halfDayLeave / 2) ?? 0;
            $userData['monthlyappliedLeave'] = $fullDayLeave + $halfDayLeave;
        }
        $response = ['err' => 0, 'msg' => '', 'data' => $userData];
    }

    return $response;
}

//display dates used in hlidays selected for leave
function displayDates($fromDate = '', $toDate = '', $format = "Y-m-d")
{
    $resp = ['err' => 1, 'msg' => 'Something went wrong!'];
    if (isset($fromDate) && $fromDate != '' && isset($toDate) && $toDate != '') {
        $dates = array();
        $current = strtotime($fromDate);
        $toDate = strtotime($toDate);
        $stepVal = '+1 day';
        while ($current <= $toDate) {
            $dates[] = date($format, $current);
            $current = strtotime($stepVal, $current);
        }
        $resp = ['err' => 0, 'msg' => 'Display dates fetched successfully.', 'data' => $dates];
    }

    return $resp;
}

function getHolidaysDD($fromDate = '', $toDate = '') // holidays selected for leave
{
    global $DB;
    $response = ['err' => 1, 'msg' => 'Dates are empty', 'data' => ''];
    if (isset($fromDate) && $fromDate != '' && isset($toDate) && $toDate != '') {
        $response['err'] = '';
        $response['data'] = '';
        $response['count'] = 0;
        $optAmWhere = array("sql" => "status = ?", "types" => "i", "vals" => array(1));
        $params = ["table" => $DB->pre . "attendance_holidays", "key" => "ahID", "val" => "ahDate", "where" => $optAmWhere];
        $holidayArr  = getDataArray($params);
        $selectType = json_decode(SELECTTYPE, 1);
        $resp =  displayDates($fromDate, $toDate);
        if ($resp['err'] == 0) {
            $date = $resp['data'];
            for ($i = 0; $i < count($date); $i++) {

                $thisDate = date("Y-m-d", strtotime($date[$i]));
                if (in_array($thisDate, $holidayArr)) {
                    $leaveDetailsArr[$i]['selectType'] = "<option value='-1'>Official holiday</option>";
                } else {
                    $leaveDetailsArr[$i]['selectType'] = getArrayDD(["data" => array("data" => $selectType), "selected" => 0]); //getArrayDD($selectType);
                }
                $leaveDetailsArr[$i]['leaveDate'] = $date[$i];

                $leaveDetailsArr[$i]['leaveDateFormat'] = date("l, jS F Y", strtotime($date[$i]));
            }
            $response['data'] = $leaveDetailsArr;
        } else {
            $response = $resp;
        }
    }

    return $response;
}

function addEmployeeLeave($leaveData = [])
{
    $response = array("err" => 1, "Something went wrong!");
    if (isset($leaveData) && count($leaveData) > 0) {
        global $DB;
        $reason = '';
        if (isset($leaveData["leaveID"]))
            $leaveData["leaveID"] = intval($leaveData["leaveID"]);
        if (isset($leaveData["leaveType"]))
            $leaveData["leaveType"] = cleanTitle($leaveData["leaveType"]);
        if (isset($leaveData["reason"]))
            $reason = $leaveData["reason"] = cleanTitle($leaveData["reason"]);
        if (isset($leaveData["fromDate"]))
            $leaveData["fromDate"] = cleanTitle($leaveData["fromDate"]);
        if (isset($leaveData["toDate"]))
            $leaveData["toDate"] = cleanTitle($leaveData["toDate"]);
        if (isset($leaveData["leaveStatus"]))
            $leaveData["leaveStatus"] = cleanTitle($leaveData["leaveStatus"]);
        if (isset($leaveData["emailID"]))
            $leaveData["emailID"] = cleanTitle($leaveData["emailID"]);
        $leaveData["attachedFile"]  = mxGetFileName("attachedFile");
        if (isset($leaveData['userID']) && $leaveData['userID'] > 0) {
            $leaveData["userID"] = $leaveData['userID'];
        } else {
            $leaveData["userID"] = $_SESSION[SITEURL]["MXID"];
        }
        $leaveData['dateAdded'] = date('Y-m-d');
        $DB->table = $DB->pre . "leave";
        $DB->data = $leaveData;
        if ($DB->dbInsert()) {
            $leaveID = $DB->insertID;
            if ($leaveID) {
                $response = array("err" => 1, 'msg' => 'Error occurred while updating user leaves.');
                addUpdateLeaveDetails($leaveID, $leaveData);
                $year = date("Y", strtotime($_POST["fromDate"]));
                $month = date("m", strtotime($_POST["fromDate"]));
                $resp = updateUserLeaves($year, $month, $_POST["userID"]);
                if ($resp == 'OK') {
                    $resp = sendLeaveMail($leaveID, $reason);
                    if ($resp['err'] == 0) {
                        $response = array("err" => 0, "param" => "id=$leaveID");
                    } else {
                        $response = $resp;
                    }
                }
            }
        } else {
            $response = array("err" => 1, 'msg' => 'Error occurred while adding leave.');
        }
    }
    return $response;
}

function addUpdateLeaveDetails($leaveID = 0, $leaveData = [])
{
    global $DB, $TPL;
    if (isset($leaveID) && intval($leaveID) > 0) {
        $DB->vals = array($leaveID);
        $DB->types = "i";
        $DB->sql = "DELETE FROM " . $DB->pre . "leave_details WHERE leaveID=?";
        $DB->dbQuery();
        $DB->table = $DB->pre . "leave_details";
        if (isset($leaveData["leaveDetailID"]) && count($leaveData["leaveDetailID"]) > 0) {
            for ($i = 0; $i < count($leaveData["leaveDetailID"]); $i++) {
                $arrIn = array(
                    "leaveID" => $leaveID,
                    "userID" => $leaveData["userID"] ?? 0,
                    "leaveDate" => $leaveData["leaveDate"][$i],
                    "lType" => $leaveData["lType"][$i],
                );
                $DB->data = $arrIn;
                $DB->dbInsert();
            }
        }
    }
}

//END :  functions common in xsite and xadmin
