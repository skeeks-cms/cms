<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $loadedComponents
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */

?>

<?/* \skeeks\cms\modules\admin\widgets\Pjax::begin([
    'id' => 'widget-select-component'
]) */?>
<form id="selector-component" action="" method="get" data-pjax>
    <label>Компонент или модуль</label>
    <?=
    \skeeks\widget\chosen\Chosen::widget([
        'name' => 'component',
        'items' => $loadedForSelect,
        'value' => $component->className()
    ])
    ?>
</form>


<? if ($component && $component->hasConfigFormFile()) : ?>
    <p>
        <? if ($component->fetchDefaultSettings()) : ?>
            <button type="submit" class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-remove"></i> сбросить настройки по умолчанию</button>
        <? endif; ?>
    </p>
    <?= $component->renderConfigForm(); ?>
<? else: ?>
    <p>Нет доступных настроек</p>
<? endif; ?>

<?
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.SelectorComponent = sx.classes.Component.extend({

        _init: function()
        {},

        _onDomReady: function()
        {
            $("#selector-component select").on('change', function()
            {
                $("#selector-component").submit();
            });
        },

        _onWindowReady: function()
        {}
    });

    new sx.classes.SelectorComponent();
})(sx, sx.$, sx._);
JS
)
?>

<?/* \skeeks\cms\modules\admin\widgets\Pjax::end(); */?>
