<?php

use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $tree Tree */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
$controller = $this->context;
$action = $controller->action;
$tree = $action->model;

$contents = \skeeks\cms\models\CmsContent::find()->andWhere(['cms_tree_type_id' => $tree->tree_type_id])->all();
?>
<?php foreach ($contents as $content) : ?>

    <div class="sx-cms-content">
        <div style="text-transform: uppercase; font-weight: bold;">
            <?php echo $content->name; ?>
        </div>

        <div style="color: gray; margin-bottom: 5px;">При добавлении элементов "<?php echo $content->name; ?>" в этот раздел, будет заполнять такие характеристики.</div>

        <?php
        $create = null;
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
                        'cmsContents' => [$content->id],
                        'cmsTrees'    => [$tree->id],
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
    window.location.reload();
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
        ?>



        <? if ($createProperty) : ?>
            <div class="sx-controlls" style="margin-bottom: 10px;">
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
        'tree_id': {$tree->id},
        'content_id': {$content->id}
    });
    
    console.log(ajaxQuery.toArray());
    
    var AjaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
    AjaxHandler.on("success", function() {
        window.location.reload();
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

        <?php

        $model = new \skeeks\cms\models\CmsContentElement([
            'content_id' => $content->id,
            'tree_id'    => $tree->id,
        ]);
        $model->relatedPropertiesModel->initAllProperties();

        $form = \skeeks\cms\backend\widgets\ActiveFormBackend::begin();


        $properties = \skeeks\cms\models\CmsContentProperty::find()
            ->cmsSite()
            ->joinWith('cmsContentProperty2trees as map')
            ->joinWith('cmsContentProperty2contents as cmap')
            ->andWhere([
                'or',
                ['map.cms_tree_id' => $tree->id],
                ['map.cms_tree_id' => null],
            ])
            ->andWhere([
                'cmap.cms_content_id' => $content->id,
            ])
            ->groupBy('code')
            ->orderBy([\skeeks\cms\models\CmsContentProperty::tableName().'.priority' => SORT_ASC])
            ->all();
        ?>

        <?php if ($properties) : ?>
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
    position: absolute;
    top: 50%;
    transform: translateX(-50%) translateY(-50%);
    left: 15px;
    z-index: 999;
}
CSS
            );
            ?>
            <?php foreach ($properties as $property) : ?>
                <?php if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) : ?>

                    <?php /*$pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); */ ?>
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
                                'content'      => '<i class="fas fa-pencil-alt"></i>',
                                'options'      => [
                                    'class' => 'sx-fast-edit',
                                    'title' => 'Редактировать характеристику',
                                ],
                            ]);

                            $field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-5">{input}{hint}{error}</div><div class="col-md-3 my-auto">'.$create.'</div></div>';
                            echo $field;
                            ?>
                        </div>
                    </div>
                    <?php /*\skeeks\cms\modules\admin\widgets\Pjax::end(); */ ?>
                <?php elseif ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_ELEMENT): ?>

                    <?php /*$pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); */ ?>
                    <div class="row">
                        <div class="col-md-12">
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
                                    'content'      => '<i class="fas fa-pencil-alt"></i>',
                                    'options'      => [
                                        'class' => 'sx-fast-edit',
                                        'title' => 'Редактировать характеристику',
                                    ],
                                ]);
                                ?>
                                <? $field = $property->renderActiveForm($form, $model);
                                $field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-5">{input}{hint}{error}</div><div class="col-md-3 my-auto">'.$create.'</div></div>';
                                echo $field;
                                ?>

                            <?php else: ?>
                                <?

                                $editBtn = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
                                    'controllerId' => "/cms/admin-cms-content-property/",
                                    'modelId'      => $property->id,
                                    'tag'          => 'span',
                                    'content'      => '<i class="fas fa-pencil-alt"></i>',
                                    'options'      => [
                                        'class' => 'sx-fast-edit',
                                        'title' => 'Редактировать характеристику',
                                    ],
                                ]);
                                ?>

                                <? $field = $property->renderActiveForm($form, $model);
                                $field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}111</div><div class="col-md-8">{input}{hint}{error}</div></div>';
                                echo $field;
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php /*\skeeks\cms\modules\admin\widgets\Pjax::end(); */ ?>

                <?php else
                    : ?>
                    <?

                    $editBtn = \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::widget([
                        'controllerId' => "/cms/admin-cms-content-property/",
                        'modelId'      => $property->id,
                        'tag'          => 'span',
                        'content'      => '<i class="fas fa-pencil-alt"></i>',
                        'options'      => [
                            'class' => 'sx-fast-edit',
                            'title' => 'Редактировать характеристику',
                        ],
                    ]);
                    ?>

                    <? $field = $property->renderActiveForm($form, $model);
                    $field->template = '<div class="row sx-inline-row">'.$editBtn.'<div class="col-md-3 text-md-right my-auto">{label}</div><div class="col-md-8">{input}{hint}{error}</div></div>';
                    echo $field;
                    ?>
                <?php endif;
                ?>
            <?php endforeach; ?>

        <?php else : ?>
            <?php /*= \Yii::t('skeeks/cms','Additional properties are not set')*/ ?>
        <?php endif; ?>

        <?
        \skeeks\cms\backend\widgets\ActiveFormBackend::end();
        ?>

        <? if (count($properties) > 10) : ?>
            <? if ($createProperty) : ?>
                <div class="sx-controlls" style="margin-bottom: 10px; margin-top: 10px;">
                    <?php echo $createProperty; ?>
                    <a href="#" class="btn btn-default sx-btn-search-property"><i class="fa fa-search"></i> Добавить существующую</a>
                </div>
            <? endif; ?>
        <? endif; ?>

    </div>
<?php endforeach; ?>

