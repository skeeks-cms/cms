<?php

namespace skeeks\cms\services;

use skeeks\cms\forms\CmsLeadNoAnswerForm;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTask;
use yii\helpers\Html;

/**
 * Records a failed call to a lead and plans what happens next.
 *
 * In one transaction: the attempt counter grows, the manager's nearest open
 * task on the lead is moved to the new time (or a call task is created), and a
 * lead comment states the attempt and the decision. Closing the lead as
 * unreachable rejects it through normal validation and cancels the open tasks
 * the manager participates in. Task status changes go through TaskWorkflow.
 */
class CmsLeadNoAnswerService
{
    public const DEFAULT_CALL_DURATION = 15 * 60;

    /** @var TaskWorkflow */
    private $workflow;

    public function __construct(?TaskWorkflow $workflow = null)
    {
        $this->workflow = $workflow ?: new TaskWorkflow();
    }

    public function apply(CmsLead $lead, CmsLeadNoAnswerForm $form): CmsLog
    {
        $actorId = (int)\Yii::$app->user->id;
        if (!$actorId) {
            throw new \RuntimeException('Нужен авторизованный сотрудник.');
        }

        $log = CmsLead::getDb()->transaction(function () use ($lead, $form, $actorId) {
            $lead->updateCounters(['no_answer_count' => 1]);
            $attempt = (int)$lead->no_answer_count;

            $decision = $form->isReject
                ? $this->reject($lead, $attempt, $actorId, $form->reject_reason_id ? (int)$form->reject_reason_id : null)
                : $this->planCall($lead, (int)$form->callbackTime, $actorId);

            $comment = Html::tag('p', 'Не дозвонились, попытка '.$attempt.'.').Html::tag('p', $decision);
            if ($form->comment !== null) {
                $comment .= Html::tag('p', nl2br(Html::encode((string)$form->comment)));
            }

            $log = new CmsLog();
            $log->log_type = CmsLog::LOG_TYPE_COMMENT;
            $log->model_code = $lead->skeeksModelCode;
            $log->model_id = $lead->id;
            $log->model_as_text = $lead->asText;
            $log->comment = $comment;
            $log->is_pinned = 0;
            if (!$log->save()) {
                throw new \RuntimeException(implode('; ', $log->getFirstErrors()));
            }

            return $log;
        });

        $lead->notifyPartnerAboutComment((int)$log->id);

        return $log;
    }

    private function planCall(CmsLead $lead, int $callAt, int $actorId): string
    {
        $when = \Yii::$app->formatter->asDatetime($callAt, 'd MMMM, HH:mm');

        $task = $lead->getActiveTasks()->andWhere([CmsTask::tableName().'.executor_id' => $actorId])->limit(1)->one();
        if ($task) {
            if ($task->status === CmsTask::STATUS_IN_WORK) {
                $this->workflow->transition($task, CmsTask::STATUS_ON_PAUSE);
                $task->refresh();
            }
            $task->plan_start_at = $callAt;
            $this->save($task);

            return 'Задача «'.Html::encode($task->name).'» перенесена на '.$when.'.';
        }

        $task = new CmsTask();
        $task->name = 'Перезвонить: '.$lead->displayName;
        $task->executor_id = $actorId;
        $task->cms_lead_id = (int)$lead->id;
        if ($lead->cms_company_id) {
            $task->cms_company_id = (int)$lead->cms_company_id;
        } elseif ($lead->cms_user_id) {
            $task->cms_user_id = (int)$lead->cms_user_id;
        }
        $task->plan_start_at = $callAt;
        $task->plan_duration = self::DEFAULT_CALL_DURATION;
        $phone = $lead->mainPhone;
        if ($phone) {
            $task->description = Html::tag('p', 'Телефон: '.Html::encode($phone->value));
        }
        $this->save($task);

        return 'Поставлена задача «'.Html::encode($task->name).'» на '.$when.'.';
    }

    private function reject(CmsLead $lead, int $attempt, int $actorId, ?int $reasonId): string
    {
        $lead->status = CmsLead::STATUS_REJECTED;
        $lead->cms_lead_reject_reason_id = $reasonId;
        $lead->reject_reason = 'Не удалось дозвониться (попыток: '.$attempt.')';
        $lead->processed_at = time();
        if (!$lead->save()) {
            throw new \RuntimeException(implode('; ', $lead->getFirstErrors()));
        }

        $canceled = 0;
        foreach ($lead->getActiveTasks()->all() as $task) {
            if ((int)$task->executor_id !== $actorId && (int)$task->created_by !== $actorId) {
                continue;
            }
            $this->workflow->transition($task, CmsTask::STATUS_CANCELED);
            $canceled++;
        }

        return 'Лид закрыт: не удалось дозвониться.'.($canceled ? ' Отменено открытых задач: '.$canceled.'.' : '');
    }

    private function save(CmsTask $task): void
    {
        if (!$task->save()) {
            throw new \RuntimeException(implode('; ', $task->getFirstErrors()));
        }
    }
}
