<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.02.2015
 */
namespace skeeks\cms;
use skeeks\cms\modules\admin\components\UrlRule;
use yii\base\Event;
use yii\web\Application;
use yii\web\View;

/**
 * Class ConsoleModule
 * @package skeeks\cms
 */
class ConsoleModule extends Module
{
    public $controllerNamespace = 'skeeks\cms\console\controllers';
}