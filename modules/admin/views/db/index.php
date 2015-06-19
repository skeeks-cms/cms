<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 08.06.2015
 */
/* @var $this yii\web\View */
/* @var $dbBackupDir \skeeks\sx\Dir */

use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

$db = \Yii::$app->db;
$schema = $db->getSchema();
$schema->refresh();

?>
<?= \yii\helpers\Html::a("<i class=\"glyphicon glyphicon-retweet\"></i> Обновить кэш структуры таблиц", \skeeks\cms\helpers\UrlHelper::construct('admin/db/index')->set('act', 'refresh-tables')->enableAdmin(), [
    'class'         => 'btn btn-primary',
    'data-method'   => 'post'
])?>



<p></p>
<? $form = ActiveForm::begin(); ?>

<?= $form->fieldSet('Структура таблиц'); ?>
    <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
        'dataProvider'  => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'name',
            'fullName',

            [
                'class'         => \yii\grid\DataColumn::className(),
                'attribute'     => 'schemaName',
                'label'         => 'Количество колонок',
                'value' => function(yii\db\TableSchema $model)
                {
                    return count($model->columns);
                }
            ],


            [
                'class'         => \yii\grid\DataColumn::className(),
                'attribute'     => 'schemaName',
                'label'         => 'Количество внешних ключей',
                'value' => function(yii\db\TableSchema $model)
                {
                    return count($model->foreignKeys);
                }
            ],
        ],
    ]); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Настройки'); ?>

<?= \skeeks\cms\modules\admin\widgets\GridView::widget([
        'dataProvider'  => new \yii\data\ArrayDataProvider([
            'allModels' =>
            [
                [
                    'name' => 'Кэш структуры таблиц',
                    'value' => $db->enableSchemaCache ? "Y" : "N"
                ],

                [
                    'name' => 'Кэш запросов',
                    'value' => $db->enableSchemaCache ? "Y" : "N"
                ]
            ]
        ]),
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
            [
                'class' => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute' => 'value',
            ]
        ]
    ]);
?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet('Бэкапы'); ?>

    <? \skeeks\cms\modules\admin\widgets\Pjax::begin([
        'id' => 'sx-backups'
    ])?>
        <? $url = \skeeks\cms\helpers\UrlHelper::construct('admin/db/backup')->enableAdmin()->toString(); ?>
        <?= \yii\helpers\Html::a("<i class=\"glyphicon glyphicon-save\"></i> Сделать бэкап", $url, [
            'class'         => 'btn btn-primary',
            'onclick'   => new \yii\web\JsExpression(<<<JS
    new sx.classes.Backup({'backend' : '{$url}'}); return false;
JS
)
        ]); ?>

        <p>Для создания бэкапов базы данных используется утилита mysqldump, и данный инструмент работает только в том случае если эта утилита установлена на вашем сервере.</p>
        <hr />
        <? if ($dbBackupDir->isExist()) : ?>
            <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
                    'dataProvider'  => new \yii\data\ArrayDataProvider([
                        'allModels' => $dbBackupDir->findFiles()
                    ]),
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        [
                            'class'         => \yii\grid\DataColumn::className(),
                            'value' => function(\skeeks\sx\File $model)
                            {
                                return $model->getBaseName();
                            }
                        ],

                        [
                            'class'         => \yii\grid\DataColumn::className(),
                            'value' => function(\skeeks\sx\File $model)
                            {
                                return $model->size()->formatedSize();
                            }
                        ],

                    ]
                ]);
            ?>

            <?
                echo \mihaildev\elfinder\ElFinder::widget([
                    'language'         => \Yii::$app->admin->languageCode,
                    'controller'       => 'cms/elfinder-full', // вставляем название контроллера, по умолчанию равен elfinder
                    //'filter'           => 'image',    // фильтр файлов, можно задать массив фильтров https://github.com/Studio-42/elFinder/wiki/Client-configuration-options#wiki-onlyMimes
                    'callbackFunction' => new \yii\web\JsExpression('function(file, id){}'), // id - id виджета
                    'frameOptions' => [
                        'style' => 'width: 100%; height: 800px;'
                    ]
                ]);
            ?>
        <? else: ?>
            <p>Дирриктория с файлами бэкапов базы данных не найдена.</p>
        <? endif; ?>

    <? \skeeks\cms\modules\admin\widgets\Pjax::end() ?>

<?
    $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.classes.Backup = sx.classes.Component.extend({

            _init: function()
            {
                var ajax = sx.ajax.preparePostQuery(this.get("backend"));
                var rr = new sx.classes.AjaxHandlerStandartRespose(ajax);
                rr.bind('error', function(e, data)
                {
                    $.pjax.reload('#sx-backups', {});
                    return false;
                });

                rr.bind('success', function(e, data)
                {
                    $.pjax.reload('#sx-backups', {});
                    return false;
                });
                ajax.execute();
            },
        });

    })(sx, sx.$, sx._);
JS
)
?>

<?= $form->fieldSetEnd(); ?>

<? ActiveForm::end();; ?>