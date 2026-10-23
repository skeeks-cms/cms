<?php

use skeeks\cms\assets\admin\LeadMatchesAsset;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\telephony\widgets\TelephonyWidget;
use skeeks\cms\widgets\admin\CmsCommentWidget;
use skeeks\cms\widgets\admin\CmsLogListWidget;
use skeeks\cms\widgets\admin\CrmContactBlockWidget;
use skeeks\cms\widgets\Pjax;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/** @var CmsLead $model */
$this->title = 'Лид №'.(int)$model->id;
$canWork = $model->canBeWorkedBy((int)\Yii::$app->user->id);
$sourceData = (array)$model->source_data;
$formSendId = $model->source_type === CmsLead::SOURCE_FORM
    ? (int)ArrayHelper::getValue($sourceData, 'form_send_id', $model->source_ref)
    : 0;
$description = trim((string)$model->description);
if ($model->source_type === CmsLead::SOURCE_FORM && $description !== '') {
    $descriptionLines = preg_split('/\R/u', $description) ?: [];
    $descriptionLines = array_filter($descriptionLines, static function (string $line): bool {
        return !preg_match('/^(ID отправки формы|Страница отправки|UTM (source|medium|campaign|content|term))\s*:/ui', trim($line));
    });
    $description = trim(implode("\n", $descriptionLines));
}
$utmValues = [];
foreach (CmsLead::utmLabels() as $attribute => $label) {
    $value = trim((string)$model->{$attribute});
    if ($value !== '') {
        $utmValues[$label] = $value;
    }
}
$sourceTypeName = CmsLead::sources()[$model->source_type] ?? $model->source_type;
$sourceName = trim((string)$model->source_name);
$sourceReferrer = trim((string)ArrayHelper::getValue($sourceData, 'request.referer', ''));
TelephonyWidget::widget();
LeadMatchesAsset::register($this);
$identityCard = static function (
    string $controllerId,
    int $modelId,
    string $label,
    string $title,
    string $icon,
    array $meta = []
): string {
    $metaHtml = '';
    foreach (array_filter($meta, static fn($value) => trim((string)$value) !== '') as $value) {
        $metaHtml .= Html::tag('div', Html::encode((string)$value), ['class' => 'sx-lead-identity-card__meta']);
    }

    $content = Html::tag('div',
        Html::tag('span', Html::tag('i', '', ['class' => $icon, 'aria-hidden' => 'true']), [
            'class' => 'sx-lead-identity-card__icon',
        ])
        .Html::tag('span',
            Html::tag('span', Html::encode($label), ['class' => 'sx-lead-identity-card__label'])
            .Html::tag('span', Html::encode($title), ['class' => 'sx-lead-identity-card__title'])
            .$metaHtml,
            ['class' => 'sx-lead-identity-card__body']
        )
        .Html::tag('i', '', ['class' => 'fas fa-chevron-right sx-lead-identity-card__arrow', 'aria-hidden' => 'true']),
        ['class' => 'sx-surface sx-surface--raised sx-lead-identity-card']
    );

    return BackendEntityLink::widget([
        'controllerId' => $controllerId,
        'modelId' => $modelId,
        'content' => $content,
        'options' => ['class' => 'sx-lead-identity-card-link'],
    ]);
};
$this->registerJs(<<<JS
$(document).off('click.sxLeadSms', '.sx-send-sms-trigger').on('click.sxLeadSms', '.sx-send-sms-trigger', function() {
    $('#sx-send-sms-modal').modal('show');
    $('.sx-send-sms-phone').text($(this).data('phone'));
    $('#sx-send-sms-phone-value').val($(this).data('phone'));
    return false;
});
JS
);
$this->registerCss(<<<CSS
.sx-lead-layout > * {
    min-width: 0;
}
.sx-lead-side .sx-properties {
    padding: 0;
}
.sx-lead-description {
    min-width: 0;
    overflow-wrap: anywhere;
}
.sx-lead-matches__loading,
.sx-lead-matches__error {
    color: var(--sx-color-text-muted);
}
.sx-lead-matches__loading {
    padding: 1rem;
}
.sx-lead-matches__error {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.sx-lead-matches__list,
.sx-lead-match,
.sx-lead-match__main,
.sx-lead-match__relations {
    display: grid;
    gap: 0.75rem;
}
.sx-lead-match {
    min-width: 0;
    padding-block: 1rem;
    border-bottom: 1px solid var(--sx-color-border);
}
.sx-lead-match:first-child {
    padding-top: 0;
}
.sx-lead-match:last-child {
    padding-bottom: 0;
    border-bottom: 0;
}
.sx-lead-match__kind,
.sx-lead-match__reason,
.sx-lead-match__relations-title,
.sx-lead-matches__notice {
    color: var(--sx-color-text-muted);
    font-size: 0.875rem;
}
.sx-lead-match__relation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    min-width: 0;
    padding: 0.75rem;
    background: var(--sx-color-surface-muted);
    border-radius: 0.75rem;
}
.sx-lead-match__form {
    margin: 0;
}
.sx-lead-matches__notice {
    margin: 0;
}
.sx-lead-matches-slot {
    min-width: 0;
}
.sx-lead-matches-slot .sx-lead-matches__loading {
    padding: 1rem 1.25rem;
    color: var(--sx-color-text-muted);
    border: 1px solid var(--sx-color-border);
    border-radius: var(--sx-surface-radius);
    background: var(--sx-color-surface);
}
.sx-lead-matches-surface {
    color: var(--sx-color-success-on-soft);
    border: 1px solid var(--sx-color-success);
    background: var(--sx-color-success-soft);
}
.sx-lead-matches-surface .sx-surface__title,
.sx-lead-matches-surface .sx-surface__hint,
.sx-lead-matches-surface .sx-collection-cell__secondary,
.sx-lead-matches-surface .sx-lead-match__reason,
.sx-lead-matches-surface .sx-lead-match__relations-title {
    color: var(--sx-color-success-on-soft);
}
.sx-lead-identity-card {
    box-sizing: border-box;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    width: 100%;
    min-height: 5rem;
    padding: 0.875rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.sx-lead-identity-card-link {
    display: block;
    color: inherit;
    text-decoration: none;
}
.sx-lead-identity-card-link:hover,
.sx-lead-identity-card-link:focus {
    color: inherit;
    text-decoration: none;
    outline: none;
}
.sx-lead-identity-card-link:hover .sx-lead-identity-card,
.sx-lead-identity-card-link:focus .sx-lead-identity-card {
    border-color: var(--sx-color-accent-border);
    box-shadow: var(--sx-button-focus-shadow);
}
.sx-lead-identity-card__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    color: var(--sx-color-text-muted);
    background: var(--sx-color-surface-muted);
}
.sx-lead-identity-card__body {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-width: 0;
}
.sx-lead-identity-card__label,
.sx-lead-identity-card__meta {
    color: var(--sx-color-text-muted);
    font-size: 0.8125rem;
}
.sx-lead-identity-card__label {
    margin-bottom: 0.25rem;
}
.sx-lead-identity-card__title {
    overflow-wrap: anywhere;
    font-weight: 600;
}
.sx-lead-identity-card__meta {
    margin-top: 0.25rem;
    overflow-wrap: anywhere;
}
.sx-lead-identity-card__arrow {
    align-self: center;
    flex: 0 0 auto;
    color: var(--sx-color-text-subtle);
    font-size: 0.75rem;
}
CSS
);
?>

