<?php
/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
use skeeks\cms\modules\admin\widgets\GridView;

/* @var $model \skeeks\cms\models\Publication */
/* @var $this yii\web\View */
/* @var $searchModel common\models\searchs\Game */
/* @var $dataProvider yii\data\ActiveDataProvider */
$groups = $model->getFilesGroups();
?>
<div id="sx-file-manager">
    <div class="sx-upload-sources">
        <a href="#" id="source-simpleUpload" class="btn btn-primary btn-sm source-simpleUpload">Загрузить с компьютера</a>
        <a href="#" class="btn btn-primary btn-sm">Загрузить по ссылке http://</a>
        <a href="#" class="btn btn-primary btn-sm">Добавить из файлового менеджера</a>
        <div class="sx-progress-bar"></div>
    </div>
    <? if ($groupsObjects = $groups->getComponents()) : ?>
    <div class="sx-select-group">
         <? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
             'usePjax' => true
         ]); ?>
            <label>Группа файлов:</label>

            <?= \skeeks\widget\chosen\Chosen::widget([
                    'name' => 'group',
                    'value' => $group,
                    'items' => \yii\helpers\ArrayHelper::map(
                         $groupsObjects,
                         "id",
                         "name"
                    ),
                ]);
            ?>
            <small>
                Вы можете загружать файлы и привязывать их к определенным группам.<br />
                От этого будет зависеть, в каком месте на сайте будет показываться этот файл.
            </small>
            <? print_r($groups->getComponent($group)->config);die; ?>
        <? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>
    </div>
    <? endif; ?>


    <br />
    <br />

    <?= GridView::widget([

        'dataProvider'  => $dataProvider,
        'filterModel'   => $searchModel,
        'PjaxOptions' =>
        [
            'id' => 'sx-table-files'
        ],

        'columns' => [

            ['class' => 'yii\grid\SerialColumn'],

            [
                'class'         => \skeeks\cms\modules\admin\grid\ActionColumn::className(),
                'controller'    => $controller,
                'isOpenNewWindow'    => true
            ],

            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\StorageFile $model)
                {
                    if ($model->isImage())
                    {
                        \Yii::$app->view->registerCss(<<<CSS
    .sx-img-small {
        max-height: 50px;
    }
CSS
);

                        return \yii\helpers\Html::img($model->src, [
                            'width' => '50',
                            'class' => 'sx-img-small'
                        ]);
                    }

                    return \yii\helpers\Html::tag('span', $model->extension, ['class' => 'label label-primary', 'style' => 'font-size: 18px;']);
                },
                'format' => 'html'
            ],



            [
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\StorageFile $file)
                {
                    if ($groups = $file->getFilesGroups())
                    {
                        $result = \yii\helpers\ArrayHelper::map($groups, "id", "name");
                        return implode(', ', $result);
                    }
                },
                'format' => 'html'
            ],

            'name',

            /*[
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\StorageFile $model)
                {
                    return \yii\helpers\Html::tag('pre', $model->src);
                },

                'format' => 'html',
                'attribute' => 'src'
            ],*/

            /*[
                'class'     => \yii\grid\DataColumn::className(),
                'value'     => function(\skeeks\cms\models\StorageFile $model)
                {
                    $model->cluster_id;
                    $cluster = \Yii::$app->storage->getCluster($model->cluster_id);
                    return $cluster->name;
                },

                'format' => 'html',
            ],*/

            //'name_to_save',
            'mime_type',
            'extension',

            [
                'class' => \skeeks\cms\grid\FileSizeColumnData::className(),
                'attribute' => 'size'
            ],

            //['class' => \skeeks\cms\grid\CreatedAtColumn::className()],
            //['class' => \skeeks\cms\grid\UpdatedAtColumn::className()],

           // ['class' => \skeeks\cms\grid\CreatedByColumn::className()],
            //['class' => \skeeks\cms\grid\UpdatedByColumn::className()],

        ],

    ]); ?>
</div>



<?

$clientOptionsString = \yii\helpers\Json::encode($clientOptions);
\skeeks\cms\modules\admin\assets\ActionFilesAsset::register($this);
$this->registerJs(<<<JS
(function(sx, $, _)
{


    sx.FileMangager = new sx.classes.files.Manager('#sx-file-manager', {$clientOptionsString});
})(sx, sx.$, sx._);
JS
);

?>