<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components\imaging;

use yii\base\BaseObject;
/**
 * @property string $cssAspectRatio
 * 
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class Preview extends BaseObject
{
    /**
     * @var int
     */
    public $width;
    /**
     * @var int
     */
    public $height;
    /**
     * @var string
     */
    public $src;

    /**
     * Соотношение сторон для CSS
     * @return string
     */
    public function getCssAspectRatio()
    {
        $result = "1/1";
        
        if ($this->width > 0 && $this->height > 0) {
            $result = "{$this->width}/{$this->height}";
        }
        
        return $result;
    }
}