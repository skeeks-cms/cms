<?php

namespace skeeks\cms\services;

use skeeks\cms\events\LeadReportPartnerRewardsEvent;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadRejectReason;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\CmsUser;
use yii\base\Component;

/**
 * Lead report: what happened to the leads created in a period.
 *
 * Leads are counted by creation date (a cohort) and shown in their current
 * state. "Target" leads exclude rejections with a non-target reason (spam,
 * duplicates); conversion is successful / target. Partner rewards come from
 * extensions through EVENT_PARTNER_REWARDS, so this core package does not
 * depend on the partner program package.
 */
class CmsLeadReportService extends Component
{
    public const EVENT_PARTNER_REWARDS = 'partnerRewards';

    /** @var CmsLeadPipeline|null the pipeline of the current build */
    private $pipeline;

    /**
     * @param int $from unix time, inclusive
     * @param int $to unix time, inclusive
     * @param string|null $sourceType CmsLead::SOURCE_* or null for all
     * @param CmsLeadPipeline|null $pipeline only the deals of this pipeline (П2б)
     */
    public function build(int $from, int $to, ?string $sourceType = null, ?CmsLeadPipeline $pipeline = null): array
    {
        $this->pipeline = $pipeline;
        $table = CmsLead::tableName();
        $query = CmsLead::find()->forManager()->cmsSite()
            ->select([
                $table.'.id', $table.'.status', $table.'.source_type', $table.'.partner_id',
                $table.'.cms_lead_stage_id', $table.'.cms_lead_reject_reason_id', $table.'.deal_amount',
                $table.'.utm_source', $table.'.utm_campaign',
            ])
            ->andWhere(['between', $table.'.created_at', $from, $to]);
        if (CmsLead::triageReady()) {
            // Spam, requests added to another deal and support are not sales.
            $query->andWhere([$table.'.triage_result' => null]);
        }
        if ($sourceType) {
            $query->andWhere([$table.'.source_type' => $sourceType]);
        }
        if ($pipeline) {
            $query->andWhere($pipeline->leadCondition($table));
        }
        $leads = $query->asArray()->all();

        $reasons = [];
        foreach (CmsLeadRejectReason::forSite() as $reason) {
            $reasons[(int)$reason->id] = $reason;
        }

        $successPartnerIds = [];
        foreach ($leads as $lead) {
            if ($lead['status'] === CmsLead::STATUS_SUCCESS && $lead['partner_id']) {
                $successPartnerIds[] = (int)$lead['id'];
            }
        }
        $event = new LeadReportPartnerRewardsEvent(['leadIds' => $successPartnerIds]);
        if ($successPartnerIds) {
            $this->trigger(self::EVENT_PARTNER_REWARDS, $event);
        }
        $rewards = $event->rewards;

        $isNotTarget = static function (array $lead) use ($reasons): bool {
            $reasonId = (int)$lead['cms_lead_reject_reason_id'];
            return $lead['status'] === CmsLead::STATUS_REJECTED && $reasonId && isset($reasons[$reasonId])
                && $reasons[$reasonId]->is_not_target;
        };

        $sources = CmsLead::sources();
        $bySource = $byPartner = $byCampaign = [];
        $total = $this->emptyGroup('Всего');
        $funnel = $rejectReasons = [];
        foreach ($leads as $lead) {
            $notTarget = $isNotTarget($lead);
            $reward = $rewards[(int)$lead['id']] ?? null;

            $this->add($total, $lead, $notTarget, $reward);

            $sourceKey = (string)$lead['source_type'];
            $bySource[$sourceKey] = $bySource[$sourceKey] ?? $this->emptyGroup($sources[$sourceKey] ?? $sourceKey);
            $this->add($bySource[$sourceKey], $lead, $notTarget, $reward);

            if ($lead['partner_id']) {
                $partnerKey = (int)$lead['partner_id'];
                $byPartner[$partnerKey] = $byPartner[$partnerKey] ?? $this->emptyGroup('');
                $this->add($byPartner[$partnerKey], $lead, $notTarget, $reward);
            }

            if ($lead['utm_source'] || $lead['utm_campaign']) {
                $campaignKey = trim($lead['utm_source'].' / '.$lead['utm_campaign'], ' /');
                $byCampaign[$campaignKey] = $byCampaign[$campaignKey] ?? $this->emptyGroup($campaignKey);
                $this->add($byCampaign[$campaignKey], $lead, $notTarget, $reward);
            }

            if ($lead['status'] === CmsLead::STATUS_REJECTED) {
                $reasonId = (int)$lead['cms_lead_reject_reason_id'];
                $reasonKey = isset($reasons[$reasonId]) ? $reasonId : 0;
                $rejectReasons[$reasonKey] = ($rejectReasons[$reasonKey] ?? 0) + 1;
            } elseif (!$notTarget) {
                $stageKey = $lead['status'] === CmsLead::STATUS_IN_WORK ? 'stage-'.(int)$lead['cms_lead_stage_id'] : $lead['status'];
                $funnel[$stageKey] = ($funnel[$stageKey] ?? 0) + 1;
            }
        }

        foreach ($byPartner as $partnerId => &$group) {
            $user = CmsUser::findOne($partnerId);
            $group['title'] = $user ? trim((string)$user->shortDisplayName) : 'Партнёр №'.$partnerId;
        }
        unset($group);

        return [
            'from' => $from,
            'to' => $to,
            'total' => $this->finish($total),
            'bySource' => $this->sortGroups($bySource),
            'byPartner' => $this->sortGroups($byPartner),
            'byCampaign' => $this->sortGroups($byCampaign),
            'funnel' => $this->funnelRows($funnel),
            'rejectReasons' => $this->reasonRows($rejectReasons, $reasons),
            'hasRewards' => (bool)$rewards,
        ];
    }

