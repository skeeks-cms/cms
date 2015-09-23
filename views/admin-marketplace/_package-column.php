<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.06.2015
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\components\marketplace\models\PackageModel */
?>
<div style="width: 120px; float:left; ">

    <a data-pjax="0" href="<?= $model->adminUrl->toString(); ?>" class="sx-img-link-hover sx-border-1px">

        <?= \yii\helpers\Html::img($model->imageSrc, [
            'width' => '100'
        ]); ?>

    </a>
    
</div>
<div>
    <h3 style="margin-top: 0px;">
        <a data-pjax="0" href="<?= $model->adminUrl->toString(); ?>">
            <?= $model->name; ?>
        </a>
    </h3>
    <p>
        <a data-pjax="0" href="<?= $model->getPackagistUrl(); ?>" class="btn btn-default btn-xs" target="_blank" title="Посмотреть на Packagist.org (откроется в новом окне)">
            <?= $model->packagistCode; ?>
            <i class="glyphicon glyphicon-search"></i>
        </a>
        <i class="glyphicon glyphicon-user"></i> <?= $model->authorName; ?>
    </p>

    <p>
        <a data-pjax="0" href="<?= $model->url; ?>" class="btn btn-default btn-primary" target="_blank" title="Посмотреть на Маркетплейс (откроется в новом окне)"><i class="glyphicon glyphicon-shopping-cart"></i> Маркетплейс</a>
    </p>
</div>