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
            $this->access->setMain($recordCode, $recordId, $newUserId, CrmAccess::LEVEL_EDIT);

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
