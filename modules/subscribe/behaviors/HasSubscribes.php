<?php
/**
 * Поведение которое перед удалением сущьности, собирается удалить комментарии связанные с ней.
 * TODO: добавить опции наподобии как в БД
 * ON_DELETE ON_UPDATE
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\subscribe\behaviors;

use skeeks\cms\modules\subscribe\models\Subscribe;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasComments
 * @package common\models\behaviors
 */
class HasSubscribes extends Behavior
{
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE      => "deleteSubscribes",
        ];
    }

    /**
     *
     * Находим все комментарии и удаляем поочереди, потому как при удалении коммента может что то еще срабатыать.
     * Дабы сгенерировать событие
     *
     * @throws \Exception
     */
    public function deleteSubscribes()
    {
        if ($comments = Subscribe::find($this->owner->getRef()->toArray())->all())
        {
            foreach ($comments as $comment)
            {
                $comment->delete();
            }
        }
    }
}