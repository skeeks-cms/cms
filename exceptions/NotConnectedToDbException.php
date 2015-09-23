<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 03.03.2015
 */
namespace skeeks\cms\exceptions;

use yii\base\UserException;

/**
 * Class NotConnectedToDbException
 * @package skeeks\cms\exceptions
 */
class NotConnectedToDbException extends UserException
{
    static public $invalidConnectionCodes = [1049, 2002, 1045, 1146, 2002];
}