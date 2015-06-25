<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.06.2015
 */
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsExtension */
?>

<? if ($model->marketplacePackage) : ?>
    <div style="width: 120px; float:left; ">

        <a data-pjax="0" href="<?= $model->marketplacePackage->adminUrl->toString(); ?>">

            <?= \yii\helpers\Html::img($model->marketplacePackage->image, [
                'width' => '100'
            ]); ?>

        </a>
    </div>
    <div>
        <h3 style="margin-top: 0px;">
            <a data-pjax="0" href="<?= $model->marketplacePackage->adminUrl->toString(); ?>">
                <?= $model->marketplacePackage->name; ?>
            </a>
        </h3>
        <p>
            <a data-pjax="0" href="<?= $model->getPackagistUrl(); ?>" class="btn btn-default btn-xs" target="_blank" title="Посмотреть на Packagist.org (откроется в новом окне)">
                <?= $model->marketplacePackage->packagistCode; ?>
                <i class="glyphicon glyphicon-search"></i>
            </a>
            <i class="glyphicon glyphicon-user"></i> <?= $model->marketplacePackage->authorName; ?>
        </p>

        <p>
            <a data-pjax="0" href="<?= $model->marketplacePackage->url; ?>" class="btn btn-default btn-primary" target="_blank" title="Посмотреть на Маркетплейс (откроется в новом окне)"><i class="glyphicon glyphicon-shopping-cart"></i> Маркетплейс</a>
        </p>
    </div>
<? else : ?>
    <div>
        <p>
            <a data-pjax="0" href="<?= $model->getPackagistUrl(); ?>" class="btn btn-default btn-xs" target="_blank" title="Посмотреть на Packagist.org (откроется в новом окне)">
                <?= $model->name; ?>
                <i class="glyphicon glyphicon-search"></i>
            </a>
        </p>
    </div>
<? endif; ?>
