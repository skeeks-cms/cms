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

$sidebarHidden = \Yii::$app->user->getIsGuest();

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
<body class="<?= $sidebarHidden ? "sidebar-hidden" : ""?>">



<?php $this->beginBody() ?>
<div class="navbar" role="navigation">
    <div class="navbar-header">
        <?= Html::a('<i class="fa fa-lightbulb-o"></i> <span>SkeekS Cms</span>', \Yii::$app->cms->moduleAdmin()->createUrl(["admin/index/index"]), ["class" => "navbar-brand"]); ?>
    </div>

    <ul class="nav navbar-nav navbar-right visible-md visible-lg">
        <!--<li><span class="timer"><i class="icon-clock"></i> <span id="clock"></span></span></li>-->
        <li class="dropdown visible-md visible-lg"></li>
        <? if (!Yii::$app->user->isGuest): ?>
        <li class="dropdown visible-md visible-lg">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-settings"></i><!--<span class="badge">!</span>--></a>
            <ul class="dropdown-menu">
                <li class="dropdown-menu-header text-center">
                    <strong><?= Yii::$app->user->identity->username ?></strong>
                </li>
                <li><a href="<?= UrlHelper::construct("cms/admin-profile")->enableAdmin() ?>"><i class="glyphicon glyphicon-user"></i> Профиль</a></li>
                <!--<li><a href="#"><i class="fa fa-envelope-o"></i> Сообщения <span class="label label-info">42</span></a></li>-->
                <li class="divider"></li>
                <li>
                    <?= Html::a('<i class="fa fa-lock"></i> Выход', UrlHelper::construct("admin/auth/logout")->enableAdmin()->setCurrentRef(), ["data-method" => "post"])?>
                </li>
            </ul>
        </li>
        <? else: ?>
            <a href="/">Перейти на сайт &rarr;</a>
        <? endif; ?>
    </ul>
</div>

<?= \skeeks\cms\modules\admin\widgets\Alert::widget(); ?>
<?= $content ?>


<footer class="sx-admin-footer">
    <div class="row">
        <div class="col-sm-5">
            <?= \Yii::$app->cms->moduleCms()->getDescriptor()->getCopyright(); ?>
             | <a href="http://skeeks.com" target="_blank" data-sx-widget="tooltip" title="Перейти на сайт разработчика системы">SkeekS.com</a>
        </div><!--/.col-->
        <div class="col-sm-7 text-right">

        </div><!--/.col-->
    </div><!--/.row-->
</footer>

    <?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
