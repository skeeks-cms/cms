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
namespace skeeks\cms\models\behaviors;

use \skeeks\cms\models\Vote;
use yii\db\BaseActiveRecord;
use \yii\base\Behavior;

/**
 * Class HasVotes
 * @package skeeks\cms\models\behaviors
 */
class HasVotes extends Behavior
{
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE      => "deleteVotes",
        ];
    }

    /**
     *
     * Находим все комментарии и удаляем поочереди, потому как при удалении коммента может что то еще срабатыать.
     * Дабы сгенерировать событие
     *
     * @throws \Exception
     */
    public function deleteVotes()
    {
        if ($models = Vote::find($this->owner->getRef()->toArray())->all())
        {
            foreach ($models as $model)
            {
                $model->delete();
            }
        }
    }
}