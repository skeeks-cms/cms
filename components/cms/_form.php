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
    <?= $form->field($model, 'appName')->textInput()->hint(''); ?>
    <?= $form->field($model, 'adminEmail')->textInput()->hint('E-Mail администратора сайта (отправитель по умолчанию).'); ?>
    <?= $form->field($model, 'notifyAdminEmails')->textInput()->hint('E-Mail адрес или список адресов через запятую на который будут дублироваться все исходящие сообщения.'); ?>
    <?= $form->field($model, 'noImageUrl')->textInput()->hint('Это изображение показывается в тех случаях, когда не найдено основное.'); ?>
    <?= $form->fieldSelect($model, 'template', \yii\helpers\ArrayHelper::map(\Yii::$app->cms->templates, 'code', 'name')); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>


