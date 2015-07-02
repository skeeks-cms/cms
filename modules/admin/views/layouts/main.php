<?php
use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;

/* @var $this \yii\web\View */
/* @var $content string */

AdminAsset::register($this);
\Yii::$app->admin->registerAsset($this);

$sidebarHidden = \Yii::$app->user->getIsGuest();

$userLastActivity = [
    'lastAdminActivityAgo'  => \Yii::$app->user->identity->lastAdminActivityAgo,
    'blockedTime'           => \Yii::$app->admin->blockedTime,
    'timeLeft'              => (\Yii::$app->admin->blockedTime - \Yii::$app->user->identity->lastAdminActivityAgo),
    'startTime'             => \Yii::$app->formatter->asTimestamp(time()),
];
$userLastActivity = \yii\helpers\Json::encode($userLastActivity);

$this->registerJs(<<<JS
    new sx.classes.UserLastActivity({$userLastActivity});
JS
)

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <link rel="icon" href="http://skeeks.com/favicon.ico"  type="image/x-icon" />
    <?php $this->head() ?>
</head>
<body class="<?= $sidebarHidden ? "sidebar-hidden" : ""?> <?= \Yii::$app->admin->isEmptyLayout() ? "empty" : ""?>">


<?php $this->beginBody() ?>
<div class="navbar sx-navbar" role="navigation">
    <!--<div class="navbar-header">
        <?/*= Html::a('<i class="fa fa-lightbulb-o"></i> <!--<span>Logo</span>-->', \Yii::$app->cms->moduleAdmin()->createUrl(["admin/index/index"]), ["class" => "navbar-brand"]); */?>
    </div>-->

    <? if (!$sidebarHidden): ?>

    <ul class="nav navbar-nav navbar-actions navbar-left">
        <li class="visible-md visible-lg visible-sm visible-xs">
            <a href="<?= \Yii::$app->cms->moduleAdmin()->createUrl(["admin/index/index"]); ?>" data-sx-widget="tooltip-b" data-original-title="На главную страницу админки"><i class="glyphicon glyphicon-home"></i></a>
        </li>
    </ul>

    <? endif; ?>

    <ul class="nav navbar-nav navbar-right visible-md visible-lg visible-sm visible-xs sx-top-nav-menu">
        <!--<li><span class="timer"><i class="icon-clock"></i> <span id="clock"></span></span></li>-->
        <li class="dropdown visible-md visible-lg"></li>
        <? if (!Yii::$app->user->isGuest): ?>


        <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
            <a href="/" style="width: auto;" data-sx-widget="tooltip-b" data-original-title="Открыть сайтовую часть"><i class="glyphicon glyphicon-globe"></i></a>
        </li>

        <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
            <a class="request-fullscreen toggle-active" href="#" onclick="new sx.classes.Fullscreen(); return false;" data-sx-widget="tooltip-b" data-original-title="Переключение полноэкранного режима">
                <i class="glyphicon glyphicon-fullscreen"></i>
            </a>
        </li>

        <li class="sx-left-border dropdown visible-md visible-lg visible-sm visible-xs">
            <a href="<?= UrlHelper::construct('cms/admin-settings')->enableAdmin(); ?>" style="width: auto;" data-sx-widget="tooltip-b" data-original-title="Настройки проекта"><i class="glyphicon glyphicon-cog"></i></a>
        </li>

        <li class="dropdown sx-left-border">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding: 0px;" data-sx-widget="tooltip-b" data-original-title="Ваш профиль">
                <? if (Yii::$app->cms->getAuthUser()->hasMainImage()) : ?>
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
                <li><a href="<?= UrlHelper::construct("cms/admin-profile/update")->enableAdmin() ?>"><i class="glyphicon glyphicon-user"></i> Профиль</a></li>
                <!--<li><a href="#"><i class="fa fa-envelope-o"></i> Сообщения <span class="label label-info">42</span></a></li>-->
                <li class="divider"></li>
                <li>
                    <?= Html::a('<i class="fa fa-shield"></i> Заблокировать', UrlHelper::construct("admin/auth/lock")->enableAdmin()->setCurrentRef(), ["data-method" => "post"])?>
                </li>
                <li>
                    <?= Html::a('<i class="glyphicon glyphicon-off"></i> Выход', UrlHelper::construct("admin/auth/logout")->enableAdmin()->setCurrentRef(), ["data-method" => "post"])?>
                </li>
            </ul>
        </li>

        <? endif; ?>
    </ul>

</div>

<? if (!$sidebarHidden): ?>

