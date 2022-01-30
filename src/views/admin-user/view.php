<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;



$jsData = \yii\helpers\Json::encode([
    'backend' => \yii\helpers\Url::to(['update-attribute', 'pk' => $model->id]),
]);

$this->registerJs(<<<JS



(function(sx, $, _)
{
    sx.classes.FastEdit = sx.classes.Component.extend({
   
        _onDomReady: function()
        {
            var self = this;
            
            $('body').on('click', function (e) {
                //did not click a popover toggle or popover
                if ($(e.target).data('toggle') !== 'popover'
                    && $(e.target).closest('.popover').length === 0
                    && !$(e.target).hasClass("sx-fast-edit-popover")
                    && !$(e.target).closest(".sx-fast-edit-popover").length
                    ) { 
                    $('.sx-fast-edit-popover').popover('hide');
                }
            });
            
            $("body").on("click", ".sx-fast-edit-popover", function() {
                var jWrapper = $(this);
                $(".sx-fast-edit-popover").popover("hide");
                self._createPopover(jWrapper);
            });
            
            
        },
        
        _createPopover(jWrapper) {
            
            if (!jWrapper.hasClass('is-rendered')) {
                jWrapper.popover({
                    "html": true,
                    //'container': "body",
                    'trigger': "click",
                    'boundary': 'window',
                    'title': jWrapper.data('title').length ? jWrapper.data('title') : "",
                    'content': $(jWrapper.data('form'))
                });
    
                jWrapper.on('show.bs.popover', function (e, data) {
                    jWrapper.addClass('is-rendered');
                });
            }
            

            jWrapper.popover('show');
        }
        
        
    });
})(sx, sx.$, sx._);

new sx.classes.FastEdit({$jsData});
JS
);
$this->registerCSS(<<<CSS
.sx-fast-edit-value {
    padding: 5px;
}

.sx-fast-edit-form-wrapper {
    display: none;
}

.sx-fast-edit {
    cursor: pointer;
    min-width: 40px;
    border-bottom: 1px dotted;
}


/**
 * Современное оформление свойств
 */
.sx-properties-wrapper.sx-columns-1 ul.sx-properties {
    -moz-column-count: 1;
    column-count: 1;
}

.sx-properties-wrapper.sx-columns-2 ul.sx-properties {
    -moz-column-count: 2;
    column-count: 2;
}

.sx-properties-wrapper.sx-columns-3 ul.sx-properties {
    -moz-column-count: 3;
    column-count: 3;
}

ul.sx-properties {
    -moz-column-count: 2;
    column-count: 2;
    grid-column-gap: 40px;
    -moz-column-gap: 40px;
    column-gap: 40px;
    margin: 0px;
    padding: 0px;
}

ul.sx-properties li {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 8px;
    page-break-inside: avoid;
    -moz-column-break-inside: avoid;
    break-inside: avoid;
}

ul.sx-properties .sx-properties--value {
    text-align: right;
    max-width: 200px;
    line-height: 1.4;
}

ul.sx-properties .sx-properties--name {
    color: gray;
    flex: 1;
    display: flex;
    align-items: baseline;
    white-space: nowrap;
}

ul.sx-properties .sx-properties--name:after {
    content: "";
    flex-grow: 1;
    opacity: .25;
    margin: 0 6px 0 2px;
    border-bottom: 1px dotted gray;
}



.sx-table td, .sx-table th {
    border: 0;
    text-align: center;
    padding: 7px 10px;
    font-size: 13px;
    border-bottom: 1px solid #dee2e68f;
    background: white;
}


.sx-table th {
    background: #f9f9f9;
}

.sx-table-wrapper {
    border-radius: 5px;
    border-left: 1px solid #dee2e68f;
    border-right: 1px solid #dee2e68f;
    border-top: 1px solid #dee2e68f;
}
.sx-table-wrapper table {
    margin-bottom: 0;
}


.sx-info-block {
    background: #f9f9f9;
    margin-top: 10px;
    padding: 10px;
}
.sx-title {
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 5px;
}

CSS
);
$noValue = "<span style='color: silver;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
?>


