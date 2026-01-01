<?php
$motorDetailArr = getMDetail($TPL->data['motorID']);

// Include motor schema generator if available
$schema_file = dirname(__FILE__) . '/../../core-site/pump-schema.inc.php';
if (file_exists($schema_file)) {
    require_once($schema_file);
}

// Get first detail record if available
$detailData = !empty($motorDetailArr) && count($motorDetailArr) > 0 ? $motorDetailArr[0] : null;

// Generate and output Product Schema
if (function_exists('echoProductSchema')) {
    echoProductSchema($TPL->data, $detailData);
}

// Generate and output BreadcrumbList Schema for SEO
// Check if parent category exists for proper 3-level hierarchy
$breadcrumbs = array(
    array('name' => 'Motors', 'url' => SITEURL . '/motor/'),
);

// Add parent category if it exists and is different from direct category
if (!empty($TPL->dataM['parentID']) && !empty($TPL->dataParent)) {
    $breadcrumbs[] = array(
        'name' => $TPL->dataParent['categoryTitle'],
        'url' => SITEURL . '/' . $TPL->dataParent['seoUri'] . '/'
    );
}

// Add direct category
$breadcrumbs[] = array(
    'name' => $TPL->dataM['categoryTitle'],
    'url' => SITEURL . '/' . $TPL->dataM['seoUri'] . '/'
);

// Add product page
$breadcrumbs[] = array(
    'name' => $TPL->data['motorTitle'],
    'url' => $_SERVER['REQUEST_URI'] ?? ''
);

if (function_exists('echoBreadcrumbSchema')) {
    echoBreadcrumbSchema($breadcrumbs);
}
?>
<!--Page Header Start-->
<input type="hidden" id="motorTitle" value="<?php echo $TPL->data['motorTitle'] ?>">
<input type="hidden" id="mCategoryTitle" value="<?php echo $TPL->dataM['categoryTitle'] ?>">
<input type="hidden" id="modTypeID" value="1">
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL; ?>/images/page-header-bg.jpg);">
    </div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li><a href="<?php echo SITEURL . '/motor/' ?>">Motors</a></li>
                <li><span>/</span></li>
                <?php if (!empty($TPL->dataParent)) { ?>
                    <li><a href="<?php echo SITEURL . '/' . $TPL->dataParent['seoUri'] . '/' ?>"><?php echo $TPL->dataParent['categoryTitle']; ?></a></li>
                    <li><span>/</span></li>
                <?php } ?>
                <li><a href="<?php echo SITEURL . '/' . $TPL->dataM['seoUri'] . '/' ?>"><?php echo $TPL->dataM['categoryTitle']; ?></a></li>
                <li><span>/</span></li>
                <li><?php echo $TPL->data['motorTitle']; ?></li>
            </ul>
            <h1><?php echo $TPL->data['motorTitle']; ?></h1>
        </div>
    </div>
</section>
<!--Page Header End-->
<!-- Motor Detail Header Title - White color for readability -->
<style>
.page-header .page-header__inner h1 {
    color: #ffffff !important;
}
</style>
<!--Product Details Start-->
<section class="product-details">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-xl-6">
                <div class="product-details__img" style="height: auto; margin-top: 0;">
                    <img src="<?php echo UPLOADURL . "/motor/530_530_crop_100/" . $TPL->data['motorImage']; ?>" alt="<?php echo htmlspecialchars($TPL->data['motorTitle'], ENT_QUOTES, 'UTF-8'); ?> - Industrial motor specifications and performance details" style="width: 100%; height: auto; display: block;">
                </div>
            </div>
            <div class="col-lg-6 col-xl-6" style="display: flex; flex-direction: column; justify-content: flex-start;">
                <div class="product-details__top" style="margin-top: 0;">
                    <h2 class="product-details__title"><?php echo $TPL->data["motorTitle"]; ?></h2>
                </div>
                <div class="product-details__content">
                    <p class="sub-title"><?php echo $TPL->data["motorSubTitle"]; ?></p>
                    <p class="product-details__content-text1"><?php echo $TPL->data["motorDesc"]; ?></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 text-center">
                <a href="javascript:volid(0)" class="thm-btn contact-us">Contact us</a>
            </div>
        </div>
    </div>
</section>
<!--Product Details End-->
<!--Specifications Start-->
<?php if (is_array($motorDetailArr) && count($motorDetailArr) > 0) { ?>
    <section class="Specifications">
        <div class="testimonial-two__shape-1 float-bob-x">
            <img src="<?php echo SITEURL; ?>/images/pump-2.png" alt="">
        </div>
        <div class="testimonial-two__shape-2 float-bob-y">
            <img src="<?php echo SITEURL; ?>/images/pump-1.png" alt="">
        </div>
        <div class="container">
            <div class="spec-tbl">
                <div class="body-scroll">
                    <table border="0" width="100%">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Output Power</th>
                                <th>Voltages</th>
                                <th>Frame Size</th>
                                <th>Standards</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($motorDetailArr as $specification) {
                                echo "<tr>
                                    <td>" . $specification["descriptionTitle"] . "</td>
                                    <td>" . $specification["descriptionOutput"] . "</td>
                                    <td>" . $specification["descriptionVoltage"] . "</td>
                                    <td>" . $specification["descriptionFrameSize"] . "</td>
                                    <td>" . $specification["descriptionStandard"] . "</td>
                                </tr>";
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
<?php } ?>
<!--Specifications End-->