<?/* if ($this->beginCache('test', ['variations' => [Yii::$app->language, \Yii::$app->cms->getAuthUser()->id]])) : */?>
<!-- start: Main Menu -->
<div class="sidebar sx-sidebar">

    <a href="#" onclick="sx.App.Menu.toggleTrigger(); return false;" class="btn btn-default btn-xs sx-main-menu-toggle sx-main-menu-toggle-opened" data-sx-widget="tooltip-l" data-original-title="Закрыть меню">
        <i class="glyphicon glyphicon-menu-left"></i>
    </a>

    <a href="#" onclick="sx.App.Menu.toggleTrigger(); return false;" class="btn btn-default btn-xs sx-main-menu-toggle sx-main-menu-toggle-closed" data-sx-widget="tooltip-r" data-original-title="Открыть меню">
        <i class="glyphicon glyphicon-menu-right"></i>
    </a>

    <div class="inner-wrapper scrollbar-macosx">
        <div class="sidebar-collapse sx-sidebar-collapse">

            <? if ($items = \Yii::$app->adminMenu->getItems()) : ?>
                <? foreach ($items as $adminMenuItem) : ?>
                    <? if ($adminMenuItem->isAllowShow()) : ?>
                        <div class="sidebar-menu" id="<?= $adminMenuItem->code; ?>">
                            <div class="sx-head" title="<?= $adminMenuItem->label; ?>">
                                <? if ($imgUrl = $adminMenuItem->getImgUrl()) : ?>
                                    <span class="sx-icon">
                                        <img src="<?= $imgUrl; ?>" />
                                    </span>
                                <? else : ?>
                                    <i class="icon icon-arrow-up" style=""></i>
                                <? endif; ?>
                                <?= $adminMenuItem->label; ?>
                            </div>

                            <? if ($subAdminMenuItems = $adminMenuItem->items) : ?>
                                <ul class="nav nav-sidebar">
                                <? foreach ($subAdminMenuItems as $subAdminMenuItem) : ?>
                                    <? if ($subAdminMenuItem->isAllowShow()) : ?>
                                        <li <?= $subAdminMenuItem->isActive() ? 'class="active"' : '' ?>>
                                            <a href="<?= $subAdminMenuItem->getUrl() ?>" title="<?= $subAdminMenuItem->label; ?>" class="sx-test">
                                                <span class="sx-icon">
                                                    <img src="<?= $subAdminMenuItem->getImgUrl(); ?>" />
                                                </span>
                                                <span class="txt"><?= $subAdminMenuItem->label; ?></span>
                                            </a>
                                        </li>
                                    <? endif; ?>
                                <? endforeach; ?>
                                </ul>
                            <? endif; ?>
                        </div>
                    <? endif; ?>
                <? endforeach; ?>
            <? endif; ?>

        </div>
    </div>
</div>
<?/*= $this->endCache();*/?>
<?/* endif; */?>
<!-- end: Main Menu -->
<? endif; ?>



<div class="main">


    <div class="col-lg-12">
        <div class="panel panel-primary sx-panel sx-panel-content">
            <div class="panel-heading sx-no-icon">
                <h2>
                    <?= Breadcrumbs::widget([
                        'homeLink' => ['label' => \Yii::t("yii", "Home"), 'url' =>
                            UrlHelper::construct('admin/index')->enableAdmin()->toString()
                        ],
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                </h2>
                <div class="panel-actions">
                </div>
            </div><!-- End .panel-heading -->
            <div class="panel-body">
                    <div class="panel-content-before">
                        <? if (!UrlHelper::constructCurrent()->getSystem(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL)) : ?>
                            <?= \yii\helpers\ArrayHelper::getValue($this->params, 'actions'); ?>
                        <? endif; ?>
                        <?/*= Alert::widget() */?>
                    </div>
                    <div class="panel-content sx-unblock-onWindowReady">
                        <!--<div class="sx-show-onWindowReady">-->
                            <?= \skeeks\cms\modules\admin\widgets\Alert::widget(); ?>
                            <?= $content ?>
                        <!--</div>-->
                    </div><!-- End .panel-body -->
            </div><!-- End .panel-body -->
        </div><!-- End .widget -->

    </div><!-- End .col-lg-12  -->


</div>

<footer class="sx-admin-footer">
    <div class="row">
        <div class="col-sm-5">
            <div class="sx-footer-copyright">
                <a href="http://cms.skeeks.com" target="_blank" data-sx-widget="tooltip" title="Перейти на сайт SkeekS CMS">
                    <?= \Yii::$app->cms->moduleCms()->getDescriptor()->getCopyright(); ?>
                </a>
                | <a href="http://skeeks.com" target="_blank" data-sx-widget="tooltip" title="Перейти на сайт разработчика системы">SkeekS.com</a>
            </div>
        </div><!--/.col-->
        <div class="col-sm-7 text-right">

        </div><!--/.col-->
    </div><!--/.row-->
</footer>

    <?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
