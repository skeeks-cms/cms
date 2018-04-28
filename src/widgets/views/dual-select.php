<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 24.03.2018
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\DualSelect */
/* @var $element string */
$widget = $this->context;
\yii\jui\Sortable::widget();

$hidden = $widget->items;
$visible = [];
$values = (array) $widget->model->{$widget->attribute};

foreach ($values as $value)
{
    if ($value) {
        $visible[$value] = \yii\helpers\ArrayHelper::getValue($hidden, $value);
        \yii\helpers\ArrayHelper::remove($hidden, $value);
    }

}

\skeeks\cms\widgets\assets\DualSelectAsset::register($this);
\yii\jui\Sortable::widget();

$js = \yii\helpers\Json::encode($widget->jsOptions);
$this->registerJs(<<<JS
(function(sx, $, _)
{
    new sx.classes.DualSelect({$js});
})(sx, sx.$, sx._);
JS
);
?>
<?= \yii\helpers\Html::beginTag('div', $widget->wrapperOptions); ?>
<div style="display: none;"><?= $element; ?></div>
<div class="row">
    <div class="col-sm-4 col-sm-offset-1">
        <?= $widget->renderHtml($widget->hiddenLabel); ?>
        <ul class="sx-sortable-hidden sx-sortable-list cursor-move <?= $widget->id; ?>-conncected">
            <? if ($hidden): ?>
                <? foreach ($hidden as $value => $label) : ?>
                    <?= $widget->renderItem($value, $label); ?>
                <? endforeach; ?>
            <? endif; ?>
        </ul>
    </div>
    <div class="col-sm-2 text-center">
        <div class="sx-dual-select-separator">
            <i class="glyphicon glyphicon-resize-horizontal"></i>
        </div>
    </div>
    <div class="col-sm-4">
        <?= $widget->renderHtml($widget->visibleLabel); ?>
        <ul class="sx-sortable-visible sx-sortable-list cursor-move <?= $widget->id; ?>-conncected">
            <? foreach ($visible as $value => $label) : ?>
                <?= $widget->renderItem($value, $label); ?>
            <? endforeach; ?>
        </ul>
    </div>
</div>
<?= \yii\helpers\Html::endTag('div'); ?>
