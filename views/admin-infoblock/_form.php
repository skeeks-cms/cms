<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */

?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'code')->textInput(['maxlength' => 255])->label('Уникальный код блока') ?>
<?= $form->field($model, 'description')->textarea()->label('Описание') ?>

<?= $form->field($model, 'widget')->label('Виджет')->widget(
    \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \Yii::$app->registeredWidgets->getComponents(),
                 "id",
                 "name"
             ),
    ])->hint('В зависимости от выбранного виджета, будут меняться доступные настройки, в разделе настроек инфоблока');
?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

<div class="well well-sm">
    <h2>Немного информации</h2>
    <p>Инфоблоки — промежуточное звено между виджетами и сайтом. Все виджеты поставляются различными модулями и дописываются в проекте.</p>
    <p>Можете использовать подобную конструкцию в любом коде, для того чтобы отрисовать инфоблок</p>
    <code>
        Yii::$app->cms->widgetInfoblock('Уникальный код инфоблока или его id')
    </code>
    <p></p>
    <!--<p>Для текущего блока:</p>
    <p></p>
    <code>
        \Yii::$app->cms->widgetStaticBlock("уникальный код блока")
    </code>-->
</div>