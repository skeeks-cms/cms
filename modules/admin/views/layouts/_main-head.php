<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 12.04.2016
 */
?>

<div class="col-md-12 sx-empty-hide">

    <div class="row sx-main-head sx-bg-glass sx-bg-glass-hover">
        <div class="col-md-6 pull-left">
            <?= \yii\widgets\Breadcrumbs::widget([
                'homeLink' => ['label' => \Yii::t("yii", "Home"), 'url' =>
                    \skeeks\cms\helpers\UrlHelper::construct('admin/index')->enableAdmin()->toString()
                ],
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
        </div>
        <div class="col-md-6">
            <div class="pull-right">

                <? if (\Yii::$app->user->can('admin/admin-role') && \Yii::$app->controller instanceof \skeeks\cms\modules\admin\controllers\AdminController) : ?>

                    <a href="#sx-permissions-for-controller" class="btn btn-default btn-primary sx-fancybox">
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
    </div>
</div>
