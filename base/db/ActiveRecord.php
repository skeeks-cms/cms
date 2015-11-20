<?php
/**
 * Все модели необходимо наследовать от этой базовой cms ActiveRecord
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\base\db;

use skeeks\cms\query\CmsActiveQuery;
use \yii\db\ActiveRecord as YiiActiveRecord;


/**
 * Class ActiveRecord
 *
 * @package skeeks\cms\db
 */
class ActiveRecord
    extends YiiActiveRecord
{
    /**
     * @return CmsActiveQuery
     */
    public static function find()
    {
        return new CmsActiveQuery(get_called_class());
    }
}
