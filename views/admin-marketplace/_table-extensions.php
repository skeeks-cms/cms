<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
/* @var $this yii\web\View */
/* @var $models \skeeks\cms\models\CmsExtension[] */
/* @var $message string */
use \skeeks\cms\components\marketplace\models\PackageModel;
use \skeeks\cms\models\CmsExtension;

$self = $this;
?>

<? if ($message) : ?>
    <?
        \yii\bootstrap\Alert::begin([
            'options' => [
              'class' => 'alert-info',
          ]
        ]);
    ?>
        <?= $message; ?>
    <? \yii\bootstrap\Alert::end(); ?>

<? endif; ?>
<? if ($models) :  ?>
    <?= \skeeks\cms\modules\admin\widgets\GridView::widget([
        'dataProvider' => (new \yii\data\ArrayDataProvider([
            'allModels' => $models,
            'pagination' => [
                'defaultPageSize' => 200
            ]
        ])),
        //'layout' => "{summary}\n{items}\n{pager}",
        'columns' =>
        [
            [
                'class' => \yii\grid\DataColumn::className(),
                'value' => function(CmsExtension $model) use ($self)
                {
                    return $self->render('_image-column', [
                        'model' => $model
                    ]);

                },
                'format' => 'raw'
            ],

            [
                'class' => \yii\grid\DataColumn::className(),
                'value' => function(CmsExtension $model) use ($self)
                {
                    return $model->version;
                },

                'format' => 'raw',
                'attribute' => 'version'
            ],

            [
                'class' => \yii\grid\DataColumn::className(),
                'value' => function(CmsExtension $model) use ($self)
                {

                    if ($model->canDelete())
                    {
                        return \yii\helpers\Html::a('<i class="glyphicon glyphicon-remove"></i> ' . \Yii::t('app','Delete'), $model->controllUrl, [
                            'class' => 'btn btn-danger btn-xs',
                            'data-pjax' => '0'
                        ]);
                    } else
                    {
                        return " — ";
                    }
                },

                'format' => 'raw',
                'label' => \Yii::t('app','Possible actions')
            ],
        ]
    ])?>
<? endif; ?>
