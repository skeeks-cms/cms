<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 16.10.2015
 */
/* @var $this yii\web\View */

use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use skeeks\cms\helpers\UrlHelper;

$langOptions = \yii\helpers\Json::encode([
    'backend' => UrlHelper::construct(['/cms/admin-ajax/set-lang'])->enableAdmin()->toString()
]);

$clearCacheOptions = \yii\helpers\Json::encode([
    'backend' => UrlHelper::construct(['/admin/clear/index'])->enableAdmin()->toString()
]);

$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.ChangeLang = sx.classes.Component.extend({

        setLang: function(code)
        {
            this.ajaxQuery = sx.ajax.preparePostQuery(this.get('backend'), {
                'code' : code
            });

            var Handler = new sx.classes.AjaxHandlerStandartRespose(this.ajaxQuery);

            Handler.bind('success', function()
            {
                window.location.reload();
            });

            this.ajaxQuery.execute();
        }
    });

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
    sx.ChangeLang = new sx.classes.ChangeLang({$langOptions});

})(sx, sx.$, sx._);
JS
);
?>
<div class="navbar sx-navbar" role="navigation">
<? if (!\Yii::$app->user->isGuest): ?>
    <ul class="nav navbar-nav navbar-actions navbar-left">
        <li class="visible-md visible-lg visible-sm visible-xs">
            <a href="<?= \Yii::$app->cms->moduleAdmin()->createUrl(["admin/index/index"]); ?>" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','To main page of admin area')?>"><i class="glyphicon glyphicon-home"></i></a>
        </li>

        <li class="visible-md visible-lg visible-sm visible-xs">
            <a href="<?= \yii\helpers\Url::home(); ?>" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','To main page of site')?>"><i class="glyphicon glyphicon-globe"></i></a>
        </li>
    </ul>
<? endif; ?>

<ul class="nav navbar-nav navbar-right visible-md visible-lg visible-sm visible-xs sx-top-nav-menu">
    <!--<li><span class="timer"><i class="icon-clock"></i> <span id="clock"></span></span></li>-->
    <li class="dropdown visible-md visible-lg"></li>
    <? if (!Yii::$app->user->isGuest): ?>


    <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs dropdown">
        <a class="request-fullscreen toggle-active dropdown-toggle" style="width: auto;" href="#"  data-toggle="dropdown" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','Interface language')?>">
            [<?= \Yii::$app->admin->cmsLanguage->code; ?>] <?= \Yii::$app->admin->cmsLanguage->name; ?> <span class="caret"></span>
        </a>
        <? if ($langs = \skeeks\cms\models\CmsLang::find()->active()->all()) : ?>
            <ul class="dropdown-menu ">
            <? foreach ($langs as $lang) : ?>
                <li><a href="#" onclick="sx.ChangeLang.setLang('<?= $lang->code; ?>'); return false;">[<?= $lang->code; ?>] <?= $lang->name; ?></a></li>
            <? endforeach; ?>
            </ul>
        <? endif;  ?>
    </li>

    <!--<li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
        <a class="request-fullscreen toggle-active" href="#" onclick="new sx.classes.Fullscreen(); return false;" data-sx-widget="tooltip-b" data-original-title="<?/*=\Yii::t('app','Toggle Full Screen')*/?>">
            <i class="glyphicon glyphicon-fullscreen"></i>
        </a>
    </li>-->

    <? if (\Yii::$app->user->can('admin/clear')) : ?>
    <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
        <a href="#" onclick="sx.ClearCache.execute(); return false;" style="width: auto;" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','Clear cache and temporary files')?>"><i class="glyphicon glyphicon-refresh"></i></a>
    </li>
    <? endif; ?>

    <? if (\Yii::$app->user->can('cms/admin-settings')) : ?>
    <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
        <a href="<?= UrlHelper::construct('cms/admin-settings')->enableAdmin(); ?>" style="width: auto;" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','Project settings')?>"><i class="glyphicon glyphicon-cog"></i></a>
    </li>
    <? endif; ?>


    <li class="dropdown sx-left-border">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding: 0px;" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','Your profile')?>">
            <? if (Yii::$app->cms->getAuthUser()->image) : ?>
                <img src="<?= Yii::$app->cms->getAuthUser()->getAvatarSrc(); ?>" width="49" height="49"/>
            <? else : ?>
                <img src="<?= Yii::$app->cms->moduleAdmin()->noImage; ?>" width="49" height="49"/>
            <? endif; ?>
        </a>
        <!--sx-dropdown-menu-left-->
        <ul class="dropdown-menu ">
            <li class="dropdown-menu-header text-center">
                <strong><?= Yii::$app->cms->getAuthUser()->username ?></strong>
            </li>
            <li><a href="<?= UrlHelper::construct("cms/admin-profile/update")->enableAdmin() ?>"><i class="glyphicon glyphicon-user"></i> <?=\Yii::t('app','Profile')?></a></li>
            <!--<li><a href="#"><i class="fa fa-envelope-o"></i> Сообщения <span class="label label-info">42</span></a></li>-->
            <li class="divider"></li>
            <li>
                <?= Html::a('<i class="fa fa-shield"></i> '.\Yii::t('app','To block'), UrlHelper::construct("admin/auth/lock")->enableAdmin()->setCurrentRef(), ["data-method" => "post"])?>
            </li>
            <li>
                <?= Html::a('<i class="glyphicon glyphicon-off"></i> '.\Yii::t('app','Exit'), UrlHelper::construct("admin/auth/logout")->enableAdmin()->setCurrentRef(), ["data-method" => "post"])?>
            </li>
        </ul>
    </li>

    <? endif; ?>
</ul>
</div>