<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms\traits;

use yii\helpers\ArrayHelper;

/**
 * @deprecated
 *
 * @property $name;
 * @property $icon;
 * @property $image;
 */
trait THasInfo
{
    /**
     * @var string
     */
    protected $_name = '';

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string|array $name
     * @return $this
     */
    public function setName($name)
    {
        if (is_array($name)) {
            $this->_name = \Yii::t(
                ArrayHelper::getValue($name, 0),
                ArrayHelper::getValue($name, 1, ''),
                ArrayHelper::getValue($name, 2, []),
                ArrayHelper::getValue($name, 3)
            );
        } else if (is_string($name)) {
            $this->_name = $name;
        }
        
        return $this;
    }


    /**
     * @var string
     */
    protected $_icon = '';

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->_icon;
    }

    /**
     * @param $icon
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->_icon = $icon;
        return $this;
    }


    /**
     * @var string
     */
    protected $_image = '';

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->_image;
    }

    /**
     * @param $image
     * @return $this
     */
    public function setImage($image)
    {
        $this->_image = $image;
        return $this;
    }
}