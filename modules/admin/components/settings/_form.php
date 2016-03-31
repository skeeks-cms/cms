<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
?>

<?= $form->fieldSet(\Yii::t('app','Main')); ?>
    <?= $form->fieldRadioListBoolean($model, 'enableCustomConfirm') ?>
    <?= $form->fieldRadioListBoolean($model, 'enableCustomPromt') ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Language settings')); ?>
    <?= $form->fieldSelect($model, 'languageCode', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsLang::find()->active()->all(),
        'code',
        'name'
    )); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Setting tables')); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledPjaxPagination', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldInputInt($model, 'pageSize'); ?>
    <?= $form->fieldInputInt($model, 'pageSizeLimitMin'); ?>
    <?= $form->fieldInputInt($model, 'pageSizeLimitMax'); ?>
    <?= $form->field($model, 'pageParamName')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Setting the visual editor')); ?>
    <?= $form->fieldSelect($model, 'ckeditorPreset', \skeeks\yii2\ckeditor\CKEditorPresets::allowPresets()); ?>
    <?= $form->fieldSelect($model, 'ckeditorSkin', \skeeks\yii2\ckeditor\CKEditorPresets::skins()); ?>
    <?= $form->fieldInputInt($model, 'ckeditorHeight'); ?>
    <?= $form->fieldRadioListBoolean($model, 'ckeditorCodeSnippetGeshi')->hint(\Yii::t('app','It will be activated this plugin') . ' http://ckeditor.com/addon/codesnippetgeshi'); ?>
    <?= $form->fieldSelect($model, 'ckeditorCodeSnippetTheme', [
        'monokai_sublime' => 'monokai_sublime',
        'default' => 'default',
        'arta' => 'arta',
        'ascetic' => 'ascetic',
        'atelier-dune.dark' => 'atelier-dune.dark',
        'atelier-dune.light' => 'atelier-dune.light',
        'atelier-forest.dark' => 'atelier-forest.dark',
        'atelier-forest.light' => 'atelier-forest.light',
        'atelier-heath.dark' => 'atelier-heath.dark',
        'atelier-heath.light' => 'atelier-heath.light',
        'atelier-lakeside.dark' => 'atelier-lakeside.dark',
        'atelier-lakeside.light' => 'atelier-lakeside.light',
    ])->hint('https://highlightjs.org/static/demo/ - ' . \Yii::t('app','topics')); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('app','Security')); ?>
    <?= $form->fieldInputInt($model, 'blockedTime')->hint(\Yii::t('app','If a user, for a specified time, not active in the admin panel, it will be prompted for a password.')); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet(\Yii::t('app','Access')); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => 'Основное']); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
        'label'                 => \Yii::t('app','Access to the administrate area'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_EDIT_VIEW_FILES,
        'label'                 => \Yii::t('app','The ability to edit view files'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => "cms/admin-settings",
        'label'                 => \Yii::t('app','The ability to edit settings'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_DASHBOARDS_EDIT,
        'label'                 => \Yii::t('app','Access to edit dashboards'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_USER_FULL_EDIT,
        'label'                 => \Yii::t('app','The ability to manage user groups'),
    ]); ?>


    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('app',"Control recodrs")]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_CREATE,
        'label'                 => \Yii::t('app','The ability to manage user groups'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE,
        'label'                 => \Yii::t('app','The ability to update records'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED,
        'label'                 => \Yii::t('app','The ability to update service information at records'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE,
        'label'                 => \Yii::t('app','Ability to delete records'),
    ]); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget(['content' => \Yii::t('app','Control only own records')]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_OWN,
        'label'                 => \Yii::t('app','The ability to update their records'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_UPDATE_ADVANCED_OWN,
        'label'                 => \Yii::t('app','The ability to update service information at records'),
    ]); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ALLOW_MODEL_DELETE_OWN,
        'label'                 => \Yii::t('app','Ability to delete own records'),
    ]); ?>


<?= $form->fieldSetEnd(); ?>




