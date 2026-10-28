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
 * @var bool $protectionReady П2д: the protection of the client (crm\CrmProtection)
 * @var string $protectionState free|protected|always
 * @var string $protectionText «Под защитой до …: последний платёж …»
 * @var bool $canTransfer
 * @var string $transferUrl
 * @var array $work leads (CmsLead[]), tasks (CmsTask[]) for the transfer
 * @var skeeks\cms\models\CmsCrmAccessRequest[] $requests pending requests about the client
 * @var string|null $myLevel the viewer's own level in words
 * @var skeeks\cms\models\CmsCrmAccessLog[] $history the journal of the access to this client
 */

use skeeks\cms\assets\admin\LeadIdentityAsset;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\controllers\AdminCmsCrmAccessRequestController as Requests;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
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
            <ul class="sx-crm-access__list">
                <?= $this->render('@skeeks/cms/views/crm/_access-row', [
                    'employee' => $main, 'mode' => 'tab', 'canManage' => false, 'accessUrl' => $url,
                ]); ?>
            </ul>
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
        'hint' => '«Свои сделки» — карточка клиента, звонки и письма, всё по своим сделкам, новый телефон или e-mail. «Свои сделки и данные компании» — плюс правка данных: телефоны, e-mail, люди, адреса, реквизиты. «Полный» — плюс все сделки, услуги, счета, платежи и документы клиента, удаление; на двух первых уровнях чужие сделки и финансы скрыты. Добавить сотрудника — кнопкой под списком.',
        'raised' => true,
        'responsive' => true,
    ]); ?>
        <?php if (!$others) : ?>
            <p class="sx-lead-identity__note">Кроме основного ответственного, с клиентом никто не работает.</p>
        <?php endif; ?>
        <ul class="sx-crm-access__list">
            <?php foreach ($others as $employee) : ?>
                <?= $this->render('@skeeks/cms/views/crm/_access-row', [
                    'employee' => $employee, 'mode' => 'tab', 'canManage' => $canManage, 'accessUrl' => $url,
                ]); ?>
            <?php endforeach; ?>
        </ul>
        <?php if ($canManage) : ?>
            <?= $this->render('@skeeks/cms/views/crm/_access-add-button', [
                'controllerRoute' => '/'.Yii::$app->controller->uniqueId, 'recordId' => (int)$model->id,
            ]); ?>
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

    <?php if (!empty($protectionReady)) : ?>
        <?php BackendSurfaceWidget::begin([
            'title' => 'Защита клиента',
            'titleTag' => 'h2',
            'hint' => '«Свободная» — нет платежей за последнее время и действующих услуг: без ответственного клиента берут в работу, с ответственным — работают по своей сделке с уведомлением. «Под защитой» — был платёж или есть действующая услуга: другой менеджер работает только по согласованию, сделка после одобрения. «Всегда по согласованию» — для ключевых клиентов, не истекает. Обращения клиента не ждут одобрения.',
            'raised' => true,
            'responsive' => true,
        ]); ?>
            <p><strong><?= Html::encode($protectionText); ?></strong></p>
            <div class="sx-crm-access__actions">
                <?= Html::dropDownList('protection', $protectionState === 'always' ? 'always' : 'auto', [
                    'auto' => 'Авто: по платежам и услугам',
                    'always' => 'Всегда по согласованию',
                ], [
                    'class' => 'form-control',
                    'disabled' => !$canManage,
                    'aria-label' => 'Защита клиента',
                    'data-sx-crm-access' => 'protection',
                    'data-url' => $url,
                ]); ?>
                <?php if ($canManage && $main && $protectionState === 'free') : ?>
                    <?= Html::button('Вернуть в общий пул', [
                        'class' => 'sx-button sx-button--secondary sx-button--sm',
                        'data-sx-crm-access' => 'pool',
                        'data-url' => $url,
                        'data-sx-confirm' => 'Вернуть клиента в общий пул? У него не останется ответственных сотрудников, его возьмёт в работу первый, кто его найдёт.',
                    ]); ?>
                <?php endif; ?>
            </div>
        <?php BackendSurfaceWidget::end(); ?>
    <?php else : ?>
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
    <?php endif; ?>
    <?php if (!empty($history)) : ?>
        <?php BackendSurfaceWidget::begin([
            'title' => 'История доступа',
            'titleTag' => 'h2',
            'hint' => 'Кто, когда и на каком основании получал доступ к этому клиенту. Весь журнал — во вкладке «Доступы» раздела «Компании».',
            'raised' => true,
            'responsive' => true,
        ]); ?>
            <?= $this->render('@skeeks/cms/views/crm/_access-log-table', ['events' => $history, 'withClient' => false]); ?>
        <?php BackendSurfaceWidget::end(); ?>
    <?php endif; ?>
</div>
