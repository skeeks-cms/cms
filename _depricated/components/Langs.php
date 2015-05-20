<?php
/**
 * Langs
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\models\Lang;
use Yii;

/**
 * @method Lang[]   getComponents()
 * @method Lang     getComponent($id)
 *
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
class Langs extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\Lang';
}