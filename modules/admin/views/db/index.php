<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 08.11.2014
 * @since 1.0.0
 */

$db = \Yii::$app->db;
$schema = $db->getSchema();
$schema->refresh()
?>
Общая информация:

<p><b>Кэш структуры таблиц:</b> <?= $db->enableSchemaCache ? "да" : "нет" ?></p>
<p><b>Кэш запросов:</b> <?= $db->enableQueryCache ? "да" : "нет" ?></p>


<?= \yii\helpers\Html::a("Обновить кэш структуры таблиц", \skeeks\cms\helpers\UrlHelper::construct('admin/db/index')->set('act', 'refresh-tables')->enableAdmin(), [
    'class'         => 'btn btn-primary',
    'data-method'   => 'post'
])?>

<hr />
<?= \yii\grid\GridView::widget([
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
