<?php
/**
 * Универсальная опция страницы
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\components;

use skeeks\cms\models\PageOption;
use Yii;

/**
 * @method PageOption[]   getComponents()
 * @method PageOption     getComponent($id)
 *
 * Class CollectionComponents
 * @package skeeks\cms\components
 */
class PageOptions extends CollectionComponents
{
    public $componentClassName  = 'skeeks\cms\models\PageOption';
}