<script type="text/javascript" src="<?php echo mxGetUrl($TPL->modUrl . "/inc/js/x-purchase.inc.js")
                                    ?>"></script>
<!-- <script type="text/javascript" src="<?php // echo SITEURL . '/xadmin/inc/js/site.inc.js' 
                                          ?>"></script> -->
<link rel="stylesheet" type="text/css" href="<?php echo mxGetUrl($TPL->modUrl . "x-purchase-inward.css") ?>" />
<?php
$D = array();
$arrProduct = [];
$arrWhere = array("sql" => "status = ?", "types" => "i", "vals" => array(1));
$params = ["table" => $DB->pre . "unit", "key" => "unitID", "val" => "unitName", "where" => $arrWhere, "order" => "unitName ASC"];
$arrUnit  = getDataArray($params);

$DB->sql = "SELECT stateID FROM `" . $DB->pre . "site_setting` WHERE siteSettingID=1 ";
$arrSett = $DB->dbRow();
$arrD = array();

if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
  $id = intval($_GET["id"]);
  $DB->vals = array(1, $id);
  $DB->types = "ii";
  $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND " . $MXMOD["PK"] . " =?";
  $D = $DB->dbRow();

  $DB->vals = array($id);
  $DB->types = "i";
  $DB->sql = "SELECT POI.*,P.productSku AS productTitle FROM `" . $DB->pre . "purchase_details` AS POI 
              LEFT JOIN `" . $DB->pre . "product_sku` AS P ON POI.productID = P.productSkuID 
              WHERE POI.purchaseID=?";
  $DB->dbRows();
  if ($DB->numRows > 0) {
    foreach ($DB->rows as $k => $v) {
      $v['productSku'] = $v['productTitle'];
      $v["unitID"] = getArrayDD(array("data" => $arrUnit, "selected" => ($v['unitID'] ?? "")));
      $arrD[$k] = $v;
    }
  } else {
    $v = array();
    $v["unitID"] = getArrayDD(array("data" => $arrUnit, "selected" => ($v['unitID'] ?? "")));
    $arrD[] = $v;
  }
} else {
  $arrYr = getFinancialYear();
  if (count($arrD) < 1) {
    $v = array();
    $v["unitID"] = getArrayDD(array("data" => $arrUnit, "selected" => ($v['unitID'] ?? "")));
    $arrD[] = $v;
  }
}

$vendorOpt = getUnitVendorDD(($D["vendorID"] ?? 0));

$arrForm = array(
  // array("type" => "date", "name" => "purchaseInwardDate", "value" => $D["purchaseInwardDate"] ?? "", "title" => "PO Inward Date", "validate" => "required", "attr" => "pattern=((0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4})|[0-9]{4}|(0[1-9]|1[012]).[0-9]{4}", "attrp" => ' class="c4"'),
  array("type" => "select", "name" => "vendorID", "value" => $vendorOpt, "title" => "Vendor Name",  "validate" => "required", "attrp" => ' class="c3"'),
  // array("type" => "text", "name" => "paymentTerms", "value" => $D["paymentTerms"] ?? "", "title" => "Payment Terms", "attrp" => ' class="c3"'),
  // array("type" => "text", "name" => "deliveryDetails", "value" => $D["deliveryDetails"] ?? "", "title" => "Delivery Details", "attrp" => ' class="c3"'),
  // array("type" => "text", "name" => "invoiceNo", "value" => $D["invoiceNo"] ?? "", "title" => "Invoice No ",  "attrp" => ' class="c4"'),
  // array("type" => "date", "name" => "invoiceDateTime", "value" => $D["invoiceDateTime"] ?? "", "title" => "Invoice Date", "attrp" => ' class="c4"'),
  // array("type" => "select", "name" => "siteSettingDID", "value" => $shipFrmOpt, "title" => "Godown Loc", "attrp" => ' class="c4"'),
  array("type" => "hidden", "name" => "stateID", "value" => $arrSett['stateID'] ?? "")
);



$arrFrmProd = array(
  array("type" => "hidden", "name" => "productID", "class" => "productID"),
  array("type" => "autocomplete", "name" => "productSku", "value" => $D["productSku"] ?? "", "title" => "Product Sku ", "params" => array("xAction" => "getProductData", "callback" => "callbackProduct"),  "attrp" => ' class="c5"'),
  // array("type" => "text", "name" => "productDesc", "value" => $D["productDesc"] ?? "", "class" => "productDesc", "title" => "Product Description", "attrp" => ' width="16%"'),
  array("type" => "text", "name" => "hsnCode", "title" => "HSN", "validate" => "required", "class" => "right hsnCode", "attr" => " readonly='readonly' "),
  array("type" => "select", "name" => "unitID", "value" => "", "title" => "Unit", "validate" => "required", "class" => "right unitID", "attrp" => ' width="5%"'),
  array("type" => "text", "name" => "quantity", "title" => "QTY", "validate" => "required,min:0,number", "attr" => ' onkeyup="calculateAmount();"', "class" => "right quantity"),
  array("type" => "text", "name" => "prodPurchaseRate", "title" => "Rate", "validate" => "required,min:0,number", "attr" => ' onkeyup="calculateAmount();"', "class" => "right productRate"),
  array("type" => "text", "name" => "amount", "title" => "AMT", "validate" => "number",  "attr" => " readonly='readonly'", "class" => "right amount"),
  array("type" => "text", "name" => "taxRate", "title" => "Tax%", "validate" => "number", "attr" => " readonly='readonly' ", "class" => "right taxRate"),
  array("type" => "text", "name" => "cgstAmt", "title" => "CGST", "validate" => "number", "attr" => " readonly='readonly'", "class" => "right cgstAmt"),
  array("type" => "text", "name" => "sgstAmt", "title" => "SGST", "validate" => "number", "attr" => " readonly='readonly'", "class" => "right sgstAmt"),
  array("type" => "text", "name" => "igstAmt", "title" => "IGST", "validate" => "number", "attr" => " readonly='readonly'", "class" => "right igstAmt"),
  array("type" => "text", "name" => "totalAmt", "title" => "TOT", "validate" => "number", "attr" => " readonly='readonly'", "class" => "right totalAmt", "attrp" => ' width="8%"'),
);

$MXFRM = new mxForm();
?>
<div class="wrap-right">
  <?php echo getPageNav(); ?>
  <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" action="" method="post" enctype="multipart/form-data">
    <div class="wrap-form">
      <ul class="tbl-form"><?php echo $MXFRM->getForm($arrForm); ?></ul>
    </div>
    <div class="wrap-form calculateDropdown">
      <?php echo $MXFRM->getFormG(array("flds" => $arrFrmProd, "vals" => $arrD, "tfoot" => getProductFooter($D), "class" => " products small")); ?>
    </div>
    <?php echo $MXFRM->closeForm(); ?>
  </form>
</div>