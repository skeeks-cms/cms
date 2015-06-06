<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
?>


<?php $form = ActiveForm::begin(); ?>



<?= $form->fieldSet('Основное'); ?>

    <?= $form->field($model, 'image')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\StorageImages::className(),
        [
            'fileGroup' => 'image',
        ]
    )->label('Главное изображение'); ?>

    <?= $form->fieldRadioListBoolean($model, 'active'); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => 255])->hint("Этот параметр влияет на адрес страницы, будте внимательно при его редактировании."); ?>

    <?= Html::checkbox("isLink", $model->isLink(), [
        'value'     => '1',
        'label'     => 'Этот раздел является ссылкой',
        'class'     => 'smartCheck',
        'id'        => 'isLink',
    ]); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">
        <?= $form->field($model, 'tree_type_id')->widget(
            \skeeks\widget\chosen\Chosen::className(), [
                    'items' => \yii\helpers\ArrayHelper::map(
                         \skeeks\cms\models\CmsTreeType::find()->active()->all(),
                         "id",
                         "name"
                     ),
            ])->label('Тип раздела')->hint('От выбранного типа раздела может зависеть, то, как она будет отображаться.');
        ?>
    </div>

    <div data-listen="isLink" data-show="1" class="sx-hide">
    <?= $form->field($model, 'redirect', [

    ])->textInput(['maxlength' => 500])->label('Редиррект') ?>
    </div>

<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('Дополнительные разделы') ?>



<?= $form->field($model, 'tree_menu_ids')->label('Метки')->widget(
    \skeeks\cms\widgets\formInputs\EditedSelect::className(), [
        'items' => \yii\helpers\ArrayHelper::map(
             \skeeks\cms\models\TreeMenu::find()->all(),
             "id",
             "name"
         ),
        'multiple' => true,
        'controllerRoute' => 'cms/admin-tree-menu',
    ]
    /*\skeeks\widget\chosen\Chosen::className(), [
            'items' => \yii\helpers\ArrayHelper::map(
                 \skeeks\cms\models\TreeMenu::find()->all(),
                 "id",
                 "name"
             ),
            'multiple' => true
    ]*/
    )->hint('Вы можете привязать текущий раздел к несокльким меткам, и в зависимости от этого раздел будет показываться в разных меню например.');
?>

<!--<div data-listen="isLink" data-show="0" class="sx-hide">

    <?/*= $form->field($model, 'tree_ids')->widget(
        \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
        [
            'mode' => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_MULTI
        ])->label('Дополнительные разделы сайта')->hint('Дополнительные разделы сайта, где бы хотелось видеть этот раздел.');
    */?>

</div>-->



<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('Изображения'); ?>
    <?/*= $form->field($model, 'files')->widget(\skeeks\cms\widgets\formInputs\StorageImages::className())->label(false); */?>
    <?= $form->field($model, 'images')->widget(
        \skeeks\cms\modules\admin\widgets\formInputs\StorageImages::className(),
        [
            'fileGroup' => 'images',
        ]
    )->label('Изображения');; ?>
<?= $form->fieldSetEnd()?>


<?= $form->fieldSet('Анонс'); ?>
    <div data-listen="isLink" data-show="0" class="sx-hide">
        <?/*= $form->field($model, 'description_short')->widget(
            \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className(),
            [
                'options'       => ['rows' => 6],
                'preset'        => 'full',
                'relatedModel'  => $model,
            ])
        */?>

        <?= $form->field($model, 'description_short')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'ckeditorOptions' => [
                'options'       => ['rows' => 20],
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
                'options'   =>
                [
                    'rows' => 20
                ],
                'clientOptions'   =>
                [
                    'theme' => 'night'
                ],
            ]
        ])
    ?>

    </div>
<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('Описание'); ?>

<div data-listen="isLink" data-show="0" class="sx-hide">

    <?/*= $form->field($model, 'description_full')->widget(
        \skeeks\cms\widgets\formInputs\ckeditor\Ckeditor::className(),
        [
            'options'       => ['rows' => 20],
            'preset'        => 'full',
            'relatedModel'  => $model,
        ])
    */?>

    <?= $form->field($model, 'description_full')->widget(
        \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
        [
            'ckeditorOptions' => [
                'options'       => ['rows' => 20],
                'preset'        => 'full',
                'relatedModel'  => $model,
            ],
            'codemirrorOptions' =>
            [
                'preset'    => 'htmlmixed',
                'assets'    =>
                [
                    \skeeks\widget\codemirror\CodemirrorAsset::THEME_NIGHT
                ],
                'options'   =>
                [
                    'rows' => 20
                ],
                'clientOptions'   =>
                [
                    'theme' => 'night'
                ],
            ]
        ])
    ?>

</div>
<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet('SEO'); ?>
    <?= $form->field($model, 'meta_title')->textarea(); ?>
    <?= $form->field($model, 'meta_description')->textarea(); ?>
    <?= $form->field($model, 'meta_keywords')->textarea(); ?>
<?= $form->fieldSetEnd() ?>



<?= $form->buttonsCreateOrUpdate($model); ?>

<? $this->registerJs(<<<JS
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
<?php ActiveForm::end(); ?>