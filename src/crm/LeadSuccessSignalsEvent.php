<?php

namespace skeeks\cms\crm;

use skeeks\cms\models\CmsLead;
use yii\base\Event;

/**
 * LeadPipelines::EVENT_SUCCESS_SIGNALS: a package says why an open deal may
 * be closed successfully under the pipeline criterion (`rule`). Setting
 * `handled` replaces the built-in check over the shop tables.
 */
class LeadSuccessSignalsEvent extends Event
{
    /** @var CmsLead */
    public $lead;

    /** @var string CmsLeadPipeline::SUCCESS_ORDER or SUCCESS_PAYMENT */
    public $rule;

    /** @var string[] texts for the employee */
    public $signals = [];
}
