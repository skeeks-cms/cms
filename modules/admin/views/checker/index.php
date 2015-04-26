<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
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
                        'attribute' => "name",
                        'label'     => ''
                    ],

                    [
                        'attribute' => "name",
                        'label'     => ''
                    ]
                ]
            ])?>
        <? endif; ?>

    <? endforeach; ?>
</div>




