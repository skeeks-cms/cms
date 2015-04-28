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

$dataProvider->sort->defaultOrder = ['created_at' => SORT_DESC];
?>
<div id="sx-file-manager" class="<?= $mode; ?>">
    <div class="sx-upload-sources">
        <div class="btn-group">
          <button type="button" id="source-simpleUpload-main" class="btn btn-default source-simpleUpload"><i class="glyphicon glyphicon-download-alt"></i> Загрузить</button>
          <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <span class="caret"></span>
            <span class="sr-only">Toggle Dropdown</span>
          </button>
          <ul class="dropdown-menu" role="menu">
            <li><a href="#" id="source-simpleUpload" ><i class="glyphicon glyphicon-download-alt"></i> Загрузить с компьютера</a></li>
            <li><a href="#" id="source-remoteUpload" class="source-remoteUpload" ><i class="glyphicon glyphicon-globe "></i> Загрузить по ссылке http://</a></li>
            <li><a href="#" onclick="sx.notify.info('Будет реализованно позже'); return false;"><i class="glyphicon glyphicon-folder-open"></i> Добавить из файлового менеджера</a></li>
          </ul>
        </div>

        <div class="sx-progress-bar-file" style="display: none;">
            <span style="vertical-align:middle;">Загрузка файла: <span class="sx-uploaded-file-name"></span></span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

        <div class="sx-progress-bar" style="display: none;">
            <span style="vertical-align:middle;">Загрузка файлов (<span class="sx-uploadedFiles"></span> / <span class="sx-allFiles"></span>)</span>
            <div>
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-success"></div>
                </div>
            </div>
        </div>

        <p></p>
    </div>
    <? if ($groupsObjects = $groups->getComponents()) : ?>
    <div class="sx-select-group">
         <? $form = \skeeks\cms\modules\admin\widgets\ActiveForm::begin([
             'usePjax' => true
         ]); ?>

            <label><i class="glyphicon glyphicon-tags"></i> Метки файлов:</label>
            <div class="row">
                <div class="col-md-6">
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
                </div>
                <div class="col-md-6">
                    <div id="sx-help-info" style="display: none;">
                    Вы можете загружать файлы и привязывать их к определенным меткам.<br />
                    От этого будет зависеть, в каком месте на сайте будет показываться этот файл.
                    <br />
                    <br />
                    Так же вы можете не присваивать файлам никаую метку. Это дополнительная, необязательная опция.
                    <? if ($group): ?>
                        <? $selectedGroup = $groups->getComponent($group);
                        ?>
                        <p>
                            <h3>Требования к файлам с меткой (<?= $selectedGroup->name; ?>):</h3>
                            <br />
                            <? foreach($selectedGroup->config as $key => $val) : ?>
                                <?= \skeeks\cms\models\behaviors\HasFiles::configLabels()[$key]; ?>:
                                    <? if (is_array($val)) : ?>
                                        <?= implode(", ", $val); ?>
                                    <? else:?>
                                        <? if ($key == \skeeks\cms\models\behaviors\HasFiles::MAX_SIZE) : ?>
                                            <?= \Yii::$app->formatter->asShortSize($selectedGroup->getConfigMaxSize()); ?>
                                        <? else: ?>
                                            <?= $val; ?>
                                        <? endif; ?>

                                    <? endif;?>
                                <br />
                            <? endforeach; ?>
                        </p>
                    <? endif; ?>
                    </div>

                    <?= \yii\helpers\Html::a("<i class='glyphicon glyphicon-question-sign'></i>", "#" . $infoId, [
                        'class' => 'btn btn-default',
                        'onclick' => "sx.dialog({'title': 'Справка', 'content': '#sx-help-info'}); return false;"
                    ]); ?>
                </div>
            </div>

        <? \skeeks\cms\modules\admin\widgets\ActiveForm::end(); ?>
    </div>
    <? endif; ?>


    <div class="sx-files-table">
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

                        $smallImage = \Yii::$app->imaging->getImagingUrl($model->src, new \skeeks\cms\components\imaging\filters\Thumbnail());

                        return "<a href='{$model->src}' class='sx-fancybox'>" . \yii\helpers\Html::img($smallImage, [
                            'width' => '50',
                            'class' => 'sx-img-small'
                        ]) . '</a>';
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

                        if ($result)
                        {
                            foreach ($result as $key => $name)
                            {
                                $result[$key] = '<span class="label label-info"><i class="glyphicon glyphicon-tag"></i> ' . $name . '</span>';
                            }
                        }

                        return implode(' ', $result);
                    }
                },
                'format' => 'html',
                'label' => 'Метки'
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

            [
                'attribute' => 'mime_type',
                'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'mime_type', 'mime_type'),
            ],

            [
                'attribute' => 'extension',
                'filter' => \yii\helpers\ArrayHelper::map(\skeeks\cms\models\StorageFile::find()->all(), 'extension', 'extension'),
            ],


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
</div>



<?

$clientOptionsString = \yii\helpers\Json::encode($clientOptions);
\skeeks\cms\modules\admin\assets\ActionFilesAsset::register($this);
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.AdminFileManager = sx.classes.DefaultFileManager.extend({

        _init: function()
        {
            this.set('simpleUpload', _.extend(
                this.get('simpleUpload', {}),
                {
                    'buttons' : [
                        document.getElementById('source-simpleUpload'),
                        document.getElementById('source-simpleUpload-main'),
                    ]
                }

            ));

            this.applyParentMethod(sx.classes.DefaultFileManager, '_init', []); // TODO: make a workaround for magic parent calling

            this.bind('completeUpload', function(e, data)
            {
                if ($('#sx-table-files')[0])
                {
                    $.pjax.reload('#sx-table-files', {});
                }
            });



        },

        _onDomReady: function()
        {
            var self = this;
            this._initSelectForm();

            $(".source-remoteUpload").on('click', function()
            {
                self.SourceRemoteUpload.run();
            });
        },

        /**
        *
        * @returns {sx.classes.FileManager}
        * @private
        */
        _initSelectForm: function()
        {
            var self = this;
            this.JselectType        = $('.sx-select-group', this.getWrapper());
            this.JselectTypeForm    = $('form', this.JselectType);

            this.getWrapper().on("change", ".sx-select-group select", function()
            {
                var data = {'group': $(this).val()};

                self.mergeCommonData(data);

                $(this).closest("form").submit();

                _.delay(function()
                {
                    $.pjax.reload('#sx-table-files', {});
                }, 500);
                return false;
            });

            return this;
        },
    });

    new sx.classes.AdminFileManager('#sx-file-manager', {$clientOptionsString});

})(sx, sx.$, sx._);
JS
);

?>

<?

$this->registerCss(<<<CSS
.sx-onlyUpload .sx-select-group
{
    display: none;
}

.sx-onlyUpload .sx-files-table
{
    /*display: none;*/
}
CSS
);

?>