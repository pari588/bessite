<?php
// Get HRMS settings
$DB->vals = array(1);
$DB->types = "i";
$DB->sql = "SELECT * FROM `" . $DB->pre . "hrms_settings` WHERE status=?";
$settings = $DB->dbRows();
$settingsArr = array();
foreach ($settings as $s) {
    $settingsArr[$s['settingKey']] = $s;
}

// Get email recipients
$DB->vals = array($MXSTATUS);
$DB->types = "i";
$DB->sql = "SELECT * FROM `" . $DB->pre . "hr_email_recipients` WHERE status=?";
$DB->dbQuery();
$MXTOTREC = $DB->numRows;
?>

<div class="wrap-right">
    <?php echo getPageNav(); ?>

    <div class="wrap-data">
        <!-- HRMS Settings Form -->
        <h3 class="form-head">HRMS Settings</h3>
        <form id="settingsForm">
            <ul class="tbl-form">
                <li>
                    <label>Work Start Time</label>
                    <input type="time" name="settings[work_start_time]" value="<?php echo $settingsArr['work_start_time']['settingValue'] ?? '09:00'; ?>">
                    <span class="help-block">Default check-in time</span>
                </li>
                <li>
                    <label>Work End Time</label>
                    <input type="time" name="settings[work_end_time]" value="<?php echo $settingsArr['work_end_time']['settingValue'] ?? '18:00'; ?>">
                    <span class="help-block">Default check-out time</span>
                </li>
                <li>
                    <label>Late Grace Period (minutes)</label>
                    <input type="number" name="settings[late_grace_minutes]" value="<?php echo $settingsArr['late_grace_minutes']['settingValue'] ?? '15'; ?>" min="0" max="60">
                    <span class="help-block">Minutes before marking late</span>
                </li>
                <li>
                    <label>Early Checkout Grace (minutes)</label>
                    <input type="number" name="settings[early_checkout_grace_minutes]" value="<?php echo $settingsArr['early_checkout_grace_minutes']['settingValue'] ?? '15'; ?>" min="0" max="60">
                    <span class="help-block">Minutes before marking early checkout</span>
                </li>
                <li>
                    <label>Working Days/Month</label>
                    <input type="number" name="settings[working_days_per_month]" value="<?php echo $settingsArr['working_days_per_month']['settingValue'] ?? '26'; ?>" min="20" max="31">
                    <span class="help-block">For salary calculation</span>
                </li>
                <li>
                    <label>Email Send Day</label>
                    <input type="number" name="settings[email_send_day]" value="<?php echo $settingsArr['email_send_day']['settingValue'] ?? '1'; ?>" min="1" max="28">
                    <span class="help-block">Day of month to send salary emails</span>
                </li>
                <li>
                    <label>Email Send Time</label>
                    <input type="time" name="settings[email_send_time]" value="<?php echo $settingsArr['email_send_time']['settingValue'] ?? '09:00'; ?>">
                    <span class="help-block">Time to send salary emails</span>
                </li>
            </ul>
            <div class="mx-btn">
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="wrap-data" style="margin-top:20px;">
        <h3 class="form-head">HR Email Recipients <a href="?mod=hr-email-settings&act=add" class="btn btn-sm" style="float:right;">+ Add Recipient</a></h3>
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#ID", "recipientID", ' width="1%" align="center"', true),
                array("Name", "recipientName", ' width="20%" align="left"'),
                array("Email", "recipientEmail", ' width="25%" align="left"'),
                array("Email Types", "emailTypes", ' width="40%" align="left"'),
            );

            $DB->vals = array($MXSTATUS);
            $DB->types = "i";
            $DB->sql = "SELECT * FROM `" . $DB->pre . "hr_email_recipients` WHERE status=? ORDER BY recipientName ASC";
            $DB->dbRows();

            $emailTypeLabels = array(
                'individual_slip' => 'Individual Slip',
                'hr_master' => 'HR Master Report',
                'attendance_summary' => 'Attendance Summary'
            );
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        // Format email types using xadmin label classes
                        $types = explode(',', $d['emailTypes']);
                        $typeBadges = '';
                        foreach ($types as $t) {
                            $t = trim($t);
                            if (isset($emailTypeLabels[$t])) {
                                $labelClass = 'label label-default';
                                if ($t == 'individual_slip') $labelClass = 'label label-info';
                                elseif ($t == 'hr_master') $labelClass = 'label label-success';
                                elseif ($t == 'attendance_summary') $labelClass = 'label label-warning';
                                $typeBadges .= '<span class="' . $labelClass . '">' . $emailTypeLabels[$t] . '</span> ';
                            }
                        }
                        $d['emailTypes'] = $typeBadges ?: '-';
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["recipientID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3]) && $v[3] != '') {
                                        echo getViewEditUrl("id=" . $d["recipientID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]] ?? "-";
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="alert alert-info">No email recipients configured. <a href="?mod=hr-email-settings&act=add">Add one now</a></p>
        <?php } ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#settingsForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&xAction=updateSettings&token=' + $('input[name="token"]').val();

        $.ajax({
            url: 'x-hr-email-settings.inc.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                var res = JSON.parse(response);
                if (res.err == 0) {
                    alert(res.alert || 'Settings saved successfully');
                } else {
                    alert(res.msg || 'Error saving settings');
                }
            },
            error: function() {
                alert('Request failed');
            }
        });
    });
});
</script>
