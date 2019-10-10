<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
$controller = $this->context;
$action = $controller->action;
\skeeks\cms\themes\unify\admin\assets\UnifyAdminIframeAsset::register($this);
?>
<?php $form = $action->beginActiveForm([
    'id' => 'sx-dynamic-form',
    'enableAjaxValidation' => false,
    'enableClientValidation' => false,
]); ?>

<?php $this->registerJs(<<<JS

(function(sx, $, _)
{
    sx.classes.DynamicForm = sx.classes.Component.extend({

        _onDomReady: function()
        {
            var self = this;

            $("[data-form-reload=true]").on('change', function()
            {
                self.update();
            });
        },

        update: function()
        {
            _.delay(function()
            {
                var jForm = $("#sx-dynamic-form");
                jForm.append($('<input>', {'type': 'hidden', 'name' : 'sx-not-submit', 'value': 'true'}));
                jForm.submit();
            }, 200);
        }
    });

    sx.DynamicForm = new sx.classes.DynamicForm();
})(sx, sx.$, sx._);


JS
); ?>


<? if ($is_saved && @$is_create) : ?>
    <?php $this->registerJs(<<<JS
    sx.Window.openerWidgetTriggerEvent('model-create', {
        'submitBtn' : '{$submitBtn}'
    });
JS
    ); ?>

<? elseif ($is_saved) : ?>
    <?php $this->registerJs(<<<JS
sx.Window.openerWidgetTriggerEvent('model-update', {
        'submitBtn' : '{$submitBtn}'
    });
JS
    ); ?>
<? endif; ?>

<? if (@$redirect) : ?>
    <?php $this->registerJs(<<<JS
window.location.href = '{$redirect}';
console.log('window.location.href');
console.log('{$redirect}');
JS
    ); ?>
<? endif; ?>

<?php echo $form->errorSummary([$model, $model->relatedPropertiesModel]); ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>



<?= $form->fieldRadioListBoolean($model, 'active'); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'name_hidden')->textInput(['maxlength' => 255])
                ->hint(\Yii::t('skeeks/cms', 'Not displayed on the site')) ?>
        </div>
    </div>

<?= $form->field($model, 'code')->textInput(['maxlength' => 255])
    ->hint(\Yii::t('skeeks/cms',
        \Yii::t('skeeks/cms', 'This affects the address of the page, be careful when editing.'))); ?>




<?= Html::checkbox("isLink", (bool)($model->redirect || $model->redirect_tree_id), [
    'value' => '1',
    'label' => \Yii::t('skeeks/cms', 'This section is a link'),
    'class' => 'smartCheck',
    'id' => 'isLink',
]); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">
        <?= $form->field($model, 'tree_type_id')->widget(
            \skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                \skeeks\cms\models\CmsTreeType::find()->active()->all(),
                "id",
                "name"
            ),
            'options' =>
                [
                    'data-form-reload' => 'true'
                ]
        ])->label('Тип раздела')->hint(\Yii::t('skeeks/cms',
            'On selected type of partition can depend how it will be displayed.'));
        ?>

        <?= $form->field($model, 'view_file')->textInput()
            ->hint('@app/views/template-name || template-name'); ?>

    </div>

    <div data-listen="isLink" data-show="1" class="sx-hide">
        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
            'content' => \Yii::t('skeeks/cms', 'Redirect')
        ]); ?>
        <?= $form->field($model, 'redirect_code', [])->radioList([
            301 => 'Постоянное перенаправление [301]',
            302 => 'Временное перенаправление [302]'
        ])
            ->label(\Yii::t('skeeks/cms', 'Redirect Code')) ?>
        <div class="row">
            <div class="col-md-5">
                <?= $form->field($model, 'redirect', [])->textInput(['maxlength' => 500])->label(\Yii::t('skeeks/cms',
                    'Redirect'))
                    ->hint(\Yii::t('skeeks/cms',
                        'Specify an absolute or relative URL for redirection, in the free form.')) ?>
            </div>
            <div class="col-md-7">
                <?= $form->field($model, 'redirect_tree_id')->widget(
                    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
                ) ?>
                <?/*= $form->field($model, 'redirect_tree_id')->widget(
                    \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
                    [
                        "attributeSingle" => "redirect_tree_id",
                        "mode" => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_SINGLE
                    ]
                ) */?>
            </div>
        </div>


    </div>


