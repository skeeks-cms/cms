<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStage;
use skeeks\cms\models\CmsLog;
use yii\base\Event;
use yii\db\BaseActiveRecord;

/**
 * «Исходящие продажи» (П2д, platform-plan crm/links-contract.md п. 13): the default stages of
 * the outbound pipeline and the personal offer moving a deal through them.
 *
 * A presentation or a КП a manager sent from a deal moves it to «КП
 * отправлено», the first view of the client — to «Смотрел». Only a deal in
 * work of the outbound pipeline moves, and only forward: a deal already
 * further (or in «Неразобранное», or closed) stays where it is. A sending of
 * a campaign (the flow: a bot through MCP/API, a mailing) never moves a deal:
 * the campaign makes or completes deals itself (cms-communication), and takes
 * its stages «Смотрел» and «Ответил» here — pipeline() and stage(). The
 * pipeline and the two stages of manual offers are the settings of the site
 * CRM (CrmSettings, «Исходящие продажи»); empty — the pipeline by its code
 * and the stages by their names.
 *
 * The core does not depend on cms-proposal: it listens to the feed entries
 * the proposal package writes into the card of the deal (CmsLog of type
 * `proposal`, `data.event` sent/opened, ProposalFeed). The call task on the
 * first view and the reply in the same deal belong to cms-proposal
 * (ProposalTasks) and cms-communication (the letters of a conversation pass
 * their links on).
 */
class OutboundSales
{
    public const STAGE_FIRST = 'Первый контакт';
    public const STAGE_SENT = 'КП отправлено';
    public const STAGE_VIEWED = 'Смотрел';
    public const STAGE_REPLIED = 'Ответил';
    public const STAGE_THINKING = 'Думает';

    /** The chain of a new outbound pipeline; «Успешно» and «Отказ» are the results of every pipeline. */
    public const DEFAULT_STAGES = [self::STAGE_FIRST, self::STAGE_SENT, self::STAGE_VIEWED, self::STAGE_REPLIED, self::STAGE_THINKING];

    public const ROLE_SENT = 'sent';
    public const ROLE_VIEWED = 'viewed';
    /** A reply of a campaign recipient (cms-communication); always by the name «Ответил». */
    public const ROLE_REPLIED = 'replied';

    /** Stage names by role, used when the settings choose none. */
    public const ROLE_STAGES = [self::ROLE_SENT => self::STAGE_SENT, self::ROLE_VIEWED => self::STAGE_VIEWED, self::ROLE_REPLIED => self::STAGE_REPLIED];

    /** CmsLog::$log_type of the proposal feed (cms-proposal CmsProposal::LOG_TYPE). */
    public const PROPOSAL_LOG_TYPE = 'proposal';

    /** Events of the proposal feed that move a deal, event => role. */
    public const PROPOSAL_EVENTS = ['sent' => self::ROLE_SENT, 'opened' => self::ROLE_VIEWED];

    /** Attached once at bootstrap (Skeeks::bootstrap()). */
    public static function subscribe(): void
    {
        Event::on(CmsLog::class, BaseActiveRecord::EVENT_AFTER_INSERT, [static::class, 'onFeed']);
    }

    /** A proposal entry in the feed of a deal; never breaks the sending or the client page. */
    public static function onFeed(Event $event): void
    {
        /** @var CmsLog $log */
        $log = $event->sender;
        if ($log->log_type !== self::PROPOSAL_LOG_TYPE) {
            return;
        }
        $data = is_string($log->data) ? (array)json_decode($log->data, true) : (array)$log->data;
        $role = self::PROPOSAL_EVENTS[$data['event'] ?? ''] ?? null;
        if ($role === null || (string)$log->model_code !== (string)(new CmsLead())->skeeksModelCode) {
            return;
        }
        try {
            $lead = CmsLead::findOne((int)$log->model_id);
            if ($lead && !static::isCampaignSend((int)($data['send'] ?? 0))) {
                static::advance($lead, $role);
            }
        } catch (\Throwable $e) {
            \Yii::error('Этап сделки #'.(int)$log->model_id.' по предложению не изменён: '.$e->getMessage(), self::class);
        }
    }

    /**
     * Moves the deal forward to the stage of the role; returns whether it moved.
     * The history entry gets the signed-in user, on the client page — nobody.
     */
    public static function advance(CmsLead $lead, string $role): bool
    {
        if ($lead->status !== CmsLead::STATUS_IN_WORK || !CmsLead::pipelineReady()) {
            return false;
        }
        $pipeline = static::pipeline((int)$lead->cms_site_id);
        $current = $lead->getEffectivePipeline();
        if (!$pipeline || !$current || (int)$current->id !== (int)$pipeline->id) {
            return false;
        }
        $target = static::stage($pipeline, $role);
        if (!$target || (int)$lead->cms_lead_stage_id === (int)$target->id) {
            return false;
        }
        $order = array_map(static fn(CmsLeadStage $stage) => (int)$stage->id, CmsLeadStage::forPipeline($pipeline, (int)$lead->cms_site_id));
        $from = $lead->cms_lead_stage_id ? array_search((int)$lead->cms_lead_stage_id, $order, true) : -1;
        $to = array_search((int)$target->id, $order, true);
        if ($to === false || ($from !== false && $from >= $to)) {
            return false;
        }

        $lead->cms_lead_stage_id = (int)$target->id;
        if (!$lead->save(false)) {
            throw new \RuntimeException('Не удалось сохранить сделку.');
        }

        return true;
    }

