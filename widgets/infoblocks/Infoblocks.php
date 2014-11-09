<?php
/**
 * Infoblocks
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\infoblocks;

use skeeks\cms\base\Widget;
use Yii;

/**
 * Class Infoblocks
 * @package skeeks\cms\widgets\infoblocks
 */
class Infoblocks extends Widget
{
    /**
     * @var array
     */
    public $infoblockIds = [];

    /**
     * @return string
     */
    public function run()
    {
        return $this->infoblockIds;
    }
}
