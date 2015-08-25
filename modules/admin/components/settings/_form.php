<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\WidgetConfig */
?>
<?php $form = ActiveForm::begin(); ?>



<?= $form->fieldSet('Основное'); ?>
    <?= $form->fieldRadioListBoolean($model, 'enableCustomConfirm') ?>
    <?= $form->fieldRadioListBoolean($model, 'enableCustomPromt') ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Языковые настройки'); ?>
    <?= $form->fieldSelect($model, 'languageCode', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsLang::find()->active()->all(),
        'code',
        'name'
    )); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Настройка таблиц'); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledPjaxPagination', \Yii::$app->cms->booleanFormat()); ?>
    <?= $form->fieldInputInt($model, 'pageSize'); ?>
    <?= $form->field($model, 'pageParamName')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Настройка визуального редактора'); ?>
    <?= $form->fieldSelect($model, 'ckeditorPreset', \skeeks\yii2\ckeditor\CKEditorPresets::allowPresets()); ?>
    <?= $form->fieldSelect($model, 'ckeditorSkin', \skeeks\yii2\ckeditor\CKEditorPresets::skins()); ?>
    <?= $form->fieldInputInt($model, 'ckeditorHeight'); ?>
    <?= $form->fieldRadioListBoolean($model, 'ckeditorCodeSnippetGeshi')->hint('Будет задействован этот плагин http://ckeditor.com/addon/codesnippetgeshi'); ?>
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
    ])->hint('https://highlightjs.org/static/demo/ - темы'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Безопасность'); ?>
    <?= $form->fieldInputInt($model, 'blockedTime')->hint('Если пользователь, в течение указанного времени, не проявит активность в админ панели, у него будет запрошен пароль.'); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Доступ'); ?>

    <?= \skeeks\cms\widgets\rbac\PermissionForRoles::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ADMIN_ACCESS,
        'label'                 => 'Доступ к административной части',
    ]); ?>


<?= $form->fieldSetEnd(); ?>


<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


