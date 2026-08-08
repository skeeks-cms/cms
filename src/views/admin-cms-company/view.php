<?php
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use yii\helpers\Html;

/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \skeeks\cms\models\CmsCompany */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$makeQuickAccessActionUrl = function ($route, $id) {
    return (string) \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
        $route,
        'pk' => $id,
    ])->enableEmptyLayout()->enableNoActions()->url;
};
$makeQuickAccessImageUrl = function ($model) {
    if ($model && $model->cmsImage) {
        return (string) \Yii::$app->imaging->thumbnailUrlOnRequest($model->cmsImage->src, new \skeeks\cms\components\imaging\filters\Thumbnail([
            'w' => 80,
            'h' => 80,
            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
        ]), '', true);
    }

    return null;
};

$surfaceConfig = static function (string $title, string $tooltip = ''): array {
    $config = [
        'title'      => $title,
        'raised'     => true,
        'responsive' => true,
        'options'    => ['class' => 'sx-surface--compact'],
    ];

    if ($tooltip !== '') {
        $config['actions'] = Html::tag('i', '', [
            'class'       => 'far fa-question-circle sx-hint-icon',
            'data-toggle' => 'tooltip',
            'data-html'   => 'true',
            'data-container' => 'body',
            'data-placement' => 'top',
            'tabindex'    => '0',
            'aria-label'  => $tooltip,
            'title'       => $tooltip,
        ]);
    }

    return $config;
};

$quickAccessItemsJson = \yii\helpers\Json::encode([[
    'type'   => 'companies',
    'id'     => (int) $model->id,
    'name'   => (string) $model->name,
    'url'    => \yii\helpers\Url::to(['/cms/admin-cms-company/view', 'pk' => $model->id]),
    'action' => $makeQuickAccessActionUrl('/cms/admin-cms-company/view', $model->id),
    'image'  => $makeQuickAccessImageUrl($model),
]]);

$this->registerJs(<<<JS
(function(item) {
    var attempts = 0;
    var mountFavorite = function() {
        attempts++;
        var windows = [window, window.parent, window.top, window.opener];
        var target = null;

        for (var w = 0; w < windows.length; w++) {
            try {
                var candidate = windows[w];
                if (!candidate || !candidate.sx || !candidate.sx.Project || !candidate.sx.Project.quickAccessToggleFavorite) {
                    continue;
                }

                if (candidate.document && candidate.document.querySelector('[data-sx-quick-access-edge-favorites]')) {
                    target = candidate;
                    break;
                }

                if (!target) {
                    target = candidate;
                }
            } catch (e) {
            }
        }

        var \$title = $('h1').first();
        if (!target || !\$title.length) {
            if (attempts < 10) {
                setTimeout(mountFavorite, 300);
            }
            return false;
        }

        var \$button = \$title.find('[data-sx-quick-access-favorite]').first();
        var isNewButton = !\$button.length;
        if (isNewButton) {
            \$button = $('<button type="button" class="sx-quick-access-favorite-btn" data-sx-quick-access-favorite title="Добавить в избранное"><i class="far fa-star"></i></button>');
        }
        \$button.attr('data-sx-quick-access-item', JSON.stringify(item));
        \$button.attr('data-sx-quick-access-external', '1');
        var update = function(active) {
            if (typeof active === 'undefined') {
                active = false;
                try {
                    active = target.sx.Project.quickAccessIsFavorite(item);
                } catch (e) {
                }
            }

            \$button.toggleClass('is-active', active);
            \$button.attr('title', active ? 'Убрать из избранного' : 'Добавить в избранное');
            \$button.find('i').toggleClass('fas', active).toggleClass('far', !active);
        };

        \$button.off('click.sxQuickAccessFavorite').on('click.sxQuickAccessFavorite', function(e) {
            e.preventDefault();
            e.stopPropagation();
            update(target.sx.Project.quickAccessToggleFavorite(item));
        });

        if (isNewButton) {
            \$title.append(\$button);
        }
        update();
        return true;
    };

    mountFavorite();
})({$quickAccessItemsJson}[0]);
JS
);

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


