<?php
function sendReminderOfLeaves()
{
    global $DB;
    $str = '';

    $newArr = getCommunicationEmail($newArr = '');
    $ccEmail   = $newArr;
    $fromEmail = "";
    $fromName  = "";
    $subject   = "Reminder Of Employee Leaves Upto 7 Days";
    $currentDate = date("Y-m-d");
    $estimateDate = date('Y-m-d', strtotime($currentDate . ' +7 days'));

    if ($DB->numRows > 0) {
        $DB->vals = array(1, $currentDate, $estimateDate);
        $DB->types = "iss";
        $DB->sql = "SELECT AU.displayName, L.fromDate, L.toDate, L.leaveType, L.reason, LT.leaveTypeName  FROM " . $DB->pre . "leave AS L
        LEFT JOIN " . $DB->pre . "x_admin_user AS AU ON AU.userID=L.userID 
        LEFT JOIN " . $DB->pre . "leave_type AS LT ON LT.leaveTypeID = L.leaveType
        WHERE L.status=? AND L.fromDate >= ? AND L.toDate <= ?";
        $result = $DB->dbRows();
        $subject = "Employee Leaves on : " . date("d, F Y");
        $str = 'List of Employee Leaves on ' . date("d, F Y") . "<br><br>";
        $str .= '<table width="50%" border="1" style="border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th align="center">Sr.No</th>
                                        <th>Name</th>
                                        <th>Leave Type</th>
                                        <th>Leave Reason</th>
                                        <th>Applied From Date </th>
                                        <th>Applied To date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                ';
        foreach ($result as $k => $val) {

            if ($val['lType'] == 1) {
                $leaveType = "First Half";
            } else if ($val['lType'] == 2) {
                $leaveType = "Second Half";
            } else {
                $leaveType = "Full Day";
            }

            $str .= '<tr>
                                    <td align="center">' . ($k + 1) . '</td>
                                    <td>' . $val['displayName'] . '</td>
                                    <td align="center">' . $leaveType . '-' . $val['leaveTypeName'] . ' </td>
                                    <td>' . $val['reason'] . '</td>
                                    <td>' . $val['fromDate'] . '</td>
                                    <td>' . $val['toDate'] . '</td>
                                    
                                </tr>';
        }
        $str .= '</tbody></table>';

        $mailData = array();
        $mailData['toEmail'] =  $newArr;
        $mailData['toName']   = $_POST['displayName'];
        $mailData['bccEmail'] = array();
        $mailData['subject']  = $subject;
        $mailData['body'] = $str;

        if (sendEmail($mailData)) {
            echo "success";
        } else {
            echo "error";
        }
    }
}

//Start: To check if that date has a holiday record.
function hasRecord($date = "")
{
    global $DB;
    $str = false;
    $DB->vals = array($date);
    $DB->types = "s";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "attendance_holidays` WHERE ahDate =?";
    $DB->dbRows();
    if ($DB->numRows > 0) {
        $str = true;
    }
    return $str;
}

function getSundays($startDate = "", $endDate = "")
{
    $start_date = strtotime($startDate);
    $end_date = strtotime($endDate);

    // Create an empty array to store the Sundays
    $sundays = array();

    // Iterate through each day within the time frame
    $current_date = $start_date;
    while ($current_date <= $end_date) {
        // Check if the current day is a Sunday (0 = Sunday, 6 = Saturday)
        if (date('w', $current_date) == 0) {
            $sundays[] = date('Y-m-d', $current_date); // Add the Sunday to the array
        }

        // Move to the next day
        $current_date = strtotime('+1 day', $current_date);
    }
    return $sundays;
}

