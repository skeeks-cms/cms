<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $model Tree */

?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->field($model, 'code')->textInput(['maxlength' => 255])->label('Уникальный код блока') ?>
<?= $form->field($model, 'description')->textarea()->label('Описание')->hint('Внутрненнее описание блока, просто для вашего понимания') ?>
<?= $form->field($model, 'multiValue')->textarea()->label('Значение')->hint('Значение будет сохранено для выбранного сайта и языка (см сверху справа)') ?>
<!--
--><?/*= $form->field($model, 'value')->widget(
    \skeeks\cms\widgets\formInputs\multiLangAndSiteTextarea\multiLangAndSiteTextarea::className(),
    [
        'lang' => \skeeks\cms\App::moduleAdmin()->getCurrentLang(),
        'site' => \skeeks\cms\App::moduleAdmin()->getCurrentSite(),
    ]
);
*/?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>

<div class="well well-sm">
    <h2>Немного информации</h2>
    <p>Статические блоки предназначены для быстрого вывода информации на сайт.</p>
    <p>Можете использовать подобную конструкцию в любом коде, для того чтобы получить данные блока</p>
    <code>
        \Yii::$app->cms->widgetStaticBlock("уникальный код блока")
    </code>
    <p></p>
    <!--<p>Для текущего блока:</p>
    <p></p>
    <code>
        \Yii::$app->cms->widgetStaticBlock("уникальный код блока")
    </code>-->
</div>
