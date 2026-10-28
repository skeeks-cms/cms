<?php
/**
 * «Доступ» of a company or a client (П2а+, owner's decision 28.10.2026): all
 * about who works with the client in one tab — the main responsible and
 * «Передать клиента», the employees with their level in words and where the
 * access comes from, «Добавить сотрудника», «Доступ только по согласованию»,
 * pending requests. Changed by the main responsible, his leaders and
 * administrators; others see it read only with their own level.
 * ClientTransferActions::clientAccess().
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCompany|skeeks\cms\models\CmsUser $model
 * @var array[] $employees id, name, level, main, origin (CrmAccess::accessList())
 * @var array[] $leaders id, name — leaders of the main responsible
 * @var bool $canManage
 * @var bool $approval
 * @var bool $canTransfer
 * @var string $transferUrl
 * @var array $work leads (CmsLead[]), tasks (CmsTask[]) for the transfer
 * @var skeeks\cms\models\CmsCrmAccessRequest[] $requests pending requests about the client
 * @var string|null $myLevel the viewer's own level in words
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\controllers\AdminCmsCrmAccessRequestController as Requests;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use yii\helpers\Html;
use yii\helpers\Url;

LeadIdentityAsset::register($this);
$levels = CrmAccess::levels();
$url = Url::current();
$main = null;
$others = [];
foreach ($employees as $employee) {
    if ($employee['main'] && !$main) {
        $main = $employee;
    } else {
        $others[] = $employee;
    }
}
$worker = static fn(int $id) => ($user = CmsUser::findOne($id)) ? CmsWorkerViewWidget::widget(['user' => $user]) : '';
$workerSearch = static function ($word = '') {
    $query = CmsUser::find()->isWorker()->cmsSite()->andWhere([CmsUser::tableName().'.is_active' => 1]);

    return $word ? $query->search($word) : $query;
};
?>
<div class="sx-surface-stack">
    <?php if ($myLevel !== null && !$canManage) : ?>
        <p class="sx-lead-identity__note">Ваш доступ к клиенту: <strong><?= Html::encode($myLevel); ?></strong>. Менять доступ может основной ответственный, его руководитель или администратор.</p>
    <?php endif; ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Основной ответственный',
        'titleTag' => 'h2',
        'hint' => 'Ведёт клиента: одобряет запросы, получает уведомления о письмах, сообщениях и звонках клиента, показан первым. Передать клиента может руководитель отдела или администратор; прежний основной остаётся с уровнем «Свои сделки и данные компании».',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?php if ($main) : ?>
            <?= $worker($main['id']); ?>
        <?php else : ?>
            <p class="sx-lead-identity__note">Основного ответственного нет.</p>
        <?php endif; ?>
        <?php if ($leaders) : ?>
            <p class="sx-lead-identity__note">Руководитель отдела видит клиента полностью: <?= Html::encode(implode(', ', array_column($leaders, 'name'))); ?>.</p>
        <?php endif; ?>

        <?php if ($canTransfer) : ?>
            <details class="sx-crm-access__transfer">
                <summary class="sx-button sx-button--secondary sx-button--sm">Передать клиента</summary>
                <?= Html::beginForm($transferUrl, 'post', ['data-sx-lead-ajax-form' => true]); ?>
                    <div class="sx-lead-identity__part">
                        <?= AjaxSelectModel::widget([
                            'id' => 'sx-crm-transfer-executor',
                            'name' => 'executor_id',
                            'modelClass' => CmsUser::class,
                            'modelShowAttribute' => 'shortDisplayName',
                            'searchQuery' => $workerSearch,
                            'options' => ['placeholder' => 'Новый основной ответственный'],
                        ]); ?>
                    </div>
                    <?php if ($work['leads'] || $work['tasks']) : ?>
                        <p class="sx-lead-identity__note">Открытые сделки и задачи перейдут новому ответственному. Отметьте те, что остаются у прежнего исполнителя.</p>
                        <ul class="sx-lead-triage__repeats">
                            <?php foreach ($work['leads'] as $lead) : ?>
                                <li class="sx-lead-triage__repeat">
                                    <span>Сделка №<?= (int)$lead->id; ?> «<?= Html::encode((string)$lead->name); ?>»
                                        <span class="sx-lead-hint__meta">— <?= Html::encode($lead->executor ? (string)$lead->executor->shortDisplayName : 'без ответственного'); ?></span></span>
                                    <label class="sx-lead-identity__choice"><?= Html::checkbox('keep_leads[]', false, ['value' => (int)$lead->id]); ?> оставить</label>
                                </li>
                            <?php endforeach; ?>
                            <?php foreach ($work['tasks'] as $task) : ?>
                                <li class="sx-lead-triage__repeat">
                                    <span>Задача №<?= (int)$task->id; ?> «<?= Html::encode((string)$task->name); ?>»
                                        <span class="sx-lead-hint__meta">— <?= Html::encode($task->executor ? (string)$task->executor->shortDisplayName : '—'); ?></span></span>
                                    <label class="sx-lead-identity__choice"><?= Html::checkbox('keep_tasks[]', false, ['value' => (int)$task->id]); ?> оставить</label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="sx-lead-hint__actions">
                        <?= Html::submitButton('Передать', [
                            'class' => 'sx-button sx-button--primary sx-button--sm',
                            'data-confirm' => 'Передать клиента? Новый сотрудник станет основным ответственным.',
                        ]); ?>
                    </div>
                <?= Html::endForm(); ?>
            </details>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Работают с клиентом',
        'titleTag' => 'h2',
        'hint' => '«Свои сделки» — карточка клиента, звонки и письма, всё по своим сделкам, новый телефон или e-mail. «Свои сделки и данные компании» — плюс правка данных: телефоны, e-mail, люди, адреса, реквизиты. «Полный» — всё, включая удаление.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?php if (!$others) : ?>
            <p class="sx-lead-identity__note">Кроме основного ответственного, с клиентом никто не работает.</p>
        <?php endif; ?>
        <ul class="sx-lead-requests">
            <?php foreach ($others as $employee) : ?>
                <li class="sx-lead-hint">
                    <div class="sx-lead-triage__request">
                        <?= $worker($employee['id']); ?>
                        <span class="sx-lead-hint__meta">
                            <?= Html::encode(($levels[$employee['level']] ?? $employee['level']).' · '.$employee['origin']); ?>
                        </span>
                    </div>
                    <?php if ($canManage) : ?>
                        <div class="sx-lead-hint__actions">
                            <?= Html::dropDownList('level', $employee['level'], $levels, [
                                'class' => 'form-control',
                                'aria-label' => 'Уровень доступа '.$employee['name'],
                                'data-sx-crm-access' => 'level',
                                'data-user-id' => $employee['id'],
                                'data-url' => $url,
                            ]); ?>
                            <?= Html::button('Убрать', [
                                'class' => 'sx-button sx-button--secondary sx-button--sm',
                                'data-sx-crm-access' => 'remove',
                                'data-user-id' => $employee['id'],
                                'data-url' => $url,
                                'data-sx-confirm' => 'Сотрудник '.$employee['name'].' больше не будет работать с клиентом. Его сделки останутся у него.',
                            ]); ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($canManage) : ?>
            <div class="sx-lead-hint__actions sx-crm-access__add">
                <?= AjaxSelectModel::widget([
                    'id' => 'sx-crm-access-add-user',
                    'name' => 'add_user_id',
                    'modelClass' => CmsUser::class,
                    'modelShowAttribute' => 'shortDisplayName',
                    'searchQuery' => $workerSearch,
                    'options' => ['placeholder' => 'Сотрудник'],
                ]); ?>
                <?= Html::dropDownList('add_level', CrmAccess::LEVEL_DEAL, $levels, [
                    'id' => 'sx-crm-access-add-level',
                    'class' => 'form-control',
                    'aria-label' => 'Уровень доступа нового сотрудника',
                ]); ?>
                <?= Html::button('Добавить сотрудника', [
                    'class' => 'sx-button sx-button--primary sx-button--sm',
                    'data-sx-crm-access' => 'add',
                    'data-user-input' => '#sx-crm-access-add-user',
                    'data-level-input' => '#sx-crm-access-add-level',
                    'data-url' => $url,
                ]); ?>
            </div>
        <?php endif; ?>
    <?php BackendSurfaceWidget::end(); ?>

    <?php if ($requests) : ?>
        <?php BackendSurfaceWidget::begin([
            'title' => 'Запросы по клиенту',
            'titleTag' => 'h2',
            'hint' => 'Сотрудники, которые работают с клиентом по своей сделке или просят разрешения. Решение — в карточке запроса.',
            'raised' => true,
            'responsive' => true,
        ]); ?>
            <ul class="sx-lead-requests">
                <?php foreach ($requests as $request) : ?>
                    <li class="sx-lead-hint">
                        <div class="sx-lead-triage__request">
                            <?= BackendEntityLink::widget([
                                'controllerId' => '/cms/admin-cms-crm-access-request', 'modelId' => $request->id, 'action' => 'view',
                                'label' => $request->asText(),
                            ]); ?>
                            <span class="sx-lead-hint__meta">
                                <?= Requests::employeeLink($request->requester); ?>
                                <?php if ($request->levelName !== '') : ?> · уровень «<?= Html::encode($request->levelName); ?>»<?php endif; ?>
                                · <?= Yii::$app->formatter->asDatetime($request->created_at, 'short'); ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>

    <?php BackendSurfaceWidget::begin([
        'title' => 'Доступ только по согласованию',
        'titleTag' => 'h2',
        'hint' => 'Для ключевых клиентов. Выключено: сотрудник, который нашёл клиента, сразу работает по своей сделке, основному ответственному приходит уведомление. Включено: сначала уходит запрос, сделка создаётся после одобрения. Обращения клиента не ждут одобрения.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <label class="sx-lead-identity__choice">
            <?= Html::checkbox('approval', $approval, [
                'disabled' => !$canManage,
                'data-sx-crm-access' => 'approval',
                'data-url' => $url,
            ]); ?>
            Работать с клиентом только по согласованию с основным ответственным
        </label>
    <?php BackendSurfaceWidget::end(); ?>
</div>