<?php $pjax = \skeeks\cms\widgets\Pjax::begin(); ?>
<div class="row no-gutters">
    <div class="col-lg-4 col-sm-6 col-12">
        <div style="padding: 10px;" class="sx-bg-secondary">
            <div class="sx-properties-wrapper sx-columns-1" style="">
                <ul class="sx-properties">

                    <li>
                        <span class="sx-properties--name">
                            Активность
                        </span>
                        <span class="sx-properties--value">
                            <span class="sx-fast-edit sx-fast-edit-popover"
                                  data-form="#is_active-form"
                                  data-title="Активность"
                            >
                                <?php echo $model->is_active ? '<span data-toggle="tooltip" title="Пользователь активен"  style="color: green;">✓</span>' : '<span data-toggle="tooltip" title="Товар не активен" style="color: red;">x</span>' ?>
                            </span>

                            <div class="sx-fast-edit-form-wrapper">
                                <?php $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                                    'id'             => "is_active-form",
                                    'action'         => \yii\helpers\Url::to(['update-attribute', 'pk' => $model->id]),
                                    'options'        => [
                                        'class' => 'sx-fast-edit-form',
                                    ],
                                    'clientCallback' => new \yii\web\JsExpression(<<<JS
                                        function (ActiveFormAjaxSubmit) {
                                            ActiveFormAjaxSubmit.on('success', function(e, response) {
                                                $.pjax.reload("#{$pjax->id}");
                                                $(".sx-fast-edit").popover("hide");
                                            });
                                        }
JS
                                    ),
                                ]); ?>
                                <?php echo $form->field($model, 'is_active')->radioList(\Yii::$app->formatter->booleanFormat)->label(false); ?>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-check"></i> Сохранить</button>
                                    </div>
                                <?php $form::end(); ?>
                            </div>

                        </span>
                    </li>

                    <li>
                        <span class="sx-properties--name">
                            Email
                        </span>
                        <span class="sx-properties--value">
                            <span class="sx-fast-edit sx-fast-edit-popover"
                                  data-form="#emails-form"
                                  data-title="Email"
                            >
                                <?php echo $model->email ? $model->email : $noValue; ?>
                            </span>

                            <div class="sx-fast-edit-form-wrapper">
                                <?php $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                                    'id'             => "emails-form",
                                    'action'         => \yii\helpers\Url::to(['update-attribute', 'pk' => $model->id]),
                                    'options'        => [
                                        'class' => 'sx-fast-edit-form',
                                    ],
                                    'clientCallback' => new \yii\web\JsExpression(<<<JS
                                        function (ActiveFormAjaxSubmit) {
                                            ActiveFormAjaxSubmit.on('success', function(e, response) {
                                                $.pjax.reload("#{$pjax->id}");
                                                $(".sx-fast-edit").popover("hide");
                                            });
                                        }
JS
                                    ),
                                ]); ?>
                                <?php echo $form->field($model, 'email')->textInput()->label(false); ?>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-check"></i> Сохранить</button>
                                    </div>
                                <?php $form::end(); ?>
                            </div>

                        </span>
                    </li>

                    <li>
                        <span class="sx-properties--name">
                            Телефон
                        </span>
                        <span class="sx-properties--value">
                            <span class="sx-fast-edit sx-fast-edit-popover"
                                  data-form="#phones-form"
                                  data-title="Телефон"
                            >
                                <?php echo $model->phone ? $model->phone : $noValue; ?>
                            </span>

                            <div class="sx-fast-edit-form-wrapper">
                                <?php $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                                    'id'             => "phones-form",
                                    'action'         => \yii\helpers\Url::to(['update-attribute', 'pk' => $model->id]),
                                    'options'        => [
                                        'class' => 'sx-fast-edit-form',
                                    ],
                                    'clientCallback' => new \yii\web\JsExpression(<<<JS
                                        function (ActiveFormAjaxSubmit) {
                                            ActiveFormAjaxSubmit.on('success', function(e, response) {
                                                $.pjax.reload("#{$pjax->id}");
                                                $(".sx-fast-edit").popover("hide");
                                            });
                                        }
JS
                                    ),
                                ]); ?>
                                <?php echo $form->field($model, 'phone')->textInput()->label(false); ?>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-check"></i> Сохранить</button>
                                    </div>
                                <?php $form::end(); ?>
                            </div>

                        </span>
                    </li>

                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-8 col-sm-6 col-12">
        <div style="margin-left: 10px; padding: 10px;" class="sx-bg-secondary">
            Комментарии
        </div>
    </div>
</div>
<?php $pjax = \skeeks\cms\widgets\Pjax::end(); ?>
