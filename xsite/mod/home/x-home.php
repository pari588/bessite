  <link rel="stylesheet" href="<?php echo mxGetUrl(SITEURL . '/vendors/swiper/swiper.min.css'); ?>" />
  <script src="<?php echo mxGetUrl(SITEURL . '/vendors/swiper/swiper.min.js'); ?>"></script>
  <?php
    $getHomeInfoArr = getHomeInfo();
    $homeInfoDataArr = $getHomeInfoArr["homeInfoData"];
    $homeSliderDataArr = $getHomeInfoArr["homeSliderData"];
    $bestPartnerDataArr = $getHomeInfoArr["bestPartnerData"];
    ?>
  <!--Main Slider Start-->
  <section class="main-slider-two">
      <div class="swiper-container thm-swiper__slider" data-swiper-options='{"slidesPerView": 1, "loop": false,
        "effect": "fade",
        "pagination": {
        "el": "#main-slider-pagination",
        "type": "bullets",
        "clickable": true
        },
        "navigation": {
        "nextEl": "#main-slider__swiper-button-next",
        "prevEl": "#main-slider__swiper-button-prev"
        },
        "autoplay": {
        "delay": 5000
        }}'>
          <div class="swiper-wrapper">
              <?php if (is_array($homeSliderDataArr) && count($homeSliderDataArr) > 0) {
                    foreach ($homeSliderDataArr as $key => $val) { ?>
                      <div class="swiper-slide">
                          <div class="image-layer-two" style="background-image: url(<?php echo SITEURL . '/uploads/home/' . $val["sliderImage"]; ?>);"></div>
                          <!-- /.image-layer -->
                          <div class="container">
                              <div class="row">
                                  <div class="col-xl-12">
                                      <div class="main-slider-two__content">
                                          <?php if ($key == 0) { ?>
                                              <h2 class="main-slider-two__title"><?php echo $homeInfoDataArr["homeTitle"]; ?></h2>
                                              <?php echo $homeInfoDataArr["homeDesc"]; ?>
                                          <?php } ?>
                                          <div class="main-slider-two__btn-box">
                                              <a href="<?php echo SITEURL . '/' . $homeInfoDataArr["contactUsUrl"] . '/';  ?>" class="thm-btn main-slider-two__btn">Contact us</a>
                                              <a href="<?php echo SITEURL . '/' . $homeInfoDataArr["aboutUrl"] . '/';  ?>" class="thm-btn spa-center__btn">Find out more</a>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
              <?php }
                } ?>
          </div>
          <!-- <div class="swiper-pagination" id="main-slider-pagination"></div> -->
          <!-- If we need navigation buttons -->
      </div>
  </section>
  <!--Main Slider End-->

  <!-- features -->
  <section class="process-one">
      <div class="container">
          <div class="process-one__inner">
              <div class="process-one__shape-1"></div>
              <div class="row">
                  <!--Process One Single Start-->
                  <div class="col-xl-4 col-lg-4">
                      <div class="process-one__single">
                          <div class="process-one__img-box">
                              <div class="process-one__img">
                                  <img src="<?php echo SITEURL . '/images/icons/icon-1.svg' ?>" alt="" />
                                  <!-- <i class="far fa-gem"></i> -->
                              </div>
                              <div class="process-one__count">
                              </div>
                          </div>
                          <h3 class="process-one__title"><?php echo $homeInfoDataArr["otherTitleOne"]; ?></h3>
                          <p class="process-one__text"><?php echo $homeInfoDataArr["otherDescOne"]; ?></p>
                      </div>
                  </div>
                  <!--Process One Single End-->
                  <!--Process One Single Start-->
                  <div class="col-xl-4 col-lg-4">
                      <div class="process-one__single">
                          <div class="process-one__img-box">
                              <div class="process-one__img">
                                  <img src="<?php echo SITEURL . '/images/icons/icon-2.svg' ?>" alt="" />
                                  <!-- <i class="fas fa-infinity"></i> -->
                              </div>
                              <div class="process-one__count"></div>
                          </div>
                          <h3 class="process-one__title"><?php echo $homeInfoDataArr["otherTitleTwo"]; ?></h3>
                          <p class="process-one__text"><?php echo $homeInfoDataArr["otherDescTwo"]; ?></p>
                      </div>
                  </div>
                  <!--Process One Single End-->
                  <!--Process One Single Start-->
                  <div class="col-xl-4 col-lg-4">
                      <div class="process-one__single">
                          <div class="process-one__img-box">
                              <div class="process-one__img">
                                  <img src="<?php echo SITEURL . '/images/icons/icon-3.svg' ?>" alt="" />
                                  <!-- <i class="fab fa-react"></i> -->
                              </div>
                              <div class="process-one__count"></div>
                          </div>
                          <h3 class="process-one__title"><?php echo $homeInfoDataArr["otherTitleThree"]; ?></h3>
                          <p class="process-one__text"><?php echo $homeInfoDataArr["otherDescThree"]; ?></p>
                      </div>
                  </div>
                  <!--Process One Single End-->
              </div>
          </div>
      </div>
  </section>
  <!-- features -->
  <!-- challenging requirements -->
  <section class="spa-center">
      <div class="spa-center__bg jarallax" data-jarallax data-speed="0.2" data-imgPosition="50% 0%" style="background-image: url(<?php echo SITEURL . '/images/we-are_bg.jpeg' ?>);"></div>
      <div class="spa-center__inner">
          <div class="container">
              <div class="spa-center__content text-center">
                  <div class="spa-center__img">
                      <img src="<?php echo SITEURL . '/images/icons/water-pump.png' ?>" alt="" />
                  </div>
                  <h3 class="spa-center__title"><?php echo $homeInfoDataArr["otherTitleFour"]; ?></h3>
                  <p class="sub-title"><?php echo $homeInfoDataArr["otherDescFour"]; ?></p>
              </div>
          </div>
      </div>
  </section>
  <!-- challenging requirements -->

  <!--Services Start-->
  <section class="why-choose-one">
      <div class="why-choose-one__shape-1 float-bob-x">
          <img src="<?php echo SITEURL . '/images/services-shape-bg.png' ?>" alt="">
      </div>
      <div class="container">
          <div class="row">
              <div class="col-xl-6">
                  <div class="why-choose-one__left">
                      <div class="why-choose-one__img">
                          <img src="<?php echo SITEURL . '/uploads/home/' . $homeInfoDataArr["serviceImg"]; ?>" alt="" />
                      </div>
                  </div>
              </div>
              <div class="col-xl-6">
                  <div class="why-choose-one__right">
                      <div class="section-title text-left">
                          <span class="section-title__tagline">Our Services</span>
                          <h2 class="section-title__title mb-3"><?php echo $homeInfoDataArr["serviceTitle"]; ?></h2>
                          <p><?php echo $homeInfoDataArr["serviceSubTitle"]; ?></p>
                      </div>
                      <div class="why-choose-one__faq">
                          <div class="accrodion-grp" data-grp-name="faq-one-accrodion">
                              <div class="accrodion active">
                                  <?php echo $homeInfoDataArr["serviceDescOne"]; ?>
                              </div>
                              <div class="accrodion">
                                  <?php echo $homeInfoDataArr["serviceDescTwo"]; ?>
                              </div>
                              <div class="accrodion last-child">
                                  <?php echo $homeInfoDataArr["serviceDescThree"]; ?>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </section>
  <!--Services End-->

  <!--Our partners Start-->
  <section class="instagram">
      <div class="container">
          <!-- <div class="instagram__title">
            <h3>Our Best Partners</h3>
        </div> -->
          <div class="section-title text-center">
              <h2 class="section-title__title">Our Best Partners</h2>
          </div>
          <div class="row">
              <!--partners Single Start-->
              <?php if (is_array($bestPartnerDataArr) && count($bestPartnerDataArr) > 0) {
                    foreach ($bestPartnerDataArr as $key => $val) { ?>
                      <div class="col-xl-2 col-lg-4 col-md-4 col-6">
                          <div class="instagram__single">
                              <div class="instagram__img">
                                  <img src="<?php echo SITEURL . '/uploads/home/' . $val["bestPartnerImg"]; ?>" alt="">
                              </div>
                          </div>
                      </div>
              <?php }
                } ?>
          </div>

      </div>
  </section>
  <!--Our partners End-->

  <!--Counter One Start-->
  <section class="counter-one">
      <div class="counter-one__bg jarallax" data-jarallax data-speed="0.2" data-imgPosition="50% 0%" style="background-image: url(<?php echo SITEURL . '/images/we-are_bg.jpeg' ?>);">
      </div>
      <div class="container">
          <div class="row">
              <!--Counter One Single Start-->
              <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="counter-one__single">
                      <div class="counter-one__icon">
                          <span class="far fa-calendar-alt"></span>
                          <!-- <span class="icon-massage-1"></span> -->
                      </div>
                      <div class="counter-one__content-box">
                          <div class="counter-one__count-box">
                              <h3 class="odometer" data-count="<?php echo $homeInfoDataArr["effectiveIncrease"]; ?>%">00%</h3>
                          </div>
                          <p class="counter-one__text">Effective Increase</p>
                      </div>
                  </div>
              </div>
              <!--Counter One Single End-->
              <!--Counter One Single Start-->
              <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="counter-one__single counter-one__single-2">
                      <div class="counter-one__icon">
                          <span class="far fa-thumbs-up"></span>
                      </div>
                      <div class="counter-one__content-box">
                          <div class="counter-one__count-box">
                              <h3 class="odometer" data-count="<?php echo $homeInfoDataArr["yearsExperience"]; ?>%">00</h3>
                          </div>
                          <p class="counter-one__text">Years Experience</p>
                      </div>
                  </div>
              </div>
              <!--Counter One Single End-->
          </div>
      </div>
  </section>
  <!--Counter One End-->