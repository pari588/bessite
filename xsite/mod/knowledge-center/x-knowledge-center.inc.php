<?php

function getknowledgeCenters()
{
    global $DB, $MXTOTREC, $MXSHOWREC;
    $data = [];
    $data["strPaging"] = '';

    $vals = array(1);
    $types = "i";
    $where = '';

    $DB->vals = $vals;
    $DB->types = $types;
    $DB->sql = "SELECT knowledgeCenterID FROM `" . $DB->pre . "knowledge_center` e
             WHERE status=? " . $where;
    $DB->dbQuery();
    if ($DB->numRows > 0) {
        $data['totRec'] = $MXTOTREC = $DB->numRows;
        $MXSHOWREC = 4;
        $data["strPaging"] = getPaging("", "");

        $DB->vals = $vals;
        $DB->types = $types;
        $DB->sql = "SELECT knowledgeCenterID,knowledgeCenterImage,knowledgeCenterTitle,synopsis,seoUri FROM `" . $DB->pre . "knowledge_center` 
             WHERE status=? " . $where . mxOrderBy(" knowledgeCenterID DESC ") . mxQryLimit();
        $data['kCenters'] = $DB->dbRows();


        if ($DB->numRows > 0) {
            if ($data["strPaging"] != "") {
                $data['paging'] =   '
                <div class="product__showing-result">
            <div class="product__showing-text-box">
                <div class="mxpaging">
                    <div class="mxpaging">
                        ' . $data["strPaging"] . '
                    </div>
                </div>
            </div>
        </div>';
            }
        }
    }
    return $data;
}
