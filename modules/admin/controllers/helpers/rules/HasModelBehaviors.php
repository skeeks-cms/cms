<?php
/**
 * Дейсвтие сработает только когда у модели которая работает с контроллерам имеются нужне behaviors
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers\rules;
use skeeks\cms\base\db\ActiveRecord;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class HasModelBehaviors extends HasModel
{
    /**
     * @var array|string
     */
    public $behaviors = null;

    public function isAllow()
    {
        if (!parent::isAllow())
        {
            return false;
        }

        if (!$this->behaviors)
        {
            return true;
        }

        $model = $this->controller->getModel();

        $behaviors = $this->behaviors;

        if (is_string($this->behaviors))
        {
            $behaviors = [$this->behaviors];
        }

        if (!$this->_hasBehaviorsForModel($model, $behaviors))
        {
            return false;
        }

        return true;
    }




    /**
     *
     * TODO: нужно выносить часто нужно знать
     *
     * Проверка наличия поведений у модели
     * Если хотя бы одного нету будет false
     *
     * TODO: думаю нужно делать по типу механимзма валидаций skeeks\sx\Validator
     *
     * @param ActiveRecord $model
     * @param array $behaviors
     * @return bool
     */
    protected function _hasBehaviorsForModel(ActiveRecord $model, array $behaviors = [])
    {

        foreach ($behaviors as $behaviorNeed)
        {
            if (!$this->_hasBehaviorForModel($model, $behaviorNeed))
            {
                return false;
            }
        }


        return true;
    }


    /**
     * TODO: нужно выносить часто нужно знать
     *
     * Проверка есть ли у модели поведение
     *
     * @param ActiveRecord $model
     * @param Behavior $behaviorNeed
     * @return bool
     */
    protected function _hasBehaviorForModel(ActiveRecord $model, $behaviorNeed)
    {
        foreach ($model->getBehaviors() as $behavior)
        {
            if ($behavior instanceof $behaviorNeed)
            {
                return true;
            }
        }

        return false;
    }
}