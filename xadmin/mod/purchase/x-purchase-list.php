<script type="text/javascript" src="<?php echo $TPL->modUrl  ?>inc/js/x-purchase.inc.js"></script>
<?php
$vendorOpt = getUnitVendorDD(($_GET["vendorID"] ?? 0));

$arrSearch = array(
    array("type" => "text", "name" => "purchaseID", "value" => $_GET["purchaseID"] ?? "", "title" => "#ID", "where" => "AND PI.purchaseID= ? ", "dtype" => "i"),
    array("type" => "select", "name" => "vendorID", "value" => $vendorOpt, "title" => "vendor", "where" => "AND PI.vendorID=?", "dtype" => "i"),
);

$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS);
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT PI.purchaseID FROM `" . $DB->pre . $MXMOD["TBL"] . "` 
            AS PI JOIN " . $DB->pre . "vendor AS V ON PI.vendorID  = V.vendorID  
            WHERE PI.status=?" . $MXFRM->where . mxWhere("PI.");

$DB->dbQuery();
$MXTOTREC = $DB->numRows;
if ($MXTOTREC < 1) {
    if ((!isset($MXFRM->where) || $MXFRM->where == "")) {
        $strSearch = "";
    }
}
echo $strSearch;
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php
        if ($MXTOTREC > 0) {
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT PI.*,CV.vendorID  , CV.vendorName FROM `" . $DB->pre . $MXMOD["TBL"] . "` AS PI 
                        JOIN " . $DB->pre . "vendor AS CV ON PI.vendorID  = CV.vendorID  
                        WHERE PI.status=?" . $MXFRM->where  . mxWhere("PI.") . mxOrderBy("PI.purchaseInwardDate DESC,PI.purchaseID DESC ") . mxQryLimit();
            $DB->dbRows();

            $MXCOLS = array(
                array("#ID", "purchaseID", ' width="1%" nowrap align="center" title="Purchase Inward ID"', true),
                array("vendor", "vendorName", ' nowrap align="left" title="Vendor name"'),
                array("Tot Qty", "totQuantity", ' nowrap align="right" title="Total quantity"'),
                array("Tot Prod Amt", "totProductAmt", ' nowrap align="right" title="Total product amount"'),
                array("Tot  CGST", "totCGST", ' nowrap align="right" title="Total cgst"'),
                array("Tot SGST", "totSGST", ' nowrap align="right" title="Total sgst"'),
                array("Tot IGST", "totIGST", ' nowrap align="right" title="Total igst"'),
                array("Grand Tot ", "grandTotal", ' nowrap align="right" title="Grand total"'),
            );
            $arrTot = [];
        ?>
            <table border="0" cellspacing="0" width="100%" cellpadding="8" class="tbl-list">
                <thead>
                    <tr>
                        <?php echo getListTitle($MXCOLS); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($DB->rows as $d) {
                        $arrTot["totQuantity"] = ($arrTot["totQuantity"] ?? 0) + $d["totQuantity"];
                        $arrTot["totProductAmt"] = ($arrTot["totProductAmt"] ?? 0) + $d["totProductAmt"];
                        $arrTot["totTaxableAmt"] = ($arrTot["totTaxableAmt"] ?? 0) + $d["totProductAmt"];
                        $arrTot["totCGST"] = ($arrTot["totCGST"] ?? 0) + $d["totCGST"];
                        $arrTot["totSGST"] = ($arrTot["totSGST"] ?? 0) + $d["totSGST"];
                        $arrTot["totIGST"] = ($arrTot["totIGST"] ?? 0) + $d["totIGST"];
                        $arrTot["subTotal"] = ($arrTot["subTotal"] ?? 0) + $d["subTotal"];
                        $arrTot["grandTotal"] = ($arrTot["grandTotal"] ?? 0) + $d["grandTotal"];
                        $d['totProductAmt'] = number_format($d['totProductAmt'], 2);
                        $d['totTaxableAmt'] = number_format($d['totTaxableAmt'], 2);
                        $d['totCGST'] = number_format($d['totCGST'], 2);
                        $d['totSGST'] = number_format($d['totSGST'], 2);
                        $d['totIGST'] = number_format($d['totIGST'], 2);
                        $d['subTotal'] = number_format($d['subTotal'], 2);
                    ?>
                        <tr>
                            <?php echo getMAction("mid", $d["purchaseID"]); ?>
                            <?php foreach ($MXCOLS as $v) { ?>
                                <td <?php echo $v[2] ?>>
                                    <?php if (isset($v[3]) && $v[3]) {
                                        echo getViewEditUrl("id=" . $d["purchaseID"], $d[$v[1]]);
                                    } else {
                                        echo $d[$v[1]];
                                    } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
                </tbody>
                <tfoot>
                    <tr style="text-align:right;" class="trcolspan">
                        <th class="noprint"></th>
                        <th colspan="2">Total</th>
                        <th><?php echo number_format($arrTot["totQuantity"], 2, '.', ''); ?></th>
                        <th><?php echo number_format($arrTot["totTaxableAmt"], 2, '.', ''); ?></th>
                        <th><?php echo number_format($arrTot["totCGST"], 2, '.', ''); ?></th>
                        <th><?php echo number_format($arrTot["totSGST"], 2, '.', ''); ?></th>
                        <th><?php echo number_format($arrTot["totIGST"], 2, '.', ''); ?></th>
                        <th><?php echo number_format($arrTot["grandTotal"], 2, '.', ''); ?></th>
                    </tr>
                </tfoot>
            </table>

        <?php } else { ?>
            <div class="no-records">No records found</div>
        <?php } ?>
    </div>
</div>