    /**
     * The sending went out with a letter of a campaign (the flow). Read by
     * tables, so the core needs neither cms-proposal nor cms-communication.
     */
    public static function isCampaignSend(int $sendId): bool
    {
        $db = CmsLead::getDb();
        if (!$sendId || !$db->getTableSchema('{{%cms_proposal_send}}') || !$db->getTableSchema('{{%cms_comm_message}}')
            || !$db->getTableSchema('{{%cms_comm_message}}')->getColumn('cms_comm_campaign_id')) {
            return false;
        }

        return (new \yii\db\Query())->from(['s' => '{{%cms_proposal_send}}'])
            ->innerJoin(['m' => '{{%cms_comm_message}}'], 'm.id = s.cms_comm_message_id')
            ->andWhere(['s.id' => $sendId])->andWhere(['not', ['m.cms_comm_campaign_id' => null]])
            ->exists($db);
    }

    /** The outbound pipeline of a site: the setting, otherwise the one with the code `outbound`. */
    public static function pipeline(int $siteId): ?CmsLeadPipeline
    {
        $settingId = (int)self::setting('outboundPipelineId');
        foreach (CmsLeadPipeline::forSite($siteId) as $pipeline) {
            if ($settingId ? (int)$pipeline->id === $settingId : $pipeline->code === CmsLeadPipeline::CODE_OUTBOUND) {
                return $pipeline;
            }
        }

        return null;
    }

    /**
     * The stage of a role (ROLE_*) in the pipeline: the setting of manual
     * offers when it belongs there, otherwise by name. A campaign takes
     * «Смотрел» and «Ответил» of the outbound pipeline this way.
     */
    public static function stage(CmsLeadPipeline $pipeline, string $role): ?CmsLeadStage
    {
        $settingId = (int)self::setting(['sent' => 'proposalSentStageId', 'viewed' => 'proposalViewedStageId'][$role] ?? '');
        $name = self::ROLE_STAGES[$role] ?? '';
        $byName = null;
        foreach (CmsLeadStage::forPipeline($pipeline, (int)$pipeline->cms_site_id) as $stage) {
            if ($settingId && (int)$stage->id === $settingId) {
                return $stage;
            }
            if (!$byName && mb_strtolower(trim((string)$stage->name)) === mb_strtolower($name)) {
                $byName = $stage;
            }
        }

        return $byName;
    }

    /**
     * The default stages of an outbound pipeline: all of them for a pipeline
     * without stages, otherwise only the missing «КП отправлено», «Смотрел»
     * and «Ответил» the offers and campaigns need — «КП отправлено» after the
     * first stage, «Смотрел» right after it, «Ответил» after «Смотрел».
     * Nothing existing is changed.
     *
     * @return string[] names of the created stages
     */
    public static function ensureStages(CmsLeadPipeline $pipeline): array
    {
        $siteId = (int)$pipeline->cms_site_id;
        $stages = CmsLeadStage::find()->andWhere(['cms_lead_pipeline_id' => (int)$pipeline->id])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->all();
        $created = [];
        $add = static function (string $name, int $sort) use ($pipeline, $siteId, &$created): CmsLeadStage {
            $stage = new CmsLeadStage(['cms_site_id' => $siteId, 'cms_lead_pipeline_id' => (int)$pipeline->id, 'name' => $name, 'sort' => $sort]);
            if (!$stage->save()) {
                throw new \RuntimeException('Этап «'.$name.'»: '.implode(' ', $stage->getFirstErrors()));
            }
            $created[] = $name;

            return $stage;
        };
        if (!$stages) {
            foreach (self::DEFAULT_STAGES as $index => $name) {
                $add($name, ($index + 1) * 10);
            }

            return $created;
        }

        $find = static function (string $name) use (&$stages): ?CmsLeadStage {
            foreach ($stages as $stage) {
                if (mb_strtolower(trim((string)$stage->name)) === mb_strtolower($name)) {
                    return $stage;
                }
            }

            return null;
        };
        // A sort between the stage and the next one (equal sorts keep the order of creation).
        $after = static function (CmsLeadStage $stage) use (&$stages): int {
            $sorts = array_map(static fn(CmsLeadStage $s) => (int)$s->sort, $stages);
            $next = null;
            foreach ($sorts as $sort) {
                if ($sort > (int)$stage->sort && ($next === null || $sort < $next)) {
                    $next = $sort;
                }
            }

            return $next === null ? (int)$stage->sort + 10 : intdiv((int)$stage->sort + $next, 2);
        };
        $sent = $find(self::STAGE_SENT);
        if (!$sent) {
            $sent = $add(self::STAGE_SENT, $after($stages[0]));
            $stages[] = $sent;
        }
        $viewed = $find(self::STAGE_VIEWED);
        if (!$viewed) {
            $viewed = $add(self::STAGE_VIEWED, $after($sent));
            $stages[] = $viewed;
        }
        if (!$find(self::STAGE_REPLIED)) {
            $stages[] = $add(self::STAGE_REPLIED, $after($viewed));
        }

        return $created;
    }

    private static function setting(string $name)
    {
        return $name !== '' && \Yii::$app->has('crmSettings') && isset(\Yii::$app->crmSettings->{$name}) ? \Yii::$app->crmSettings->{$name} : null;
    }
}
