<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\log;

use skeeks\cms\models\CmsLog;

/**
 * Base of package log renderers: implement entry(); render() gives the same
 * content as an HTML fragment for places without the feed frame.
 */
abstract class CmsLogEntryRenderer implements CmsLogEntryRendererInterface
{
    abstract public function entry(CmsLog $log): CmsLogEntry;

    public function render(CmsLog $log): string
    {
        return $this->entry($log)->renderContent();
    }
}
