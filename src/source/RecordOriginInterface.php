<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\source;

use yii\db\ActiveRecord;

/**
 * A record source (Skeeks::$recordSources) that can also say which saved
 * records were created from it: the card of a task, lead or deal shows
 * "Создано из письма «…»" at the top (RecordOriginWidget). Optional: a
 * source without it only fills new records.
 */
interface RecordOriginInterface
{
    /**
     * Where the record came from, for the current employee.
     *
     * @return array[] each: `label` (plain text, "Создано из письма") and
     *                 `html` (trusted HTML: encode every value; a link to the
     *                 source only when the employee may open it)
     */
    public function origins(ActiveRecord $record): array;
}
