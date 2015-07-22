<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
return [
    [
        'class' => \skeeks\cms\grid\ImageColumn::className(),
    ],

    'name',
    ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
    ['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],
    ['class' => \skeeks\cms\grid\PublishedAtColumn::className()],
    [
        'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
        'attribute' => "published_to"
    ],

    ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
    //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

    [
        'class'     => \yii\grid\DataColumn::className(),
        'value'     => function(\skeeks\cms\models\CmsContentElement $model)
        {

            return "<small><a href='{$model->cmsTree->url}' target='_blank' data-pjax='0'>{$model->cmsTree->name}</a></small>";

        },
        'format'    => 'raw',
        'attribute' => 'tree_id'
    ],

    [
        'class'     => \yii\grid\DataColumn::className(),
        'value'     => function(\skeeks\cms\models\CmsContentElement $model)
        {
            $result = [];

            if ($model->cmsContentElementTrees)
            {
                foreach ($model->cmsContentElementTrees as $contentElementTree)
                {
                    $result[] = "<small><a href='{$contentElementTree->tree->url}' target='_blank' data-pjax='0'>{$contentElementTree->tree->name}</a></small>";

                }
            }

            return implode('<br />', $result);

        },
        'format' => 'raw',
        'label' => 'Дополнительные разделы',
    ],

    [
        'class'     => \yii\grid\DataColumn::className(),
        'value'     => function(\skeeks\cms\models\CmsContentElement $model)
        {

            return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->absoluteUrl, [
                'target' => '_blank',
                'title' => 'Посмотреть на сайте (Откроется в новом окне)',
                'data-pjax' => '0',
                'class' => 'btn btn-default btn-sm'
            ]);

        },
        'format' => 'raw'
    ],

    [
        'attribute' => 'active',
        'class' => \skeeks\cms\grid\BooleanColumn::className()
    ]
]
?>


