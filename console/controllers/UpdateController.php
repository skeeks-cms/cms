<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.02.2015
 */
namespace skeeks\cms\console\controllers;

use skeeks\cms\base\console\Controller;
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
     * Не задавать вопросы в процессе установки
     */
    public $noInteraction = false;

    /**
     * @var bool
     * Откатить изменнные файлы перед началом установки
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
    public $composerAssetPluginV = "1.0.2";


    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return ArrayHelper::merge(parent::options($actionID), [
            'optimize', 'composerVersion', 'composerAssetPluginV', 'noInteraction', 'revertModified'
        ]);
    }

    /**
     * Полное обновление проекта, с сохранением дампа базы
     */
    public function actionAll()
    {
        //Создание бэкапа базы данных.
        $this->systemCmdRoot("php yii cms/backup/db-execute");

        //Список сохранненных баз данных
        $this->systemCmdRoot("php yii cms/backup/db-list");

        //Удаление блокирующего файла
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

        //Обновление зависимостей
        $this->systemCmdRoot("php yii cms/composer/update " . ($this->optimize ? " -o ": " ") . ($this->noInteraction ? "--noInteraction":"" ));

        //Генерация файла со списком модулей
        $this->systemCmdRoot("php yii cms/utils/generate-modules-config-file");

        //Установка всех миграций
        $this->systemCmdRoot("php yii cms/db/apply-migrations");

        //Чистка временных диррикторий
        $this->systemCmdRoot("php yii cms/utils/clear-runtimes");


        //Сброс кэша стрктуры базы данных
        $this->systemCmdRoot("php yii cms/db/db-refresh");

        //Обновление привилегий
        $this->systemCmdRoot("php yii cms/rbac/init");
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