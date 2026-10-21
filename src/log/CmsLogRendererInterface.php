<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\log;

use skeeks\cms\models\CmsLog;

/**
 * Renders the body of one activity feed entry of a log type owned by another
 * package (for example a message of skeeks/cms-communication).
 *
 * Register it in the `skeeks` component:
 *
 * ```php
 * 'skeeks' => [
 *     'logTypes' => ['message' => 'Сообщение'],
 *     'logRenderers' => ['message' => MessageLogRenderer::class],
 *     'activityLogTypes' => ['message'],
 * ],
 * ```
 *
 * The result is trusted HTML: encode every dynamic value.
 */
interface CmsLogRendererInterface
{
    public function render(CmsLog $log): string;
}
