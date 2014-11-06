<?php
/**
 * Поведение сообщающее, что у текущей модели $this->owner, к которой прикручено это поведение, есть сущности, которые могут цеплятся по универсальной ссылке \skeeks\sx\models\Ref
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\Exception;
use skeeks\cms\models\searchs\User;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * Class HasLinkedModels
 * @package skeeks\cms\models\behaviors
 */
class HasLinkedModels extends \skeeks\cms\base\behaviors\ActiveRecord
{
    const MODE_RESTRICT     = "restrict";
    const MODE_CASCADE      = "cascade";
    const MODE_SET_NULL     = "setnull";

    public $mode                    = "restrict";
    public $restrictMessageError    = "Невозможно удалить запись, для начала необходимо удалить все связанные.";

    public $canBeLinkedModels = [];

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE      => "deleteLinkedModels",
        ];
    }

    public function attach($owner)
    {
        $owner->attachBehavior(HasModelRef::className(), [
            "class"  => HasModelRef::className(),
        ]);

        parent::attach($owner);
    }

    /**
     * До удаления сущьности, текущей необходим проверить все описанные модели, и сделать операции с ними (удалить, убрать привязку или ничего не делать кинуть Exception)
     * @throws Exception
     */
    public function deleteLinkedModels()
    {
        if (!$this->canBeLinkedModels)
        {
            return true;
        }

        foreach ($this->canBeLinkedModels as $key => $data)
        {
            $modelData = $this->_parseModelInfo($data);
            $className = $modelData["class"];

            //Ищем все модели, привязанного класса
            if ($models = $className::find()->where($this->owner->getRef()->toArray())->all())
            {
                if ($this->mode == static::MODE_RESTRICT)
                {
                    throw new Exception($this->restrictMessageError);
                }

                if ($this->mode == static::MODE_SET_NULL)
                {
                    //TODO: реализовать
                    throw new Exception("Реализовать");

                    foreach ($models as $model)
                    {

                    }
                }

                foreach ($models as $model)
                {
                    if (!$model->delete())
                    {
                        throw new Exception("Не получилось удалить " . $className . " - " . $model->id);
                    }
                }
            }
        }
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    private function _parseModelInfo($data)
    {
        $result = [];
        if (is_array($data))
        {
            $result = $data;
        } else if (is_string($data)) {
            $result["class"] = $data;
        } else
        {
            throw new Exception("Некорректно сконфигурировано поведение: " . static::className());
        }

        if (!class_exists($result["class"]))
        {
            throw new Exception("Класс не найден: " . $result["class"]);
        }

        if (!is_subclass_of($result["class"], ActiveRecord::className()))
        {
            throw new Exception("Класс {$result["class"]} должен быть дочерним классом: " . ActiveRecord::className());
        }

        return $result;
    }



}