<?php
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
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

$surfaceConfig = static function (string $title, string $tooltip = ''): array {
    $config = [
        'title'      => $title,
        'raised'     => true,
        'responsive' => true,
        'options'    => ['class' => 'sx-surface--compact'],
    ];

    if ($tooltip !== '') {
        $config['actions'] = CrmContactBlockWidget::hintIcon($tooltip);
    }

    return $config;
};
// Contact blocks share the card density; the widget renders them as in the company and lead cards.
$contactSurface = ['options' => ['class' => 'sx-surface--compact']];
\skeeks\cms\assets\admin\CrmContactsAsset::register($this);

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


        <div class="sx-detail-layout__aside sx-surface-stack">

            <?php if ($model->companiesAll) : ?>
                <?= CrmContactBlockWidget::widget([
                    'title' => 'Компании',
                    'hint' => 'Компании с которыми связан клиент',
                    'icon' => 'building',
                    'surface' => $contactSurface,
                    'items' => array_map(static fn($company) => [
                        'label' => 'Компания',
                        'value' => BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-cms-company',
                            'modelId' => $company->id,
                            'content' => Html::encode($company->name),
                        ]),
                    ], $model->companiesAll),
                ]); ?>
            <?php endif; ?>

            <?php // "Сделка как услуга": how we can reach a client person; packages add their channels. ?>
            <?php if (!$model->is_worker) : ?>
                <?= \skeeks\cms\widgets\admin\ClientChannelsWidget::widget(['user' => $model]); ?>
            <?php endif; ?>


            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Общие данные',
                'Основные данные пользователя и его баланс.'
            )); ?>

                <?php if (isset(\Yii::$app->shop)) : ?>
                        <div class="d-flex mb-2">

                            <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                <div class="sx-collection-cell__secondary">
                                    Количество бонусов
                                </div>
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
                <?php endif; ?>


            <?php BackendSurfaceWidget::end(); ?>

            <?php
            $approvedNote = static fn($contact) => $contact->is_approved ? 'Подтверждён клиентом' : 'Не подтверждён';
            // П2а+: "Свои сделки" sees the data and contacts the client; changing it — "edit", "full", a leader, an administrator.
            $canEditData = \skeeks\cms\crm\CrmAccess::instance()->canEditClientRecord((int)\Yii::$app->user->id, $model);
            echo CrmContactBlockWidget::widget([
                'title' => 'Телефоны',
                'hint' => 'У пользователя может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию.',
                'icon' => 'phone',
                'surface' => $contactSurface,
                'items' => CrmContactBlockWidget::phoneItems($model->cmsUserPhones, '/cms/admin-user-phone', $model, ['note' => $approvedNote, 'editable' => $canEditData]),
                'emptyText' => 'Телефонов пока нет',
                'addLabel' => 'Добавить телефон',
                'addRoute' => $canEditData ? ['/cms/admin-user-phone/create', 'CmsUserPhone' => ['cms_user_id' => $model->id]] : null,
                'addSize' => 'small',
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Email',
                'hint' => 'У пользователя может быть задано несколько email адресов. Первый из них является основным и используется по умолчанию.',
                'icon' => 'mail',
                'surface' => $contactSurface,
                'items' => CrmContactBlockWidget::emailItems($model->cmsUserEmails, '/cms/admin-user-email', $model, ['note' => $approvedNote, 'editable' => $canEditData]),
                'emptyText' => 'Email пока нет',
                'addLabel' => 'Добавить email',
                'addRoute' => $canEditData ? ['/cms/admin-user-email/create', 'CmsUserEmail' => ['cms_user_id' => $model->id]] : null,
                'addSize' => 'small',
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Адреса',
                'hint' => 'У пользователя может быть задано несколько адресов. Первый из них является основным и используется по умолчанию.',
                'icon' => 'map-pin',
                'surface' => $contactSurface,
                'items' => array_map(static fn($address) => [
                    'label' => $address->name ?: 'Адрес',
                    'value' => Html::encode($address->value),
                    'note' => implode(', ', array_filter([
                        $address->entrance ? 'подъезд '.$address->entrance : null,
                        $address->floor ? 'этаж '.$address->floor : null,
                        $address->apartment_number ? 'кв./офис '.$address->apartment_number : null,
                        $address->comment ?: null,
                    ])),
                    'actions' => $canEditData ? CrmContactBlockWidget::menu('/cms/admin-user-address', (int)$address->id, 'Изменить адрес') : '',
                ], $model->cmsUserAddresses),
                'emptyText' => 'Адресов пока нет',
                'addLabel' => 'Добавить адрес',
                'addRoute' => $canEditData ? ['/cms/admin-user-address/create', 'CmsUserAddress' => ['cms_user_id' => $model->id]] : null,
            ]);
            ?>

            <?php BackendSurfaceWidget::begin($surfaceConfig(
                'Информация',
                'Общая информация по пользователю, есть возможность создать любое количество полей с данными.'
            )); ?>
                    <?
                    $eav = $model->relatedPropertiesModel;
                    //$eav->initAllProperties();
                    //print_r($eav->toArray());die;
                    //print_r($model->relatedProperties);die;
                    ?>
                    <? if ($eav->toArray()) : ?>
                        <? foreach ($eav->toArray() as $key => $value) : ?>
                            <? if ($value) : ?>
                                <div class="d-flex mb-2">
                                    <div class="sx-collection-cell sx-collection-cell--stack w-100">
                                        <div class="sx-collection-cell__secondary">
                                            <? echo $eav->getAttributeLabel($key); ?>
                                        </div>
                                        <div class="sx-collection-cell__primary">
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
                    <?php if ($canEditData) : ?>
                    <button type="button" class="sx-contact-add" onclick='<?= new \yii\web\JsExpression(<<<JS
               new sx.classes.backend.widgets.Action({$actionData}).go(); return false;