function addSundayHolidays($startDate = "", $endDate = "")
{
    global $DB;
    $sundays = getSundays($startDate, $endDate);
    $str = "ERR";
    if (count($sundays) > 0) {
        foreach ($sundays as $sun) {
            $date  = $sun;
            $dbdata = array();
            $dbdata['status'] = 1;
            $dbdata['ahDate'] = $date;
            $dbdata['holidayType'] = 1;
            $dbdata['ahReason'] = 'Weekly off';
            if (!hasRecord($date)) {
                $DB->table = $DB->pre . "attendance_holidays";
                $DB->data  = $dbdata;
                $DB->dbInsert();
                $str = "OK";
            } else {
                $DB->table = $DB->pre . "attendance_holidays";
                $DB->data  = $dbdata;
                $DB->dbUpdate("ahDate='$date'");
                $DB->dbUpdate("ahDate=?", "s", array($date));
                $str = "OK";
            }
        }
    }
    return $str;
}

function sendReminderon31March()
{
    global $DB;
    $str = '';
    $newArr = getCommunicationEmail($newArr = '');
    $fromEmail = "";
    $fromName  = "";
    $subject   = "Reminder Of  Add Total Number of Leaves of Next Financial Year on leaveSetting Module";
    $currentDate = date("Y-m-d");
    $inputDate =  Date('Y-03-31');

    if ($inputDate == $currentDate) {
        $lastYear = Date('Y') - 1;
        $nextYear = Date('Y') + 1;
        $nextFYStartDate = Date('Y-04-01');
        $nextFYEndDate = Date($nextYear . '-03-31');
        addSundayHolidays($nextFYStartDate, $nextFYEndDate); //Add sundays in holidays
        //Check already added or not 
        $DB->vals = array(1, Date($nextFYStartDate), $nextFYEndDate);
        $DB->types = "iss";
        $DB->sql = "SELECT * 
                    FROM `" . $DB->pre . "leave_setting`
                    WHERE status=? AND DATE(FYStartDate)=? AND DATE(FYEndDate)=?";
        $DB->dbRow();
        if ($DB->numRows == 0) {
            //Get existing year details and add in to new year
            $DB->vals = array(1, Date($lastYear . '-04-01'), Date('Y-03-31'));
            $DB->types = "iss";
            $DB->sql = "SELECT * 
                        FROM `" . $DB->pre . "leave_setting` 
                        WHERE status=? AND DATE(FYStartDate)=? AND DATE(FYEndDate)=?";
            $leaveSettingDataArr = $DB->dbRow();

            $arrIn = array(
                "FYStartDate" => $nextFYStartDate,
                "FYEndDate" => $nextFYEndDate,
                "totalLeave" => $leaveSettingDataArr["totalLeave"],
            );

            $DB->table = $DB->pre . "leave_setting";
            $DB->data = $arrIn;
            $DB->dbInsert();
        }

        $subject = "Reminder for Update New Year Leave Setting : " . date("d, F Y");
        $body = '<html><head><meta charset="utf-8">
                <title>Bombay Engineering Syndicate</title>
                    </head>
                    <body>
                    <div style="font-family:Arial; font-size:14px; color:#172049; width:650px; display:inline-block;">
                        <p>Your leave setting are updated for the Fiancial Year ' . date('Y') . '-' . $nextYear . ' </p>
                    </div>
                </body>
                </html>';
        $mailData['toEmail']  = $newArr;
        $mailData['ccEmail']  = array();
        $mailData['bccEmail'] = array();
        $mailData['subject']  = $subject;
        $mailData['body'] = $body;
        if (sendEmail($mailData)) {
            echo "Success";
        } else {
            echo "Error";
        }
    } else {
        echo "Sorry, today is not a 31st March";
    }
}

