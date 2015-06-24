<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */
use \skeeks\cms\components\marketplace\models\PackageModel;
?>
<? if ($allModels = PackageModel::fetchInstalls()) :  ?>
    <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
        'dataProvider' => new \yii\data\ArrayDataProvider([
            'allModels' => $allModels
        ]),
        'columns' =>
        [
            [
                'class' => \yii\grid\DataColumn::className(),
                'value' => function(PackageModel $model)
                {
                    return \yii\helpers\Html::img($model->image, [
                        'width' => '100'
                    ]);
                },
                'format' => 'raw'
            ],

            'name',
        ]
    ])?>
<? endif; ?>
