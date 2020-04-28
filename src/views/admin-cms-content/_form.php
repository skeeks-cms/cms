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

<?= $form->field($model, 'is_active')->checkbox([], false); ?>
<?= $form->field($model, 'name'); ?>
<?= $form->field($model, 'code'); ?>

<?= $form->field($model, 'view_file'); ?>



<?= $form->field($model, 'is_visible')->checkbox([], false); ?>
<?= $form->field($model, 'is_have_page')->checkbox([], false); ?>
<?
$element = new \skeeks\cms\models\CmsContentElement();
$items = [
    'description_short' => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "description_short"),
    'description_full'  => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "description_full"),
    'priority'          => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "priority"),
    'active'            => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "active"),
    'code'              => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "code"),
    'image_id'          => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "image_id"),
    'image_full_id'     => "Главное изображение (из подробного описания)",
    'imageIds'          => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "imageIds"),
    'fileIds'           => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "fileIds"),
    'treeIds'           => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "treeIds"),
    'external_id'       => \yii\helpers\ArrayHelper::getValue($element->attributeLabels(), "external_id"),
    'published_at'      => "Время публикации",
];
echo $form->field($model, 'editable_fields')->widget(
    \skeeks\widget\chosen\Chosen::class, [
        'multiple' => true,
        'items'    => $items,
    ]
); ?>


<?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
    'content' => \Yii::t('skeeks/cms', 'Link to section'),
]); ?>

<?= $form->field($model, 'default_tree_id')->widget(
    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
); ?>

<?= $form->field($model, 'is_allow_change_tree')->checkbox([], false); ?>


<?= $form->field($model, 'root_tree_id')->widget(
    \skeeks\cms\backend\widgets\SelectModelDialogTreeWidget::class
); ?>

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

<?= $form->field($model, 'is_parent_content_required')->checkbox([], false); ?>

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






<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Additionally')); ?>


<?= $form->field($model, 'is_access_check_element')->checkbox([], false); ?>

<?= $form->field($model, 'priority')->widget(
    \skeeks\cms\backend\widgets\forms\NumberInputWidget::class
); ?>
<?= $form->field($model, 'is_count_views')->checkbox([], false); ?>
<?php /*= $form->fieldRadioListBoolean($model, 'index_for_search'); */ ?>

<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Captions'), ['isOpen' => false]); ?>
<?= $form->field($model, 'name_one')->textInput(); ?>
<?= $form->field($model, 'name_meny')->textInput(); ?>
<? $fieldSet::end(); ?>

<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Seo'), ['isOpen' => false]); ?>
<?= $form->field($model, 'meta_title_template')->textarea()->hint("Используйте конструкции вида {=model.name}"); ?>
<?= $form->field($model, 'meta_description_template')->textarea(); ?>
<?= $form->field($model, 'meta_keywords_template')->textarea(); ?>
<? $fieldSet::end(); ?>

<?= $form->buttonsStandart($model); ?>
<?php $form::end(); ?>
