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

use skeeks\cms\models\behaviors\HasRef;
use skeeks\sx\models\Ref;
use \yii\db\ActiveRecord as YiiActiveRecord;


/**
 * Class ActiveRecord
 *
 * @method Ref getRef();
 * @method bool hasRef();
 *
 * @package skeeks\cms\db
 */
class ActiveRecord
    extends YiiActiveRecord
{
    //У этого объекта есть ссылка
    //use HasRef;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            HasRef::className() => HasRef::className()
        ];
    }

}
