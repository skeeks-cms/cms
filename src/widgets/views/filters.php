<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 18.03.2018
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\FiltersWidget */
$widget = $this->context;
$fields = $widget->filtersModel->builderFields();
?>

<?
$activeFormClassName = \yii\helpers\ArrayHelper::getValue($widget->activeForm, 'class', \yii\widgets\ActiveForm::class);
\yii\helpers\ArrayHelper::remove($widget->activeForm, 'class');

$form = $activeFormClassName::begin((array)$widget->activeForm);

echo (new \skeeks\yii2\form\Builder([
    'models'     => $widget->filtersModel->builderModels(),
    'model'      => $widget->filtersModel,
    'activeForm' => $form,
    'fields'     => $fields,
]))->render();

?>
<div class="row sx-form-buttons">
<div class="col-sm-12">
    <div class="col-sm-3""></div>
    <div class="col-sm-6">
        <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-filter"></i> Применить</button>
    </div>
    <div class="col-sm-3">

    </div>
</div>
</div>

<?
$activeFormClassName::end();
?>
