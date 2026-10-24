<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\log;

use skeeks\cms\models\CmsLog;

/**
 * A log renderer that gives only the content of a feed entry: the type
 * icon, the title, badges, the main line, details and the body
 * (CmsLogEntry). The feed draws the frame, so the events of all packages
 * look alike. Extend CmsLogEntryRenderer to get render() for free.
 */
interface CmsLogEntryRendererInterface extends CmsLogRendererInterface
{
    public function entry(CmsLog $log): CmsLogEntry;
}
