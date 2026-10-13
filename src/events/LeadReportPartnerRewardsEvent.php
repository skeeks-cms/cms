<?php
namespace skeeks\cms\events;

/**
 * The lead report asks extensions for partner rewards of successful leads.
 * The partner program (skeeks/cms-shop) fills $rewards as lead ID => amount;
 * without it the report simply has no reward figures.
 */
class LeadReportPartnerRewardsEvent extends \yii\base\Event
{
    /** @var int[] */
    public $leadIds = [];
    /** @var array<int, float> */
    public $rewards = [];
}
