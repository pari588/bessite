<?php

class manageTemplate
{

    var $uriArr = array();
    //var $urlBase = "";
    var $modDir = "mod";
    var $modName = "";
    var $modIncUrl = "";
    var $modUrl = "";
    var $modPath = "";
    var $metaTitle = "";
    var $metaKeyword = "";
    var $metaDesc = "";
    var $data = array();
    var $dataM = array();
    var $tplFile = "";
    var $tplInc = "";
    var $pageUri = "";
    var $pageType = "";
    var $pageUrl = "";
    var $requestUri = "";
    var $params = "";
    var $SAASID = 0;

    public function setDetailPage($tpl = array())
    {
        if (isset($tpl) && count($tpl) > 0) {
            if (isset($tpl["tblDetail"]) && isset($tpl["pkDetail"]) && $tpl["tblDetail"] != "" && $tpl["pkDetail"] != "") {
                global $DB;
                $DB->types = "is";
                $DB->vals = array(1, end($this->uriArr));
                $DB->sql = "SELECT * FROM `" . $DB->pre . $tpl["tblDetail"] . "` WHERE status=? AND `seoUri`= ? " . mxWhereS() . " LIMIT 1";
                $d = $DB->dbRow();
                if ($DB->numRows > 0) {
                    $this->data = $d;
                    $this->setMetaData($tpl["metaKeyD"], $d[$tpl["pkDetail"]]);
                    if ($this->metaTitle == "404 : Page not found") {
                        $this->metaTitle = $d[$tpl["titleDetail"]];
                    }
                    $this->pageType =  "x-detail";
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    public function setMetaData($metaKey = "", $metaValue = 0, $metaType = 0)
    {
        if ($metaKey) {
            global $DB;
            $DB->vals = array($metaKey, $metaValue, $metaType);
            $DB->types = "sss";
            $DB->sql = "SELECT metaTitle,metaKeyword,metaDesc FROM " . $DB->pre . "x_meta WHERE metaKey = ? AND metaValue=? AND metaType=?";
            $DB->dbRow();
            if ($DB->numRows) {
                if ($DB->row["metaTitle"]) {
                    $this->metaTitle = $DB->row["metaTitle"];
                }
                if ($DB->row["metaKeyword"]) {
                    $this->metaKeyword = $DB->row["metaKeyword"];
                }
                if ($DB->row["metaDesc"]) {
                    $this->metaDesc = $DB->row["metaDesc"];
                }
            }
        }
    }

    private function set404()
    {
        $this->metaTitle = "404 : Page not found";
        $this->pageType = "404";
        $this->tplFile = SITEPATH . "/inc/x-404.php";
    }

    private function setHome()
    {
        $this->tplFile = SITEPATH . "/$this->modDir/home/x-home.php";
        $this->tplInc = SITEPATH . "/$this->modDir/home/x-home.inc.php";
        $this->modPath = SITEPATH . "/$this->modDir/home";
        $this->modUrl = SITEURL . "/$this->modDir/home";
        $this->modIncUrl = $this->modUrl . "/x-home.inc.php";
        $this->pageType = "home";
        $this->modName = "home";
        $this->setMetaData($this->modDir . "/home");
    }

    private function setStaticMod()
    {
        $module = $folder = $this->uriArr[0];
        $this->modName = end($this->uriArr);
        $cnt = 0;
        foreach ($this->uriArr as $f) {
            $fPath = SITEPATH . "/$this->modDir/$folder/x-$f.php";
            $fPath1 = SITEPATH . "/$this->modDir/$folder/$f/x-$f.php";
            if (file_exists($fPath1) && is_file($fPath1)) {
                $cnt++;
                $this->tplFile = $fPath1;
                $this->modPath = SITEPATH . "/$this->modDir/$folder/$f";
                $this->modUrl = SITEURL . "/$this->modDir/$folder/$f";
                $folder .= "/" . $f;
            } elseif (file_exists($fPath) && is_file($fPath)) {
                $cnt++;
                $this->tplFile = $fPath;
                $this->modPath = SITEPATH . "/$this->modDir/$folder";
                $this->modUrl = SITEURL . "/$this->modDir/$folder";
            }
        }

        if ($this->tplFile && $cnt > 0) {
            $this->pageType = "module";
            $iPath = SITEPATH . "/$this->modDir/$module/x-$module.inc.php";
            if (file_exists($iPath) && is_file($iPath)) {
                $this->tplInc = $iPath;
                $this->modIncUrl = SITEURL . "/$this->modDir/$module/x-$module.inc.php";
            }

            if (count($this->uriArr) > $cnt) {
                $fPath = SITEPATH . "/$this->modDir/$folder/x-detail.php";
                if (file_exists($fPath) && is_file($fPath)) {
                    global $DB;
                    $uriA = $this->uriArr;
                    array_pop($uriA);
                    $this->modName = end($uriA);
                    $seoUriD = implode("/", $uriA);
                    $DB->vals = array(1, $seoUriD);
                    $DB->types = "is";
                    $DB->sql = "SELECT * FROM `" . $DB->pre . "x_template` WHERE modType=? AND seoUri=? AND tblDetail != '' AND pkDetail != '' ORDER BY xOrder ASC";
                    $tpl = $DB->dbRow();
                    if ($DB->numRows > 0) {
                        $this->set404();
                        if ($this->setDetailPage($tpl)) {
                            $this->tplFile = $fPath;
                        }
                    } else {
                        $this->pageType =  "x-detail";
                        $this->tplFile = $fPath;
                        $this->metaTitle = ucfirst($this->modName) . " : Detail Page";
                    }
                } else {
                    $this->setMetaData($this->modDir . "/" . $this->pageUri, 0);
                }
            } else {
                $this->setMetaData($this->modDir . "/" . $this->pageUri, 0);
            }
        }
    }

    private function setDynamicMod()
    {
        global $DB;
        $arrTpl = array();
        $DB->vals = array(0, $this->modDir);
        $DB->types = "is";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "x_template` WHERE modType=? AND modDir=? ORDER BY xOrder ASC";
        $arrTpl = $DB->dbRows();
        if ($DB->numRows > 0) {
            $cnt = count($this->uriArr);
            $seoUriM = implode("/", $this->uriArr);
            $seoUriD = "";
            $uriA = array();
            if ($cnt > 1) {
                $uriA = $this->uriArr;
                array_pop($uriA);
                $seoUriD = implode("/", $uriA);
            }
            //print_r($seoUriD);
            foreach ($arrTpl as $tpl) {
                if (isset($tpl["tblMaster"]) != "") { //tmp add by ganesh.
                    $DB->types = "iss";
                    $DB->vals = array(1, $seoUriM, $seoUriD);
                    $DB->sql = "SELECT * FROM `" . $DB->pre . $tpl["tblMaster"] . "` WHERE status=? AND (`seoUri`= ? OR `seoUri`= ?)" . mxWhereS() . " LIMIT 2";
                    $dt = $DB->dbRows();
                }
                $fPath = "";
                if ($DB->numRows > 0) {
                    foreach ($dt as $d) {
                        if ($d["seoUri"] == $seoUriM) {
                            $this->data = $d;
                            $this->pageType = $tpl["seoUri"];
                            $tplFile = $d[$tpl["tplFileCol"]] ?? "";

                            if (isset($tplFile) && $tplFile != "") {
                                $file = $tplFile;
                            } else {
                                $file = 'x-' . $tpl["seoUri"] . ".php";
                            }

                            if (!strpos($file, 'php')) {
                                $file = 'x-' . $file . ".php";
                            }

                            $fPath = SITEPATH . "/$this->modDir/" . $tpl["seoUri"] . "/" . $file;
                            if (file_exists($fPath) && is_file($fPath)) {
                                $this->setMetaData($tpl["seoUri"], $d[$tpl["pkMaster"]]);
                                if ($this->metaTitle == "404 : Page not found") {
                                    $this->metaTitle = $d[$tpl["titleMaster"]];
                                }
                            }
                        } else if ($d["seoUri"] == $seoUriD) {
                            $this->dataM = $d;
                            $fPathT = SITEPATH . "/$this->modDir/" . $tpl["seoUri"] . "/x-detail.php";
                            if (file_exists($fPathT) && is_file($fPathT)) {
                                if ($this->setDetailPage($tpl)) {
                                    $fPath = $fPathT;
                                }
                            }
                        }
                    }
                }
                if ($fPath != "") {
                    $this->tplFile = $fPath;
                    $this->modName = $tpl["seoUri"];
                    $this->modPath = SITEPATH . "/$this->modDir/" . $tpl["seoUri"];
                    $this->modUrl = SITEURL . "/$this->modDir/" . $tpl["seoUri"];
                    $this->tplInc = $this->modPath . "/x-" . $tpl["seoUri"] . ".inc.php";
                    $this->modIncUrl = $this->modUrl . "/x-" . $tpl["seoUri"] . ".inc.php";

                    break;
                }
            }
        }
    }

    private function setPageUri()
    {
        global $FOLDER, $MXSET;
        $this->pageUrl = SITEURL . "/";
        if ($_SERVER["REQUEST_URI"] == "/")
            $_SERVER["REQUEST_URI"] = "";

        if ($_SERVER["REQUEST_URI"] !== "") {
            $arrUri = array();
            $arrUriT = parse_url($_SERVER["REQUEST_URI"]);

            if (isset($arrUriT["query"]))
                $this->params = $arrUriT["query"];

            if (isset($FOLDER) && $FOLDER !== "")
                $strUri = str_replace($FOLDER . "/", "", $arrUriT["path"]);
            else
                $strUri = $arrUriT["path"];

            // $this->pageUrl = SITEURL . "/" . $strUri;
            $this->pageUrl = SITEURL . $strUri;

            if (isset($strUri) && $strUri !== "") {
                $arrUri = array_values(array_filter(explode("/", $strUri)));

                if (count($arrUri) > 0) {
                    $ORGURI = "";
                    if (intval($MXSET["SAAS"]) === 1) {
                        global $DB;
                        $DB->types = "is";
                        $DB->vals = array(1, $arrUri[0]);
                        $DB->sql = "SELECT userID FROM `" . $DB->pre . "x_admin_user` WHERE status=? AND `seoUri`= ?";
                        $DB->display;
                        $dt = $DB->dbRow();
                        if ($DB->numRows > 0) {
                            $this->SAASID = $dt['userID'];
                            $ORGURI = "/" . $arrUri[0];
                            array_shift($arrUri);
                        }
                    }

                    $modDirP = SITEPATH . "/" . "mod-" . $arrUri[0];
                    if (file_exists($modDirP) && is_dir($modDirP)) {
                        $this->modDir = "mod-" . $arrUri[0];
                        array_shift($arrUri);
                    }

                    if (isset($MXSET["MULTILINGUAL"]) && $MXSET["MULTILINGUAL"] == 1) {
                        global $MXLANGS;
                        if (count($MXLANGS)) {
                            $lcode = strtolower($arrUri[0]);
                            foreach ($MXLANGS as $d) {
                                if (isset($d["langPrefix"]) && $d["langPrefix"] == $lcode) {
                                    define("LANGCODE", $lcode);
                                    array_shift($arrUri);
                                    break;
                                }
                            }

                            if (!defined('LANGCODE'))
                                define('LANGCODE', $MXSET["LANGDEFAULT"]);

                            if (LANGCODE == $MXSET["LANGDEFAULT"])
                                define('LSITEURL', SITEURL . $ORGURI);
                            else
                                define('LSITEURL', SITEURL . $ORGURI . "/" . LANGCODE);
                        }
                    }
                    if ($MXSET["SAAS"] === 1)
                        define('OSITEURL', SITEURL . $ORGURI);
                }
                $this->uriArr = $arrUri;
                $this->pageUri = implode("/", $this->uriArr);
            } else {
                $this->pageUri = "";
            }
        }
    }

    public function setTemplate()
    {
        global $MXSET;
        $this->set404();
        $this->setPageUri();


        if (!defined("LANGCODE"))
            define("LANGCODE", "");

        if (!defined('LSITEURL'))
            define('LSITEURL', SITEURL);



        if ($this->pageUri !== "") {
            //$this->urlBase = end($this->uriArr);
            $this->setStaticMod();
        } else {
            $this->setHome();
        }

        if ($this->pageType == '404') {
            $this->setDynamicMod();
        }
        //print_r($this);exit;
        $MXSET = array_merge($MXSET, array("MODINCURL" => $this->modIncUrl, "MODURL" => $this->modUrl, "LANGCODE" => LANGCODE, "LSITEURL" => LSITEURL));
    }
}
