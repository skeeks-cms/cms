<?php
/* @var $this yii\web\View */
/* @var $model \skeeks\cms\models\CmsContentElement */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
?>
<? $fieldSet = $form->fieldSet(\Yii::t('skeeks/cms', 'Main')); ?>

<? if ($contentModel->isAllowEdit("active")) : ?>
    <?= $form->field($model, 'active')->checkbox([
        'uncheck'                                                         => \skeeks\cms\components\Cms::BOOL_N,
        'value'                                                           => \skeeks\cms\components\Cms::BOOL_Y,
        \skeeks\cms\helpers\RequestResponse::DYNAMIC_RELOAD_FIELD_ELEMENT => 'true',
    ]); ?>
<? endif; ?>

<?= $form->field($model, 'name')->textInput(['maxlength' => 255]) ?>



<?php if ($contentModel->root_tree_id) : ?>
    <?php $rootTreeModels = \skeeks\cms\models\CmsTree::findAll($contentModel->root_tree_id); ?>
<?php else
    : ?>
    <? if ($model->cms_site_id) : ?>
        <?php $rootTreeModels = \skeeks\cms\models\CmsTree::findRoots()->andWhere(['cms_site_id' => $model->cms_site_id])->joinWith('cmsSiteRelation')
            ->orderBy([\skeeks\cms\models\CmsSite::tableName().".priority" => SORT_ASC])->all();
        ?>
    <? else : ?>
        <?php $rootTreeModels = \skeeks\cms\models\CmsTree::findRootsForSite()->joinWith('cmsSiteRelation')->orderBy([\skeeks\cms\models\CmsSite::tableName().".priority" => SORT_ASC])->all();
        ?>
    <? endif; ?>

<?php endif; ?>

<?php if ($contentModel->is_allow_change_tree) : ?>
    <?php if ($rootTreeModels) : ?>
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
    <?php endif; ?>
<?php endif; ?>




<?

$properties = $model->relatedProperties;
/**
 * @var $property \skeeks\cms\relatedProperties\models\RelatedPropertyModel
 */
