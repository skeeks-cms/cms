<?php
/**
 * AfterLinkedModel
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors\events;

use yii\base\Event;

/**
 * Class AfterLinkedModel
 * @package skeeks\cms\models\behaviors\events
 */
class AfterLinkedModel extends Event
{
    public $model = null;
}