    private function emptyGroup(string $title): array
    {
        return [
            'title' => $title, 'leads' => 0, 'notTarget' => 0, 'success' => 0, 'inWork' => 0, 'new' => 0,
            'rejected' => 0, 'dealSum' => 0.0, 'dealCount' => 0, 'rewardSum' => 0.0, 'rewardDealSum' => 0.0,
            'rewardShareSum' => 0.0,
        ];
    }

    private function add(array &$group, array $lead, bool $notTarget, ?float $reward): void
    {
        $group['leads']++;
        if ($notTarget) {
            $group['notTarget']++;
            return;
        }
        switch ($lead['status']) {
            case CmsLead::STATUS_SUCCESS:
                $group['success']++;
                if ($lead['deal_amount'] !== null) {
                    $group['dealSum'] += (float)$lead['deal_amount'];
                    $group['dealCount']++;
                    if ($reward !== null) {
                        // The share uses only leads where both numbers are known.
                        $group['rewardDealSum'] += (float)$lead['deal_amount'];
                        $group['rewardShareSum'] += $reward;
                    }
                }
                if ($reward !== null) {
                    $group['rewardSum'] += $reward;
                }
                break;
            case CmsLead::STATUS_IN_WORK:
                $group['inWork']++;
                break;
            case CmsLead::STATUS_NEW:
                $group['new']++;
                break;
            case CmsLead::STATUS_REJECTED:
                $group['rejected']++;
                break;
        }
    }

    private function finish(array $group): array
    {
        $target = $group['leads'] - $group['notTarget'];
        $group['target'] = $target;
        $group['conversion'] = $target > 0 ? $group['success'] / $target : null;
        $group['avgCheck'] = $group['dealCount'] > 0 ? $group['dealSum'] / $group['dealCount'] : null;
        // Share of the deal amount paid to partners, only over deals that have both numbers.
        $group['rewardShare'] = $group['rewardDealSum'] > 0 ? $group['rewardShareSum'] / $group['rewardDealSum'] : null;

        return $group;
    }

    private function sortGroups(array $groups): array
    {
        $groups = array_map([$this, 'finish'], array_values($groups));
        usort($groups, static fn(array $a, array $b) => [$b['dealSum'], $b['leads']] <=> [$a['dealSum'], $a['leads']]);

        return $groups;
    }

    /** Current state of the target leads, in board order. */
    private function funnelRows(array $counts): array
    {
        $rows = [['title' => CmsLead::staffStatuses()[CmsLead::STATUS_NEW], 'count' => $counts[CmsLead::STATUS_NEW] ?? 0, 'color' => null]];
        $stages = CmsLeadStage::forPipeline($this->pipeline);
        if (!$stages || !empty($counts['stage-0'])) {
            $rows[] = ['title' => $stages ? 'В работе: без этапа' : 'В работе', 'count' => $counts['stage-0'] ?? 0, 'color' => null];
        }
        foreach ($stages as $stage) {
            $rows[] = ['title' => $stage->name, 'count' => $counts['stage-'.(int)$stage->id] ?? 0, 'color' => $stage->color];
        }
        $rows[] = ['title' => CmsLead::staffStatuses()[CmsLead::STATUS_SUCCESS], 'count' => $counts[CmsLead::STATUS_SUCCESS] ?? 0, 'color' => null];

        return $rows;
    }

    /** Rejections by reason, most frequent first; non-target reasons are marked. */
    private function reasonRows(array $counts, array $reasons): array
    {
        $rows = [];
        foreach ($counts as $reasonId => $count) {
            $reason = $reasons[$reasonId] ?? null;
            $rows[] = [
                'title' => $reason ? $reason->name : 'Причина не выбрана',
                'count' => $count,
                'notTarget' => $reason ? (bool)$reason->is_not_target : false,
            ];
        }
        usort($rows, static fn(array $a, array $b) => $b['count'] <=> $a['count']);

        return $rows;
    }
}
