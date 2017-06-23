<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<?= $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>
    <?= $form->fieldRadioListBoolean($model, 'active'); ?>
    <?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>



    <? if ($contentModel->root_tree_id) : ?>
        <? $rootTreeModels = \skeeks\cms\models\CmsTree::findAll($contentModel->root_tree_id); ?>
    <? else : ?>
        <? $rootTreeModels = \skeeks\cms\models\CmsTree::findRoots()->joinWith('cmsSiteRelation')->orderBy([\skeeks\cms\models\CmsSite::tableName() . ".priority" => SORT_ASC])->all(); ?>
    <? endif; ?>

    <? if ($contentModel->is_allow_change_tree == \skeeks\cms\components\Cms::BOOL_Y) : ?>
        <? if ($rootTreeModels) : ?>
            <div class="row">
                <div class="col-lg-8 col-md-12 col-sm-12">
                    <?= $form->field($model, 'tree_id')->widget(
                        \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                        [
                            'options' => [
                                'data-form-reload' => 'true'
                            ],
                            'multiple' => false,
                            'treeWidgetOptions' =>
                            [
                                'models' => $rootTreeModels
                            ]
                        ]
                    ); ?>
                </div>
            </div>
        <? endif; ?>
    <? endif; ?>

<?

$properties = $model->getRelatedProperties()
                ->joinWith('cmsContentProperty2trees as map2trees')
                ->groupBy(\skeeks\cms\models\CmsContentProperty::tableName() . ".id")
;

$treeIds = $model->treeIds;
if ($model->tree_id)
{
    $treeIds[] = $model->tree_id;
}
if ($treeIds)
{
    $properties->andWhere([
        'or',
        ['map2trees.cms_tree_id' => $treeIds],
        ['map2trees.cms_tree_id' => null],
    ]);
} else
{
    $properties->andWhere(['map2trees.cms_tree_id' => null]);
}

$properties = $properties->all();

?>
    <? if ($properties) : ?>
        <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
            'content' => \Yii::t('skeeks/cms', 'Additional properties')
        ]); ?>
        <? foreach ($properties as $property) : ?>
            <?= $property->renderActiveForm($form, $model)?>
        <? endforeach; ?>

    <? else : ?>
        <?/*= \Yii::t('skeeks/cms','Additional properties are not set')*/?>
    <? endif; ?>
<?= $form->fieldSetEnd()?>
