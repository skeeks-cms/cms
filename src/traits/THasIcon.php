<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\traits;

/**
 * @property $icon;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
trait THasIcon
{
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
}