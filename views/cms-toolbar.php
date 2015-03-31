<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.03.2015
 */
/* @var $this yii\web\View */
use \skeeks\cms\helpers\UrlHelper;
$clientOptionsJson = \yii\helpers\Json::encode($clientOptions);
?>

<div id="skeeks-cms-toolbar" class="skeeks-cms-toolbar-top hidden-print">
    <div class="skeeks-cms-toolbar-block title">
        <a href="<?= \Yii::$app->cms->moduleCms()->getDescriptor()->homepage; ?>" title="Текущая версия SkeekS SMS <?= \Yii::$app->cms->moduleCms()->getDescriptor()->getVersion(); ?>" target="_blank">
            <img width="29" height="30" alt="" src="<?= \Yii::$app->cms->logo(); ?>">
             <span class="label"><?= \Yii::$app->cms->moduleCms()->getDescriptor()->getVersion(); ?></span>
        </a>
    </div>

    <div class="skeeks-cms-toolbar-block">
        <a href="<?= UrlHelper::construct('')->enableAdmin()->toString(); ?>" title="Перейти в панель администрирования"><span class="label label-info">Администрирование</span></a>
    </div>

    <div class="skeeks-cms-toolbar-block">
        <a onclick="new sx.classes.toolbar.Dialog('<?= $urlSettings; ?>'); return false;" href="<?= $urlSettings; ?>" title="Управление настройками проекта"><span class="label label-info">Настройки проекта</span></a>
    </div>

    <div class="skeeks-cms-toolbar-block sx-profile">
        <a href="<?= $urlUserEdit; ?>" onclick="new sx.classes.toolbar.Dialog('<?= $urlUserEdit; ?>'); return false;" title="Это вы, перейти к редактированию свох данных">
            <img height="30" src="<?= \skeeks\cms\helpers\Image::getSrc(\Yii::$app->cms->getAuthUser()->getAvatarSrc()); ?>" style="margin-left: 5px;"/> <span class="label label-info"><?= \Yii::$app->cms->getAuthUser()->getDisplayName(); ?></span></a>
    </div>

    <? if ($urlEditModel && $editModel) : ?>
    <div class="skeeks-cms-toolbar-block">
        <a href="<?= $urlEditModel; ?>" onclick="new sx.classes.toolbar.Dialog('<?= $urlEditModel; ?>'); return false;" title="Редактировать">
             <span class="label">Редактировать страницу</span>
        </a>
    </div>
    <? endif; ?>

    <div class="skeeks-cms-toolbar-block">
        <input type="checkbox" value="1" onclick="sx.Toolbar.triggerEditMode();" <?= \Yii::$app->cmsToolbar->isEditMode() ? "checked" : ""; ?>/>
        <span>Режим редактирования</span>
    </div>

    <span class="skeeks-cms-toolbar-toggler" onclick="sx.Toolbar.close(); return false;">›</span>
</div>

<div id="skeeks-cms-toolbar-min">
    <a href="#" onclick="sx.Toolbar.open(); return false;" title="Открыть панель управления SkeekS Cms" id="skeeks-cms-toolbar-logo">
        <img width="29" height="30" alt="" src="<?= \Yii::$app->cms->logo(); ?>">
    </a>
    <span class="skeeks-cms-toolbar-toggler" onclick="sx.Toolbar.open(); return false;">‹</span>
</div>

<?
$this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.Toolbar = new sx.classes.SkeeksToolbar({$clientOptionsJson});

    })(sx, sx.$, sx._);
JS
);
?>