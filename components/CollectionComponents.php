<?php
/**
 * CollectionComponents
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
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

    /**
     * @return Component[]
     */
    public function getComponents()
    {
        if ($this->_components === null)
        {
            $this->_components = [];

            if ($this->components)
            {
                foreach ($this->components as $id => $data)
                {
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
                        $this->_components[$id] = new $class($data);
                    }
                }
            }
        }

        return $this->_components;
    }

    /**
     * @param string $id
     * @return Component|null
     */
    public function getComponent($id)
    {
        $components = $this->getComponents();
        return ArrayHelper::getValue($components, $id);
    }

    /**
     * @return Component[]
     */
    public function all()
    {
        return $this->getComponents();
    }
}