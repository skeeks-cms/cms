<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.02.2016
 */
/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\modules\admin\dashboards\ContentElementListDashboard */
$this->registerCss(<<<CSS
.sx-content-element-list .sx-table-additional
{
    padding: 0 15px;
}
CSS
)
?>

<div class="row sx-content-element-list">
    <div class="col-md-12 col-lg-12">

        <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
            'dataProvider'  => $widget->dataProvider,
            'filterModel'   => $widget->search->loadedModel,
            'columns' => [
                [
                    'class'                 => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                    'controller'            => \Yii::$app->createController('/cms/admin-cms-content-element')[0],
                    'isOpenNewWindow'       => 1
                ],
                [
                    'class' => \skeeks\cms\grid\ImageColumn2::className(),
                ],
                'name',

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
            ],
        ]); ?>

    </div>
</div>


