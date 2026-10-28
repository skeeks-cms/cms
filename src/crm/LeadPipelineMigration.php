<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\CmsLeadStageHistory;
use yii\base\BaseObject;
use yii\db\Expression;
use yii\db\Query;

/**
 * Moving a site to pipelines (П2б): the two default pipelines «Входящие»
 * (default, new requests) and «Исходящие продажи» (cold calls), old stages
 * into «Входящие», cold deals with a copy of their stage into «Исходящие»,
 * every other deal into «Входящие», and one history row "state on the day
 * of the move" (`is_migrated`) per deal without history.
 *
 * Repeatable: pipelines are found by code, only deals and stages without a
 * pipeline are moved, only deals without history get the snapshot. No dates
 * are invented: the snapshot is dated by the run, `stage_changed_at` of old
 * deals stays empty. Deals are written by updateAll(): no notifications, no
 * optimistic lock bump, no partner events.
 *
 *   php yii cms/lead-pipeline/init            # report only
 *   php yii cms/lead-pipeline/init --apply    # apply
 */
class LeadPipelineMigration extends BaseObject
{
    /** @var int */
    public $siteId;

    /** @var string[] a deal whose source name starts with one of them is a cold call */
    public $coldNames = ['База клиентов'];

    /** @var string[] sources of cold deals (imports of recipients) */
    public $coldSources = [CmsLead::SOURCE_MAILING, CmsLead::SOURCE_IMPORT];

    public $inboundName = 'Входящие';
    public $outboundName = 'Исходящие продажи';

    /** What would be done; nothing is changed. */
    public function plan(): array
    {
        $inbound = $this->pipeline(CmsLeadPipeline::CODE_INBOUND);
        $outbound = $this->pipeline(CmsLeadPipeline::CODE_OUTBOUND);
        $lead = CmsLead::tableName();
        $free = (new Query())->from($lead)->andWhere([$lead.'.cms_site_id' => $this->siteId, $lead.'.cms_lead_pipeline_id' => null]);

        $coldStages = (new Query())->select(['s.name', 'cnt' => new Expression('count(*)')])
            ->from(['l' => $lead])
            ->leftJoin(['s' => CmsLeadStage::tableName()], 's.id = l.cms_lead_stage_id')
            ->andWhere(['l.cms_site_id' => $this->siteId, 'l.cms_lead_pipeline_id' => null])
            ->andWhere($this->coldCondition('l'))
            ->groupBy(['s.name'])->all();

        return [
            'inbound' => $inbound ? 'есть: «'.$inbound->name.'» №'.$inbound->id : 'будет создана «'.$this->inboundName.'» (основная)',
            'outbound' => $outbound ? 'есть: «'.$outbound->name.'» №'.$outbound->id : 'будет создана «'.$this->outboundName.'»',
            'stagesToInbound' => (int)CmsLeadStage::find()->andWhere(['cms_site_id' => $this->siteId, 'cms_lead_pipeline_id' => null])->count(),
            'coldLeads' => (int)(clone $free)->andWhere($this->coldCondition($lead))->count(),
            'coldStages' => $coldStages,
            // Not "NOT cold": an empty source name makes that condition NULL.
            'otherLeads' => (int)(clone $free)->count() - (int)(clone $free)->andWhere($this->coldCondition($lead))->count(),
            'snapshots' => (int)$this->withoutHistory()->count(),
        ];
    }

