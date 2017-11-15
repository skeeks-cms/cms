<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?= \Yii::t('skeeks/cms', 'Recalculate the priorities of childs') ?><br/>
    По полю: <?= Html::dropDownList('column', null, [
    'name' => \Yii::t('skeeks/cms', 'Name'),
    'created_at' => \Yii::t('skeeks/cms', 'Created At'),
    'updated_at' => \Yii::t('skeeks/cms', 'Updated At')
]) ?>
    <br/>
    Порядок: <?= Html::dropDownList('sort', null,
    ['desc' => \Yii::t('skeeks/cms', 'Descending'), 'asc' => \Yii::t('skeeks/cms', 'Ascending')]) ?>
    <br/>
<?= Html::submitButton(\Yii::t('skeeks/cms', 'Recalculate'),
    ['class' => 'btn btn-xs btn-primary', 'name' => 'recalculate_children_priorities', 'value' => '1']) ?>
    <br/><br/>

<?php ActiveForm::end(); ?>