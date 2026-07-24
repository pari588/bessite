<?php
// ============================================================================
// WhatsApp Inquiry List - Admin View
// ============================================================================

// Status filter dropdown options
$statusOpts = '<option value="">-- All --</option>';
$statusOpts .= '<option value="new"' . (($_GET['inquiryStatus'] ?? '') === 'new' ? ' selected' : '') . '>New</option>';
$statusOpts .= '<option value="contacted"' . (($_GET['inquiryStatus'] ?? '') === 'contacted' ? ' selected' : '') . '>Contacted</option>';
$statusOpts .= '<option value="quoted"' . (($_GET['inquiryStatus'] ?? '') === 'quoted' ? ' selected' : '') . '>Quoted</option>';
$statusOpts .= '<option value="converted"' . (($_GET['inquiryStatus'] ?? '') === 'converted' ? ' selected' : '') . '>Converted</option>';
$statusOpts .= '<option value="closed"' . (($_GET['inquiryStatus'] ?? '') === 'closed' ? ' selected' : '') . '>Closed</option>';

// Product type filter
$typeOpts = '<option value="">-- All --</option>';
$typeOpts .= '<option value="pump"' . (($_GET['productType'] ?? '') === 'pump' ? ' selected' : '') . '>Pump</option>';
$typeOpts .= '<option value="motor"' . (($_GET['productType'] ?? '') === 'motor' ? ' selected' : '') . '>Motor</option>';
$typeOpts .= '<option value="drive"' . (($_GET['productType'] ?? '') === 'drive' ? ' selected' : '') . '>Drive</option>';
$typeOpts .= '<option value="other"' . (($_GET['productType'] ?? '') === 'other' ? ' selected' : '') . '>Other</option>';

// Assigned to filter
$assignedOpts = '<option value="">-- All --</option>';
$assignedOpts .= '<option value="mumbai"' . (($_GET['assignedTo'] ?? '') === 'mumbai' ? ' selected' : '') . '>Mumbai</option>';
$assignedOpts .= '<option value="ahmedabad"' . (($_GET['assignedTo'] ?? '') === 'ahmedabad' ? ' selected' : '') . '>Ahmedabad</option>';

