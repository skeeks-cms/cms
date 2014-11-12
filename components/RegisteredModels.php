<?php
/**
 * RegisteredModels
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class RegisterdModels
 * @package skeeks\cms\components
 */
class RegisteredModels extends Component
{
    public $models           = [];

    public function init()
    {
        parent::init();
    }

    /**
     *
     * Получить реальное название класса модели по ее коду
     *
     * @param $code
     * @return bool|string
     */
    public function getClassNameByCode($code)
    {
        $modelData = ArrayHelper::getValue($this->models, $code);

        if (!$modelData)
        {
            return false;
        }

        if (is_array($modelData))
        {
            return ArrayHelper::getValue($modelData, "class", false);
        } else if (is_string($modelData))
        {
            return $modelData;
        }

        return false;
    }

    /**
     * @param $code
     * @return array
     */
    public function getModelDataByCode($code)
    {
        $modelData = (array) ArrayHelper::getValue($this->models, $code);

        if (!$modelData)
        {
            return [];
        }

        return $modelData;
    }


    /**
     *
     * Получить уникальный код по названию модели
     *
     * @param ActiveRecord $model
     * @return string
     */
    public function getCodeByModel(ActiveRecord $model)
    {
        foreach ($this->models as $code => $modelData)
        {
            if (is_array($modelData))
            {
                $className = ArrayHelper::getValue($modelData, "class", false);
            } else if (is_string($modelData))
            {
                $className = $modelData;
            } else
            {
                continue;
            }

            if ($model->className() == $className)
            {
                return (string) $code;
            }
        }
    }

}