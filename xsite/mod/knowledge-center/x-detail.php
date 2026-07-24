<?php
$seoUri = $TPL->uriArr[1] ?? '';
if (!isset($seoUri) && $seoUri  == '') {
    echo "<script>window.location.href = '" . SITEURL . "/blog/';</script>";
    exit;
}

$kCenter = [];
$DB->vals = array(1, $seoUri);
$DB->types = "is";
$DB->sql = "SELECT knowledgeCenterImage,knowledgeCenterTitle,knowledgeCenterContent,synopsis,datePublish FROM `" . $DB->pre . "knowledge_center` WHERE status=? AND seoUri=?";
$kCenter = $DB->dbRow();
if ($DB->numRows <= 0) {
    echo "<script>window.location.href = '" . SITEURL . "/blog/';</script>";
    exit;
}

// Load schema generator and emit TechArticle + (optional) FAQPage + BreadcrumbList
$schema_file = dirname(__FILE__) . '/../../core-site/pump-schema.inc.php';
if (file_exists($schema_file)) {
    require_once($schema_file);
}
$alt_file = dirname(__FILE__) . '/../../core-site/kc-image-alt.inc.php';
if (file_exists($alt_file)) {
    require_once($alt_file);
}
$calc_file = dirname(__FILE__) . '/../../core-site/hp-kw-calculator.inc.php';
if (file_exists($calc_file)) {
    require_once($calc_file);
}
$articleUrl = SITEURL . '/knowledge-center/' . $seoUri . '/';
if (function_exists('echoArticleSchema')) {
    echoArticleSchema($kCenter, $articleUrl);
}
if (function_exists('echoBreadcrumbSchema')) {
    echoBreadcrumbSchema(array(
        array('name' => 'Knowledge Center', 'url' => SITEURL . '/knowledge-center/'),
        array('name' => $kCenter['knowledgeCenterTitle'], 'url' => $articleUrl),
    ));
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
                <li><a href="<?php echo SITEURL . '/knowledge-center/' ?>">Knowledge Center</a></li>
                <li><span>/</span></li>
                <li><?php echo $kCenter['knowledgeCenterTitle']; ?></li>
            </ul>
            <h1><?php echo $kCenter['knowledgeCenterTitle']; ?></h1>
        </div>
    </div>
</section>
<!--Page Header End-->
<!-- Knowledge Center Article Header Title - White color for readability -->
<style>
.page-header .page-header__inner h1 {
    color: #ffffff !important;
}
</style>

<?php if (is_array($kCenter) && count($kCenter) > 0) { ?>
    <section class="blog-details">
        <div class="container">
            <?php if (isset($kCenter['knowledgeCenterImage']) && $kCenter['knowledgeCenterImage'] != '') { ?>
                <div class="img-box">
                    <img src="<?php echo UPLOADURL . '/knowledge-center/' . $kCenter['knowledgeCenterImage'] ?>" alt="<?php echo htmlspecialchars(function_exists('kcImageAlt') ? kcImageAlt($seoUri, $kCenter['knowledgeCenterTitle']) : $kCenter['knowledgeCenterTitle'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            <?php } ?>
            <!-- <span class="date">28th July 2024</span> -->
            <h2><?php echo $kCenter['knowledgeCenterTitle']; ?></h2>
            <?php
            $kcBody = $kCenter['knowledgeCenterContent'];
            if (strpos($kcBody, '[[HP_KW_CALCULATOR]]') !== false && function_exists('getHpKwCalculator')) {
                $kcBody = str_replace('[[HP_KW_CALCULATOR]]', getHpKwCalculator(), $kcBody);
            }
            echo $kcBody;
            ?>
        </div>
        </div>
    </section>
<?php } ?>