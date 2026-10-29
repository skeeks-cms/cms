<?php

namespace skeeks\cms\services;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\queries\CmsLeadQuery;
use yii\db\StaleObjectException;

/**
 * Lead kanban of one pipeline: columns and the rules for moving a lead
 * between them.
 *
 * Columns are «Неразобранное» (always in the default pipeline, in another
 * one only while it has deals), one column per stage of the pipeline (plus a
 * "without stage" column while it has deals), then the two terminal
 * statuses. A site without pipelines has one implicit pipeline: every stage
 * of the site. The system status stays authoritative: a move into "in work"
 * claims or reopens the lead through its normal validation, while a move
 * into a terminal column only asks the caller to open the processing form,
 * because a result or a rejection reason is required.
 */
class CmsLeadBoardService
{
    public const COLUMN_NEW = 'new';
    public const COLUMN_SUCCESS = 'success';
    public const COLUMN_REJECTED = 'rejected';
    public const STAGE_PREFIX = 'stage-';

    /** Leads per active column; the board is a work tool, not an archive. */
    public const ACTIVE_LIMIT = 200;
    /** Terminal columns show only recently processed leads. */
    public const TERMINAL_LIMIT = 30;
    public const TERMINAL_DAYS = 30;

    /** Quick filters of the board (amoCRM/Bitrix24 presets): source and "no task". */
    public const FILTER_NO_TASK = 'no_task';

    /** @var CmsLeadPipeline|null the pipeline of the board; null on a site without pipelines */
    public $pipeline;

    /** @var array source_type, no_task (bool) */
    public $filters = [];

    public function __construct(?CmsLeadPipeline $pipeline = null, array $filters = [])
    {
        $this->pipeline = $pipeline;
        $this->filters = $filters;
    }

    /** The board of a lead's own pipeline (card stage bar, kanban move). */
    public static function forLead(CmsLead $lead): self
    {
        return new static($lead->getEffectivePipeline());
    }

    public static function stageColumn(?int $stageId): string
    {
        return self::STAGE_PREFIX.(int)$stageId;
    }

    /** @return CmsLeadStage[] */
    public function stages(): array
    {
        return CmsLeadStage::forPipeline($this->pipeline);
    }

    /**
     * Optional filters: the responsible employee and a free-text search
     * (name, description, phone or email, see `CmsLeadQuery::search()`).
     *
     * @return array[] each: key, title, status, stage (CmsLeadStage|null), leads (CmsLead[]), total, amount (open deals' «Сумма»)
     */
    public function columns(?int $executorId = null, string $search = ''): array
    {
        $stages = $this->stages();
        $table = CmsLead::tableName();
        $columns = [];
        $new = $this->column(self::COLUMN_NEW, CmsLead::staffStatuses()[CmsLead::STATUS_NEW], CmsLead::STATUS_NEW, null,
            $this->query($executorId, $search)->andWhere([$table.'.status' => CmsLead::STATUS_NEW])
                ->orderBy([$table.'.created_at' => SORT_DESC]),
            self::ACTIVE_LIMIT);
        // As in amoCRM: new requests land in the default pipeline; another one shows the column only while it has deals.
        if (!$this->pipeline || $this->pipeline->isMain || $new['total'] > 0) {
            $columns[] = $new;
        }

        $inWork = function (?int $stageId) use ($executorId, $search, $table): CmsLeadQuery {
            return $this->query($executorId, $search)
                ->andWhere([$table.'.status' => CmsLead::STATUS_IN_WORK])
                ->andWhere([$table.'.cms_lead_stage_id' => $stageId])
                ->orderBy([$table.'.updated_at' => SORT_DESC]);
        };
        $withoutStage = $this->column(self::stageColumn(null), $stages ? 'В работе: без этапа' : 'В работе',
            CmsLead::STATUS_IN_WORK, null, $inWork(null), self::ACTIVE_LIMIT);
        // With stages, this column only collects leads taken into work before
        // the stages existed; it disappears once they are sorted into stages.
        if (!$stages || $withoutStage['total'] > 0) {
            $columns[] = $withoutStage;
        }
        foreach ($stages as $stage) {
            $columns[] = $this->column(self::stageColumn((int)$stage->id), $stage->name,
                CmsLead::STATUS_IN_WORK, $stage, $inWork((int)$stage->id), self::ACTIVE_LIMIT);
        }

        $since = time() - self::TERMINAL_DAYS * 86400;
        foreach ([self::COLUMN_SUCCESS => CmsLead::STATUS_SUCCESS, self::COLUMN_REJECTED => CmsLead::STATUS_REJECTED] as $key => $status) {
            $columns[] = $this->column($key, CmsLead::staffStatuses()[$status], $status, null,
                $this->query($executorId, $search)
                    ->andWhere([$table.'.status' => $status])
                    ->andWhere(['>=', $table.'.processed_at', $since])
                    // Sorted deals (spam, added to another deal, support) are not lost sales.
                    ->andWhere(CmsLead::triageReady() ? [$table.'.triage_result' => null] : [])
                    ->orderBy([$table.'.processed_at' => SORT_DESC]),
                self::TERMINAL_LIMIT);
        }

        return $columns;
    }

