<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
/* @var $this yii\web\View */

$allCkecks = [];
?>
<h2>Полное тестирование системы</h2>
<p>
    Полная проверка системы помогает найти причины проблем в работе сайта и избежать появление ошибок в дальнейшем. Справка по каждому тесту поможет устранить причину ошибки.
</p>
<div class="sx-box sx-p-10 sx-bg-primary">
    <p>
        <a href="#" class="btn btn-primary"><i class="glyphicon glyphicon-play"></i> Начать тестирование</a>
        <a href="#" class="btn btn-default" disabled="disabled"><i class="glyphicon glyphicon-stop"></i> Остановить</a>
    </p>

    <? foreach(\Yii::$app->cms->getModules() as $module) : ?>

        <? if ($checks = $module->loadChecksComponents()) : ?>
            <? $allCkecks = \yii\helpers\ArrayHelper::merge($allCkecks, $checks); ?>
            <hr />
            <h3>Модуль <?= $module->getName(); ?> (проверок: <?= count($checks); ?>)</h3>
            <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
                'dataProvider' => new \yii\data\ArrayDataProvider([
                    'allModels' => $checks
                ]),
                'layout' => "{items}",
                'columns' =>
                [
                    [
                        'attribute' => "name",
                        'label'     => 'Тест'
                    ],

                    [
                        'class'     => \yii\grid\DataColumn::className(),
                        'value'     => function(\skeeks\cms\base\CheckComponent $model)
                        {
                            return '';
                        },
                        'format' => 'html'
                    ],

                    [
                        'class'     => \yii\grid\DataColumn::className(),
                        'value'     => function(\skeeks\cms\base\CheckComponent $model)
                        {
                            $infoId = str_replace("\\", '-', $model->className()) ."-info";
                            $info = \yii\helpers\Html::tag("div", $model->description, [
                                'class' => '',
                                'id'    => $infoId,
                            ]);
                            $infoWrapper = \yii\helpers\Html::tag("div", $info, [
                                'style' => 'display: none;',
                            ]);

                            return \yii\helpers\Html::a("<i class='glyphicon glyphicon-exclamation-sign'></i>", "#" . $infoId, [
                                'class' => 'btn btn-default sx-fancybox'
                            ]) . $infoWrapper;
                        },
                        'format' => 'raw'
                    ],
                ]
            ])?>
        <? endif; ?>

    <? endforeach; ?>
</div>

<?
$allCkecksJson = \yii\helpers\Json::encode(array_keys($allCkecks));

$this->registerJs(<<<JS
(function(sx, $, _)
{



    sx.classes.Checker = sx.classes.Component.extend({
    
        _init: function()
        {},
        
        _onDomReady: function()
        {},
        
        _onWindowReady: function()
        {},

        run: function()
        {
            return this;
        },
    });

    sx.Checker = new sx.classes.Checker({
        'checks' : $allCkecksJson
    });
})(sx, sx.$, sx._);
JS
);
?>




