<?php

function getUnitVendorDD($vendorID = 0)
{
    global $DB;
    if (isset($vendorID) && $vendorID > 0) {
        $whereArr = array("sql" => "status=? AND (isActive=? OR vendorID =?) ", "types" => "iii", "vals" => array(1, 1, ($D['vendorID'] ?? 0)));
    } else {
        $whereArr = array("sql" => "status=? AND isActive=?", "types" => "ii", "vals" => array(1, 1));
    }

    $extFields = array("stateID", "postalCode");
    $params = ["table" => $DB->pre . "vendor", "key" => "vendorID", "val" => "vendorName", "selected" => $vendorID, "where" => $whereArr, "order" => "vendorName ASC", "extFields" => $extFields, "lang" => false];
    $custOpt  = getUnitTableDD($params);
    return $custOpt;
}


function getUnitArrayDD($arr = [])
{
    $options = "";
    if (isset($arr["data"]) && count($arr["data"]) > 0) {
        $defaults = ["data" => [], "selected" => "", "extFields" => []];
        $arr = array_merge($defaults, $arr);
        extract($arr);
        foreach ($data["data"] as $k => $v) {
            if ($v != "") {
                $sel = "";
                if (is_array($selected)) {

                    if (in_array($k, $selected)) {
                        $sel = ' selected="selected"';
                    }
                } else if ("$k" == "$selected") {
                    $sel = ' selected="selected"';
                }

                $extAttr = "";
                if (isset($extFields) && count($extFields) > 0) {
                    foreach ($extFields as $fldName) {
                        if (isset($data["$fldName"][$k]) && $data["$fldName"][$k] != "") {
                            $extAttr .= ' ' . $fldName . '="' . $data["$fldName"][$k] . '"';
                        }
                    }
                }
                $options .= "\n<option value=\"" . $k . "\"" .  $extAttr . $sel . ">" . $v . "</option>";
            }
        }
    }
    return $options;
}

function getUnitTableDD($arr = [])
{
    $dataArr  = getUnitDataArray($arr);
    $selected = "";
    if (isset($arr["selected"])) {
        $selected = $arr["selected"];
    }

    $extFields = [];
    if (isset($arr["extFields"])) {
        $extFields = $arr["extFields"];
    }

    $options = "";
    if (isset($dataArr) && count($dataArr) > 0)
        $options  = getUnitArrayDD(array("data" => $dataArr, "selected" => $selected, "extFields" => $extFields));

    return $options;
}


function getUnitDataArray($arr = [])
{
    $defaults = ["table" => "", "key" => "", "val" => "", "where" => [], "order" => "", "extFields" => [], "org" => false, "lang" => false];
    $arr = array_merge($defaults, $arr);
    extract($arr);

    $arrData = array();
    if ($table && $key && $val) {
        global $DB;
        if (!isset($order) || $order == "") {
            $order = $val;
        }

        $strWhere = "";
        if (isset($where) && count($where) > 0) {
            $strWhere = "WHERE " . $where["sql"];
            $DB->vals = $where["vals"];
            $DB->types = $where["types"];
        }
        $extra = "";
        if (isset($extFields) && count($extFields) > 0) {
            $extra = "," . implode(", ", $extFields);
        }

        $DB->sql = "SELECT `$key`,$val $extra FROM `$table` $strWhere " . mxWhere("", $lang, $org) . " ORDER BY $order";
        $DB->dbRows();
        if ($DB->numRows > 0) {
            foreach ($DB->rows as $v) {
                $arrData["data"][$v[$key]] = $v[$val];
                if ($extra != "") {
                    foreach ($extFields as $fldName) {
                        $arrData["$fldName"][$v[$key]] = $v[$fldName];
                    }
                }
            }
        }
    }
    return $arrData;
}


// function getProductFooter($D = [])
// {
//     return '<tfoot>
//             <tr>
//                 <th colspan="3"></th>
//                 <th><input type="text" name="totQuantity" id="totQuantity" value="' . (number_format(($D['totQuantity'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="QTY" placeholder="QTY" xtype="text"></th>
//                 <th></th>
//                 <th><input type="text" name="totProductAmt" id="totProductAmt" value="' . (number_format(($D['totProductAmt'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="AMT" placeholder="AMT" xtype="text"></th>