    /**
     * Applies a move. Returns false when the target is terminal: the lead is
     * unchanged and the caller must open the processing form instead.
     *
     * @throws \RuntimeException with a user-facing message
     */
    public function move(CmsLead $lead, string $column, int $actorId): bool
    {
        $denied = $this->denyReason($lead, $column, $actorId);
        if ($denied !== null) {
            throw new \RuntimeException($denied);
        }
        if ($column === self::COLUMN_SUCCESS || $column === self::COLUMN_REJECTED) {
            return false;
        }

        $stageId = (int)substr($column, strlen(self::STAGE_PREFIX)) ?: null;
        if ($stageId) {
            $stage = CmsLeadStage::find()->cmsSite((int)$lead->cms_site_id)->andWhere(['id' => $stageId])->one();
            if (!$stage || (CmsLead::pipelineReady() && !$lead->stageBelongsToPipeline($stage))) {
                throw new \RuntimeException('Этап не найден в воронке сделки. Обновите страницу.');
            }
        }

        if ($lead->status === CmsLead::STATUS_NEW) {
            $lead->executor_id = $lead->executor_id ?: $actorId;
            $lead->status = CmsLead::STATUS_IN_WORK;
        } elseif ($lead->isTerminal) {
            $lead->status = CmsLead::STATUS_IN_WORK;
            $lead->processed_at = null;
        }

        $lead->cms_lead_stage_id = $stageId;
        try {
            if (!$lead->save()) {
                throw new \RuntimeException(implode(' ', $lead->getFirstErrors()));
            }
        } catch (StaleObjectException $e) {
            throw new \RuntimeException('Сделка уже изменена в другой вкладке. Обновите страницу.', 0, $e);
        }
        // A deal taken from «Неразобранное»: its responsible works with the client.
        \skeeks\cms\crm\CrmAccess::instance()->grantLeadExecutorLevel($lead, $actorId);

        return true;
    }

    /**
     * Why the actor may not move the lead to the column, or null when allowed.
     * Shared by the kanban drop and the stage bar of the lead card.
     */
    public function denyReason(CmsLead $lead, string $column, int $actorId): ?string
    {
        if ($column === self::COLUMN_NEW) {
            return 'Вернуть сделку в «Неразобранное» нельзя.';
        }
        if ($column === self::COLUMN_SUCCESS || $column === self::COLUMN_REJECTED) {
            return $lead->status === CmsLead::STATUS_IN_WORK && $lead->canBeWorkedBy($actorId)
                ? null
                : 'Завершить можно только сделку в работе. '.\skeeks\cms\crm\CrmAccess::LEAD_WORK_DENIED;
        }
        if (strpos($column, self::STAGE_PREFIX) !== 0) {
            return 'Неизвестная колонка.';
        }
        if ($lead->status === CmsLead::STATUS_NEW) {
            return $lead->canBeClaimed || $lead->canBeWorkedBy($actorId) ? null : 'Сделка уже закреплена за другим менеджером.';
        }
        if ($lead->isTerminal) {
            return $lead->canBeReopenedBy($actorId) ? null : \skeeks\cms\crm\CrmAccess::LEAD_WORK_DENIED;
        }

        return $lead->canBeWorkedBy($actorId) ? null : \skeeks\cms\crm\CrmAccess::LEAD_WORK_DENIED;
    }

