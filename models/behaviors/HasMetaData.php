<?php
/**
 * HasMetaData
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use yii\base\Behavior;

/**
 * Class HasMetaData
 * @package skeeks\cms\models\behaviors
 */
class HasMetaData extends Behavior
{
    public $canBeLinkedToModels = [];

    public function events()
    {
        return [];
    }
}