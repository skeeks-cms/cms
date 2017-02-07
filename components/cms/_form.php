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

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Main')
    ])?>
    <?= $form->field($model, 'appName')->textInput()->hint(''); ?>

    <?= $form->field($model, 'adminEmail')->textInput()->hint('E-Mail администратора сайта. Этот email будет отображаться как отправитель, в отправленных письмах с сайта.'); ?>

    <?= $form->field($model, 'noImageUrl')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\OneImage::className()
    )->hint('Это изображение показывается в тех случаях, когда не найдено основное.'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Языковые настройки'); ?>
    <?= $form->fieldSelect($model, 'languageCode', \yii\helpers\ArrayHelper::map(
        \skeeks\cms\models\CmsLang::find()->active()->all(),
        'code',
        'name'
    )); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Безопасность'); ?>
    <?= $form->fieldInputInt($model, 'passwordResetTokenExpire')->hint('Другими словами, ссылки на восстановление пароля перестанут работать через указанное время'); ?>
<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Авторизация'); ?>
    <?= $form->fieldSelectMulti($model, 'registerRoles',
        \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description')
    )->hint('Так же после созданию пользователя, ему будут назначены, выбранные группы.'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Разделы'); ?>
    <?= $form->field($model, 'tree_max_code_length'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Элементы'); ?>
    <?= $form->field($model, 'element_max_code_length'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Доступ'); ?>

     <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-warning',
      ],
    ]); ?>
    <b>Внимание!</b> Права доступа сохраняются в режиме реального времени. Так же эти настройки не зависят от сайта или пользователя.
    <? \yii\bootstrap\Alert::end()?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => "Файлы"
    ])?>

    <?= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_USER_FILES,
        'label'                 => 'Доступ к личным файлам',
    ]); ?>

    <?= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_COMMON_PUBLIC_FILES,
        'label'                 => 'Доступ к общим файлам',
    ]); ?>


    <?= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
        'permissionName'        => \skeeks\cms\rbac\CmsManager::PERMISSION_ELFINDER_ADDITIONAL_FILES,
        'label'                 => 'Доступ ко всем файлам',
    ]); ?>


<?= $form->fieldSetEnd(); ?>



