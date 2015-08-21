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

$templates = [];

foreach (\Yii::$app->cms->templates as $code => $data)
{
    $templates[$code] = \yii\helpers\ArrayHelper::getValue($data, 'name');
}

$emailTemplates = [];

foreach (\Yii::$app->cms->emailTemplates as $code => $data)
{
    $emailTemplates[$code] = \yii\helpers\ArrayHelper::getValue($data, 'name');
}
?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->fieldSet('Основное'); ?>
    <?= $form->field($model, 'appName')->textInput()->hint(''); ?>

    <?= $form->field($model, 'noImageUrl')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\OneImage::className()
    )->hint('Это изображение показывается в тех случаях, когда не найдено основное.'); ?>


<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Агенты'); ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-warning',
      ],
    ]); ?>
    <b>Внимание!</b> По возможности, используйте работу агентов на хитах, это увеличит производительность вашего сайта.
    <? \yii\bootstrap\Alert::end()?>

    <?= $form->fieldRadioListBoolean($model, 'enabledHitAgents')->hint('Если вы отключаете выполнение агентов на хитах, то не забудте включить их в задание cron'); ?>
    <?= $form->fieldInputInt($model, 'hitAgentsInterval')->hint('Если агенты выполняются на хитах, то их выполнение будет осуществляться с заданным переидом (сек.)'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Email'); ?>
    <?= $form->field($model, 'adminEmail')->textInput()->hint('E-Mail администратора сайта. Этот email будет отображаться как отправитель, в отправленных письмах с сайта.'); ?>
    <?= $form->field($model, 'notifyAdminEmailsHidden')->textInput()->hint('E-Mail адрес или список адресов через запятую на который будут дублироваться все исходящие сообщения. Скрытая копия!'); ?>
    <?= $form->field($model, 'notifyAdminEmails')->textInput()->hint('E-Mail адрес или список адресов через запятую на который будут дублироваться все исходящие сообщения. Эти email адреса будут отображаться в открытой копии.'); ?>

    <?= $form->fieldSelect($model, 'emailTemplate', $emailTemplates); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Шаблоны/отображение'); ?>
    <?= $form->fieldSelect($model, 'template', $templates); ?>
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

    <hr />
    <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-warning',
      ],
    ]); ?>
    <b>Внимание!</b> аккуратно используйте эту настройку.
    <? \yii\bootstrap\Alert::end()?>

    <?= $form->fieldRadioListBoolean($model, 'enabledHttpAuth')->hint('Очень осторожно включайте эту настройку! Вы не сможете попасть ни на одну страницу сайта, без логина и пароля указанного ниже.'); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledHttpAuthAdmin'); ?>
    <?= $form->field($model, 'httpAuthLogin')->textInput(); ?>
    <?= $form->field($model, 'httpAuthPassword')->textInput(); ?>

<?= $form->fieldSetEnd(); ?>


<?= $form->fieldSet('Авторизация'); ?>
    <?= $form->fieldSelectMulti($model, 'registerRoles',
        \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description')
    )->hint('Так же после созданию пользователя, ему будут назначены, выбранные группы.'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Отладка'); ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-warning',
      ],
    ]); ?>
    <b>Внимание!</b> Помните, в режиме отладки ваш сайт работает медленнее. Не включайте отладку на рабочих сайтах.
    <? \yii\bootstrap\Alert::end()?>
    <?= $form->fieldRadioListBoolean($model, 'debugEnabled'); ?>
    <?= $form->field($model, 'debugAllowedIPs')->textarea([
        'placeholder' => '80.243.13.242,127.*'
    ])->hint('Укажите ip адреса для которых будет показана отладочная панель дебага через запятую.'); ?>
    <p><b>Ваш ip:</b> <?= \Yii::$app->getRequest()->getUserIP(); ?></p>


<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Разработка'); ?>
    <? \yii\bootstrap\Alert::begin([
        'options' => [
          'class' => 'alert-warning',
      ],
    ]); ?>
    Обратите внимание на эти настройки
    <? \yii\bootstrap\Alert::end()?>
    <?= $form->fieldRadioListBoolean($model, 'giiEnabled'); ?>
    <?= $form->field($model, 'giiAllowedIPs')->textarea([
        'placeholder' => '80.243.13.242,127.*'
    ])->hint('Укажите ip адреса для которых будет включен генератор кода через запятую.'); ?>
    <p><b>Ваш ip:</b> <?= \Yii::$app->getRequest()->getUserIP(); ?></p>


<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


