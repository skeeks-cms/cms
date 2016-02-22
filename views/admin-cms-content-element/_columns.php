<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
return [
    [
        'class' => \skeeks\cms\grid\ImageColumn2::className(),
    ],

    'name',
    ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
    [
        'class' => \skeeks\cms\grid\UpdatedAtColumn::className(),
        'visible' => false
    ],
    [
        'class' => \skeeks\cms\grid\PublishedAtColumn::className(),
        'visible' => false
    ],
    [
        'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
        'attribute' => "published_to",
        'visible' => false
    ],

    ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
    //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

    [
        'class'     => \yii\grid\DataColumn::className(),
        'value'     => function(\skeeks\cms\models\CmsContentElement $model)
        {
            if (!$model->cmsTree)
            {
                return null;
            }

            $path = [];

            if ($model->cmsTree->parents)
            {
                foreach ($model->cmsTree->parents as $parent)
                {
                    if ($parent->isRoot())
                    {
                        $path[] =  "[" . $parent->site->name . "] " . $parent->name;
                    } else
                    {
                        $path[] =  $parent->name;
                    }
                }
            }
            $path = implode(" / ", $path);
            return "<small><a href='{$model->cmsTree->url}' target='_blank' data-pjax='0'>{$path} / {$model->cmsTree->name}</a></small>";
        },
        'format'    => 'raw',
        'filter' => \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(),
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

                    $site = $contentElementTree->tree->root->site;
                    $result[] = "<small><a href='{$contentElementTree->tree->url}' target='_blank' data-pjax='0'>[{$site->name}]/.../{$contentElementTree->tree->name}</a></small>";

                }
            }

            return implode('<br />', $result);

        },
        'format' => 'raw',
        'label' => \Yii::t('app','Additional sections'),
        'visible' => false
    ],

    [
        'attribute' => 'active',
        'class' => \skeeks\cms\grid\BooleanColumn::className()
    ],

    [
        'class'     => \yii\grid\DataColumn::className(),
        'value'     => function(\skeeks\cms\models\CmsContentElement $model)
        {

            return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->absoluteUrl, [
                'target' => '_blank',
                'title' => \Yii::t('app','Watch to site (opens new window)'),
                'data-pjax' => '0',
                'class' => 'btn btn-default btn-sm'
            ]);

        },
        'format' => 'raw'
    ]
]
?>


