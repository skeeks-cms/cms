<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;

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

<?php   $this->registerCss(<<<CSS
.sx-hide {
    display: none;
}
CSS
);
?>

<?php echo $form->errorSummary([$model, $model->relatedPropertiesModel]); ?>


<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>

    <div class="d-flex">
        <div>
            <?= $form->field($model, 'active')->checkbox([
                'uncheck' => \skeeks\cms\components\Cms::BOOL_N,
                'value'   => \skeeks\cms\components\Cms::BOOL_Y,
            ]); ?>
            <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>


            <?php if ($model->level > 0) : ?>
                <div data-listen="isLink" data-show="0" class="sx-hide">
                    <?php
                    $this->registerCss(<<<CSS
.sx-link-url .form-group {
    padding: 0px !important;
}
.sx-link-url .form-control {
    border: 0 !important;
    padding: 0 !important;
}
CSS
                    );
                    ?>
                    <div class="form-group sx-link-url">
                        <label class="control-label" for="tree-code">Адрес страницы</label>
                        <div class="d-flex no-fluid">
                            <div class="my-auto">
                                <?php if ($model->parent->level == 0) : ?>
                                    <?php echo $model->parent->url; ?>
                                <?php else: ?>
                                    <?php echo $model->parent->url; ?>/
                                <?php endif; ?>


                            </div>
                            <div style="width: 100%;">
                                <?= $form->field($model, 'code')
                                    ->textInput(['maxlength' => 255])
                                    ->label(false); ?>
                            </div>
                            <!--\Yii::t('skeeks/cms', 'This affects the address of the page, be careful when editing.')-->
                        </div>
                    </div>


                </div>
            <?php endif; ?>



            <?= $form->field($model, 'tree_type_id')->widget(
                \skeeks\cms\widgets\AjaxSelect::class,
                [
                    'dataCallback' => function ($q = '') {

                        $query = \skeeks\cms\models\CmsTreeType::find()
                            ->active();

                        if ($q) {
                            $query->andWhere(['like', 'name', $q]);
                        }

                        $data = $query->limit(100)
                            ->all();

                        $result = [];

                        if ($data) {
                            foreach ($data as $model) {
                                $result[] = [
                                    'id'   => $model->id,
                                    'text' => $model->name,
                                ];
                            }
                        }

                        return $result;
                    },

                    'valueCallback' => function ($value) {
                        return \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsTreeType::find()->where(['id' => $value])->all(), 'id', 'name');
                    },
                    /*'items'   => \yii\helpers\ArrayHelper::map(
                       \skeeks\cms\models\CmsTreeType::find()->active()->all(),
                        "id",
                        "name"
                    ),*/
                    'allowDeselect' => false,
                    'options'       => [
                        'data-form-reload' => 'true',
                    ],
                ])->label('Тип раздела')->hint(\Yii::t('skeeks/cms',
                'On selected type of partition can depend how it will be displayed.'));
            ?>

        </div>
        <div>
            <?= $form->field($model, 'image_id')->widget(
                \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ]
            ); ?>
        </div>
    </div>


    <div class="form-group">
        <?= Html::checkbox("isLink", (bool)($model->redirect || $model->redirect_tree_id), [
            'value' => '1',
            'label' => \Yii::t('skeeks/cms', 'Этот раздел является ссылкой (редирректом)'),
            'class' => 'smartCheck',
            'id'    => 'isLink',
        ]); ?>
    </div>


    <div data-listen="isLink" data-show="1" class="sx-hide">

        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
            'content' => \Yii::t('skeeks/cms', 'Настройки перенаправления (редирректа)'),
        ]); ?>


        <?= $form->field($model, 'redirect_code', [])->radioList([
            301 => 'Постоянное перенаправление [301]',
            302 => 'Временное перенаправление [302]',
        ])
            ->label(\Yii::t('skeeks/cms', 'Redirect Code')) ?>

        <div class="d-flex">
            <div style="min-width: 300px;" class="my-auto">
                <? /*= $form->field($model, 'redirect_tree_id')->widget(
                    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
                ) */ ?>
                <?= $form->field($model, 'redirect_tree_id')->widget(
                    \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                    [
                        'multiple' => false,
                    ]
                )->label("Выбрать раздел")->hint("Выбрать существующий раздел"); ?>

            </div>
            <div class="my-auto">
                или
            </div>

            <div style="width: 100%;">
                <?= $form->field($model, 'redirect', [])->textInput(['maxlength' => 500])->label(\Yii::t('skeeks/cms',
                    'Redirect'))
                    ->label("Ссылка в свободной форме")
                    ->hint(\Yii::t('skeeks/cms',
                        'Specify an absolute or relative URL for redirection, in the free form.')) ?>

            </div>


        </div>


    </div>


