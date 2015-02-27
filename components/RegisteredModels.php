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
use skeeks\cms\models\ModelDescriptor;
use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * @method ModelDescriptor[]   getComponents()
 * @method ModelDescriptor     getComponent($id)
 *
 * Class RegisterdModels
 * @package skeeks\cms\components
 */
class RegisteredModels extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\ModelDescriptor';

    /**
     *
     * Получить реальное название класса модели по ее коду
     *
     * @param $code
     * @return bool|string
     */
    public function getClassNameByCode($code)
    {
        $modelData = ArrayHelper::getValue($this->components, $code);

        if (!$modelData)
        {
            return false;
        }

        if (is_array($modelData))
        {
            return ArrayHelper::getValue($modelData, "modelClass", false);
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
        $modelData = (array) ArrayHelper::getValue($this->components, $code);

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
        foreach ($this->components as $code => $modelData)
        {
            if (is_array($modelData))
            {
                $className = ArrayHelper::getValue($modelData, "modelClass", false);
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

        //Если класс явно не определен, пробуем опредилить класс использую is_subclass_of (например если мы переопределили какую нибудь модель в конфиге)

        foreach ($this->components as $code => $modelData)
        {
            if (is_array($modelData))
            {
                $className = ArrayHelper::getValue($modelData, "modelClass", false);
            } else if (is_string($modelData))
            {
                $className = $modelData;
            } else
            {
                continue;
            }

            if ( is_subclass_of($model, $className))
            {
                return (string) $code;
            }
        }

    }


    /**
     * @param $className
     * @return null|ModelDescriptor
     */
    public function getDescriptor($className)
    {
        if ($className instanceof Model)
        {
            $className = $className->className();
        }

        if ($this->getComponents())
        {
            foreach ($this->getComponents() as $id => $component)
            {
                if ($className == $component->modelClass)
                {
                    return $component;
                }
            }
        }

        return null;
    }


    /**
     * @param string $id
     * @return Component|null
     */
    /*public function getComponent($id)
    {
        if ($component = parent::getComponent($id))
        {
            return $component;
        }


    }*/
}