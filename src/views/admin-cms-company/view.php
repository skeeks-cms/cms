<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsCompany */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;


$jsData = \yii\helpers\Json::encode([
    'backend' => \yii\helpers\Url::to(['update-attribute', 'pk' => $model->id]),
]);

$this->registerJs(<<<JS



(function(sx, $, _)
{
    $("body").on("click", ".sx-send-sms-trigger", function() {
        $("#sx-send-sms-modal").modal('show');
        $(".sx-send-sms-phone").empty().append($(this).data("phone"));
        $("#sx-send-sms-phone-value").val($(this).data("phone"));
        return false;
    });
    
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

.sx-send-sms-phone {
    font-weight: bold;
}
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

.sx-block-title {
    font-size: 12px; 
    text-transform: uppercase; 
    margin-bottom: 5px; 
    font-weight: bold;
    color: #3a3a3a;
}
.sx-block {
    margin-bottom: 20px;
    /*padding: 10px;*/
}
/*.sx-block .sx-block-content {
    padding: 10px;
    background: #f9f9f9;
}*/

.sx-label {
    font-size: 11px;
    color: #a1a1a1;
}

.sx-value-row {
    margin-bottom: 10px;
    line-height: 1.3;
}
.sx-edit-btn {
    color: silver;
    cursor: pointer;
    opacity: 0;
    margin-right: 5px;
    transition: 0.4s;
}
.sx-value-row:hover .sx-edit-btn {
    opacity: 1;
}

CSS
);
$noValue = "<span style='color: silver;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
?>


<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-global',
]); ?>
    <div class="row">


        <div class="col-lg-4 col-sm-6 col-12">

            <?php if ($model->description || $model->categories || $model->cms_company_status_id) : ?>
                <div class="sx-block">
                    <div class="sx-block-title">Общая информация <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                                    title=""
                                                                    class="far fa-question-circle"></i>
                    </div>


                    <div class="sx-block-content">
                        <div class="sx-phones-block">
                            <?php if ($model->status) : ?>
                                <div class="sx-value-row d-flex">
                                    <div style="width: 100%;">
                                        <div class="sx-label">
                                            Статус
                                        </div>
                                        <div class="sx-value">
                                            <?php echo $model->status->name; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->categories) : ?>
                                <div class="sx-value-row d-flex">
                                    <div style="width: 100%;">
                                        <div class="sx-label">
                                            Сферы деятельности
                                        </div>
                                        <div class="sx-value">
                                            <?php echo implode("<br>", \yii\helpers\ArrayHelper::map($model->categories, "id", "name")); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->description) : ?>
                                <div class="sx-value-row d-flex">
                                    <div style="width: 100%;">
                                        <div class="sx-label">
                                            Описание
                                        </div>
                                        <div class="sx-value">
                                            <?php echo $model->description; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="sx-block">
                <div class="sx-block-title">Контакты <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                        title="Сотрудники или люди связанные с компанией. Они являются полноценными пользователями, которые могут авторизоваться на сайте и смотерть данные по этой компании в личном кабинете."
                                                        class="far fa-question-circle"></i>
                </div>


                <div class="sx-block-content">
                    <div class="sx-users-block sx-phones-block">
                        <? if ($model->cmsCompany2users) : ?>
                            <? foreach ($model->getCmsCompany2users()->orderBy(['sort' => SORT_ASC])->all() as $cmsCompany2users) : ?>
                                <div class="sx-value-row d-flex">
                                    <div style="width: 100%;">
                                        <div class="my-auto">
                                            <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                                                'cmsUser' => $cmsCompany2users->cmsUser,
                                                'append'  => "<span style='color: gray; font-size: 12px; text-decoration: none; border-bottom: 0px;'>{$cmsCompany2users->comment}</span>",
                                            ]); ?>
                                        </div>
                                    </div>
                                    <div class="my-auto">
                                        <?

                                        \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                            'controllerId' => "/cms/admin-cms-company2user",
                                            'modelId'      => $cmsCompany2users->id,
                                            'tag'          => 'div',
                                            'options'      => [
                                                'title' => 'Редактировать',
                                                'class' => 'sx-edit-btn btn btn-default',
                                            ],
                                        ]);
                                        ?>
                                        <!--<i class="hs-admin-angle-down"></i>-->
                                        <i class="fas fa-ellipsis-v"></i>
                                        <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>
                                    </div>

                                </div>
                            <? endforeach; ?>
                        <? endif; ?>
                    </div>

                    <?php
                    $userClass = \Yii::$app->user->identityClass;
                    $newContact = new $userClass();
                    \skeeks\cms\admin\assets\JqueryMaskInputAsset::register($this);
                    $id = \yii\helpers\Html::getInputId($newContact, "phone");

                    $individualTipUrl = \yii\helpers\Url::to(['tip-users']);

                    $this->registerJs(<<<JS
$("#{$id}").mask("+7 999 999-99-99");

(function(sx, $, _)
{
    sx.classes.IndividualTip = sx.classes.Component.extend({
        _init: function() {
            this.on("tip", function() {
                
            });
        },
        
        _onDomReady: function()
        {
            var self = this;
            
            $("body").on("change", "#sx-add-child-contact #sx-user-id", function() {
                var jForm = $(this).closest("form");
                $(".sx-new-contractor-fields", jForm).fadeOut(400, function() {
                    $(this).remove();
                    jForm.submit();
                });
                
            });
            
            $("body").on("click", "#sx-add-child-contact .sx-btn-select", function() {
                var jForm = $(this).closest("form");
                $("#sx-user-id", jForm).val($(this).data("id")).trigger("change");
            });
            
            $("#sx-add-child-contact input").on("keyup", function() {
                 var data = $(this).closest("form").serializeArray();
                 var ajaxQuery = sx.ajax.preparePostQuery('{$individualTipUrl}', data);
                 var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
                 ajaxHandler.on("success", function(e, data) {
                    if (data.data.htmlTip) {
                        $(".sx-tips-wrapper").show();
                        $(".sx-tips").empty().append(data.data.htmlTip);
                        $(".sx-button-wrapper").hide();
                    } else {
                        $(".sx-tips").empty();
                        $(".sx-tips-wrapper").hide();
                        $(".sx-button-wrapper").show();
                    }
                 });
                 ajaxQuery.execute();
                 
                 setTimeout(function() {
                     self.trigger("tip");
                 }, 1000);
            });
        }
    });
    
    new sx.classes.IndividualTip();
})(sx, sx.$, sx._);

JS
                    );

                    $widget = \yii\bootstrap\Modal::begin([
                        'header'       => "Добавление контакта",
                        'size'         => \yii\bootstrap\Modal::SIZE_DEFAULT,
                        'toggleButton' => [
                            'label' => 'Добавить',
                            'class' => 'btn btn-default btn-sm',
                        ],
                    ]); ?>

                    <?php
                    $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                        'id'                     => "sx-add-child-contact",
                        'action'                 => \yii\helpers\Url::to(['add-user', 'pk' => $model->id]),
                        'enableAjaxValidation'   => false,
                        'enableClientValidation' => false,
                        'clientCallback'         => new \yii\web\JsExpression(<<<JS
        function (ActiveFormAjaxSubmit) {
            ActiveFormAjaxSubmit.on('success', function(e, response) {
                console.log('111');
                
                $(".modal").modal("hide");
                setTimeout(function() {
                    window.location.reload();
                }, 500);
                
            });
        }
JS
                        ),
                    ]); ?>
                    <div class="sx-new-contractor-fields">
                        <?php echo $form->field($newContact, 'phone'); ?>
                        <?php echo $form->field($newContact, 'email'); ?>

                        <?php echo $form->field($newContact, 'last_name'); ?>
                        <?php echo $form->field($newContact, 'first_name'); ?>
                        <?php echo $form->field($newContact, 'patronymic'); ?>
                    </div>
                    <div style="display: none;">
                        <input type="text" name="sx-user-id" id="sx-user-id"/>
                    </div>

                    <div class="sx-button-wrapper" style="display: none;">
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>

                    <div class="sx-tips-wrapper" style="display: none; padding-top: 10px;">
                        <h5>Выберите контакт</h5>
                        <div class="row sx-tips"></div>
                    </div>

                    <?php $form::end(); ?>

                    <?php $widget::end(); ?>


                </div>
            </div>

            <div class="sx-block">
                <div class="sx-block-title">Телефон <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                       title="У компании может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию."
                                                       class="far fa-question-circle"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->phones as $cmsUserPhone) : ?>
                            <div class="sx-value-row d-flex">
                                <div style="width: 100%;">
                                    <div class="sx-label">
                                        <? if ($cmsUserPhone->name) : ?>
                                            <? echo $cmsUserPhone->name; ?>
                                        <? else : ?>
                                            Телефон
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <a href="#"><?php echo $cmsUserPhone->value; ?></a>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-cms-company-phone",
                                        'modelId'      => $cmsUserPhone->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать телефон',
                                            'class' => 'sx-edit-btn btn btn-default',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <div class="my-auto d-flex">

                                    <div class="btn btn-default sx-send-sms-trigger" data-phone="<?php echo $cmsUserPhone->value; ?>" data-html="true" title="Написать sms" style="margin-right: 5px;">
                                        <i class="fas fa-sms"></i>
                                    </div>


                                    <div class="btn btn-default" data-html="true" title="Начать звонок">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-cms-company-phone/create",
                            'CmsCompanyPhone' => [
                                'cms_company_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                </div>
            </div>


            <div class="sx-block">
                <div class="sx-block-title">Email <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                     title="У пользователя может быть задано несколько email адресов. Первый из них является основным и используется по умолчанию."
                                                     class="far fa-question-circle"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->emails as $cmsUserEmail) : ?>
                            <div class="sx-value-row d-flex">
                                <div style="width: 100%;">
                                    <div class="sx-label">
                                        <? if ($cmsUserEmail->name) : ?>
                                            <? echo $cmsUserEmail->name; ?>
                                        <? else : ?>
                                            Email
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <a href="#"><?php echo $cmsUserEmail->value; ?></a>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-cms-company-email",
                                        'modelId'      => $cmsUserEmail->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать email',
                                            'class' => 'sx-edit-btn btn btn-default',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <div class="my-auto">
                                    <div class="btn btn-default" data-html="true" title="Написать письмо">
                                        <i class="far fa-envelope"></i>
                                    </div>
                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-cms-company-email/create",
                            'CmsCompanyEmail' => [
                                'cms_company_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                </div>
            </div>


            <div class="sx-block">
                <div class="sx-block-title">Адреса <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                      title="У пользователя может быть задано несколько адресов. Первый из них является основным и используется по умолчанию."
                                                      class="far fa-question-circle"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->addresses as $cmsUserAddress) : ?>
                            <div class="sx-value-row d-flex">
                                <div style="width: 100%;">
                                    <div class="sx-label">
                                        <? if ($cmsUserAddress->name) : ?>
                                            <? echo $cmsUserAddress->name; ?>
                                        <? else : ?>
                                            Адрес
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <a href="#"><?php echo $cmsUserAddress->value; ?></a>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-cms-company-address",
                                        'modelId'      => $cmsUserAddress->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать адрес',
                                            'class' => 'sx-edit-btn btn btn-default',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        //"size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-cms-company-address/create",
                            'CmsCompanyAddress' => [
                                'cms_company_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                </div>
            </div>


            <div class="sx-block">
                <div class="sx-block-title">Реквизиты <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                         title="Для оформления заказов и сделок на юридическое лицо необходимо добавить контрагента-компанию в этот раздел." class="far fa-question-circle"></i></div>


                <? foreach ($model->contractors as $cmsContractor) : ?>
                    <div class="sx-value-row d-flex">
                        <div style="width: 100%;">
                            <div class="sx-label">
                                <? if ($cmsContractor->contractor_type) : ?>
                                    <? echo $cmsContractor->getTypeAsText(); ?>
                                <? else : ?>

                                <? endif; ?>
                            </div>
                            <div class="sx-value">
                                <a href="#">
                                    <?php echo $cmsContractor->asText; ?>
                                    <?php if ($cmsContractor->inn) : ?>
                                        <br/><small style="color: gray;"><?php echo $cmsContractor->inn; ?></small>
                                    <?php endif; ?>

                                </a>
                            </div>
                        </div>

                        <div class="my-auto"><?
                            \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                'controllerId' => "/cms/admin-cms-contractor",
                                'modelId'      => $cmsContractor->id,
                                'tag'          => 'div',
                                'options'      => [
                                    'title' => 'Редактировать юр. лицо',
                                    'class' => 'sx-edit-btn btn btn-default',
                                ],
                            ]);
                            ?>
                            <!--<i class="hs-admin-angle-down"></i>-->
                            <i class="fas fa-ellipsis-v"></i>
                            <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                        </div>
                        <!--<div class="my-auto">
                            <div class="btn btn-default" data-html="true" title="Написать письмо">
                                <i class="far fa-envelope"></i>
                            </div>
                        </div>-->
                    </div>
                <? endforeach; ?>


                <div class="sx-block-content">
                    <?php $widget = \yii\bootstrap\Modal::begin([
                        'header'       => "Добавление юр. лица",
                        'size'         => \yii\bootstrap\Modal::SIZE_DEFAULT,
                        'toggleButton' => [
                            'label' => 'Добавить',
                            'class' => 'btn btn-default btn-sm',
                        ],
                    ]); ?>

                    <?php
                    $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
                        'action'               => \yii\helpers\Url::to(['add-contractor', 'pk' => $model->id]),
                        'enableAjaxValidation' => false,
                        'clientCallback'       => new \yii\web\JsExpression(<<<JS
        function (ActiveFormAjaxSubmit) {
            ActiveFormAjaxSubmit.on('success', function(e, response) {
                $(".modal").modal("hide");
                setTimeout(function() {
                    window.location.reload();
                }, 500);
                
            });
        }
JS
                        ),
                    ]); ?>

                    <div class="d-flex flex-row" style="padding-bottom: 20px;">
                        <input type="text" name="inn" class="form-control" placeholder="Инн организации или ИП">
                        <button type="submit" class="btn btn-primary">Добавить</button>
                    </div>
                    <?php $form::end(); ?>

                    <?php $widget::end(); ?>

                </div>

                <? /*

            $actionData = \yii\helpers\Json::encode([
                "isOpenNewWindow" => true,
                //"size"            => 'small',
                "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                    "/cms/admin-cms-contractor/create",
                    'cms_user_id' => $model->id,
                ])->enableEmptyLayout()->enableNoActions()->url,
            ]);
            */ ?><!--
            


            <div class="sx-block-content">
                <button class="btn btn-default btn-sm" onclick='<? /*= new \yii\web\JsExpression(<<<JS
           new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
            ); */ ?>'>Добавить
            </button>
            </div>-->
            </div>


            <div class="sx-block">
                <div class="sx-block-title">Ссылки <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                      title="Ссылки на социальные сети и сайты компании"
                                                      class="far fa-question-circle"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->links as $cmsLink) : ?>
                            <div class="sx-value-row d-flex">
                                <div style="width: 100%;">
                                    <div class="sx-label">
                                        <? if ($cmsLink->name) : ?>
                                            <? echo $cmsLink->name; ?>
                                        <? else : ?>
                                            Ссылка
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <a href="<?php echo $cmsLink->url; ?>" target="_blank" data-pjax="0"><?php echo $cmsLink->url; ?></a>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-cms-company-link",
                                        'modelId'      => $cmsLink->id,
                                        'tag'          => 'div',
                                        'options'      => [
                                            'title' => 'Редактировать ссылку',
                                            'class' => 'sx-edit-btn btn btn-default',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <!--<div class="my-auto">
                                    <div class="btn btn-default" data-html="true" title="Написать письмо">
                                        <i class="far fa-envelope"></i>
                                    </div>
                                </div>-->
                            </div>
                        <? endforeach; ?>
                    </div>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-cms-company-link/create",
                            'CmsCompanyLink' => [
                                'cms_company_id' => $model->id,
                            ],
                        ])->enableEmptyLayout()->enableNoActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Добавить
                    </button>
                </div>
            </div>

            <? if ($model->managers) : ?>
                <div class="sx-block">
                    <div class="sx-block-title">Работают с компанией <i style="color: silver;" data-toggle="tooltip" data-html="true" title="Сотрудники нашей компании, которые работают с этой компанией."
                                                                        class="far fa-question-circle"></i></div>

                    <div class="sx-block-content">
                        <div class="sx-users-block">

                            <? foreach ($model->managers as $manager) : ?>
                                <?php echo \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                    'user' => $manager,
                                ]); ?>
                                <?php /*echo \yii\helpers\Html::tag("div", "Добавить", [
                                    'class' => 'btn btn-sm btn-primary sx-btn-select',
                                    'style' => '    position: absolute;
                    right: 20px;
                    top: calc(50% - 15px);',
                                    'data'  => $model->toArray(),
                                ]); */ ?>
                            <? endforeach; ?>
                        </div>
                    </div>
                </div>
            <? endif; ?>

            <!--<div class="sx-block-content">
                <button class="btn btn-default btn-sm">Добавить</button>
            </div>-->
        </div>

        <div class="col-lg-8 col-sm-6 col-12" style="padding-left: 10px;">

            <?
            $this->registerCss(<<<CSS
.sx-expired-tasks .sx-preview-card {
    flex-grow: 1;
}
CSS
            );
            
            $tasks = $model->getTasks()->expired()->orderPlanStartAt()->limit(2)->all();
            if ($tasks) :
            ?>
                <div class="sx-expired-tasks">
                
                    <? 
                    
                    foreach ($tasks as $task) : ?>
                        <div class="sx-block">
                            <div style="    display: flex
