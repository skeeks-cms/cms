<?php

use skeeks\cms\assets\admin\LeadMatchesAsset;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\telephony\widgets\TelephonyWidget;
use skeeks\cms\widgets\admin\CmsCommentWidget;
use skeeks\cms\widgets\admin\CmsLogListWidget;
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
$contactAction = static function (string $route, string $modelClass, int $leadId): string {
    return \yii\helpers\Json::encode([
        'isOpenNewWindow' => true,
        'size' => 'small',
        'url' => (string)\skeeks\cms\backend\helpers\BackendUrlHelper::createByParams([
            $route,
            $modelClass => ['cms_lead_id' => $leadId],
        ])->enableEmptyLayout()->enableNoActions()->url,
    ]);
};
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
.sx-lead-contact {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}
.sx-lead-contact__value,
.sx-lead-description {
    min-width: 0;
    overflow-wrap: anywhere;
}
.sx-lead-contact__action {
    flex: 0 0 auto;
}
.sx-lead-contact-list {
    display: grid;
    gap: 0.75rem;
}
.sx-lead-contact-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}
.sx-lead-contact-row .sx-collection-cell {
    flex: 1 1 auto;
    min-width: 0;
}
.sx-lead-contact-actions {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    flex: 0 0 auto;
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

            <?php BackendSurfaceWidget::begin([
                'title' => 'Телефоны',
                'titleTag' => 'h2',
                'raised' => true,
                'responsive' => true,
            ]); ?>
                <div class="sx-lead-contact-list">
                    <?php foreach ($model->phones as $phone) : ?>
                        <div class="sx-lead-contact-row">
                            <div class="sx-collection-cell sx-collection-cell--stack">
                                <span class="sx-collection-cell__secondary"><?= Html::encode($phone->name ?: 'Телефон'); ?></span>
                                <span class="sx-collection-cell__primary sx-lead-contact__value"><?= Html::a(Html::encode($phone->value), 'tel:'.preg_replace('/[^\d+]/', '', $phone->value), ['data-pjax' => '0']); ?></span>
                            </div>
                            <?php if ($canWork) : ?>
                                <div class="sx-lead-contact-actions">
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => '/cms/admin-cms-lead-phone',
                                        'modelId' => $phone->id,
                                        'tag' => 'div',
                                        'options' => ['title' => 'Изменить телефон', 'class' => 'sx-icon-action'],
                                    ]); ?><i class="fas fa-ellipsis-v" aria-hidden="true"></i><?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>
                                    <button type="button" class="sx-button sx-button--secondary sx-button--sm sx-send-sms-trigger" data-phone="<?= Html::encode($phone->value); ?>" title="Написать SMS" aria-label="Написать SMS"><i class="fas fa-sms" aria-hidden="true"></i></button>
                                    <button type="button" class="sx-button sx-button--secondary sx-button--sm sx-telephony-btn" data-value="<?= Html::encode($phone->value); ?>" data-lead-id="<?= (int)$model->id; ?>" title="Позвонить" aria-label="Позвонить"><i class="fas fa-phone" aria-hidden="true"></i></button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$model->phones) : ?><span class="sx-collection-cell__secondary">Телефонов пока нет</span><?php endif; ?>
                    <?php if ($canWork) : ?>
                        <?= Html::a('<i class="fas fa-plus" aria-hidden="true"></i> Добавить телефон', '#', [
                            'class' => 'sx-button sx-button--secondary sx-button--sm align-self-start',
                            'onclick' => 'new sx.classes.backend.widgets.Action('.$contactAction('/cms/admin-cms-lead-phone/create', 'CmsLeadPhone', (int)$model->id).').go(); return false;',
                        ]); ?>
                    <?php endif; ?>
                </div>
            <?php BackendSurfaceWidget::end(); ?>

            <?php BackendSurfaceWidget::begin([
                'title' => 'Email',
                'titleTag' => 'h2',
                'raised' => true,
                'responsive' => true,
            ]); ?>
                <div class="sx-lead-contact-list">
                    <?php foreach ($model->emails as $email) : ?>
                        <div class="sx-lead-contact-row">
                            <div class="sx-collection-cell sx-collection-cell--stack">
                                <span class="sx-collection-cell__secondary"><?= Html::encode($email->name ?: 'Email'); ?></span>
                                <span class="sx-collection-cell__primary sx-lead-contact__value"><?= Html::a(Html::encode($email->value), 'mailto:'.$email->value, ['data-pjax' => '0']); ?></span>
                            </div>
                            <?php if ($canWork) : ?>
                                <div class="sx-lead-contact-actions">
                                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                                        'controllerId' => '/cms/admin-cms-lead-email',
                                        'modelId' => $email->id,
                                        'tag' => 'div',
                                        'options' => ['title' => 'Изменить email', 'class' => 'sx-icon-action'],
                                    ]); ?><i class="fas fa-ellipsis-v" aria-hidden="true"></i><?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>
                                    <?= Html::a('<i class="fas fa-envelope" aria-hidden="true"></i>', 'mailto:'.$email->value, [
                                        'class' => 'sx-button sx-button--secondary sx-button--sm',
                                        'title' => 'Написать письмо',
                                        'aria-label' => 'Написать письмо',
                                        'data-pjax' => '0',
                                    ]); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$model->emails) : ?><span class="sx-collection-cell__secondary">Email пока нет</span><?php endif; ?>
                    <?php if ($canWork) : ?>
                        <?= Html::a('<i class="fas fa-plus" aria-hidden="true"></i> Добавить email', '#', [
                            'class' => 'sx-button sx-button--secondary sx-button--sm align-self-start',
                            'onclick' => 'new sx.classes.backend.widgets.Action('.$contactAction('/cms/admin-cms-lead-email/create', 'CmsLeadEmail', (int)$model->id).').go(); return false;',
                        ]); ?>
                    <?php endif; ?>
                </div>
            <?php BackendSurfaceWidget::end(); ?>

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
                <div class="sx-lead-contact-list">
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

        <main class="sx-detail-layout__main">
            <?php $pjax = Pjax::begin(['id' => 'cms-lead-comments', 'options' => ['class' => 'sx-surface-stack sx-activity-thread']]); ?>
                <?php if ($canWork) : ?>
                    <?php BackendSurfaceWidget::begin(['title' => $model->partner_id ? 'Написать партнёру' : 'Добавить комментарий', 'titleTag' => 'h2', 'raised' => true, 'responsive' => true]); ?>
                        <?= CmsCommentWidget::widget(['model' => $model, 'backend_url' => ['/cms/admin-cms-lead/add-comment', 'pk' => $model->id], 'isShowPin' => false]); ?>
                    <?php BackendSurfaceWidget::end(); ?>
                <?php endif; ?>
                <?= CmsLogListWidget::widget([
                    'query' => $model->getLogs()->logType([
                        CmsLog::LOG_TYPE_PHONE_CALL,
                        CmsLog::LOG_TYPE_COMMENT,
                    ]),
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