$arrSearch = array(
    array("type" => "text", "name" => "inquiryID", "title" => "#ID", "where" => "AND inquiryID=?", "dtype" => "i", "attr" => "style='width:50px;'"),
    array("type" => "text", "name" => "referenceNumber", "title" => "Ref #", "where" => "AND referenceNumber LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:100px;'"),
    array("type" => "text", "name" => "customerName", "title" => "Customer", "where" => "AND customerName LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:120px;'"),
    array("type" => "text", "name" => "fromNumber", "title" => "Phone", "where" => "AND fromNumber LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:100px;'"),
    array("type" => "text", "name" => "city", "title" => "City", "where" => "AND city LIKE CONCAT('%',?,'%')", "dtype" => "s", "attr" => "style='width:80px;'"),
    array("type" => "select", "name" => "productType", "title" => "Type", "value" => $typeOpts, "where" => "AND productType=?", "dtype" => "s"),
    array("type" => "select", "name" => "inquiryStatus", "title" => "Status", "value" => $statusOpts, "where" => "AND inquiryStatus=?", "dtype" => "s"),
    array("type" => "select", "name" => "assignedTo", "title" => "Office", "value" => $assignedOpts, "where" => "AND assignedTo=?", "dtype" => "s"),
    array("type" => "date", "name" => "fromDate", "title" => "From", "where" => "AND DATE(createdAt) >=?", "dtype" => "s", "attr" => "style='width:100px;'"),
    array("type" => "date", "name" => "toDate", "title" => "To", "where" => "AND DATE(createdAt) <=?", "dtype" => "s", "attr" => "style='width:100px;'"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT inquiryID FROM " . $DB->pre . "wa_inquiry WHERE status=?" . $MXFRM->where;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;

if (!$MXFRM->where && $MXTOTREC < 1)
    $strSearch = "";

echo $strSearch;
?>

<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#", "inquiryID", ' width="1%" align="center"'),
                array("Ref #", "referenceNumber", ' width="8%" align="center"'),
                array("Customer", "customerName", ' width="10%" align="left"'),
                array("Phone", "fromNumber", ' width="8%" align="left"'),
                array("City", "city", ' width="6%" align="center"'),
                array("Type", "productType", ' width="5%" align="center"'),
                array("Product", "selectedProductTitle", ' width="14%" align="left"'),
                array("Requirement", "requirementText", ' width="15%" align="left"'),
                array("Office", "assignedTo", ' width="6%" align="center"'),
                array("Status", "inquiryStatus", ' width="7%" align="center"'),
                array("Follow-up", "followUpDate", ' width="8%" align="center"'),
                array("Date", "createdAt", ' width="10%" align="center"'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT * FROM " . $DB->pre . "wa_inquiry WHERE status=?" . $MXFRM->where . mxOrderBy("inquiryID DESC") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr>
                        <?php echo getListTitle($MXCOLS); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($DB->rows as $d) { ?>
                        <tr>
                            <?php echo getMAction("mid", $d["inquiryID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    $val = $d[$v[1]] ?? '';
                                    switch ($v[1]) {
                                        case 'createdAt':
                                            echo $val ? date('d M Y H:i', strtotime($val)) : '';
                                            break;
                                        case 'followUpDate':
                                            if ($val) {
                                                $isOverdue = strtotime($val) < strtotime('today');
                                                echo '<span style="color:' . ($isOverdue ? 'red' : 'inherit') . '">' . date('d M Y', strtotime($val)) . '</span>';
                                            }
                                            break;
                                        case 'inquiryStatus':
                                            $colors = ['new' => '#28a745', 'contacted' => '#17a2b8', 'quoted' => '#ffc107', 'converted' => '#007bff', 'closed' => '#6c757d'];
                                            $color = $colors[$val] ?? '#333';
                                            echo '<span style="background:' . $color . ';color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;">' . ucfirst($val) . '</span>';
                                            break;
                                        case 'productType':
                                            echo '<span style="text-transform:uppercase;font-size:11px;font-weight:bold;">' . htmlspecialchars($val) . '</span>';
                                            break;
                                        case 'assignedTo':
                                            echo ucfirst($val);
                                            break;
                                        case 'requirementText':
                                        case 'selectedProductTitle':
                                            $truncated = mb_strlen($val) > 40 ? mb_substr($val, 0, 40) . '...' : $val;
                                            echo htmlspecialchars($truncated);
                                            break;
                                        case 'fromNumber':
                                            // Format as +91 XXXXX XXXXX
                                            if (strlen($val) >= 10) {
                                                $last10 = substr($val, -10);
                                                echo preg_replace('/^(\d{5})(\d{5})$/', '$1 $2', $last10);
                                            } else {
                                                echo htmlspecialchars($val);
                                            }
                                            break;
                                        case 'inquiryID':
                                        case 'customerName':
                                            echo getViewEditUrl("id=" . $d["inquiryID"], htmlspecialchars($val));
                                            break;
                                        default:
                                            echo htmlspecialchars($val);
                                            break;
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <div class="no-records">
                <p>No WhatsApp inquiries found. <?php if ($MXFRM->where) echo 'Try adjusting your search filters.'; ?></p>
            </div>
        <?php } ?>
    </div>
</div>

<style>
.tbl-list { font-size: 13px; }
.tbl-list thead { background-color: #f5f5f5; }
.tbl-list tbody tr:hover { background-color: #f0fff0; }
.no-records { padding: 40px 20px; text-align: center; color: #666; background-color: #f9f9f9; border-radius: 4px; margin: 20px 0; }
</style>
