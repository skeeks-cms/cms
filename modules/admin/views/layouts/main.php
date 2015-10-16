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
\Yii::$app->admin->registerAsset($this)->initJs();
\skeeks\cms\modules\admin\widgets\UserLastActivityWidget::widget();
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
<body class="<?= \Yii::$app->user->isGuest ? "sidebar-hidden" : ""?> <?= \Yii::$app->admin->isEmptyLayout() ? "empty" : ""?>">


<?php $this->beginBody() ?>
<div class="navbar sx-navbar" role="navigation">
    <?= $this->render('_header'); ?>
</div>

<? if (!\Yii::$app->user->isGuest): ?>

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

            <?= $this->render('_admin-menu'); ?>

        </div>
    </div>
</div>
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

                    <? if (\Yii::$app->user->can('admin/admin-role') && \Yii::$app->controller instanceof \skeeks\cms\modules\admin\controllers\AdminController) : ?>

                        <a href="#sx-permissions-for-controller" class="sx-fancybox">
                            <i class="glyphicon glyphicon-exclamation-sign" data-sx-widget="tooltip-b" data-original-title="Настройки доступа к этому разделу" style="color: white;"></i>
                        </a>

                        <div style="display: none;">
                            <div id="sx-permissions-for-controller" style="min-height: 300px;">

                                <?
                                $adminPermission = \Yii::$app->authManager->getPermission(\skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS);
                                $items = [];
                                foreach (\Yii::$app->authManager->getRoles() as $role)
                                {
                                    if (\Yii::$app->authManager->hasChild($role, $adminPermission))
                                    {
                                        $items[] = $role;
                                    }
                                }
                                ?>
                                <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
                                    'permissionName'        => \Yii::$app->controller->permissionName,
                                    'permissionDescription' => "Администрирование | " . \Yii::$app->controller->name,
                                    'label'                 => "Настройки доступа к разделу: " . \Yii::$app->controller->name,
                                    'items'                 => \yii\helpers\ArrayHelper::map($items, 'name', 'description'),
                                ]); ?>
                                Укажите пользователи каких групп получат доступ.
                                <hr />
                                <? \yii\bootstrap\Alert::begin([
                                    'options' => [
                                      'class' => 'alert-info',
                                    ],
                                ])?>
                                    <p>Код привилегии: <b><?= \Yii::$app->controller->permissionName; ?></b></p>
                                    <p>В списке показаны только те группы, которые имеют доступ к системе администрирования.</p>
                                <? \yii\bootstrap\Alert::end()?>
                            </div>
                        </div>

                    <? endif; ?>

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
