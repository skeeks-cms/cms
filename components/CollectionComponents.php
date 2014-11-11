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
        $class = $this->componentClassName;
        if ($this->_components === null)
        {
            $this->_components = [];

            if ($this->components)
            {
                foreach ($this->components as $code => $data)
                {
                    $this->_components[$code] = new $class($data);
                }
            }
        }

        return $this->_components;
    }
}