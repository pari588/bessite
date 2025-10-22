<?php
$motorDetailArr = getMDetail($TPL->data['motorID']);
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
                <li>Motor</li>
            </ul>
            <h2>Motor Details</h2>
        </div>
    </div>
</section>
<!--Page Header End-->
<!--Product Details Start-->
<section class="product-details">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-xl-6">
                <div class="product-details__img">
                    <img src="<?php echo UPLOADURL . "/motor/530_530_crop_100/" . $TPL->data['motorImage']; ?>" alt="">
                </div>
            </div>
            <div class="col-lg-6 col-xl-6">
                <div class="product-details__top">
                    <h3 class="product-details__title"><?php echo $TPL->data["motorTitle"]; ?></span> </h3>
                </div>
                <div class="product-details__content">
                    <p class="sub-title"><?php echo $TPL->data["motorSubTitle"]; ?></p>
                    <p class="product-details__content-text1"><?php echo $TPL->data["motorDesc"]; ?></p>
                </div>
            </div>
            <div class="text-center">
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