JS
                    ); ?>'><?= CrmContactBlockWidget::icon('edit'); ?> Редактировать
                    </button>
                    <?php endif; ?>
            <?php BackendSurfaceWidget::end(); ?>

            <?php
            // Full contractor form with "fill by INN"; an INN already in the base is only linked.
            echo CrmContactBlockWidget::widget([
                'title' => 'Реквизиты',
                'hint' => 'Для оформления заказов и сделок на юридическое лицо необходимо добавить контрагента-компанию в этот раздел.',
                'icon' => 'id-card',
                'surface' => $contactSurface,
                'items' => array_map(static fn($contractor) => [
                    'label' => $contractor->contractor_type ? $contractor->getTypeAsText() : 'Реквизиты',
                    'value' => BackendEntityLink::widget([
                        'controllerId' => '/cms/admin-cms-contractor',
                        'modelId' => $contractor->id,
                        'content' => Html::encode($contractor->asText),
                    ]),
                    'note' => $contractor->inn ? 'ИНН '.$contractor->inn : null,
                    'actions' => $canEditData ? CrmContactBlockWidget::menu('/cms/admin-cms-contractor', (int)$contractor->id, 'Изменить реквизиты') : '',
                ], $model->cmsContractors),
                'emptyText' => 'Реквизитов пока нет',
                'addLabel' => 'Добавить реквизиты',
                'addRoute' => $canEditData ? ['/cms/admin-cms-user-contractor/create', 'user_id' => $model->id] : null,
            ]);
            ?>

            <?php // Blocks of other packages (КП and others), CrmCardBlocksWidget::EVENT_BLOCKS. ?>
            <?= \skeeks\cms\widgets\admin\CrmCardBlocksWidget::widget(['model' => $model]); ?>

            <? if ($model->managers) : ?>
                <?php BackendSurfaceWidget::begin($surfaceConfig(
                    'Работают с клиентом',
                    'Сотрудники нашей компании, которые работают с этим клиентом.'
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
            <?php $pjax = \skeeks\cms\widgets\Pjax::begin([
                'id'      => 'sx-comments',
                'options' => ['class' => 'sx-surface-stack sx-activity-thread'],
            ]); ?>

                <?php // One block of actions over the feed, as in the deal card; a task, a call and a letter — for a client, not an employee. ?>
                <?php echo \skeeks\cms\widgets\admin\CrmCardActionsWidget::widget([
                    'model'      => $model,
                    'commentUrl' => ['/cms/admin-cms-log/add-comment'],
                    'actions'    => $model->is_worker ? [] : (\Yii::$app->user->can('cms/admin-task') ? ['task' => ['label' => 'Задача', 'icon' => 'plus', 'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                        '/cms/admin-cms-task/create', 'CmsTask' => ['cms_user_id' => $model->id],
                    ])->enableEmptyLayout()->enableNoActions()->url]] : [])
                        + \skeeks\cms\widgets\admin\CrmCardActionsWidget::contactTabs($model, $model->cmsUserPhones, $model->cmsUserEmails),
                ]); ?>

                <?php // Pinned entries once, right under the block, marked as in the feed; the feed below skips them. ?>
                <?php $pinnedUserLogsQuery = $model->getUserLogs()->activity()->pinned(); ?>
                <?php if ($pinnedUserLogsQuery->count()) : ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                        'query'                => $pinnedUserLogsQuery,
                        'is_show_model'        => false,
                        'is_show_pin_controls' => true,
                    ]); ?>
                <?php endif; ?>

                        <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                            // His deals' records too, signed by the deal (CmsLogListWidget::$ownModel).
                            'query'         => $model->getCardLogs()->activity()->notPinnedOf($model),
                            'is_show_model' => false,
                            'ownModel'      => $model,
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
