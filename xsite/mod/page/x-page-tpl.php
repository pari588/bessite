<?php
// AboutPage schema for /about-us/ specifically; generic WebPage otherwise
$isAboutUs = ($TPL->data['seoUri'] ?? '') === 'about-us';
$pageSchemaType = $isAboutUs ? 'AboutPage' : 'WebPage';
$pageUrl = SITEURL . '/' . ($TPL->data['seoUri'] ?? '') . '/';
?>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "<?php echo $pageSchemaType; ?>",
    "name": <?php echo json_encode($TPL->data['pageTitle'] ?? '', JSON_UNESCAPED_UNICODE); ?>,
    "url": "<?php echo $pageUrl; ?>",
    "description": <?php echo json_encode(trim(strip_tags(substr($TPL->data['pageContent'] ?? $TPL->data['synopsis'] ?? '', 0, 300))), JSON_UNESCAPED_UNICODE); ?>,
    "inLanguage": "en-IN",
    "isPartOf": {
        "@type": "WebSite",
        "name": "Bombay Engineering Syndicate",
        "url": "<?php echo SITEURL; ?>/"
    },
    "about": {
        "@type": "Organization",
        "name": "Bombay Engineering Syndicate",
        "@id": "<?php echo SITEURL; ?>/#organization"
    }
}
</script>
<style>
.page-header .page-header__inner h1 {
    color: #ffffff !important;
}
</style>
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
            <h1><?php echo $TPL->data["pageTitle"] ?></h1>
        </div>
    </div>
</section>
<!--Page Header End-->
<!--About One Start-->
<section class="about-one">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="about-one__left">
                    <div class="about-one__img-box wow fadeInLeft" data-wow-delay="30ms" data-wow-duration="1000ms">
                        <div class="about-one__img">
                            <img src="<?php echo SITEURL . '/uploads/page/' . $TPL->data["pageImage"] ?>" alt="<?php echo htmlspecialchars($TPL->data['pageTitle'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <?php echo $TPL->data["pageContent"] ?>
        </div>
        <?php echo $TPL->data["synopsis"] ?>
    </div>
</section>
<!--About One End-->