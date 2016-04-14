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

use Yii;
use yii\console\Controller as YiiController;
use yii\helpers\Console;

class Controller extends YiiController
{
    /**
     *
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return $this
     */
    public function startTool()
    {
        $this->stdoutN('Yii2 (' . \Yii::getVersion() . ')');
        $this->stdoutN(\Yii::$app->cms->descriptor);
        $this->stdoutN('App:' . \Yii::$app->id);
        $this->hr();
        return $this;
    }

    /**
     * @return $this
     */
    public function hr()
    {
        $this->stdoutN('-----------------------------');
        return $this;
    }

    /**
     * @param $text
     * @return $this
     */
    public function stdoutN($text = '')
    {
        $this->stdout("{$text}" . PHP_EOL);
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function stdoutBlock($text = '')
    {
        $this->stdoutN('');
        $this->stdout(" ****** {$text} *****" . PHP_EOL);
        $this->stdoutN('');
        return $this;
    }

    /**
     * @param $cmd
     */
    public function systemCmd($cmd)
    {
        $this->stdoutN(' - system cmd: ' . $cmd);
        echo \Yii::$app->console->execute($cmd);
    }

    /**
     * @param $cmd
     */
    public function systemCmdRoot($cmd)
    {
        $this->stdoutN(' - system cmd: ' . $cmd);
        //echo \Yii::$app->console->execute("cd " . ROOT_DIR. "; " . $cmd);
        system("cd " . ROOT_DIR. "; " . $cmd);
    }
}