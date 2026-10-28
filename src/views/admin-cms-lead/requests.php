<?php
/**
 * Requests about a client of another main responsible (П2а): «Работать с
 * этим клиентом» — the main responsible (his leader, an administrator) gives a
 * level or declines; my own requests.
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCrmAccessRequest[] $toDecide
 * @var skeeks\cms\models\CmsCrmAccessRequest[] $mine
 * @var skeeks\cms\models\CmsCrmAccessRequest[] $decided decided requests the employee could decide
 * @var skeeks\cms\crm\CrmAccessRequests $service
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsCrmAccessRequest;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$decideUrl = Url::to(['/cms/admin-cms-lead/request-decide']);
$row = static function (CmsCrmAccessRequest $request, bool $decide) use ($service, $decideUrl): string {
    $lead = $request->cms_lead_id ? $request->lead : null;
    // A private person the viewer does not see stays "контакт из базы" (no name).
    $clientName = $service->recordName($request, (int)\Yii::$app->user->id);
    $html = Html::tag('strong', Html::encode($request->kindName)).' — '
        .Html::encode($request->record_code === CmsCrmAccessRequest::RECORD_COMPANY
            ? 'компания «'.$clientName.'»'
            : ($request->record && \skeeks\cms\crm\CrmAccess::instance()->canSeeClient((int)\Yii::$app->user->id, (int)$request->record_id) ? 'контакт «'.$clientName.'»' : $clientName));
    $meta = [];
    $meta[] = 'сотрудник '.Html::encode($request->requester ? (string)$request->requester->shortDisplayName : '—');
    if ($request->owner) {
        $meta[] = 'основной ответственный '.Html::encode((string)$request->owner->shortDisplayName);
    }
    if ($lead) {
        $meta[] = 'сделка '.Html::a('№'.(int)$lead->id, ['/cms/admin-cms-lead/view', 'pk' => $lead->id], ['data-pjax' => 0, 'target' => '_blank']);
    }
    $meta[] = \Yii::$app->formatter->asDatetime($request->created_at, 'short');
    $meta[] = Html::encode($request->statusName);
    $html .= Html::tag('span', implode(' · ', $meta), ['class' => 'sx-lead-hint__meta']);
    if ($request->comment) {
        $html .= Html::tag('p', Html::encode((string)$request->comment));
    }
    if ($request->decision_comment) {
        $html .= Html::tag('p', 'Ответ: '.Html::encode((string)$request->decision_comment));
    }
    if (!$request->isPending && $request->decidedBy) {
        $html .= Html::tag('span', 'Решил '.Html::encode((string)$request->decidedBy->shortDisplayName).', '
            .\Yii::$app->formatter->asDatetime($request->decided_at, 'short'), ['class' => 'sx-lead-hint__meta']);
    }
    if ($request->levelName !== '') {
        $html .= Html::tag('span', 'Уровень: '.Html::encode($request->levelName), ['class' => 'sx-lead-hint__meta']);
    }
    $actions = '';
    if ($decide && $request->kind !== CmsCrmAccessRequest::KIND_DELEGATE) {
        // The employee already has "Свои сделки" (work): the main responsible may raise it.
        $levels = \skeeks\cms\crm\CrmAccess::levels();
        $actions .= Html::dropDownList('level', $request->level ?: \skeeks\cms\crm\CrmAccess::LEVEL_DEAL,
            array_map(static fn($name) => 'Доступ: '.mb_strtolower($name, 'UTF-8'), $levels),
            ['class' => 'form-control', 'data-sx-crm-request-level' => true, 'aria-label' => 'Уровень доступа']);
    }
    if ($decide) {
        $actions .= Html::button('Одобрить', [
            'class' => 'sx-button sx-button--primary sx-button--sm',
            'data-sx-crm-request-decide' => 'approve',
            'data-id' => (int)$request->id,
            'data-url' => $decideUrl,
            'data-confirm' => $request->kind === CmsCrmAccessRequest::KIND_DELEGATE
                ? 'Передать сделку сотруднику? Клиент останется у ответственного.'
                : ($request->kind === CmsCrmAccessRequest::KIND_WORK && !$request->cms_lead_id
                    ? 'Разрешить сотруднику работать с клиентом? Ему будет создана сделка.'
                    : 'Выдать сотруднику выбранный доступ к клиенту?'),
        ]);
        $actions .= Html::button($request->kind === CmsCrmAccessRequest::KIND_WORK && $request->cms_lead_id ? 'Не расширять' : 'Отклонить', [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-sx-crm-request-decide' => 'decline',
            'data-id' => (int)$request->id,
            'data-url' => $decideUrl,
        ]);
    } elseif ($request->isPending) {
        $actions .= Html::button('Отменить запрос', [
            'class' => 'sx-button sx-button--secondary sx-button--sm',
            'data-sx-crm-request-decide' => 'cancel',
            'data-id' => (int)$request->id,
            'data-url' => $decideUrl,
            'data-confirm' => 'Отменить запрос?',
        ]);
    }

    return Html::tag('li',
        Html::tag('div', $html, ['class' => 'sx-lead-triage__request'])
        .($actions ? Html::tag('div', $actions, ['class' => 'sx-lead-hint__actions']) : ''),
        ['class' => 'sx-lead-hint']
    );
};
?>
<div class="sx-surface-stack">
    <?php BackendSurfaceWidget::begin([
        'title' => 'Ждут вашего решения',
        'titleTag' => 'h2',
        'hint' => 'Сотрудники работают с вашими клиентами по своим сделкам (доступ «Свои сделки») или просят разрешения для клиентов «по согласованию». Решает основной ответственный, его руководитель или администратор: расширить доступ или отказать — сделка при отказе остаётся у сотрудника.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?php if ($toDecide) : ?>
            <ul class="sx-lead-requests">
                <?php foreach ($toDecide as $request) : ?>
                    <?= $row($request, true); ?>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="sx-lead-identity__note">Запросов нет.</p>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if ($decided) : ?>
        <?php BackendSurfaceWidget::begin([
            'title' => 'Решённые',
            'titleTag' => 'h2',
            'hint' => 'Запросы по вашим клиентам, которые уже решили вы или другой ответственный.',
            'raised' => true,
            'responsive' => true,
        ]); ?>
            <ul class="sx-lead-requests">
                <?php foreach ($decided as $request) : ?>
                    <?= $row($request, false); ?>
                <?php endforeach; ?>
            </ul>
        <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Мои запросы',
        'titleTag' => 'h2',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?php if ($mine) : ?>
            <ul class="sx-lead-requests">
                <?php foreach ($mine as $request) : ?>
                    <?= $row($request, false); ?>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p class="sx-lead-identity__note">Вы ещё не отправляли запросов.</p>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>
</div>