//                 <th>
//                     <input type="hidden" name="totTaxAmt" id="totTaxAmt" value="' . (number_format(($D['totTaxAmt'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="Taxable" placeholder="Tax" xtype="text">
//                 </th>
//                 <th><input type="text" name="totCGST" id="totCGST" value="' . (number_format(($D['totCGST'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="CGST" placeholder="CGST" xtype="text"></th>
//                 <th><input type="text" name="totSGST" id="totSGST" value="' . (number_format(($D['totSGST'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="SGST" placeholder="SGST" xtype="text"></th>
//                 <th><input type="text" name="totIGST" id="totIGST" value="' . (number_format(($D['totIGST'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="IGST" placeholder="IGST" xtype="text"></th>
//                 <th><input type="text" name="subTotal" id="subTotal" value="' . (number_format(($D['subTotal'] ?? 0), 2, ".", "")) . '" readonly="readonly" title="TOT" placeholder="TOT" xtype="text"></th>
//                 <th></th>
//             </tr>
//             <tr>
//                 <th colspan="9" align="right">TCS% </th>
//                 <th><input type="text" name="tcsPercent" id="tcsPercent" value="' . (number_format(($D['tcsPercent'] ?? 0.00), 2, ".", "")) . '" class="tcsPercent" id="tcsPercent" title="TCS %" placeholder="TCS %" xtype="text"></th>
//                 <th> <input type="text" name="tcsAmount" id="tcsAmount" value="' . (number_format(($D['tcsAmount'] ?? 0.00), 2, ".", "")) . '" readonly="readonly" title="TCS AMT" placeholder="TCS AMT" xtype="text"></th>
//                 <th></th>
//             </tr>
//             <tr>
//                 <th colspan="10" align="right">Grand Total </th>
//                 <th> <input type="text" name="grandTotal" id="grandTotal" value="' . (number_format(($D['grandTotal'] ?? 0.00), 2, ".", "")) . '" readonly="readonly" title="Grand Total" placeholder="Grand Total" xtype="text"></th>
//                 <th></th>
//             </tr>
//             </tfoot>';
// }





// function getFinancialYear()
// {
//     $currMon = date("m");
//     if ($currMon > 3 && date("Y-m-d H:i:s") >= (date("Y")) . "-04-01 00:00:00") {
//         $fStart = date("y");
//         $fEnd   = (date("y") + 1);
//     } else {
//         $fStart = (date("y") - 1);
//         $fEnd   = date("y");
//     }
//     return array("start" => $fStart, "end" => $fEnd);
// }
function savePurchaseInwardDetail($purchaseID = 0)
{
    if ($purchaseID) {
        global $DB;
        $unitWhere = array("sql" => "status = ?", "types" => "i", "vals" => array(1));
        $params = ["table" => $DB->pre . "unit", "key" => "unitID", "val" => "unitName", "where" => $unitWhere, "order" => "unitName ASC"];
        $unitData  = getUnitDataArray($params);
        for ($k = 0; $k < count($_POST["productID"]); $k++) {
            // print_r($_POST);die;
            $_POST['unitName'][$k] = $unitData["data"][$_POST['unitID'][$k]];
            $arrIn = array(
                "purchaseID" => $purchaseID,
                "productID"   => $_POST["productID"][$k],
                "hsnCode"     => $_POST["hsnCode"][$k],
                "unitID"      => $_POST["unitID"][$k],
                "unitName"    => $_POST["unitName"][$k],
                "prodPurchaseRate" => $_POST["prodPurchaseRate"][$k],
                "quantity"     => $_POST["quantity"][$k],
                "amount"       => $_POST["amount"][$k],
                "taxRate"   => $_POST["taxRate"][$k],
                "totalAmt"     => $_POST["totalAmt"][$k],
                "cgstAmt"     => $_POST["cgstAmt"][$k],
                "sgstAmt"     => $_POST["sgstAmt"][$k],
                "igstAmt"     => $_POST["igstAmt"][$k],
                "purchaseDate" => date("Y-m-d")
            );

            $DB->table = $DB->pre . "purchase_details";
            $DB->data = $arrIn;
            $DB->dbInsert();
        }
    }
}

function addPurchaseInward()
{
    global $DB;
    $DB->table = $DB->pre . "purchase";
    $DB->data = $_POST;
    $productArr = array();
    $productArr = $_POST['productID'];
    $productStr = implode(",", $productArr);
    if ($DB->dbInsert()) {
        $purchaseID = $DB->insertID;
        if ($purchaseID) {
            savePurchaseInwardDetail($purchaseID);
            updateTotalQty($productStr);
            setResponse(["err" => 0, "param" => "id=$purchaseID"]);
        }
    } else {
        setResponse(["err" => 1]);
    }
}

