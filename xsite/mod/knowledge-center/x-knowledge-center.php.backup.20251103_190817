<?php
$data = getknowledgeCenters() ?? [];
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
                <li>Knowledge Center</li>
            </ul>
            <h2>Knowledge Center</h2>
        </div>
    </div>
</section>
<!--Page Header End-->


<section class="blog-sec about-one">
    <div class="container">
        <!-- <div class="section-title text-center">
            <span class="section-title__tagline">Blogs</span>
            <h2 class="section-title__title">knowledge Center</h2>
        </div> -->
        <?php if (isset($data['totRec']) && $data['totRec'] > 0) { ?>
            <ul class="blog-list">
                <?php foreach ($data['kCenters'] as $kCenter) { ?>
                    <li>
                        <?php if (isset($kCenter['knowledgeCenterImage']) && $kCenter['knowledgeCenterImage'] != '') { ?>
                            <div class="blog-one__img">
                                <img src="<?php echo UPLOADURL . '/knowledge-center/' . $kCenter['knowledgeCenterImage'] ?>" alt="">
                            </div>
                        <?php } ?>

                        <div class="blog-one__content">
                            <h3 class="blog-one__title"><a href="<?php echo SITEURL . '/knowledge-center/' . $kCenter['seoUri'] ?>"><?php echo $kCenter['knowledgeCenterTitle']; ?></a></h3>
                            <p class="blog-one__text"><?php echo $kCenter['synopsis']; ?></p>
                            <a class="thm-btn product__all-btn" href="<?php echo SITEURL . '/knowledge-center/' . $kCenter['seoUri'] ?>">Read more <span class="icon-right-arrow"></span></a>
                        </div>
                    </li>
                <?php  } ?>
            </ul>
            <?php echo $data['strPaging']; ?>
        <?php } else {
        ?>
            <div class="product__items">
                <div class="no-rec">Sorry! No records found...</div>
            </div>
        <?php
        } ?>
    </div>
</section>