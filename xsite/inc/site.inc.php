<?php
$MXSHOWREC = 9;
$ARRCAT = [];
//Start: To get categories and child categories
function getCatChilds($categoryID = 0)
{
    global $TBLCAT, $PKCAT;
    $arrData = [];
    if ($categoryID > 0) {
        global $DB;
        $DB->vals = array(1, $categoryID);
        $DB->types = "ii";
        $DB->sql = "SELECT $PKCAT,parentID FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND parentID=?";
        $dataL1 = $DB->dbRows();
        if ($DB->numRows > 0) {
            foreach ($dataL1 as $l1) {
                $arrData[] = $l1["$PKCAT"];
                $DB->vals = array(1, $l1["$PKCAT"]);
                $DB->types = "ii";
                $DB->sql = "SELECT $PKCAT,parentID FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND parentID=?";
                $dataL2 = $DB->dbRows();
                if ($DB->numRows > 0) {
                    foreach ($dataL2 as $l2) {
                        $arrData[] = $l2["$PKCAT"];
                        $DB->vals = array(1, $l2["$PKCAT"]);
                        $DB->types = "ii";
                        $DB->sql = "SELECT $PKCAT,parentID FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND parentID=?";
                        $dataL3 = $DB->dbRows();
                        if ($DB->numRows > 0) {
                            foreach ($dataL3 as $l3) {
                                $arrData[] = $l3["$PKCAT"];
                            }
                        }
                    }
                }
            }
        }
    }
    return $arrData;
}
//Start: Comman header section.
function getPageHeader($imageName = "")
{
    global $TPL, $TBLCAT;
    $imageUrl = SITEURL . '/images/page-header-bg.jpg';
    if (isset($TPL->data["imageName"]) && $TPL->data["imageName"] !== "")
        $imageUrl = UPLOADURL . "/" . $TBLCAT . "/" . $TPL->data["imageName"];
?>
    <section class="page-header">
        <div class="page-header__bg" style="background-image: url(<?php echo $imageUrl; ?>);"></div>
        <div class="container">
            <div class="page-header__inner">
                <ul class="thm-breadcrumb list-unstyled">
                    <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                    <li><span>/</span></li>
                    <li><?php echo $TPL->data["categoryTitle"]; ?></li>
                </ul>
                <h2><?php echo $TPL->data["categoryTitle"]; ?></h2>
            </div>
        </div>
    </section>
<?php
}
// End.
//Start: To show category and subcategory
function getSideNav()
{
    global $DB, $TBLCAT, $PKCAT, $TPL, $ARRCAT, $CATSEOURI;
    $topCat = $categoryID = $TPL->data["$PKCAT"];
    if (is_array($TPL->uriArr) && count($TPL->uriArr) > 0) {
        $DB->vals = array(1, $TPL->uriArr[0]);
        $DB->types = "is";
        $DB->sql = "SELECT $PKCAT,categoryTitle FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND seoUri=?";
        $DB->dbRow();
        $topCat = $DB->row["$PKCAT"];
    }

    $ARRCAT = array($categoryID);
    if ($topCat == $categoryID) {
        $ARRCAT = getCatChilds($topCat);
    }
?>
    <div class="col-xl-4 col-lg-4">
        <div class="services-details-two__left">
            <div class="services-details-two__category">
                <h3 class="product__sidebar-title">Categories</h3>
                <?php $DB->vals = array(1, $topCat);
                $DB->types = "ii";
                $DB->sql = "SELECT * FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND parentID=?";
                $dataL1 = $DB->dbRows();
                if ($DB->numRows > 0) { ?>
                    <ul class="services-details-two__category-list list-unstyled">
                        <?php
                        foreach ($dataL1 as $l1) {
                        ?>
                            <li>
                                <span><?php echo $l1["categoryTitle"] ?></span>
                                <?php
                                $arrData[] = $l1["$PKCAT"];
                                $DB->vals = array(1, $l1["$PKCAT"]);
                                $DB->types = "ii";
                                $DB->sql = "SELECT * FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND parentID=?";
                                $dataL2 = $DB->dbRows();
                                if ($DB->numRows > 0) { ?>
                                    <ul class="sub-category">
                                        <?php
                                        foreach ($dataL2 as $l2) {
                                            $active = "";
                                            if ($l2["$PKCAT"] == $categoryID) {
                                                $active = "active";
                                            }
                                            if ($l2["$PKCAT"] == $TPL->data['parentID']) {
                                                $active = "active";
                                            }
                                            $arrData[] = $l2["$PKCAT"];
                                            $DB->vals = array(1, $l2["$PKCAT"]);
                                            $DB->types = "ii";
                                            $DB->sql = "SELECT * FROM `" . $DB->pre . "$TBLCAT` WHERE status=? AND parentID=?";
                                            $dataL3 = $DB->dbRows();
                                            $catList = "";
                                            foreach ($dataL3 as $l3) {
                                                if ($l2["$PKCAT"] == $l3["parentID"]) {
                                                    $catList = "category-list";
                                                }
                                            }
                                        ?>
                                            <li class="<?php echo $catList . ' ' . $active; ?>"><a href="<?php echo SITEURL . '/' . $l2["seoUri"]; ?>/"><?php echo $l2["categoryTitle"] ?></a>
                                                <ul>
                                                    <?php
                                                    foreach ($dataL3 as $l3) {
                                                        $active1 = '';
  if(isset($TPL->data['categoryPID'])){
                                                        if ($l3["$PKCAT"] == $TPL->data['categoryPID']) {
                                                            $active1 = "class='active'";
                                                        }  
}
?>
                                                        <li <?php echo $active1; ?>><a href="<?php echo SITEURL . '/' . $l3["seoUri"]; ?>/"><?php echo $l3["categoryTitle"] ?></a>
                                                        <?php } ?>
                                                </ul>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
        </div>
    </div>
<?php
}
//End.
//Start: To save countact us form.
function saveProductContactFrm()
{
    global $DB;
    $data = array();
    $data['err'] = 1;
    $data['msg'] = "Someting went wrong";
    if ($_POST["userName"] != "" && $_POST["userLastName"] != "" && $_POST["userEmail"] != "" && $_POST["userSubject"] != "" && $_POST["userMessage"] != "") {
        $DB->table = $DB->pre . "contact_us";
        $DB->data = $_POST;
        if ($DB->dbInsert()) {
            $data['err'] = 0;
            $data['msg'] = "Thank you for contacting us!";
        }
    }
    return $data;
}
//End.
//Start: To fetch header and footer info.
function getSiteInfo()
{
    global $DB;
    $DB->vals = array(1, 1);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . "site_setting` WHERE status=? AND siteSettingID =?";
    $siteSettingInfo = $DB->dbRow();
    return $siteSettingInfo;
}
// End.
function logout()
{
    $data['err'] = 1;
    $data['msg'] = "Something Went Wrong!";
    $sessionDestroy = session_destroy();
    if ($sessionDestroy == true) {
        $data['err'] = 0;
        $data['msg'] = "Session Destroy!";
    }
    return $data;
}
// End.
if (isset($_POST["xAction"])) {
    require_once("../../core/core.inc.php");
    $MXRES = mxCheckRequest(false, false);
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "saveProductContactFrm":
                $MXRES = saveProductContactFrm($_POST);
                break;
            case "logout":
                $MXRES = logout($_POST);
                break;
        }
    }
    echo json_encode($MXRES);
}
