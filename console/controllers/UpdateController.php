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
    public $composerVersion = "1.0.0-beta1";

    /**
     * @var string
     * Версия композер assets, последняя стабильная
     */
    public $composerAssetPluginV = "1.1.2";


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
        //Генерация файла со списком модулей
        $this->systemCmdRoot("php yii cms/update/generate-config-files");

        //Установка всех миграций
        $this->systemCmdRoot("php yii cms/db/apply-migrations");

        //Чистка временных диррикторий
        $this->systemCmdRoot("php yii cms/utils/clear-runtimes");

        //Чистка asset файлов
        //$this->systemCmdRoot("php yii cms/utils/clear-assets");

        //Сброс кэша стрктуры базы данных
        $this->systemCmdRoot("php yii cms/db/refresh");

        //Обновление привилегий
        $this->systemCmdRoot("php yii cms/rbac/init");

        //Дополнительные действия после обновления
        $this->systemCmdRoot("php yii cms/utils/trigger-after-update");
    }

    public function actionGenerateConfigFiles()
    {
        \Yii::$app->cms->generateModulesConfigFile();
    }


}