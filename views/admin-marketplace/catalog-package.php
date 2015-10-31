<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var $packageModel PackageModel */

use \skeeks\cms\components\marketplace\models\PackageModel;
use \skeeks\cms\models\CmsExtension;
$self = $this;

$extension = $packageModel->createCmsExtension();
?>
<div class="row">
    <div class="col-lg-2">
        <a href="<?= $packageModel->imageSrc; ?>" class="sx-img-link-hover sx-border-1px sx-fancybox">
            <img src="<?= $packageModel->imageSrc; ?>" style="width: 100%" />
        </a>
    </div>
    <div class="col-lg-8">
        <h1><?= $packageModel->name; ?></h1>
        <h2><?= $packageModel->packagistCode; ?></h2>
        <a data-pjax="0" href="<?= $packageModel->url; ?>" class="btn btn-default btn-primary" target="_blank" title="<?=\Yii::t('app','Watch to {site} (opens in new window)',['site' => \Yii::t('app','Marketplace')])?>">
            <i class="glyphicon glyphicon-shopping-cart"></i> <?= \Yii::t('app','Marketplace') ?>
        </a>

        <? if ($packageModel->isInstalled()) : ?>
            <a data-pjax="0" href="<?= $packageModel->url; ?>" class="btn btn-default btn-success" target="_blank" title="">
                <i class="glyphicon glyphicon-ok"></i> <?=\Yii::t('app','Installed version')?>: <?= $extension->version; ?>
            </a>

            <? if ($extension) : ?>
                <? if ($extension->canDelete()) : ?>
                    <a data-pjax="0" href="<?= $extension->controllUrl; ?>" class="btn btn-default btn-danger" title="">
                        <i class="glyphicon glyphicon-remove"></i> <?=\Yii::t('app','Delete')?>
                    </a>
                <? endif; ?>
            <? endif; ?>


        <? else : ?>
            <a data-pjax="0" href="<?= $packageModel->url; ?>" class="btn btn-default btn-danger" target="_blank" title="">
                <i class="glyphicon glyphicon-download-alt"></i> <?=\Yii::t('app','Install')?>
            </a>

        <? endif; ?>

        <p></p>
        <p><?= $packageModel->description_short; ?></p>
    </div>
</div>
<? if ($images = $packageModel->imagesSrc) : ?>
    <div class="row" style="margin-top: 15px;">
        <div class="col-lg-12">
        <h2><?=\Yii::t('app','Photos and screenshots')?></h2>
        <? foreach($images as $image) : ?>
            <a href="<?= $image; ?>" class="sx-fancybox sx-img-link-hover sx-border-1px">
                <img src="<?= $image; ?>" style="max-width: 300px;"/>
            </a>
        <? endforeach; ?>
        </div>
    </div>
<? endif; ?>

<? if ($videoUrl = $packageModel->getVideoUrl()) : ?>
    <div class="row" style="margin-top: 15px;">
        <div class="col-lg-12">
            <h2>Видео</h2>
            <iframe allowfullscreen="" frameborder="0" height="315" src="<?= $videoUrl; ?>" width="560"></iframe>

        </div>
    </div>
<? endif; ?>

<div class="row" style="margin-top: 15px;">
      <div class="col-lg-12">

          <? $form = \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::begin(); ?>
          <?= $form->fieldSet(\Yii::t('app','Description')); ?>
              <?= $packageModel->description_full; ?>
          <?= $form->fieldSetEnd(); ?>

          <?= $form->fieldSet(\Yii::t('app','Installation')); ?>
              <? if ($packageModel->installHelp): ?>
                  <?= $packageModel->installHelp; ?>
              <? else: ?>
                  <?=\Yii::t('app','To install the module, you must run the standard installation.')?>
              <? endif; ?>
          <?= $form->fieldSetEnd(); ?>

          <?= $form->fieldSet(\Yii::t('app','Support')); ?>
              <? if ($packageModel->support): ?>
                  <?= $packageModel->support; ?>
              <? else: ?>
                  <?=\Yii::t('app','The developer did not leave a contact for communication with them. But you can always turn to the {cms} developers',['cms' => 'SkeekS CMS'])?>  <a href="http://cms.skeeks.com/contacts" target="_blank">http://cms.skeeks.com/contacts</a>
              <? endif; ?>
          <?= $form->fieldSetEnd(); ?>

          <? if ($packageModel->demoUrl) : ?>
              <?= $form->fieldSet(\Yii::t('app','Demo')); ?>
                  <a href="<?= $packageModel->demoUrl; ?>" target="_blank"><?= $packageModel->demoUrl; ?></a>
              <?= $form->fieldSetEnd(); ?>
          <? endif; ?>

          <? if ($images = $packageModel->imagesSrc) : ?>
              <?= $form->fieldSet(\Yii::t('app','Photos and screenshots')); ?>
                <div class="row" style="margin-top: 15px;">
                    <div class="col-lg-12">
                    <? foreach($images as $image) : ?>
                        <a href="<?= $image; ?>" class="sx-fancybox">
                            <img src="<?= $image; ?>" style="max-width: 300px;"/>
                        </a>
                    <? endforeach; ?>
                    </div>
                </div>
              <?= $form->fieldSetEnd(); ?>
        <? endif; ?>

          <? if ($extension->changeLog) : ?>
              <?= $form->fieldSet(\Yii::t('app','Development process')); ?>
                    <?= \kartik\markdown\Markdown::convert($extension->changeLog); ?>

              <?= $form->fieldSetEnd(); ?>
          <? endif; ?>

          <? if ($extension->readme) : ?>
              <?= $form->fieldSet(\Yii::t('app','For developer')); ?>
              <?= \kartik\markdown\Markdown::convert($extension->readme); ?>
              <?= $form->fieldSetEnd(); ?>
          <? endif; ?>


          <? \skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab::end(); ?>
      </div>
</div>