<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $this yii\web\View
 */
$this->registerCss(<<<CSS
.sx-design .card {
    text-align: center;
}
.sx-design .sx-img-wrapper {
    margin-bottom: 20px;
}

.sx-additional {
    font-size: 12px;
}
.sx-not-use {
    color: gray;
}

.sx-use {
    color: green;
    font-weight: bold;
}
.sx-card-active {
    border-color: green;
    background: #00800012;
}

.sx-item {
    cursor:pointer;
    height: 100%;
    text-align: center;
}
.sx-item-wrapper {
    margin: 15px 0;
}

.sx-info {
    /*position: absolute;
    bottom: 0;
    width: 100%*/;
}
CSS
);
/**
 * @var \skeeks\cms\models\CmsTheme[] $cmsThemes
 */
$cmsThemes = \skeeks\cms\models\CmsTheme::find()->cmsSite()->sort()->all();
?>
<div class="sx-design">
    <h1>Дизайн сайта</h1>

    <div class="alert-default alert">
        В этом разделе вы можете выбрать нужную тему дазайна для сайта и настроить ее.
    </div>

    <?php if ($cmsThemes) : ?>
        <div class="row">
            <?php foreach ($cmsThemes as $cmsTheme) : ?>
                <div class="col-lg-3 col-md-4 col-sm-6 sx-item-wrapper">
                    <div class="sx-item sx-block g-pa-15 g-mb-30 sx-bg-secondary <?php echo $cmsTheme->is_active ? "sx-card-active" : ""; ?>"
                         onclick="location.href='<?php echo \yii\helpers\Url::to(['update', 'code' => $cmsTheme->code]); ?>'; return false;">

                        <div class="sx-img-wrapper">
                            <img src="<?php echo $cmsTheme->themeImageSrc; ?>" class="img-fluid">
                        </div>
                        <div class="sx-info">
                            <div class="sx-title">
                                <h4><?php echo $cmsTheme->themeName; ?></h4>
                            </div>
                            <div class="sx-additional">
                                <? if ($cmsTheme->is_active) : ?>
                                    <div class="sx-use">✓ Используется</div>
                                <? else : ?>
                                    <div class="sx-not-use">Не используется</div>
                                <? endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
