<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCrmAccessRequest;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use yii\helpers\Html;

/**
 * «Передать клиента» (П2а, owner's decision 28.10.2026): a change of the main
 * responsible, by an administrator or a leader of an employee of the client in
 * its card. The new one becomes the main responsible ("full"); the former main
 * keeps his link with the level "Свои сделки и данные компании" (it may be
 * removed on the access page), other employees keep theirs. Open deals and
 * open tasks of the client and of its open deals go to the new main except
 * those the leader keeps with their executors (a kept deal keeps its tasks);
 * closed deals and the history do not change.
 */
class CrmClientTransfer
{
    private CrmAccess $access;

    public function __construct(?CrmAccess $access = null)
    {
        $this->access = $access ?? CrmAccess::instance();
    }

    /** Open deals and open tasks of the client that would move: what the leader may keep. */
    public function openWork(string $recordCode, int $recordId): array
    {
        $column = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? 'cms_company_id' : 'cms_user_id';
        $leads = CmsLead::find()->andWhere([$column => $recordId, 'status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])
            ->with(['executor'])->orderBy(['id' => SORT_DESC])->all();
        $task = CmsTask::tableName();
        // Tasks of the client and tasks of its open deals (a deal task may have no company of its own).
        $condition = ['or', [$task.'.'.$column => $recordId]];
        if ($leads) {
            $condition[] = [$task.'.cms_lead_id' => array_map(static fn($lead) => (int)$lead->id, $leads)];
        }

        return [
            'leads' => $leads,
            'tasks' => CmsTask::find()->andWhere($condition)->andWhere([$task.'.status' => CmsTask::activeStatuses()])
                ->orderBy([$task.'.id' => SORT_DESC])->all(),
        ];
    }

    /**
     * «Передать всех клиентов» of an employee (owner's decision 29.10.2026, п. 13а):
     * the clients he is the main responsible of, his open deals of them and his
     * open tasks of them and of those deals.
     *
     * @return array clients (code, id, name), leads (CmsLead[]), tasks (CmsTask[])
     */
    public function workOf(int $userId): array
    {
        $clients = [];
        $companies = CmsCompany::find()->andWhere(['id' => \skeeks\cms\models\CmsCompany2manager::find()->select('cms_company_id')
            ->andWhere(['cms_user_id' => $userId, 'is_main' => 1])])->orderBy(['name' => SORT_ASC])->all();
        foreach ($companies as $company) {
            $clients[] = ['code' => CmsCrmAccessRequest::RECORD_COMPANY, 'id' => (int)$company->id, 'name' => (string)$company->name];
        }
        $persons = CmsUser::find()->andWhere(['id' => \skeeks\cms\models\CmsUser2manager::find()->select('client_id')
            ->andWhere(['worker_id' => $userId, 'is_main' => 1])])->andWhere(['is_worker' => 0])->all();
        foreach ($persons as $person) {
            $clients[] = ['code' => CmsCrmAccessRequest::RECORD_CLIENT, 'id' => (int)$person->id, 'name' => (string)$person->shortDisplayName];
        }
        $companyIds = array_map(static fn($company) => (int)$company->id, $companies);
        $personIds = array_map(static fn($person) => (int)$person->id, $persons);
        $leads = $companyIds || $personIds ? CmsLead::find()->andWhere(['executor_id' => $userId, 'status' => [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK]])
            ->andWhere(['or', ['cms_company_id' => $companyIds ?: [0]], ['cms_user_id' => $personIds ?: [0]]])
            ->orderBy(['id' => SORT_DESC])->all() : [];
        $task = CmsTask::tableName();
        $tasks = $companyIds || $personIds ? CmsTask::find()->andWhere([$task.'.executor_id' => $userId, $task.'.status' => CmsTask::activeStatuses()])
            ->andWhere(['or', [$task.'.cms_company_id' => $companyIds ?: [0]], [$task.'.cms_user_id' => $personIds ?: [0]],
                [$task.'.cms_lead_id' => array_map(static fn($lead) => (int)$lead->id, $leads) ?: [0]]])
            ->orderBy([$task.'.id' => SORT_DESC])->all() : [];

        return ['clients' => $clients, 'leads' => $leads, 'tasks' => $tasks];
    }

    /**
     * Gives every client of $fromUserId (he is the main one) to $toUserId: the
     * main responsible, his open deals and tasks of them (except the kept
     * ones). The former main one stays with "edit" or, $removeFormer (a
     * dismissal), leaves the client. Each client is written to the journal.
     *
     * @return string the result in words
     */
    public function transferAllOf(int $fromUserId, int $toUserId, int $actorId, array $keepLeadIds = [], array $keepTaskIds = [], bool $removeFormer = false): string
    {
        if (!$this->access->canTransferClientsOf($actorId, $fromUserId)) {
            throw new \DomainException('Передать клиентов сотрудника может его руководитель или администратор.');
        }
        $newUser = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $toUserId, CmsUser::tableName().'.is_active' => 1])->one();
        if (!$newUser || $toUserId === $fromUserId) {
            throw new \DomainException('Выберите другого активного сотрудника.');
        }
        $keepLeadIds = array_map('intval', $keepLeadIds);
        $keepTaskIds = array_map('intval', $keepTaskIds);
        $work = $this->workOf($fromUserId);
        $from = CmsUser::findOne($fromUserId);
        $fromName = $from ? (string)$from->shortDisplayName : '';

        $transaction = CmsLead::getDb()->beginTransaction();
        try {
            foreach ($work['clients'] as $client) {
                $this->access->setMain($client['code'], $client['id'], $toUserId, CrmAccess::LEVEL_EDIT);
                CrmAccessLog::write(CrmAccessLog::TRANSFER, $client['code'], $client['id'], [
                    'actor_id' => $actorId, 'user_id' => $toUserId, 'level_to' => CrmAccess::LEVEL_FULL,
                    'comment' => 'все клиенты сотрудника '.$fromName.($removeFormer ? ', при увольнении' : ''),
                ]);
                if ($removeFormer) {
                    $this->access->removeLink($client['code'], $client['id'], $fromUserId);
                    CrmAccessLog::write(CrmAccessLog::REMOVE, $client['code'], $client['id'], [
                        'actor_id' => $actorId, 'user_id' => $fromUserId, 'level_from' => CrmAccess::LEVEL_EDIT, 'comment' => 'увольнение',
                    ]);
                }
            }
            $leads = 0;
            foreach ($work['leads'] as $lead) {
                if (in_array((int)$lead->id, $keepLeadIds, true)) {
                    continue;
                }
                $lead->executor_id = $toUserId;
                if (!$lead->save(false, ['executor_id', 'updated_at'])) {
                    throw new \DomainException('Сделка №'.(int)$lead->id.' не передана.');
                }
                $lead->addSystemActivity('Клиенты сотрудника '.Html::encode($fromName).' переданы сотруднику '
                    .Html::encode((string)$newUser->shortDisplayName).', сделка перешла к нему.', $actorId);
                $leads++;
            }
            $tasks = 0;
            foreach ($work['tasks'] as $task) {
                if (in_array((int)$task->id, $keepTaskIds, true) || ($task->cms_lead_id && in_array((int)$task->cms_lead_id, $keepLeadIds, true))) {
                    continue;
                }
                $task->updateAttributes(['executor_id' => $toUserId]);
                $tasks++;
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
        if ($work['clients'] && \Yii::$app->has('notifications')) {
            \Yii::$app->notifications->notify(array_values(array_diff([$toUserId], [$actorId])) ?: [], 'crm.access_decided', [
                'name' => 'Вам переданы клиенты сотрудника '.$fromName.': '.count($work['clients']),
                'eventKey' => 'crm.transfer_all:'.$fromUserId.':'.$toUserId.':'.time(),
            ]);
        }

        return 'Клиентов передано: '.count($work['clients']).', открытых сделок: '.$leads.', задач: '.$tasks.'.';
    }

    /**
     * @param int[] $keepLeadIds open deals that stay with their executors
     * @param int[] $keepTaskIds open tasks that stay with their executors
     */
    public function transfer(string $recordCode, int $recordId, int $newUserId, int $actorId, array $keepLeadIds = [], array $keepTaskIds = []): string
    {
        if (!$this->access->canTransferClient($actorId, $recordCode, $recordId)) {
            throw new \DomainException('Передать клиента может руководитель ответственного или администратор.');
        }
        $record = $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne($recordId) : CmsUser::findOne($recordId);
        $newUser = CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $newUserId, CmsUser::tableName().'.is_active' => 1])->one();
        if (!$record || !$newUser) {
            throw new \DomainException('Клиент или сотрудник не найден.');
        }
        $keepLeadIds = array_map('intval', $keepLeadIds);
        $keepTaskIds = array_map('intval', $keepTaskIds);

        $previousMain = $this->access->mainResponsibleId($recordCode, $recordId);
        $previous = $previousMain ? [$previousMain] : ($recordCode === CmsCrmAccessRequest::RECORD_COMPANY
            ? $this->access->companyResponsibleIds($recordId)
            : $this->access->clientResponsibleIds($recordId));

        $transaction = CmsLead::getDb()->beginTransaction();
        try {
            $previousMain = $this->access->mainResponsibleId($recordCode, $recordId);
            $this->access->setMain($recordCode, $recordId, $newUserId, CrmAccess::LEVEL_EDIT);
            CrmAccessLog::write(CrmAccessLog::TRANSFER, $recordCode, $recordId, [
                'actor_id' => $actorId, 'user_id' => $newUserId, 'level_to' => CrmAccess::LEVEL_FULL,
                'comment' => $previousMain && ($was = CmsUser::findOne($previousMain)) ? 'прежний основной — '.$was->shortDisplayName.', у него «Свои сделки и данные компании»' : null,
            ]);

            $work = $this->openWork($recordCode, $recordId);
            $leads = 0;
            foreach ($work['leads'] as $lead) {
                if (in_array((int)$lead->id, $keepLeadIds, true) || (int)$lead->executor_id === $newUserId) {
                    continue;
                }
                $lead->executor_id = $newUserId;
                if ($lead->status === CmsLead::STATUS_NEW) {
                    $lead->status = CmsLead::STATUS_IN_WORK;
                }
                if (!$lead->save()) {
                    throw new \DomainException('Сделка №'.(int)$lead->id.': '.implode('; ', $lead->getFirstErrors()));
                }
                $lead->addSystemActivity('Клиент передан сотруднику '.Html::encode((string)$newUser->shortDisplayName).', сделка перешла к нему.', $actorId);
                $leads++;
            }
            $tasks = 0;
            foreach ($work['tasks'] as $task) {
                // A task of a deal the leader kept stays with it.
                if (in_array((int)$task->id, $keepTaskIds, true) || (int)$task->executor_id === $newUserId
                    || ($task->cms_lead_id && in_array((int)$task->cms_lead_id, $keepLeadIds, true))) {
                    continue;
                }
                // The executor only: the status, the time and the history of the task stay.
                $task->updateAttributes(['executor_id' => $newUserId]);
                $tasks++;
            }

            $log = new CmsLog([
                'log_type' => CmsLog::LOG_TYPE_COMMENT,
                'model_code' => $record->skeeksModelCode,
                'model_id' => (int)$record->id,
                'model_as_text' => $record->asText,
                'cms_company_id' => $recordCode === CmsCrmAccessRequest::RECORD_COMPANY ? (int)$record->id : null,
                'cms_user_id' => $recordCode === CmsCrmAccessRequest::RECORD_CLIENT ? (int)$record->id : null,
                'comment' => 'Основной ответственный — '.Html::encode((string)$newUser->shortDisplayName)
                    .'. Перешло открытых сделок: '.$leads.', задач: '.$tasks.'.',
            ]);
            if (!$log->save()) {
                throw new \DomainException(implode('; ', $log->getFirstErrors()));
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }

        $recipients = array_values(array_diff(array_unique(array_merge([$newUserId], $previous)), [$actorId]));
        if ($recipients) {
            \Yii::$app->notifications->notify($recipients, 'crm.access_decided', [
                'name' => 'Клиент передан сотруднику '.$newUser->shortDisplayName,
                'model' => $record,
                'eventKey' => 'crm.transfer:'.$recordCode.':'.$recordId.':'.time(),
            ]);
        }

        return 'Клиент передан сотруднику '.$newUser->shortDisplayName.'. Перешло открытых сделок: '.$leads.', задач: '.$tasks.'.';
    }
}
