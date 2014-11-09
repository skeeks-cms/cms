<?php
/**
 * Text
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\text;

use skeeks\cms\base\Widget;
use Yii;

/**
 * Class Text
 * @package skeeks\cms\widgets\text
 */
class Text extends Widget
{
    /**
     * @var null|string
     */
    public $text = null;

    /**
     * @return string
     */
    public function run()
    {
        return (string) $this->text;
    }
}