    /**
     * Steps of the stage bar in the lead card: the path "new → stages" of the
     * lead's pipeline and the two outcomes. Each step: column, title, stage,
     * state (done|current|future), allowed, confirm (text or null).
     */
    public function steps(CmsLead $lead, int $actorId): array
    {
        $stages = $this->stages();
        $path = [['column' => self::COLUMN_NEW, 'title' => CmsLead::staffStatuses()[CmsLead::STATUS_NEW], 'stage' => null]];
        if (!$stages || ($lead->status === CmsLead::STATUS_IN_WORK && !$lead->cms_lead_stage_id)) {
            $path[] = ['column' => self::stageColumn(null), 'title' => CmsLead::staffStatuses()[CmsLead::STATUS_IN_WORK], 'stage' => null];
        }
        foreach ($stages as $stage) {
            $path[] = ['column' => self::stageColumn((int)$stage->id), 'title' => $stage->name, 'stage' => $stage];
        }

        $current = $lead->status === CmsLead::STATUS_NEW ? self::COLUMN_NEW : null;
        if ($lead->status === CmsLead::STATUS_IN_WORK) {
            $current = self::stageColumn($lead->cms_lead_stage_id ? (int)$lead->cms_lead_stage_id : null);
        }
        $currentIndex = null;
        foreach ($path as $index => $step) {
            if ($step['column'] === $current) {
                $currentIndex = $index;
            }
        }

        $result = [];
        foreach ($path as $index => $step) {
            $state = 'future';
            if ($lead->isTerminal || ($currentIndex !== null && $index < $currentIndex)) {
                $state = 'done';
            } elseif ($index === $currentIndex) {
                $state = 'current';
            }
            $result[] = $step + [
                'state' => $state,
                'allowed' => $state !== 'current' && $this->denyReason($lead, $step['column'], $actorId) === null,
                'confirm' => $lead->isTerminal ? 'Вернуть сделку в работу?' : null,
            ];
        }
        foreach ([self::COLUMN_SUCCESS => CmsLead::STATUS_SUCCESS, self::COLUMN_REJECTED => CmsLead::STATUS_REJECTED] as $column => $status) {
            $isCurrent = $lead->status === $status;
            $result[] = [
                'column' => $column,
                'title' => CmsLead::staffStatuses()[$status],
                'stage' => null,
                'state' => $isCurrent ? 'current' : 'future',
                'allowed' => !$isCurrent && $this->denyReason($lead, $column, $actorId) === null,
                'confirm' => null,
            ];
        }

        return $result;
    }

    private function query(?int $executorId, string $search = ''): CmsLeadQuery
    {
        $table = CmsLead::tableName();
        $query = CmsLead::find()->forManager()->cmsSite()->with(['activeTasks', 'stage', 'executor', 'rejectReasonItem', 'company', 'client']);
        if ($this->pipeline) {
            $query->andWhere($this->pipeline->leadCondition($table));
        }
        if ($executorId) {
            $query->forExecutor($executorId);
        }
        if (trim($search) !== '') {
            $query->search($search);
        }
        $source = (string)($this->filters['source_type'] ?? '');
        if ($source !== '' && array_key_exists($source, CmsLead::sources())) {
            $query->andWhere([$table.'.source_type' => $source]);
        }
        if (!empty($this->filters[self::FILTER_NO_TASK])) {
            $query->nextStep(CmsLeadQuery::NEXT_STEP_MISSING);
        }

        return $query;
    }

    private function column(string $key, string $title, string $status, ?CmsLeadStage $stage, CmsLeadQuery $query, int $limit): array
    {
        $amount = null;
        // «Сумма» of open deals, «Сумма сделки» of successful ones; a rejected column shows no money.
        // Over a subquery: a search groups the deals, and each deal is summed once.
        $sumColumn = null;
        if (CmsLead::amountReady() && in_array($status, [CmsLead::STATUS_NEW, CmsLead::STATUS_IN_WORK], true)) {
            $sumColumn = 'amount';
        } elseif ($status === CmsLead::STATUS_SUCCESS) {
            $sumColumn = 'deal_amount';
        }
        if ($sumColumn) {
            $table = CmsLead::tableName();
            $deals = (clone $query)->with([])->select([$table.'.id', $table.'.'.$sumColumn])->orderBy([]);
            $amount = (float)(new \yii\db\Query())->from(['d' => $deals])->sum('d.'.$sumColumn);
        }

        return [
            'key' => $key,
            'title' => $title,
            'status' => $status,
            'stage' => $stage,
            'total' => (int)(clone $query)->orderBy([])->count(),
            'amount' => $amount,
            'leads' => $query->limit($limit)->all(),
        ];
    }
}
