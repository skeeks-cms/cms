<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 21.03.2017
 */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
$controller = $this->context;
$action = $controller->action;

$model->load(\Yii::$app->request->get());
?>
<?php $form = $action->beginActiveForm(); ?>
<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>

<?php if ($model->content_type) : ?>
    <div style="display: none;">
        <?= $form->field($model, 'content_type')->hiddenInput()->label(false); ?>
    </div>
<?php else : ?>
    <?= $form->fieldSelect($model, 'content_type',
        \yii\helpers\ArrayHelper::map(\skeeks\cms\models\CmsContentType::find()->all(), 'code', 'name'));
    ?>
<?php endif; ?>
<?= $form->field($model, 'is_active')->checkbox(); ?>
<?= $form->field($model, 'name')->textInput(); ?>
<?= $form->field($model, 'code')->textInput()
    ->hint(\Yii::t('skeeks/cms',
        'The name of the template to draw the elements of this type will be the same as the name of the code.')); ?>

<?= $form->field($model, 'view_file')->textInput()
    ->hint(\Yii::t('skeeks/cms', 'The path to the template. If not specified, the pattern will be the same code.')); ?>



<?= $form->field($model, 'is_visible')->checkbox(); ?>
<?= $form->field($model, 'is_have_page')->checkbox(); ?>


<?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
    'content' => \Yii::t('skeeks/cms', 'Link to section'),
]); ?>

<?= $form->field($model, 'default_tree_id')->widget(
    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
); ?>

<?= $form->field($model, 'is_allow_change_tree')->checkbox(); ?>


<?= $form->field($model, 'root_tree_id')->widget(
    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
)->hint(\Yii::t('skeeks/cms', 'If it is set to the root partition, the elements can be tied to him and his sub.')); ?>

<?php /*= $form->fieldSelect($model, 'root_tree_id', \skeeks\cms\helpers\TreeOptions::getAllMultiOptions(), [
            'allowDeselect' => true
        ])->hint(\Yii::t('skeeks/cms', 'If it is set to the root partition, the elements can be tied to him and his sub.')); */ ?>

<?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
    'content' => \Yii::t('skeeks/cms', 'Relationship to other content'),
]); ?>

<?= $form->fieldSelect($model, 'parent_content_id', \skeeks\cms\models\CmsContent::getDataForSelect(true,
    function (\yii\db\ActiveQuery $activeQuery) use ($model) {
        if (!$model->isNewRecord) {
            //$activeQuery->andWhere(['!=', 'id', $model->id]);
        }
    }),
    [
        'allowDeselect' => true,
    ]
); ?>

<?= $form->field($model, 'is_parent_content_required')->checkbox(); ?>

<?= $form->fieldSelect($model, 'parent_content_on_delete',
    \skeeks\cms\models\CmsContent::getOnDeleteOptions()); ?>



<?php if ($model->childrenContents) : ?>
    <p><b><?= \Yii::t('skeeks/cms', 'Children content') ?></b></p>
    <?php foreach ($model->childrenContents as $contentChildren) : ?>
        <p><?= \yii\helpers\Html::a($contentChildren->name, \skeeks\cms\helpers\UrlHelper::construct([
                '/cms/admin-cms-content/update',
                'pk' => $contentChildren->id,
            ])->enableAdmin()->toString()) ?></p>
    <?php endforeach; ?>

<?php endif; ?>



<? $fieldSet::end(); ?>


<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Seo')); ?>
<?= $form->field($model, 'meta_title_template')->textarea()->hint("Используйте конструкции вида {=model.name}"); ?>
<?= $form->field($model, 'meta_description_template')->textarea(); ?>
<?= $form->field($model, 'meta_keywords_template')->textarea(); ?>
<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Captions')); ?>
<?= $form->field($model, 'name_one')->textInput(); ?>
<?= $form->field($model, 'name_meny')->textInput(); ?>
<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Additionally')); ?>


<?= $form->field($model, 'is_access_check_element')->checkbox(); ?>

<?= $form->field($model, 'priority'); ?>
<?= $form->field($model, 'is_count_views')->checkbox(); ?>
<?php /*= $form->fieldRadioListBoolean($model, 'index_for_search'); */ ?>

<? $fieldSet::end(); ?>

<?= $form->buttonsStandart($model); ?>
<?php $form::end(); ?>
