<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.06.2015
 */
namespace skeeks\cms\widgets\ssh;
use skeeks\cms\components\Cms;
use yii\base\Widget;
use yii\helpers\Json;

/**
 * Class SshConsoleWidget
 * @package skeeks\cms\widgets\ssh
 */
class SshConsoleWidget extends Widget
{
    public $consoleHeight       = "600px";
    public $consoleWidth        = "100%";

    public $enabledTabs             = Cms::BOOL_Y;
    public $enabledTabFastCmd       = Cms::BOOL_Y;
    public $enabledTabHelp          = Cms::BOOL_Y;
    public $enabledTabCmds          = Cms::BOOL_Y;

    public $iframeId            = "";

    public function init()
    {
        parent::init();

        if (!$this->iframeId)
        {
            $this->iframeId = 'sx-iframe-' . $this->id;
        }

    }
    public function run()
    {
        return $this->render('ssh-console', [
            'widget' => $this
        ]);
    }

    /**
     * @return string
     */
    public function getClientOptionsJson()
    {
        return Json::encode([
            'id'                    => $this->id,
            'iframeId'              => $this->iframeId,
        ]);
    }
}