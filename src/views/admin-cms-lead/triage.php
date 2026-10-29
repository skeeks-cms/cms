<?php
/**
 * An unsorted deal («Неразобранное», п. 13б): «Взять в работу» — the client
 * (the found match is already chosen; another company; a new client), the
 * responsible is me, one save; «Не продажа» — «Уже есть сделка» (add to an
 * open deal) or «Поддержка». Spam is posted from the header. Actions:
 * LeadTriageActions::triage().
 *
 * @var string $mode LeadTriageActions::TRIAGE_TAKE | TRIAGE_MERGE | TRIAGE_SUPPORT
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsLead $model
 * @var skeeks\cms\crm\LeadIdentityForm $form
 * @var array[] $candidates
 * @var skeeks\cms\models\CmsLead[] $repeats open deals of the found client the employee sees
 * @var int $hiddenRepeats open deals of the client the employee does not see
 * @var skeeks\cms\models\CmsDeal[] $deals services of the client for «Это поддержка»
 * @var int|null $stageId the kanban column the deal was dropped on
 */

use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\crm\LeadIdentityForm;
use skeeks\cms\models\CmsLead;
use yii\helpers\Html;
use yii\helpers\Url;

$titles = ['merge' => 'Уже есть сделка', 'support' => 'Поддержка'];
$this->title = ($titles[$mode] ?? 'Взять в работу').': сделка №'.(int)$model->id;
$description = trim((string)$model->description);
$contacts = array_merge(
    array_map(static fn($phone) => (string)$phone->value, $model->phones),
    array_map(static fn($email) => (string)$email->value, $model->emails)
);
?>
<?= Html::beginForm(Url::to(['/cms/admin-cms-lead/triage', 'pk' => $model->id]), 'post', ['data-sx-lead-ajax-form' => true, 'data-sx-lead-open-card' => $mode === 'take' && Yii::$app->request->get('from') !== 'card']); ?>
<?php if ($stageId) : ?>
    <?= Html::hiddenInput('stage_id', $stageId); ?>
<?php endif; ?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Что пришло',
        'titleTag' => 'h2',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <div class="sx-contact-list">
            <div class="sx-collection-cell sx-collection-cell--stack">
                <span class="sx-collection-cell__secondary">Источник</span>
                <span class="sx-collection-cell__primary"><?= Html::encode($model->sourceNameAsText); ?>, <?= Yii::$app->formatter->asDatetime($model->created_at, 'short'); ?></span>
            </div>
            <div class="sx-collection-cell sx-collection-cell--stack">
                <span class="sx-collection-cell__secondary">Название</span>
                <span class="sx-collection-cell__primary"><?= Html::encode((string)$model->name); ?></span>
            </div>
            <?php if ($contacts) : ?>
                <div class="sx-collection-cell sx-collection-cell--stack">
                    <span class="sx-collection-cell__secondary">Контакты</span>
                    <span class="sx-collection-cell__primary"><?= Html::encode(implode(', ', $contacts)); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($description !== '') : ?>
                <div class="sx-collection-cell sx-collection-cell--stack">
                    <span class="sx-collection-cell__secondary">Текст</span>
                    <span class="sx-lead-description"><?= nl2br(Html::encode(mb_substr($description, 0, 2000))); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if ($mode === 'merge') : ?>
        <?php if (!$repeats && !$hiddenRepeats) : ?>
            <p class="sx-lead-identity__note">Открытых сделок этого клиента не найдено. Возьмите заявку в работу или отметьте её как поддержку.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($mode === 'merge' && ($repeats || $hiddenRepeats)) : ?>
        <?php BackendSurfaceWidget::begin([
            'title' => 'Открытые сделки этого клиента',
            'titleTag' => 'h2',
            'hint' => 'Если это продолжение того же разговора — добавьте обращение в открытую сделку. Новая потребность — новая сделка.',
            'raised' => true,
            'responsive' => true,
        ]); ?>
            <ul class="sx-lead-triage__repeats">
                <?php foreach ($repeats as $repeat) : ?>
                    <li class="sx-lead-triage__repeat">
                        <span>
                            <?= Html::a('№'.(int)$repeat->id.' '.Html::encode((string)$repeat->name), ['/cms/admin-cms-lead/view', 'pk' => $repeat->id], ['target' => '_blank', 'data-pjax' => 0]); ?>
                            <span class="sx-lead-hint__meta">
                                — <?= Html::encode($repeat->staffStatusName); ?><?= $repeat->stage ? ', '.Html::encode($repeat->stage->name) : ''; ?>,
                                <?= $repeat->executor ? 'ответственный '.Html::encode((string)$repeat->executor->shortDisplayName) : 'без ответственного'; ?>
                            </span>
                        </span>
                        <?= Html::submitButton('Добавить в эту сделку', [
                            'name' => 'op',
                            'value' => 'merge:'.(int)$repeat->id,
                            'class' => 'sx-button sx-button--secondary sx-button--sm',
                            'data-confirm' => 'Добавить обращение в сделку №'.(int)$repeat->id.'? Эта сделка закроется.',
                        ]); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($hiddenRepeats) : ?>
                <p class="sx-lead-identity__note">Ещё открытых сделок у других сотрудников: <?= (int)$hiddenRepeats; ?>.</p>
            <?php endif; ?>
        <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>

    <?php if ($mode === 'take') : ?>
    <?php BackendSurfaceWidget::begin([
        'title' => 'Клиент',
        'titleTag' => 'h2',
        'hint' => $form->company_mode === LeadIdentityForm::MODE_EXISTING || $form->client_mode === LeadIdentityForm::MODE_EXISTING
            ? 'Подходящая запись из базы уже выбрана. Если это другой клиент — выберите другую компанию или создайте нового.'
            : ($candidates ? 'В базе есть похожие записи: выберите подходящую, другую компанию или создайте нового клиента.' : null),
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?= $this->render('@skeeks/cms/views/admin-cms-lead/_identity', [
            'form' => $form,
            'lead' => $model,
            'candidates' => $candidates,
            'allowForeign' => true,
            'withExecutor' => true,
            'withContacts' => true,
        ]); ?>
        <div class="sx-lead-triage__actions">
            <?= Html::submitButton('Взять в работу', [
                'name' => 'op',
                'value' => 'accept',
                'class' => 'sx-button sx-button--primary',
            ]); ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>

    <?php if ($mode === 'support') : ?>
    <?php BackendSurfaceWidget::begin([
        'title' => 'Поддержка',
        'titleTag' => 'h2',
        'hint' => 'Вопрос по действующей услуге — задача в поддержку. Сделка закроется и не попадёт в отказы.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <div class="sx-lead-triage__actions">
            <?= Html::dropDownList('deal_id', null, ['' => $deals ? 'Без услуги' : 'У клиента нет доступных услуг']
                + \yii\helpers\ArrayHelper::map($deals, 'id', static fn($deal) => $deal->asText.($deal->is_active ? '' : ' (неактивна)')),
                ['class' => 'form-control', 'style' => 'max-width: 420px', 'aria-label' => 'Услуга клиента']); ?>
            <?= Html::submitButton('Передать в поддержку', [
                'name' => 'op',
                'value' => 'support',
                'class' => 'sx-button sx-button--primary',
            ]); ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>
</div>
<?= Html::endForm(); ?>
