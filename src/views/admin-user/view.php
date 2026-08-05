<?php
use skeeks\cms\backend\widgets\BackendEntityLink;
use yii\helpers\Html;

/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\backend\controllers\BackendModelController */
/* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;
$model = $action->model;

$makeQuickAccessActionUrl = function ($route, $id) {
    return (string) \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
        $route,
        'pk' => $id,
    ])->enableEmptyLayout()->enableNoActions()->url;
};

if (!$model->is_worker) {
    $quickAccessItemsJson = \yii\helpers\Json::encode([[
        'type'   => 'clients',
        'id'     => (int) $model->id,
        'name'   => trim((string) $model->shortDisplayName),
        'url'    => \yii\helpers\Url::to(['/cms/admin-user/view', 'pk' => $model->id]),
        'action' => $makeQuickAccessActionUrl('/cms/admin-user/view', $model->id),
        'image'  => (string) $model->avatarSrc,
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
}

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


<?php $pjax = \skeeks\cms\widgets\Pjax::begin(); ?>
    <div class="sx-detail-layout">


        <div class="sx-detail-layout__aside">

            <?php if ($model->companiesAll) : ?>
                <div class="sx-block">
                    <div class="sx-block-title">Компании <i data-toggle="tooltip" data-html="true"
                                                             title="Компании с которыми связан клиент"
                                                             class="far fa-question-circle sx-hint-icon"></i>
                    </div>
                    <div class="sx-block-content">

                    <?php foreach ($model->companiesAll as $company) : ?>
                        <div class="sx-value-row d-flex">
                            <div class="w-100">
                                <?= BackendEntityLink::widget([
                                    'controllerId' => '/cms/admin-cms-company',
                                    'modelId'      => $company->id,
                                    'content'      => Html::encode($company->name),
                                    'options'      => [
                                        'class'      => 'sx-preview-card__related',
                                        'aria-label' => $company->name,
                                    ],
                                ]); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>


            <div class="sx-block">
                <div class="sx-block-title">Общие данные <i data-toggle="tooltip" data-html="true"
                                                             title="У пользователя может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию."
                                                             class="far fa-question-circle sx-hint-icon"></i>
                </div>

                <?php if (isset(\Yii::$app->shop)) : ?>
                    <div class="sx-block-content">
                        <div class="sx-value-row d-flex">

                            <div class="w-100">
                                Количество бонусов
                            </div>

                            <div class="my-auto">
                                <?php if ($model->bonusBalance > 0) : ?>
                                    <a class="sx-collection-cell__amount sx-text--success" href="<?php echo \yii\helpers\Url::to(['bonus', 'pk' => $model->id]); ?>" data-pjax="0">
                                        +&nbsp;<?php echo $model->bonusBalance ? \Yii::$app->formatter->asDecimal($model->bonusBalance) : "нет бонусов"; ?>
                                    </a>
                                <?php elseif ($model->bonusBalance < 0) : ?>
                                    <a class="sx-collection-cell__amount sx-text--danger" href="<?php echo \yii\helpers\Url::to(['bonus', 'pk' => $model->id]); ?>" data-pjax="0">
                                        -&nbsp;<?php echo $model->bonusBalance ? \Yii::$app->formatter->asDecimal($model->bonusBalance) : "нет бонусов"; ?>
                                    </a>
                                <?php else : ?>
                                    <a class="sx-collection-cell__amount sx-text--muted" href="<?php echo \yii\helpers\Url::to(['bonus', 'pk' => $model->id]); ?>" data-pjax="0">
                                        нет&nbsp;бонусов
                                    </a>
                                <?php endif; ?>


                            </div>
                        </div>
                    </div>
                <?php endif; ?>


            </div>

            <div class="sx-block">
                <div class="sx-block-title">Телефон <i data-toggle="tooltip" data-html="true"
                                                       title="У пользователя может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию."
                                                       class="far fa-question-circle sx-hint-icon"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserPhones as $cmsUserPhone) : ?>
                            <div class="sx-value-row d-flex">
                                <div class="w-100">
                                    <div class="sx-label">
                                        <? if ($cmsUserPhone->name) : ?>
                                            <? echo $cmsUserPhone->name; ?>
                                        <? else : ?>
                                            Телефон
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <?= Html::a(
                                            Html::encode($cmsUserPhone->value),
                                            'tel:' . preg_replace('/[^\d+]/', '', $cmsUserPhone->value)
                                        ); ?>
                                        <?php if ($cmsUserPhone->is_approved) : ?>
                                            <span class="sx-text--success" data-html="true" data-toggle="tooltip"
                                                  title="Телефон подтвержден пользователем<br />Пользователь реально получил код на этот телефон и подтвердил его.">✓</span>
                                        <?php else : ?>
                                            <span class="sx-text--muted" data-html="true" data-toggle="tooltip" title="Пользователь не подтверждал телефон."><i class="far fa-question-circle"></i></span>
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

                                    <button type="button" class="btn btn-default sx-send-sms-trigger mr-1"
                                            data-phone="<?= Html::encode($cmsUserPhone->value); ?>"
                                            data-html="true" title="Написать sms">
                                        <i class="fas fa-sms"></i>
                                    </button>


                                    <button type="button" class="btn btn-default sx-telephony-btn"
                                            data-value="<?= Html::encode($cmsUserPhone->value); ?>"
                                            data-html="true" title="Начать звонок">
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
                <div class="sx-block-title">Email <i data-toggle="tooltip" data-html="true"
                                                     title="У пользователя может быть задано несколько email адресов. Первый из них является основным и используется по умолчанию."
                                                     class="far fa-question-circle sx-hint-icon"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserEmails as $cmsUserEmail) : ?>
                            <div class="sx-value-row d-flex">
                                <div class="w-100">
                                    <div class="sx-label">
                                        <? if ($cmsUserEmail->name) : ?>
                                            <? echo $cmsUserEmail->name; ?>
                                        <? else : ?>
                                            Email
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <?= Html::a(Html::encode($cmsUserEmail->value), 'mailto:' . $cmsUserEmail->value); ?>
                                        <?php if ($cmsUserEmail->is_approved) : ?>
                                            <span class="sx-text--success" data-html="true" data-toggle="tooltip"
                                                  title="Email подтвержден пользователем<br />Пользователь реально получил код на этот email и подтвердил его.">✓</span>
                                        <?php else : ?>
                                            <span class="sx-text--muted" data-html="true" data-toggle="tooltip" title="Пользователь не подтверждал email."><i class="far fa-question-circle"></i></span>
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
                                    <a class="btn btn-default" href="mailto:<?= Html::encode($cmsUserEmail->value); ?>" data-html="true" title="Написать письмо">
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
                <div class="sx-block-title">Адреса <i data-toggle="tooltip" data-html="true"
                                                      title="У пользователя может быть задано несколько адресов. Первый из них является основным и используется по умолчанию."
                                                      class="far fa-question-circle sx-hint-icon"></i>
                </div>
                <div class="sx-block-content">
                    <div class="sx-phones-block">
                        <? foreach ($model->cmsUserAddresses as $cmsUserAddress) : ?>
                            <div class="sx-value-row d-flex">
                                <div class="w-100">
                                    <div class="sx-label">
                                        <? if ($cmsUserAddress->name) : ?>
                                            <? echo $cmsUserAddress->name; ?>
                                        <? else : ?>
                                            Адрес
                                        <? endif; ?>
                                    </div>
                                    <div class="sx-value">
                                        <?= Html::encode($cmsUserAddress->value); ?>
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
                <div class="sx-block-title">Информация <i data-toggle="tooltip" data-html="true"
                                                          title="Общая информация по пользователю, есть возможность создать любое количество полей с данными." class="far fa-question-circle sx-hint-icon"></i></div>
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
                                    <div class="w-100">
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
                <div class="sx-block-title">Реквизиты <i data-toggle="tooltip" data-html="true"
                                                         title="Для оформления заказов и сделок на юридическое лицо необходимо добавить контрагента-компанию в этот раздел." class="far fa-question-circle sx-hint-icon"></i></div>


                <? foreach ($model->cmsContractors as $cmsContractor) : ?>
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
                    <div class="sx-value-row d-flex">
                        <div class="w-100">
                            <div class="sx-label">
                                <? if ($cmsContractor->contractor_type) : ?>
                                    <? echo $cmsContractor->getTypeAsText(); ?>
                                <? else : ?>

                                <? endif; ?>
                            </div>
                            <div class="sx-value">
                                <?= BackendEntityLink::widget([
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

                    <div class="d-flex flex-row pb-3">
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
                    <div class="sx-block-title">Работают с клиентом <i data-toggle="tooltip" data-html="true" title="Сотрудники нашей компании, которые работают с этой компанией."
                                                                       class="far fa-question-circle sx-hint-icon"></i></div>

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

        <div class="sx-detail-layout__main">
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
                            'query'         => $model->getUserLogs()->logType([
                                \skeeks\cms\models\CmsLog::LOG_TYPE_PHONE_CALL,
                                \skeeks\cms\models\CmsLog::LOG_TYPE_COMMENT
                            ]),
                            'is_show_model' => false,
                            'is_show_pin_controls' => true,
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
