<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright 2010 SkeekS
 * @date 05.03.2017
 */

namespace skeeks\cms;

/**
 * @deprecated
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
interface IHasInfo
{
    /**
     * Name
     * @return string
     */
    public function getName();

    /**
     * Icon name
     * @return array
     */
    public function getIcon();

    /**
     * Image url
     * @return string
     */
    public function getImage();
}