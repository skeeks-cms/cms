<?php

use skeeks\cms\models\Tree;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Tree */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsLang */
/* @var $relatedModel \skeeks\cms\relatedProperties\models\RelatedPropertiesModel */
$controller = $this->context;
$action = $controller->action;
?>

<? $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
    'clientCallback' => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
        ActiveFormAjaxSubmit.on('success', function(e, response) {
            $("#sx-result").empty();
            
            if (response.data.html) {
                $("#sx-result").append(response.data.html);
            }
        });
    }
JS
)
]); ?>

<?php echo $form->field($dm, 'is_copy_childs')->checkbox(); ?>
<?php echo $form->field($dm, 'is_copy_elements')->checkbox(); ?>

<div class="form-group">
    <button type="submit" class="btn btn-primary">Запустить копирование</button>
</div>

<? $form::end(); ?>
