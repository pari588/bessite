<?php
// Document type labels
$docTypes = array(
    'aadhaar' => 'Aadhaar Card',
    'pan' => 'PAN Card',
    'passport' => 'Passport',
    'photo' => 'Photo',
    'appointment_letter' => 'Appointment Letter',
    'increment_letter' => 'Increment Letter',
    'exit_letter' => 'Exit Letter',
    'experience_letter' => 'Experience Letter',
    'policy' => 'Policy Document',
    'training_cert' => 'Training Certificate',
    'other' => 'Other'
);

// Build document type options
$docTypeOpts = '<option value="">-- All Types --</option>';
foreach ($docTypes as $key => $label) {
    $docTypeOpts .= '<option value="' . $key . '">' . $label . '</option>';
}

// Search array
$arrSearch = array(
    array("type" => "text", "name" => "displayName", "title" => "Employee", "where" => "AND U.displayName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
    array("type" => "select", "name" => "documentType", "value" => $docTypeOpts, "title" => "Document Type", "where" => "AND D.documentType=?", "dtype" => "s"),
    array("type" => "text", "name" => "documentName", "title" => "Document Name", "where" => "AND D.documentName LIKE CONCAT('%',?,'%')", "dtype" => "s"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);

// Build query
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT D.*, U.displayName, U.employeeCode
            FROM `" . $DB->pre . "employee_document` AS D
            LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON D.userID = U.userID
            WHERE D.status=? " . $MXFRM->where;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;

if (!$MXFRM->where && $MXTOTREC < 1)
    $strSearch = "";

echo $strSearch;
?>

<style>
.doc-type-badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; }
.doc-type-aadhaar { background: #e3f2fd; color: #1565c0; }
.doc-type-pan { background: #fff3e0; color: #e65100; }
.doc-type-passport { background: #e8f5e9; color: #2e7d32; }
.doc-type-appointment_letter { background: #f3e5f5; color: #7b1fa2; }
.doc-type-other { background: #f5f5f5; color: #616161; }
.file-link { color: #1976d2; text-decoration: none; }
.file-link:hover { text-decoration: underline; }
.expired { color: #dc3545; font-weight: bold; }
.expiring-soon { color: #ffc107; font-weight: bold; }
</style>

<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $MXCOLS = array(
                array("#ID", "documentID", ' width="1%" align="center"', true),
                array("Employee", "displayName", ' width="20%" align="left"'),
                array("Document Type", "documentType", ' width="15%" align="center"'),
                array("Document Name", "documentName", ' width="20%" align="left"'),
                array("File", "fileName", ' width="15%" align="center"', '', 'nosort'),
                array("Valid Upto", "validUpto", ' width="10%" align="center"'),
                array("Uploaded", "createdAt", ' width="10%" align="center"'),
            );

            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT D.*, U.displayName, U.employeeCode
                        FROM `" . $DB->pre . "employee_document` AS D
                        LEFT JOIN `" . $DB->pre . "x_admin_user` AS U ON D.userID = U.userID
                        WHERE D.status=? " . $MXFRM->where . " " . mxOrderBy(" D.createdAt DESC ") . mxQryLimit();
            $DB->dbRows();
        ?>
            <table width="100%" border="0" cellspacing="0" cellpadding="8" class="tbl-list">
                <thead>
                    <tr><?php echo getListTitle($MXCOLS); ?></tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        // Document type badge
                        $typeLabel = $docTypes[$d['documentType']] ?? $d['documentType'];
                        $d['documentType'] = '<span class="doc-type-badge doc-type-' . $d['documentType'] . '">' . $typeLabel . '</span>';

                        // File link
                        if ($d['fileName']) {
                            $d['fileName'] = '<a href="' . SITEURL . '/uploads/employee_document/' . $d['fileName'] . '" target="_blank" class="file-link"><i class="fa fa-file"></i> View</a>';
                        } else {
                            $d['fileName'] = '-';
                        }

                        // Valid upto with expiry warning
                        if ($d['validUpto']) {
                            $validDate = strtotime($d['validUpto']);
                            $today = strtotime(date('Y-m-d'));
                            $daysUntilExpiry = ($validDate - $today) / (60 * 60 * 24);

                            if ($daysUntilExpiry < 0) {
                                $d['validUpto'] = '<span class="expired">' . date('d M Y', $validDate) . ' (Expired)</span>';
                            } else if ($daysUntilExpiry <= 30) {
                                $d['validUpto'] = '<span class="expiring-soon">' . date('d M Y', $validDate) . '</span>';
                            } else {
                                $d['validUpto'] = date('d M Y', $validDate);
                            }
                        } else {
                            $d['validUpto'] = '-';
                        }

                        // Created date
                        $d['createdAt'] = date('d M Y', strtotime($d['createdAt']));
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["documentID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2]; ?> title="<?php echo $v[0]; ?>">
                                    <?php
                                    if (isset($v[3]) && $v[3] != '') {
                                        echo getViewEditUrl("id=" . $d["documentID"], $d[$v[1]]);
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
            <div class="no-records">No documents found</div>
        <?php } ?>
    </div>
</div>
