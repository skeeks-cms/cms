<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 24.03.2018
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\SortSelect */
/* @var $element string */
$widget = $this->context;

$values = (array) $widget->model->{$widget->attribute};


?>
<?= \yii\helpers\Html::beginTag('div', $widget->wrapperOptions); ?>
<div style="display: none;"><?= $element; ?></div>
<div class="row">
    <? $counter = 0; ?>
    <? foreach ($values as $key => $value) : ?>
        <div class="col-sm-4 col-sm-offset-1">
            <?= \yii\helpers\Html::listBox(\yii\helpers\Html::getInputName($widget->model, $widget->attribute) . "[{$counter}]", $key, $widget->items, [
                'class' => 'form-control',
                'size' => 1
            ]); ?>
        </div>
        <div class="col-sm-4 col-sm-offset-1">
            <?= \yii\helpers\Html::listBox(\yii\helpers\Html::getInputName($widget->model, $widget->attribute) . "[{$counter}]", $value, [
                SORT_ASC => "asc",
                SORT_DESC => "desc",
            ], [
                'class' => 'form-control',
                'size' => 1
            ]); ?>
        </div>
        <? $counter ++; ?>
    <? endforeach; ?>
</div>
<?= \yii\helpers\Html::endTag('div'); ?>