<?php $relatedModel->initAllProperties(); ?>
<?php if ($relatedModel->properties) : ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Additional properties')
    ]); ?>

    <?php foreach ($relatedModel->properties as $property) : ?>
        <?= $property->renderActiveForm($form); ?>
    <?php endforeach; ?>

<?php else
    : ?>
    <?php /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
<?php endif;
?>

<?= $form->fieldSetEnd() ?>



<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Announcement')); ?>

<?= $form->field($model, 'image_id')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => false
    ]
); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">
        <?= $form->field($model, 'description_short')->widget(
            \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
            [
                'modelAttributeSaveType' => 'description_short_type',
            ]);
        ?>

        <?php /*= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'modelAttributeSaveType' => 'description_short_type',
            'ckeditorOptions' => [

                'preset'        => 'full',
                'relatedModel'  => $model,
            ],
            'codemirrorOptions' =>
            [
                'preset'    => 'php',
                'assets'    =>
                [
                    \skeeks\widget\codemirror\CodemirrorAsset::THEME_NIGHT
                ],

                'clientOptions'   =>
                [
                    'theme' => 'night',
                ],
            ]
        ])
        */ ?>

    </div>
<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'In detal')); ?>

<?= $form->field($model, 'image_full_id')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => false
    ]
); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">

        <?= $form->field($model, 'description_full')->widget(
            \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
            [
                'modelAttributeSaveType' => 'description_full_type',
            ]);
        ?>

    </div>
<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms', 'SEO')); ?>
<?= $form->field($model, 'seo_h1'); ?>
<?= $form->field($model, 'meta_title')->textarea(); ?>
<?= $form->field($model, 'meta_description')->textarea(); ?>
<?= $form->field($model, 'meta_keywords')->textarea(); ?>
<?= $form->fieldSetEnd() ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Images/Files')); ?>

<?= $form->field($model, 'imageIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept' => 'image/*',
        'multiple' => true
    ]
); ?>

<?= $form->field($model, 'fileIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'multiple' => true
    ]
); ?>

<?= $form->fieldSetEnd() ?>