?>
<?php if ($properties) : ?>
    <?= \skeeks\cms\modules\admin\widgets\BlockTitleWidget::widget([
        'content' => \Yii::t('skeeks/cms', 'Характеристики'),
    ]); ?>

    <?php
    $createProperty = null;
    if ($model->tree_id) {

        if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-property')[0]) {

            /**
             * @var \skeeks\cms\backend\BackendAction $actionIndex
             * @var \skeeks\cms\backend\BackendAction $actionCreate
             */
            $createAction = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
            if ($createAction) {

                $r = new \ReflectionClass(\skeeks\cms\models\CmsContentProperty::class);

                $createAction->url = \yii\helpers\ArrayHelper::merge($createAction->urlData, [
                    $r->getShortName() => [
                        'cmsContents' => [$model->content_id],
                        'cmsTrees'    => [$model->tree_id],
                    ],
                ]);


                $createAction->name = \Yii::t("skeeks/cms", "Создать характеристику");

                /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                    'actions' => ['create' => $actionCreate],
                    'isOpenNewWindow' => true
                ]);*/

                $createProperty = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                    'actions'         => ['create' => $createAction],
                    'clientOptions'   => [
                        'updateSuccessCallback' => new \yii\web\JsExpression(<<<JS
    function() {
    $("[data-form-reload]:first").trigger("change");
    }
JS
                        ),
                    ],
                    'isOpenNewWindow' => true,
                    'tag'             => 'span',
                    'minViewCount'    => 1,
                    'itemWrapperTag'  => 'span',
                    'itemTag'         => 'button',
                    'itemOptions'     => ['class' => 'btn btn-default'],
                    'options'         => ['class' => 'sx-controll-actions'],
                ]);

            }
        }
    }

    ?>


    <? if ($createProperty) : ?>
        <div class="sx-controlls" style="margin-bottom: 10px; margin-left: 15px;">
            <?php echo $createProperty; ?>
            <a href="#" class="btn btn-default sx-btn-search-property"><i class="fa fa-search"></i> Добавить существующую</a>

            <div style="display: none;" class="sx-search-property-element-wrapper">
                <?

                $url = \yii\helpers\Url::to(['/cms/admin-cms-content-property/join-property']);

                $this->registerJs(<<<JS
    var propertyUrl = "{$url}";
    $("#search-exist-property").on("change", function() {
    var ajaxQuery = sx.ajax.preparePostQuery(propertyUrl + "&pk=" + $(this).val());
    ajaxQuery.setData({
        'tree_id': {$model->tree_id},
        'content_id': {$model->content_id}
    });
    
    console.log(ajaxQuery.toArray());
    
    var AjaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
    AjaxHandler.on("success", function() {
        $("[data-form-reload]:first").trigger("change");
    });
    
    ajaxQuery.execute();
    
    return false;
    });
    $(".sx-btn-search-property").on("click", function() {
    $(".sx-search-property-element-wrapper .sx-btn-create").click();
    return false;
    });
JS
                );
                echo \skeeks\cms\backend\widgets\SelectModelDialogWidget::widget([
                    'id'             => 'search-exist-property',
                    'modelClassName' => \skeeks\cms\models\CmsContentProperty::class,
                    'name'           => 'search-property',
                    'dialogRoute'    => [
                        '/cms/admin-cms-content-property',
                    ],
                ]);
                ?>
            </div>

        </div>
    <? endif; ?>


    <?
    $this->registerCss(<<<CSS
.form-group .sx-fast-edit {
    opacity: 0;
}
.form-group:hover .sx-fast-edit {
    opacity: 1;
    cursor: pointer;
}
.form-group {
    position: relative;
}
.form-group .sx-fast-edit {
    transition: 1s;
    color: gray;
    left: 15px;
    z-index: 999;
}
.sx-edit-prop-wrapper {
    max-width: 100px;
    text-align: right;
}
CSS
    );
    ?>
    <?php foreach ($properties as $property) : ?>
        <?php if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) : ?>

            <?php /*$pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); */ ?>
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

                            $actionCreate->name = \Yii::t("skeeks/cms", "Добавить опцию");

                            /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                'actions' => ['create' => $actionCreate],
                                'isOpenNewWindow' => true
                            ]);*/

                            $create = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                                'actions'         => ['create' => $actionCreate],
                                'clientOptions'   => [
                                    'updateSuccessCallback' => new \yii\web\JsExpression(<<<JS
function() {
}
JS
                                    ),
                                ],
                                'isOpenNewWindow' => true,
                                'tag'             => 'div',
                                'minViewCount'    => 1,
                                'itemWrapperTag'  => 'span',
                                'itemTag'         => 'button',
                                'itemOptions'     => ['class' => 'btn btn-default btn-sm'],
                                'options'         => ['class' => 'sx-controll-actions'],
                            ]);
                        }
                        ?>
                    <?php endif; ?>

                    <?
                    $field = $property->renderActiveForm($form, $model);

                    $editBtn = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
                        'controllerId' => "/cms/admin-cms-content-property/",
                        'modelId'      => $property->id,
                        'tag'          => 'span',
                        'content'      => '<i title="Настроить характеристику" class="fas fa-cog"></i>',
                        'options'      => [
                            'class' => 'sx-fast-edit',
                        ],
                    ]);

                    $field->template = '{label}<div class="row"><div class="col" style="width: 100%;">{input}</div><div class="col my-auto" style="max-width: 162px;">'.$create.'</div><div class="col my-auto sx-edit-prop-wrapper">'.$editBtn.'</div></div>{hint}{error}';
                    //$field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-5">{input}{hint}{error}</div><div class="col-md-3 my-auto">'.$create.'</div></div>';
                    echo $field;
                    ?>
            <?php /*\skeeks\cms\modules\admin\widgets\Pjax::end(); */ ?>
        <?php elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT): ?>

            <?php /*$pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); */ ?>
                    <?
                    $create = '';
                    ?>
                    <?php if (1 == 1) : ?>
                        <?php if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-content-element')[0]) : ?>
                            <?

                            $controllerProperty->content = \skeeks\cms\models\CmsContent::findOne($property->handler->content_id);
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

                                $actionCreate->name = \Yii::t("skeeks/cms", "Добавить опцию");

                                /*echo \skeeks\cms\backend\widgets\DropdownControllerActionsWidget::widget([
                                    'actions' => ['create' => $actionCreate],
                                    'isOpenNewWindow' => true
                                ]);*/

                                $create = \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
                                    'actions'         => ['create' => $actionCreate],
                                    'clientOptions'   => [
                                        'updateSuccessCallback' => new \yii\web\JsExpression(<<<JS
function() {
    
}
JS
                                        ),
                                    ],
                                    'isOpenNewWindow' => true,
                                    'tag'             => 'div',
                                    'minViewCount'    => 1,
                                    'itemWrapperTag'  => 'span',
                                    'itemTag'         => 'button',
                                    'itemOptions'     => ['class' => 'btn btn-default btn-sm'],
                                    'options'         => ['class' => 'sx-controll-actions'],
                                ]);
                            }
                            ?>
                        <?php endif; ?>

                        <?

                        $editBtn = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
                            'controllerId' => "/cms/admin-cms-content-property/",
                            'modelId'      => $property->id,
                            'tag'          => 'span',
                            'content'      => '<i title="Настроить характеристику" class="fas fa-cog"></i>',
                            'options'      => [
                                'class' => 'sx-fast-edit',
                            ],
                        ]);
                        ?>
                        <? $field = $property->renderActiveForm($form, $model);
                        //$field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-5">{input}{hint}{error}</div><div class="col-md-3 my-auto">'.$create.'</div></div>';
                        $field->template = '{label}<div class="row"><div class="col" style="width: 100%;">{input}</div><div class="col my-auto" style="max-width: 162px;">'.$create.'</div><div class="col my-auto sx-edit-prop-wrapper">'.$editBtn.'</div></div>{hint}{error}';
                        echo $field;
                        ?>

                    <?php else: ?>
                        <?

                        $editBtn = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
                            'controllerId' => "/cms/admin-cms-content-property/",
                            'modelId'      => $property->id,
                            'tag'          => 'span',
                            'content'      => '<i title="Настроить характеристику" class="fas fa-cog"></i>',
                            'options'      => [
                                'class' => 'sx-fast-edit',
                            ],
                        ]);
                        ?>

                        <? $field = $property->renderActiveForm($form, $model);
                        $field->template = '{label}<div class="row"><div class="col" style="width: 100%;">{input}</div><div class="col my-auto" style="max-width: 162px;">'.$create.'</div><div class="col my-auto sx-edit-prop-wrapper">'.$editBtn.'</div></div>{hint}{error}';
                        //$field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-8">{input}{hint}{error}</div></div>';
                        echo $field;
                        ?>
                    <?php endif; ?>
            <?php /*\skeeks\cms\modules\admin\widgets\Pjax::end(); */ ?>

        <?php else
            : ?>
            <?

            $editBtn = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
                'controllerId' => "/cms/admin-cms-content-property/",
                'modelId'      => $property->id,
                'tag'          => 'span',
                'content'      => '<i title="Настроить характеристику" class="fas fa-cog"></i>',
                'options'      => [
                    'class' => 'sx-fast-edit',
                ],
            ]);
            ?>

            <? $field = $property->renderActiveForm($form, $model);
            //$field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-8">{input}{hint}{error}</div></div>';
            $field->template = '{label}<div class="row"><div class="col" style="width: 100%;">{input}</div><div class="col my-auto sx-edit-prop-wrapper">'.$editBtn.'</div></div>{hint}{error}';
            echo $field;
            ?>
        <?php endif;
        ?>
    <?php endforeach; ?>

<?php else : ?>
    <?php /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
<?php endif; ?>

<? if (count($properties) > 10) : ?>
    <? if ($createProperty) : ?>
        <div class="sx-controlls" style="margin-bottom: 10px; margin-top: 10px; margin-left: 15px;">
            <?php echo $createProperty; ?>
            <a href="#" class="btn btn-default sx-btn-search-property"><i class="fa fa-search"></i> Добавить существующую</a>
        </div>
    <? endif; ?>
<? endif; ?>

<? $fieldSet::end(); ?>
