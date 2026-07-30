<?php
getPageHeader();

// Include schema generator
$schema_file = dirname(__FILE__) . '/../../core-site/pump-schema.inc.php';
if (file_exists($schema_file)) {
    require_once($schema_file);
}

// Generate BreadcrumbList for category pages
$breadcrumbs = array(
    array('name' => 'Motors', 'url' => SITEURL . '/motor/')
);

// Add category breadcrumbs if available
if (!empty($TPL->dataM)) {
    if (!empty($TPL->dataM['parentID']) && $TPL->dataM['parentID'] > 0) {
        // Get parent category if this is a subcategory
        global $DB;
        $DB->sql = "SELECT categoryTitle, seoUri FROM `" . $DB->pre . "motor_category` WHERE categoryMID = ? AND status = 1";
        $DB->vals = array($TPL->dataM['parentID']);
        $DB->types = "i";
        $DB->dbRow();
        if ($DB->numRows > 0) {
            $parent = $DB->row;
            $breadcrumbs[] = array('name' => $parent['categoryTitle'], 'url' => SITEURL . '/' . $parent['seoUri'] . '/');
        }
    }
    // Add current category
    $breadcrumbs[] = array('name' => $TPL->dataM['categoryTitle'], 'url' => SITEURL . '/' . $TPL->dataM['seoUri'] . '/');
}

// Output BreadcrumbList Schema
if (function_exists('echoBreadcrumbSchema')) {
    echoBreadcrumbSchema($breadcrumbs);
}
?>
<?php if (!empty($TPL->dataM['synopsis'])) { ?>
<section class="category-intro">
    <div class="container">
        <div class="category-intro__card">
            <span class="category-intro__eyebrow">Overview &amp; Buying Guide</span>
            <div class="category-intro__text">
                <?php echo $TPL->dataM['synopsis']; ?>
            </div>
        </div>
    </div>
</section>
<style>
.category-intro{padding:38px 0 6px}
.category-intro__card{background:#fff;border:1px solid #eef2f6;border-left:4px solid #157bba;border-radius:10px;box-shadow:0 4px 20px rgba(21,123,186,.07);padding:26px 32px;max-width:980px;margin:0 auto;text-align:center}
.category-intro__text{text-align:left}
.category-intro__eyebrow{display:inline-block;font-size:12px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#157bba;background:rgba(21,123,186,.08);padding:5px 12px;border-radius:20px;margin-bottom:16px}
.category-intro__text p{font-size:15px;line-height:1.8;color:#586674;margin:0 0 15px}
.category-intro__text p:first-of-type{font-size:17.5px;line-height:1.7;color:#283746;font-weight:500}
.category-intro__text p:last-child{margin-bottom:0}
.category-intro__text strong{color:#16283a;font-weight:700}
.category-intro__text ul{margin:0 0 15px;padding-left:0;list-style:none}
.category-intro__text ul li{position:relative;padding-left:26px;margin-bottom:9px;font-size:15px;line-height:1.7;color:#586674}
.category-intro__text ul li::before{content:'';position:absolute;left:0;top:9px;width:8px;height:8px;background:#157bba;border-radius:50%}
.category-intro__text a{color:#157bba;font-weight:600;text-decoration:none;border-bottom:1px solid rgba(21,123,186,.4)}
.category-intro__text a[href*="inquiry"],.category-intro__text a[href*="contact"]{display:inline-block;margin-top:8px;background:#157bba;color:#fff!important;padding:10px 20px;border-radius:6px;border:0;font-weight:600;border-bottom:0;transition:background .2s}
.category-intro__text a[href*="inquiry"]:hover,.category-intro__text a[href*="contact"]:hover{background:#0f5a8f}
@media(max-width:767px){.category-intro__card{padding:20px 18px}.category-intro__text p:first-of-type{font-size:16px}}
</style>
<?php } ?>
<section class="product">
    <div class="container">
        <div class="row">
            <?php getSideNav(); ?>
            <div class="col-xl-8 col-lg-8">
                <?php
                $motorProductsArr =  getMotorProducts();
                if (!empty($motorProductsArr['productList'])) {
                    $listItems = array();
                    $pos = 1;
                    foreach ($motorProductsArr['productList'] as $row) {
                        $catPath = $row['cseoUri'] ?? ($TPL->dataM['seoUri'] ?? '');
                        if (empty($row['seoUri']) || empty($catPath)) continue;
                        $listItems[] = array(
                            '@type' => 'ListItem',
                            'position' => $pos++,
                            'url' => SITEURL . '/' . trim($catPath, '/') . '/' . $row['seoUri'] . '/',
                            'name' => $row['motorTitle'] ?? ''
                        );
                    }
                    if (!empty($listItems)) {
                        $schemaData = array(
                            '@context' => 'https://schema.org',
                            '@type' => 'ItemList',
                            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
                            'numberOfItems' => count($listItems),
                            'itemListElement' => $listItems
                        );
                        echo "\n<!-- ItemList Schema (JSON-LD) -->\n";
                        echo '<script type="application/ld+json">' . "\n";
                        echo json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                        echo "\n</script>\n";
                    }
                }
                ?>
                <div class="product__items">
                    <?php if (count($motorProductsArr["productList"]) > 0) { ?>
                        <?php if ($motorProductsArr["strPaging"] != "") { ?>
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="product__showing-result">
                                        <div class="product__showing-text-box">
                                            <div class="mxpaging"><?php echo $motorProductsArr["strPaging"]; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="product__all">
                            <div class="row">
                                <?php
                                if (count($motorProductsArr["productList"]) > 0) {
                                    foreach ($motorProductsArr["productList"] as $d) { ?>
                                        <div class="col-xl-4 col-lg-4 col-md-6">
                                            <div class="product__all-single">
                                                <div class="product__all-btn-box">
                                                    <a href="<?php echo SITEURL . '/' . $d["cseoUri"] . '/' . $d["seoUri"] . '/'; ?>" class="thm-btn product__all-btn">Know More</a>
                                                </div>
                                                <div class="product__all-img">
                                                    <img src="<?php echo UPLOADURL . "/motor/235_235_crop_100/" . $d["motorImage"]; ?>" alt="<?php echo htmlspecialchars($d['motorTitle'], ENT_QUOTES, 'UTF-8'); ?> - Industrial motor">
                                                </div>
                                                <div class="product__all-content">
                                                    <h4 class="product__all-title"><a href="<?php echo SITEURL . '/' . $d["cseoUri"] . '/' . $d["seoUri"] . '/'; ?>"><?php echo $d["motorTitle"]; ?></a></h4>
                                                    <p class="product-short-description"><?php echo limitChars($d["motorSubTitle"], 20); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                <?php }
                                } ?>
                            </div>
                        </div>
                        <?php if ($motorProductsArr["strPaging"] != "") { ?>
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="product__showing-result">
                                        <div class="product__showing-text-box">
                                            <div class="mxpaging"><?php echo $motorProductsArr["strPaging"]; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="no-rec">Sorry! No records found...</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</section>