CSS
);
?>


<?php $pjax = \skeeks\cms\widgets\Pjax::begin([
    'id' => 'sx-global',
]); ?>
    <div class="sx-detail-layout">


        <div class="sx-detail-layout__aside sx-surface-stack">

            <?php if ($model->description || $model->categories || $model->cms_company_status_id) : ?>
                <?php BackendSurfaceWidget::begin($surfaceConfig('Общая информация')); ?>
                        <div class="sx-phones-block">
                            <?php if ($model->status) : ?>
                                <div class="d-flex mb-2">
                                    <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                        <div class="sx-collection-cell__secondary">
                                            Статус
                                        </div>
                                        <div class="sx-collection-cell__primary">
                                            <?php echo $model->status->name; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->categories) : ?>
                                <div class="d-flex mb-2">
                                    <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                        <div class="sx-collection-cell__secondary">
                                            Сферы деятельности
                                        </div>
                                        <div class="sx-collection-cell__primary">
                                            <?php echo implode("<br>", \yii\helpers\ArrayHelper::map($model->categories, "id", "name")); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($model->description) : ?>
                                <div class="d-flex mb-2">
                                    <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                        <div class="sx-collection-cell__secondary">
                                            Описание
                                        </div>
                                        <div class="sx-collection-cell__primary">
                                            <?php echo $model->description; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                <?php BackendSurfaceWidget::end(); ?>
            <?php endif; ?>

            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Контакты',
                'Сотрудники или люди связанные с компанией. Они являются полноценными пользователями, которые могут авторизоваться на сайте и смотерть данные по этой компании в личном кабинете.'
            )); ?>
                    <div class="sx-users-block sx-phones-block">
                        <? if ($model->cmsCompany2users) : ?>
                            <? foreach ($model->getCmsCompany2users()->orderBy(['sort' => SORT_ASC])->all() as $cmsCompany2users) : ?>
                                <div class="d-flex mb-2">
                                    <div class="w-100">
                                        <div class="my-auto">
                                            <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                                                'cmsUser' => $cmsCompany2users->cmsUser,
                                                'append'  => Html::tag('span', Html::encode($cmsCompany2users->comment), [
                                                    'class' => 'sx-collection-cell__secondary',
                                                ]),
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
                                                'class' => 'sx-icon-action',
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


            <?php BackendSurfaceWidget::end(); ?>

            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Телефон',
                'У компании может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию.'
            )); ?>
                    <div class="sx-phones-block">
                        <? foreach ($model->phones as $cmsUserPhone) : ?>
                            <?php $phoneUrl = 'tel:'.preg_replace('/[^\d+]/', '', (string) $cmsUserPhone->value); ?>
                            <div class="d-flex mb-2">
                                <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                    <div class="sx-collection-cell__secondary">
                                        <? if ($cmsUserPhone->name) : ?>
                                            <? echo $cmsUserPhone->name; ?>
                                        <? else : ?>
                                            Телефон
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-collection-cell__primary">
                                        <?php echo Html::a(Html::encode($cmsUserPhone->value), $phoneUrl, [
                                            'data-pjax' => '0',
                                        ]); ?>
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
                                            'class' => 'sx-icon-action',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <div class="my-auto d-flex">

                                    <button type="button" class="btn btn-default sx-send-sms-trigger mr-1" data-phone="<?php echo Html::encode($cmsUserPhone->value); ?>" data-html="true" title="Написать sms">
                                        <i class="fas fa-sms"></i>
                                    </button>


                                    <button type="button" class="btn btn-default sx-telephony-btn" data-value="<?php echo Html::encode($cmsUserPhone->value); ?>" data-html="true" title="Начать звонок">
                                        <i class="fas fa-phone"></i>
                                    </button>
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
            <?php BackendSurfaceWidget::end(); ?>


            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Email',
                'У пользователя может быть задано несколько email адресов. Первый из них является основным и используется по умолчанию.'
            )); ?>
                    <div class="sx-phones-block">
                        <? foreach ($model->emails as $cmsUserEmail) : ?>
                            <div class="d-flex mb-2">
                                <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                    <div class="sx-collection-cell__secondary">
                                        <? if ($cmsUserEmail->name) : ?>
                                            <? echo $cmsUserEmail->name; ?>
                                        <? else : ?>
                                            Email
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-collection-cell__primary">
                                        <?php echo Html::a(Html::encode($cmsUserEmail->value), 'mailto:'.(string) $cmsUserEmail->value, [
                                            'data-pjax' => '0',
                                        ]); ?>
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
                                            'class' => 'sx-icon-action',
                                        ],
                                    ]);
                                    ?>
                                    <!--<i class="hs-admin-angle-down"></i>-->
                                    <i class="fas fa-ellipsis-v"></i>
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>

                                </div>
                                <div class="my-auto">
                                    <a class="btn btn-default" href="<?= Html::encode('mailto:'.(string) $cmsUserEmail->value); ?>" data-pjax="0" title="Написать письмо">
                                        <i class="far fa-envelope"></i>
                                    </a>
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
            <?php BackendSurfaceWidget::end(); ?>


            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Адреса',
                'У пользователя может быть задано несколько адресов. Первый из них является основным и используется по умолчанию.'
            )); ?>
                    <div class="sx-phones-block">
                        <? foreach ($model->addresses as $cmsUserAddress) : ?>
                            <div class="d-flex mb-2">
                                <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                    <div class="sx-collection-cell__secondary">
                                        <? if ($cmsUserAddress->name) : ?>
                                            <? echo $cmsUserAddress->name; ?>
                                        <? else : ?>
                                            Адрес
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-collection-cell__primary">
                                        <?php echo Html::encode($cmsUserAddress->value); ?>
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
                                            'class' => 'sx-icon-action',
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
            <?php BackendSurfaceWidget::end(); ?>


            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Реквизиты',
                'Для оформления заказов и сделок на юридическое лицо необходимо добавить контрагента-компанию в этот раздел.'
            )); ?>
                <? foreach ($model->contractors as $cmsContractor) : ?>
                    <?php
                    $contractorContent = Html::tag('span', Html::encode($cmsContractor->asText), [
                        'class' => 'sx-collection-cell__primary',
                    ]);
                    if ($cmsContractor->inn) {
                        $contractorContent .= Html::tag('small', 'ИНН '.Html::encode($cmsContractor->inn), [
                            'class' => 'sx-collection-cell__secondary',
                        ]);
                    }
                    ?>
                    <div class="d-flex mb-2">
                        <div class="sx-collection-cell sx-collection-cell--stack w-100">
                            <div class="sx-collection-cell__secondary">
                                <? if ($cmsContractor->contractor_type) : ?>
                                    <? echo $cmsContractor->getTypeAsText(); ?>
                                <? else : ?>

                                <? endif; ?>
                            </div>
                            <div class="sx-collection-cell__primary">
                                <?php echo BackendEntityLink::widget([
                                    'controllerId' => '/cms/admin-cms-contractor',
                                    'modelId'      => $cmsContractor->id,
                                    'content'      => Html::tag('span', $contractorContent, [
                                        'class' => 'sx-collection-cell sx-collection-cell--stack',
                                    ]),
                                    'options'      => [
                                        'class'      => 'sx-preview-card__related',
                                        'aria-label' => $cmsContractor->asText,
                                    ],
                                ]); ?>
                            </div>
                        </div>

                        <div class="my-auto"><?
                            \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                'controllerId' => "/cms/admin-cms-contractor",
                                'modelId'      => $cmsContractor->id,
                                'tag'          => 'div',
                                'options'      => [
                                    'title' => 'Редактировать юр. лицо',
                                    'class' => 'sx-icon-action',
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


                <div>
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

            <?php BackendSurfaceWidget::end(); ?>


            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Ссылки',
                'Ссылки на социальные сети и сайты компании'
            )); ?>
                    <div class="sx-phones-block">
                        <? foreach ($model->links as $cmsLink) : ?>
                            <div class="d-flex mb-2">
                                <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                    <div class="sx-collection-cell__secondary">
                                        <? if ($cmsLink->name) : ?>
                                            <? echo $cmsLink->name; ?>
                                        <? else : ?>
                                            Ссылка
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-collection-cell__primary">
                                        <?php echo Html::a(Html::encode($cmsLink->url), $cmsLink->url, [
                                            'target'    => '_blank',
                                            'rel'       => 'noopener noreferrer',
                                            'data-pjax' => '0',
                                        ]); ?>
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
                                            'class' => 'sx-icon-action',
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
            <?php BackendSurfaceWidget::end(); ?>

            <? if ($model->managers) : ?>
                <?php BackendSurfaceWidget::begin($surfaceConfig(
                    'Работают с компанией',
                    'Сотрудники нашей компании, которые работают с этой компанией.'
                )); ?>
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
                <?php BackendSurfaceWidget::end(); ?>
            <? endif; ?>
        </div>

        <div class="sx-detail-layout__main sx-surface-stack">

            <?
            $this->registerCss(<<<CSS
.sx-expired-tasks .sx-preview-card {
    flex-grow: 1;
}

.sx-company-task-row {
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
CSS
            );
            
            $tasks = $model->getTasks()
                ->expired()
                ->andWhere([
                    'not in',
                    'status',
                    [
                        \skeeks\cms\models\CmsTask::STATUS_READY,
                        \skeeks\cms\models\CmsTask::STATUS_CANCELED,
                    ],
                ])
                ->orderPlanStartAt()
                ->limit(2)
                ->all();
            if ($tasks) :
            ?>
                <div class="sx-expired-tasks sx-surface-stack">
                
                    <? 
                    
                    foreach ($tasks as $task) : ?>
                        <?php BackendSurfaceWidget::begin([
                            'raised'  => true,
                            'options' => ['class' => 'sx-surface--compact'],
                        ]); ?>
                            <div class="sx-company-task-row">
                                <? echo \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $task]); ?>
                                <? echo \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $task]); ?>
                            </div>
                        <?php BackendSurfaceWidget::end(); ?>
                    <? endforeach; ?>
                    
                </div>
            <? endif; ?>


            <?php $pjax = \skeeks\cms\widgets\Pjax::begin([
                'id'      => 'sx-comments',
                'options' => ['class' => 'sx-surface-stack'],
            ]); ?>

                <?php $pinnedCompanyCommentsQuery = $model->getCompanyLogs()->comments()->pinned(); ?>
                <?php if ($pinnedCompanyCommentsQuery->count()) : ?>
                    <?php BackendSurfaceWidget::begin($surfaceConfig('Закрепленные комментарии')); ?>
                        <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                            'query'                => $pinnedCompanyCommentsQuery,
                            'is_show_model'        => false,
                            'is_show_pin_controls' => true,
                            'is_raised'             => false,
                        ]); ?>
                    <?php BackendSurfaceWidget::end(); ?>
                <?php endif; ?>

                <?php BackendSurfaceWidget::begin([
                    'raised'     => true,
                    'responsive' => true,
                ]); ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsCommentWidget::widget([
                        'model' => $model,
                    ]); ?>
                <?php BackendSurfaceWidget::end(); ?>

                <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                    'query'                => $model->getCompanyLogs()->logType([
                        \skeeks\cms\models\CmsLog::LOG_TYPE_PHONE_CALL,
                        \skeeks\cms\models\CmsLog::LOG_TYPE_COMMENT,
                    ]),
                    'is_show_model'        => false,
                    'is_show_pin_controls' => true,
                ]); ?>

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
