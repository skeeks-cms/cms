<?php
/**
 * The card of an access request (П2а, AdminCmsCrmAccessRequestController):
 * who asks and the level, the comment, «Одобрить» with a level or «Отказать»
 * with a reason, the client with its employees and open deals, the deal.
 * Companies, contacts, employees and deals open their cards in the backend
 * window (BackendEntityLink).
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCrmAccessRequest $model
 * @var bool $canDecide
 * @var bool $canCancel
 * @var bool $clientOpen the viewer sees the client (its employees and deals are shown)
 * @var array[] $employees CrmAccess::accessList()
 * @var skeeks\cms\models\CmsLead[] $openLeads
 * @var string $clientName
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\controllers\AdminCmsCrmAccessRequestController as Requests;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsUser;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$decideUrl = Url::to(['/cms/admin-cms-crm-access-request/decide']);
$levels = CrmAccess::levels();
$lead = $model->cms_lead_id ? $model->lead : null;
$what = [
    CmsCrmAccessRequest::KIND_WORK => $model->cms_lead_id ? 'работает с клиентом по своей сделке и просит уровень доступа' : 'просит разрешения работать с клиентом (доступ по согласованию)',
    CmsCrmAccessRequest::KIND_DELEGATE => 'просит передать ему сделку',
    CmsCrmAccessRequest::KIND_ACCESS => 'просит доступ к клиенту',
][$model->kind] ?? '';
?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => $model->asText(),
        'titleTag' => 'h2',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <div class="sx-lead-triage__request">
            <p><?= Requests::employeeLink($model->requester); ?> <?= Html::encode($what); ?></p>
            <span class="sx-lead-hint__meta">
                Клиент: <?= Requests::recordLink($model); ?>
                <?php if ($model->levelName !== '') : ?> · уровень «<?= Html::encode($model->levelName); ?>»<?php endif; ?>
                · <?= Yii::$app->formatter->asDatetime($model->created_at, 'short'); ?>
                · <?= Html::encode($model->statusName); ?>
            </span>
            <?php if ($model->comment) : ?>
                <p><?= nl2br(Html::encode((string)$model->comment)); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($canDecide) : ?>
            <div class="sx-lead-hint__actions">
                <?php if ($model->kind !== CmsCrmAccessRequest::KIND_DELEGATE) : ?>
                    <?= Html::dropDownList('level', $model->level ?: CrmAccess::LEVEL_DEAL,
                        array_map(static fn($name) => 'Доступ: '.mb_strtolower($name, 'UTF-8'), $levels),
                        ['class' => 'form-control', 'data-sx-crm-request-level' => true, 'aria-label' => 'Уровень доступа']); ?>
                <?php endif; ?>
                <?= Html::button('Одобрить', [
                    'class' => 'sx-button sx-button--primary sx-button--sm',
                    'data-sx-crm-request-decide' => 'approve',
                    'data-id' => (int)$model->id,
                    'data-url' => $decideUrl,
                    'data-sx-confirm' => $model->kind === CmsCrmAccessRequest::KIND_WORK && !$model->cms_lead_id
                        ? 'Разрешить сотруднику работать с клиентом? Ему будет создана сделка.'
                        : 'Выдать сотруднику выбранный доступ к клиенту?',
                ]); ?>
                <?= Html::button($model->kind === CmsCrmAccessRequest::KIND_WORK && $model->cms_lead_id ? 'Не расширять' : 'Отказать', [
                    'class' => 'sx-button sx-button--secondary sx-button--sm',
                    'data-sx-crm-request-decide' => 'decline',
                    'data-id' => (int)$model->id,
                    'data-url' => $decideUrl,
                ]); ?>
            </div>
            <?php if ($model->kind === CmsCrmAccessRequest::KIND_WORK && $model->cms_lead_id) : ?>
                <p class="sx-lead-identity__note">При отказе сделка остаётся у сотрудника с уровнем «Свои сделки».</p>
            <?php endif; ?>
        <?php elseif ($canCancel) : ?>
            <div class="sx-lead-hint__actions">
                <?= Html::button('Отменить запрос', [
                    'class' => 'sx-button sx-button--secondary sx-button--sm',
                    'data-sx-crm-request-decide' => 'cancel',
                    'data-id' => (int)$model->id,
                    'data-url' => $decideUrl,
                    'data-sx-confirm' => 'Отменить запрос?',
                ]); ?>
            </div>
        <?php endif; ?>

        <?php if (!$model->isPending && $model->decidedBy) : ?>
            <p class="sx-lead-identity__note">
                <?= Html::encode($model->statusName); ?>: <?= Requests::employeeLink($model->decidedBy); ?>,
                <?= Yii::$app->formatter->asDatetime($model->decided_at, 'short'); ?>
                <?php if ($model->decision_comment) : ?> — <?= Html::encode((string)$model->decision_comment); ?><?php endif; ?>
            </p>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title' => $model->record_code === CmsCrmAccessRequest::RECORD_COMPANY ? 'Компания' : 'Контакт',
        'titleTag' => 'h2',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <p><?= Requests::recordLink($model); ?></p>
        <?php if ($clientOpen) : ?>
            <ul class="sx-lead-requests">
                <?php foreach ($employees as $employee) : ?>
                    <li class="sx-lead-hint">
                        <div class="sx-lead-triage__request">
                            <?= Requests::employeeLink(CmsUser::findOne($employee['id'])); ?>
                            <span class="sx-lead-hint__meta"><?= $employee['main'] ? 'Основной ответственный' : Html::encode('Доступ: '.mb_strtolower($levels[$employee['level']] ?? $employee['level'], 'UTF-8')); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="sx-lead-identity__note">Открытые сделки клиента: <?= $openLeads ? '' : 'нет'; ?></p>
            <?php if ($openLeads) : ?>
                <ul class="sx-lead-requests">
                    <?php foreach ($openLeads as $open) : ?>
                        <li class="sx-lead-hint">
                            <div class="sx-lead-triage__request">
                                <?= Requests::leadLink($open); ?>
                                <span class="sx-lead-hint__meta">Ответственный: <?= Requests::employeeLink($open->executor); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php else : ?>
            <p class="sx-lead-identity__note">Основной ответственный: <?= Html::encode(implode(', ', array_column(array_filter($employees, static fn($e) => $e['main']), 'name')) ?: '—'); ?>. Остальное откроется, когда запрос одобрят.</p>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if ($lead) : ?>
        <?php BackendSurfaceWidget::begin(['title' => 'Сделка', 'titleTag' => 'h2', 'raised' => true, 'responsive' => true]); ?>
            <p><?= Requests::leadLink($lead); ?></p>
            <span class="sx-lead-hint__meta">
                Ответственный: <?= Requests::employeeLink($lead->executor); ?> · <?= Html::encode($lead->staffStatusName); ?>
            </span>
        <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>
</div>
