<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms;

/**
 * @property string $icon;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
interface IHasIcon
{
    /**
     * @return string
     */
    public function getIcon();

    /**
     * @param string $icon
     * @return mixed
     */
    public function setIcon($icon);
}