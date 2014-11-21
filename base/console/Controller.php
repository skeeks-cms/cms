<?php
/**
 * Controller
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\base\console;

use skeeks\cms\App;
use Yii;
use yii\console\Controller as YiiController;
use yii\helpers\Console;

class Controller extends YiiController
{
    public function startTool()
    {
        $this->stdout(App::moduleCms()->getDescriptor()->toString() . PHP_EOL);
    }

}