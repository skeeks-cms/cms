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
$this->title = 'Сделка №'.(int)$model->id;
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
.sx-lead-success-hint {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    color: var(--sx-color-success-on-soft);
    border: 1px solid var(--sx-color-success);
    background: var(--sx-color-success-soft);
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
            'steps' => \skeeks\cms\services\CmsLeadBoardService::forLead($model)->steps($model, (int)\Yii::$app->user->id),
        ]); ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php
    // П2б: the pipeline criterion (order, first payment) only suggests closing; the manager closes.
    $successSignals = $canWork ? \skeeks\cms\crm\LeadPipelines::instance()->successSignals($model) : [];
    ?>
    <?php if ($successSignals) : ?>
        <?php $successUrl = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
            '/cms/admin-cms-lead/process', 'pk' => $model->id, 'status' => CmsLead::STATUS_SUCCESS,
        ])->enableEmptyLayout()->enableNoActions()->url; ?>
        <div class="sx-surface sx-surface--raised sx-surface--padded sx-lead-success-hint" role="status">
            <div>
                <strong>Похоже, сделка состоялась.</strong>
                <?= Html::encode(implode(' ', $successSignals)); ?>
            </div>
            <?= Html::a('<i class="fas fa-check" aria-hidden="true"></i> Закрыть успешно', $successUrl, [
                'class' => 'sx-button sx-button--primary sx-button--sm',
                'data-pjax' => '0',
                'onclick' => 'new sx.classes.backend.widgets.Action('.\yii\helpers\Json::encode(['isOpenNewWindow' => true, 'url' => $successUrl]).').go(); return false;',
            ]); ?>
        </div>
    <?php endif; ?>

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
            <?php
            // A deal given to an employee without its company: the company only as "название + ответственный".
            $crmAccess = \skeeks\cms\crm\CrmAccess::instance();
            $viewerId = (int)\Yii::$app->user->id;
            $companyVisible = $model->company && $crmAccess->canSeeCompany($viewerId, (int)$model->company->id);
            $clientVisible = $model->client && $crmAccess->canSeeClient($viewerId, (int)$model->client->id);
            $briefCard = static function (string $label, string $title, array $responsible, string $icon): string {
                $names = implode(', ', array_column($responsible, 'name'));
                return Html::tag('div',
                    Html::tag('span', Html::tag('i', '', ['class' => $icon, 'aria-hidden' => 'true']), ['class' => 'sx-lead-identity-card__icon'])
                    .Html::tag('span',
                        Html::tag('span', Html::encode($label), ['class' => 'sx-lead-identity-card__label'])
                        .Html::tag('span', Html::encode($title), ['class' => 'sx-lead-identity-card__title'])
                        .Html::tag('div', $names !== '' ? 'Ответственный: '.Html::encode($names) : 'Ответственного нет', ['class' => 'sx-lead-identity-card__meta']),
                        ['class' => 'sx-lead-identity-card__body']
                    ),
                    ['class' => 'sx-surface sx-surface--raised sx-lead-identity-card']
                );
            };
            ?>
            <?= $this->render('_about', ['model' => $model, 'canWork' => $canWork]); ?>

            <?php if ($model->company && $companyVisible) : ?>
                <?= $identityCard(
                    '/cms/admin-cms-company',
                    (int)$model->company->id,
                    'Компания',
                    (string)$model->company->asText,
                    'fas fa-building',
                    [
                        ArrayHelper::getValue($model->company->phones, '0.value'),
                        ArrayHelper::getValue($model->company->emails, '0.value'),
                        // The viewer's level of the company; below "full" it works as "full" until П2а+.
                        (static function () use ($crmAccess, $viewerId, $model) {
                            $level = $crmAccess->linkLevel($viewerId, 'company', (int)$model->company->id);
                            if (!$level) {
                                return null;
                            }
                            $name = mb_strtolower(\skeeks\cms\crm\CrmAccess::levels()[$level] ?? $level, 'UTF-8');

                            return 'Ваш доступ: '.$name.($level !== \skeeks\cms\crm\CrmAccess::LEVEL_FULL && !$crmAccess->enforceLevels ? ' (пока как полный)' : '');
                        })(),
                    ]
                ); ?>
            <?php elseif ($model->company) : ?>
                <?= $briefCard('Компания', (string)$model->company->name, $crmAccess->names($crmAccess->companyResponsibleIds((int)$model->company->id)), 'fas fa-building'); ?>
            <?php endif; ?>

            <?php if ($model->client && $clientVisible) : ?>
                <?= $identityCard(
                    '/cms/admin-user',
                    (int)$model->client->id,
                    'Клиент',
                    (string)$model->client->asText(),
                    'fas fa-user',
                    [$model->client->phone, $model->client->email]
                ); ?>
            <?php elseif ($model->client) : ?>
                <?= $briefCard('Контакт', 'Контакт из базы', $crmAccess->names($crmAccess->clientResponsibleIds((int)$model->client->id)), 'fas fa-user'); ?>
            <?php endif; ?>

            <?= $this->render('_client', ['model' => $model, 'companyVisible' => $companyVisible, 'clientVisible' => $clientVisible]); ?>

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

            // П2г: the phones, emails and addresses of the request are read only while the deal has no company and no person;
            // once a client is linked, the «Клиент» blocks show the client's own contacts.
            $requestData = !$model->company && !$model->client;
            if ($requestData) {
            echo CrmContactBlockWidget::widget([
                'title' => 'Телефоны из обращения',
                'hint' => 'Телефоны, с которыми пришла сделка. Первый из них основной, по нему звонят и пишут SMS. Когда у сделки появится компания или клиент, здесь будут их контакты.',
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
                'title' => 'Email из обращения',
                'hint' => 'Адреса, с которыми пришла сделка. Первый из них основной. Когда у сделки появится компания или клиент, здесь будут их контакты.',
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
                'title' => 'Адреса из обращения',
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
            }

            if ($canWork) {
                $detachUrl = \yii\helpers\Json::encode(Url::to(['/cms/admin-cms-lead-contractor/detach', 'lead_id' => $leadId]));
                $this->registerJs(<<<JS
$(document).off('click.sxLeadContractor').on('click.sxLeadContractor', '[data-sx-lead-contractor-detach]', function () {
    if (!confirm('Убрать эти реквизиты из сделки? Сами реквизиты останутся в базе.')) {
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
                        ? CrmContactBlockWidget::iconButton('close', 'Убрать из сделки', ['data-sx-lead-contractor-detach' => (int)$contractor->id])
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
            // The block of actions over the feed, as in Bitrix24 (CrmCardActionsWidget): «Примечание»,
            // «Задача», «Позвонить», «Письмо», «Не дозвонились», then the tabs of packages
            // (CrmCardBlocksWidget::EVENT_ACTIONS: «Отправить презентацию», «Отправить КП», …).
            $cardActions = [];
            $taskUrl = (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                '/cms/admin-cms-task/create',
                'CmsTask' => ['cms_lead_id' => $model->id],
            ])->enableEmptyLayout()->enableNoActions()->url;
            $canTask = $canWork && \Yii::$app->user->can('cms/admin-task');
            if ($canTask) {
                $cardActions['task'] = ['label' => 'Задача', 'icon' => 'plus', 'url' => $taskUrl];
            }
            // The phone and email of the request, else of the contact, else of the company (only a visible card).
            $contactOf = static function (string $kind) use ($model, $clientVisible, $companyVisible): string {
                $own = $kind === 'phone' ? $model->mainPhone : $model->mainEmail;
                if ($own) {
                    return (string)$own->value;
                }
                if ($model->client && $clientVisible && trim((string)$model->client->{$kind}) !== '') {
                    return (string)$model->client->{$kind};
                }
                if ($model->company && $companyVisible) {
                    return (string)ArrayHelper::getValue($kind === 'phone' ? $model->company->phones : $model->company->emails, '0.value', '');
                }

                return '';
            };
            $callPhone = $contactOf('phone');
            if ($canWork && $callPhone !== '') {
                $cardActions['call'] = ['label' => 'Позвонить', 'icon' => 'phone', 'options' => [
                    'class' => 'sx-telephony-btn',
                    'data-value' => $callPhone,
                    'data-lead-id' => (int)$model->id,
                    'title' => 'Позвонить на '.$callPhone,
                ]];
            }
            $mailTo = $contactOf('email');
            if ($canWork && $mailTo !== '') {
                // The letter of the CRM mail (a package adds it to the email row); else the mail program.
                $mailHtml = \skeeks\cms\widgets\admin\CrmContactBlockWidget::contactActions(
                    \skeeks\cms\widgets\admin\CrmContactBlockWidget::CONTACT_EMAIL, $mailTo, $model, []);
                $mailOnClick = preg_match('/onclick="([^"]+)"/', $mailHtml, $match) ? html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5) : null;
                $cardActions['mail'] = ['label' => 'Письмо', 'icon' => 'mail', 'options' => [
                    'onclick' => $mailOnClick ?: 'window.location.href = '.\yii\helpers\Json::encode('mailto:'.$mailTo).'; return false;',
                    'title' => 'Письмо на '.$mailTo,
                ]];
            }
            if ($canWork && $isInWork) {
                $cardActions['no-answer'] = [
                    'label' => 'Не дозвонились',
                    'icon' => 'phone-missed',
                    'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
                        '/cms/admin-cms-lead/no-answer',
                        'pk' => $model->id,
                    ])->enableEmptyLayout()->enableNoActions()->url,
                    'window' => ['size' => 'small'],
                ];
            }
            if ($canTask && !$leadTasks) {
                $nextStepActions[] = $openActionLink(\skeeks\cms\backend\helpers\BackendIcon::renderConfigured('plus', ['size' => 16]).' Поставить задачу', $taskUrl, 'primary');
            }
            ?>
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
CSS
                );
                ?>
            <?php $pjax = Pjax::begin(['id' => 'cms-lead-comments', 'options' => ['class' => 'sx-surface-stack sx-activity-thread']]); ?>
            <?php // One block of actions with the note field, inside the feed pjax: a sent note reloads the feed. ?>
            <?= \skeeks\cms\widgets\admin\CrmCardActionsWidget::widget([
                'model' => $model,
                'commentUrl' => $canWork ? ['/cms/admin-cms-lead/add-comment', 'pk' => $model->id] : null,
                'noteLabel' => $model->partner_id ? 'Партнёру' : 'Примечание',
                'notePlaceholder' => $model->partner_id ? 'Напишите партнёру…' : 'Напишите примечание…',
                'actions' => $cardActions,
                'packageActions' => $canWork,
            ]); ?>
                <?php // Pinned entries once, right under the block of actions, marked as in the feed; the feed below skips them. ?>
                <?php $pinnedLeadLogsQuery = $model->getLogs()->activity()->pinned(); ?>
                <?php if ($pinnedLeadLogsQuery->count()) : ?>
                    <?= CmsLogListWidget::widget([
                        'query' => $pinnedLeadLogsQuery,
                        'is_show_model' => false,
                        'is_show_pin_controls' => $canWork,
                    ]); ?>
                <?php endif; ?>
            <?php if ($leadTasks || $isInWork) : ?>
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
                            <span class="sx-status sx-status--warning">Нет запланированных задач</span>
                            Поставьте задачу, чтобы не забыть про сделку: когда перезвонить или что сделать дальше.
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
                <?= CmsLogListWidget::widget([
                    'query' => $model->getLogs()->activity()->notPinned(),
                    'is_show_model' => false,
                    'is_show_pin_controls' => $canWork,
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
