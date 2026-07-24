<?php
getPageHeader();

// Include pump schema generator
$schema_file = dirname(__FILE__) . '/../../core-site/pump-schema.inc.php';
if (file_exists($schema_file)) {
    require_once($schema_file);
}

// Generate BreadcrumbList for category pages
$breadcrumbs = array(
    array('name' => 'Pumps', 'url' => SITEURL . '/pump/')
);

// Add category breadcrumbs if available
if (!empty($TPL->dataM)) {
    if (!empty($TPL->dataM['parentID']) && $TPL->dataM['parentID'] > 0) {
        // Get parent category if this is a subcategory
        global $DB;
        $DB->sql = "SELECT categoryTitle, seoUri FROM `" . $DB->pre . "pump_category` WHERE categoryPID = ? AND status = 1";
        $DB->vals = array($TPL->dataM['parentID']);
        $DB->types = "i";
        if ($DB->numRows > 0) {
            $parent = $DB->dbRow();
            $breadcrumbs[] = array('name' => $parent['categoryTitle'], 'url' => SITEURL . '/' . $parent['seoUri'] . '/');
        }
    }
    // Add current category
    $breadcrumbs[] = array('name' => $TPL->dataM['categoryTitle'], 'url' => SITEURL . '/' . $TPL->dataM['seoUri'] . '/');
}

// Output BreadcrumbList Schema
echoBreadcrumbSchema($breadcrumbs);
?>

<?php if (!empty($TPL->dataM['synopsis'])) { ?>
<section class="category-intro" style="padding:30px 0 0;">
    <div class="container">
        <div class="category-intro__text" style="font-size:15px;color:#555;line-height:1.7;max-width:960px;">
            <?php echo $TPL->dataM['synopsis']; ?>
        </div>
    </div>
</section>
<?php } ?>
<section class="product">
    <div class="container">
        <div class="row">
            <?php getSideNav(); ?>
            <div class="col-xl-8 col-lg-8">
                <?php
                $motorProductsArr = getPumpProducts();
                // ItemList schema for AI/Google to see all products on this category page
                if (!empty($motorProductsArr['productList'])) {
                    // Build absolute URLs per-row using each product's actual category path (cseoUri)
                    $itemsForSchema = array();
                    foreach ($motorProductsArr['productList'] as $row) {
                        $catPath = $row['cseoUri'] ?? ($TPL->dataM['seoUri'] ?? '');
                        if (empty($row['seoUri']) || empty($catPath)) continue;
                        $itemsForSchema[] = array(
                            'pumpTitle'  => $row['pumpTitle'] ?? '',
                            // Pre-build the full slug as cat-path/product-slug so generator concatenation works
                            'pumpSeoUri' => $row['seoUri'],
                            '_catPath'   => $catPath,
                        );
                    }
                    if (!empty($itemsForSchema) && function_exists('echoItemListSchema')) {
                        // For mixed-category lists, generate manually
                        $listItems = array();
                        $pos = 1;
                        foreach ($itemsForSchema as $it) {
                            $listItems[] = array(
                                '@type' => 'ListItem',
                                'position' => $pos++,
                                'url' => SITEURL . '/' . trim($it['_catPath'], '/') . '/' . $it['pumpSeoUri'] . '/',
                                'name' => $it['pumpTitle']
                            );
                        }
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
                                            <?php echo $motorProductsArr["strPaging"]; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="product__all">
                            <div class="row">
                                <?php if (count($motorProductsArr["productList"]) > 0) {
                                    foreach ($motorProductsArr["productList"] as $d) { ?>
                                        <div class="col-xl-4 col-lg-4 col-md-6">
                                            <div class="product__all-single">
                                                <div class="product__all-btn-box">
                                                    <a href="<?php echo SITEURL . '/' . $d["cseoUri"] . '/' . $d["seoUri"] . '/'; ?>" class="thm-btn product__all-btn">Know More</a>
                                                </div>
                                                <div class="product__all-img">
                                                    <img src="<?php echo UPLOADURL . "/pump/235_235_crop_100/" . $d["pumpImage"]; ?>" alt="<?php echo htmlspecialchars($d['pumpTitle'], ENT_QUOTES, 'UTF-8'); ?> - Submersible pump">
                                                </div>
                                                <div class="product__all-content">
                                                    <h4 class="product__all-title"><a href="#"><?php echo $d["pumpTitle"]; ?></a></h4>
                                                    <p class="product-short-description"><?php echo limitChars($d["pumpFeatures"], 20); ?></p>
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
                                            <?php echo $motorProductsArr["strPaging"]; ?>
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