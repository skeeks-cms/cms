<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.03.2015
 */
/* @var $widget \skeeks\cms\widgets\formInputs\MainStorageFile */
/* @var $this yii\web\View */
?>
<?
    $this->registerCss(<<<CSS
.sx-fromWidget-mainImage
{}

    .sx-fromWidget-mainImage .sx-main-image img
    {
        max-width: 250px;
        border: 2px solid silver;
    }

    .sx-fromWidget-mainImage .sx-main-image img:hover
    {
        border: 2px solid #20a8d8;
    }

    .sx-fromWidget-mainImage .sx-controlls
    {
        margin-top: 3px;
    }

CSS
);
?>
<div class="sx-fromWidget-mainImage">
    <label>Главное изображение</label>
    <? if ($model->hasMainImage()) : ?>
        <div class="sx-main-image">
            <img src="<?= $model->getMainImageSrc(); ?>" />
            <div class="sx-controlls">
                <a href="#" class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-remove-circle"></i> Удалить</a>
            </div>
        </div>
    <? else: ?>
        <div class="sx-main-image">
            <img src="<?= \Yii::$app->cms->moduleAdmin()->noImage; ?>" />
        </div>
    <? endif; ?>
</div>