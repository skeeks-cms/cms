<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.06.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsAgent;
use skeeks\sx\Dir;
use Yii;
use yii\base\Event;
use yii\console\controllers\HelpController;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Полезные утилиты SkeekS CMS
 *
 * @package skeeks\cms\console\controllers
 */
class UtilsController extends Controller
{
    /**
     * Проверка какие библиотечные файлы были заменены вручную (папка vendor)
     * Не желательно менять библиотечные файлы, поскольку при следующем обновлении все изменения могут быть стерты.
     */
    public function actionVendorStatus()
    {
        $this->systemCmdRoot("php composer.phar status -v");
    }

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
     * Читска временный файлов (runtimes)
     *
     * '@console/runtime'
     * '@common/runtime'
     * '@frontend/runtime'
     *
     */
    public function actionClearRuntimes()
    {
        $dir = new Dir(\Yii::getAlias('@console/runtime'));
        $dir->clear();
        $this->_checkIsEmptyDir($dir);

        $dir = new Dir(\Yii::getAlias('@common/runtime'));
        $dir->clear();
        $this->_checkIsEmptyDir($dir);

        $dir = new Dir(\Yii::getAlias('@frontend/runtime'));
        $dir->clear();
        $this->_checkIsEmptyDir($dir);
    }

    /**
     * Читска временный файлов ('@frontend/web/assets')
     */
    public function actionClearAssets($dirPath = '@frontend/web/assets')
    {
        $dir = new Dir(\Yii::getAlias($dirPath));
        $dir->clear();
        $this->_checkIsEmptyDir($dir);
    }

    /**
     * Проверка папка пустая или нет
     * @param $dirPath
     */
    protected function _checkIsEmptyDir($dirPath)
    {
        if ($dirPath instanceof Dir)
        {
            $dir = $dirPath;
        } else
        {
            $dir = new Dir($dirPath);
        }

        if ($dir->findFiles() || $dir->findDirs())
        {
            $this->stdoutN('Папка assets (' . $dir->getPath() . ') не очищена. В ней остались файлы');
        } else
        {
            $this->stdoutN('Папка assets (' . $dir->getPath() . ') очищена.');
        }
    }

    /**
     * Генерация файла со списком модулей
     */
    public function actionGenerateModulesConfigFile()
    {
        \Yii::$app->cms->generateModulesConfigFile();
    }

    /**
     * Сообщение всем подключенным компонентам, что нужно обновление завершено
     * В этот момент компоненты, могут выполнить какой либо код, например добавить агентов или email событий
     */
    public function actionTriggerAfterUpdate()
    {
        //Загрузка всех компонентов.
        $components = \Yii::$app->getComponents();
        foreach ($components as $id => $data)
        {
            try
            {
                \Yii::$app->get($id);
            } catch (\Exception $e)
            {
                continue;
            }
        }

        \Yii::$app->trigger(Cms::EVENT_AFTER_UPDATE, new Event([
            'name' => Cms::EVENT_AFTER_UPDATE
        ]));
    }

    /**
     * Выполнить агентов
     */
    public function actionAgentsExecute()
    {
        /**
         * Поиск агентов к выполнению
         */
        $agents = CmsAgent::findForExecute()->all();

        Yii::info('Agents execute: ' . count($agents), CmsAgent::className());

        if ($agents)
        {
            foreach ($agents as $agent)
            {
                $agent->execute();
            }
        }

    }

}