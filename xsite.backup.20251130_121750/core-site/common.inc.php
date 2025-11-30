<?php
function mxWhereS($al = "") {
    $where = "";
    global $MXSET;
    if ($MXSET["MULTILINGUAL"] == '1' && !defined(LANGCODE) && LANGCODE != '') {
        global $DB;
        array_push($DB->vals, LANGCODE);
        $DB->types .= "s";

        $where = " AND " . $al . "langCode=?";
    }
    return $where;
}

if (!function_exists('mxGetMetaArray')) {
    function mxGetMetaArray($metaKey = "", $metaValue = 0, $metaType = 0)
    {
        $arr = array("metaTitle" => "", "metaKeyword" => "", "metaDesc" => "");
        if ($metaKey) {
            global $DB;
            $DB->vals = array($metaKey, $metaValue, $metaType);
        $DB->types = "sss";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "x_meta` WHERE `metaKey` = ? AND `metaValue`=? AND metaType=?";
        if ($DB->numRows > 0)
            $arr = $DB->dbRow();
        }
        return $arr;
    }
} // End of if (!function_exists('mxGetMetaArray'))

function mxGetMeta() {
    global $TPL;
    if ($TPL->metaTitle)
        $str = "\n" . '<title>' . mx_strip_all_tags($TPL->metaTitle) . '</title>';
    if ($TPL->metaDesc)
        $str .= "\n" . '<meta name="description" content="' . mx_strip_all_tags(limitChars($TPL->metaDesc, 160, ''), true) . '" />';
    if ($TPL->metaKeyword)
        $str .= "\n" . '<meta name="keywords" content="' . mx_strip_all_tags(limitChars($TPL->metaKeyword, 160, ''), true) . '" />';
    return $str;
}

function createMenu($menuID = 0, $depth = 100, $level = 0) {
    global $TPL, $DB; $str = "";
    if ($menuID) {
        $DB->vals = array(1, $menuID);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM " . $DB->pre . "x_menu WHERE status=? AND parentID = ? ORDER BY xOrder ASC";
        $DB->dbRows();
        
        if ($DB->numRows) {
            if ($level)
                $str .= '<ul class="tree-list">';
            foreach ($DB->rows as $v) {
                $class = '';
                $target = '';
                $url = '';
                if (isset($v["seoUri"])) {
                    if (strpos($TPL->pageUri, $v["seoUri"]) !== false) {
                        $class = ' class="active"';
                    }
                } else if ($TPL->pageUri == $v["seoUri"]) {
                    $class = ' class="active"';
                }
                if ($v["menuType"] == 'exlink') {
                    $url = $v["seoUri"] ? $v["seoUri"] : SITEURL;
                    $target =  $v['menuTarget'] ? ' target="_blank"' : '';
                } else if ($v["menuType"] == 'page') {
                    $url = SITEURL . "/" . $v["seoUri"] . "/";
                } else if ($v["menuTarget"] > 0) { 
                    $target = ' target="_blank"';
                } else {
                    $url = SITEURL . "/" . $v["seoUri"] . "/";
                }

                $classLi = "";
                $aRel = "";
                if (isset($v["menuClass"]) && $v["menuClass"]) {
                    $classLi = ' class="' . $v["menuClass"] . '"';
                    $aRel = ' rel="' . $v["menuClass"] . '"';
                }

                $str .= '<li' . $classLi . '><a' . $class . ' href="' . $url . '"' . $target . ' title="' . $v["menuTitle"] . '"' . $aRel . '>' . $v["menuTitle"] . '</a>';
                $str .= createMenu($v["menuID"], $depth, $level + 1);
                $level - 1;
                $str .= '</li>';
            }
            if ($level)
                $str .= '</ul>';
        }
    }
    return $str;
}

function getMenu($menuTitle = "", $depth = 100) {
    global $DB;
    $str = "";
    if ($menuTitle) {
        $DB->vals = array(1, $menuTitle);
        $DB->types = "is";
        $DB->sql = "SELECT menuID FROM " . $DB->pre . "x_menu WHERE status=? AND menuTitle = ?";
        $d = $DB->dbRow();
        if ($DB->numRows) {
            $str = createMenu($d["menuID"], $depth);
        }
    }
    return $str;
}

function getPostCatId($postID = "") {
    global $DB;
    $DB->vals = array($postID);
    $DB->types = "i";
    $DB->sql = "SELECT categoryID  FROM " . $DB->pre . "post_category WHERE postID= ?";
    $DB->dbRows();
    $arrCat = array();

    if ($DB->numRows > 0) {
        foreach ($DB->rows as $cat) {
            $arrCat[] = $cat['categoryID'];
        }
    }
    return $arrCat;
}