<?php $relatedModel->initAllProperties(); ?>
<?php if ($relatedModel->properties) : ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Additional properties'),
    ]); ?>

    <?php foreach ($relatedModel->properties as $property) : ?>
        <?

        if (in_array($property->property_type, [
            \skeeks\cms\relatedProperties\PropertyType::CODE_LIST,
            \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT,
        ])) {

            $property->handler->setAjaxSelectUrl(\yii\helpers\Url::to(['/cms/ajax/autocomplete-tree-eav-options', 'code' => $property->code, 'cms_site_id' => \Yii::$app->skeeks->site->id]));
            $property->handler->setEnumClass(\skeeks\cms\models\CmsTreeTypePropertyEnum::class);
        }
        ?>
        <?= $property->renderActiveForm($form); ?>
    <?php endforeach; ?>

<?php else
    : ?>
    <?php /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
<?php endif;
?>

<? $fieldSet::end(); ?>


<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Announcement'), ['isOpen' => false]); ?>



<?= $form->field($model, 'description_short')->label(false)->widget(
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

<? $fieldSet::end(); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">

        <? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'In detal'), ['isOpen' => false]); ?>

        <?= $form->field($model, 'image_full_id')->widget(
            \skeeks\cms\widgets\AjaxFileUploadWidget::class,
            [
                'accept'   => 'image/*',
                'multiple' => false,
            ]
        ); ?>



        <?= $form->field($model, 'description_full')->widget(
            \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
            [
                'modelAttributeSaveType' => 'description_full_type',
            ]);
        ?>

        <? $fieldSet::end(); ?>
    </div>

    <div data-listen="isLink" data-show="0" class="sx-hide">

        <? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'SEO'), ['isOpen' => false]); ?>
        <?= $form->field($model, 'is_index')->checkbox(); ?>
        <?= $form->field($model, 'seo_h1'); ?>
        <?= $form->field($model, 'meta_title')->textarea(); ?>
        <?= $form->field($model, 'meta_description')->textarea(); ?>
        <?= $form->field($model, 'meta_keywords')->textarea(); ?>

        <div class="form-group">
            <?= Html::checkbox("isCanonical", (bool)($model->isCanonical), [
                'value' => '1',
                'label' => \Yii::t('skeeks/cms', 'Указать атрибут canonical'),
                'class' => 'smartCheck',
                'id'    => 'isCanonical',
            ]); ?>
            <div class="hint-block">Атрибут rel=canonical сообщает поисковой системе, что некоторые страницы сайта являются одинаковыми <a href='https://skeeks.com/atribut-rel-canonical-chto-ehto-i-dlya-chego-isp-501' data-pjax='0' target='_blank'>подробнее</a></div>
        </div>

        <div data-listen="isCanonical" data-show="1" class="sx-hide">

            <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
                'content' => \Yii::t('skeeks/cms', 'Настройки canonical'),
            ]); ?>


            <div class="d-flex">
                <div style="min-width: 300px;">
                    <? /*= $form->field($model, 'redirect_tree_id')->widget(
                    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
                ) */ ?>
                    <?= $form->field($model, 'canonical_tree_id')->widget(
                        \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                        [
                            'multiple' => false,
                        ]
                    )->label("Выбрать раздел"); ?>

                </div>
                <div class="my-auto">
                    или
                </div>

                <div style="width: 100%;">
                    <?= $form->field($model, 'canonical_link', [])->textInput(['maxlength' => 500])->label(\Yii::t('skeeks/cms',
                        'Redirect'))
                        ->label("Ссылка в свободной форме")
                        ->hint(\Yii::t('skeeks/cms',
                            'Укажите абсолютный или относительный адрес ссылки.')) ?>

                </div>


            </div>


        </div>

        <? $fieldSet::end(); ?>
    </div>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Дополнительно'), ['isOpen' => false]); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">


        <?= $form->field($model, 'view_file')->textInput()
            ->hint('@app/views/template-name || template-name'); ?>

    </div>

<?= $form->field($model, 'name_hidden')->textInput(['maxlength' => 255])->hint(\Yii::t('skeeks/cms', 'Not displayed on the site')) ?>

<?= $form->field($model, 'is_adult')->checkbox(); ?>


<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Images/Files'), ['isOpen' => false]); ?>

<?= $form->field($model, 'imageIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'accept'   => 'image/*',
        'multiple' => true,
    ]
); ?>

<?= $form->field($model, 'fileIds')->widget(
    \skeeks\cms\widgets\AjaxFileUploadWidget::class,
    [
        'multiple' => true,
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

                $('.smartCheck').each(function() {
                    var jSmartCheck = $(this);
                    self.updateInstance(jSmartCheck);
    
                    jSmartCheck.on("change", function()
                    {
                        self.updateInstance($(this));
                    });
                    
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
                console.log(id);
                if (!id)
                {
                    return false;
                }

                if (val == 0)
                {
                    if (id == 'isCanonical') {
                        $('#tree-canonical_link').val('');
                        $('#tree-canonical_tree_id').val('');
                    } else {
                        $('#tree-redirect').val('');
                        $('#tree-redirect_tree_id').val('');
                    }
                    
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