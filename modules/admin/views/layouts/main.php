<?php
use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;

/* @var $this \yii\web\View */
/* @var $content string */

AdminAsset::register($this);

$sidebarHidden = \yii\helpers\ArrayHelper::getValue($this->params, "sidebar-hidden", false);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="<?= $sidebarHidden ? "sidebar-hidden" : ""?>">
<?php $this->beginBody() ?>
<div class="navbar" role="navigation">
    <div class="navbar-header">
        <?= Html::a('<i class="fa fa-lightbulb-o"></i> <span>SkeekS Cms</span>', Yii::$app->homeUrl, ["class" => "navbar-brand"]); ?>
    </div>

    <? if (!$sidebarHidden): ?>

    <ul class="nav navbar-nav navbar-actions navbar-left">
        <li class="visible-md visible-lg"><a href="#" id="main-menu-toggle"><i class="fa fa-bars"></i></a></li>
        <li class="visible-xs visible-sm"><a href="#" id="sidebar-menu"><i class="fa fa-bars"></i></a></li>
    </ul>

    <? endif; ?>

    <!--<ul class="nav navbar-nav visible-md visible-lg">
        <li>&nbsp;</li>
        <li><button onclick="return false;" class="btn btn-default">Перейти на сайт</button></li>
    </ul>-->

    <ul class="nav navbar-nav navbar-right visible-md visible-lg">
        <li><span class="timer"><i class="icon-clock"></i> <span id="clock"><!-- JavaScript clock will be displayed here, if you want to remove clock delete parent <li> --></span></span></li>
        <li class="dropdown visible-md visible-lg"></li>
        <? if (!Yii::$app->user->isGuest): ?>
        <li class="dropdown visible-md visible-lg">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-settings"></i><!--<span class="badge">!</span>--></a>
            <ul class="dropdown-menu">
                <li class="dropdown-menu-header text-center">
                    <strong><?= Yii::$app->user->identity->username ?></strong>
                </li>
                <li><a href="<?= Yii::$app->urlManager->createUrl("profile") ?>"><i class="glyphicon glyphicon-user"></i> Профиль</a></li>
                <li><a href="#"><i class="fa fa-envelope-o"></i> Сообщения <span class="label label-info">42</span></a></li>
                <li class="divider"></li>
                <li>
                    <?= Html::a('<i class="fa fa-lock"></i> Выход', Yii::$app->urlManager->createUrl("site/logout"), ["data-method" => "post"])?>
                </li>
            </ul>
        </li>
        <? endif; ?>
    </ul>

    <?php
/*        NavBar::begin([
            'brandLabel' => 'My Company',
            'brandUrl' => Yii::$app->homeUrl,
            'options' => [
                'class' => 'navbar-inverse navbar-fixed-top',
            ],
        ]);
        $menuItems = [
            ['label' => 'Home', 'url' => ['/site/index']],
        ];
        if (Yii::$app->user->isGuest) {
            $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
        } else {
            $menuItems[] = [
                'label' => 'Logout (' . Yii::$app->user->identity->username . ')',
                'url' => ['/site/logout'],
                'linkOptions' => ['data-method' => 'post']
            ];
        }
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav navbar-right'],
            'items' => $menuItems,
        ]);
        NavBar::end();
    */?>
</div>

<? if (!$sidebarHidden): ?>
<!-- start: Main Menu -->
<div class="sidebar sx-sidebar">
    <div class="inner-wrapper scrollbar-macosx">
        <div class="sidebar-collapse sx-sidebar-collapse">

            <div class="sidebar-menu" id="sx-admin-menu-">
                <div class="sx-head">
                    <i class="icon icon-arrow-up" style=""></i>
                    Основное
                </div>

                <ul class="nav nav-sidebar">
                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("user") ?>" title="#">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Управление пользователями</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("user-authclient") ?>" title="#">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Социальные профили</span>
                        </a>
                    </li>
                </ul>
            </div>


            <div class="sidebar-menu" id="sx-admin-menu-">
                <div class="sx-head">
                    <i class="icon icon-arrow-up" style=""></i>
                    Игровой портал
                </div>

                <ul class="nav nav-sidebar">
                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("game") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Игры</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("game-genre") ?>">
                            <span class="sx-icon">
                            </span>
                            <span class="txt">Игровые жанры</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("game-platform") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Игровые платформы</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("game-company") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Компании</span>
                        </a>
                    </li>

                </ul>
            </div>


            <div class="sidebar-menu" id="sx-admin-menu-">
                <div class="sx-head">
                    <i class="icon icon-arrow-up" style=""></i>
                    Публикации
                </div>

                <ul class="nav nav-sidebar">
                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("publication") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Публикации</span>
                        </a>
                    </li>
                </ul>
            </div>


            <div class="sidebar-menu" id="sx-admin-menu-">
                <div class="sx-head">
                    <i class="icon icon-arrow-up" style=""></i>
                    Комментарии
                </div>

                <ul class="nav nav-sidebar">
                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("comment") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Комментарии</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-menu" id="sx-admin-menu-">
                <div class="sx-head">
                    <i class="icon icon-arrow-up" style=""></i>
                    Файлы
                </div>

                <ul class="nav nav-sidebar">
                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("storage-file") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Управление файлами</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="sidebar-menu" id="sx-admin-menu-">
                <div class="sx-head">
                    <i class="icon icon-arrow-up" style=""></i>
                    Для разработчика
                </div>

                <ul class="nav nav-sidebar">
                    <li>
                        <a href="<?= Yii::$app->urlManager->createUrl("developer/gii") ?>">
                            <span class="sx-icon">
                                
                            </span>
                            <span class="txt">Генератор кода Yii</span>
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>
<!-- end: Main Menu -->

<? endif; ?>



<div class="main">



    <div class="col-lg-12">


        <div class="panel panel-primary sx-panel">

            <div class="panel-heading sx-no-icon">
                <h2>
                    <?= Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                </h2>
                <div class="panel-actions">
                </div>

            </div><!-- End .panel-heading -->

            <div class="panel-body">
                <div class="panel-content-before">
                    <?/*= Alert::widget() */?>
                </div>
                <div class="panel-content">
                    <?= $content ?>
                </div><!-- End .panel-body -->
            </div><!-- End .panel-body -->
        </div><!-- End .widget -->

    </div><!-- End .col-lg-12  -->



</div>

<footer class="sx-admin-footer">
    <div class="row">
        <div class="col-sm-5">
            &copy; SkeekS <?= date('Y') ?> - сайт разработчика cms: <a href="http://skeeks.com" target="_blank" data-sx-widget="tooltip" title="Перейти на сайт разработчика системы">SkeekS.com</a>
        </div><!--/.col-->
        <div class="col-sm-7 text-right">

        </div><!--/.col-->
    </div><!--/.row-->
</footer>

    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
