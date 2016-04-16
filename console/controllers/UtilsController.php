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
     * Удаление старых поисковых запросов
     */
    public function actionClearSearchPhrase()
    {
        if (\Yii::$app->cmsSearch->phraseLiveTime)
        {
            $deleted = CmsSearchPhrase::deleteAll([
                '<=', 'created_at', \Yii::$app->formatter->asTimestamp(time()) - (int) \Yii::$app->cmsSearch->phraseLiveTime
            ]);

            \Yii::info("Удалено поисковых запросов: " . $deleted);
        }
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

        \Yii::info('Agents execute: ' . count($agents), CmsAgent::className());

        if ($agents)
        {
            foreach ($agents as $agent)
            {
                $agent->execute();
            }
        }
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