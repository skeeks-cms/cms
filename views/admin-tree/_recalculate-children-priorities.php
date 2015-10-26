<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

<?=\Yii::t('app','Recalculate the priorities of childs')?><br />
По полю: <?= Html::dropDownList('column', null, ['name' => \Yii::t('app','Name'), 'created_at' => \Yii::t('app','Created At'), 'updated_at' => \Yii::t('app','Updated At')]) ?>
<br />
Порядок: <?= Html::dropDownList('sort', null, ['desc' => \Yii::t('app','Descending'), 'asc' => \Yii::t('app','Ascending')]) ?>
<br />
<?= Html::submitButton(\Yii::t('app','Recalculate'), ['class' => 'btn btn-xs btn-primary', 'name' => 'recalculate_children_priorities', 'value' => '1']) ?>
<br /><br />

<?php ActiveForm::end(); ?>