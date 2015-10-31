<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.02.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
use skeeks\cms\helpers\FileHelper;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\rbac\AuthorRule;
use skeeks\sx\Dir;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Утилита для обновления проекта а так же отдельных его частей.
 *
 * @package skeeks\cms\console\controllers
 */
class UpdateController extends Controller
{
    public $defaultAction = 'all';

    /**
     * @var bool
     * optimize-autoloader оптимизировать автолоадер? (рекоммендуется)
     */
    public $optimize = true;

    /**
     * @var bool
     * Сделать бэкап базы данных, перед обновлением
     */
    public $dbDump = true;

    /**
     * @var bool
     * Использовать опцию композер --profile
     */
    public $profile = true;


    /**
     * @var bool
     * Не задавать вопросы в процессе установки
     */
    public $noInteraction = false;

    /**
     * @var bool
     * Откатить изменнные файлы перед началом установки (если установлен гит)
     */
    public $revertModified = true;

    /**
     * @var string
     * Версия композера, последняя стабильная
     */
    public $composerVersion = "1.0.0-alpha10";

    /**
     * @var string
     * Версия композер assets, последняя стабильная
     */
    public $composerAssetPluginV = "1.0.3";


    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return ArrayHelper::merge(parent::options($actionID), [
            'optimize',
            'composerVersion',
            'composerAssetPluginV',
            'noInteraction',
            'revertModified',
            'profile',
            'dbDump'
        ]);
    }

    /**
     * Полное обновление проекта, с сохранением дампа базы
     */
    public function actionAll()
    {
        if ($this->dbDump)
        {
            //Создание бэкапа базы данных.
            $this->systemCmdRoot("php yii cms/backup/db-execute");
        }

        //Удаление блокирующего файла TODO: rewrite this is
        $this->systemCmdRoot("rm -f composer.lock");

        //Проверка версии композера, его установка если нет
        $this->systemCmdRoot("php yii cms/composer/self-update " . ($this->noInteraction ? "--noInteraction":"" ));
        //Обновление asset plugins composer
        $this->systemCmdRoot("php yii cms/composer/update-asset-plugins " . ($this->noInteraction ? "--noInteraction":"" ));

        if ($this->revertModified)
        {
            //Откатить измененные файлы
            $this->systemCmdRoot("php yii cms/composer/revert-modified-files");
        }


        ob_start();
            system('cd '  . ROOT_DIR . '; COMPOSER_HOME=.composer php composer.phar status');
        $result = ob_get_clean();
        $result = trim($result);

        if ($result)
        {
            $dirs = explode("\n", $result);

            if ($dirs)
            {
                foreach ($dirs as $dirPath)
                {
                    FileHelper::removeDirectory($dirPath);
                }
            }
        }



        $options = [];

        if ($this->optimize)
        {
            $options[] = "-o";
        }

        if ($this->noInteraction)
        {
            $options[] = "--no-interaction";
        }

        if ($this->profile)
        {
            $options[] = "--profile";
        }

        $this->systemCmdRoot('COMPOSER_HOME=.composer php composer.phar update ' . implode(" ", $options) );

        //Генерация файла со списком модулей
        \Yii::$app->cms->generateModulesConfigFile();

        //Установка всех миграций
        $this->systemCmdRoot("php yii cms/db/apply-migrations");

        //Чистка временных диррикторий
        $this->systemCmdRoot("php yii cms/utils/clear-runtimes");


        //Сброс кэша стрктуры базы данных
        \Yii::$app->db->getSchema()->refresh();

        //Обновление привилегий
        $this->systemCmdRoot("php yii cms/rbac/init");

        //Дополнительные действия после обновления
        $this->systemCmdRoot("php yii cms/utils/trigger-after-update");
    }


    /**
     * Установка пакета
     *
     * @param $package skeeks/cms-module:*
     */
    public function actionInstall($package)
    {
        $this->systemCmdRoot("php yii cms/composer/require {$package}");
        $this->systemCmdRoot("php yii cms/update");
    }

    /**
     * Удаление пакета
     *
     * @param $package skeeks/cms-module
     */
    public function actionRemove($package)
    {
        $this->systemCmdRoot("php yii cms/composer/remove {$package}");
        $this->systemCmdRoot("php yii cms/update");
    }
}