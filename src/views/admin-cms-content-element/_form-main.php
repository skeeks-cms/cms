<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>

<? if ($contentModel->isAllowEdit("active")) : ?>
<div class="row">
    <div class="col" style="<?= !$model->is_active ? "color: red;" : ""; ?>">
        <?= $form->field($model, 'active')->checkbox([
            'uncheck'                                                         => \skeeks\cms\components\Cms::BOOL_N,
            'value'                                                           => \skeeks\cms\components\Cms::BOOL_Y,
            \skeeks\cms\helpers\RequestResponse::DYNAMIC_RELOAD_FIELD_ELEMENT => 'true',
        ]); ?>
    </div>
</div>
<? endif; ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>



<?php if ($contentModel->root_tree_id) : ?>
    <?php $rootTreeModels = \skeeks\cms\models\CmsTree::findAll($contentModel->root_tree_id); ?>
<?php else
    : ?>
    <? if ($model->cms_site_id) : ?>
        <?php $rootTreeModels = \skeeks\cms\models\CmsTree::findRoots()->andWhere(['cms_site_id' => $model->cms_site_id])->joinWith('cmsSiteRelation')->orderBy([\skeeks\cms\models\CmsSite::tableName().".priority" => SORT_ASC])->all();
    ?>
    <? else : ?>
    <?php $rootTreeModels = \skeeks\cms\models\CmsTree::findRootsForSite()->joinWith('cmsSiteRelation')->orderBy([\skeeks\cms\models\CmsSite::tableName().".priority" => SORT_ASC])->all();
    ?>
    <? endif; ?>
    
<?php endif; ?>

<?php if ($contentModel->is_allow_change_tree) : ?>
    <?php if ($rootTreeModels) : ?>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'tree_id')->widget(
                    \skeeks\cms\widgets\formInputs\selectTree\SelectTreeInputWidget::class,
                    [
                        'options'           => [
                            'data-form-reload' => 'true',
                        ],
                        'multiple'          => false,
                        'treeWidgetOptions' =>
                            [
                                'models' => $rootTreeModels,
                            ],
                    ]
                ); ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<? if ($contentModel->isAllowEdit("external_id")) : ?>
    <?= $form->field($model, 'external_id'); ?>
<? endif; ?>


<?

$properties = $model->getRelatedProperties()->all();
/**
 * @var $property \skeeks\cms\relatedProperties\models\RelatedPropertyModel
 */
?>
<?php if ($properties) : ?>
    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Additional properties'),
    ]); ?>

    <?php foreach ($properties as $property) : ?>
        <?php if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) : ?>

            <?php $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>
            <div class="row">
                <div class="col-md-12">
                    <?
                    $create = '';
                    ?>
                    <?php if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-property-enum')[0]) : ?>
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
                                'property_id' => $property->id,
                            ]);

                            $actionCreate->name = \Yii::t("skeeks/cms", "Create");

                            /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                'actions' => ['create' => $actionCreate],
                                'isOpenNewWindow' => true
                            ]);*/

                            $create = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                                'actions'         => ['create' => $actionCreate],
                                'clientOptions'   => ['pjax-id' => $pjax->id],
                                'isOpenNewWindow' => true,
                                'tag'             => 'div',
                                'minViewCount'  => 1,
                                'itemWrapperTag'  => 'span',
                                'itemTag'         => 'button',
                                'itemOptions'     => ['class' => 'btn btn-default'],
                                'options'         => ['class' => 'sx-controll-actions'],
                            ]);
                        }
                        ?>
                    <?php endif; ?>

                    <? $field = $property->renderActiveForm($form, $model);
                    $field->template = '<div class="row sx-inline-row"><div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-5">{input}{hint}{error}</div><div class="col-md-3">' . $create . '</div></div>';
                    echo $field;
                    ?>

                </div>
            </div>
            <?php \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>
        <?php elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT): ?>

            <?php $pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); ?>
            <div class="row">
                <div class="col-md-12">
                    <?
                    $create = '';
                    ?>
                    <?php if (!in_array($property->handler->fieldElement, [
                        \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement::FIELD_ELEMENT_SELECT_DIALOG,
                        \skeeks\cms\relatedProperties\propertyTypes\PropertyTypeElement::FIELD_ELEMENT_SELECT_DIALOG_MULTIPLE,
                    ])) : ?>
                        <?php if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-element')[0]) : ?>
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
                                    'content_id' => $property->handler->content_id,
                                ]);

                                $actionCreate->name = \Yii::t("skeeks/cms", "Create");

                                /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                    'actions' => ['create' => $actionCreate],
                                    'isOpenNewWindow' => true
                                ]);*/

                                $create = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                                    'actions'         => ['create' => $actionCreate],
                                    'clientOptions'   => ['pjax-id' => $pjax->id],
                                    'isOpenNewWindow' => true,
                                    'tag'             => 'div',
                                    'minViewCount'    => 1,
                                    'itemWrapperTag'  => 'span',
                                    'itemTag'         => 'button',
                                    'itemOptions'     => ['class' => 'btn btn-default'],
                                    'options'         => ['class' => 'sx-controll-actions'],
                                ]);
                            }
                            ?>
                        <?php endif; ?>

                        <? $field = $property->renderActiveForm($form, $model);
                        $field->template = '<div class="row sx-inline-row"><div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-5">{input}{hint}{error}</div><div class="col-md-3">' . $create . '</div></div>';
                        echo $field;
                        ?>

                    <?php else: ?>
                        <?= $property->renderActiveForm($form, $model) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php \skeeks\cms\modules\admin\widgets\Pjax::end(); ?>

        <?php else
            : ?>
            <?= $property->renderActiveForm($form, $model) ?>
        <?php endif;
        ?>
    <?php endforeach; ?>

<?php else
    : ?>
    <?php /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
<?php
endif;
?>
<? $fieldSet::end(); ?>
