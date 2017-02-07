<?php

use yii\helpers\Html;
use skeeks\cms\modules\admin\widgets\form\ActiveFormUseTab as ActiveForm;
use common\models\User;

/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContent */
?>


<?php $form = ActiveForm::begin(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Main')); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Main')
    ]); ?>

    <? if ($content_type = \Yii::$app->request->get('content_type')) : ?>
        <?= $form->field($model, 'content_type')->hiddenInput(['value' => $content_type])->label(false); ?>
    <? else: ?>
        <div style="display: none;">
            <?= $form->fieldSelect($model, 'content_type', \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsContentType::find()->all(), 'code', 'name')); ?>
        </div>
    <? endif; ?>

    <?= $form->field($model, 'name')->textInput(); ?>
    <?= $form->field($model, 'code')->textInput()
        ->hint(\Yii::t('skeeks/cms', 'The name of the template to draw the elements of this type will be the same as the name of the code.')); ?>

    <?= $form->field($model, 'viewFile')->textInput()
        ->hint(\Yii::t('skeeks/cms', 'The path to the template. If not specified, the pattern will be the same code.')); ?>


    <?= $form->fieldRadioListBoolean($model, 'active'); ?>
    <?= $form->fieldRadioListBoolean($model, 'visible'); ?>


    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Link to section')
    ]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->fieldSelect($model, 'default_tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(), [
                'allowDeselect' => true
            ]); ?>
        </div>
        <div class="col-md-6">
            <?= $form->fieldRadioListBoolean($model, 'is_allow_change_tree'); ?>
        </div>
    </div>


    <?= $form->fieldSelect($model, 'root_tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(), [
        'allowDeselect' => true
    ])->hint(\Yii::t('skeeks/cms', 'If it is set to the root partition, the elements can be tied to him and his sub.')); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Relationship to other content')
    ]); ?>

    <div class="row">
        <div class="col-md-3">
            <?= $form->fieldSelect($model, 'parent_content_id', \skeeks\cms\models\CmsContent::getDataForSelect(true, function(\yii\db\ActiveQuery $activeQuery) use ($model)
                {
                    if (!$model->isNewRecord)
                    {
                        $activeQuery->andWhere(['!=', 'id', $model->id]);
                    }
                }),
                [
                'allowDeselect' => true
                ]
            ); ?>
        </div>
        <div class="col-md-3">
            <?= $form->fieldRadioListBoolean($model, 'parent_content_is_required'); ?>
        </div>
        <div class="col-md-3">
            <?= $form->fieldSelect($model, 'parent_content_on_delete', \skeeks\cms\models\CmsContent::getOnDeleteOptions()); ?>
        </div>
    </div>



    <? if ($model->childrenContents) : ?>
        <p><b><?= \Yii::t('skeeks/cms', 'Children content')?></b></p>
        <? foreach ($model->childrenContents as $contentChildren) : ?>
            <p><?= Html::a($contentChildren->name, \skeeks\cms\helpers\UrlHelper::construct(['/cms/admin-cms-content/update', 'pk' => $contentChildren->id])->enableAdmin()->toString())?></p>
        <? endforeach;  ?>

    <? endif ; ?>


<?= $form->fieldSetEnd(); ?>


<? if (!$model->isNewRecord) : ?>
    <?= $form->fieldSet(\Yii::t('skeeks/cms','Properties')) ?>
        <?= \skeeks\cms\modules\admin\widgets\RelatedModelsGrid::widget([
            'label'             => \Yii::t('skeeks/cms',"Element properties"),
            'hint'              => \Yii::t('skeeks/cms',"Every content on the site has its own set of properties, its sets here"),
            'parentModel'       => $model,
            'relation'          => [
                'content_id' => 'id'
            ],
            'controllerRoute'   => 'cms/admin-cms-content-property',

            'gridViewOptions'   => [
                'sortable' => true,
                'columns' => [
                    [
                        'attribute'     => 'name',
                        'enableSorting' => false
                    ],

                    [
                        'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                        'attribute'     => 'active',
                        'falseValue'    => \skeeks\cms\components\Cms::BOOL_N,
                        'trueValue'     => \skeeks\cms\components\Cms::BOOL_Y,
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'code',
                        'enableSorting' => false
                    ],

                    [
                        'attribute'     => 'priority',
                        'enableSorting' => false
                    ],
                ],
            ],
        ]); ?>
    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet(\Yii::t('skeeks/cms','Access')); ?>
        <? \yii\bootstrap\Alert::begin([
            'options' => [
              'class' => 'alert-warning',
          ],
        ]); ?>
        <b>Внимание!</b> Права доступа сохраняются в режиме реального времени. Так же эти настройки не зависят от сайта или пользователя.
        <? \yii\bootstrap\Alert::end()?>

        <?= \skeeks\cms\rbac\widgets\adminPermissionForRoles\AdminPermissionForRolesWidget::widget([
            'permissionName'        => $model->adminPermissionName,
            'label'                 => 'Доступ в административной части',
        ]); ?>



    <?= $form->fieldSetEnd(); ?>

    <?= $form->fieldSet(\Yii::t('skeeks/cms','Seo')); ?>
        <?= $form->field($model, 'meta_title_template')->textarea()->hint("Используйте конструкции вида {=model.name}"); ?>
        <?= $form->field($model, 'meta_description_template')->textarea(); ?>
        <?= $form->field($model, 'meta_keywords_template')->textarea(); ?>
    <?= $form->fieldSetEnd(); ?>

<? endif; ?>


<?= $form->fieldSet(\Yii::t('skeeks/cms','Captions')); ?>
    <?= $form->field($model, 'name_one')->textInput(); ?>
    <?= $form->field($model, 'name_meny')->textInput(); ?>
<?= $form->fieldSetEnd(); ?>

<?= $form->fieldSet(\Yii::t('skeeks/cms','Additionally')); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Access')
    ]); ?>
    <?= $form->fieldRadioListBoolean($model, 'access_check_element'); ?>

    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Additionally')
    ]); ?>
    <?= $form->fieldInputInt($model, 'priority'); ?>
    <?= $form->fieldRadioListBoolean($model, 'index_for_search'); ?>

<?= $form->fieldSetEnd(); ?>

<?= $form->buttonsCreateOrUpdate($model); ?>
<?php ActiveForm::end(); ?>