;
    width: 100%;
    align-items: center;
    justify-content: space-between;">
                                <? echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                                <? echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task]); ?>
                            </div>
                        </div>
                    <? endforeach; ?>
                    
                </div>
            <? endif; ?>


            <?php $pjax = \skeeks\cms\widgets\Pjax::begin([
                'id' => 'sx-comments',
            ]); ?>

                <div class="row">
                    <div class="col-12">
                        <div class="sx-block">
                            <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
                                'model' => $model,
                            ]); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                            'query'         => $model->getCompanyLogs()->comments(),
                            'is_show_model' => false,
                        ]); ?>
                    </div>
                </div>

            <?php $pjax::end(); ?>


        </div>
    </div>
<?php $pjax = \skeeks\cms\widgets\Pjax::end(); ?>


<?php $widget = \yii\bootstrap\Modal::begin([
    'header'       => "SMS на <span class='sx-send-sms-phone'></span>",
    'id'           => 'sx-send-sms-modal',
    'size'         => \yii\bootstrap\Modal::SIZE_DEFAULT,
    'toggleButton' => false,
]); ?>

<?php if (\Yii::$app->cms->smsProvider) : ?>

    <?php
    $form = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
        'action'               => \yii\helpers\Url::to(['send-sms', 'pk' => $model->id]),
        'enableAjaxValidation' => false,
        'clientCallback'       => new \yii\web\JsExpression(<<<JS
    function (ActiveFormAjaxSubmit) {
    ActiveFormAjaxSubmit.on('success', function(e, response) {
    $(".modal").modal("hide");
    setTimeout(function() {
        window.location.reload();
    }, 1500);
    
    });
    }
    JS
        ),
    ]); ?>

    <div class="">
        <div class="form-group" style="display:none;">
            <label class="control-label">На телефон</label>
            <input type="text" id="sx-send-sms-phone-value" name="phone" class="form-control" placeholder="">
        </div>
        <div class="form-group">
            <textarea class="form-control" name="message" placeholder="Сообщение" rows="5"></textarea>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Отправить</button>
        </div>
    </div>
    <?php $form::end(); ?>
<?php else : ?>
    <p>На вашем сайте не настроен ни один SMS провайдер.</p>
    <p>Все просто! Зайдите в <a href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-sms-provider']); ?>">этот раздел</a> и настройте отправку смс!</p>
    <p><a class="btn btn-primary" href="<?php echo \yii\helpers\Url::to(['/cms/admin-cms-sms-provider']); ?>">Настроить SMS отправку</a></p>
<?php endif; ?>

<?php $widget::end(); ?>