<?php
/*
getParentSeouri = To fetch categories parent seouri using categoryID.
getCatSeoUri    = To make category seouri using category title.
addCategory     = To add category data.
updateCategory  = To update category data.
getCatTemplates = To get categories Templates.
*/
// Start: To fetch categories parent seouri using categoryID.
function getParentSeouri($parentID = 0)
{
    global $DB;
    $str = "";
    $parentID = intval($parentID);
    if ($parentID) {
        $DB->vals = array($parentID);
        $DB->types = "i";
        $DB->sql = "SELECT seoUri FROM `" . $DB->pre . "motor_category` WHERE categoryMID=?";
        $d = $DB->dbRow();
        if ($d["seoUri"])
            $str = $d["seoUri"];
    }
    return $str;
}
// End.
// Start: To make category seouri using category title.
function getCatSeoUri($titleCol = "", $parentID = 0)
{
    $arrSeoUri = array();
    if ($parentID > 0)
        $arrSeoUri[] = getParentSeouri($parentID);
    if (isset($titleCol) && $titleCol != "")
        $arrSeoUri[] = makeSeoUri($titleCol);
    return implode("/", $arrSeoUri);
}
// End.
// Start: To add category data.
function addCategory()
{
    global $DB, $TPL;
    if (isset($_POST["categoryTitle"]))
        $_POST["categoryTitle"] = cleanTitle($_POST["categoryTitle"]);
    if (isset($_POST["synopsis"]))
        $_POST["synopsis"] = cleanTitle($_POST["synopsis"]);
    if (isset($_POST["parentID"]))
        $_POST["parentID"] = intval($_POST["parentID"]);
    if (isset($_POST["xOrder"]))
        $_POST["xOrder"] = intval($_POST["xOrder"]);

    $_POST["seoUri"] = getCatSeoUri($_POST["categoryTitle"], $_POST["parentID"]);
    $_POST["imageName"] = mxGetFileName("imageName");
    $DB->table = $DB->pre . "motor_category";
    $DB->data = $_POST;
    if ($DB->dbInsert()) {
        $categoryMID = $DB->insertID;
        if ($categoryMID) {
            setResponse(array("err" => 0, "param" => "id=$categoryMID"));
        }
    } else {
        setResponse(1);
    }
}
// End.
// Start: To update category data.
function updateCategory()
{
    global $DB;
    $categoryMID = intval($_POST["categoryMID"]);
    if (isset($_POST["categoryTitle"]))
        $_POST["categoryTitle"] = cleanTitle($_POST["categoryTitle"]);
    if (isset($_POST["synopsis"]))
        $_POST["synopsis"] = cleanTitle($_POST["synopsis"]);
    if (isset($_POST["parentID"]))
        $_POST["parentID"] = intval($_POST["parentID"]);
    if (isset($_POST["xOrder"]))
        $_POST["xOrder"] = intval($_POST["xOrder"]);

    $_POST["seoUri"] = getCatSeoUri($_POST["categoryTitle"], $_POST["parentID"]);
    $_POST["imageName"] = mxGetFileName("imageName");
    $DB->table = $DB->pre . "motor_category";
    $DB->data = $_POST;

    if ($DB->dbUpdate("categoryMID=?", "i", array($categoryMID))) {
        if (file_exists(ADMINPATH . "/mod/menu")) {
            $DB->vals = array($_POST["seoUri"], $_POST["oldUri"], "motor_category");
            $DB->types = "sss";
            $DB->sql = "UPDATE " . $DB->pre . "x_menu SET seoUri=? WHERE seoUri=? AND menuType=?";
            $DB->dbQuery();
        }
        updateContactUsCategory($_POST["oldCatName"], $_POST["categoryTitle"], 1);
        setResponse(array("err" => 0, "param" => "id=$categoryMID"));
    } else {
        setResponse(1);
    }
}
// End.
// Start: To get categories Templates.
function getCatTemplates()
{
    global $TPL;
    $arr = array();
    if ($dir = @opendir(SITEPATH . "/mod/motor_category/")) {
        $skMod = array("x-motor_category.inc.php", "x-motor_category.php", "x-detail.php", ".DS_Store");
        while (false !== ($file = readdir($dir))) {
            if (!is_dir($file) && !in_array($file, $skMod)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if ($ext == "php") {
                    $arr[$file] = $file;
                }
            }
        }
    }
    return $arr;
}
// End. 
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest();
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD":
                addCategory();
                break;
            case "UPDATE":
                updateCategory();
                break;
            case "mxDelFile":
                $param = array("dir" => "motor_category", "tbl" => "motor_category", "pk" => "categoryMID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    if (function_exists("setModVars")) setModVars(array("TBL" => "motor_category", "PK" => "categoryMID", "UDIR" => array("imageName" => "motor_category", "categoryDelightboxImage" => "category_delightbox_slider")));
}
