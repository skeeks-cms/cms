<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $form yii\widgets\ActiveForm */
?>


<?php $form = ActiveForm::begin(); ?>

Пересчитать приоритеты детей<br />
По полю: <?= Html::dropDownList('column', null, ['name' => 'Название', 'created_at' => 'Дата добавления', 'updated_at' => 'Дата обновления']) ?>
<br />
Порядок: <?= Html::dropDownList('sort', null, ['desc' => 'По убыванию', 'asc' => 'По возрастанию']) ?>
<br />
<?= Html::submitButton('Пересчитать', ['class' => 'btn btn-xs btn-primary', 'name' => 'recalculate_children_priorities', 'value' => '1']) ?>
<br /><br />

<?php ActiveForm::end(); ?>