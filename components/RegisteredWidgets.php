<?php
/**
 * RegisteredWidgets
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class RegisterdModels
 * @package skeeks\cms\components
 */
class RegisteredWidgets extends Component
{

    /**
     *
     *
     * 'skeeks\cms\widgets\infoblocks\Infoblocks' =>
    [
        'label'         => 'Список инфоблоков',
        'description'   => 'Виджет который содержит в себе другие инфоблоки',
        'templates'     =>
        [
            'default' =>
            [
                'label' => 'Шаблон по умолчанию'
            ]
        ],
        'enabled'       => true
    ]
     *
     *
     * @var array
     */
    public $widgets           = [];

    public function init()
    {
        parent::init();
    }

    protected $_models = null;

    /**
     * @return Model[]
     */
    public function getModels()
    {
        if ($this->_models === null)
        {
            $this->_models = [];

            if ($this->widgets)
            {
                foreach ($this->widgets as $class => $data)
                {
                    $data['class'] = $class;
                    $this->_models[$class] = new Model($data);
                }
            }
        }

        return $this->_models;
    }

    /**
     * @return Model[]
     */
    public function getEnabledModels()
    {
        $result = [];

        if ($this->getModels())
        {
            foreach ($this->getModels() as $model)
            {
                if ($model->enabled)
                {
                    $result[$model->class] = $model;
                }
            }
        }

        return $result;
    }

    /**
     * @param $classNameWiget
     * @return Model|null
     */
    public function getModel($classNameWiget)
    {
        if ($models = $this->getModels())
        {
            return ArrayHelper::getValue($models, (string) $classNameWiget, null);
        }

        return null;
    }
}