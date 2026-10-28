<?php

namespace skeeks\cms\services;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadRejectReason;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\CmsLeadStageHistory;
use skeeks\cms\models\CmsUser;
use yii\db\Query;

/**
 * Pipeline report by the history of transitions (П2б), not by the current
 * column of the kanban.
 *
 * Period rules (shown to people in the report):
 * - the funnel, conversion, sources and responsible: deals that entered the
 *   pipeline in the period (a cohort); their movement is counted up to now;
 * - sales (first and repeat): deals closed successfully in the period;
 * - a deal counts once: the furthest stage it reached in this pipeline and
 *   its last result (a reopened deal is not a second sale);
 * - the amount is the agreed deal amount, once per deal; bills and payments
 *   are not added (they are other measures);
 * - «Неразобранное», sorting results (spam, added to another deal, support)
 *   and non-target reject reasons are outside the conversion base;
 * - time on stages only by real transitions: the snapshot of the move to
 *   pipelines (`is_migrated`) has no known start.
 */
class CmsLeadFunnelReportService
{
    /**
     * @param int $from unix time, inclusive
     * @param int $to unix time, inclusive
     * @param array $filters executor_id, source_type
     */
    public function build(?CmsLeadPipeline $pipeline, int $from, int $to, array $filters = []): array
    {
        $result = [
            'ready' => CmsLead::pipelineReady(),
            'steps' => [],
            'entered' => 0, 'taken' => 0, 'success' => 0, 'rejected' => 0, 'open' => 0,
            'conversion' => null, 'successAmount' => 0.0,
            'stageTimes' => [],
            'sales' => ['first' => 0, 'repeat' => 0, 'firstAmount' => 0.0, 'repeatAmount' => 0.0, 'unknown' => 0],
            'bySource' => [], 'byExecutor' => [],
            'migratedAt' => null,
        ];
        if (!$result['ready']) {
            return $result;
        }

        $stages = CmsLeadStage::forPipeline($pipeline);
        $position = [];
        // Path: 0 «Поступило» (Неразобранное), 1 «Взято в работу», 2.. the stages, then «Успешно».
        foreach ($stages as $index => $stage) {
            $position[(int)$stage->id] = $index + 2;
        }
        $successPosition = count($stages) + 2;

        $leadTable = CmsLead::tableName();
        $leadQuery = CmsLead::find()->forManager()->cmsSite()
            ->select(array_merge([$leadTable.'.id', $leadTable.'.status', $leadTable.'.created_at', $leadTable.'.processed_at', $leadTable.'.source_type',
                $leadTable.'.executor_id', $leadTable.'.deal_amount', $leadTable.'.cms_company_id', $leadTable.'.cms_user_id',
                $leadTable.'.cms_lead_reject_reason_id', $leadTable.'.cms_lead_pipeline_id'],
                CmsLead::triageReady() ? [$leadTable.'.triage_result'] : []));
        if (!empty($filters['executor_id'])) {
            $leadQuery->andWhere([$leadTable.'.executor_id' => (int)$filters['executor_id']]);
        }
        if (!empty($filters['source_type'])) {
            $leadQuery->andWhere([$leadTable.'.source_type' => (string)$filters['source_type']]);
        }

        // Deals that were in this pipeline at some moment (by the history; deals without a pipeline belong to the default one).
        $historyTable = CmsLeadStageHistory::tableName();
        $inPipeline = $pipeline
            ? ($pipeline->isMain ? ['or', ['h.to_pipeline_id' => (int)$pipeline->id], ['h.to_pipeline_id' => null]] : ['h.to_pipeline_id' => (int)$pipeline->id])
            : [];
        $leadIds = (new Query())->select(['h.cms_lead_id'])->distinct()->from($historyTable.' h')
            ->andWhere(['h.cms_site_id' => (int)\Yii::$app->skeeks->site->id])->andWhere($inPipeline)->column();
        if (!$leadIds) {
            return $result;
        }
        $leads = (clone $leadQuery)->andWhere([$leadTable.'.id' => $leadIds])->indexBy('id')->asArray()->all();
        $rows = (new Query())->from($historyTable)->andWhere(['cms_lead_id' => array_keys($leads)])
            ->orderBy(['cms_lead_id' => SORT_ASC, 'id' => SORT_ASC])->all();
        $byLead = [];
        foreach ($rows as $row) {
            $byLead[(int)$row['cms_lead_id']][] = $row;
            if ($row['is_migrated'] && ($result['migratedAt'] === null || (int)$row['created_at'] > $result['migratedAt'])) {
                $result['migratedAt'] = (int)$row['created_at'];
            }
        }

        $reasons = [];
        foreach (CmsLeadRejectReason::forSite() as $reason) {
            $reasons[(int)$reason->id] = $reason;
        }
        $isThisPipeline = static function (array $row) use ($pipeline): bool {
            if (!$pipeline) {
                return true;
            }
            if ($row['to_pipeline_id'] === null) {
                return $pipeline->isMain;
            }

            return (int)$row['to_pipeline_id'] === (int)$pipeline->id;
        };

        $reached = array_fill(0, $successPosition + 1, 0);
        $times = [];
        $now = time();
        foreach ($leads as $leadId => $lead) {
            $history = $byLead[(int)$leadId] ?? [];
            $entered = null;
            $furthest = null;
            $leadTimes = [];
            foreach ($history as $index => $row) {
                if (!$isThisPipeline($row)) {
                    continue;
                }
                if ($entered === null) {
                    // A deal created or migrated here entered at its creation; one moved here — at the move.
                    $entered = in_array($row['kind'], [CmsLeadStageHistory::KIND_CREATE, CmsLeadStageHistory::KIND_MIGRATED], true)
                        ? (int)$lead['created_at'] : (int)$row['created_at'];
                }
                $place = $this->place($row, $position, $successPosition);
                if ($place !== null) {
                    $furthest = max((int)$furthest, $place);
                }
                // Time on a stage: from this real transition to the next one (or now while it stays).
                if (!$row['is_migrated'] && in_array($row['to_status'], [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK], true)) {
                    $next = $history[$index + 1] ?? null;
                    $end = $next ? (int)$next['created_at'] : ($lead['status'] === $row['to_status'] ? $now : null);
                    if ($end !== null) {
                        $key = $row['to_status'] === CmsLead::STATUS_NEW ? 'new' : 'stage-'.(int)$row['to_stage_id'];
                        $leadTimes[$key][] = max(0, $end - (int)$row['created_at']);
                    }
                }
            }
            // The period selects the cohort for every measure, the time on stages included.
            if ($entered === null || $entered < $from || $entered > $to) {
                continue;
            }
            foreach ($leadTimes as $key => $values) {
                $times[$key] = array_merge($times[$key] ?? [], $values);
            }
            $sorted = !empty($lead['triage_result']);
            $notTarget = $lead['status'] === CmsLead::STATUS_REJECTED && (int)$lead['cms_lead_reject_reason_id']
                && isset($reasons[(int)$lead['cms_lead_reject_reason_id']]) && $reasons[(int)$lead['cms_lead_reject_reason_id']]->is_not_target;
            $result['entered']++;
            if ($sorted || $notTarget) {
                continue;
            }
            $isSuccess = $lead['status'] === CmsLead::STATUS_SUCCESS;
            if ($isSuccess) {
                $furthest = $successPosition;
            }
            $furthest = (int)$furthest;
            for ($i = 0; $i <= $furthest; $i++) {
                $reached[$i]++;
            }
            $taken = $furthest >= 1;
            if ($taken) {
                $result['taken']++;
            }
            if ($isSuccess) {
                $result['success']++;
                $result['successAmount'] += (float)$lead['deal_amount'];
            } elseif ($lead['status'] === CmsLead::STATUS_REJECTED) {
                $result['rejected']++;
            } else {
                $result['open']++;
            }

            foreach (['bySource' => (string)$lead['source_type'], 'byExecutor' => (int)$lead['executor_id']] as $group => $key) {
                $result[$group][$key] = $result[$group][$key] ?? ['entered' => 0, 'taken' => 0, 'success' => 0, 'amount' => 0.0];
                $result[$group][$key]['entered']++;
                $result[$group][$key]['taken'] += $taken ? 1 : 0;
                $result[$group][$key]['success'] += $isSuccess ? 1 : 0;
                $result[$group][$key]['amount'] += $isSuccess ? (float)$lead['deal_amount'] : 0.0;
            }
        }
        $result['conversion'] = $result['taken'] ? $result['success'] / $result['taken'] : null;

        // Steps of the funnel: how many reached at least this step, and the move from the previous one.
        $titles = [0 => 'Поступило', 1 => 'Взято в работу'];
        foreach ($stages as $index => $stage) {
            $titles[$index + 2] = $stage->name;
        }
        $titles[$successPosition] = CmsLead::staffStatuses()[CmsLead::STATUS_SUCCESS];
        $previous = null;
        foreach ($titles as $index => $title) {
            $stage = $index > 1 && $index < $successPosition ? $stages[$index - 2] : null;
            $result['steps'][] = [
                'title' => $title,
                'color' => $stage ? $stage->color : null,
                'reached' => $reached[$index],
                'fromPrevious' => $previous ? $reached[$index] / $previous : null,
            ];
            $previous = $reached[$index] ?: null;
        }

        // Time on stages: the average and the median of real stays.
        $timeTitles = ['new' => CmsLead::staffStatuses()[CmsLead::STATUS_NEW]];
        foreach ($stages as $stage) {
            $timeTitles['stage-'.(int)$stage->id] = $stage->name;
        }
        foreach ($timeTitles as $key => $title) {
            $values = $times[$key] ?? [];
            sort($values);
            $count = count($values);
            $result['stageTimes'][] = [
                'title' => $title,
                'count' => $count,
                'average' => $count ? array_sum($values) / $count : null,
                'median' => $count ? ($count % 2 ? $values[intdiv($count, 2)] : ($values[$count / 2 - 1] + $values[$count / 2]) / 2) : null,
            ];
        }

        $result['sales'] = $this->sales($pipeline, $from, $to, $filters);
        $result['bySource'] = $this->groups($result['bySource'], static fn($key) => CmsLead::sources()[$key] ?? (string)$key);
        $result['byExecutor'] = $this->groups($result['byExecutor'], static function ($key) {
            $user = $key ? CmsUser::findOne((int)$key) : null;
            return $user ? (string)$user->shortDisplayName : 'Без ответственного';
        });

        return $result;
    }

