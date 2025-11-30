 <?php
    function getHomeInfo()
    {
        global $DB;
        $data = array();
        //Get home info.
        $DB->vals = array(1, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT * FROM `" . $DB->pre . "home` WHERE status = ? AND homeID = ?";
        $data["homeInfoData"] = $DB->dbRow();
        // End.
        // Get home info.
        $DB->vals = array(1, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT homeID,sliderImage FROM `" . $DB->pre . "home_slider` WHERE status = ? AND homeID = ?";
        $data["homeSliderData"] = $DB->dbRows();
        //Get home info.
        $DB->vals = array(1, 1);
        $DB->types = "ii";
        $DB->sql = "SELECT homeID,bestPartnerTitle,bestPartnerImg FROM `" . $DB->pre . "home_best_partner` WHERE status = ? AND homeID = ?";
        $data["bestPartnerData"] = $DB->dbRows();
        // End.
        return $data;
    }
