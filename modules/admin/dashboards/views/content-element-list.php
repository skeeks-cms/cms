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

.sx-content-element-list .sx-content-element-list-controlls
{
    padding-top: 0px;
    padding-bottom: 15px;
    padding-left: 15px;
    padding-right: 15px;
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


    <? if (count($widget->content_ids) == 1) : ?>
    <?
        $contentId = array_shift($widget->content_ids);
        /**
         * @var $content \skeeks\cms\models\CmsContent
         */
        $content = \skeeks\cms\models\CmsContent::findOne($contentId)
    ?>
    <div class="col-md-12">
        <div class="sx-content-element-list-controlls">
            <a href="<?= \skeeks\cms\helpers\UrlHelper::construct(['/cms/admin-cms-content-element', 'content_id' => $contentId])?>" data-pjax="0" class="btn btn-primary">
                <i class="glyphicon glyphicon-th-list"></i> <?/*= $content->name_meny */?> Все записи
            </a>

            <a href="<?= \skeeks\cms\helpers\UrlHelper::construct(['/cms/admin-cms-content-element/create', 'content_id' => $contentId])?>" data-pjax="0" class="btn btn-primary">
                <i class="glyphicon glyphicon-plus"></i> <?/*= $content->name_meny */?> Добавить
            </a>
        </div>
    </div>
    <? endif; ?>


</div>




