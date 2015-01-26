
<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use \skeeks\sx\validate\Validate;
use \skeeks\cms\validators\HasBehavior;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\Page */
?>


<?php $form = ActiveForm::begin(); ?>


<? if (Validate::validate( new HasBehavior(\yii\behaviors\BlameableBehavior::className()), $model)->isValid()) : ?>
    <?= $form->field($model, 'created_by')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                \Yii::$app->cms->findUser()->all(),
                'id',
                'username'
            ),
        ]);
    ?>

<? endif;?>

<? if (Validate::validate( new HasBehavior(\skeeks\cms\models\behaviors\HasStatus::className()), $model)->isValid()) : ?>
    <?= $form->field($model, 'status')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => $model->getPossibleStatuses(),
        ]);
    ?>
<? endif;?>


<? if (Validate::validate( new HasBehavior(\skeeks\cms\models\behaviors\HasAdultStatus::className()), $model)->isValid()) : ?>
    <?= $form->field($model, 'status_adult')->widget(
        \skeeks\widget\chosen\Chosen::className(), [
            'items' => $model->getPossibleAdultStatuses(),
        ]);
    ?>
<? endif;?>

<? if (Validate::validate( new HasBehavior(\skeeks\cms\models\behaviors\HasSeoPageUrl::className()), $model)->isValid()) : ?>
    Текущий адрес на сайте: <?= Html::a($model->createAbsoluteUrl(), $model->createAbsoluteUrl(), ["target" => "_blank"])?>
    <?= $form->field($model, $model->seoPageNameAttribute)->textInput(['maxlength' => 64]) ?>
<? endif;?>

<? if (Validate::validate( new HasBehavior(\yii\behaviors\TimestampBehavior::className()), $model)->isValid()) : ?>
    <?= $form->field($model, 'created_at')->widget(\kartik\datecontrol\DateControl::classname(), [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
    ]); ?>
<? endif;?>

<? if (Validate::validate( new HasBehavior(\skeeks\cms\models\behaviors\TimestampPublishedBehavior::className()), $model)->isValid()) : ?>
    <?= $form->field($model, 'published_at')->widget(\kartik\datecontrol\DateControl::classname(), [
        //'displayFormat' => 'php:d-M-Y H:i:s',
        'type' => \kartik\datecontrol\DateControl::FORMAT_DATETIME,
    ]); ?>
<? endif;?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php ActiveForm::end(); ?>

