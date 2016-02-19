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
    <?= $this->render('_header'); ?>
    <? if (!\Yii::$app->user->isGuest): ?>
        <?= $this->render('_admin-menu'); ?>
    <? endif; ?>
        <div class="main">
            <div class="col-lg-12 sx-main-body">
                <div class="panel panel-primary sx-panel sx-panel-content">
                    <div class="panel-heading sx-no-icon">
                        <div class="pull-left">
                            <h2>
                                <?= Breadcrumbs::widget([
                                    'homeLink' => ['label' => \Yii::t("yii", "Home"), 'url' =>
                                        UrlHelper::construct('admin/index')->enableAdmin()->toString()
                                    ],
                                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                                ]) ?>
                            </h2>
                        </div>
                        <div class="panel-actions">

                            <? if (\Yii::$app->user->can('admin/admin-role') && \Yii::$app->controller instanceof \skeeks\cms\modules\admin\controllers\AdminController) : ?>

                                <a href="#sx-permissions-for-controller" class="sx-fancybox">
                                    <i class="glyphicon glyphicon-exclamation-sign" data-sx-widget="tooltip-b" data-original-title="<?=\Yii::t('app','Setting up access to this section')?>" style="color: white;"></i>
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
                                            'permissionDescription' => \Yii::t('app','Administration')." | " . \Yii::$app->controller->name,
                                            'label'                 => \Yii::t('app','Setting up access to the section').": " . \Yii::$app->controller->name,
                                            'items'                 => \yii\helpers\ArrayHelper::map($items, 'name', 'description'),
                                        ]); ?>
                                        <?=\Yii::t('app','Specify which groups of users will have access.')?>
                                        <hr />
                                        <? \yii\bootstrap\Alert::begin([
                                            'options' => [
                                              'class' => 'alert-info',
                                            ],
                                        ])?>
                                            <p><?=\Yii::t('app','Code privileges')?>: <b><?= \Yii::$app->controller->permissionName; ?></b></p>
                                            <p><?=\Yii::t('app','The list displays only those groups that have access to the system administration.')?></p>
                                        <? \yii\bootstrap\Alert::end()?>
                                    </div>
                                </div>

                            <? endif; ?>

                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="panel-content-before">
                            <? if (!UrlHelper::constructCurrent()->getSystem(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_NO_ACTIONS_MODEL)) : ?>
                                <?= \yii\helpers\ArrayHelper::getValue($this->params, 'actions'); ?>
                            <? endif; ?>
                        </div>
                        <div class="panel-content sx-unblock-onWindowReady">
                            <?= \skeeks\cms\modules\admin\widgets\Alert::widget(); ?>
                            <?= $content ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php echo $this->render('_footer'); ?>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
