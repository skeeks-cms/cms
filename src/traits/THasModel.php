<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms\traits;

use yii\base\Model;

/**
 * @property Model $model;
 *
 * Class THasModel
 * @package skeeks\cms\traits
 */
trait THasModel
{
    /**
     * @var string
     */
    protected $_model = '';

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @param Model $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->_model = $model;
        return $this;
    }

}