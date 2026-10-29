<?php
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
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
        $config['actions'] = CrmContactBlockWidget::hintIcon($tooltip);
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

            <?php
            //"Сделка как услуга": people of the client with their role in the company and grants in its deals.
            $accessReady = \skeeks\cms\models\queries\CmsDealQuery::dealAccessReady();
            // П2а+: "Свои сделки" sees the data and contacts the client; changing it — "edit", "full", a leader, an administrator.
            $canEditData = \skeeks\cms\crm\CrmAccess::instance()->canEditClientRecord((int)\Yii::$app->user->id, $model);
            $canManageAccess = $accessReady && $canEditData && \Yii::$app->user->can("cms/admin-company/manage", ['model' => $model]);
            $accessByUser = [];
            if ($accessReady) {
                \skeeks\cms\assets\admin\ClientAccessAsset::register($this);
                foreach (\skeeks\cms\models\CmsClientAccess::find()->with('cmsDealRole')->andWhere(['cms_company_id' => (int)$model->id])->all() as $grant) {
                    $accessByUser[(int)$grant->cms_user_id][] = $grant;
                }
            }
            ?>
            <?php BackendSurfaceWidget::begin($surfaceConfig(
                $accessReady ? 'Люди клиента и доступы' : 'Контакты',
                $accessReady
                    ? 'Люди компании и что они видят в личном кабинете: полный доступ ко всем услугам или только выбранные услуги. Нажмите на человека, чтобы выдать роль в услугах.'
                    : 'Сотрудники или люди связанные с компанией. Они являются полноценными пользователями, которые могут авторизоваться на сайте и смотерть данные по этой компании в личном кабинете.'
            )); ?>
                    <?php // Person rows keep their avatar but share the contact row look of the card. ?>
                    <div class="sx-contact-list">
                        <?php foreach ($model->getCmsCompany2users()->orderBy(['sort' => SORT_ASC])->all() as $cmsCompany2users) : ?>
                            <?php
                            $access = '';
                            $personUrl = '';
                            if ($accessReady) {
                                $grants = $accessByUser[(int)$cmsCompany2users->cms_user_id] ?? [];
                                $companyGrant = null;
                                $dealCount = 0;
                                foreach ($grants as $grant) {
                                    if ($grant->cms_deal_id) {
                                        $dealCount++;
                                    } else {
                                        $companyGrant = $grant;
                                    }
                                }
                                if ($companyGrant && $companyGrant->is_full) {
                                    $badges = [Html::tag('span', 'Полный доступ ко всем услугам', ['class' => 'sx-deal-badge sx-deal-badge--accent'])];
                                } else {
                                    $badges = [];
                                    if ($companyGrant) {
                                        $badges[] = Html::tag('span', 'Во всех услугах: '.Html::encode($companyGrant->cmsDealRole ? $companyGrant->cmsDealRole->name : 'настроено вручную'), ['class' => 'sx-deal-badge sx-deal-badge--accent']);
                                    }
                                    $badges[] = Html::tag('span', $dealCount ? 'Выбранные услуги: '.$dealCount : ($companyGrant ? '' : 'Нет доступа к услугам'), ['class' => 'sx-deal-badge']);
                                }
                                $access = Html::tag('span', implode('', array_filter($badges, static function ($badge) {
                                    return strip_tags($badge) !== '';
                                })), ['class' => 'sx-company-person__access']);
                                if ($canManageAccess) {
                                    $personUrl = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams(['/cms/admin-cms-company/client-person', 'pk' => $model->id, 'user' => $cmsCompany2users->cms_user_id])
                                        ->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url;
                                }
                            }
                            ?>
                            <div class="sx-contact-row<?= $personUrl ? ' sx-company-person' : ''; ?>"<?= $personUrl ? ' data-sx-person="'.Html::encode($personUrl).'" title="Доступ к услугам компании"' : ''; ?>>
                                <div class="sx-contact-body">
                                    <?= \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                                        'cmsUser' => $cmsCompany2users->cmsUser,
                                        'append'  => ($cmsCompany2users->comment
                                            ? Html::tag('span', Html::encode($cmsCompany2users->comment), ['class' => 'sx-contact-note'])
                                            : '').$access,
                                    ]); ?>
                                </div>
                                <div class="sx-contact-actions">
                                    <?= $canEditData ? CrmContactBlockWidget::menu('/cms/admin-cms-company2user', (int)$cmsCompany2users->id, 'Изменить контакт') : ''; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$model->cmsCompany2users) : ?><div class="sx-contact-empty">Контактов пока нет</div><?php endif; ?>
                    </div>
                    <?php if ($accessReady) : ?>
                        <?php
                        //A click on a person opens the window of their access; the "⋮" menu and links keep their own behavior.
                        $this->registerJs(<<<JS
$(document).off('click.sxCompanyPerson').on('click.sxCompanyPerson', '[data-sx-person]', function (e) {
    if ($(e.target).closest('a, button, .sx-icon-action, [data-sx-ajax-actions], .dropdown-menu').length) {
        return;
    }
    new sx.classes.backend.widgets.Action({isOpenNewWindow: true, url: $(this).data('sx-person')}).go();
});
JS
                        );
                        ?>
                    <?php endif; ?>

                    <?= $canEditData ? $this->render('_add-person', ['model' => $model, 'accessReady' => $accessReady]) : ''; ?>


            <?php BackendSurfaceWidget::end(); ?>

            <?php
            $contactSurface = ['options' => ['class' => 'sx-surface--compact']];

            echo CrmContactBlockWidget::widget([
                'title' => 'Телефоны',
                'hint' => 'У компании может быть задано несколько телефонов. Первый из них является основным и используется по умолчанию.',
                'icon' => 'phone',
                'surface' => $contactSurface,
                'items' => CrmContactBlockWidget::phoneItems($model->phones, '/cms/admin-cms-company-phone', $model, ['editable' => $canEditData]),
                'emptyText' => 'Телефонов пока нет',
                'addLabel' => 'Добавить телефон',
                'addRoute' => $canEditData ? ['/cms/admin-cms-company-phone/create', 'CmsCompanyPhone' => ['cms_company_id' => $model->id]] : null,
                'addSize' => 'small',
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Email',
                'hint' => 'У компании может быть задано несколько email адресов. Первый из них является основным и используется по умолчанию.',
                'icon' => 'mail',
                'surface' => $contactSurface,
                'items' => CrmContactBlockWidget::emailItems($model->emails, '/cms/admin-cms-company-email', $model, ['editable' => $canEditData]),
                'emptyText' => 'Email пока нет',
                'addLabel' => 'Добавить email',
                'addRoute' => $canEditData ? ['/cms/admin-cms-company-email/create', 'CmsCompanyEmail' => ['cms_company_id' => $model->id]] : null,
                'addSize' => 'small',
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Адреса',
                'hint' => 'У компании может быть задано несколько адресов. Первый из них является основным и используется по умолчанию.',
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
                    'actions' => $canEditData ? CrmContactBlockWidget::menu('/cms/admin-cms-company-address', (int)$address->id, 'Изменить адрес') : '',
                ], $model->addresses),
                'emptyText' => 'Адресов пока нет',
                'addLabel' => 'Добавить адрес',
                'addRoute' => $canEditData ? ['/cms/admin-cms-company-address/create', 'CmsCompanyAddress' => ['cms_company_id' => $model->id]] : null,
            ]);

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
                ], $model->contractors),
                'emptyText' => 'Реквизитов пока нет',
                'addLabel' => 'Добавить реквизиты',
                'addRoute' => $canEditData ? ['/cms/admin-cms-company-contractor/create', 'company_id' => $model->id] : null,
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Ссылки',
                'hint' => 'Ссылки на социальные сети и сайты компании',
                'icon' => 'link',
                'surface' => $contactSurface,
                'items' => array_map(static fn($link) => [
                    'label' => $link->name ?: $link->linkTypeAsText ?: 'Ссылка',
                    'value' => Html::a(Html::encode($link->url), $link->url, ['target' => '_blank', 'rel' => 'noopener noreferrer', 'data-pjax' => '0']),
                    'actions' => $canEditData ? CrmContactBlockWidget::menu('/cms/admin-cms-company-link', (int)$link->id, 'Изменить ссылку') : '',
                ], $model->links),
                'emptyText' => 'Ссылок пока нет',
                'addLabel' => 'Добавить ссылку',
                'addRoute' => $canEditData ? ['/cms/admin-cms-company-link/create', 'CmsCompanyLink' => ['cms_company_id' => $model->id]] : null,
                'addSize' => 'small',
            ]);
            ?>
            <?php // Blocks of other packages (КП and others), CrmCardBlocksWidget::EVENT_BLOCKS. ?>
            <?= \skeeks\cms\widgets\admin\CrmCardBlocksWidget::widget(['model' => $model]); ?>
            <?php // П2а+: who works with the client — the main responsible first, levels, add and remove (views/crm/_access-block.php). ?>
            <?= $this->render('@skeeks/cms/views/crm/_access-block', [
                'model' => $model,
                'controllerRoute' => '/'.$this->context->uniqueId,
                'surface' => $surfaceConfig('Работают с компанией', 'Сотрудники нашей компании, которые работают с этой компанией. Уровень доступа и основной ответственный — во вкладке «Доступ».'),
            ]); ?>
        </div>

        <div class="sx-detail-layout__main sx-surface-stack">
            <?php // П2д: a client of the general pool — «Взять в работу» at the top (views/crm/_pool-banner.php). ?>
            <?= $this->render('@skeeks/cms/views/crm/_pool-banner', ['model' => $model, 'controllerRoute' => '/'.$this->context->uniqueId]); ?>

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
                'options' => ['class' => 'sx-surface-stack sx-activity-thread'],
            ]); ?>

                <?php // One block of actions over the feed, as in the deal card; through his deals for «Свои сделки» (CrmCardActionsWidget::client()). ?>
                <?php echo \skeeks\cms\widgets\admin\CrmCardActionsWidget::client($model, $model->phones, $model->emails); ?>

                <?php // Pinned entries once, right under the block, marked as in the feed; the feed below skips them. ?>
                <?php $pinnedCompanyLogsQuery = $model->getCompanyLogs()->activity()->pinned(); ?>
                <?php if ($pinnedCompanyLogsQuery->count()) : ?>
                    <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                        'query'                => $pinnedCompanyLogsQuery,
                        'is_show_model'        => false,
                        'is_show_pin_controls' => true,
                    ]); ?>
                <?php endif; ?>

                <?php echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
                    // Its deals' records too, signed by the deal (CmsLogListWidget::$ownModel).
                    'query'                => $model->getCardLogs()->activity()->notPinnedOf($model),
                    'is_show_model'        => false,
                    'ownModel'             => $model,
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
