<?php

namespace skeeks\cms\widgets\admin;

use yii\base\Event;

/**
 * Blocks that packages add to a CRM card, see {@see CrmCardBlocksWidget}.
 *
 * Handlers add, replace or remove entries of `$blocks` (id => trusted HTML,
 * usually a BackendSurfaceWidget) and may set `$priority[id]` (lower first,
 * default 100). A handler checks its own permissions and renders nothing
 * when the employee may not see its data.
 */
class CrmCardBlocksEvent extends Event
{
    /** @var string CrmCardBlocksWidget::POSITION_* */
    public string $position = '';

    /** @var \yii\db\ActiveRecord the card record: CmsLead, CmsCompany, CmsDeal */
    public $owner;

    /** @var string[] id => trusted HTML */
    public array $blocks = [];

    /** @var int[] id => sort order, lower first; 100 when not set */
    public array $priority = [];

    /**
     * {@see CrmCardBlocksWidget::EVENT_ACTIONS}: id => `label` (plain text),
     * `url` (a backend window, opened over the card), optional `icon`
     * (BackendIcon name); order by `$priority`.
     *
     * @var array[]
     */
    public array $actions = [];
}
