<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */
namespace skeeks\cms\models\behaviors;
use yii\base\Behavior;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Class HasProperties
 * @package skeeks\cms\models\behaviors
 */
class HasProperties extends Behavior
{
    /**
     * @var Model the owner of this behavior
     */
    public $owner;
}