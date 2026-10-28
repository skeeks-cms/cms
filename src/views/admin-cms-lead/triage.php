<?php
/**
 * Sorting an unsorted deal («Неразобранное», П2а): who it is, take into work,
 * add to an open deal, support or spam. Actions: LeadTriageActions::triage().
 *
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
use skeeks\cms\models\CmsLead;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Разбор сделки №'.(int)$model->id;
$description = trim((string)$model->description);
$contacts = array_merge(
    array_map(static fn($phone) => (string)$phone->value, $model->phones),
    array_map(static fn($email) => (string)$email->value, $model->emails)
);
?>
<?= Html::beginForm(Url::to(['/cms/admin-cms-lead/triage', 'pk' => $model->id]), 'post', ['data-sx-lead-ajax-form' => true]); ?>
<?php if ($stageId) : ?>
    <?= Html::hiddenInput('stage_id', $stageId); ?>
<?php endif; ?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Что пришло',
        'titleTag' => 'h2',
        'hint' => 'Неразобранная сделка: определите клиента и решите, что с ней делать.',
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

    <?php if ($repeats || $hiddenRepeats) : ?>
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

    <?php BackendSurfaceWidget::begin([
        'title' => 'Клиент',
        'titleTag' => 'h2',
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
            <?= Html::submitButton('Принять в работу', [
                'name' => 'op',
                'value' => 'accept',
                'class' => 'sx-button sx-button--primary',
            ]); ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Это не продажа',
        'titleTag' => 'h2',
        'hint' => 'Вопрос по действующей услуге — задача в поддержку. Сделка закроется и не попадёт в отказы.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <div class="sx-lead-triage__actions">
            <?= Html::dropDownList('deal_id', null, ['' => $deals ? 'Без услуги' : 'У клиента нет доступных услуг']
                + \yii\helpers\ArrayHelper::map($deals, 'id', static fn($deal) => $deal->asText.($deal->is_active ? '' : ' (неактивна)')),
                ['class' => 'form-control', 'style' => 'max-width: 420px', 'aria-label' => 'Услуга клиента']); ?>
            <?= Html::submitButton('Это поддержка', [
                'name' => 'op',
                'value' => 'support',
                'class' => 'sx-button sx-button--secondary',
                'data-confirm' => 'Поставить задачу поддержки и закрыть сделку?',
            ]); ?>
            <?= Html::submitButton('Спам', [
                'name' => 'op',
                'value' => 'spam',
                'class' => 'sx-button sx-button--danger',
                'data-confirm' => 'Отметить сделку как спам и закрыть её?',
            ]); ?>
        </div>
    <?php BackendSurfaceWidget::end(); ?>
</div>
<?= Html::endForm(); ?>
