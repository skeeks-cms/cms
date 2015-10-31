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

?>

<? if ($code = \Yii::$app->request->get("code")) : ?>
    <div class="sx-box sx-p-10 sx-mb-10">

        <? if ($packageModel = PackageModel::fetchByCode($code)) : ?>

             <?= $this->render('catalog-package', [
                'packageModel' => $packageModel
            ])?>

        <? else: ?>
            <?= \Yii::t('app','The extension is not found') ?>
        <? endif; ?>

    </div>
<? else : ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-info',
      ]
    ]); ?>
        <p><?= \Yii::t('app','You can choose the suitable solution for your project and install it.') ?></p>
        <p><?= \Yii::t('app','Future versions will be integrated {market} here. For now, simply click the link below.',['market' => \Yii::t('app','Marketplace')])?></p>
    <? \yii\bootstrap\Alert::end(); ?>

    <div class="sx-marketplace">
        <a href="http://marketplace.cms.skeeks.com/" target="_blank">Marketplace.CMS.SkeekS.com</a> — <?=\Yii::t('app','catalog of available solutions')?>
    </div>

<?
$this->registerCss(<<<CSS
.sx-marketplace
{
    text-align: center;
    font-size: 30px;
    color: #e74c3c;
}
    .sx-marketplace a
    {
        font-size: 30px;
        color: #e74c3c;
    }
CSS
);
?>

<? endif; ?>

