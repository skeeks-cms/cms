<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;

/* @var $this yii\web\View */
/* @var $model Tree */
?>

<div class="sx-box sx-p-10 sx-bg-primary" style="margin-bottom: 10px;">
    <div class="row">
        <div class="col-md-12">
            <div class="pull-left">
                <? if ($model->parents) : ?>
                    <? foreach ($model->parents as $tree) : ?>
                        <a href="<?= $tree->url ?>" target="_blank" title="<?=\Yii::t('skeeks/cms','Watch to site (opens new window)')?>">
                            <?= $tree->name ?>
                            <? if ($tree->level == 0) : ?>
                                [<?= $tree->site->name; ?>]
                            <? endif;  ?>
                        </a>
                        /
                    <? endforeach; ?>
                <? endif; ?>
                <a href="<?= $model->url ?>" target="_blank" title="<?=Yii::t('skeeks/cms','Watch to site (opens new window)')?>">
                    <?= $model->name; ?>
                </a>
            </div>
            <div class="pull-right">

            </div>
        </div>
    </div>
</div>

<?php $form = ActiveForm::begin(); ?>



<?= $form->fieldSet(\Yii::t('skeeks/cms','Main')); ?>



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
        ->hint(\Yii::t('skeeks/cms', \Yii::t('skeeks/cms','This affects the address of the page, be careful when editing.'))); ?>




    <?= Html::checkbox("isLink", (bool) ($model->redirect || $model->redirect_tree_id), [
        'value'     => '1',
        'label'     => \Yii::t('skeeks/cms','This section is a link'),
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
            ])->label('Тип раздела')->hint(\Yii::t('skeeks/cms','On selected type of partition can depend how it will be displayed.'));
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
            ->label(\Yii::t('skeeks/cms','Redirect Code')) ?>
        <div class="row">
            <div class="col-md-5">
                <?= $form->field($model, 'redirect', [])->textInput(['maxlength' => 500])->label(\Yii::t('skeeks/cms','Redirect'))
                    ->hint(\Yii::t('skeeks/cms', 'Specify an absolute or relative URL for redirection, in the free form.')) ?>
            </div>
            <div class="col-md-7">
                <?= $form->field($model, 'redirect_tree_id')->widget(
                    \skeeks\cms\widgets\formInputs\selectTree\SelectTree::className(),
                    [
                        "attributeSingle" => "redirect_tree_id",
                        "mode" => \skeeks\cms\widgets\formInputs\selectTree\SelectTree::MOD_SINGLE
                    ]
                ) ?>
            </div>
        </div>


    </div>



    <? if ($model->relatedPropertiesModel->properties) : ?>

        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
            'content' => \Yii::t('skeeks/cms', 'Additional properties')
        ]); ?>

        <? foreach ($model->relatedPropertiesModel->properties as $property) : ?>
            <?= $property->renderActiveForm($form); ?>
        <? endforeach; ?>

    <? else : ?>
        <?/*= \Yii::t('skeeks/cms','Additional properties are not set')*/?>
    <? endif; ?>

<?= $form->fieldSetEnd() ?>



<?= $form->fieldSet(\Yii::t('skeeks/cms','Announcement')); ?>

    <?= $form->field($model, 'image_id')->widget(
        \skeeks\cms\widgets\formInputs\StorageImage::className()
    ); ?>

    <div data-listen="isLink" data-show="0" class="sx-hide">
        <?= $form->field($model, 'description_short')->widget(
            \skeeks\cms\widgets\formInputs\comboText\ComboTextInputWidget::className(),
            [
                'modelAttributeSaveType' => 'description_short_type',
            ]);
        ?>

        <?/*= $form->field($model, 'description_short')->widget(
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
        */?>

    </div>
<?= $form->fieldSetEnd() ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','In detal')); ?>

    <?= $form->field($model, 'image_full_id')->widget(
        \skeeks\cms\widgets\formInputs\StorageImage::className()
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

<?= $form->fieldSet(\Yii::t('skeeks/cms','SEO')); ?>
    <?= $form->field($model, 'meta_title')->textarea(); ?>
    <?= $form->field($model, 'meta_description')->textarea(); ?>
    <?= $form->field($model, 'meta_keywords')->textarea(); ?>
<?= $form->fieldSetEnd() ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms','Images')); ?>

    <?= $form->field($model, 'images')->widget(
        \skeeks\cms\widgets\formInputs\ModelStorageFiles::className()
    ); ?>

<?= $form->fieldSetEnd()?>


<?= $form->fieldSet(\Yii::t('skeeks/cms','Files')); ?>

    <?= $form->field($model, 'files')->widget(
        \skeeks\cms\widgets\formInputs\ModelStorageFiles::className()
    ); ?>

<?= $form->fieldSetEnd()?>

<!--
<?/*= $form->fieldSet(\Yii::t('skeeks/cms','Additionally')) */?>

    <?/*= $form->field($model, 'tree_menu_ids')->label(\Yii::t('skeeks/cms','Marks'))->widget(
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
    */?>

--><?/*= $form->fieldSetEnd() */?>


<?
/*$columnsFile = \Yii::getAlias('@skeeks/cms/views/admin-cms-content-element/_columns.php');*/
/**
 * @var $content \skeeks\cms\models\CmsContent
 */
?>
<?/* if ($contents = \skeeks\cms\models\CmsContent::find()->active()->all()) : */?><!--
    <?/* foreach ($contents as $content) : */?>
        <?/*= $form->fieldSet($content->name) */?>


            <?/*= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
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
            ]); */?>

        <?/*= $form->fieldSetEnd() */?>
    <?/* endforeach; */?>
--><?/* endif; */?>

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
<?php ActiveForm::end(); ?>