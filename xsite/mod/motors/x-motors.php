<?php getPageHeader(); ?>
<section class="product">
    <div class="container">
        <div class="row">
            <?php getSideNav(); ?>
            <div class="col-xl-8 col-lg-8">
                <?php
                $motorProductsArr =  getMotorProducts();
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
                                                    <img src="<?php echo UPLOADURL . "/motor/235_235_crop_100/" . $d["motorImage"]; ?>" alt="">
                                                </div>
                                                <div class="product__all-content">
                                                    <h4 class="product__all-title"><a href="#"><?php echo $d["motorTitle"]; ?></a></h4>
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