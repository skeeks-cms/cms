<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
return [

    [
        'class' => \yii\grid\DataColumn::className(),
        'value' => function($model) {
            return \yii\helpers\Html::a('<i class="glyphicon glyphicon-circle-arrow-left"></i> ' . \Yii::t('skeeks/cms',
                    'Choose'), $model->id, [
                'class' => 'btn btn-primary sx-row-action',
                'onclick' => 'sx.SelectCmsElement.submit(' . \yii\helpers\Json::encode(array_merge($model->toArray(), [
                        'url' => $model->url
                    ])) . '); return false;',
                'data-pjax' => 0
            ]);
        },
        'format' => 'raw'
    ],


    [
        'class' => \yii\grid\DataColumn::className(),
        'value' => function(\skeeks\cms\models\CmsContentElement $model) {
            return $model->cmsContent->name;
        },
        'format' => 'raw',
        'attribute' => 'content_id',
        'filter' => \skeeks\cms\models\CmsContent::getDataForSelect()
    ],


    [
        'class' => \skeeks\cms\grid\ImageColumn2::className(),
    ],

    'name',
    ['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
    ///['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],
    ///['class' => \skeeks\cms\grid\PublishedAtColumn::className()],
    /*[
        'class' => \skeeks\cms\grid\DateTimeColumnData::className(),
        'attribute' => "published_to",
    ],*/

    //['class' => \skeeks\cms\grid\CreatedByColumn::className()],
    //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

    [
        'class' => \yii\grid\DataColumn::className(),
        'value' => function(\skeeks\cms\models\CmsContentElement $model) {
            if (!$model->cmsTree) {
                return null;
            }

            $path = [];

            if ($model->cmsTree->parents) {
                foreach ($model->cmsTree->parents as $parent) {
                    if ($parent->isRoot()) {
                        $path[] = "[" . $parent->site->name . "] " . $parent->name;
                    } else {
                        $path[] = $parent->name;
                    }
                }
            }
            $path = implode(" / ", $path);
            return "<small><a href='{$model->cmsTree->url}' target='_blank' data-pjax='0'>{$path} / {$model->cmsTree->name}</a></small>";
        },
        'format' => 'raw',
        'filter' => \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(),
        'attribute' => 'tree_id'
    ],

    [
        'class' => \yii\grid\DataColumn::className(),
        'value' => function(\skeeks\cms\models\CmsContentElement $model) {
            $result = [];

            if ($model->cmsContentElementTrees) {
                foreach ($model->cmsContentElementTrees as $contentElementTree) {

                    $site = $contentElementTree->tree->root->site;
                    $result[] = "<small><a href='{$contentElementTree->tree->url}' target='_blank' data-pjax='0'>[{$site->name}]/.../{$contentElementTree->tree->name}</a></small>";

                }
            }

            return implode('<br />', $result);

        },
        'format' => 'raw',
        'label' => \Yii::t('skeeks/cms', 'Additional sections'),
    ],

    [
        'attribute' => 'active',
        'class' => \skeeks\cms\grid\BooleanColumn::className()
    ],

    [
        'class' => \yii\grid\DataColumn::className(),
        'value' => function(\skeeks\cms\models\CmsContentElement $model) {

            return \yii\helpers\Html::a('<i class="glyphicon glyphicon-arrow-right"></i>', $model->absoluteUrl, [
                'target' => '_blank',
                'title' => \Yii::t('skeeks/cms', 'Watch to site (opens new window)'),
                'data-pjax' => '0',
                'class' => 'btn btn-default btn-sm'
            ]);

        },
        'format' => 'raw'
    ]
]
?>


