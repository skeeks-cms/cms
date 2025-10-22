<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */
/* @var $this   yii\web\View */
/* @var $widget \skeeks\cms\cmsWidgets\contentElements\ContentElementsCmsWidget */
$this->registerCss(<<<CSS
/*------------------------------------
  Accordions
------------------------------------*/
.u-accordion__header {
  padding: 0.71429rem 1.07143rem;
}

.u-accordion__body {
  padding: 1.07143rem;
}

.u-accordion__control-icon i:nth-child(1) {
  display: none;
}

.collapsed .u-accordion__control-icon i:nth-child(1) {
  display: inline;
}

.u-accordion__control-icon i:nth-child(2) {
  display: inline;
}

.collapsed .u-accordion__control-icon i:nth-child(2) {
  display: none;
}

[aria-expanded="false"] .u-accordion__control-icon i:nth-child(1) {
  display: inline;
}

[aria-expanded="false"] .u-accordion__control-icon i:nth-child(2) {
  display: none;
}

[aria-expanded="true"] .u-accordion__control-icon i:nth-child(1) {
  display: none;
}

[aria-expanded="true"] .u-accordion__control-icon i:nth-child(2) {
  display: inline;
}

.u-accordion [class*="et-icon-"], .u-accordion-line-icon-pro {
  position: relative;
  top: 3px;
}

.u-accordion-color-primary .u-accordion__header [aria-expanded="true"] {
  color: #72c02c !important;
}

.u-accordion-color-white .u-accordion__header [aria-expanded="true"] {
  color: #fff !important;
}

.u-accordion-bg-primary .u-accordion__header [aria-expanded="true"] {
  background-color: #72c02c !important;
  border-color: #72c02c !important;
}

.u-accordion-bg-white .u-accordion__header [aria-expanded="true"] {
  background-color: #fff !important;
  border-color: #fff !important;
}

.u-accordion-brd-primary .u-accordion__header [aria-expanded="true"] {
  border-color: #72c02c !important;
}

.u-accordion-brd-white .u-accordion__header [aria-expanded="true"] {
  border-color: #fff !important;
}

#accordion .card {
    margin-bottom: 1.5rem;
}

#accordion .card .sx-accordion-heading a {
    padding: 1.5rem 2rem;
    box-shadow: 0 5px 10px -6px rgba(0, 0, 0, 0.1);
    color: var(--text-color);
}
#accordion .card .u-accordion__body {
    padding: 1.5rem 2rem;
}
#accordion .card .u-accordion__body p:last-child {
    margin-bottom: 0;
}
CSS
);
?>

<? if (@$elements) : ?>
    <span itemscope itemtype="https://schema.org/FAQPage">
    <div id="accordion" class="u-accordion u-accordion-color-primary" role="tablist" aria-multiselectable="true">
        <? foreach ($elements as $model) : ?>
            <!-- Card -->
            <div class="card g-brd-none rounded g-mb-20 g-bg-secondary" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <div id="accordion-heading-<?= $model->id; ?>" class="g-pa-0 sx-accordion-heading" role="tab">
                    <h5 class="mb-0">

                        <a class="collapsed d-flex justify-content-between u-shadow-v19 g-color-main g-text-underline--none--hover rounded g-px-30 g-py-20"
                           href="#accordion-body-<?= $model->id; ?>"
                           data-toggle="collapse"
                           data-parent="#accordion"
                           aria-expanded="false"
                           aria-controls="accordion-body-01"

                        >

                            <span itemprop="name">
                            <?= $model->name; ?>
                                </span>

                            <span class="u-accordion__control-icon g-color-primary">

                          <i class="fas fa-angle-down"></i>

                          <i class="fas fa-angle-up"></i>

                        </span>

                        </a>

                    </h5>
                </div>
                <div id="accordion-body-<?= $model->id; ?>" class="collapse" role="tabpanel" aria-labelledby="accordion-heading-<?= $model->id; ?>" data-parent="#accordion"
                     itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer"
                >
                    <div class="u-accordion__body g-color-gray-dark-v4 g-pa-30">
                        <span itemprop="text">
                        <?= $model->response; ?>
                        </span>
                    </div>
                </div>
            </div>
            <!-- End Card -->
        <? endforeach; ?>
    </div>
    </span>
<? endif; ?>

