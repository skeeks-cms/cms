<?php
/**
 * ModelDescriptor
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use yii\base\Model;
use skeeks\cms\components\ModelActionViews;
use skeeks\cms\components\ModelTypes;

/**
 * Class ModelDescriptor
 * @package skeeks\cms\models
 */
class ModelDescriptor extends ComponentModel
{
    public $modelClass              = null;
    public $types                   = [];
    public $actionViews             = [];
    public $adminControllerRoute    = null;
    public $behaviors               = [];

    /**
     * @var ModelActionViews|null
     */
    protected $_actionViews = null;
    /**
     * @return ModelActionViews|null
     */
    public function getActionViews()
    {
        if ($this->_actionViews === null)
        {
            $this->_actionViews = new ModelActionViews([
                'components' => $this->actionViews
            ]);
        }

        return $this->_actionViews;
    }


    /**
     * @var ModelTypes|null
     */
    protected $_types = null;
    /**
     * @return ModelTypes|null
     */
    public function getTypes()
    {
        if ($this->_types === null)
        {
            $this->_types = new ModelTypes([
                'components' => $this->types
            ]);
        }

        return $this->_types;
    }

}