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
?>
<?php $form = ActiveForm::begin(); ?>


<?= $form->fieldSet('Основное'); ?>
    <?= $form->field($model, 'appName')->textInput()->hint(''); ?>
    <?= $form->field($model, 'adminEmail')->textInput()->hint('E-Mail администратора сайта (отправитель по умолчанию).'); ?>
    <?= $form->field($model, 'notifyAdminEmails')->textInput()->hint('E-Mail адрес или список адресов через запятую на который будут дублироваться все исходящие сообщения.'); ?>

    <?= $form->field($model, 'noImageUrl')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\OneImage::className()
    )->hint('Это изображение показывается в тех случаях, когда не найдено основное.'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Агенты'); ?>
    <?= $form->fieldRadioListBoolean($model, 'enabledHitAgents')->hint('Если вы отключаете выполнение агентов на хитах, то не забудте включить их в задание cron'); ?>
    <?= $form->fieldInputInt($model, 'hitAgentsInterval')->hint('Если агенты выполняются на хитах, то их выполнение будет осуществляться с заданным переидом (сек.)'); ?>
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
    <?= $form->fieldSelect($model, 'sessionType', [
        \skeeks\cms\components\Cms::SESSION_FILE => 'В файлах',
        \skeeks\cms\components\Cms::SESSION_DB => 'В базе данных',
    ])->hint('Хранилище сессий'); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


