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
<?php $form = $action->beginActiveForm(); ?>

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


<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>



<?= $form->field($model, 'active')->checkbox([
    'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
    'value'   => \skeeks\cms\components\Cms::BOOL_Y,
]); ?>
<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'name_hidden')->textInput(['maxlength' => 255])->hint(\Yii::t('skeeks/cms', 'Not displayed on the site')) ?>


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

<? $fieldSet::end(); ?>



<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Announcement'), ['isOpen' => false]); ?>

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
<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'In detal'), ['isOpen' => false]); ?>

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
<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'SEO'), ['isOpen' => false]); ?>
<?= $form->field($model, 'seo_h1'); ?>
<?= $form->field($model, 'meta_title')->textarea(); ?>
<?= $form->field($model, 'meta_description')->textarea(); ?>
<?= $form->field($model, 'meta_keywords')->textarea(); ?>
<? $fieldSet::end(); ?>


<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Images/Files'), ['isOpen' => false]); ?>

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

<? $fieldSet::end(); ?>


<?= $form->buttonsStandart($model); ?>

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
<?php $form::end(); ?>