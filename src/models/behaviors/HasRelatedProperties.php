<?php
/**
 * Наличие свойств в связанных таблицах
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */

namespace skeeks\cms\models\behaviors;

use skeeks\cms\relatedProperties\models\RelatedPropertiesModel;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ErrorHandler;

/**
 * Class HasRelatedProperties
 * @package skeeks\cms\models\behaviors
 */
class HasRelatedProperties extends Behavior
{
    /**
     * @var string связующая модель ( например CmsContentElementProperty::className() )
     */
    public $relatedElementPropertyClassName;

    /**
     * @var string модель свойства ( например CmsContentProperty::className() )
     */
    public $relatedPropertyClassName;

    /**
     * Значения связанных свойств.
     * Вернуться только заданные значения свойств.
     *
     * @return ActiveQuery
     */
    public function getRelatedElementProperties()
    {
        return $this->owner->hasMany($this->relatedElementPropertyClassName, ['element_id' => 'id']);
    }

    /**
     * @return array
     */
    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE => "_deleteRelatedProperties",
        ];
    }

    /**
     * before removal of the model you want to delete related properties
     */
    public function _deleteRelatedProperties()
    {
        $rpm = $this->owner->relatedPropertiesModel;
        $rpm->delete();
    }

    /**
     *
     * Все возможные свойства, для модели.
     * Это может зависеть от группы элемента, или от его типа, например.
     * Для разных групп пользователей можно задать свои свойства, а у пользователя можно заполнять только те поля котоыре заданы для группы к которой он относиться.
     *
     * @return ActiveQuery
     */
    public function getRelatedProperties()
    {
        $className = $this->relatedPropertyClassName;
        $find = $className::find()->orderBy(['priority' => SORT_ASC]); ;
        $find->multiple = true;

        return $find;
    }

    /**
     * @return RelatedPropertiesModel
     */
    public function createRelatedPropertiesModel()
    {
        return new RelatedPropertiesModel([], [
            'relatedElementModel' => $this->owner
        ]);
    }

    /**
     * @var RelatedPropertiesModel
     */
    public $_relatedPropertiesModel = null;

    /**
     * @return RelatedPropertiesModel
     */
    public function getRelatedPropertiesModel()
    {
        if ($this->_relatedPropertiesModel === null) {
            $this->_relatedPropertiesModel = $this->createRelatedPropertiesModel();
        }

        return $this->_relatedPropertiesModel;
    }
}