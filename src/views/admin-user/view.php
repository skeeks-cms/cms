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
.sx-block .sx-block-content {
    /*padding: 10px;
    background: #f9f9f9;*/
}

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


<?php $pjax = \skeeks\cms\widgets\Pjax::begin(); ?>
    <div class="row">


        <div class="col-lg-4 col-sm-6 col-12">

            <?php if ($model->companiesAll) : ?>
                <div class="sx-block">
                    <div class="sx-block-title">Компании <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                            title="Компании с которыми связан клиент"
                                                            class="far fa-question-circle"></i>
                    </div>
                    <div class="sx-block-content">

                    <?php foreach ($model->companiesAll as $company) : ?>
                        <div class="sx-value-row d-flex">
                            <div style="width: 100%;">
                                <?php echo $company->name; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>


            <div class="sx-block">
                <div class="sx-block-title">Общие данные <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                            title="У пользователя может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию."
                                                            class="far fa-question-circle"></i>
                </div>

                <?php if (isset(\Yii::$app->shop)) : ?>
                    <div class="sx-block-content">
                        <div class="sx-value-row d-flex">

                            <div style="width: 100%;">
                                Количество бонусов
                            </div>

                            <div class="my-auto">
                                <?php if ($model->bonusBalance > 0) : ?>
                                    <a href="<?php echo \yii\helpers\Url::to(['bonus', 'pk' => $model->id]); ?>" data-pjax="0" style="color: green;">
                                        +&nbsp;<?php echo $model->bonusBalance ? \Yii::$app->formatter->asDecimal($model->bonusBalance) : "нет бонусов"; ?>
                                    </a>
                                <?php elseif ($model->bonusBalance < 0) : ?>
                                    <a href="<?php echo \yii\helpers\Url::to(['bonus', 'pk' => $model->id]); ?>" data-pjax="0" style="color: red;">
                                        -&nbsp;<?php echo $model->bonusBalance ? \Yii::$app->formatter->asDecimal($model->bonusBalance) : "нет бонусов"; ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo \yii\helpers\Url::to(['bonus', 'pk' => $model->id]); ?>" data-pjax="0">
                                        нет&nbsp;бонусов
                                    </a>
                                <?php endif; ?>


                            </div>
                        </div>
                    </div>
                <?php endif; ?>


            </div>

            <div class="sx-block">
                <div class="sx-block-title">Телефон <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                       title="У пользователя может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию."
                                                       class="far fa-question-circle"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserPhones as $cmsUserPhone) : ?>
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
                                        <?php if ($cmsUserPhone->is_approved) : ?>
                                            <span data-html="true" data-toggle="tooltip" style="color: green;"
                                                  title="Телефон подтвержден пользователем<br />Пользователь реально получил код на этот телефон и подтвердил его.">✓</span>
                                        <?php else : ?>
                                            <span data-html="true" data-toggle="tooltip" style="color: silver; font-size: 12px;" title="Пользователь не подтверждал телефон."><i class="far fa-question-circle"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-user-phone",
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
                            "/cms/admin-user-phone/create",
                            'CmsUserPhone' => [
                                'cms_user_id' => $model->id,
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
                        <? foreach ($model->cmsUserEmails as $cmsUserEmail) : ?>
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
                                        <?php if ($cmsUserEmail->is_approved) : ?>
                                            <span data-html="true" data-toggle="tooltip" style="color: green;"
                                                  title="Email подтвержден пользователем<br />Пользователь реально получил код на этот email и подтвердил его.">✓</span>
                                        <?php else : ?>
                                            <span data-html="true" data-toggle="tooltip" style="color: silver; font-size: 12px;" title="Пользователь не подтверждал email."><i class="far fa-question-circle"></i></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="my-auto">
                                    <?
                                    \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => "/cms/admin-user-email",
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
                            "/cms/admin-user-email/create",
                            'CmsUserEmail' => [
                                'cms_user_id' => $model->id,
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
                        <? foreach ($model->cmsUserAddresses as $cmsUserAddress) : ?>
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
                                        'controllerId' => "/cms/admin-user-address",
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
                            "/cms/admin-user-address/create",
                            'CmsUserAddress' => [
                                'cms_user_id' => $model->id,
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
                <div class="sx-block-title">Информация <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                          title="Общая информация по пользователю, есть возможность создать любое количество полей с данными." class="far fa-question-circle"></i></div>
                <div class="sx-block-content">
                    <?
                    $eav = $model->relatedPropertiesModel;
                    //$eav->initAllProperties();
                    //print_r($eav->toArray());die;
                    //print_r($model->relatedProperties);die;
                    ?>
                    <? if ($eav->toArray()) : ?>
                        <? foreach ($eav->toArray() as $key => $value) : ?>
                            <? if ($value) : ?>
                                <div class="sx-value-row d-flex">
                                    <div style="width: 100%;">
                                        <div class="sx-label">
                                            <? echo $eav->getAttributeLabel($key); ?>
                                        </div>
                                        <div class="sx-value">
                                            <?php echo $eav->getSmartAttribute($key); ?>
                                        </div>
                                    </div>
                                </div>
                            <? endif; ?>

                        <? endforeach; ?>
                    <? endif; ?>
                    <?

                    $actionData = \yii\helpers\Json::encode([
                        "isOpenNewWindow" => true,
                        "size"            => 'small',
                        "url"             => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                            "/cms/admin-user/update-eav",
                            'pk' => $model->id,
                        ])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
                    ]);
                    ?>
                    <button class="btn btn-default btn-sm" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'>Редактировать
                    </button>
                </div>
            </div>

            <div class="sx-block">
                <div class="sx-block-title">Реквизиты <i style="color: silver;" data-toggle="tooltip" data-html="true"
                                                         title="Для оформления заказов и сделок на юридическое лицо необходимо добавить контрагента-компанию в этот раздел." class="far fa-question-circle"></i></div>


                <? foreach ($model->cmsContractors as $cmsContractor) : ?>
                    <div class="sx-value-row d-flex">
                        <div style="width: 100%;">
                            <div class="sx-label">
                                <? if ($cmsContractor->contractor_type) : ?>
                                    <? echo $cmsContractor->getTypeAsText(); ?>
                                <? else : ?>

                                <? endif; ?>
                            </div>
                            <div class="sx-value">
                                <a href="#"><?php echo $cmsContractor->asText; ?></a>
                            </div>
                        </div>

                        <div class="my-auto">
                            <?
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

            <? if ($model->managers) : ?>
                <div class="sx-block">
                    <div class="sx-block-title">Работают с клиентом <i style="color: silver;" data-toggle="tooltip" data-html="true" title="Сотрудники нашей компании, которые работают с этой компанией."
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
                            'query'         => $model->getUserLogs()->comments(),
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