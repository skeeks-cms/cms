<?php

use skeeks\cms\models\Tree;

/* @var $this yii\web\View */
/* @var $tree Tree */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $tree \skeeks\cms\models\CmsTree */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
$controller = $this->context;
$action = $controller->action;
$tree = $action->model;

$model = $tree;




?>

<?php
$create = null;
if ($controllerProperty = \Yii::$app->createController('cms/admin-cms-faq')[0]) {

    /**
     * @var \skeeks\cms\backend\BackendAction $actionIndex
     * @var \skeeks\cms\backend\BackendAction $actionCreate
     */
    $createAction = \yii\helpers\ArrayHelper::getValue($controllerProperty->actions, 'create');
    if ($createAction) {

        $r = new \ReflectionClass(\skeeks\cms\models\CmsFaq::class);

        $createAction->url = \yii\helpers\ArrayHelper::merge($createAction->urlData, [
            $r->getShortName() => [
                'contentElements'    => [$tree->id],
            ],
        ]);


        $createAction->name = \Yii::t("skeeks/cms", "Создать вопрос/ответ");

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



<div class="sx-cms-content">
    <div class="sx-controlls" style="margin-bottom: 10px;">
        <?php echo $createProperty; ?>
        <a href="#" class="btn btn-default sx-btn-search-property"><i class="fa fa-search"></i> Привязать уже созданный вопрос</a>

        <div style="display: none;" class="sx-search-property-element-wrapper">
            <?

            $url = \yii\helpers\Url::to(['/cms/admin-cms-faq/join-tree']);

            $this->registerJs(<<<JS
var propertyUrl = "{$url}";
$("#search-exist-faq").on("change", function() {
var ajaxQuery = sx.ajax.preparePostQuery(propertyUrl + "&pk=" + $(this).val());
ajaxQuery.setData({
'tree_id': {$tree->id},
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
                'id'             => 'search-exist-faq',
                'modelClassName' => \skeeks\cms\models\CmsFaq::class,
                'name'           => 'search-faq',
                'dialogRoute'    => [
                    '/cms/admin-cms-faq',
                ],
            ]);
            ?>
        </div>


    </div>

    <?php if($model->cmsFaqs) : ?>
        <?php echo $this->render('@skeeks/cms/views/admin-tree/_faqs', [
            'elements' => $model->cmsFaqs
        ]); ?>
    <?php endif; ?>
</div>


