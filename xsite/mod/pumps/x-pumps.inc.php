<?php
$TBLCAT = "pump_category";
$PKCAT = "categoryPID";
$CATSEOURI = "";

//Start: To fetch pump product list.
function getPumpProducts()
{
    global $DB, $ARRCAT, $PKCAT, $TBLCAT, $MXTOTREC;

    $data = array("strPaging" => "", "productList" => array());

    //To fetch get categories product's count for pagination.
    $inWhere = implode(",", array_fill(0, count($ARRCAT), "?"));
    $inWhere1 = implode(",", array_fill(0, count($ARRCAT), "?"));
    $DB->vals =  array_merge($ARRCAT, $ARRCAT);
    array_unshift($DB->vals, 1);
    array_push($DB->vals, 1, 1);
    $DB->types =  implode("", array_fill(0, count($DB->vals), "i"));
    $DB->sql = "SELECT P.pumpID FROM `" . $DB->pre . "pump` AS P
               LEFT JOIN `" . $DB->pre . "$TBLCAT` AS C ON C.$PKCAT = P.$PKCAT
               WHERE P.status=? AND P.$PKCAT IN(" . $inWhere . ") OR P.$PKCAT IN(SELECT categoryPID from " . $DB->pre . "pump_category WHERE   parentID IN(" . $inWhere1 . ") AND status=?) AND P.status=?";
    $DB->dbQuery();
    if ($DB->numRows > 0) {
        $MXTOTREC = $DB->numRows;
        $data["strPaging"] = getPaging("", "");
    }
    // End
    // To fetch get categories product list.
    $inWhere = implode(",", array_fill(0, count($ARRCAT), "?"));
    $inWhere1 = implode(",", array_fill(0, count($ARRCAT), "?"));
    $DB->vals =  array_merge($ARRCAT, $ARRCAT);
    array_unshift($DB->vals, 1);
    array_push($DB->vals, 1, 1);
    $DB->types =  implode("", array_fill(0, count($DB->vals), "i"));
    $DB->sql = "SELECT P.seoUri,P.pumpTitle,P.pumpFeatures,P.pumpImage,C.seoUri AS cseoUri FROM `" . $DB->pre . "pump` AS P
                LEFT JOIN `" . $DB->pre . "$TBLCAT` AS C ON C.$PKCAT = P.$PKCAT
                WHERE P.status=? AND P.$PKCAT IN(" . $inWhere . ") OR P.$PKCAT IN(SELECT categoryPID from " . $DB->pre . "pump_category WHERE   parentID IN(" . $inWhere1 . ") AND status=?) AND P.status=?" . mxQryLimit();
    $productListArr = $DB->dbRows();
    if ($DB->numRows > 0) {
        $data["productList"] = $productListArr;
    }
    // End
    return $data;
}
// End.
//Start: To fetch pump's product details data.
function getPDetail($pumpID = 0)
{
    global $DB;
    $pumsDetailArr = array();
    if ($pumpID) {
        $DB->vals = array(1, $pumpID);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "pump_detail` WHERE status=? AND pumpID=?";
        $pumsDetailArr = $DB->dbRows();
    }
    return $pumsDetailArr;
}
// End.
