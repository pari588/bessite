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
            <h1 style="color: #ffffff;">Knowledge Center</h1>
        </div>
    </div>
</section>
<!--Page Header End-->

<!-- Knowledge Center Grid Section -->
<section class="kc-grid-section">
    <div class="container">
        <?php if (isset($data['totRec']) && $data['totRec'] > 0) { ?>
            <!-- Knowledge Center Grid -->
            <div class="kc-grid">
                <?php foreach ($data['kCenters'] as $kCenter) { ?>
                    <article class="kc-card">
                        <!-- Card Image -->
                        <?php if (isset($kCenter['knowledgeCenterImage']) && $kCenter['knowledgeCenterImage'] != '') { ?>
                            <div class="kc-card__image-wrapper">
                                <img
                                    src="<?php echo UPLOADURL . '/knowledge-center/' . $kCenter['knowledgeCenterImage'] ?>"
                                    alt="<?php echo htmlspecialchars($kCenter['knowledgeCenterTitle']); ?>"
                                    class="kc-card__image"
                                    loading="lazy"
                                >
                            </div>
                        <?php } else { ?>
                            <div class="kc-card__image-wrapper kc-card__image-wrapper--empty">
                                <div class="kc-card__placeholder">
                                    <span>No Image</span>
                                </div>
                            </div>
                        <?php } ?>

                        <!-- Card Content -->
                        <div class="kc-card__content">
                            <!-- Card Title -->
                            <h5 class="kc-card__title">
                                <a href="<?php echo SITEURL . '/knowledge-center/' . $kCenter['seoUri'] ?>">
                                    <?php echo $kCenter['knowledgeCenterTitle']; ?>
                                </a>
                            </h5>

                            <!-- Card Description -->
                            <p class="kc-card__description">
                                <?php echo $kCenter['synopsis']; ?>
                            </p>

                            <!-- Read More Link -->
                            <a
                                class="kc-card__link"
                                href="<?php echo SITEURL . '/knowledge-center/' . $kCenter['seoUri'] ?>"
                                aria-label="Read more about <?php echo htmlspecialchars($kCenter['knowledgeCenterTitle']); ?>"
                            >
                                Read More →
                            </a>
                        </div>
                    </article>
                <?php } ?>
            </div>

            <!-- Pagination -->
            <?php if (isset($data['strPaging']) && $data['strPaging'] != '') { ?>
                <div class="kc-pagination">
                    <?php echo $data['strPaging']; ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <!-- No Records Message -->
            <div class="kc-no-records">
                <p>Sorry! No records found...</p>
            </div>
        <?php } ?>
    </div>
</section>

<!-- Knowledge Center Styles -->
<style>
/* ========================================
   Knowledge Center Grid Responsive Layout
   ======================================== */

.kc-grid-section {
    position: relative;
    display: block;
    padding: 80px 0;
    background-color: #ffffff;
}

/* Grid Container - Responsive 3/2/1 Column Layout */
.kc-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-bottom: 50px;
}

/* Tablet Layout - 2 Columns */
@media (max-width: 1024px) {
    .kc-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }
}

/* Mobile Layout - 1 Column */
@media (max-width: 640px) {
    .kc-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Card Styles */
.kc-card {
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #ffffff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.kc-card:hover {
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    transform: translateY(-8px);
}

/* Card Image Wrapper */
.kc-card__image-wrapper {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 66.67%;
    overflow: hidden;
    background-color: #f5f5f5;
}

.kc-card__image-wrapper--empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: auto;
    padding: 100px 20px;
}

/* Card Image */
.kc-card__image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.3s ease;
}

.kc-card:hover .kc-card__image {
    transform: scale(1.05);
}

.kc-card__placeholder {
    text-align: center;
    color: #999;
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    font-weight: 500;
}

/* Card Content */
.kc-card__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    padding: 24px;
}

/* Card Title - H5 Bold */
.kc-card__title {
    font-family: 'Libre Baskerville', serif;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.4;
    margin: 0 0 16px 0;
    color: #27252a;
}

.kc-card__title a {
    color: #27252a;
    text-decoration: none;
    transition: color 0.3s ease;
}

.kc-card__title a:hover {
    color: #157bba;
}

/* Card Description - 2-3 Lines */
.kc-card__description {
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #89868d;
    margin: 0 0 24px 0;
    flex-grow: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Read More Link - Bottom Aligned */
.kc-card__link {
    font-family: 'Manrope', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #157bba;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: all 0.3s ease;
    align-self: flex-start;
}

.kc-card__link:hover {
    color: #0f5a8f;
    margin-left: 4px;
}

/* No Records Message */
.kc-no-records {
    text-align: center;
    padding: 60px 20px;
    color: #89868d;
    font-family: 'Manrope', sans-serif;
    font-size: 16px;
}

/* Pagination Wrapper */
.kc-pagination {
    margin-top: 40px;
    display: flex;
    justify-content: center;
}

.kc-pagination .mxpaging {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

/* Section Padding Responsive */
@media (max-width: 768px) {
    .kc-grid-section {
        padding: 60px 15px;
    }

    .kc-card__content {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .kc-grid-section {
        padding: 40px 15px;
    }

    .kc-card__title {
        font-size: 16px;
    }

    .kc-card__description {
        font-size: 13px;
    }
}
</style>