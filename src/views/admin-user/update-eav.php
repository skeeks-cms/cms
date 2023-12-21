<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;
?>


<?php $form = $action->beginActiveForm(); ?>
<input type="hidden" data-form-reload="true" />

<? if ($is_saved && @$is_create) : ?>
    <?php $this->registerJs(<<<JS
    sx.Window.openerWidgetTriggerEvent('model-create', {
        'submitBtn' : '{$submitBtn}'
    });
JS
    ); ?>

<? elseif ($is_saved) : ?>
    <?php $this->registerJs(<<<JS
sx.Window.openerWidgetTriggerEvent('model-update', {
        'submitBtn' : '{$submitBtn}'
    });
JS
    ); ?>
<? endif; ?>

<? if (@$redirect) : ?>
    <?php $this->registerJs(<<<JS
window.location.href = '{$redirect}';
console.log('window.location.href');
console.log('{$redirect}');
JS
    ); ?>
<? endif; ?>


<?php echo $form->errorSummary([$model, $relatedModel]); ?>


<?

$properties = $model->relatedProperties;
/**
 * @var $property \skeeks\cms\relatedProperties\models\RelatedPropertyModel
 */
?>


<?php
$createProperty = null;

if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-user-universal-property')[0]) {

    /**
     * @var \skeeks\cms\backend\BackendAction $actionIndex
     * @var \skeeks\cms\backend\BackendAction $actionCreate
     */
    $createAction = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
    if ($createAction) {

        $r = new \ReflectionClass(\skeeks\cms\models\CmsContentProperty::class);

        $createAction->url = \yii\helpers\ArrayHelper::merge($createAction->urlData, [
            $r->getShortName() => [
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

?>


<? if ($createProperty) : ?>
    <div class="sx-controlls" style="margin-bottom: 10px; margin-left: 15px;">
        <?php echo $createProperty; ?>
    </div>
<? endif; ?>

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
    left: 15px;
    z-index: 999;
}
.sx-edit-prop-wrapper {
    max-width: 40px;
    text-align: right;
}
CSS
    );
    ?>
    <?php foreach ($properties as $property) : ?>
        <?php if ($property->property_type == \skeeks\cms\relatedProperties\PropertyType::CODE_LIST) : ?>

            <?php /*$pjax = \skeeks\cms\modules\admin\widgets\Pjax::begin(); */ ?>
            <?
            $property->handler->setAjaxSelectUrl(\yii\helpers\Url::to(['/cms/ajax/autocomplete-user-eav-options', 'property_id' => $property->id]));
            $create = '';
            ?>
            <?php if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-user-universal-property-enum')[0]) : ?>
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
                'controllerId' => "/cms/admin-cms-user-universal-property/",
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
            $property->handler->setAjaxSelectUrl(\yii\helpers\Url::to(['/cms/ajax/autocomplete-user-eav-options', 'property_id' => $property->id]));
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
                    'controllerId' => "/cms/admin-cms-user-universal-property/",
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
                    'controllerId' => "/cms/admin-cms-user-universal-property/",
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
                'controllerId' => "/cms/admin-cms-user-universal-property/",
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

    <?= $form->buttonsStandart($model, ['save']); ?>

<?php else : ?>
    <div class="col-12">
        <p>На сайте еще не создано ни одной характеристики для пользователя.</p>
        <p>Создайте характеристику и форма появится в этом месте.</p>
    </div>
<?php endif; ?>

<?php echo $form->errorSummary([$model, $relatedModel]); ?>
<?php $form::end(); ?>