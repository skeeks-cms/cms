<?php
/**
 * ModelRef
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.02.2015
 */
namespace skeeks\cms\models\helpers;
use skeeks\cms\base\db\ActiveRecord;
/**
 * Class ModelRef
 * @package skeeks\cms\models\helpers
 */
class ModelRef
{
    protected $_code                = null;
    protected $_pkValue             = null;

    /**
     * @param $code
     * @param $pkValue
     */
    public function __construct($code, $pkValue)
    {
        $this->_code        = $code;
        $this->_pkValue     = $pkValue;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            "linked_to_model" => $this->_code,
            "linked_to_value" => $this->_pkValue,
        ];
    }

    /**
     * @param $data
     * @return static
     */
    static public function createFromData($data)
    {
        return new static($data["linked_to_model"], $data["linked_to_value"]);
    }

    /**
     * @param ActiveRecord $model
     * @return static
     */
    static public function createFromModel(ActiveRecord $model)
    {
        $code = \Yii::$app->registeredModels->getCodeByModel($model);
        return new static($code, $model->primaryKey);
    }

    /**
     * @return array|null|\yii\db\ActiveRecord
     */
    public function findModel()
    {
        $className = \Yii::$app->registeredModels->getClassNameByCode($this->_code);

        if (!$className)
        {
            return null;
        }
        /**
         * @var \yii\db\ActiveRecord $className
         */
        $find = $className::find()->where([$className::primaryKey()[0] => $this->_pkValue]);
        return $find->one();
    }


    /**
     * @return string
     */
    public function getCode()
    {
        return (string)$this->_code;

    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->_pkValue;
    }

}


