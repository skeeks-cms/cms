<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsSearchPhrase;
use skeeks\cms\models\StorageFile;
use skeeks\sx\Dir;
use Yii;
use yii\base\Event;
use yii\console\Controller;
use yii\console\controllers\HelpController;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Productivity SkeekS CMS
 *
 * @package skeeks\cms\console\controllers
 */
class UtilsController extends Controller
{
    /**
     * Получение списка всех возможных консольных команд
     * Используется в console ssh для автокомплита
     */
    public function actionAllCmd()
    {
        /**
         * @var $controllerHelp HelpController
         */
        $controllerHelp = \Yii::$app->createController('help')[0];
        $commands = $controllerHelp->getCommands();

        foreach ($controllerHelp->getCommands() as $controller)
        {
            $subController = \Yii::$app->createController($controller)[0];
            $actions = $controllerHelp->getActions($subController);

            if ($actions)
            {
                foreach ($actions as $actionId)
                {
                    $commands[] = $controller . "/" . $actionId;
                }
            }
        };

        $this->stdout(implode("\n", $commands));
    }

    /**
     * Читка всех сгенерированных миниатюр
     */
    public function actionClearAllThumbnails()
    {
        /**
         * @var $files StorageFile[]
         */
        if ($files = StorageFile::find()->all())
        {
            foreach ($files as $file)
            {
                $file->deleteTmpDir();
            }
        }
    }
}