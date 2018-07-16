<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms;

/**
 * @property string $image;
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
interface IHasImage
{
    /**
     * @return string
     */
    public function getImage();

    /**
     * @param string $image
     * @return mixed
     */
    public function setImage($image);
}