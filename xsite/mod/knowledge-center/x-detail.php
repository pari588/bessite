<?php
$seoUri = $TPL->uriArr[1] ?? '';
if (!isset($seoUri) && $seoUri  == '') {
    echo "<script>window.location.href = '" . SITEURL . "/blog/';</script>";
    exit;
}

$kCenter = [];
$DB->vals = array(1, $seoUri);
$DB->types = "is";
$DB->sql = "SELECT knowledgeCenterImage,knowledgeCenterTitle,knowledgeCenterContent FROM `" . $DB->pre . "knowledge_center` WHERE status=? AND seoUri=?";
$kCenter = $DB->dbRow();
if ($DB->numRows <= 0) {
    echo "<script>window.location.href = '" . SITEURL . "/blog/';</script>";
    exit;
}


?>
<!--Page Header Start-->
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);">
    </div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li><a href="<?php echo SITEURL . '/knowledge-center' ?>">Knowledge Center</a></li>
                <li><span>/</span></li>
                <li><?php echo $kCenter['knowledgeCenterTitle']; ?></li>
            </ul>
            <h2><?php echo $kCenter['knowledgeCenterTitle']; ?></h2>
        </div>
    </div>
</section>
<!--Page Header End-->
<?php if (is_array($kCenter) && count($kCenter) > 0) { ?>
    <section class="blog-details">
        <div class="container">
            <?php if (isset($kCenter['knowledgeCenterImage']) && $kCenter['knowledgeCenterImage'] != '') { ?>
                <div class="img-box">
                    <img src="<?php echo UPLOADURL . '/knowledge-center/' . $kCenter['knowledgeCenterImage'] ?>" alt="<?php echo htmlspecialchars($kCenter['knowledgeCenterTitle'], ENT_QUOTES, 'UTF-8'); ?> - Technical knowledge center article illustration">
                </div>
            <?php } ?>
            <!-- <span class="date">28th July 2024</span> -->
            <h3><?php echo $kCenter['knowledgeCenterTitle']; ?></h3>
            <?php echo $kCenter['knowledgeCenterContent']; ?>
        </div>
        </div>
    </section>
<?php } ?>