<?php if (!$model->isNewRecord) : ?>
    <?php if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-property')[0]) : ?>
        <?
        /**
         * @var \skeeks\cms\backend\BackendAction $actionIndex
         * @var \skeeks\cms\backend\BackendAction $actionCreate
         */
        $actionCreate = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
        $actionIndex = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'index');
        ?>

        <?php if ($actionIndex) : ?>

            <?php if ($contents = \skeeks\cms\models\CmsContent::find()->active()->all()) : ?>

                <?= $form->fieldSet(\Yii::t('skeeks/cms', 'Properties of elements')) ?>


                <?php foreach ($contents as $content) : ?>

                    <h2><?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
                            'content' => $content->name
                        ]); ?></h2>
                    <?php $pjax = \yii\widgets\Pjax::begin(); ?>
                    <?
                    $query = \skeeks\cms\models\CmsContentProperty::find()->orderBy(['priority' => SORT_ASC]);
                    $query->joinWith('cmsContentProperty2contents cmap');
                    $query->joinWith('cmsContentProperty2trees tmap');
                    $query->andWhere([
                        'cmap.cms_content_id' => $content->id,
                    ]);
                    $query->andWhere([
                        'or',
                        ['tmap.cms_tree_id' => $model->id],
                        ['tmap.cms_tree_id' => null],
                    ]);
                    ?>
                    <?
                    if ($actionCreate) {
                        $actionCreate->url = \yii\helpers\ArrayHelper::merge($actionCreate->urlData, [
                            'content_id' => $content->id,
                            'tree_id' => $model->id
                        ]);

                        $actionCreate->name = \Yii::t("skeeks/cms", "Create");

                        echo \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                            'actions' => ['create' => $actionCreate],
                            'clientOptions' => ['pjax-id' => $pjax->id],
                            'isOpenNewWindow' => true,
                            'tag' => 'div',
                            'itemWrapperTag' => 'span',
                            'itemTag' => 'button',
                            'itemOptions' => ['class' => 'btn btn-default'],
                            'options' => ['class' => 'sx-controll-actions'],
                        ]);
                    }
                    ?>
                    <?= \skeeks\cms\modules\admin\widgets\GridViewStandart::widget([
                        'dataProvider' => new \yii\data\ActiveDataProvider([
                            'query' => $query
                        ]),
                        'settingsData' =>
                            [
                                'namespace' => \Yii::$app->controller->uniqueId . "__" . $model->id
                            ],
                        'adminController' => $controllerProperty,
                        'isOpenNewWindow' => true,
                        //'filterModel'       => $searchModel,
                        'autoColumns' => false,
                        'pjax' => $pjax,
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
                                        $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsContents,
                                            'id', 'name');
                                        return implode(', ', $contents);
                                    }
                                ],

                                [
                                    'label' => \Yii::t('skeeks/cms', 'Sections'),
                                    'format' => 'raw',
                                    'value' => function(\skeeks\cms\models\CmsContentProperty $cmsContentProperty) {
                                        if ($cmsContentProperty->cmsTrees) {
                                            $contents = \yii\helpers\ArrayHelper::map($cmsContentProperty->cmsTrees,
                                                'id', function($cmsTree) {
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
                                                    'Only shown in sections') . ':</b><br />' . implode('<br />',
                                                    $contents);
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

                <?php endforeach; ?>




                <?= $form->fieldSetEnd(); ?>


            <?php endif; ?>


        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

        <!--
<?php /*= $form->fieldSet(\Yii::t('skeeks/cms','Additionally')) */ ?>

    <?php /*= $form->field($model, 'tree_menu_ids')->label(\Yii::t('skeeks/cms','Marks'))->widget(
        \skeeks\cms\widgets\formInputs\EditedSelect::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\TreeMenu::find()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true,
            'controllerRoute' => 'cms/admin-tree-menu',
        ]

        )->hint(\Yii::t('skeeks/cms','You can link the current section to a few marks, and according to this, section will be displayed in different menus for example.'));
    */ ?>

--><?php /*= $form->fieldSetEnd() */ ?>


<?
/*$columnsFile = \Yii::getAlias('@skeeks/cms/views/admin-cms-content-element/_columns.php');*/
/**
 * @var $content \skeeks\cms\models\CmsContent
 */
?>
<?php /* if ($contents = \skeeks\cms\models\CmsContent::find()->active()->all()) : */ ?><!--
    <?php /* foreach ($contents as $content) : */ ?>
        <?php /*= $form->fieldSet($content->name) */ ?>


            <?php /*= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
                'label'             => $content->name,
                'hint'              => \Yii::t('skeeks/cms',"Showing all elements of type '{name}' associated with this section. Taken into account only the main binding.",['name' => $content->name]),
                'parentModel'       => $model,
                'relation'          => [
                    'tree_id'       => 'id',
                    'content_id'    => $content->id
                ],

                'sort'              => [
                    'defaultOrder' =>
                    [
                        'priority' => 'published_at'
                    ]
                ],

                'controllerRoute'   => 'cms/admin-cms-content-element',
                'gridViewOptions'   => [
                    'columns' => (array) include $columnsFile
                ],
            ]); */ ?>

        <?php /*= $form->fieldSetEnd() */ ?>
    <?php /* endforeach; */ ?>
--><?php /* endif; */ ?>

<?= $form->buttonsCreateOrUpdate($model); ?>

<?php $this->registerJs(<<<JS
    (function(sx, $, _)
    {
        sx.createNamespace('classes', sx);

        sx.classes.SmartCheck = sx.classes.Component.extend({

            _init: function()
            {},

            _onDomReady: function()
            {
                var self = this;

                this.JsmartCheck = $('.smartCheck');

                self.updateInstance($(this.JsmartCheck));

                this.JsmartCheck.on("change", function()
                {
                    self.updateInstance($(this));
                });
            },

            updateInstance: function(JsmartCheck)
            {
                if (!JsmartCheck instanceof jQuery)
                {
                    throw new Error('1');
                }

                var id  = JsmartCheck.attr('id');
                var val = Number(JsmartCheck.is(":checked"));

                if (!id)
                {
                    return false;
                }

                if (val == 0)
                {
                    $('#tree-redirect').val('');
                    $('#tree-redirect_tree_id').val('');
                }

                $('[data-listen="' + id + '"]').hide();
                $('[data-listen="' + id + '"][data-show="' + val + '"]').show();

            },
        });

        new sx.classes.SmartCheck();
    })(sx, sx.$, sx._);
JS
);
?>
<?php echo $form->errorSummary([$model, $model->relatedPropertiesModel]); ?>
<?php ActiveForm::end(); ?>