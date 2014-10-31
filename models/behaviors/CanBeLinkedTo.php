<?php
/**
 * CanBeLinkedTo
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;

use skeeks\cms\base\db\ActiveRecord;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * Class HasLinkedModels
 * @package skeeks\cms\models\behaviors
 */
class CanBeLinkedTo extends Behavior
{
    public $canBeLinkedToModels = [];

    public function events()
    {
        return [
            //BaseActiveRecord::EVENT_BEFORE_DELETE      => "beforeDelete",
            //BaseActiveRecord::EVENT_BEFORE_UPDATE      => "deleteLinkedModels",
        ];
    }

    /**
     * @param ActiveRecord $model
     * @throws \skeeks\sx\Exception
     */
    public function linkTo(ActiveRecord $model)
    {
        $ref = $model->getRef(); //ссылка на объект к которому будем привязываться
        $this->owner->linked_to_model = $ref->getClassName();
        $this->owner->linked_to_value = $ref->getValue();

        $this->owner->save();
    }


}