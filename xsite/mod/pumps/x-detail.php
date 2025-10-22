<?php
$pumsDetailArr = getPDetail($TPL->data['pumpID']);
?>
<input type="hidden" id="pumpTitle" value="<?php echo $TPL->data['pumpTitle'] ?>">
<input type="hidden" id="aCategoryTitle" value="<?php echo $TPL->dataM['categoryTitle'] ?>">
<input type="hidden" id="modTypeID" value="2">
<!--Page Header Start-->
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL; ?>/images/page-header-bg.jpg);">
    </div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li>Pumps</li>
            </ul>
            <h2>Pumps Details</h2>
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
                    <img src="<?php echo UPLOADURL . "/pump/530_530_crop_100/" . $TPL->data['pumpImage']; ?>" alt="">
                </div>
            </div>
            <div class=" col-lg-6 col-xl-6">
                <div class="product-details__top">
                    <h3 class="product-details__title"><?php echo $TPL->data["pumpTitle"]; ?></span> </h3>
                </div>
                <div class="product-details__content">
                    <?php echo $TPL->data["pumpFeatures"]; ?>
                </div>
                <h4 class="product-description__title">Additional information</h4>
                <ul class="list-unstyled services-two__list">
                    <li>
                        <div class="services-two__services-name">
                            <h5>Kw/Hp</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><a href="#"><?php echo $TPL->data["kwhp"]; ?></a></h6>
                        </div>
                    </li>
                    <li>
                        <div class="services-two__services-name">
                            <h5>Supply Phase</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><a href="#"><?php echo $TPL->data["supplyPhase"]; ?></a></h6>
                        </div>
                    </li>
                    <li>
                        <div class="services-two__services-name">
                            <h5>Delivery Pipe</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><a href="#"><?php echo $TPL->data["deliveryPipe"]; ?></a></h6>
                        </div>
                    </li>
                    <li>
                        <div class="services-two__services-name">
                            <h5>No. of Stages</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><a href="#"><?php echo $TPL->data["noOfStage"]; ?></a></h6>
                        </div>
                    </li>
                    <li>
                        <div class="services-two__services-name">
                            <h5>ISI</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><a href="#"><?php echo $TPL->data["isi"]; ?></a></h6>
                        </div>
                    </li>
                    <li>
                        <div class="services-two__services-name">
                            <h5>MNRE</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><a href="#"><?php echo $TPL->data["mnre"]; ?></a></h6>
                        </div>
                    </li>
                    <li>
                        <div class="services-two__services-name">
                            <h5>Agricultural Pumps Type</h5>
                        </div>
                        <div class="services-two__services-price">
                            <h6><?php echo $TPL->data["pumpType"]; ?></h6>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="text-center">
                <a href="javascript:volid(0)" class="thm-btn contact-us">Contact us</a>
            </div>
        </div>
    </div>
</section>
<!--Product Details End-->


<!--Specifications Start-->
<?php if (isset($pumsDetailArr) && count($pumsDetailArr) > 0) { ?>
    <section class="Specifications">
        <div class="testimonial-two__shape-1 float-bob-x">
            <img src="<?php echo SITEURL; ?>/images/pump-2.png" alt="">
        </div>
        <div class="testimonial-two__shape-2 float-bob-y">
            <img src="<?php echo SITEURL; ?>/images/pump-1.png" alt="">
        </div>
        <div class="container">
            <div class="section-title text-center">
                <h2 class="section-title__title">Specifications</h2>
            </div>
            <div class="spec-tbl">
                <div class="body-scroll">
                    <table border="0" width="100%">
                        <thead>
                            <tr>
                                <th>Catref</th>
                                <th>Power (Kw)</th>
                                <th>Power (HP)</th>
                                <th>Supply Phase</th>
                                <th>Pipe Size (mm)</th>
                                <th>No. of Stage</th>
                                <th>Head Range (m)</th>
                                <th>Discharge Range</th>
                                <th>MRP (INR)</th>
                                <th>Warranty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pumsDetailArr as $specification) {
                                echo "<tr>
                                    <td>" . $specification["categoryref"] . "</td>
                                    <td>" . $specification["powerKw"] . "</td>
                                    <td>" . $specification["powerHp"] . "</td>
                                    <td>" . $specification["supplyPhaseD"] . "</td>
                                    <td>" . $specification["pipePhase"] . "</td>
                                    <td>" . $specification["noOfStageD"] . "</td>
                                    <td>" . $specification["headRange"] . "</td>
                                    <td>" . $specification["dischargeRange"] . "</td>
                                    <td>" . $specification["mrp"] . "</td>
                                    <td>" . $specification["warrenty"] . "</td>
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