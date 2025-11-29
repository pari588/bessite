<?php
/**
 * Fuel Expense List & Report Page
 * Displays monthly summary report with payment status breakdown
 * Uses xadmin standard look and feel
 */

global $DB, $MXFRM, $MXSTATUS, $TPL;

// Initialize form handler
$MXFRM = new mxForm();

// Get list of vehicles for dropdown
$vehicleOptions = array("" => "All Vehicles");
$DB->sql = "SELECT vehicleID, vehicleName FROM `" . $DB->pre . "vehicle` WHERE status=1 ORDER BY vehicleName";
$DB->dbRows();
if (isset($DB->rows) && is_array($DB->rows)) {
    foreach ($DB->rows as $v) {
        $vehicleOptions[$v["vehicleID"]] = $v["vehicleName"];
    }
}

// Build vehicle dropdown for search
$vehicleDD = getArrayDD(array("data" => array("data" => $vehicleOptions), "selected" => ($_GET["vehicleID"] ?? "")));

// Build payment status dropdown for search
$statusOptions = array("" => "All Status", "Paid" => "Paid", "Unpaid" => "Unpaid");
$statusDD = getArrayDD(array("data" => array("data" => $statusOptions), "selected" => ($_GET["paymentStatus"] ?? "")));

// Define search fields
$arrSearch = array(
    array("type" => "select", "name" => "vehicleID",
          "value" => $vehicleDD,
          "title" => "Vehicle", "where" => "AND vehicleID=?", "dtype" => "s"),
    array("type" => "select", "name" => "paymentStatus",
          "value" => $statusDD,
          "title" => "Payment Status", "where" => "AND paymentStatus=?", "dtype" => "s"),
    array("type" => "date", "name" => "fromDate", "value" => $_GET["fromDate"] ?? "",
          "title" => "From Date", "where" => "AND billDate >= ?", "dtype" => "s"),
    array("type" => "date", "name" => "toDate", "value" => $_GET["toDate"] ?? "",
          "title" => "To Date", "where" => "AND billDate <= ?", "dtype" => "s"),
);

// Generate search form
$strSearch = $MXFRM->getFormS($arrSearch);

// Build count query - use mxFramework values directly
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;

$DB->sql = "SELECT fuelExpenseID FROM `" . $DB->pre . "fuel_expense`
            WHERE status=?" . $MXFRM->where;
$DB->dbRows();
$MXTOTREC = $DB->numRows;

if (!$MXFRM->where && $MXTOTREC < 1)
    $strSearch = "";

echo $strSearch;
?>

<div class="wrap-right">
    <?php echo getPageNav('', '', array("add")); ?>

    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("Date", "billDate", ' width="11%" align="center"'),
                array("Vehicle", "vehicleName", ' width="20%" align="left"'),
                array("Amount", "expenseAmount", ' width="12%" align="right"'),
                array("Qty (L)", "fuelQuantity", ' width="10%" align="center"'),
                array("Status", "paymentStatus", ' width="10%" align="center"'),
                array("Paid Date", "paidDate", ' width="11%" align="center"'),
                array("Bill Image", "billImage", ' width="10%" align="center"'),
                array("Remarks", "remarks", ' width="13%" align="left"'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;

            $DB->sql = "SELECT fe.fuelExpenseID, fe.billDate, fe.expenseAmount, fe.fuelQuantity, fe.paymentStatus, fe.paidDate, fe.remarks, fe.billImage, v.vehicleName
                        FROM `" . $DB->pre . "fuel_expense` fe
                        LEFT JOIN `" . $DB->pre . "vehicle` v ON fe.vehicleID = v.vehicleID
                        WHERE fe.status=?" . $MXFRM->where . mxOrderBy("fe.billDate DESC ") . mxQryLimit();
            $rt = $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $expense) {
                        $isPaid = $expense["paymentStatus"] === "Paid";
                        $statusBadge = $isPaid ?
                            '<span style="background-color: #28a745; color: white; padding: 3px 8px; border-radius: 3px; cursor: pointer;" onclick="markPaymentStatus(' . $expense["fuelExpenseID"] . ', \'Unpaid\')">PAID</span>' :
                            '<span style="background-color: #ffc107; color: black; padding: 3px 8px; border-radius: 3px; cursor: pointer;" onclick="markPaymentStatus(' . $expense["fuelExpenseID"] . ', \'Paid\')">UNPAID</span>';

                        // Bill image download link
                        $billImageLink = "";
                        if (!empty($expense["billImage"])) {
                            $fileExt = strtolower(pathinfo($expense["billImage"], PATHINFO_EXTENSION));
                            $icon = ($fileExt === 'pdf') ? '📄' : '🖼️';
                            $fileType = ($fileExt === 'pdf') ? 'PDF' : 'Image';
                            $billImageLink = '<a href="/uploads/fuel-expense/' . htmlspecialchars($expense["billImage"]) . '" target="_blank" download style="background-color: #007bff; color: white; padding: 4px 8px; border-radius: 3px; text-decoration: none; font-size: 0.85rem;">' . $icon . ' ' . $fileType . '</a>';
                        } else {
                            $billImageLink = '<span style="color: #999;">No file</span>';
                        }
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $expense["fuelExpenseID"]); ?>
                            <td width="11%" align="center" title="Date"><?php echo date('d-M-Y', strtotime($expense["billDate"])); ?></td>
                            <td width="20%" align="left" title="Vehicle">
                                <?php echo getViewEditUrl("id=" . $expense["fuelExpenseID"], $expense["vehicleName"] ?? "Unknown"); ?>
                            </td>
                            <td width="12%" align="right" title="Amount">₹ <?php echo number_format($expense["expenseAmount"], 2); ?></td>
                            <td width="10%" align="center" title="Qty (L)"><?php echo number_format($expense["fuelQuantity"], 2) ?? "-"; ?></td>
                            <td width="10%" align="center" title="Status"><?php echo $statusBadge; ?></td>
                            <td width="11%" align="center" title="Paid Date"><?php echo $expense["paidDate"] ? date('d-M-Y', strtotime($expense["paidDate"])) : "-"; ?></td>
                            <td width="10%" align="center" title="Bill Image"><?php echo $billImageLink; ?></td>
                            <td width="13%" align="left" title="Remarks"><?php echo htmlspecialchars(substr($expense["remarks"] ?? "", 0, 30)); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        <?php } else { ?>
            <div class="no-records">No expenses found</div>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">
// Mark single expense as paid/unpaid by clicking the status badge
function markPaymentStatus(fuelExpenseID, status) {
    if (!confirm('Mark this expense as ' + status + '?')) {
        return;
    }

    var action = status === 'Paid' ? 'MARK_PAID' : 'MARK_UNPAID';

    $.post(
        window.location.href.replace(/\/[^\/]*$/, '/x-fuel-expense.inc.php'),
        {
            xAction: action,
            fuelExpenseID: fuelExpenseID
        },
        function(response) {
            try {
                var result = JSON.parse(response);
                if (result.err === 0) {
                    alert('Payment status updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + result.msg);
                }
            } catch(e) {
                alert('Error processing request');
                console.log(response);
            }
        }
    );
}
</script>
