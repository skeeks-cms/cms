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
        <a href="<?= \Yii::$app->cms->moduleCms()->getDescriptor()->homepage; ?>" title="<?=\Yii::t('app','The current version {cms} ',['cms' => 'SkeekS SMS'],\Yii::$app->admin->languageCode)?> <?= \Yii::$app->cms->moduleCms()->descriptor->version; ?>" target="_blank">
            <img width="29" height="30" alt="" src="<?= \Yii::$app->cms->logo(); ?>">
             <span class="label"><?= \Yii::$app->cms->moduleCms()->descriptor->version; ?></span>
        </a>
    </div>

    <? if (\Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS)) : ?>
        <div class="skeeks-cms-toolbar-block">
            <a href="<?= UrlHelper::construct('')->enableAdmin()->toString(); ?>" title="<?=\Yii::t('app','Go to the administration panel',[],\Yii::$app->admin->languageCode)?>"><span class="label label-info"><?=\Yii::t('app','Administration',[],\Yii::$app->admin->languageCode)?></span></a>
        </div>
    <? endif; ?>


    <? if (\Yii::$app->user->can('cms/admin-settings')) : ?>
        <div class="skeeks-cms-toolbar-block">
            <a onclick="new sx.classes.toolbar.Dialog('<?= $urlSettings; ?>'); return false;" href="<?= $urlSettings; ?>" title="<?=\Yii::t('app','Managing project settings',[],\Yii::$app->admin->languageCode)?>"><span class="label label-info"><?=\Yii::t('app','Project settings',[],\Yii::$app->admin->languageCode)?></span></a>
        </div>
    <? endif; ?>

    <div class="skeeks-cms-toolbar-block sx-profile">
        <a href="<?= $urlUserEdit; ?>" onclick="new sx.classes.toolbar.Dialog('<?= $urlUserEdit; ?>'); return false;" title="<?=\Yii::t('app','It is you, go to edit your data',[],\Yii::$app->admin->languageCode)?>">
            <img src="<?= \skeeks\cms\helpers\Image::getSrc(\Yii::$app->cms->getAuthUser()->getAvatarSrc()); ?>"/>
            <span class="label label-info"><?= \Yii::$app->cms->getAuthUser()->getDisplayName(); ?></span>
        </a>
        <!--<a href="<?/*= $urlEditModel; */?>" onclick="new sx.classes.toolbar.Dialog('<?/*= $urlEditModel; */?>'); return false;" title="Выход">
             <span class="label">Выход</span>
        </a>-->
        <?= \yii\helpers\Html::a('<span class="label">'.\Yii::t('app','Exit',[],\Yii::$app->admin->languageCode).'</span>', UrlHelper::construct("admin/auth/logout")->enableAdmin()->setCurrentRef(), ["data-method" => "post"])?>

    </div>

    <? if ($urlEditModel && $editModel) : ?>
        <div class="skeeks-cms-toolbar-block">
            <a href="<?= $urlEditModel; ?>" onclick="new sx.classes.toolbar.Dialog('<?= $urlEditModel; ?>'); return false;" title="<?=\Yii::t('app','Edit the current page',[],\Yii::$app->admin->languageCode)?>">
                 <span class="label"><?=\Yii::t('app','Edit',[],\Yii::$app->admin->languageCode)?></span>
            </a>
        </div>
    <? endif; ?>

    <div class="skeeks-cms-toolbar-block">
        <input type="checkbox" value="1" onclick="sx.Toolbar.triggerEditMode();" <?= \Yii::$app->cmsToolbar->isEditMode() ? "checked" : ""; ?>/>
        <span><?=\Yii::t('app','Editing widgets',[],\Yii::$app->admin->languageCode)?></span>
    </div>


    <? if (\Yii::$app->user->can('admin/clear')) : ?>

        <?
            $clearCacheOptions = \yii\helpers\Json::encode([
                'backend' => UrlHelper::construct(['/admin/clear/index'])->enableAdmin()->toString()
            ]);

        $this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.ClearCache = sx.classes.Component.extend({

        execute: function(code)
        {
            this.ajaxQuery = sx.ajax.preparePostQuery(this.get('backend'), {
                'code' : code
            });

            var Handler = new sx.classes.AjaxHandlerStandartRespose(this.ajaxQuery);

            this.ajaxQuery.execute();
        }
    });

    sx.ClearCache = new sx.classes.ClearCache({$clearCacheOptions});

})(sx, sx.$, sx._);
JS
);
        ?>

        <div class="skeeks-cms-toolbar-block">

            <a href="#" onclick="sx.ClearCache.execute(); return false;" title="<?=\Yii::t('app','Clear cache and temporary files',[],\Yii::$app->admin->languageCode)?>">
                 <span class="label label-info"><?=\Yii::t('app','Clear cache',[],\Yii::$app->admin->languageCode)?></span>
            </a>
            <span></span>
        </div>
    <? endif; ?>

    <span class="skeeks-cms-toolbar-toggler" onclick="sx.Toolbar.close(); return false;">›</span>
</div>

<div id="skeeks-cms-toolbar-min">
    <a href="#" onclick="sx.Toolbar.open(); return false;" title="<?=\Yii::t('app','Open the Control Panel {cms}',['cms' => 'SkeekS Cms'],\Yii::$app->admin->languageCode)?>" id="skeeks-cms-toolbar-logo">
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