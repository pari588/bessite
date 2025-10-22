<?php
$TBLCAT = "motor_category";
$PKCAT = "categoryMID";
$CATSEOURI = "";

//Start: To fetch motor product list.
function getMotorProducts()
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
    $DB->sql = "SELECT M.categoryMID FROM `" . $DB->pre . "motor` AS M
                LEFT JOIN `" . $DB->pre . "$TBLCAT` AS C ON C.$PKCAT = M.$PKCAT
                WHERE M.status=? AND M.$PKCAT IN(" . $inWhere . ") OR M.$PKCAT IN(SELECT categoryMID from " . $DB->pre . "motor_category WHERE   parentID IN(" . $inWhere1 . ") AND status=?) AND M.status=?";
    $DB->dbQuery();
    if ($DB->numRows > 0) {
        $MXTOTREC = $DB->numRows;
        $data["strPaging"] = getPaging("", "");
    }
    // End.

    // To fetch get categories product list.
    $inWhere = implode(",", array_fill(0, count($ARRCAT), "?"));
    $inWhere1 = implode(",", array_fill(0, count($ARRCAT), "?"));
    $DB->vals =  array_merge($ARRCAT, $ARRCAT);
    array_unshift($DB->vals, 1);
    array_push($DB->vals, 1, 1);
    $DB->types =  implode("", array_fill(0, count($DB->vals), "i"));
    $DB->sql = "SELECT M.seoUri,M.motorTitle,M.motorSubTitle,M.motorImage,C.seoUri AS cseoUri FROM `" . $DB->pre . "motor` AS M
                LEFT JOIN `" . $DB->pre . "$TBLCAT` AS C ON C.$PKCAT = M.$PKCAT
                WHERE M.status=? AND M.$PKCAT IN(" . $inWhere . ") OR M.$PKCAT IN(SELECT categoryMID from " . $DB->pre . "motor_category WHERE   parentID IN(" . $inWhere1 . ") AND status=?) AND M.status=?" . mxQryLimit();
    $productListArr = $DB->dbRows();
    // End.
    if ($DB->numRows > 0) {
        $data["productList"] = $productListArr;
    }
    return $data;
}
// End.
//Start: To fetch motor's product details data.
function getMDetail($motorID)
{
    global $DB;
    $motorDetailArr = array();
    if (intval($motorID) > 0) {
        $DB->vals = array(1, $motorID);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "motor_detail` WHERE status=? AND motorID=?";
        $motorDetailArr = $DB->dbRows();
    }
    return $motorDetailArr;
}
// end.
