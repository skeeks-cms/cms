<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

/**
 * An entry of `notifications.channels` that gives several channels at once,
 * e.g. one per installed messenger driver, so the list is not written by hand.
 */
interface NotifyChannelProviderInterface
{
    /** @return NotifyChannel[] code => channel */
    public function channels(): array;
}
