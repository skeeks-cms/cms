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


<?= \skeeks\cms\widgets\StorageFileManager::widget([
    'id' => "sx-model-uploader",
    'model' => $model,
    'fileGroup' => $group,
    'clientOptions' =>
    [
        'completeUploadFile' => new \yii\web\JsExpression(<<<JS
        function(data)
        {
            $.pjax.reload('#sx-table-files', {});
        }
JS
)
    ],
]); ?>
<br />

<div id="sx-file-manager" class="<?= $mode; ?>">
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

        'columns' => $gridColumns

    ]); ?>
    </div>
</div>



<?

\skeeks\cms\modules\admin\assets\ActionFilesAsset::register($this);
$this->registerJs(<<<JS
(function(sx, $, _)
{
    sx.classes.AdminFileManager = sx.classes.Widget.extend({

        _onDomReady: function()
        {
            var self = this;
            this._initSelectForm();
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

                var ComponentUploader = _.find(sx.components, function(Component, key)
                {
                    if (Component instanceof sx.classes.DefaultFileManager)
                    {
                        if (Component._wrapper == '#sx-file-manager-sx-model-uploader')
                        {
                            return Component;
                        }
                    }
                });

                ComponentUploader.mergeCommonData(data);

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

    new sx.classes.AdminFileManager('#sx-file-manager', {});

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