    /**
     * Successful deals of the pipeline closed in the period: first or repeat.
     * A sale is repeat when the same company (or the person of a deal without
     * a company) already had a successful deal closed earlier; a deal without
     * a client cannot be told apart and is counted as unknown.
     */
    public function sales(?CmsLeadPipeline $pipeline, int $from, int $to, array $filters = []): array
    {
        $table = CmsLead::tableName();
        $query = CmsLead::find()->forManager()->cmsSite()
            ->select([$table.'.id', $table.'.deal_amount', $table.'.processed_at', $table.'.cms_company_id', $table.'.cms_user_id'])
            ->andWhere([$table.'.status' => CmsLead::STATUS_SUCCESS])
            ->andWhere(['between', $table.'.processed_at', $from, $to]);
        if ($pipeline) {
            $query->andWhere($pipeline->leadCondition($table));
        }
        if (!empty($filters['executor_id'])) {
            $query->andWhere([$table.'.executor_id' => (int)$filters['executor_id']]);
        }
        if (!empty($filters['source_type'])) {
            $query->andWhere([$table.'.source_type' => (string)$filters['source_type']]);
        }
        $sales = ['first' => 0, 'repeat' => 0, 'unknown' => 0, 'firstAmount' => 0.0, 'repeatAmount' => 0.0, 'unknownAmount' => 0.0];
        foreach ($query->asArray()->all() as $lead) {
            $client = $lead['cms_company_id'] ? ['cms_company_id' => (int)$lead['cms_company_id']]
                : ($lead['cms_user_id'] ? ['cms_user_id' => (int)$lead['cms_user_id']] : null);
            $kind = 'unknown';
            if ($client) {
                // Any pipeline: a client who already bought is a repeat sale.
                $earlier = CmsLead::find()->andWhere($client)
                    ->andWhere(['status' => CmsLead::STATUS_SUCCESS])
                    ->andWhere(['<', 'processed_at', (int)$lead['processed_at']])
                    ->andWhere(['!=', 'id', (int)$lead['id']])
                    ->exists();
                $kind = $earlier ? 'repeat' : 'first';
            }
            $sales[$kind]++;
            $sales[$kind.'Amount'] += (float)$lead['deal_amount'];
        }

        return $sales;
    }

    /** Position of the deal after this transition: 0 unsorted, 1..n stages, n+1 success; null — not in the path. */
    private function place(array $row, array $position, int $successPosition): ?int
    {
        switch ($row['to_status']) {
            case CmsLead::STATUS_NEW:
                return 0;
            case CmsLead::STATUS_IN_WORK:
                // "In work" without a stage of this pipeline: taken, no stage reached.
                return $position[(int)$row['to_stage_id']] ?? 1;
            case CmsLead::STATUS_SUCCESS:
                return $successPosition;
        }

        return null;
    }

    private function groups(array $groups, callable $title): array
    {
        $rows = [];
        foreach ($groups as $key => $group) {
            $rows[] = $group + [
                'title' => $title($key),
                'conversion' => $group['taken'] ? $group['success'] / $group['taken'] : null,
            ];
        }
        usort($rows, static fn(array $a, array $b) => [$b['amount'], $b['entered']] <=> [$a['amount'], $a['entered']]);

        return $rows;
    }
}