<div class="sx-surface-stack">
    <?= \skeeks\cms\source\RecordOriginWidget::widget(['model' => $model]) ?>
    <?php BackendSurfaceWidget::begin(['raised' => true, 'options' => ['class' => 'sx-surface--compact']]); ?>
        <?= $this->render('_stage-bar', [
            'model' => $model,
            'steps' => (new \skeeks\cms\services\CmsLeadBoardService())->steps($model, (int)\Yii::$app->user->id),
        ]); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if (!$model->cms_company_id || !$model->cms_user_id) : ?>
        <div
            class="sx-lead-matches-slot"
            data-sx-lead-matches
            data-url="<?= Html::encode(Url::to(['/cms/admin-cms-lead/matches', 'pk' => $model->id])); ?>"
            aria-live="polite"
        >
            <div class="sx-lead-matches__loading">Проверяем компании и клиентов…</div>
        </div>
    <?php endif; ?>

    <div class="sx-lead-layout sx-detail-layout">
        <aside class="sx-detail-layout__aside sx-surface-stack">
            <?php if ($model->company) : ?>
                <?= $identityCard(
                    '/cms/admin-cms-company',
                    (int)$model->company->id,
                    'Компания',
                    (string)$model->company->asText,
                    'fas fa-building',
                    [
                        ArrayHelper::getValue($model->company->phones, '0.value'),
                        ArrayHelper::getValue($model->company->emails, '0.value'),
                    ]
                ); ?>
            <?php endif; ?>

            <?php if ($model->client) : ?>
                <?= $identityCard(
                    '/cms/admin-user',
                    (int)$model->client->id,
                    'Клиент',
                    (string)$model->client->asText(),
                    'fas fa-user',
                    [$model->client->phone, $model->client->email]
                ); ?>
            <?php endif; ?>

            <?php if ($model->partner) : ?>
                <?= $identityCard(
                    '/cms/admin-user',
                    (int)$model->partner->id,
                    'Партнёр',
                    (string)$model->partner->asText(),
                    'fas fa-handshake',
                    [$model->partner->phone, $model->partner->email]
                ); ?>
            <?php endif; ?>

            <?php if ($formSendId) : ?>
                <?= $identityCard(
                    '/form2/admin-form-send',
                    $formSendId,
                    $sourceName !== '' ? 'Форма «'.$sourceName.'»' : 'Отправка формы',
                    'Отправка №'.$formSendId,
                    'fas fa-envelope-open-text'
                ); ?>
            <?php endif; ?>

            <?php
            // The same contact blocks as in the company card; editing only for the responsible manager.
            $leadId = (int)$model->id;
            $menu = static fn(string $controllerId, int $id, string $title): string => $canWork
                ? CrmContactBlockWidget::menu($controllerId, $id, $title)
                : '';

            echo CrmContactBlockWidget::widget([
                'title' => 'Телефоны',
                'hint' => 'У лида может быть несколько телефонов. Первый из них основной, по нему звонят и пишут SMS. При создании компании телефоны переносятся в неё.',
                'icon' => 'phone',
                'items' => array_map(static fn($phone) => [
                    'label' => $phone->name ?: 'Телефон',
                    'value' => Html::a(Html::encode($phone->value), 'tel:'.preg_replace('/[^\d+]/', '', (string)$phone->value), ['data-pjax' => '0']),
                    'actions' => $menu('/cms/admin-cms-lead-phone', (int)$phone->id, 'Изменить телефон')
                        .CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_PHONE, (string)$phone->value, $model, $canWork ? [
                            'sms' => CrmContactBlockWidget::iconButton('message', 'Написать SMS', ['class' => 'sx-send-sms-trigger', 'data-phone' => $phone->value]),
                            'call' => CrmContactBlockWidget::iconButton('phone', 'Позвонить', ['class' => 'sx-telephony-btn', 'data-value' => $phone->value, 'data-lead-id' => $leadId]),
                        ] : []),
                ], $model->phones),
                'emptyText' => 'Телефонов пока нет',
                'addLabel' => 'Добавить телефон',
                'addRoute' => $canWork ? ['/cms/admin-cms-lead-phone/create', 'CmsLeadPhone' => ['cms_lead_id' => $leadId]] : null,
                'addSize' => 'small',
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Email',
                'hint' => 'У лида может быть несколько email адресов. Первый из них основной. При создании компании адреса переносятся в неё.',
                'icon' => 'mail',
                'items' => array_map(static fn($email) => [
                    'label' => $email->name ?: 'Email',
                    'value' => Html::a(Html::encode($email->value), 'mailto:'.$email->value, ['data-pjax' => '0']),
                    'actions' => $menu('/cms/admin-cms-lead-email', (int)$email->id, 'Изменить email')
                        .CrmContactBlockWidget::contactActions(CrmContactBlockWidget::CONTACT_EMAIL, (string)$email->value, $model, [
                            'mailto' => CrmContactBlockWidget::iconButton('mail', 'Написать письмо', ['href' => 'mailto:'.$email->value]),
                        ]),
                ], $model->emails),
                'emptyText' => 'Email пока нет',
                'addLabel' => 'Добавить email',
                'addRoute' => $canWork ? ['/cms/admin-cms-lead-email/create', 'CmsLeadEmail' => ['cms_lead_id' => $leadId]] : null,
                'addSize' => 'small',
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Адреса',
                'hint' => 'Адреса офиса, склада или магазина клиента. Первый из них основной. При создании компании адреса переносятся в неё.',
                'icon' => 'map-pin',
                'items' => array_map(static fn($address) => [
                    'label' => $address->name ?: 'Адрес',
                    'value' => Html::encode($address->value),
                    'note' => implode(', ', array_filter([
                        $address->entrance ? 'подъезд '.$address->entrance : null,
                        $address->floor ? 'этаж '.$address->floor : null,
                        $address->apartment_number ? 'кв./офис '.$address->apartment_number : null,
                        $address->comment ?: null,
                    ])),
                    'actions' => $menu('/cms/admin-cms-lead-address', (int)$address->id, 'Изменить адрес'),
                ], $model->addresses),
                'emptyText' => 'Адресов пока нет',
                'addLabel' => 'Добавить адрес',
                'addRoute' => $canWork ? ['/cms/admin-cms-lead-address/create', 'CmsLeadAddress' => ['cms_lead_id' => $leadId]] : null,
            ]);

            if ($canWork) {
                $detachUrl = \yii\helpers\Json::encode(Url::to(['/cms/admin-cms-lead-contractor/detach', 'lead_id' => $leadId]));
                $this->registerJs(<<<JS
$(document).off('click.sxLeadContractor').on('click.sxLeadContractor', '[data-sx-lead-contractor-detach]', function () {
    if (!confirm('Убрать эти реквизиты из лида? Сами реквизиты останутся в базе.')) {
        return false;
    }
    var ajaxQuery = sx.ajax.preparePostQuery({$detachUrl}, {contractor_id: $(this).data('sxLeadContractorDetach')});
    var handler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
    handler.on('success', function () { window.location.reload(); });
    ajaxQuery.execute();
    return false;
});
JS
                );
            }
            // Full contractor form with "fill by INN"; an INN already in the base is only linked.
            echo CrmContactBlockWidget::widget([
                'title' => 'Реквизиты',
                'hint' => 'Реквизиты организации или ИП для счетов и договоров. Если такой ИНН уже есть в базе, реквизиты просто привязываются. При создании компании они переносятся в неё.',
                'icon' => 'id-card',
                'items' => array_map(static fn($contractor) => [
                    'label' => $contractor->contractor_type ? $contractor->getTypeAsText() : 'Реквизиты',
                    'value' => BackendEntityLink::widget([
                        'controllerId' => '/cms/admin-cms-contractor',
                        'modelId' => $contractor->id,
                        'content' => Html::encode($contractor->asText),
                    ]),
                    'note' => $contractor->inn ? 'ИНН '.$contractor->inn : null,
                    'actions' => $canWork
                        ? CrmContactBlockWidget::iconButton('close', 'Убрать из лида', ['data-sx-lead-contractor-detach' => (int)$contractor->id])
                        : '',
                ], $model->contractors),
                'emptyText' => 'Реквизитов пока нет',
                'addLabel' => 'Добавить реквизиты',
                'addRoute' => $canWork ? ['/cms/admin-cms-lead-contractor/create', 'lead_id' => $leadId] : null,
            ]);

            echo CrmContactBlockWidget::widget([
                'title' => 'Ссылки',
                'hint' => 'Сайт и соцсети клиента. При создании компании ссылки переносятся в неё.',
                'icon' => 'link',
                'items' => array_map(static fn($link) => [
                    'label' => $link->name ?: $link->linkTypeAsText ?: 'Ссылка',
                    'value' => Html::a(Html::encode($link->url), $link->url, ['target' => '_blank', 'rel' => 'noopener noreferrer', 'data-pjax' => '0']),
                    'actions' => $menu('/cms/admin-cms-lead-link', (int)$link->id, 'Изменить ссылку'),
                ], $model->links),
                'emptyText' => 'Ссылок пока нет',
                'addLabel' => 'Добавить ссылку',
                'addRoute' => $canWork ? ['/cms/admin-cms-lead-link/create', 'CmsLeadLink' => ['cms_lead_id' => $leadId]] : null,
                'addSize' => 'small',
            ]);
            ?>

            <?php // Blocks of other packages (КП and others), CrmCardBlocksWidget::EVENT_BLOCKS. ?>
            <?= \skeeks\cms\widgets\admin\CrmCardBlocksWidget::widget(['model' => $model]); ?>

            <?php BackendSurfaceWidget::begin([
                'title' => 'Описание',
                'titleTag' => 'h2',
                'raised' => true,
                'responsive' => true,
            ]); ?>
                <div class="sx-lead-description"><?= $description !== '' ? nl2br(Html::encode($description)) : '—'; ?></div>
            <?php BackendSurfaceWidget::end(); ?>

            <?php BackendSurfaceWidget::begin([
                'title' => 'Источник и атрибуция',
                'titleTag' => 'h2',
                'raised' => true,
                'responsive' => true,
            ]); ?>
                <div class="sx-contact-list">
                    <div class="sx-collection-cell sx-collection-cell--stack">
                        <span class="sx-collection-cell__secondary">Источник</span>
                        <span class="sx-collection-cell__primary"><?= Html::encode($sourceTypeName); ?></span>
                    </div>
                    <?php if ($sourceName !== '') : ?>
                        <div class="sx-collection-cell sx-collection-cell--stack">
                            <span class="sx-collection-cell__secondary">Название источника</span>
                            <span class="sx-collection-cell__primary"><?= Html::encode($sourceName); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($model->source_url) : ?>
                        <div class="sx-collection-cell sx-collection-cell--stack">
                            <span class="sx-collection-cell__secondary">Страница обращения</span>
                            <?= Html::a(Html::encode($model->source_url), $model->source_url, ['target' => '_blank', 'rel' => 'noopener']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($sourceReferrer !== '' && $sourceReferrer !== (string)$model->source_url) : ?>
                        <div class="sx-collection-cell sx-collection-cell--stack">
                            <span class="sx-collection-cell__secondary">Предыдущая страница</span>
                            <?= Html::a(Html::encode($sourceReferrer), $sourceReferrer, ['target' => '_blank', 'rel' => 'noopener']); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($utmValues as $label => $value) : ?>
                        <div class="sx-collection-cell sx-collection-cell--stack">
                            <span class="sx-collection-cell__secondary"><?= Html::encode($label); ?></span>
                            <span class="sx-collection-cell__primary"><?= Html::encode($value); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php BackendSurfaceWidget::end(); ?>
        </aside>

        <main class="sx-detail-layout__main sx-surface-stack">
            <?php
            $leadTasks = $model->getActiveTasks()->limit(5)->all();
            $isInWork = $model->status === CmsLead::STATUS_IN_WORK;
            $nextStepActions = [];
            $openActionLink = static function (string $label, string $url, string $variant, array $actionData = []): string {
                $action = \yii\helpers\Json::encode(array_merge(['isOpenNewWindow' => true, 'url' => $url], $actionData));

                return Html::a($label, $url, [
                    'class' => 'sx-button sx-button--'.$variant.' sx-button--sm',
                    'data-pjax' => '0',
                    'onclick' => 'new sx.classes.backend.widgets.Action('.$action.').go(); return false;',
                ]);
            };
            if ($canWork && $isInWork) {
                $nextStepActions[] = $openActionLink(
                    '<i class="fas fa-phone-slash" aria-hidden="true"></i> Не дозвонились',
                    (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                        '/cms/admin-cms-lead/no-answer',
                        'pk' => $model->id,
                    ])->enableEmptyLayout()->enableNoActions()->url,
                    'secondary',
                    ['size' => 'small']
                );
            }
            // Actions of the next step: "Поставить задачу" and those of packages
            // (CrmCardBlocksWidget::EVENT_ACTIONS: "Отправить презентацию", "Сделать КП") in one menu.
            $stepItems = [];
            if ($canWork && \Yii::$app->user->can('cms/admin-task')) {
                $stepItems['task'] = [
                    'label' => 'Поставить задачу',
                    'icon' => 'plus',
                    'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                        '/cms/admin-cms-task/create',
                        'CmsTask' => ['cms_lead_id' => $model->id],
                    ])->enableEmptyLayout()->enableNoActions()->url,
                ];
            }
            if ($canWork) {
                $stepItems += \skeeks\cms\widgets\admin\CrmCardBlocksWidget::actions($model);
            }
            $stepVariant = $leadTasks ? 'secondary' : 'primary';
            if (count($stepItems) === 1) {
                $item = reset($stepItems);
                $nextStepActions[] = $openActionLink(
                    \skeeks\cms\backend\helpers\BackendIcon::renderConfigured((string)($item['icon'] ?? 'plus'), ['size' => 16]).' '.Html::encode($item['label']),
                    (string)$item['url'],
                    $stepVariant
                );
            } elseif ($stepItems) {
                $menu = '';
                foreach ($stepItems as $item) {
                    $menu .= Html::a(
                        (!empty($item['icon']) ? \skeeks\cms\backend\helpers\BackendIcon::renderConfigured((string)$item['icon'], ['size' => 16]).' ' : '').Html::encode($item['label']),
                        (string)$item['url'],
                        [
                            'class' => 'sx-lead-next-step__menu-item',
                            'role' => 'menuitem',
                            'data-pjax' => '0',
                            'onclick' => 'this.closest("details").removeAttribute("open"); new sx.classes.backend.widgets.Action('
                                .\yii\helpers\Json::encode(['isOpenNewWindow' => true, 'url' => (string)$item['url']]).').go(); return false;',
                        ]
                    );
                }
                $nextStepActions[] = Html::tag('details',
                    Html::tag('summary', \skeeks\cms\backend\helpers\BackendIcon::render('plus', ['size' => 16]).' Действие '
                        .Html::tag('span', '▾', ['aria-hidden' => 'true']), ['class' => 'sx-button sx-button--'.$stepVariant.' sx-button--sm'])
                    .Html::tag('div', $menu, ['class' => 'sx-lead-next-step__menu', 'role' => 'menu']),
                    ['class' => 'sx-lead-next-step__actions', 'data-sx-lead-step-menu' => true]
                );
                // A native <details>: closed by an outside click or Escape.
                $this->registerJs(<<<JS
$(document).on('click', function (e) {
    $('[data-sx-lead-step-menu][open]').each(function () { if (!this.contains(e.target)) { this.removeAttribute('open'); } });
}).on('keydown', function (e) {
    if (e.key === 'Escape') { $('[data-sx-lead-step-menu][open]').removeAttr('open'); }
});
JS
                );
            }
            ?>
            <?php if ($leadTasks || $isInWork) : ?>
                <?php
                $this->registerCss(<<<CSS
.sx-lead-next-step__task {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.sx-lead-next-step__task .sx-preview-card {
    flex-grow: 1;
    min-width: 0;
}
.sx-lead-next-step__actions {
    position: relative;
}
.sx-lead-next-step__actions > summary {
    list-style: none;
    cursor: pointer;
}
.sx-lead-next-step__actions > summary::-webkit-details-marker {
    display: none;
}
.sx-lead-next-step__menu {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    min-width: 230px;
    padding: 4px;
    border: 1px solid var(--sx-menu-border-color, var(--sx-color-border));
    border-radius: var(--sx-radius-control);
    background: var(--sx-menu-background, var(--sx-color-surface-raised));
    box-shadow: var(--sx-menu-shadow, 0 8px 24px rgba(29, 35, 48, 0.16));
}
.sx-lead-next-step__menu-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: var(--sx-radius-control);
    color: var(--sx-menu-color, var(--sx-color-text));
    white-space: nowrap;
    text-decoration: none !important;
}
.sx-lead-next-step__menu-item:hover,
.sx-lead-next-step__menu-item:focus {
    background: var(--sx-menu-hover-background, var(--sx-color-surface-hover));
    color: var(--sx-menu-active-color, var(--sx-color-accent));
}
CSS
                );
                ?>
                <?php BackendSurfaceWidget::begin([
                    'title' => 'Следующий шаг',
                    'titleTag' => 'h2',
                    'hint' => $model->no_answer_count ? 'Неудачных попыток дозвониться: '.(int)$model->no_answer_count : null,
                    'actions' => $nextStepActions,
                    'raised' => true,
                    'responsive' => true,
                    'options' => ['class' => 'sx-lead-next-step'],
                ]); ?>
                    <?php if (!$leadTasks) : ?>
                        <p>
                            <span class="sx-status sx-status--warning">Не запланирован</span>
                            Поставьте задачу, чтобы не забыть про лид: когда перезвонить или что сделать дальше.
                        </p>
                    <?php else : ?>
                        <div class="sx-surface-stack">
                            <?php foreach ($leadTasks as $leadTask) : ?>
                                <div class="sx-lead-next-step__task">
                                    <?= \skeeks\cms\widgets\admin\CmsTaskViewWidget::widget(['task' => $leadTask]); ?>
                                    <?= \skeeks\cms\widgets\admin\CmsTaskStatusWidget::widget(['task' => $leadTask]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php BackendSurfaceWidget::end(); ?>
            <?php endif; ?>
            <?php $pjax = Pjax::begin(['id' => 'cms-lead-comments', 'options' => ['class' => 'sx-surface-stack sx-activity-thread']]); ?>
                <?php if ($canWork) : ?>
                    <?php BackendSurfaceWidget::begin(['title' => $model->partner_id ? 'Написать партнёру' : 'Добавить комментарий', 'titleTag' => 'h2', 'raised' => true, 'responsive' => true]); ?>
                        <?= CmsCommentWidget::widget(['model' => $model, 'backend_url' => ['/cms/admin-cms-lead/add-comment', 'pk' => $model->id], 'isShowPin' => false]); ?>
                    <?php BackendSurfaceWidget::end(); ?>
                <?php endif; ?>
                <?= CmsLogListWidget::widget([
                    'query' => $model->getLogs()->activity(),
                    'is_show_model' => false,
                    'is_show_pin_controls' => false,
                ]); ?>
            <?php $pjax::end(); ?>
        </main>
    </div>
</div>

<?php $smsModal = \yii\bootstrap\Modal::begin([
    'header' => "SMS на <span class='sx-send-sms-phone'></span>",
    'id' => 'sx-send-sms-modal',
    'size' => \yii\bootstrap\Modal::SIZE_DEFAULT,
    'toggleButton' => false,
]); ?>
<?php if (\Yii::$app->cms->smsProvider) : ?>
    <?php $smsForm = \skeeks\cms\base\widgets\ActiveFormAjaxSubmit::begin([
        'action' => Url::to(['/cms/admin-cms-lead/send-sms', 'pk' => $model->id]),
        'enableAjaxValidation' => false,
        'clientCallback' => new \yii\web\JsExpression("function(form) { form.on('success', function() { $('#sx-send-sms-modal').modal('hide'); }); }"),
    ]); ?>
        <input type="hidden" id="sx-send-sms-phone-value" name="phone">
        <div class="form-group"><textarea class="form-control" name="message" placeholder="Сообщение" rows="5"></textarea></div>
        <button type="submit" class="sx-button sx-button--primary">Отправить</button>
    <?php $smsForm::end(); ?>
<?php else : ?>
    <p>На сайте не настроен SMS-провайдер.</p>
<?php endif; ?>
<?php $smsModal::end(); ?>
