<!--Page Header Start-->
<section class="page-header">
    <div class="page-header__bg" style="background-image: url(<?php echo SITEURL . '/images/page-header-bg.jpg' ?>);">
    </div>
    <div class="container">
        <div class="page-header__inner">
            <ul class="thm-breadcrumb list-unstyled">
                <li><a href="<?php echo SITEURL . '/' ?>">Home</a></li>
                <li><span>/</span></li>
                <li><?php echo $TPL->data["pageTitle"] ?></li>
            </ul>
            <h2><?php echo $TPL->data["pageTitle"] ?></h2>
        </div>
    </div>
</section>
<!--Page Header End-->

<!--Privacy Policy Content Start-->
<section class="privacy-policy-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 col-md-12 mx-auto">
                <div class="privacy-policy-content">
                    <?php echo $TPL->data["pageContent"] ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!--Privacy Policy Content End-->

<style>
.privacy-policy-section { padding: 60px 0 80px; background: #fff; }
.privacy-policy-content { font-size: 15px; line-height: 1.8; color: #444; }
.privacy-policy-content h2 { font-size: 22px; color: #1a1a2e; margin: 35px 0 15px; padding-bottom: 8px; border-bottom: 2px solid #157bba; }
.privacy-policy-content h3 { font-size: 18px; color: #1a1a2e; margin: 25px 0 10px; }
.privacy-policy-content p { margin-bottom: 14px; }
.privacy-policy-content ul { margin: 10px 0 20px 20px; }
.privacy-policy-content ul li { margin-bottom: 6px; list-style-type: disc; }
.privacy-policy-content a { color: #157bba; text-decoration: underline; }
.privacy-policy-content .last-updated { font-style: italic; color: #888; margin-bottom: 30px; font-size: 14px; }
.privacy-policy-content .contact-box { background: #f8fafb; border-radius: 8px; padding: 20px 24px; margin-top: 30px; }
.privacy-policy-content .contact-box h3 { margin-top: 0; }
</style>