function getCatTree($parentID = 0, $type = "checkbox", $arrCurr = array(), $skipCats = array(), $maxLevel = 100, $level = 0, $strUri = "", $strMenu = "") {
    global $DB;
    $removeCats = "";
    $DB->vals = array();
    $DB->vals[] = 1;
    $DB->vals[] = $parentID;
    $DB->types = "ii";
    if ($skipCats) {
        $DB->vals[] = implode("', '", $skipCats);
        $DB->types .= "s";
        $removeCats = " AND categoryID NOT IN (?)";
    }

    if (($level + 1) <= $maxLevel) {
        $DB->sql = "SELECT * FROM " . $DB->pre . "category WHERE status = ? AND parentID = ? " . $removeCats . " ORDER BY categoryID ASC";
        $DB->dbRows();
        if ($DB->numRows > 0) {
            if ($level > 0 && $type != "")
                $strMenu .= "<ul class='level-" . ($level - 1) . "'>";

            foreach ($DB->rows as $ct) {
                $curr = "";
                if (!$arrCurr)
                    $arrCurr = array();

                if (isset($type) && isset($ct['categoryID']) && $type == "checkbox") {
                    if (in_array($ct['categoryID'], $arrCurr))
                        $curr = ' checked="checked"';

                    $strMenu .= '<li><i class="chk"><input type="checkbox" id="categoryID' . $ct['categoryID'] . '" name="categoryID[]" value="' . $ct['categoryID'] . '" ' . $curr . ' class="checkbox" /><em></em></i>' . $ct['categoryTitle'];
                } else if ($type == "radio") {
                    if (in_array($ct['categoryID'], $arrCurr))
                        $curr = ' checked="checked"';
                    $strMenu .= '<li><i class="rdo"><input type="radio" id="categoryID' . $ct['categoryID'] . '" name="categoryID" value="' . $ct['categoryID'] . '" ' . $curr . ' class="radio" /><em></em></i>' . $ct['categoryTitle'];
                } else if ($type == "treelist") {
                    $newUri = $strUri . "/" . $ct['seoUri'];
                    if (in_array($ct['categoryID'], $arrCurr)) {
                        $curr = ' class="active"';
                    }
                    $strMenu .= "<li$curr><a $curr href='" . SITEURL . $ct['seoUri'] . "/' rel='" . $ct['categoryID'] . "'>" . $ct['categoryTitle'] . "</a>";
                } else {
                    if (in_array($ct['categoryID'], $arrCurr))
                        $curr = ' selected="selected"';
                    $strMenu .= "<option value='" . $ct['categoryID'] . "'$curr>" . str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $level) . $ct['categoryTitle'] . "</option>";
                }

                $strMenu = getCatTree($ct["categoryID"], $type, $arrCurr, $skipCats, $maxLevel, $level + 1, $newUri, $strMenu);
                $level - 1;
            }
            if ($level > 0 && $type != "")
                $strMenu .= "</ul></li>";
        } else {
            if ($type != "")
                $strMenu .= "</li>";
            $strUri = "";
        }
    }
    if ($strMenu)
        return $strMenu;
}

function queryString($params, $name = null) {
    $ret = "";
    foreach ($params as $key => $val) {
        if (isset($val) && is_array($val)) {
            if ($name == null)
                $ret .= queryString($val, $key);
            else
                $ret .= queryString($val, $name . "[$key]");
        } else {
            if ($name != null)
                $ret .= $name . "[$key]" . "=$val&";
            else
                $ret .= "$key=$val&";
        }
    }
    return $ret;
}

function getCatParents($categoryID = 0, $arrCats = array()) {
    if ($categoryID) {
        global $DB;
        $DB->vals = array($categoryID, 0);
        $DB->types = "ii";
        $DB->sql = "SELECT parentID FROM " . $DB->pre . "category WHERE categoryID=? AND parentID != ?";
        $DB->dbRow();
        if ($DB->numRows > 0) {
            $arrCats[] = $DB->row["parentID"];
            $arrCats = getCatParents($DB->row["parentID"], $arrCats);
        }
    }
    return $arrCats;
}

function getCategoryMenu($parentID, $categoryID) {
    global $DB;
    $DB->vals = array($parentID);
    $DB->types = "i";
    $DB->sql = "SELECT * FROM " . $DB->pre . "category WHERE parentID = ? ORDER BY categoryID ASC";
    $DB->dbRows();
    $strMenu = "";
    if ($DB->numRows > 0) {
        foreach ($DB->rows as $ct) {
            $curr = '';
            if ($ct['categoryID'] == $categoryID) {
                $curr = ' class="active"';
            }
            $strMenu .= "<li$curr><a $curr href='" . SITEURL . $ct['seoUri'] . "/' rel='" . $ct['categoryID'] . "' id='" . makeSeoUri($ct['categoryTitle']) . "'>" . $ct['categoryTitle'] . "</a></li>";
        }
    }
    return $strMenu;
}
// ------------------------------------------------------------------------------

function parse_youtube_url($url, $return = 'embed', $width = '', $height = '', $rel = 0) {
    $urls = parse_url($url);

    //url is http://youtu.be/xxxx
    if (isset($urls['host']) && $urls['host'] == 'youtu.be') {
        $id = ltrim($urls['path'], '/');
    }
    //url is http://www.youtube.com/embed/xxxx
    else if (isset($urls['host']) && strpos($urls['path'], 'embed') == 1) {
        $id = end(explode('/', $urls['path']));
    }
    //url is xxxx only
    else if (strpos($url, '/') === false) {
        $id = $url;
    }
    //http://www.youtube.com/watch?feature=player_embedded&v=m-t4pcO99gI
    //url is http://www.youtube.com/watch?v=xxxx
    else {
        parse_str($urls['query'],$v);
        $id = $v;
        if (!empty($feature)) {
            $id = end(explode('v=', $urls['query']));
        }
    }
    //return embed iframe
    if ($return == 'embed') {
        return '<iframe width="' . ($width ? $width : 560) . '" height="' . ($height ? $height : 349) . '" src="http://www.youtube.com/embed/' . $id . '?rel=' . $rel . '&autoplay=0&autohide=1&wmode=transparent" frameborder="0" allowfullscreen></iframe>';
    }
    //return normal thumb
    else if ($return == 'thumb') {
        return 'http://i1.ytimg.com/vi/' . $id . '/default.jpg';
    }
    //return hqthumb
    else if ($return == 'hqthumb') {
        return 'http://i1.ytimg.com/vi/' . $id . '/hqdefault.jpg';
    }
    // else return id
    else {
        return $id;
    }
}

?>