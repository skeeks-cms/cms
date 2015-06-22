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
     *
     * @param int $autoremove стереть существующие файлы и скачать заново
     */
    public function actionAll($autoremove = 0)
    {
        //Создание бэкапа базы данных.
        $this->systemCmdRoot("php yii cms/backup/db-execute");

        //Список сохранненных баз данных
        $this->systemCmdRoot("php yii cms/backup/db-list");

        if ($autoremove)
        {
            $this->stdoutN('    - remove all');

            //$this->systemCmdRoot("rm -rf .composer");
            $this->systemCmdRoot("rm -f composer.lock");
            $this->systemCmdRoot("rm -f composer.phar");
            //$this->systemCmdRoot("rm -rf vendor");
        }


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


        $this->actionMigration();
        $this->actionClearRuntimes();
        $this->actionGenerateModulesConfigFile();
        $this->actionDbRefresh();
        $this->actionRbacUpdate();
    }


    /**
     * Обновление и добавления прав доступа
     */
    public function actionRbacUpdate()
    {
        $this->systemCmdRoot("php yii cms/rbac/init");
    }

    /**
     * Генерация файла со списком модулей
     */
    public function actionGenerateModulesConfigFile()
    {
        \Yii::$app->cms->generateModulesConfigFile();
    }

    /**
     * Инвалидация кэша стуктуры базы данных
     */
    public function actionDbRefresh()
    {
        \Yii::$app->db->getSchema()->refresh();
    }

    /**
     * Читска временный файлов (assets и runtimes)
     */
    public function actionClearRuntimes()
    {
        $dir = new Dir(\Yii::getAlias('@console/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@common/runtime'));
        $dir->clear();

        $dir = new Dir(\Yii::getAlias('@frontend/runtime'));
        $dir->clear();
        $dir = new Dir(\Yii::getAlias('@frontend/web/assets'));
        $dir->clear();
    }

    /**
     * Проведение всех миграций всех подключенных модулей
     */
    public function actionMigration()
    {
        $cmd = "php yii migrate --migrationPath=@skeeks/cms/migrations --interactive=0" ;
        $this->systemCmdRoot($cmd);

        foreach (\Yii::$app->extensions as $code => $data)
        {
            if ($data['alias'])
            {
                foreach ($data['alias'] as $code => $path)
                {
                    $migrationsPath = $path . '/migrations';


                    if (PHP_OS == 'Windows')
                    {
                        $migrationsPath = str_replace("/", "\\", $migrationsPath);
                    }


                    if (is_dir($migrationsPath))
                    {
                        $cmd = "php yii migrate --migrationPath=" . $migrationsPath . '  --interactive=0' ;
                        $this->systemCmdRoot($cmd);
                    }

                }
            }
        }

        $this->systemCmdRoot("php yii migrate --interactive=0");
    }
}