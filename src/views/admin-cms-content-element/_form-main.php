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
    ->groupBy(\skeeks\cms\models\CmsContentProperty::tableName() . ".id");

$treeIds = $model->treeIds;
if ($model->tree_id) {
    $treeIds[] = $model->tree_id;
}
if ($treeIds) {
    $properties->andWhere([
        'or',
        ['map2trees.cms_tree_id' => $treeIds],
        ['map2trees.cms_tree_id' => null],
    ]);
} else {
    $properties->andWhere(['map2trees.cms_tree_id' => null]);
}

$properties = $properties->orderBy(['priority' => SORT_ASC])->all();
/**
 * @var $property \skeeks\cms\relatedProperties\models\RelatedPropertyModel
 */
?>
<? if ($properties) : ?>
    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Additional properties')
    ]); ?>

    <? foreach ($properties as $property) : ?>
        <? if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) : ?>

            <? $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>
            <div class="row">
                <div class="col-md-8">
                    <?= $property->renderActiveForm($form, $model) ?>
                </div>
                <div class="col-md-4">

                    <? if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-property-enum')[0]) : ?>
                        <label>&nbsp;</label>
                        <?
                        /**
                         * @var \skeeks\cms\backend\BackendAction $actionIndex
                         * @var \skeeks\cms\backend\BackendAction $actionCreate
                         */
                        $actionCreate = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
                        ?>

                        <?
                        if ($actionCreate) {
                            $actionCreate->url = \yii\helpers\ArrayHelper::merge($actionCreate->urlData, [
                                'property_id' => $property->id
                            ]);

                            $actionCreate->name = \Yii::t("skeeks/cms", "Create");

                            /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                'actions' => ['create' => $actionCreate],
                                'isOpenNewWindow' => true
                            ]);*/

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
                    <? endif; ?>
                    <!--<a href="#" style="border-bottom: 1px dashed">Добавить</a>-->
                </div>
            </div>
            <? \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>
        <? elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT): ?>

            <? $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>
            <div class="row">
                <div class="col-md-8">
                    <?= $property->renderActiveForm($form, $model) ?>
                </div>
                <div class="col-md-4">
                    <? if (!in_array($property->handler->fieldElement, [
                        \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement::FIELD_ELEMENT_SELECT_DIALOG,
                        \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement::FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE
                    ])) : ?>
                        <? if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-element')[0]) : ?>
                            <label>&nbsp;</label>
                            <?
                            /**
                             * @var \skeeks\cms\backend\BackendAction $actionIndex
                             * @var \skeeks\cms\backend\BackendAction $actionCreate
                             */
                            $actionCreate = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
                            ?>

                            <?
                            if ($actionCreate) {
                                $actionCreate->url = \yii\helpers\ArrayHelper::merge($actionCreate->urlData, [
                                    'content_id' => $property->handler->content_id
                                ]);

                                $actionCreate->name = \Yii::t("skeeks/cms", "Create");

                                /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                    'actions' => ['create' => $actionCreate],
                                    'isOpenNewWindow' => true
                                ]);*/

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
                        <? endif; ?>
                    <? endif; ?>
                    <!--<a href="#" style="border-bottom: 1px dashed">Добавить</a>-->
                </div>
            </div>
            <? \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>

        <? else: ?>
            <?= $property->renderActiveForm($form, $model) ?>
        <? endif; ?>
    <? endforeach; ?>

<? else : ?>
    <? /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
<? endif; ?>
<?= $form->fieldSetEnd() ?>
