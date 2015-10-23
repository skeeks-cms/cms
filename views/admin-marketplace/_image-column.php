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
    <?= $this->render('_package-column', [
        'model' => $model->marketplacePackage
    ]); ?>
<? else : ?>
    <div>
        <p>
            <a data-pjax="0" href="<?= $model->getPackagistUrl(); ?>" class="btn btn-default btn-xs" target="_blank" title="<?=\Yii::t('app','Watch to {site} (opens in new window)',['site' => 'Packagist.org'])?>">
                <?= $model->name; ?>
                <i class="glyphicon glyphicon-search"></i>
            </a>
        </p>
    </div>
<? endif; ?>
