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
            Расширение не найдено
        <? endif; ?>

    </div>
<? else : ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-info',
      ]
    ]); ?>
        <p>Вы можете выбрать подходящее решение для вашего проекта и установить его.</p>
        <p>В следующих версиях маркетплей будет интегрирован сюда. А пока, просто перейдите по ссылке ниже.</p>
    <? \yii\bootstrap\Alert::end(); ?>

    <div class="sx-marketplace">
        <a href="http://marketplace.cms.skeeks.com/" target="_blank">Marketplace.CMS.SkeekS.com</a> — каталог доступных решений
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

