<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadPipeline;
use skeeks\cms\models\CmsLeadStage;
use yii\base\Component;
use yii\base\Event;
use yii\db\StaleObjectException;

/**
 * Pipelines of deals (П2б): moving a deal to another pipeline, the success
 * suggestion of a pipeline and the time on the current stage. Controllers,
 * the future API (П2в, `cms_lead_*`) and packages call it instead of
 * writing `cms_lead_pipeline_id` themselves; the history of every move is
 * written by CmsLead::afterSave().
 *
 * Rights stay in CrmAccess (П2а+ will widen them there, not here).
 */
class LeadPipelines extends Component
{
    /**
     * Collects reasons to suggest closing a deal successfully: a package
     * (shop, billing) adds `$event->signals[] = 'текст'` for the deal
     * `$event->lead` when the pipeline criterion `$event->rule` is met.
     */
    public const EVENT_SUCCESS_SIGNALS = 'successSignals';

    public static function instance(): self
    {
        return \Yii::$app->has('leadPipelines') ? \Yii::$app->get('leadPipelines') : new static();
    }

    /**
     * Moves an open deal to another pipeline, to the given stage or the first
     * one. An unsorted deal stays unsorted. A closed deal is reopened first.
     *
     * @throws \DomainException with a message for the employee
     */
    public function move(CmsLead $lead, int $pipelineId, ?int $stageId, int $actorId): CmsLead
    {
        if (!CmsLead::pipelineReady()) {
            throw new \DomainException('Воронки ещё не включены на сайте.');
        }
        if ($lead->isTerminal || !CrmAccess::instance()->canWorkLead($actorId, $lead) && !($lead->status === CmsLead::STATUS_NEW && CrmAccess::instance()->canTriage($actorId, $lead))) {
            throw new \DomainException('Перенести можно только открытую сделку, закреплённую за вами.');
        }
        $pipeline = null;
        foreach (CmsLeadPipeline::forSite((int)$lead->cms_site_id) as $item) {
            if ((int)$item->id === $pipelineId) {
                $pipeline = $item;
            }
        }
        if (!$pipeline) {
            throw new \DomainException('Воронка не найдена. Обновите страницу.');
        }
        $stage = null;
        if ($stageId) {
            foreach ($pipeline->stages as $item) {
                if ((int)$item->id === $stageId) {
                    $stage = $item;
                }
            }
            if (!$stage) {
                throw new \DomainException('Этап не найден в воронке «'.$pipeline->name.'».');
            }
        }

        $lead->cms_lead_pipeline_id = (int)$pipeline->id;
        if ($lead->status === CmsLead::STATUS_IN_WORK) {
            $lead->cms_lead_stage_id = $stage ? (int)$stage->id : null;
        }
        try {
            if (!$lead->save()) {
                throw new \DomainException(implode(' ', $lead->getFirstErrors()));
            }
        } catch (StaleObjectException $e) {
            throw new \DomainException('Сделка уже изменена в другой вкладке. Обновите страницу.', 0, $e);
        }

        return $lead;
    }

    /**
     * Why the pipeline suggests closing this open deal successfully; empty when
     * it does not. Only a suggestion: a person closes the deal.
     *
     * @return string[]
     */
    public function successSignals(CmsLead $lead): array
    {
        if ($lead->status !== CmsLead::STATUS_IN_WORK) {
            return [];
        }
        $pipeline = $lead->getEffectivePipeline();
        $rule = $pipeline ? (string)$pipeline->success_rule : CmsLeadPipeline::SUCCESS_MANUAL;
        if ($rule === CmsLeadPipeline::SUCCESS_MANUAL) {
            return [];
        }

        $event = new LeadSuccessSignalsEvent(['lead' => $lead, 'rule' => $rule]);
        $this->trigger(self::EVENT_SUCCESS_SIGNALS, $event);
        Event::trigger(self::class, self::EVENT_SUCCESS_SIGNALS, $event);
        if (!$event->handled) {
            $event->signals = array_merge($event->signals, $this->storeSignals($lead, $rule));
        }

        return array_values(array_unique($event->signals));
    }

    /**
     * The built-in check over the shop tables when they exist (the core does
     * not depend on the shop package): a confirmed order of the deal (the link
     * `shop_order.cms_lead_id` comes with П3) or the first incoming payment of
     * the deal's client made after the deal was opened.
     */
    protected function storeSignals(CmsLead $lead, string $rule): array
    {
        $db = CmsLead::getDb();
        if ($rule === CmsLeadPipeline::SUCCESS_ORDER) {
            $order = $db->getTableSchema('{{%shop_order}}', true);
            if (!$order || !$order->getColumn('cms_lead_id')) {
                return [];
            }
            $query = (new \yii\db\Query())->from('{{%shop_order}}')->andWhere(['cms_lead_id' => (int)$lead->id]);
            if ($order->getColumn('is_created')) {
                $query->andWhere(['is_created' => 1]);
            }

            return $query->exists($db) ? ['По сделке оформлен заказ.'] : [];
        }

        if ($rule === CmsLeadPipeline::SUCCESS_PAYMENT && ($lead->cms_company_id || $lead->cms_user_id)) {
            $payment = $db->getTableSchema('{{%shop_payment}}', true);
            if (!$payment) {
                return [];
            }
            $client = $lead->cms_company_id ? ['cms_company_id' => (int)$lead->cms_company_id] : ['cms_user_id' => (int)$lead->cms_user_id];
            $base = (new \yii\db\Query())->from('{{%shop_payment}}')->andWhere($client)->andWhere(['is_debit' => 1]);
            $before = (clone $base)->andWhere(['<', 'created_at', (int)$lead->created_at])->exists($db);
            $after = (clone $base)->andWhere(['>=', 'created_at', (int)$lead->created_at])->orderBy(['created_at' => SORT_ASC])->one($db);
            if (!$before && $after) {
                return ['Клиент оплатил '.\Yii::$app->formatter->asDate((int)$after['created_at'], 'd MMMM').' — это первая оплата.'];
            }
        }

        return [];
    }

    /** «3 дня», «сегодня»: how long the open deal is on its current stage. */
    public function timeOnStageText(CmsLead $lead): string
    {
        if ($lead->isTerminal || !$lead->hasAttribute('stage_changed_at') || !$lead->stage_changed_at) {
            return '';
        }
        $days = (int)floor((time() - (int)$lead->stage_changed_at) / 86400);
        if ($days < 1) {
            return 'сегодня';
        }

        return $days.' '.self::plural($days, 'день', 'дня', 'дней');
    }

    /** Stage choices of a site, grouped by pipeline when there are several. */
    public function stageItems(?int $siteId = null): array
    {
        $pipelines = CmsLeadPipeline::forSite($siteId);
        if (count($pipelines) < 2) {
            $items = [];
            foreach (CmsLeadStage::forPipeline($pipelines[0] ?? null, $siteId) as $stage) {
                $items[(int)$stage->id] = (string)$stage->name;
            }

            return $items;
        }
        $items = [];
        foreach ($pipelines as $pipeline) {
            foreach ($pipeline->stages as $stage) {
                $items[$pipeline->name][(int)$stage->id] = (string)$stage->name;
            }
        }

        return $items;
    }

    public static function plural(int $number, string $one, string $few, string $many): string
    {
        $mod100 = $number % 100;
        $mod10 = $number % 10;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return $many;
        }
        if ($mod10 === 1) {
            return $one;
        }

        return $mod10 >= 2 && $mod10 <= 4 ? $few : $many;
    }
}
