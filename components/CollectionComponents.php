<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
namespace skeeks\cms\components;

use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\models\StorageFile;
use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
abstract class CollectionComponents extends Component
{
    public $components          = [];
    public $componentClassName  = '';

    public function init()
    {
        parent::init();
    }

    protected $_components = null;
    protected $_allLoaded = null;

    /**
     * @return Component[]
     */
    public function getComponents()
    {
        if ($this->_allLoaded === null)
        {
            $this->_components = [];

            if ($this->components)
            {
                foreach ($this->components as $id => $data)
                {
                    $this->_createByConfig($id, $data);
                }
            }

            $this->_allLoaded = true;
        }

        return $this->_components;
    }

    private function _createByConfig($id, $data)
    {
        if (isset($this->_components[$id]))
        {
            return $this->_components[$id];
        }

        $class = $this->componentClassName;

        if (ArrayHelper::getValue($data, 'enabled', true) === true)
        {
            $newClass = ArrayHelper::getValue($data, 'class', false);

            if ($newClass !== false)
            {
                $class = $newClass;
                unset($data['class']);
            }

            $data['id'] = $id;
            $this->_components[$id] = $this->createConponent($class, $data);
        }

        return $this->_components[$id];
    }

    /**
     * @param $class
     * @param $data
     * @return mixed
     */
    public function createConponent($class, $data)
    {
        return new $class($data);
    }

    /**
     * @param string $id
     * @return Component|null
     */
    public function getComponent($id)
    {
        $componentsData = $this->components;
        $data           = ArrayHelper::getValue($componentsData, $id);
        return $this->_createByConfig($id, $data);
    }

    /**
     * @return Component[]
     */
    public function all()
    {
        return $this->getComponents();
    }
}