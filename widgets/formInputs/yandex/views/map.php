<?php
/**
 * map
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 22.01.2015
 * @since 1.0.0
 */
/**
 * @var \skeeks\cms\widgets\formInputs\yandex\Map $widget
 * @var \skeeks\cms\base\db\ActiveRecord $model
 * @var $this yii\web\View
 */

$clientOptionsJson = \yii\helpers\Json::encode($clientOptions);
?>
<div id="<?= $id; ?>" class="sx-widget-yandex-map">
    <?= \yii\helpers\Html::activeHiddenInput($model, $widget->fieldNameLat); ?>
    <?= \yii\helpers\Html::activeHiddenInput($model, $widget->fieldNameLng); ?>
    <label>Координаты и адрес:</label>
    <?= \yii\helpers\Html::activeTextInput($model, $widget->fieldNameAddress, [
        'class' => 'form-control'
    ]); ?>

    <div id="<?= $idMap; ?>" class="sx-yandex-map-container">
    </div>
</div>

<?= $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        new sx.classes.widgets.YandexMap('#{$id}', {$clientOptionsJson});
    })(sx, sx.$, sx._);
JS
)?>