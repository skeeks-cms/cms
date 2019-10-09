<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\base;

use skeeks\cms\IHasImage;
use skeeks\cms\IHasName;
use skeeks\cms\traits\THasImage;
use skeeks\cms\traits\THasName;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class ComponentDescriptor extends \yii\base\Component implements IHasName, IHasImage
{
    use THasName;
    use THasImage;

    public $description = "";
    public $keywords = [];

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->name;
    }

}