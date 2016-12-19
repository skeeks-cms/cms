<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<?= $form->fieldSet(\Yii::t('skeeks/cms','Sections')); ?>
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

    <? if ($rootTreeModels) : ?>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-sm-12">
                <?= $form->field($model, 'treeIds')->widget(
                    \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                    [
                        'multiple' => true,
                        'treeWidgetOptions' =>
                        [
                            'models' => $rootTreeModels
                        ]
                    ]
                ); ?>
            </div>
        </div>
    <? endif; ?>

<?= $form->fieldSetEnd()?>
