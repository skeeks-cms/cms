<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
/* @var $this yii\web\View */
/* @var string $packagistCode */
/* @var $packageModel PackageModel */
?>

<div id="sx-search" style="margin-bottom: 10px;">
    <p><b><a data-pjax="0" href="<?= \skeeks\cms\models\CmsExtension::getInstance('skeeks/cms')->adminUrl; ?>"><?=\Yii::t('app','{yii} Version',['yii' => 'SkeekS CMS'])?></a>: </b> <?= \Yii::$app->cms->moduleCms->descriptor->version; ?></p>
    <p><b><?=\Yii::t('app','{yii} Version',['yii' => 'Yii'])?>: </b> <?= Yii::getVersion(); ?></p>
</div>
<hr />
<p>
    Для обновления проекта воспользуйтесь мануалом:
    <a href="http://dev.cms.skeeks.com/docs/dev/ustanovka-nastroyka-konfigurirov/obnovlenie" target="_blank">
        как обновить SkeekS CMS?
    </a>
</p>
<p>
    Обратитесь к разработчикам CMS: <a href="http://skeeks.com/contacts" target="_blank">связь с разработчиками</a>.
</p>





