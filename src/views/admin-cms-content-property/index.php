<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 02.06.2015
 */
/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model \skeeks\cms\models\CmsContentElement */
?>
<?php $pjax = \yii\widgets\Pjax::begin(); ?>

<?php echo $this->render('_search', [
    'searchModel' => $searchModel,
    'dataProvider' => $dataProvider,
]); ?>

<?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'autoColumns' => false,
    'pjax' => $pjax,
    'adminController' => $controller,
    'columns' =>
        [
            'name',

            [
                'label' => \Yii::t('skeeks/cms', 'Type'),
                'format' => 'raw',
                'value' => function(\skeeks\cms\models\CmsContentProperty $cmsContentProperty) {
                    return $cmsContentProperty->handler->name;
                }
            ],

            [
                'label' => \Yii::t('skeeks/cms', 'Content'),
                'value' => function(\skeeks\cms\models\CmsContentProperty $cmsContentProperty) {
                    $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsContents, 'id', 'name');
                    return implode(', ', $contents);
                }
            ],

            [
                'label' => \Yii::t('skeeks/cms', 'Sections'),
                'format' => 'raw',
                'value' => function(\skeeks\cms\models\CmsContentProperty $cmsContentProperty) {
                    if ($cmsContentProperty->cmsTrees) {
                        $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsTrees, 'id',
                            function($cmsTree) {
                                $path = [];

                                if ($cmsTree->parents) {
                                    foreach ($cmsTree->parents as $parent) {
                                        if ($parent->isRoot()) {
                                            $path[] = "[" . $parent->site->name . "] " . $parent->name;
                                        } else {
                                            $path[] = $parent->name;
                                        }
                                    }
                                }
                                $path = implode(" / ", $path);
                                return "<small><a href='{$cmsTree->url}' target='_blank' data-pjax='0'>{$path} / {$cmsTree->name}</a></small>";

                            });


                        return '<b>' . \Yii::t('skeeks/cms',
                                'Only shown in sections') . ':</b><br />' . implode('<br />', $contents);
                    } else {
                        return '<b>' . \Yii::t('skeeks/cms', 'Always shown') . '</b>';
                    }
                }
            ],

            [
                'class' => \skeeks\cms\grid\BooleanColumn::className(),
                'attribute' => "active"
            ],

            'code',
            'priority',
        ]
]); ?>

<?php \yii\widgets\Pjax::end(); ?>