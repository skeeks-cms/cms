<?php
/**
 * Events of the journal of the access to clients (crm\CrmAccessLog) as a
 * table: the history at the bottom of
 * «Доступ» of a client (the full journal is the section «Доступы»). A pending
 * request links its card (the decision).
 *
 * @var yii\web\View $this
 * @var skeeks\cms\models\CmsCrmAccessLog[] $events
 * @var bool $withClient show the client column
 */

use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\controllers\AdminCmsCrmAccessRequestController as Requests;
use skeeks\cms\crm\CrmAccess;
use skeeks\cms\models\CmsCompany;
use yii\helpers\Html;

$levels = CrmAccess::levels();
$level = static fn($code) => $code ? ($levels[$code] ?? $code) : '';
?>
<?php if (!$events) : ?>
    <p class="sx-lead-identity__note">Событий нет.</p>
<?php else : ?>
    <div class="table-responsive">
        <table class="table sx-crm-access-log">
            <thead>
                <tr>
                    <th>Когда</th>
                    <th>Событие</th>
                    <?php if ($withClient) : ?><th>Клиент</th><?php endif; ?>
                    <th>Кто</th>
                    <th>Кому</th>
                    <th>Уровень</th>
                    <th>Основание</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event) : ?>
                    <?php
                    $record = $withClient ? $event->getRecord() : null;
                    $grounds = array_filter([
                        $event->request_id ? BackendEntityLink::widget([
                            'controllerId' => '/cms/admin-cms-crm-access-request', 'modelId' => (int)$event->request_id, 'action' => 'view',
                            'label' => 'Запрос №'.(int)$event->request_id.($event->request && $event->request->isPending ? ' — ждёт решения' : ''),
                        ]) : null,
                        $event->cms_lead_id ? Requests::leadLink($event->lead) : null,
                        $event->shop_payment_id ? Html::encode('Платёж №'.(int)$event->shop_payment_id) : null,
                        $event->comment ? Html::encode((string)$event->comment) : null,
                    ]);
                    ?>
                    <tr>
                        <td><?= Yii::$app->formatter->asDatetime($event->created_at, 'short'); ?></td>
                        <td><?= Html::encode($event->eventName); ?></td>
                        <?php if ($withClient) : ?>
                            <td><?= $record ? BackendEntityLink::widget([
                                    'controllerId' => $record instanceof CmsCompany ? '/cms/admin-cms-company' : '/cms/admin-user',
                                    'modelId' => (int)$record->id, 'action' => 'view',
                                    'label' => $record instanceof CmsCompany ? (string)$record->name : (string)$record->shortDisplayName,
                                ]) : '—'; ?></td>
                        <?php endif; ?>
                        <td><?= $event->actor ? Requests::employeeLink($event->actor) : 'CRM'; ?></td>
                        <td><?= $event->user ? Requests::employeeLink($event->user) : '—'; ?></td>
                        <td><?= Html::encode(trim($level($event->level_from).($event->level_from || $event->level_to ? ' → ' : '').$level($event->level_to), ' →')) ?: '—'; ?></td>
                        <td><?= $grounds ? implode('<br>', $grounds) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