function updatePurchaseInward()
{
    global $DB;

    $purchaseID = intval($_POST["purchaseID"]);

    $DB->table = $DB->pre . "purchase";
    $DB->data = $_POST;
    if ($DB->dbUpdate("purchaseID=?", "i", array($purchaseID))) {
        if (isset($_POST["purchaseID"]) &&  $_POST["purchaseID"] > 0) {



            //Get old product ids to update stock qty
            $invoiceDtlWhere = array("sql" => "purchaseID = ?", "types" => "i", "vals" => array($purchaseID));
            $params = ["table" => $DB->pre . "purchase_details", "key" => "productID", "val" => "productID", "where" => $invoiceDtlWhere];
            $oldProdIds = getDataArray($params);
            $oldProdIds = count($oldProdIds) > 0 ? $oldProdIds['data'] : [];

            //Get unique product ids from old array and new array
            $uniqueProdIDArr = array_unique(array_merge($oldProdIds, $_POST['productID']), SORT_REGULAR);
            $productIDs = implode(",", $uniqueProdIDArr);

            $DB->vals = array($purchaseID);
            $DB->types = "i";
            $DB->sql = "DELETE FROM " . $DB->pre . "purchase_details WHERE purchaseID=?";
            if ($DB->dbQuery()) {
                savePurchaseInwardDetail($purchaseID);
            }
            // function to update the totalSaleQty,totalPurchaseQty,totalBalanceQty fields in product table.
            updateTotalQty($productIDs);
        }
        setResponse(["err" => 0, "param" => "id=$purchaseID"]);
    } else {
        setResponse(["err" => 1]);
    }
}



function getProductData()
{
    global $DB;
    $json      = array();
    $DB->vals  = array(trim($_REQUEST['searchString']), 1);
    $DB->types = "si";

    $DB->sql = "SELECT P.productSku,P.productSkuID,H.hsnNo AS hsnCode,P.unitID,H.taxRate FROM `" . $DB->pre . "product_sku` P 
                LEFT JOIN " . $DB->pre . "hsn AS H ON H.hsnID = P.hsnID
                WHERE P.productSku LIKE CONCAT('%',?,'%') AND P.status=? " .  mxWhere("P.") . " ORDER BY P.productSku ASC LIMIT 50";
    $data = $DB->dbRows();
    if ($DB->numRows > 0) {
        foreach ($data as  $k => $v) {

            $json[] = array(
                "value" => $v["productSku"],
                "label" => $v["productSku"],
                "data" => array(
                    "productID" => $v["productSkuID"],
                    "hsnCode" => $v["hsnCode"],
                    "unitID" => $v["unitID"],
                    "quantity" => 1,
                    "taxRate" => $v["taxRate"]
                )
            );
        }
    }
    return json_encode($json);
}

//Added by Pramod Badgujar || 12 march 2024
function purchaseTrash()
{
    global $DB;
    $response['msg'] = "Something went wrong.";
    $response['err'] = 1;
    $invoiceArr = array();
    $status = $_POST['status'];
    $purchaseIDs = $_POST['purchaseIDs'];
    if ($purchaseIDs) {
        $DB->vals = array();
        $DB->types = "";
        $DB->sql = "SELECT P.purchaseID,PD.productID 
                    FROM `" . $DB->pre . "purchase` AS P
                    LEFT JOIN `" . $DB->pre . "purchase_details` AS PD ON PD.purchaseID = P.purchaseID
                    WHERE P.purchaseID IN ($purchaseIDs)";
        $purchaseResult = $DB->dbRows();
        foreach ($purchaseResult as $value) {
            $purchaseArr[] = $value['productID'];
            
            $DB->table = $DB->pre . "purchase_details";
            $DB->data = array("status"=>$status);
            $DB->dbUpdate("purchaseID=?", "i", array($value['purchaseID']));
        }
        $purchaseStr = implode(',', $purchaseArr);
        if ($DB->numRows > 0) {
            updateTotalQty($purchaseStr);
            $response['err'] = 0;
            $response['msg'] = 'Selected purchase are successfully trashed';
        }
    }
    return $response;
}



if (isset($_POST["xAction"])) {
    require("../../../core/core.inc.php");
    require(ADMINPATH . "/inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addPurchaseInward();
                break;
            case "UPDATE":
                updatePurchaseInward();
                break;
            case 'getProductData':
                echo getProductData();
                exit;
            case "purchaseTrash":
                $MXRES = purchaseTrash();
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    setModVars(array("TBL" => "purchase", "PK" => "purchaseID"));
}