    /** Applies the plan in one transaction; returns what was done. */
    public function apply(): array
    {
        $db = CmsLead::getDb();
        $transaction = $db->beginTransaction();
        try {
            $done = ['created' => [], 'stagesToInbound' => 0, 'stagesCopied' => 0, 'coldLeads' => 0, 'otherLeads' => 0, 'snapshots' => 0];
            $inbound = $this->pipeline(CmsLeadPipeline::CODE_INBOUND);
            if (!$inbound) {
                $hasDefault = CmsLeadPipeline::find()->andWhere(['cms_site_id' => $this->siteId, 'is_default' => 1])->exists();
                $inbound = $this->create(CmsLeadPipeline::CODE_INBOUND, $this->inboundName, 10, !$hasDefault);
                $done['created'][] = $inbound->name;
            }
            $outbound = $this->pipeline(CmsLeadPipeline::CODE_OUTBOUND);
            if (!$outbound) {
                $outbound = $this->create(CmsLeadPipeline::CODE_OUTBOUND, $this->outboundName, 20, false);
                $done['created'][] = $outbound->name;
                // П2д: «Первый контакт», «КП отправлено», «Смотрел», «Думает».
                OutboundSales::ensureStages($outbound);
            }

            $done['stagesToInbound'] = CmsLeadStage::updateAll(['cms_lead_pipeline_id' => (int)$inbound->id],
                ['cms_site_id' => $this->siteId, 'cms_lead_pipeline_id' => null]);

            // Cold deals keep their stage: the same name in «Исходящие».
            $lead = CmsLead::tableName();
            $coldIds = (new Query())->select(['id'])->from($lead)
                ->andWhere([$lead.'.cms_site_id' => $this->siteId, $lead.'.cms_lead_pipeline_id' => null])
                ->andWhere($this->coldCondition($lead))->column();
            $outboundStages = [];
            foreach (CmsLeadStage::find()->andWhere(['cms_lead_pipeline_id' => (int)$outbound->id])->all() as $stage) {
                $outboundStages[$stage->name] = $stage;
            }
            if ($coldIds) {
                foreach (CmsLeadStage::find()->andWhere(['cms_lead_pipeline_id' => (int)$inbound->id])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->all() as $stage) {
                    if (!isset($outboundStages[$stage->name])) {
                        $copy = new CmsLeadStage([
                            'cms_site_id' => $this->siteId,
                            'cms_lead_pipeline_id' => (int)$outbound->id,
                            'name' => $stage->name,
                            'color' => $stage->color,
                            'sort' => $stage->sort,
                        ]);
                        if (!$copy->save()) {
                            throw new \RuntimeException('Этап «'.$stage->name.'»: '.implode(' ', $copy->getFirstErrors()));
                        }
                        $outboundStages[$stage->name] = $copy;
                        $done['stagesCopied']++;
                    }
                    $done['coldLeads'] += CmsLead::updateAll(
                        ['cms_lead_pipeline_id' => (int)$outbound->id, 'cms_lead_stage_id' => (int)$outboundStages[$stage->name]->id],
                        ['id' => $coldIds, 'cms_lead_stage_id' => (int)$stage->id]
                    );
                }
                $done['coldLeads'] += CmsLead::updateAll(['cms_lead_pipeline_id' => (int)$outbound->id],
                    ['id' => $coldIds, 'cms_lead_pipeline_id' => null]);
            }
            $done['otherLeads'] = CmsLead::updateAll(['cms_lead_pipeline_id' => (int)$inbound->id],
                ['cms_site_id' => $this->siteId, 'cms_lead_pipeline_id' => null]);

            $now = time();
            $stageNames = (new Query())->select(['name', 'id'])->from(CmsLeadStage::tableName())->andWhere(['cms_site_id' => $this->siteId])->indexBy('id')->column();
            foreach ($this->withoutHistory()->select(['id', 'status', 'cms_lead_pipeline_id', 'cms_lead_stage_id'])->batch(500) as $rows) {
                $insert = [];
                foreach ($rows as $row) {
                    $stageId = $row['cms_lead_stage_id'] ? (int)$row['cms_lead_stage_id'] : null;
                    $insert[] = [$this->siteId, (int)$row['id'], $now, null, CmsLeadStageHistory::KIND_MIGRATED, null, (string)$row['status'],
                        null, $row['cms_lead_pipeline_id'] ? (int)$row['cms_lead_pipeline_id'] : null, null, $stageId,
                        null, $stageId ? ($stageNames[$stageId] ?? null) : null, 1];
                }
                $done['snapshots'] += $db->createCommand()->batchInsert(CmsLeadStageHistory::tableName(), [
                    'cms_site_id', 'cms_lead_id', 'created_at', 'created_by', 'kind', 'from_status', 'to_status',
                    'from_pipeline_id', 'to_pipeline_id', 'from_stage_id', 'to_stage_id', 'from_stage_name', 'to_stage_name', 'is_migrated',
                ], $insert)->execute();
            }

            $transaction->commit();
            CmsLeadPipeline::clearCache();

            return $done;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /** SQL condition of a cold deal for a deal table alias. */
    public function coldCondition(string $alias): array
    {
        $condition = ['or', [$alias.'.source_type' => $this->coldSources]];
        foreach ($this->coldNames as $name) {
            $condition[] = ['like', $alias.'.source_name', $name.'%', false];
        }

        return $condition;
    }

    private function withoutHistory(): Query
    {
        $lead = CmsLead::tableName();

        return (new Query())->from($lead)
            ->andWhere([$lead.'.cms_site_id' => $this->siteId])
            ->andWhere(['not exists', (new Query())->select(new Expression('1'))->from(CmsLeadStageHistory::tableName().' h')
                ->andWhere('h.cms_lead_id = '.$lead.'.id')]);
    }

    private function pipeline(string $code): ?CmsLeadPipeline
    {
        return CmsLeadPipeline::find()->andWhere(['cms_site_id' => $this->siteId, 'code' => $code])->one();
    }

    private function create(string $code, string $name, int $sort, bool $isDefault): CmsLeadPipeline
    {
        $pipeline = new CmsLeadPipeline([
            'cms_site_id' => $this->siteId,
            'code' => $code,
            'name' => $name,
            'sort' => $sort,
            'is_default' => $isDefault ? 1 : 0,
            'success_rule' => CmsLeadPipeline::SUCCESS_MANUAL,
        ]);
        if (!$pipeline->save()) {
            throw new \RuntimeException('Воронка «'.$name.'»: '.implode(' ', $pipeline->getFirstErrors()));
        }

        return $pipeline;
    }
}