function getUserOffDays($userID = 0)
{
    global $DB;
    $userOffDaysArr = [];
    $DB->vals = array($userID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "user_off_days  WHERE userID=?";
    $userOffDaysData = $DB->dbRows();
    foreach ($userOffDaysData as $k => $v) {
        $userOffDaysArr[$k] = $v['weekdayNo'];
    }
    return $userOffDaysArr;
}

function autoMarkin()
{
    global $DB;
    $str = "Auto Markin failed.";
    $currDate = date("Y-m-d");
    $currentDayNo = date('N', strtotime($currDate));

    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "user` WHERE status=? AND userType=?";
    $userData = $DB->dbRows();


    if ($DB->numRows > 0) {
        foreach ($userData as $k => $v) {

            $userOffDaysArr = getUserOffDays($v['userID']);
            if (!in_array($currentDayNo, $userOffDaysArr)) {

                $DB->vals = array(1, $currDate, $v['userID']);
                $DB->types = "isi";
                $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=?  AND dmDate=? AND userID=?";
                $driverManagement = $DB->dbRow();
                if ($DB->numRows == 0) {

                    $userFromTime = date("H:i:s", strtotime($v["userFromTime"]));

                    $arrIn = array(
                        "dmDate" => date("Y-m-d"),
                        "fromTime" => date("Y-m-d " . $userFromTime),
                        "recordType" => 1,
                        "isVerify" => 0,
                        "userID" => $v['userID']
                    );

                    $DB->table = $DB->pre . "driver_management";
                    $DB->data = $arrIn;
                    if ($DB->dbInsert()) {
                        $str = "Auto Markin successfully.";
                    }
                } elseif ($driverManagement['fromTime'] != '') {
                    $str = "Markin already done.";
                } elseif ($driverManagement['fromTime'] != '' && $driverManagement['toTime'] != '' && $driverManagement['dmDate'] != '') {
                    $str = "Markout Alredy Done";
                } else {
                    $str = "Markin already done.";
                }

                echo $str;
            }
        }
    }
}

function autoMarkout()
{
    global $DB;
    $str = "Auto Markout failed.";
    $prevDate = date("Y-m-d", strtotime("-1 day"));
    $prevDayNo = date('N', strtotime($prevDate));

    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "user` WHERE status=? AND userType=?";
    $userData = $DB->dbRows();

    if ($DB->numRows > 0) {
        foreach ($userData as $k => $v) {

            $userOffDaysArr = getUserOffDays($v['userID']);



            if (!in_array($prevDayNo, $userOffDaysArr)) {

                $DB->vals = array(1, $prevDate, $v['userID']);
                $DB->types = "isi";
                $DB->sql = "SELECT * FROM `" . $DB->pre . "driver_management` WHERE status=?  AND dmDate=? AND userID=?";
                $driverManagement = $DB->dbRow();

                if ($DB->numRows > 0) {
                    if ($driverManagement['toTime'] == "") {
                        $driverManagementID = intval($driverManagement['driverManagementID']);


                        $userToTime = date("H:i:s", strtotime($v["userToTime"]));
                        $toTime = date("Y-m-d " . $userToTime, strtotime("-1 day"));

                        $ch = curl_init();
                        $requestParam = "xAction=UPDATE&driverManagementID=" . $driverManagementID . "&toTime=" . $toTime . "&fromTime=" . $driverManagement['fromTime'] . "&expenseAmt=" . $driverManagement['expenseAmt'] . "&dmDate=" . $driverManagement['dmDate'];
                        curl_setopt($ch, CURLOPT_URL, SITEURL . "/xadmin/mod/driver-management/x-driver-management.inc.php");
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestParam);
                        $result = curl_exec($ch);

                        $resultArr = json_decode($result, true);
                        // echo "<pre>";
                        // print_r($resultArr);
                        // exit;

                        if ($resultArr['err'] == 0) {
                            $str = "Auto Markout Successfully";
                        }
                    } else {
                        $str = "Markout already done.";
                    }
                }
                echo $str;
            }
        }
    }
}

if ($_REQUEST['xAction']) {
    require("../core/core.inc.php");
    require_once("../xadmin/inc/site.inc.php");
    switch ($_REQUEST['xAction']) {
        case "sendReminderOfLeaves":
            echo sendReminderOfLeaves();
            break;
        case "sendReminderon31March":
            echo sendReminderon31March();
            break;
        case "autoMarkin":
            echo autoMarkin();
            break;
        case "autoMarkout":
            echo autoMarkout();
            break;
    }
}
